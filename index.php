<?php
require('config.php');
require('Chan.php');

$mongo = new Mongo('mongodb://'.DB_USER.':'.DB_PASS.'@localhost/'.DB_NAME);
$db = $mongo->selectDB(DB_NAME);

$chan = new Chan($db, SITE_PATH, $_GET, $_POST);

$chan->out();
?>
