<?php

ini_set('session.auto_start', '0');
ini_set('session.save_path', 'php/config/session');
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
if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
	ini_set('session.cookie_secure', '1');
}
if(isset($_COOKIE['RazorphynSupport']) && !is_string($_COOKIE['RazorphynSupport']) || !preg_match('/^[^[:^ascii:];,\s]{26,128}$/',$_COOKIE['RazorphynSupport'])){
	unset($_COOKIE['RazorphynSupport']);
}
session_start(); 

//Session Check
if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800)
	$_SESSION['time']=time();
else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
	session_unset();
	session_destroy();
	header("location: index.php?e=expired");
	exit();
}
if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	header("location: index.php?e=local");
	exit();
}

if(is_file('php/config/setting.txt')) $setting=file('php/config/setting.txt',FILE_IGNORE_NEW_LINES);
if(is_file('php/config/privacy.txt')) $privacy=file('php/config/privacy.txt',FILE_IGNORE_NEW_LINES);
if(is_file('php/config/logo.txt')) $logo=file_get_contents('php/config/logo.txt',FILE_IGNORE_NEW_LINES);

$siteurl=explode('?',curPageURL());
$siteurl=$siteurl[0];
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}
function retrive_ip(){if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];}elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];}else{$ip=$_SERVER['REMOTE_ADDR'];}return $ip;}
function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}

if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);

require_once 'php/translator/class.translation.php';
if(isset($setting[11]) && $setting[11]==0 && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
	$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	if(!is_file('php/translator/lang/'.$lang.'.csv'))
		$lang='en';
}
else if(isset($setting[11]) && $setting[11]!=0){
	$lang=$setting[11];
	if(!is_file('php/translator/lang/'.$lang.'.csv'))
		$lang='en';
}
else 
	$lang='en';
