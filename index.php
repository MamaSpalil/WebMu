<?php
// =====================================================================
//  WebMu front controller. The single entry point for every request.
//  Routes:  ?m=home|registration|register|login|logout|account|
//           change_password|ranking|character|market|vote|vote_callback|
//           donate|buy|download|about|health|metrics|maintenance
// =====================================================================
define("insite", 1);
require __DIR__ . "/core/init.php";

$m = isset($_GET["m"]) ? preg_replace('~[^a-z_]~', '', strtolower((string)$_GET["m"])) : "home";
if ($m === "") $m = "home";

// CSRF check for every state-changing endpoint.
$state_changing = [
    "register", "login", "logout",
    "change_password", "buy", "vote_callback",
];
if (in_array($m, $state_changing, true) && $_SERVER["REQUEST_METHOD"] === "POST") {
    if (honeypot_tripped() || !csrf_check()) {
        flash_set("error", lang("form.csrf_invalid"));
        // Map POST endpoints back to their visible form page.
        $back = [
            "register"        => "registration",
            "change_password" => "account",
            "vote_callback"   => "vote",
            "buy"             => "donate",
        ];
        redirect("index.php?m=" . ($back[$m] ?? $m));
    }
}

// Tag all errors logged from inside the module with the route name so the
// header notice can tell the user which page failed to read from the DB.
err_push_context($m, "module");

switch ($m) {
    case "home":            require __DIR__ . "/modules/home.php";            break;
    case "registration":    require __DIR__ . "/modules/registration.php";    break;
    case "register":        require __DIR__ . "/modules/register.php";        break;
    case "login":           require __DIR__ . "/modules/login.php";           break;
    case "logout":          require __DIR__ . "/modules/logout.php";          break;
    case "account":         require __DIR__ . "/modules/account.php";         break;
    case "change_password": require __DIR__ . "/modules/change_password.php"; break;
    case "ranking":         require __DIR__ . "/modules/ranking.php";         break;
    case "character":       require __DIR__ . "/modules/character.php";       break;
    case "market":          require __DIR__ . "/modules/market.php";          break;
    case "vote":            require __DIR__ . "/modules/vote.php";            break;
    case "vote_callback":   require __DIR__ . "/modules/vote_callback.php";   break;
    case "donate":          require __DIR__ . "/modules/donate.php";          break;
    case "buy":             require __DIR__ . "/modules/buy.php";             break;
    case "download":        require __DIR__ . "/modules/download.php";        break;
    case "about":           require __DIR__ . "/modules/about.php";           break;
    case "health":          require __DIR__ . "/modules/health.php";          break;
    case "metrics":         require __DIR__ . "/modules/metrics.php";         break;
    case "maintenance":     render_page("maintenance");                       break;
    default:
        http_response_code(404);
        render_page("notfound", ["title" => "404"]);
}
