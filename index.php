<?php

ini_set('session.hash_function', 'sha512');
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.gc_maxlifetime', '1800');
ini_set('session.entropy_length', '512');
ini_set('session.gc_probability', '20');
ini_set('session.gc_divisor', '100');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.save_path', 'php/config/session');
session_name("RazorphynSupport");
session_start();
//Session Check
if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800)
	$_SESSION['time']=time();
else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
	session_unset();
	session_destroy();
	header("location: index.php");
	exit();
}
else if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	header("location: index.php");
	exit();
}

if(is_file('php/config/setting.txt')) $setting=file('php/config/setting.txt',FILE_IGNORE_NEW_LINES);

$siteurl=explode('?',curPageURL());
$siteurl=$siteurl[0];
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		<title><?php if(isset($setting[0])) echo $setting[0];?></title>
		
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'min/?g=css_i&amp;5259487' ?>"/>
		<!--[if lt IE 9]><script src="js/html5shiv-printshiv.js"></script><![endif]-->
  </head>
	<body>
	<div class="container">
		<div class="navbar navbar-fixed-top">
				<div class="navbar-inner">
					<div class="container">
						<a class="btn btn-navbar hidden-desktop" data-toggle="collapse" data-target=".nav-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</a>
						<a class="brand" href='index.php'><?php if(isset($setting[0])) echo $setting[0];?></a>
						<div class="nav-collapse navbar-responsive-collapse collapse">
							<ul class="nav">
								<li class="active"><a href="index.php"><i class="icon-home"></i>Home</a></li>
								<li><a href="user/faq.php"><i class="icon-flag"></i>FAQs</a></li>
								<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
									<li><a href="user/newticket.php"><i class="icon-file"></i>New Ticket</a></li>
									<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="icon-folder-close"></i>Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="user/" tabindex="-1" role="menuitem"><i class="icon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation">
											<a href="user/search.php" tabindex="-1" role="menuitem"><i class="icon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
									<li><a href="user/setting.php"><i class="icon-edit"></i>Settings</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li><a href="user/users.php"><i class="icon-user"></i>Users</a></li>
									<li class="dropdown" role='button'>
										<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
											<i class="icon-eye-open"></i>Administration<b class="caret"></b>
										</a>
										<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
											<li role="presentation">
												<a href="user/admin_setting.php" tabindex="-1" role="menuitem"><i class="icon-globe"></i> Site Managment</a>
											</li>
											<li role="presentation">
												<a href="user/admin_departments.php" tabindex="-1" role="menuitem"><i class="icon-briefcase"></i> Deaprtments Managment</a>
											</li>
											<li role="presentation">
												<a href="user/admin_mail.php" tabindex="-1" role="menuitem"><i class="icon-envelope"></i> Mail Settings</a>
											</li>
											<li role="presentation">
												<a href="user/admin_faq.php" tabindex="-1" role="menuitem"><i class="icon-comment"></i> FAQs Managment</a>
											</li>
											<li role="presentation">
												<a href="user/flag.php" tabindex="-1" role="menuitem"><i class="icon-exclamation-sign"></i> Reported Tickets</a>
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
			<div class="jumbotron" >
				<h1 class="muted pagefun"><a href='http://razorphyn.com'><img id='logo' src='css/images/logo.png' alt='Razorphyn' title='Razorphyn'/></a></h1>
				<h3 class='pagefun'>Welcome to the support center</h3>
			</div>
			
			<hr>
			<?php if(isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
				<div class='row-fluid main'>
					<h2 class='titlesec'>Hello <?php echo htmlspecialchars($_SESSION['name'],ENT_QUOTES,'UTF-8'); ?>!</h2>
					<p>You are already logged in, you can start to ask for support.</p>
				</div>
			<?php } else if(isset($_SESSION['status']) && $_SESSION['status']==4){ ?>
				<div class='row-fluid main'>
					<h2 class='titlesec'>You Are Banned</h2>
					<p>Sorry,but your behaviour wasn't acceptable.</p>
				</div>
			<?php } else if(isset($_SESSION['status']) && $_SESSION['status']==3){ ?>
				<div class='row-fluid main'>
					<h2 class='titlesec'>Activation</h2>
					<p>You must activate your account to proceed, please check your inbox.</p>
					<button class='btn btn-info verify' onclick='javascript:veirfy();return !1;'>Verify Status</button>
					<button class='btn' onclick='javascript:resend();return !1;'>Send Mail Again</button>
				</div>
			<?php } else { ?>
				<div class='row-fluid main'>
					<form id='passwordform' class='login activesec'>
						<h2 class='titlesec'>Login</h2>
						<div class='row-fluid'>
							<div class='span2'><label for='mail'>Email</label></div>
							<div class='span3'><input type="email" id="mail" placeholder="Email" required></div>
						</div>
						<div class='row-fluid'>
							<div class='span2'><label for='pwd'>Password</label></div>
							<div class='span3'><input type="password" id="pwd" placeholder="Password" autocomplete="off" required></div>
						</div>
						<input type="submit" onclick='javascript:login();return false;' class="btn btn-success" value='Login'/>
					</form>
					<form class='register'>
						<h2 class='titlesec'>New User</h2>
						<div class='row-fluid'>
							<div class='span2'><label for='rname'>Name</label></div>
							<div class='span4'><input type="text" id="rname" placeholder="Name" autocomplete="off" required></div>
						</div>
						<div class='row-fluid'>
							<div class='span2'><label for='rmail'>Email</label></div>
							<div class='span4'><input type="email" id="rmail" placeholder="Email" autocomplete="off" required></div>
						</div><div class='row-fluid'>
							<div class='span2'><label for='rpwd'>Password</label></div>
							<div class='span4'><input type="password" id="rpwd" placeholder="Password" autocomplete="off" required></div>
							<div class='span2'><label for='rrpwd'>Repeat Password</label></div>
							<div class='span4'><input type="password" id="rrpwd" placeholder="Repeat Password" autocomplete="off" required></div>
						</div>
						<input type="submit" onclick='javascript:register();return false;' class="btn btn-success" value='Register'/>
					</form>
					<form class='sect pwdres'>
						<h2 class='titlesec'>Reset Password</h2>
						<div class='row-fluid'>
							<div class='span2'><label for='fname'>Name</label></div>
							<div class='span3'><input type="text" id="fname" placeholder="Name" autocomplete="off" required></div>
						</div><div class='row-fluid'>
							<div class='span2'><label for='fmail'>Email</label></div>
							<div class='span3'><input type="email" id="fmail" placeholder="Email" autocomplete="off" required></div>
						</div>
						<input type="submit" id='resetpwd' onclick='javascript:return false;' class="btn btn-success" value='Reset Password'/>
					</form>
					<div class='row-fluid act'>
						<div class='span2' ><span class='opthome' name='login'>Login</span></div><div class='span2'><span class='opthome' name='register'>New User</span></div><div class='span2'><span class='opthome' name='pwdres'>Reset Password?</span></div>
					</div>
				</div>
			<?php } ?>
			<hr>
		</div>
	</div>

	<script type="text/javascript"  src="<?php echo $siteurl.'min/?g=js_i&amp;5259487' ?>"></script>
	
	<script>
	$(document).ready(function() {
		
		
		<?php if(isset($_GET['e']) && $_GET['e']=='exipred'){ ?>
			noty({text: 'Your Session has Expired, please log in again',type:'error',timeout:9E3});
		<?php } else if(isset($_GET['e']) && $_GET['e']=='local'){ ?>
			noty({text: 'Your ip is different from the one where you have logged in, please log in again',type:'error',timeout:9E3});
		<?php }  if(isset($_GET['act']) && $_GET['act']=='activate'){ ?>
			$(".main").nimbleLoader("show", {
				position             : "fixed",
				loaderClass          : "loading_bar_body",
				
				hasBackground        : true,
				zIndex               : 999,
				backgroundColor      : "#fff",
				backgroundOpacity    : 0.9
			});
			var request= $.ajax({
				type: 'POST',
				url: 'php/function.php',
				data: {<?php echo $_SESSION['token']['act']; ?>:'activate_account',<?php echo $_SESSION['token']['activate']['key']; ?>:'<?php echo $_GET['reg']; ?>'},
				dataType : 'json',
				success : function (data) {
					$(".main").nimbleLoader("hide");
					if(data[0]=='Activated'){
						window.location = '<?php echo $siteurl; ?>';
					}
					else
						noty({text: data[0],type:'error',timeout:9E3});
				}
			});
			request.fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
	
		<?php } ?>
		$(".opthome").on("click", function() { $(".activesec").removeClass("activesec").slideToggle(800); $('form[class*="' + $(this).attr("name") + '"]').slideToggle(800).addClass("activesec") });
		
		$(document).on('click','#resetpwd', function(){
			$(".main").nimbleLoader("show", {position:"fixed",loaderClass:"loading_bar_body",hasBackground:true,zIndex:999,backgroundColor:"#fff",backgroundOpacity:0.9});
			var mail=$('#fmail').val();
			var name=$('#fname').val();
			if(mail.replace(/\s+/g,'')!='' && name.replace(/\s+/g,'')!=''){
				var request= $.ajax({
					type: 'POST',
					url: 'php/function.php',
					data: {<?php echo $_SESSION['token']['act']; ?>:'forgot',mail: mail,name:name},
					dataType : 'json',
					success : function (data) {
						$(".main").nimbleLoader("hide");
						if(data[0]=='Reset')
							noty({text: 'An email has been sent to your inbox',type:'success',timeout:9E3});
						else
							noty({text: data[0],type:'error',timeout:9E3});
					}
				});
				request.fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
			}
			else
				noty({text: 'Please complete all the fields.',type:'error',timeout:9E3});
		});
	
	});
	<?php if(isset($_SESSION['status']) && $_SESSION['status']==3){ ?>
		function veirfy(){
			$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});
			var request= $.ajax({
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
						noty({text: 'No changes has been made, please wait 5 minutes from your last check',type:'success',timeout:9E3});
					}
					else
						noty({text: data[0],type:'error',timeout:9E3});
				}
			});
			request.fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
		}
		function resend(){
			$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});
			var request= $.ajax({
				type: 'POST',
				url: 'php/function.php',
				data: {<?php echo $_SESSION['token']['act']; ?>:'send_again'},
				dataType : 'json',
				success : function (data) {
					$(".main").nimbleLoader("hide");
					if(data[0]=='Sent'){
						noty({text: 'A new Email has been sent, the previous code now is invalid.',type:'success',timeout:9E3});
					}
					else
						noty({text: data[0],type:'error',timeout:9E3});
				}
			});
			request.fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
		}
	<?php } ?>
	function register(){
		$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});
		var name=$('#rname').val();
		var mail=$('#rmail').val();
		var pwd=$('#rpwd').val();
		var rpwd=$('#rrpwd').val();
		if(name.replace(/\s+/g,'')!='' && mail.replace(/\s+/g,'')!='' && pwd===rpwd){
			var request= $.ajax({
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
			});
			request.fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
		}
		else{
			$(".main").nimbleLoader("hide");
			noty({text: 'Empty Field or Password mismatch',type:'error',timeout:9E3});
		}
	}
	
	function login(){$(".main").nimbleLoader("show",{position:"fixed",loaderClass:"loading_bar_body",debug:!0,hasBackground:!0,zIndex:999,backgroundColor:"#fff",backgroundOpacity:0.9});$.ajax({type:"POST",url:"php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"login",mail:$("#mail").val(),pwd:$("#pwd").val()},dataType:"json",success:function(a){$(".main").nimbleLoader("hide");"Logged"==a[0]? (window.location = '<?php echo $siteurl; ?>'):noty({text:a[0],type:"error",timeout:9E3})}}).fail(function(a,b){$(".main").nimbleLoader("hide");noty({text:b, type:"error",timeout:9E3})})};

	function logout(){var request= $.ajax({type: 'POST',url: 'php/function.php',data: {<?php echo $_SESSION['token']['act']; ?>:'logout'},dataType : 'json',success : function (data) {if(data[0]=='logout') window.location.reload();else alert(data[0]);}});request.fail(function(jqXHR, textStatus){alert('Error: '+ textStatus);});}
	
	</script>
  </body>
</html>
<?php function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}?>