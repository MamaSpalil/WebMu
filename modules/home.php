<?php
// Home page — renders configured widgets from $config["mainmod"].
if (!defined("insite")) die("no access");

$widgets_csv = (string)($config["mainmod"] ?? "qinfo,strongest,lastinf");
$widgets = array_filter(array_map("trim", explode(",", $widgets_csv)));

$widget_data = [];
foreach ($widgets as $w) {
    $key = preg_replace('~[^a-z0-9_]~', '', strtolower($w));
    $file = __DIR__ . "/widgets/" . $key . ".php";
    if (is_file($file)) {
        // Each widget file returns an array of data.
        $widget_data[$key] = (function ($f) use ($config) {
            return include $f;
        })($file);
    }
}

render_page("home", [
    "title"        => $config["server_name"] ?? "WebMu",
    "widgets"      => $widgets,
    "widget_data"  => $widget_data,
]);
