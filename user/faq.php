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
if(isset($_COOKIE['RazorphynSupport']) && !is_string($_COOKIE['RazorphynSupport']) || !preg_match('/^[^[:^ascii:];,\s]{22,128}$/',$_COOKIE['RazorphynSupport'])){
	setcookie(session_name(),'invalid',time()-3600);
	header("location: ../index.php?e=session");
	exit();
}
session_start(); 

include_once '../php/config/database.php';

//Session Check
if(!isset($_SESSION['status'])){
	$_SESSION['redirect_url']=curPageURL();
	header("location: ../index.php");
	exit();
}

if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800)
	$_SESSION['time']=time();
else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
	session_unset();
	session_destroy();
	header("location: ../index.php?e=expired");
	exit();
}
if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	header("location: ../index.php?e=local");
	exit();
}
$_SESSION['token']['faq']=random_token(7);

if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
if(isset($setting[9]) && $setting[9]==1){
$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
$stmt = $mysqli->stmt_init();
if($stmt){
	$query = "SELECT `id`,`question`,`answer`,`rate` FROM ".$SupportFaqTable." WHERE `active`='1' ORDER BY `position` ASC";
	$prepared = $stmt->prepare($query);
	if($prepared){
		if($stmt->execute()){
			$stmt->store_result();
			$result = $stmt->bind_result($id,$q,$a,$r);
			$list=array();
			if($stmt->num_rows>0){
				if(isset($_SESSION['status']) && $_SESSION['status']<3)
					while (mysqli_stmt_fetch($stmt))
						$list[]="<hr><div class='row'><div class='col-md-8 question'>".$q."</div><div class='col-md-4'><div class='row'><div class='col-md-9'><div class='razorate' data-average='".$r."' data-id='".($id*3823)."'></div></div><div class='col-md-3'><input type='submit' class='btn btn-success faqrate' onclick='javascript:return false;' value='Rate'/></div></div></div><div class='row'><div class='col-md-12'>".html_entity_decode($a)."</div></div></div>";
				else
					while (mysqli_stmt_fetch($stmt))
						$list[]="<hr><div class='row'><div class='col-md-8 question'>".$q."</div><div class='row'><div class='col-md-offset-8 col-md-4 reqlogin'><p>To rate this answer, please <a href='../index.php'>Log In or Register</a></div></div></div><div class='row'><div class='col-md-12'>".html_entity_decode($a)."</div></div>";

			}
		}
		else
			$error=mysqli_stmt_error($stmt);
	}
	else
		$error=mysqli_stmt_error($stmt);
}
else
	$error=mysqli_stmt_error($stmt);
$mysqli->close();

$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];

if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);

require_once '../php/translator/class.translation.php';
if(isset($setting[11]) && $setting[11]==0 && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
	$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	if(!is_file('../php/translator/lang/'.$lang.'.csv'))
		$lang='en';
}
else if(isset($setting[11]) && $setting[11]!=0){
	$lang=$setting[11];
	if(!is_file('../php/translator/lang/'.$lang.'.csv'))
		$lang='en';
}
else 
	$lang='en';
