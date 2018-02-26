<?php
require 'vendor/autoload.php';

require('config.php');
require('Chan.php');

$mongo = new MongoDB\Client('mongodb://'.DB_USER.':'.DB_PASS.'@localhost/'.DB_NAME);

// TODO: use DB_NAME
$db = $mongo->wonkychan;

$chan = new Chan($db, SITE_PATH, SITE_FS_PATH, $_GET, $_POST, $_FILES);

$chan->out();
?>
