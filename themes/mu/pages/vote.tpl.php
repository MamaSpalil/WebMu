<?php
// =====================================================================
// mu-theme page delegate. The new design's chrome (themes/mu/header +
// footer) wraps the inner page body, while the actual page content is
// rendered by the corresponding template from the legacy "ex" theme so
// every module keeps working unchanged.
// =====================================================================
if (!defined("insite")) die("no access");
require __DIR__ . "/../../ex/pages/" . basename(__FILE__);
