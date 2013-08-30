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
else if(!isset($_SESSION['status']) || $_SESSION['status']>2){
	 header("location: ../index.php");
	 exit();
}
if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="robots" content="noindex,nofollow">
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?> - Account Settings</title>
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		
		<!--[if lt IE 9]><script src="../js/html5shiv-printshiv.js"></script><![endif]-->
		
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_i&amp;5259487' ?>"/>
		
	</head>
	<body>
		<?php if(isset($_SESSION['status']) && $_SESSION['status']<3){?>
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
								<?php if(isset($setting[9]) && $setting[9]==1){?>
									<li><a href="faq.php"><i class="icon-flag"></i>FAQs</a></li>
								<?php } ?>
								<li><a href="newticket.php"><i class="icon-file"></i>New Ticket</a></li>
								<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="icon-folder-close"></i>Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="index.php" tabindex="-1" role="menuitem"><i class="icon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation" class='active'>
											<a href="search.php" tabindex="-1" role="menuitem"><i class="icon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
								<li class="active"><a href="#"><i class="icon-edit"></i>Settings</a></li>
							<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){?>
								<li><a href="users.php"><i class="icon-user"></i>Users</a></li>
								<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="icon-eye-open"></i>Administration<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="admin.php" tabindex="-1" role="menuitem"><i class="icon-globe"></i> Site Settings</a>
										</li>
										<li role="presentation">
											<a href="flag.php" tabindex="-1" role="menuitem"><i class="icon-exclamation-sign"></i> Reported Tickets</a>
										</li>
									</ul>
								</li>
							<?php } if(isset($_SESSION['status'])){ ?>
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
				<h2 class='pagefun'>Edit Information</h2>
			</div>
			<hr>
				<form>
					<h3 class='sectname'>Main Information</h3>
					<div class='row-fluid'>
						<div class='span2'><label for='usrname'>Name</label></div>
						<div class='span4'><input type="text" name='usrname' id="usrname" value='<?php echo htmlspecialchars($_SESSION['name'],ENT_QUOTES,'UTF-8'); ?>' placeholder="Name" required/></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label for='usrname'>Mail</label></div>
						<div class='span4'><input type="text" id="gna" value='<?php echo $_SESSION['mail']; ?>' placeholder="Mail" required/></div>
						<div class='span2'><label for='enablealert'>Mail Alerts</label></div>
						<div class='span4'><select id='enablealert'><option value='yes'>Yes</option><option value='no'>No</option></select></div>
					</div>
					<h3 class='sectname'>Change Password</h3>
					<div class='row-fluid'>
						<div class='span2'><label for='npass'>Old Password</label></div>
						<div class='span4'><input type="password" name='opass' id="opass" placeholder="Old Password"/></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label for='npass'>New Password</label></div>
						<div class='span4'><input type="password" name='npass' id="npass" placeholder="New Password" autocomplete="off" /></div>
						<div class='span2'><label for='ckpass'>Repeat New Password</label></div>
						<div class='span4'><input type="password" name='ckpass' id="ckpass" placeholder="Repeat New Password" autocomplete="off" /></div>
					</div>
					<br/><br/>
					<input type='submit' onclick='javascript:return false;' class='btn btn-success' id='savesett' value='Save'/>
				</form>
			<hr>
		</div>
		</div>

	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="../lib/ckeditor/ckeditor.js"></script>
	<script>
	$(document).ready(function() {
		$('#enablealert option[value="<?php echo $_SESSION['mail_alert'];?>"]').attr('selected','selected');
		$("#savesett").click(function(){var a=$("#usrname").val(),b=$("#gna").val(),e=$("#enablealert").val(),f=$("#opass").val(),c=$("#npass").val(),d=$("#ckpass").val();""!=a.replace(/\s+/g,"")&&""!=b.replace(/\s+/g,"")?""!=f.replace(/\s+/g,"")&&""!=c.replace(/\s+/g,"")&&""!=d.replace(/\s+/g,"")?c==d?(a=$.ajax({type:"POST",url:"../php/function.php",data:{act:"save_setting",name:a,mail:b,almail:e,oldpwd:f,nldpwd:c,rpwd:d},dataType:"json",success:function(a){"Saved"==a[0]?($('#opass').val(''),$('#npass').val(''),$('#ckpass').val(''),noty({text:"Saved",type:"success", timeout:9E3})):noty({text:a[0],type:"error",timeout:9E3})}}),a.fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})):noty({text:"New Passwords Mismatch",type:"error",timeout:9E3}):(a=$.ajax({type:"POST",url:"../php/function.php",data:{act:"save_setting",name:a,mail:b,almail:e},dataType:"json",success:function(a){"Saved"==a[0]?noty({text:"Saved",type:"success",timeout:9E3}):noty({text:a[0],type:"error",timeout:9E3})}}),a.fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})):noty({text:"Empty Field", type:"error",timeout:9E3})});
	});
	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{act:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
	<?php } else { ?>
		<script>window.location = "<?php echo dirname(dirname(curPageURL())).'/index.php'; ?>";</script>
	<?php } ?>
  </body>
</html>