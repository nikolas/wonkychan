<?php
session_start();
class Chan {
	private $db, $get, $post, $files;
	private $site_path, $site_fs_path;
	private $alert;
	private $route;
	private $adminName;
	private $bgColor;

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

		$this->bgColor = 'purple';
		$cursor = $this->db->chan->find(array('option' => 'bg_color'));
		foreach ($cursor as $obj) {
			if (array_key_exists('val', $obj)) {
				$this->bgColor = $obj['val'];
			}
		}

		if (isset($_SESSION) && array_key_exists('admin_name', $_SESSION)) {
			$this->adminName = $_SESSION['admin_name'];
		} else {
			$this->adminName = null;
		}

		$route = preg_replace("|{$this->site_path}|", '', $_SERVER['REQUEST_URI']);
		$route = preg_replace('/\/\//', '/', $route);
		$route = preg_replace('/^\//', '', $route);
		$this->route = preg_split('/\//', $route);

		if (!empty($this->post)
				&& array_key_exists('ajax', $this->post)
				&& $this->post['ajax'] == 1
		) {
			$message = array();
			if (array_key_exists('method', $this->post)) {
				switch ($this->post['method']) {
					case 'delete_dorp':
						$this->db->dorps->remove(array('picture' => $this->post['pic_file']));
						$message = array(
							'result' => 'success',
							'pic_file' => $this->post['pic_file']
						);
						break;
					default:
						break;
				}
			}
			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Content-type: application/json');
			$message = json_encode($message);
			echo $message;
			exit;
		}
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
		background-color: <?php echo $this->bgColor; ?>;
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
	.header {
		background-color: #9ff904;
		color: black;
		padding: 1em;
	}
</style>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
	$('.dorps')
		.delegate('.pic-container', 'hover', function(event) {
			var $this = $(this);
			if (event.type == 'mouseenter') {
				var pic = $this.find('.pic');
				var pos = pic.offset();

				var left = pos.left + 5;
				var top = pos.top + 5;
				var img = $('<img />');

				img.addClass('delete-button');
				img.css({
					'position': 'absolute',
					'cursor': 'pointer',
					'title': 'Delete',
					'opacity': 0.7,
					'top': top,
					'left': left,
					'z-index': 1000
				});
				img.attr('width', '25px;');
				img.attr('height', '25px;');
				img.attr('src', 'delete-all-tweets.jpg');

				$this.append(img);
			} else {
				$this.find('.delete-button').remove();
			}
		})
		.delegate('.delete-button', 'click', function(event) {
			var $this = $(this);
			var container = $this.closest('.pic-container');

			var pic_file = container.find('img.pic:first').attr('src');
			pic_file = pic_file.replace('<?php echo $this->site_path; ?>/d/', '');
			var data = {
				'ajax': 1,
				'method': 'delete_dorp',
				'pic_file': pic_file
			};
			$.post('<?php echo $this->site_path; ?>/index.php',
				data,
				function(response) {
				},
				'json'
			);

			container.find('.delete-button').remove();
			container.find('img.pic').animate({
				opacity: 0.25,
				left: '+=50',
				height: 'toggle'
			}, 600, function() {
				container.remove();
			});
		});
});
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
			$new_filename = $this->site_fs_path . '/d/' . basename($this->files['picture']['name']);
			if (move_uploaded_file($this->files['picture']['tmp_name'], $new_filename)) {
				$this->alert .= '<span class="success">:)</span>';
				$this->db->dorps
						->insert(array('picture' => basename($this->files['picture']['name'])));
			} else {
				$this->alert .= '<span class="error">woops</span>';
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
				$s .= '<span class="pic-container">';
				$s .= "<img class=\"pic\" src=\"{$this->site_path}/d/{$d['picture']}\" />";
				$s .= '</span>';
			}
		}
		$s .= '</div>';
