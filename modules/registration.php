<?php
// GET — show the registration form.
if (!defined("insite")) die("no access");

render_page("registration", [
    "title" => lang("reg.title"),
]);