$translate = new Translator($lang,'php/');
	
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		<title><?php if(isset($setting[0])) echo $setting[0];?></title>
		
		<link rel="stylesheet" type="text/css" href="min/?g=css_i&amp;5259487"/>
		<!--[if lt IE 9]><script src="js/html5shiv-printshiv.js"></script><![endif]-->
	</head>
	<body>
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
						<a class="navbar-brand" href='index.php'><?php if(isset($setting[0])) echo $setting[0];?></a>
					</div>
		  
					<div class="collapse navbar-collapse" id="header-nav-collapse">
						<ul class="nav navbar-nav">
							<li class="active"><a href="index.php"><i class="glyphicon glyphicon-home"></i> <?php $translate->__("Home",false); ?></a></li>
							<li><a href="user/faq.php"><i class="glyphicon glyphicon-flag"></i> <?php $translate->__("FAQs",false); ?></a></li>
							<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
								<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="glyphicon glyphicon-folder-close"></i> <?php $translate->__("Tickets",false); ?><b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="user/" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-th-list"></i> <?php $translate->__("Tickets List",false); ?></a>
										</li>
										<li role="presentation">
											<a href="user/newticket.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-folder-close"></i> <?php $translate->__("New Ticket",false); ?></a>
										</li>
										<li role="presentation">
											<a href="user/search.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-search"></i> <?php $translate->__("Search Tickets",false); ?></a>
										</li>
									</ul>
								</li>
								<li><a href="user/setting.php"><i class="glyphicon glyphicon-edit"></i> <?php $translate->__("Account",false); ?>Account</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li class="dropdown" role='button'>
										<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
											<i class="glyphicon glyphicon-eye-open"></i> <?php $translate->__("Administration",false); ?><b class="caret"></b>
										</a>
										<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
											<li role="presentation">
												<a href="user/admin_setting.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-globe"></i> <?php $translate->__("Site Management",false); ?></a>
											</li>
											<li>
												<a href="user/admin_users.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-user"></i> <?php $translate->__("Users",false); ?></a>
											</li>
											<li role="presentation">
												<a href="user/admin_departments.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-briefcase"></i> <?php $translate->__("Departments Management",false); ?></a>
											</li>
											<li role="presentation">
												<a href="user/admin_mail.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-envelope"></i> <?php $translate->__("Mail Settings",false); ?></a>
											</li>
											<li role="presentation">
												<a href="user/admin_payment.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-euro"></i> <?php $translate->__("Payment Setting/List",false); ?></a>
											</li>
											<li role="presentation">
												<a href="user/admin_faq.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-comment"></i> <?php $translate->__("FAQs Management",false); ?></a>
											</li>
											<li role="presentation">
												<a href="user/admin_reported.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-exclamation-sign"></i> <?php $translate->__("Reported Tickets",false); ?></a>
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
			<div class='daddy'>
				<div class="jumbotron" >
					<h1 class="muted pagefun">
						<img id='logo' class='img-responsive' src='<?php if(isset($logo) && !empty($logo)) echo $logo; else echo 'css/images/logo.png'; ?>' alt='<?php if(isset($setting[0])) echo $setting[0];?>' title='<?php if(isset($setting[0])) echo $setting[0];?>'/>
					</h1>
					<h1 class='pagefun'><?php $translate->__("Welcome to the support center",false); ?></h1>
				</div>
				<hr>
				<?php if(isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
					<div class='row form-group main'>
						<h1 class='titlesec'><?php $translate->__("Hello",false); ?> <?php echo htmlspecialchars($_SESSION['name'],ENT_QUOTES,'UTF-8'); ?>!</h1>
						<p><?php $translate->__("You are already logged in, you can start to ask for support",false); ?></p>
					</div>
				<?php } else if(isset($_SESSION['status']) && $_SESSION['status']==4){ ?>
					<div class='row form-group main'>
						<h1 class='titlesec'><?php $translate->__("You Are Banned",false); ?></h1>
						<p><?php $translate->__("Your behaviour was not acceptable",false); ?></p>
					</div>
				<?php } else if(isset($_SESSION['status']) && $_SESSION['status']==3){ ?>
					<div class='row form-group main'>
						<h1 class='titlesec'><?php $translate->__("Activation",false); ?></h1>
						<p><?php $translate->__("You must activate your account to proceed, please check your inbox",false); ?></p>
						<button class='btn btn-info verify' onclick='javascript:veirfy();return !1;'><?php $translate->__("Verify Status",false); ?></button>
						<button class='btn' onclick='javascript:resend();return !1;'><?php $translate->__("Send Mail Again",false); ?></button>
					</div>
				<?php } else { ?>
					<div class='row form-group main'>
						<form id='passwordform' class='login activesec' role='form'>
							<h1 class='titlesec'><?php $translate->__("Login",false); ?></h1>
							<div class='row form-group'>
								<div class='col-md-2'><label for='mail'><?php $translate->__("Email",false); ?></label></div>
								<div class='col-md-3'><input type="email" class='form-control' id="mail" placeholder="Email" required></div>
							</div>
							<div class='row form-group'>
								<div class='col-md-2'><label for='pwd'><?php $translate->__("Password",false); ?></label></div>
								<div class='col-md-3'><input type="password" class='form-control' id="pwd" placeholder="Password" autocomplete="off" required></div>
							</div>
							<input type="submit" onclick='javascript:login();return false;' class="btn btn-success" value='Login'/>
							<br/><br/>
						</form>
						<form class='register' role='form'>
							<h1 class='titlesec'><?php $translate->__("New User",false); ?></h1>
							<div class='row form-group'>
								<div class='col-md-2'><label for='rname'><?php $translate->__("Name",false); ?></label></div>
								<div class='col-md-4'><input type="text" class='form-control'  id="rname" placeholder="Name" autocomplete="off" required></div>
							</div>
							<div class='row form-group'>
								<div class='col-md-2'><label for='rmail'><?php $translate->__("Email",false); ?></label></div>
								<div class='col-md-4'><input type="email" class='form-control' id="rmail" placeholder="Email" autocomplete="off" required></div>
							</div>
							<div class='row form-group'>
								<div class='col-md-2'><label for='rpwd'><?php $translate->__("Password",false); ?></label></div>
								<div class='col-md-4'><input type="password" class='form-control' id="rpwd" placeholder="Password" autocomplete="off" required></div>
								<div class='col-md-2'><label for='rrpwd'><?php $translate->__("Repeat Password",false); ?></label></div>
								<div class='col-md-4'><input type="password" class='form-control' id="rrpwd" placeholder="Repeat Password" autocomplete="off" required></div>
							</div>
							<?php if(isset($privacy[0]) && $privacy[0]==1){ ?>
								<label><?php $translate->__("Privacy Policy",false); ?></label>
								<div class='row form-group'>
									<div class='col-md-12 privacycont'><?php echo $privacy[1]; ?></div>
								</div>
								<div class='row form-group'>
									<div class='col-md-3'><label for='privacy'><?php $translate->__("Do you accept the Privacy policy?",false); ?></label></div>
									<div class='col-md-3'><input type="checkbox" name="privacy" id="privacy" value="1"> <?php $translate->__("Yes",false); ?></div>
								</div>
							<?php } ?>
							<input type="submit" onclick='javascript:register();return false;' class="btn btn-success" value='Register'/>
							<br/><br/>
						</form>
						<form class='pwdres' role='form'>
							<h1 class='titlesec'><?php $translate->__("Forgotten Password",false); ?></h1>
							<div class='row form-group'>
								<div class='col-md-2'><label for='fname'><?php $translate->__("Name",false); ?></label></div>
								<div class='col-md-3'><input type="text"  class='form-control' id="fname" placeholder="Name" autocomplete="off" required></div>
							</div>
							<div class='row form-group'>
								<div class='col-md-2'><label for='fmail'><?php $translate->__("Email",false); ?></label></div>
								<div class='col-md-3'><input type="email" class='form-control' id="fmail" placeholder="Email" autocomplete="off" required></div>
							</div>
							<input type="submit" id='resetpwd' onclick='javascript:return false;' class="btn btn-success" value='Reset Password'/>
							<br/><br/>
						</form>
						
						<div class='row form-group act'>
							<div class='col-md-2' ><span class='opthome' name='login'><?php $translate->__("Login",false); ?></span></div>
							<div class='col-md-2'><span class='opthome' name='register'><?php $translate->__("New User",false); ?></span></div>
							<div class='col-md-2'><span class='opthome' name='pwdres'><?php $translate->__("Forgotten Password",false); ?></span></div>
						</div>
					</div>
				<?php } ?>
				<hr>
			</div>
		</div>

	<script type="text/javascript"  src="min/?g=js_i&amp;5259487"></script>
	
	<script>
	$(document).ready(function() {
		
		<?php if(isset($_GET['e']) && $_GET['e']=='expired'){ ?>
			noty({text: '<?php $translate->__("Your Session has Expired, please log in again",true); ?>',type:'error',timeout:9E3});
		<?php } else if(isset($_GET['e']) && $_GET['e']=='local'){ ?>
			noty({text: '<?php $translate->__("Your IP is different from the one where you have logged in, please log in again",true); ?>',type:'error',timeout:9E3});
		<?php } else if(isset($_GET['e']) && $_GET['e']=='invalid'){ ?>
			noty({text: '<?php $translate->__("Invalid Session ID, please log in again",true); ?>',type:'error',timeout:9E3});
		<?php } else if(isset($_GET['e']) && $_GET['e']=='token'){ ?>
			noty({text: '<?php $translate->__("Invalid Token, please log in again",true); ?>',type:'error',timeout:9E3});
		<?php }  if(isset($_GET['act']) && $_GET['act']=='activate'){ ?>
			$(".main").nimbleLoader("show", {position: "fixed",loaderClass: "loading_bar_body",hasBackground: true,zIndex: 999,backgroundColor: "#fff",backgroundOpacity: 0.9});
			
			$.ajax({
				type: 'POST',
				url: 'php/function.php',
				data: {<?php echo $_SESSION['token']['act']; ?>:'activate_account',key:'<?php echo $_GET['reg']; ?>'},
				dataType : 'json',
				success : function (data) {
					$(".main").nimbleLoader("hide");
					if(data[0]=='Activated'){
						window.location = '<?php echo $siteurl; ?>';
					}
					else
						noty({text: data[0],type:'error',timeout:9E3});
				}
			}).fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});

		<?php } ?>
		
		<?php if(isset($_SESSION['status']) && $_SESSION['status']<=3){ ?>
			setInterval(function(){
				$.ajax({
					type: 'POST',
					url: 'php/admin_function.php',
					async : 'false',
					data: {<?php echo $_SESSION['token']['act']; ?>:'timeout_update'}
				}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
			},1200000);
		<?php } ?>
		
		$(".opthome").on("click", function() { $(".activesec").removeClass("activesec").slideToggle(800); $('form[class*="' + $(this).attr("name") + '"]').slideToggle(800).addClass("activesec") });
		$('.register, .pwdres').slideToggle(400);
		$(document).on('click','#resetpwd', function(){
			$(".main").nimbleLoader("show", {position:"fixed",loaderClass:"loading_bar_body",hasBackground:true,zIndex:999,backgroundColor:"#fff",backgroundOpacity:0.9});
			var mail=$('#fmail').val();
			var name=$('#fname').val();
			if(mail.replace(/\s+/g,'')!='' && name.replace(/\s+/g,'')!=''){
				$.ajax({
					type: 'POST',
					url: 'php/function.php',
					data: {<?php echo $_SESSION['token']['act']; ?>:'forgot',mail: mail,name:name},
					dataType : 'json',
					success : function (data) {
						$(".main").nimbleLoader("hide");
						if(data[0]=='Reset')
							noty({text: '<?php $translate->__("An email has been sent to your inbox",true); ?>',type:'success',timeout:9E3});
						else
							noty({text: data[0],type:'error',timeout:9E3});
					}
				}).fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
			}
			else
				noty({text: '<?php $translate->__("Please complete all the fields",true); ?>',type:'error',timeout:9E3});
		});
	});

	<?php if(isset($_SESSION['status']) && $_SESSION['status']==3){ ?>
		function veirfy(){
			$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});
			$.ajax({
				type: 'POST',
				url: 'php/function.php',
				data: {<?php echo $_SESSION['token']['act']; ?>:'verify'},
				dataType : 'json',
				success : function (data) {
					$(".main").nimbleLoader("hide");
					if(data[0]=='Load'){
						window.location.reload();
					}
					else if(data[0]=='Time'){
						noty({text: '<?php $translate->__("No changes has been made, please wait 5 minutes from your last check",true); ?>',type:'information',timeout:9E3});
					}
					else
						noty({text: data[0],type:'error',timeout:9E3});
				}
			}).fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
		}

		function resend(){
			$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});
			$.ajax({
				type: 'POST',
				url: 'php/function.php',
				data: {<?php echo $_SESSION['token']['act']; ?>:'send_again'},
				dataType : 'json',
				success : function (data) {
					$(".main").nimbleLoader("hide");
					if(data[0]=='Sent'){
						noty({text: '<?php $translate->__("A new Email has been sent, the previous code now is invalid",true); ?>',type:'success',timeout:9E3});
					}
					else
						noty({text: data[0],type:'error',timeout:9E3});
				}
			}).fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
		}
	<?php } ?>
	function register(){
		<?php if(isset($privacy[0]) && $privacy[0]==1){ ?>
		if($('input[name="privacy"]:checked').length > 0){
		<?php } ?>
			var name=$('#rname').val();
			var mail=$('#rmail').val();
			var pwd=$('#rpwd').val();
			var rpwd=$('#rrpwd').val();
			if(name.replace(/\s+/g,'')!='' && mail.replace(/\s+/g,'')!='' && pwd.replace(/\s+/g,'')!='' && pwd===rpwd){
				$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});
				$.ajax({
					type: 'POST',
					url: 'php/function.php',
					data: {<?php echo $_SESSION['token']['act'];?>:'register',name: name,mail: mail,pwd:pwd,rpwd:rpwd},
					dataType : 'json',
					success : function (data) {
						$(".main").nimbleLoader("hide");
						if(data[0]=='Registred'){
							if(data.length>1) alert(data[1]);
							window.location.reload();
						}
						else
							noty({text: data[0],type:'error',timeout:9E3});
					}
				}).fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
			}
			else{
				noty({text: '<?php $translate->__("Empty Field or Password mismatch",true); ?>',type:'error',timeout:9E3});
			}
		<?php if(isset($privacy[0]) && $privacy[0]==1){ ?>
		}
		else
			noty({text: '<?php $translate->__("You must accept our Privacy Policy to proceed",true); ?>',type:'error',timeout:9E3});
		<?php } ?>
	}

	function login(){
		$(".main").nimbleLoader("show",{position:"fixed",loaderClass:"loading_bar_body",hasBackground:!0,zIndex:999,backgroundColor:"#fff",backgroundOpacity:0.9});
		$.ajax({
			type:"POST",
			url:"php/function.php",
			data:{<?php echo $_SESSION['token']['act']; ?>:"login",mail:$("#mail").val(),pwd:$("#pwd").val()},
			dataType:"json",
			success:function(a){
				$(".main").nimbleLoader("hide");
				if("Logged"==a[0]){
					if(typeof a[1] === 'undefined')
						window.location = '<?php echo $siteurl; ?>';
					else
						window.location = a[1];
				}
				else if("sessionerror"==a[0])
					window.location = '<?php echo $siteurl; ?>';
				else
					noty({text:a[0],type:"error",timeout:9E3})
			}
		}).fail(function(a,b){$(".main").nimbleLoader("hide");noty({text:b, type:"error",timeout:9E3})})
	};
	
	function logout(){var request= $.ajax({type: 'POST',url: 'php/function.php',data: {<?php echo $_SESSION['token']['act']; ?>:'logout'},dataType : 'json',success : function (data) {if(data[0]=='logout') window.location.reload();else alert(data[0]);}});request.fail(function(jqXHR, textStatus){alert('Error: '+ textStatus);});}

	</script>
  </body>
</html>