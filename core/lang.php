<?php
// =====================================================================
//  Language layer.  Loads lang/<code>.php (returns assoc array of
//  key => translated text), with a sane default for missing keys.
// =====================================================================
if (!defined("insite")) die("no access");

global $LANG;
$LANG = [];

function lang_load($code)
{
    global $LANG, $config;
    $code = preg_replace('~[^a-z]~', '', strtolower((string)$code));
    if ($code === "") $code = "rus";

    // session override (?lang=eng)
    if (!empty($_GET["lang"])) {
        $req = preg_replace('~[^a-z]~', '', strtolower((string)$_GET["lang"]));
        if ($req !== "") {
            $_SESSION["lang"] = $req;
        }
    }
    if (!empty($_SESSION["lang"])) {
        $code = $_SESSION["lang"];
    }

    $path = $config["__root"] . "/lang/" . $code . ".php";
    if (!is_file($path)) {
        $path = $config["__root"] . "/lang/rus.php";
    }
    $LANG = is_file($path) ? (require $path) : [];
    if (!is_array($LANG)) $LANG = [];
    $config["__lang_code"] = is_file($path) ? basename($path, ".php") : "rus";
}

/** Translate a key, with a fallback default. */
function lang($key, $default = null)
{
    global $LANG;
    if (isset($LANG[$key])) return $LANG[$key];
    return $default !== null ? $default : $key;
}
