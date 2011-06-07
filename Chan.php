<?php
class Chan {
	private $db, $get, $post, $site_url;

	public function __construct($db, $site_url = '/', $get = NULL, $post = NULL) {
		$this->db = $db;
		$this->get = $get;
		$this->post = $post;
		$this->site_url = $site_url;

		if (!empty($this->post)) {
			$this->db->dorps->insert(array('dorps' => $this->post), array('safe' => true));
		}
	}

	public function out() {
?>
<!doctype html>
<html>
<head>
<meta name="generator" content="WonkyChan 0.1">
<title>wonkychan</title>
<?php echo $this->style(); ?>
</head>
<body>
	<div id="wonky-contained">
		<?php echo $this->header(); ?>
		<p>
			<h1><a href="<?php echo $this->site_url; ?>">Wonkychan</a></h1>
			<?php echo $this->form(); ?>
			<?php var_dump(iterator_to_array($this->showDorps())); ?>
		</p>
	</div>
</body>
</html>
<?php
	}

	private function header() {
?>
<div class="header">
<?php print_r($this->get); ?>
<?php print_r($this->post); ?>
</div>
<?php
	}

	private function form() {
?>
<form name="dorp" enctype="multipart/form-data" method="post" action="index.php">
<textarea id="words" name="words"></textarea>
<input type="file" name="picture" id="picture" />
<input type="submit" />
</form>
<?
	}

	private function style() {
?>
<link rel="stylesheet" href="blueprint/screen.css" type="text/css" media="screen, projection">
<link rel="stylesheet" href="blueprint/print.css" type="text/css" media="print"> 
<!--[if lt IE 8]>
<link rel="stylesheet" href="blueprint/ie.css" type="text/css" media="screen, projection">
<![endif]-->
<style type="text/css">
	body {
		background-color: purple;
	}
	h1 {
		color: white;
	}
	#wonky-contained {
		width: 960px;
		margin: 0px auto 0px auto;
	}
	.header, .dorps {
		background-color: #9ff904;
		padding: 1em;
	}
</style>
<?php
	}

	private function showDorps() {
		return $this->db->dorps->find(array('words'));
	}
}
?>
