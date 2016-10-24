<?php
error_reporting(E_ALL&~E_NOTICE);

define('VIEW_ROOT', dirname(__FILE__) . '/..');
define('APP_VIEW_ROOT', dirname(__FILE__));

require_once(VIEW_ROOT . "/compilor/compilor.php");

$C = new Compilor();
if (!$argv[1]) {
    $C->main();
} else {
    echo "compile {$argv[1]} .....";
    $C->compile($argv[1], APP_VIEW_ROOT . "/templates/{$argv[1]}");
}
