<?php
require('config.php');
require('Chan.php');

$mongo = new Mongo('mongodb://'.DB_USER.':'.DB_PASS.'@localhost/'.DB_NAME);
$db = $mongo->selectDB(DB_NAME);

$chan = new Chan($db, SITE_URL, $_GET, $_POST);

$chan->out();
?>
