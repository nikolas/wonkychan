<?php
class Chan {
	private $db, $get, $post, $files;
	private $site_path, $site_fs_path;
	private $collection, $alert;
	private $route;

	public function __construct($db, $site_path = '/', $site_fs_path = '.', $get = null, $post = null, $files = null) {
		$this->db = $db;
		$this->get = $get;
		$this->post = $post;
		$this->files = $files;
		$this->site_path = $site_path;
		$this->site_fs_path = $site_fs_path;
		$this->alert = '';

		$route = preg_replace("|{$this->site_path}|", '', $_SERVER['REQUEST_URI']);
		$this->route = preg_split('/\//', $route);

		$this->collection = $this->db->selectCollection('dorps');

		if (!empty($this->post)) {
			if (empty($this->post['words'])) {
				$this->alert .= '>:(';
			} else {
				$this->db->dorps->insert($this->post, array('safe' => true));
			}

			if (!empty($this->files)) {
				$new_filename = $this->site_fs_path . '/d/' . basename($files['picture']['name']);
				if (move_uploaded_file($this->files['picture']['tmp_name'], $new_filename)) {
					$this->alert .= 'img moved';
					$this->db->dorps->update(array('words' => $this->post['words']), array('$set' => array('picture' => basename($files['picture']['name']))));
				} else {
					$this->alert .= 'woops';
				}
			}
		}
	}

	private function style() {
?>
<link rel="stylesheet" href="<?php echo $this->site_path; ?>/blueprint/screen.css" type="text/css" media="screen, projection">
<link rel="stylesheet" href="<?php echo $this->site_path; ?>/blueprint/print.css" type="text/css" media="print"> 
<!--[if lt IE 8]>
<link rel="stylesheet" href="<?php echo $this->site_path; ?>/blueprint/ie.css" type="text/css" media="screen, projection">
<![endif]-->
<style type="text/css">
	body {
		background-color: purple;
		color: white;
	}
	h1, a {
		color: white;
	}
	#wonky-contained {
		width: 960px;
		margin: 0px auto 0px auto;
	}
	.header, .dorps {
		background-color: #9ff904;
		color: black;
		padding: 1em;
	}
</style>
<?php
	}

	private function header() {
?>
<div class="header">
<?php echo $this->alert; ?>
</div>
<?php
	}

	private function showDorps() {
		$cursor = $this->collection->find();
		$cursor->rewind();
		$s = '';
		foreach ($cursor as $d) {
			if (array_key_exists('picture', $d)) {
				$s .= "<img class=\"pic\" src=\"{$this->site_path}/d/{$d['picture']}\" />";
			}
			$s .= "<div class=\"content\">{$d['words']}</div><hr />";
		}
		return $s;
	}

	private function form() {
?>
<form name="dorp" enctype="multipart/form-data" method="post" action="<?php echo $this->site_path; ?>">
<textarea id="words" name="words"></textarea>
<input type="file" name="picture" id="picture" />
<input type="submit" />
</form>
<?php
	}

	private function yield() {
		switch ($this->route[0]) {
			case 'forum':
				return "forum number {$this->route[1]}!";
				break;
			default:
				return $this->form() . $this->showDorps();
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
			<h1><a href="<?php echo $this->site_path; ?>">Wonkychan</a></h1>
			<?php echo $this->yield(); ?>
		</p>
	</div>
</body>
</html>
<?php
	}
}
?>
