<?php
// Last forum posts — placeholder, points at the configured forum URL.
if (!defined("insite")) die("no access");
return [
    "url"   => $config["forum"] ?? "#",
    "items" => [],
];