$translate = new Translator($lang,'../php/');

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?> - FAQs</title>
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		
		<!--[if lt IE 9]><script src="js/html5shiv-printshiv.js"></script><![endif]-->
		<link rel="stylesheet" type="text/css" href="../min/?g=css_i&amp;5259487"/>
		<link rel="stylesheet" type="text/css" href="../min/?f=css/jRating.jquery.css&amp;5259487"/>
		
	</head>
	<body>
		<?php if(isset($error)) echo '<script>alert("'.$error.'");</script>'; ?>
		<div class="container">
			<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
				<div class='container'>
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#header-nav-collapse">
							<span class="sr-only"><?php $translate->__("Toggle navigation",false); ?></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<a class="navbar-brand" href='../index.php'><?php if(isset($setting[0])) echo $setting[0];?></a>
					</div>
		  
					<div class="collapse navbar-collapse" id="header-nav-collapse">
						<ul class="nav navbar-nav">
							<li><a href="index.php"><i class="glyphicon glyphicon-home"></i> <?php $translate->__("Home",false); ?></a></li>
							<li><a href="faq.php"><i class="glyphicon glyphicon-flag"></i> <?php $translate->__("FAQs",false); ?></a></li>
							<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
								<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="glyphicon glyphicon-folder-close"></i> <?php $translate->__("Tickets",false); ?><b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="index.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-th-list"></i> <?php $translate->__("Tickets List",false); ?></a>
										</li>
										<li role="presentation">
											<a href="newticket.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-folder-close"></i> <?php $translate->__("New Ticket",false); ?></a>
										</li>
										<li role="presentation">
											<a href="search.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-search"></i> <?php $translate->__("Search Tickets",false); ?></a>
										</li>
									</ul>
								</li>
								<li><a href="setting.php"><i class="glyphicon glyphicon-edit"></i> <?php $translate->__("Account",false); ?></a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li class="dropdown" role='button'>
										<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
											<i class="glyphicon glyphicon-eye-open"></i> <?php $translate->__("Administration",false); ?><b class="caret"></b>
										</a>
										<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
											<li role="presentation">
												<a href="admin_setting.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-globe"></i> <?php $translate->__("Site Management",false); ?></a>
											</li>
											<li>
												<a href="admin_users.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-user"></i> <?php $translate->__("Users",false); ?></a>
											</li>
											<li role="presentation">
												<a href="admin_departments.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-briefcase"></i> <?php $translate->__("Departments Management",false); ?></a>
											</li>
											<li role="presentation">
												<a href="admin_mail.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-envelope"></i> <?php $translate->__("Mail Settings",false); ?></a>
											</li>
											<li role="presentation">
												<a href="admin_payment.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-euro"></i> <?php $translate->__("Payment Setting/List",false); ?></a>
											</li>
											<li role="presentation">
												<a href="admin_faq.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-comment"></i> <?php $translate->__("FAQs Management",false); ?></a>
											</li>
											<li role="presentation">
												<a href="admin_reported.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-exclamation-sign"></i> <?php $translate->__("Reported Tickets",false); ?></a>
											</li>
										</ul>
									</li>
								<?php }} if(isset($_SESSION['name'])){ ?>
									<li><a href='#' onclick='javascript:logout();return false;'><i class="glyphicon glyphicon-off"></i> <?php $translate->__("Logout",false); ?></a></li>
								<?php } ?>
						</ul>
					</div>
				</div>
			</nav>
			<div class='daddy '>
					<hr>
					<div class="jumbotron">
						<h1 class='pagefun'>Frequently Asked Questions</h1>
					</div>
					<br/>
					<input id='tok' type='hidden' value='<?php echo $_SESSION['token']['faq']; ?>' />
					<?php if(!isset($error)){if(count($list)>0) echo implode(' ',$list); else echo '<hr><p>There is no FAQ</p>'?>
					<?php } else { ?>
						<p style='text-align:center'><?php echo $error; ?></p>
					<?php } ?>
					<hr>
			</div>
		</div>
		<script type="text/javascript"  src="../min/?g=js_i&amp;5259487"></script>
		<script type="text/javascript"  src="../min/?f=js/jRating.jquery.js&amp;5259487"></script>
	<script>
	 $(document).ready(function(){
		$(".razorate").jRating();
		<?php if(isset($_SESSION['status']) && $_SESSION['status']<=3){ ?>
			setInterval(function(){
				$.ajax({
					type: 'POST',
					url: '../php/admin_function.php',
					async : 'false',
					data: {<?php echo $_SESSION['token']['act']; ?>:'timeout_update'}
				}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
			},1200000);
		<?php } ?>
	});
	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():noty({text: a[0],type:'error',timeout:9000});}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>
<?php } else {header("location: ../index.php");exit();} 
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}
function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}
function retrive_ip(){if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];}elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];}else{$ip=$_SERVER['REMOTE_ADDR'];}return $ip;}

?>
