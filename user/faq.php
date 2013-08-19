<?php 
ini_set('session.auto_start', '0');
ini_set('session.hash_function', 'sha512');
ini_set('session.gc_maxlifetime', '1800');
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.entropy_length', '512');
ini_set('session.save_path', '../php/config/session');
ini_set('session.gc_probability', '20');
ini_set('session.gc_divisor', '100');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_name("RazorphynSupport");
session_start();
include_once '../php/config/database.php';

//Session Check
if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800)
	$_SESSION['time']=time();
else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
	session_unset();
	session_destroy();
	header("location: ../index.php?e=exipred");
	exit();
}
else if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	header("location: ../index.php?e=local");
	exit();
}

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
						$list[]="<hr><div class='row-fluid'><div class='row-fluid'><div class='span9 question'>".$q."</div></div><div class='row-fluid'><div class='span9'>".html_entity_decode($a)."</div></div><div class='row-fluid'><div class='offset7 span3'><div class='razorate' data-average='".$r."' data-id='".($id*3823)."'></div></div><div class='span2'><input type='submit' class='btn btn-success faqrate' onclick='javascript:return false;' value='Rate'/></div></div></div>";
				else
					while (mysqli_stmt_fetch($stmt))
						$list[]="<hr><div class='row-fluid'><div class='row-fluid'><div class='span9 question'>".$q."</div></div><div class='row-fluid'><div class='span9'>".html_entity_decode($a)."</div></div><div class='row-fluid'><div class='offset7 span4 reqlogin'><p>To rate this answer, please <a href='../index.php'>Log In or Register</a></div></div>";

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

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?> - View Ticket</title>
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		
		<!--[if lt IE 9]><script src="js/html5shiv-printshiv.js"></script><![endif]-->
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_i&amp;5259487' ?>"/>
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?f=css/jRating.jquery.css&amp;5259487' ?>"/>
		
	</head>
	<body>
		<?php if(isset($error)) echo '<script>alert("'.$error.'");</script>'; ?>
		<div class="container">
			<div class="navbar navbar-fixed-top">
				<div class="navbar-inner">
					<div class="container">
						<a class="btn btn-navbar hidden-desktop" data-toggle="collapse" data-target=".nav-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</a>
						<a class="brand" href='../index.php'><?php if(isset($setting[0])) echo $setting[0];?></a>
						<div class="nav-collapse navbar-responsive-collapse collapse">
							<ul class="nav">
								<li><a href="../index.php"><i class="icon-home"></i>Home</a></li>
									<li class="active"><a href="faq.php"><i class="icon-flag"></i>FAQs</a></li>
								<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
									<li><a href="newticket.php"><i class="icon-file"></i>New Ticket</a></li>
									<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="icon-folder-close"></i>Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="index.php" tabindex="-1" role="menuitem"><i class="icon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation">
											<a href="search.php" tabindex="-1" role="menuitem"><i class="icon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
									<li><a href="setting.php"><i class="icon-edit"></i>Settings</a></li>
								<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li><a href="users.php"><i class="icon-user"></i>Users</a></li>
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
							<?php }} if(isset($_SESSION['name'])){ ?>
								<li><a href='#' onclick='javascript:logout();return false;'><i class="icon-off"></i>Logout</a></li>
							<?php } ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class='daddy'>
				<hr>
				<div class="jumbotron" >
					<h2 class='pagefun'>Frequently Asked Questions</h2>
				</div>
				<br/>
				<?php if(!isset($error)){if(count($list)>0) echo implode(' ',$list); else echo '<hr><p>There is no FAQ</p>'?>
				<?php } else { ?>
					<p style='text-align:center'><?php echo $error; ?></p>
				<?php } ?>
				<hr>
			</div>
		</div>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?f=js/jRating.jquery.js&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="../ckeditor/ckeditor.js"></script>
	<script>
	 $(document).ready(function(){
		$(".razorate").jRating();
	});
	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{act:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():noty({text: a[0],type:'error',timeout:9000});}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>
<?php } else {header("location: ../index.php");exit();} 
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

?>
