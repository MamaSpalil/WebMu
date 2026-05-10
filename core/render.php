<?php
// =====================================================================
//  Tiny template renderer.  Loads themes/<theme>/header.tpl.php +
//  pages/<page>.tpl.php + footer.tpl.php with $vars in scope.
// =====================================================================
if (!defined("insite")) die("no access");

function render_page($page, array $vars = [])
{
    global $config;
    $theme_dir = $config["__theme_dir"] ?? ($config["__themes"] . "/ex");
    if (!is_dir($theme_dir)) {
        $theme_dir = $config["__themes"] . "/ex";
    }
    $page_file = $theme_dir . "/pages/" . preg_replace('~[^a-z0-9_]~i', '', $page) . ".tpl.php";
    if (!is_file($page_file)) {
        http_response_code(404);
        $page_file = $theme_dir . "/pages/notfound.tpl.php";
    }

    // Common vars passed to every template.
    $vars["config"]    = $config;
    $vars["user"]      = current_user();
    $vars["flashes"]   = flash_pop();
    $vars["page_id"]   = $page;
    $vars["page_title"] = $vars["title"] ?? ($config["server_name"] ?? "WebMu");

    extract($vars, EXTR_SKIP);
    require $theme_dir . "/header.tpl.php";
    require $page_file;
    require $theme_dir . "/footer.tpl.php";
}
