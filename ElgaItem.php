<?php

if (file_exists(__DIR__ . '/SSI.php')) {
	require_once(__DIR__ . '/SSI.php');
} else {
	die('SSI.php not found');
}

$loader = require_once EXTDIR . '/elga_lib/vendor/autoload.php';
require_once SUBSDIR.'/Elga.subs.php';

global $modSettings;

if (empty($_GET['id'])) {
    header("HTTP/1.0 404 Not Found");
    die('<h1>Not Found</h1>');
}

$id = (int) $_GET['id'];
$file = ElgaSubs::getFile($id);
if (!$file) {
    header("HTTP/1.0 404 Not Found");
    die('<h1>Not Found</h1>');
}

$path = $modSettings['elga_files_path'];
$fpath = isset($_GET['preview']) ? $path . '/' . $file['preview'] :
    ( isset($_GET['thumb']) ? $path . '/' . $file['thumb'] : $path . '/' . $file['fname'] );
$fext = pathinfo($fpath, PATHINFO_EXTENSION);

try {
    $imagine = new Imagine\Imagick\Imagine();
} catch (\Imagine\Exception\RuntimeException $e) {
    $imagine = new \Imagine\Gd\Imagine();
}
$imagine->open($fpath)
   ->show($fext);

ElgaSubs::updateFile($id, 'views = views + 1');

die();


