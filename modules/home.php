<?php
// Home page — renders configured widgets from $config["mainmod"].
if (!defined("insite")) die("no access");

$widgets_csv = (string)($config["mainmod"] ?? "qinfo,strongest,lastinf");
$widgets = array_filter(array_map("trim", explode(",", $widgets_csv)));

// ----- News headlines on the home page -------------------------------
// Latest 5 admin posts + pagination metadata (links go to ?m=news&page=N).
$home_news        = [];
$home_news_total  = 0;
$home_news_pages  = 1;
$home_news_per    = 5;
if (db_table_exists("webmu_news")) {
    $row = db_one("SELECT COUNT(*) AS c FROM webmu_news");
    $home_news_total = $row ? (int)$row["c"] : 0;
    $home_news_pages = max(1, (int)ceil($home_news_total / $home_news_per));
    if ($home_news_total > 0) {
        $home_news = db_all(
            "SELECT TOP 5 id, title, body, author, posted_at, updated_at
               FROM webmu_news
              ORDER BY posted_at DESC, id DESC"
        );
    }
}

$widget_data = [];
foreach ($widgets as $w) {
    $key = preg_replace('~[^a-z0-9_]~', '', strtolower($w));
    $file = __DIR__ . "/widgets/" . $key . ".php";
    if (is_file($file)) {
        // Each widget file returns an array of data. Failures are logged
        // and the widget is silently skipped so one broken widget cannot
        // take the whole home page down.
        err_push_context($key, "widget");
        try {
            $widget_data[$key] = (function ($f) use ($config) {
                return include $f;
            })($file);
        } catch (\Throwable $e) {
            err_log("exception", "widget " . $key . ": " . $e->getMessage(), [
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ]);
            $widget_data[$key] = null;
        }
        err_pop_context();
    }
}

render_page("home", [
    "title"           => $config["server_name"] ?? "WebMu",
    "widgets"         => $widgets,
    "widget_data"     => $widget_data,
    "home_news"       => $home_news,
    "home_news_total" => $home_news_total,
    "home_news_pages" => $home_news_pages,
    "home_news_per"   => $home_news_per,
]);
