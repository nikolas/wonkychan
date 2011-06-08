<?php
require('config.php');
require('Chan.php');

$mongo = new Mongo('mongodb://'.DB_USER.':'.DB_PASS.'@localhost/'.DB_NAME);
$db = $mongo->selectDB(DB_NAME);

if ($db->dorps->find()->count() < 1) {
	$db->createCollection('dorps', true, 10*1024);
}

if ($db->forums->find()->count() < 1) {
	$db->createCollection('forums', true, 10*1024);
}

$chan = new Chan($db, SITE_PATH, SITE_FS_PATH, $_GET, $_POST, $_FILES);

$chan->out();
?>
