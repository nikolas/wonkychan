<?php
class Chan {
	private $db, $get, $post, $files;
	private $site_path, $site_fs_path;
	private $alert;
	private $route;
	private $adminName;

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

		if (isset($_SESSION) && array_key_exists('admin_name', $_SESSION)) {
			$this->adminname = $_SESSION['admin_name'];
		} else {
			$this->adminName = null;
		}

		$route = preg_replace("|{$this->site_path}|", '', $_SERVER['REQUEST_URI']);
		$route = preg_replace('/\/\//', '/', $route);
		$route = preg_replace('/^\//', '', $route);
		$this->route = preg_split('/\//', $route);

		if (!empty($this->post) && array_key_exists('redir', $this->post)) {
			$this->route[0] = $this->post['redir'];
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
	.header {
		background-color: #9ff904;
		color: black;
		padding: 1em;
	}
</style>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
	$('.dorps').delegate('img.pic', 'hover', function(event) {
		//alert(this);
	});
})
</script>
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
		$cursor = $this->db->dorps
				->find()->sort(array('$natural' => -1));
		$cursor->rewind();
		$s = '<div class="dorps">';
		foreach ($cursor as $d) {
			if (array_key_exists('picture', $d)) {
				$s .= "<img class=\"pic\" src=\"{$this->site_path}/d/{$d['picture']}\" />";
			}
		}
		$s .= '</div>';
?>
<script>
$('.header').html('<?php echo $this->alert; ?>')
</script>
<?php
		return $s;
	}

	private function showForum($link) {
		$cursor = $this->db->forums
				->find(array('link' => $link));
		$cursor->rewind();
		$s = '';
		foreach ($cursor as $d) {
			$s .= "{$d['title']}";
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

?>
<script>
var $title = $('title');
$title.text( $title.text() + ' forum' );
</script>
<?php
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

	private function showAdmin() {
		if (!empty($this->post) && array_key_exists('formname', $this->post)) {
			$this->alert = '';
			switch ($this->post['formname']) {
				case 'admin_create':
					if (strcmp($this->post['admin_name'], '') == 0) {
						$this->alert .= 'put in a name';
						break;
					}
					if (strcmp($this->post['admin_pass'], '') == 0) {
						$this->alert .= 'put in a password';
						break;
					}
					if (strcmp($this->post['admin_pass'], $this->post['admin_pass_confirm']) != 0) {
						$this->alert .= 'passwords dont match';
						break;
					}
					$hashed = crypt($this->post['admin_pass']);
					$r = $this->db->admins
							->insert(array('username' => $this->post['admin_name'], 'password' => $hashed));
					if ($r == 1) {
						print 'user made :)';
						session_start();
						print_r($_SESSION);
						$_SESSION['admin_name'] = $this->post['admin_name'];
						print_r($_SESSION);
					}
					break;
				case 'admin_login':
					break;
				case 'admin':
					break;
				default:
					break;
			}
		}

		if ($this->adminName) {
?>
<p>Welcome, <?php echo $this->adminName; ?> :)</p>
<form class="admin" name="admin" method="post" action="<?php echo $this->site_path . '/a/'; ?>">
<input type="hidden" name="redir" value="a" />
<input type="hidden" name="formname" value="admin" />
<input type="submit" />
</form>
<?php
		} else {
			if ($this->db->admins->find()->count() < 1) {
			print_r($this->post);
?>
<p>There's no admin user. Make one :)</p>
<form class="admin_create" name="admin_create" method="post" action="<?php echo $this->site_path . '/a/'; ?>">
<label for="admin_name">Username</label>
<input type="text" name="admin_name" id="admin_name" />
<br />
<label for="admin_pass">Password</label>
<input type="password" name="admin_pass" id="admin_pass" />
<br />
<label for="admin_pass_confirm">Password again</label>
<input type="password" name="admin_pass_confirm" id="admin_pass_confirm" />
<input type="hidden" name="redir" value="a" />
<input type="hidden" name="formname" value="admin_create" />
<br />
<input type="submit" />
</form>
<?php
			} else {
?>
<p>Log in to wonkyadmin</p>
<form class="admin_login" name="admin_login" method="post" action="<?php echo $this->site_path . '/a/'; ?>">
<label for="admin_name">Username</label>
<input type="text" name="admin_name" id="admin_name" />
<br />
<label for="admin_pass">Password</label>
<input type="password" name="admin_pass" id="admin_pass" />
<br />
<input type="hidden" name="redir" value="a" />
<input type="hidden" name="formname" value="admin_login" />
<input type="submit" />
</form>
<?php
			}
		}
?>
<script>
var $title = $('title');
$title.text( $title.text() + ' admin' );
$('.header').html('<?php echo $this->alert; ?>')
</script>
<?php
	}

	private function dorpForm() {
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
			case 'a': // admin
				return $this->showAdmin();
				break;
			case 'f': // forums
				if (!isset($this->route[1]) || !$this->route[1]) {
					continue;
				}
				return $this->showForum($this->route[1]);
				break;
			default:
				return $this->showForums() . $this->dorpForm() . $this->showDorps();
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
