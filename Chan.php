<?php
class Chan {
	private $db, $get, $post, $files;
	private $site_path, $site_fs_path;
	private $alert;
	private $route;

	public function __construct(
			$db,
			$site_path = '/',
			$site_fs_path = '.',
			$get = null,
			$post = null,
			$files = null
	) {
		$this->db = $db;
		$this->get = $get;
		$this->post = $post;
		$this->files = $files;
		$this->site_path = preg_replace('/\/$/', '', $site_path);
		$this->site_fs_path = $site_fs_path;
		$this->alert = '';

		$route = preg_replace("|{$this->site_path}|", '', $_SERVER['REQUEST_URI']);
		$route = preg_replace('/\/\//', '/', $route);
		$route = preg_replace('/^\//', '', $route);
		$this->route = preg_split('/\//', $route);

		if (!empty($this->files) && !empty($this->files['picture']['name'])) {
			$new_filename = $this->site_fs_path . '/d/' . basename($files['picture']['name']);
			if (move_uploaded_file($this->files['picture']['tmp_name'], $new_filename)) {
				$this->alert .= ':) ';
				$this->alert .= $this->db->dorps
						->insert(array('picture' => basename($files['picture']['name'])));
			} else {
				$this->alert .= 'woops';
			}
		} else {
			$this->alert .= '>:(';
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
	form.dorp {
		margin-right: 1em;
		padding-right: 1em;
		float: left;
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
		$cursor = $this->db->dorps
				->find()->sort(array('$natural' => -1));
		$cursor->rewind();
		$s = '';
		foreach ($cursor as $d) {
			if (array_key_exists('picture', $d)) {
				$s .= "<img class=\"pic\" src=\"{$this->site_path}/d/{$d['picture']}\" />";
			}
		}
		return $s;
	}

	private function showForum($link) {
		$cursor = $this->db->forums
				->find(array('link' => $link));
		$cursor->rewind();
		$s = '';
		foreach ($cursor as $d) {
			$s .= "yeah this is {$d['title']} uhhhhh";
		}

		$s .= '<table>'
				. '<thead>'
				. '<th>Title</th>'
				. '<th>Author</th>'
				. '<th>Re</th>'
				. '<th>Last Poster</th>'
				. '</thead>';

		$s .= '<tbody>';
		$cursor = $this->db->forumthreads
				->find(array('link' => $link));
		$cursor->rewind();
		if ($cursor->count() < 1) {
			$s .= '<tr>no threads yet mate</tr>';
		}
		$s .= '</tbody>';

		$s .= '</table>';

		return $s;
	}

	private function showForumPostThreadForm() {
?>
<form name="forum" method="get" action="<?php echo $this->site_path; ?>">
<input type="text" name="thread-title" />
</form>
<?php
	}

	private function showForums() {
		$cursor = $this->db->forums
				->find()->sort(array('$natural' => -1));
		$cursor->rewind();
		$s = '';
		foreach ($cursor as $d) {
			if (array_key_exists('link', $d)
					&& array_key_exists('title', $d)
			) {
				$s .= "<a href=\"{$this->site_path}/f/{$d['link']}\" />{$d['title']}</a>";
			}
		}
		return $s;
	}

	private function form() {
?>
<form class="dorp" name="dorp" enctype="multipart/form-data" method="post" action="<?php echo $this->site_path . '/index.php'; ?>">
<input type="file" name="picture" id="picture" />
<br />
<input type="submit" />
</form>
<?php
	}

	private function yield() {
		switch ($this->route[0]) {
			case 'f':
				if (!isset($this->route[1]) || !$this->route[1]) {
					continue;
				}
				return $this->showForum($this->route[1]);
				break;
			default:
				return $this->showForums() . $this->form() . $this->showDorps();
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
