<?php
// =====================================================================
//  News listing page — paginated archive of administrator posts.
//
//  Posts are created/edited/deleted by administrators through the
//  admin panel (?m=admin&sub=news). This module is read-only.
//
//  URL: ?m=news[&page=N]  (1-based page index, 5 posts per page)
// =====================================================================
if (!defined("insite")) die("no access");

$per_page = 5;
$page     = max(1, (int)($_GET["page"] ?? 1));

$news      = [];
$total     = 0;
$no_table  = false;

if (db_table_exists("webmu_news")) {
    $row = db_one("SELECT COUNT(*) AS c FROM webmu_news");
    $total = $row ? (int)$row["c"] : 0;

    $total_pages = max(1, (int)ceil($total / $per_page));
    if ($page > $total_pages) $page = $total_pages;

    $offset = ($page - 1) * $per_page;
    // SQL Server 2012+ supports OFFSET/FETCH NEXT — the rest of the
    // codebase already targets a modern MuOnline schema, so this is safe.
    $news = db_all(
        "SELECT id, title, body, author, posted_at, updated_at
           FROM webmu_news
          ORDER BY posted_at DESC, id DESC
         OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
        [$offset, $per_page]
    );
} else {
    $no_table = true;
}

$total_pages = max(1, (int)ceil(max(0, $total) / $per_page));

render_page("news", [
    "title"        => lang("news.title", "News"),
    "news"         => $news,
    "page"         => $page,
    "per_page"     => $per_page,
    "total"        => $total,
    "total_pages"  => $total_pages,
    "no_table"     => $no_table,
]);
