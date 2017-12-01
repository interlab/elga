<?php

if (file_exists(__DIR__ . '/SSI.php')) {
	require_once(__DIR__ . '/SSI.php');
} else {
	die('SSI.php not found');
}

// $loader = require_once EXTDIR . '/elga_lib/vendor/autoload.php';
require_once SUBSDIR.'/Elga.subs.php';
require_once CONTROLLERDIR.'/Elga.controller.php';

$obj = new ElgaController();
$obj->action_show();
