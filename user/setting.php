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
if(isset($_COOKIE['RazorphynSupport']) && !is_string($_COOKIE['RazorphynSupport']) || !preg_match('/^[a-z0-9]{26,40}$/',$_COOKIE['RazorphynSupport'])){
	setcookie(session_name(),'invalid',time()-3600);
	header("location: ../index.php?e=invalid");
	exit();
}
session_start(); 

//Session Check
if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800)
	$_SESSION['time']=time();
else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
	session_unset();
	session_destroy();
	header("location: ../index.php?e=expired");
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
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}
if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);
function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}

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
		<div class="container">
			<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
				<div class='container'>
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#header-nav-collapse">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							</button>
							<a class="navbar-brand" href='../index.php'><?php if(isset($setting[0])) echo $setting[0];?></a>
					</div>
		  
					<div class="collapse navbar-collapse" id="header-nav-collapse">
						<ul class="nav navbar-nav">
							<li><a href="index.php"><i class="glyphicon glyphicon-home"></i>Home</a></li>
							<li><a href="faq.php"><i class="glyphicon glyphicon-flag"></i>FAQs</a></li>
							<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
								<li><a href="newticket.php"><i class="glyphicon glyphicon-file"></i>New Ticket</a></li>
								<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="glyphicon glyphicon-folder-close"></i>Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="index.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation">
											<a href="search.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
								<li class="active" ><a href="setting.php"><i class="glyphicon glyphicon-edit"></i>Settings</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li><a href="users.php"><i class="glyphicon glyphicon-user"></i>Users</a></li>
									<li class="dropdown" role='button'>
										<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
											<i class="glyphicon glyphicon-eye-open"></i>Administration<b class="caret"></b>
										</a>
										<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
											<li role="presentation">
												<a href="admin_setting.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-globe"></i> Site Managment</a>
											</li>
											<li role="presentation">
												<a href="admin_departments.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-briefcase"></i> Deaprtments Managment</a>
											</li>
											<li role="presentation">
												<a href="admin_mail.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-envelope"></i> Mail Settings</a>
											</li>
											<li role="presentation">
												<a href="admin_payment.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-euro"></i> Payment Setting/List</a>
											</li>
											<li role="presentation">
												<a href="admin_faq.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-comment"></i> FAQs Managment</a>
											</li>
											<li role="presentation">
												<a href="admin_reported.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-exclamation-sign"></i> Reported Tickets</a>
											</li>
										</ul>
									</li>
								<?php }} if(isset($_SESSION['name'])){ ?>
									<li><a href='#' onclick='javascript:logout();return false;'><i class="glyphicon glyphicon-off"></i>Logout</a></li>
								<?php } ?>
						</ul>
					</div>
				</div>
			</nav>
			<div class='daddy'>
				<hr>
				<div class="jumbotron" >
					<h1 class='pagefun'>Edit Information</h1>
				</div>
				<hr>
					<form>
						<h3 class='sectname'>Main Information</h3>
						<div class='row form-group'>
							<div class='col-md-2'><label for='usrname'>Name</label></div>
							<div class='col-md-4'><input type="text" class='form-control'  name='usrname' id="usrname" value='<?php echo htmlspecialchars($_SESSION['name'],ENT_QUOTES,'UTF-8'); ?>' placeholder="Name" required/></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-2'><label for='usrname'>Mail</label></div>
							<div class='col-md-4'><input type="text" class='form-control'  id="gna" value='<?php echo $_SESSION['mail']; ?>' placeholder="Mail" required/></div>
							<div class='col-md-2'><label for='enablealert'>Mail Alerts</label></div>
							<div class='col-md-4'><select class='form-control'  id='enablealert'><option value='yes'>Yes</option><option value='no'>No</option></select></div>
						</div>
						<h3 class='sectname'>Change Password</h3>
						<div class='row form-group'>
							<div class='col-md-2'><label for='npass'>Old Password</label></div>
							<div class='col-md-4'><input type="password" class='form-control'  name='opass' id="opass" placeholder="Old Password" autocomplete="off" /></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-2'><label for='npass'>New Password</label></div>
							<div class='col-md-4'><input type="password" class='form-control'  name='npass' id="npass" placeholder="New Password" autocomplete="off" /></div>
							<div class='col-md-2'><label for='ckpass'>Repeat New Password</label></div>
							<div class='col-md-4'><input type="password" class='form-control'  name='ckpass' id="ckpass" placeholder="Repeat New Password" autocomplete="off" /></div>
						</div>
						<br/><br/>
						<input type='submit' onclick='javascript:return false;' class='btn btn-success' id='savesett' value='Save'/>
					</form>
				<hr><br/><br/>
				<div class='row form-group'>
					<div class='col-md-2 col-md-offset-5'><button id='dela' class='btn btn-danger' >Delete Account</button></div>
				</div>
				<br/>
				<div id='delaccform' style='display:none'>
					<div class='row form-group' >
						<div class='col-md-2'><label for='delpass'>Password</label></div>
						<div class='col-md-4'><input type="password" class='form-control'  name='delpass' id="delpass" placeholder="Password" autocomplete="off" required/></div>
						<input type='submit' onclick='javascript:return false;' class='btn btn-danger' id='delacc' value='Delete Account'/>
					</div>
				</div>
			</div>
		</div>

	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="../lib/ckeditor/ckeditor.js"></script>
	<script>
	$(document).ready(function() {
		$('#enablealert option[value="<?php echo $_SESSION['mail_alert'];?>"]').attr('selected','selected');
		$("#savesett").click(function(){
			var a=$("#usrname").val(),
				b=$("#gna").val(),
				e=$("#enablealert").val(),
				f=$("#opass").val(),
				c=$("#npass").val(),
				d=$("#ckpass").val();
			if(""!=a.replace(/\s+/g,"")&&""!=b.replace(/\s+/g,"")){
				if(""!=f.replace(/\s+/g,"")&&""!=c.replace(/\s+/g,"")&&""!=d.replace(/\s+/g,"")){
					if(c==d){
						$.ajax({
							type:"POST",
							url:"../php/function.php",
							data:{<?php echo $_SESSION['token']['act']; ?>:"save_setting",name:a,mail:b,almail:e,oldpwd:f,nldpwd:c,rpwd:d},
							dataType:"json",
							success:function(a){
								if("Saved"==a[0]){
									$('#opass').val(''),
									$('#npass').val(''),
									$('#ckpass').val(''),
									noty({text:"Saved",type:"success", timeout:9E3})
								}
								else if(a[0]=='sessionerror'){
									switch(a[1]){
										case 0:
											window.location.replace("<?php echo $siteurl.'?e=invalid'; ?>");
											break;
										case 1:
											window.location.replace("<?php echo $siteurl.'?e=expired'; ?>");
											break;
										case 2:
											window.location.replace("<?php echo $siteurl.'?e=local'; ?>");
											break;
										case 3:
											window.location.replace("<?php echo $siteurl.'?e=token'; ?>");
											break;
									}
								}
								else
									noty({text:a[0],type:"error",timeout:9E3})}
						}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})
					}
					else
						noty({text:"New Passwords Mismatch",type:"error",timeout:9E3})
				}
				else{
					a=$.ajax({
						type:"POST",
						url:"../php/function.php",
						data:{<?php echo $_SESSION['token']['act']; ?>:"save_setting",name:a,mail:b,almail:e},
						dataType:"json",
						success:function(a){
							if("Saved"==a[0])
								noty({text:"Saved",type:"success",timeout:9E3})
							else if(a[0]=='sessionerror'){
								switch(a[1]){
									case 0:
										window.location.replace("<?php echo $siteurl.'?e=invalid'; ?>");
										break;
									case 1:
										window.location.replace("<?php echo $siteurl.'?e=expired'; ?>");
										break;
									case 2:
										window.location.replace("<?php echo $siteurl.'?e=local'; ?>");
										break;
									case 3:
										window.location.replace("<?php echo $siteurl.'?e=token'; ?>");
										break;
								}
							}
							else
								noty({text:a[0],type:"error",timeout:9E3})}
					}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})
				}
			}
			else
				noty({text:"Empty Field", type:"error",timeout:9E3})
		});
		
		
		$('#dela').click(function(){$("#delaccform").slideToggle(800)});
		
		$('#delacc').click(function(){
			if(confirm('Do oyu really want to delete all your information?')){
				var pas=$("#delpass").val();
				if(pas.replace(/\s+/g,'')!=''){
					$.ajax({
						type: 'POST',
						url: 'php/function.php',
						data: {<?php echo $_SESSION['token']['act']; ?>:'del_account',pas: pas},
						dataType : 'json',
						success : function (a) {
							if(a[0]=='Deleted'){
								noty({text: 'The account has been deleted, bye bye',type:'success',timeout:3E3});
								setTimeout(function() {location.reload();}, 2500);
							}
							else if(a[0]=='sessionerror'){
								switch(a[1]){
									case 0:
										window.location.replace("<?php echo $siteurl.'?e=invalid'; ?>");
										break;
									case 1:
										window.location.replace("<?php echo $siteurl.'?e=expired'; ?>");
										break;
									case 2:
										window.location.replace("<?php echo $siteurl.'?e=local'; ?>");
										break;
									case 3:
										window.location.replace("<?php echo $siteurl.'?e=token'; ?>");
										break;
								}
							}
							else
								noty({text: a[0],type:'error',timeout:9E3});
						}
					}).fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
				}
				else
					noty({text: 'Empty password',type:'error',timeout:9E3});
			}
			else{
				$("#delaccform").slideToggle(800),$("#delpass").val('');
			}
		});
	});
	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>