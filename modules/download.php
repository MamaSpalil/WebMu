<?php
if (!defined("insite")) die("no access");
$downloads = [
    "client" => [
        "name" => $config["client_name"] ?? "MU Full Client (Season 3 Ep.1)",
        "size" => $config["client_size"] ?? "1.2 GB",
        "url"  => $config["client_url"] ?? "#mirror1",
    ],
    "patch" => [
        "name" => $config["patch_name"] ?? "Season 3 Episode 1 Patch",
        "size" => $config["patch_size"] ?? "85 MB",
        "url"  => $config["patch_url"] ?? "#patch",
    ],
    "launcher" => [
        "url" => $config["launcher_url"] ?? "#launcher",
    ],
];
render_page("download", ["title" => lang("nav.download"), "downloads" => $downloads]);
