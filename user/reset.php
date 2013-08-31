<?php

ini_set('session.auto_start', '0');
ini_set('session.save_path', '../php/config/session');
ini_set('session.hash_function', 'sha512');
ini_set('session.gc_maxlifetime', '1800');
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.entropy_length', '512');
ini_set('session.gc_probability', '20');
ini_set('session.gc_divisor', '100');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_name("RazorphynSupport");
if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
	ini_set('session.cookie_secure', '1');
}
if(isset($_COOKIE['RazorphynSupport']) && !empty($_COOKIE['RazorphynSupport']) && !preg_match('/^[a-z0-9]{26,40}$/',$_COOKIE['RazorphynSupport'])){
	unset($_COOKIE['RazorphynSupport']);
}
session_start(); 



//Session Check
if(isset($_SESSION['time'])){
	header("location: ../index.php?e=exipred");
	exit();
}
else if(!isset($_GET['act']) || $_GET['act']!='resetpass' || !isset($_GET['key']) || strlen($_GET['key'])!=87){
	header("location: ../index.php"); 
	exit();
}
else{
$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="robots" content="noindex,nofollow">
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?></title>

		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_i&amp;5259487' ?>"/>
		
		<!--[if lt IE 9]><script src="../js/html5shiv-printshiv.js"></script><![endif]-->
  </head>
	<body>
		<div class="container">
		<div class='daddy'>
			<div class="navbar navbar-fixed-top">
				<div class="navbar-inner">
					<div class="container">
						<a class="btn btn-navbar hidden-desktop" data-toggle="collapse" data-target=".nav-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</a>
						<a class="brand"><?php if(isset($setting[0])) echo $setting[0];?></a>
						<div class="nav-collapse navbar-responsive-collapse collapse">
							<ul class="nav">
								<li class="active"><a href="#home"><i class="icon-home"></i>Home</a></li>
								<?php if(isset($setting[9]) && $setting[9]==1){?>
									<li><a href="faq.php"><i class="icon-flag"></i>FAQs</a></li>
								<?php } if(isset($_SESSION['status']) && $_SESSION['status']<3){?>
									<li><a href="user/newticket.php"><i class="icon-file"></i>New Ticket</a></li>
									<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="icon-folder-close"></i>Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation" >
											<a href="index.php" tabindex="-1" role="menuitem"><i class="icon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation">
											<a href="search.php" tabindex="-1" role="menuitem"><i class="icon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
									<li><a href="user/setting.php"><i class="icon-edit"></i>Settings</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){?>
									<li><a href="user/users.php"><i class="icon-user"></i>Users</a></li>
									<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="icon-eye-open"></i>Administration<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="admin_setting.php" tabindex="-1" role="menuitem"><i class="icon-globe"></i> Site Managment</a>
										</li>
										<li role="presentation">
											<a href="admin_departments.php" tabindex="-1" role="menuitem"><i class="icon-briefcase"></i> Deaprtments Managment</a>
										</li>
										<li role="presentation">
											<a href="admin_mail.php" tabindex="-1" role="menuitem"><i class="icon-envelope"></i> Mail Settings</a>
										</li>
										<li role="presentation">
											<a href="admin_faq.php" tabindex="-1" role="menuitem"><i class="icon-comment"></i> FAQs Managment</a>
										</li>
										<li role="presentation">
											<a href="flag.php" tabindex="-1" role="menuitem"><i class="icon-exclamation-sign"></i> Reported Tickets</a>
										</li>
									</ul>
								</li>
							<?php }} if(isset($_SESSION['status'])){ ?>
								<li><a href='#' onclick='javascript:logout();return false;'><i class="icon-off"></i>Logout</a></li>
								<?php } ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<hr>
			<div class="jumbotron" >
				<h1 class="muted pagefun"><a href='http://razorphyn.com'><img id='logo' src='../css/images/logo.png' alt='Razorphyn' title='Razorphyn'/></a></h1>
				<h3 class='pagefun'>Welcome to the support center</h3>
			</div>
			<hr>
			<?php if(isset($_GET['key'])){?>
				<div class='row-fluid main'>
					<form id='passwordform' class='login activesec'>
						<h2 class='titlesec'>Reset Password</h2>
						<div class='row-fluid'>
							<div class='span1'><label>Your Email</label></div>
							<div class='span3'><input type="text" id="rmail" placeholder="Email" autocomplete="off" required></div>
						</div>
						<div class='row-fluid'>
							<div class='span1'><label>New Password</label></div>
							<div class='span3'><input type="password" id="npwd" placeholder="New Password" autocomplete="off" required></div>
							<div class='span2'><label>Reapeat New Password</label></div>
							<div class='span3'><input type="password" id="rnpwd" placeholder="Repeat New Password" autocomplete="off" required></div>
						</div>
						<input type="submit" id='resetpass' onclick='javascript:return false;' class="btn btn-success" value='Update Password'/>
					</form>
				</div>
			<?php } else {?>
				
			<?php } ?>
			<hr>
		</div>
	</div>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script>
	 $(document).ready(function() {
	<?php if(isset($_GET['act']) && $_GET['act']=='resetpass' && isset($_GET['key'])){ ?>
		$("#resetpass").click(function () {
			var a = $("#npwd").val(),
				b = $("#rnpwd").val(),
				c = $("#rmail").val();
			"" != a.replace(/\s+/g, "") && a == b ? $.ajax({
				type: "POST",
				url: "../php/function.php",
				data: {<?php echo $_SESSION['token']['act']; ?>: "reset_password",npass: a,rnpass: b,rmail: c,key: "<?php echo htmlspecialchars($_GET['key'],ENT_QUOTES,'UTF-8'); ?>"},
				dataType: "json",
				success: function (a) {
					"Updated" == a[0] ? window.location = "<?php echo dirname(curPageURL()); ?>" : noty({text: a[0],type: "error",timeout: 9E3})
				}
			}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})}) : noty({text: "The passwords don't match",type: "error",timeout: 9E3})
		});	
});
	<?php } ?>
	
	function logout(){var request= $.ajax({type: 'POST',url: '../php/function.php',data: {<?php echo $_SESSION['token']['act']; ?>:'logout'},dataType : 'json',success : function (data) {if(data[0]=='logout') window.location.reload();else alert(data[0]);}});request.fail(function(jqXHR, textStatus){alert('Error: '+ textStatus);});}
	</script>
  </body>
</html>
<?php 
}
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}
function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}

?>