?>
<script>
$('.header').html('<?php echo $this->alert; ?>');
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

	//creates a 3 character sequence
	private function createSalt() {
		$string = md5(uniqid(rand(), true));
		return substr($string, 0, 3);
	}

	private function showAdmin() {
		if (!empty($this->post) && array_key_exists('formname', $this->post)) {
			switch ($this->post['formname']) {
				case 'admin_create':
					if (strcmp($this->post['admin_name'], '') == 0) {
						$this->alert .= '<span class=error>put in a name</span>';
						break;
					}
					if (strcmp($this->post['admin_pass'], '') == 0) {
						$this->alert .= '<span class="error">put in a password</span>';
						break;
					}
					if (strcmp($this->post['admin_pass'], $this->post['admin_pass_confirm']) != 0) {
						$this->alert .= '<span class="error">passwords dont match</span>';
						break;
					}
					$salt = $this->createSalt();
					$hashed = crypt($this->post['admin_pass'], $salt);
					$r = $this->db->admins
							->insert(array('username' => $this->post['admin_name'], 'password' => $hashed, 'salt' => $salt));
					if ($r == 1) {
						print 'user made :)';
						$_SESSION['admin_name'] = $this->post['admin_name'];
					}
					break;
				case 'admin_login':
					$query = array("username" => $this->post['admin_name']);
					$cursor = $this->db->admins->find($query);
					$cursor->getNext();
					$salt = $p = null;
					foreach ($cursor as $obj) {
						$user = $obj['username'];
						$p = $obj['password'];
						$salt = $obj['salt'];
					}
					$entered = crypt($this->post['admin_pass'], $salt);
					if($p == $entered){
						echo "logged in!\n";
						$_SESSION['admin_name'] = $user;
					}else{
						$location = $this->site_path."/index.php";
						header($location);
					}
					break;
				case 'admin':
					if (array_key_exists('add_forum', $this->post) && !empty($this->post['add_forum'])) {
						$link = urlencode(preg_replace('/ /', '-', strtolower($this->post['add_forum'])));
						$r = $this->db->forums
								->insert(array('link' => $link, 'title' => $this->post['add_forum']));
						if ($r == 1) {
							$this->alert .= '<span class="success">forum added :)</span>';
						}
					}
					if (array_key_exists('bg_color', $this->post) && !empty($this->post['bg_color'])) {
						if ($this->db->chan->find(array('option' => 'bg_color'))->count() < 1) {
							$r = $this->db->chan
									->insert(
											array('option' => 'bg_color',
												'val' => $this->post['bg_color'])
										);
						} else {
							$r = $this->db->chan
									->update(
											array('option' => 'bg_color'),
											array('$set' => array('val' => $this->post['bg_color']))
										);
						}
					}
					break;
				default:
					break;
			}
		}


		if(array_key_exists(1, $this->route) && $this->route[1] == 'logout') {
			session_destroy();
			echo "logged out.\n";
		} else if($this->adminName) {
?>
<a href="<?php echo $this->site_path."/a/logout" ?>">logout</a>
<p>Welcome, <?php echo $this->adminName; ?> :)</p>
<form class="admin" name="admin" method="post" action="<?php echo $this->site_path . '/a/'; ?>">
	<fieldset>
		<legend>GLobal Chan options</legend>
		<label for="bg_color">Background Color</label>
		<input type="text" name="bg_color" value="<?php echo $this->bgColor; ?>" />
	</fieldset>
	<fieldset>
		<legend>Forums</legend>
		<label for="add_forum">Create Forum</label>
		<input type="text" name="add_forum" value="" />
	</fieldset>
<input type="hidden" name="redir" value="a" />
<input type="hidden" name="formname" value="admin" />
<input type="submit" />
</form>
<?php
		} else {
			if ($this->db->admins->find()->count() < 1) {
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
$('.header').html('<?php echo $this->alert; ?>');
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
	<div class="container">
		<?php echo $this->header(); ?>
		<p>
			<h1><a href="<?php echo $this->site_path; ?>"><img src="<?php echo $this->site_path.'/logo.png'?>" /></a></h1>
			<?php echo $this->yield(); ?>
		</p>
	</div>
</body>
</html>
<?php
	}
}
?>
