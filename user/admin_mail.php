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
if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	header("location: ../index.php?e=local");
	exit();
}
if(!isset($_SESSION['status']) || $_SESSION['status']!=2){
	if(!isset($_SESSION['status']))
		$_SESSION['redirect_url']=curPageURL();
	header("location: ../index.php");
	exit();
}
include_once '../php/mobileESP.php';
$uagent_obj = new uagent_info();
$isMob=$uagent_obj->DetectMobileQuick();
if(is_file('../php/config/mail/stmp.txt')){
	$stmp=file('../php/config/mail/stmp.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$string='<?php'."\n".'$smailservice='.$stmp[0].";\n".'$smailname=\''.$stmp[1]."';\n".'$settingmail=\''.$stmp[2]."';\n".'$smailhost=\''.$stmp[3]."';\n".'$smailport='.$stmp[4].";\n".'$smailssl='.$stmp[5].";\n".'$smailauth='.$stmp[6].";\n".'$smailuser=\''.$stmp[7]."';\n".'$smailpassword=\''.$stmp[8]."';\n ?>";
	file_put_contents('../php/config/mail/stmp.php',$string);
	file_put_contents('../php/config/mail/stmp.txt','');
	unlink('../php/config/mail/stmp.txt');
}

if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
if(is_file('../php/config/mail/stmp.php')) include_once('../php/config/mail/stmp.php');

if(is_file('../php/config/mail/newuser.txt')) $nu=file('../php/config/mail/newuser.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/newreply.txt')) $nr=file('../php/config/mail/newreply.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/newticket.txt')) $nt=file('../php/config/mail/newticket.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/assigned.txt')) $as=file('../php/config/mail/assigned.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/forgotten.txt')) $fo=file('../php/config/mail/forgotten.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];

if(!empty($smailpassword)){
	include_once ('../php/endecrypt.php');
	$e = new Encryption(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$smailpassword = $e->decrypt($smailpassword, $smailenckey);
}
if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);
function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}
function retrive_ip(){if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];}elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];}else{$ip=$_SERVER['REMOTE_ADDR'];}return $ip;}

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
		<meta name="robots" content="noindex,nofollow">
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?> - Admin</title>
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		
		<!--[if lt IE 9]><script src="../js/html5shiv-printshiv.js"></script><![endif]-->

		<link rel="stylesheet" type="text/css" href="../min/?g=css_i&amp;5259487"/>
		<link rel="stylesheet" type="text/css" href="../min/?g=css_d&amp;5259487"/>
		<?php if($isMob) { ?>
			<link rel="stylesheet" type="text/css" href="../min/?g=css_m&amp;5259487"/>
		<?php } ?>
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
									<li class="active dropdown" role='button'>
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
											<li class="active" role="presentation">
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
			<div class='daddy'>
				<hr>
				<div class="jumbotron" >
					<h1 class='pagefun'>Administration - Mail</h1>
				</div>
				<hr>
				<h4 class='sectname'>Mail Setting</h4>
				<form>
					<div class='row  form-group stmpinfo' >
						<div class='col-md-12'><label><strong>SMTP Setting</strong></label></div>
						<div class='row form-group'>
							<div class='col-md-2'><label>Service</label></div>
							<div class='col-md-4'><select class='form-control'  id='stmpserv' ><option value='0'>This Server</option><option value='1'>External Service</option></select></div>
						</div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='stmpname'>Name</label></div>
								<div class='col-md-4'><input class='form-control' id='stmpname' type='text' value='<?php if(isset($smailname)) echo htmlspecialchars($smailname,ENT_QUOTES,'UTF-8');?>' required/></div>
								<div class='col-md-2'><label for='stmpmail'>Mail Address</label></div>
								<div class='col-md-4'><input class='form-control' id='stmpmail' type='email' value='<?php if(isset($settingmail)) echo htmlspecialchars($settingmail,ENT_QUOTES,'UTF-8'); ?>' required /></div>
						</div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='stmphost'>Hostname</label></div>
								<div class='col-md-4'><input class='form-control' id='stmphost' type='text' value='<?php if(isset($smailhost)) echo htmlspecialchars($smailhost,ENT_QUOTES,'UTF-8'); ?>' /></div>
								<div class='col-md-2'><label for='stmpport'>Port</label></div>
								<div class='col-md-4'><input class='form-control' id='stmpport' type='number' value='<?php if(isset($smailport)) echo $smailport; ?>' required/></div>
						</div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='stmpsec'>SSL/TLS</label></div>
								<div class='col-md-4'><select class='form-control'  id='stmpsec' ><option value='0'>No</option><option value='1'>SSL</option><option value='2'>TLS</option></select></div>
						</div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='stmpaut'>Authentication</label></div>
								<div class='col-md-4'><select class='form-control'  id='stmpaut' ><option value='0'>No</option><option value='1'>Yes</option></select></div>
						</div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='stmpusr'>Username</label></div>
								<div class='col-md-4'><input class='form-control' id='stmpusr' type='text' value='<?php if(isset($smailuser)) echo htmlspecialchars($smailuser,ENT_QUOTES,'UTF-8'); ?>' /></div>
								<div class='col-md-2'><label for='stmppas'>Password</label></div>
								<div class='col-md-4'><input class='form-control' id='stmppas' type='password' value='<?php if(isset($smailpassword)) echo htmlspecialchars($smailpassword,ENT_QUOTES,'UTF-8'); ?>' autocomplete="off" /></div>
						</div>
						<br/>
						<input type='submit' id='savestmp' onclick='javascript:return false;' value='Save' class='btn btn-success'/>
					</div>
				</form>
				<br/><br/>
				<h4 class='sectname'>Mail Template</h4>
				<form>
					<div class='row form-group'>
						<div class='col-md-12'><label><strong>New Member</strong></label></div>
						<div class='row form-group'>
								<div class='col-md-2'><label  for='nmsub'>Subject</label></div>
								<div class='col-md-4'><input id='nmsub' class='form-control mailsubject' type='text' value='<?php if(isset($nu[0])) echo htmlspecialchars($nu[0],ENT_QUOTES,'UTF-8');?>' required /></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-12'><textarea class='mailmessage' id='newmememess' rows="5" placeholder='Welcome Message' required><?php if(isset($nu[1])) echo $nu[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success newuser submailbody'/>
					</div>
				</form>
				<br/><br/>
				<form>
					<div class='row form-group'>
						<div class='col-md-12'><label><strong>New Reply</strong></label></div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='nrsub'>Subject</label></div>
								<div class='col-md-4'><input id='nrsub' class='form-control mailsubject' type='text' value='<?php if(isset($nr[0])) echo htmlspecialchars($nr[0],ENT_QUOTES,'UTF-8');?>' required/></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-12'><textarea class='mailmessage' id='newreplymess' rows="5" placeholder='New Reply Message' required><?php if(isset($nr[1])) echo $nr[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success newreply submailbody'/>
					</div>
				</form>
				<br/><br/>
				<form>
					<div class='row form-group'>
						<div class='col-md-12'><label><strong>New Ticket</strong></label></div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='ntsub'>Subject</label></div>
								<div class='col-md-4'><input id='ntsub' class='form-control mailsubject' type='text' value='<?php if(isset($nt[0])) echo  htmlspecialchars($nt[0],ENT_QUOTES,'UTF-8');?>' required/></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-12'><textarea class='mailmessage' id='newticketmess' rows="5" placeholder='New Ticket Message' required><?php if(isset($nt[1])) echo $nt[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success newticket submailbody'/>

					</div>
				</form>
				<br/><br/>
				<form>
					<div class='row form-group'>
						<div class='col-md-12'><label><strong>Assigned Ticket</strong></label></div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='atsub'>Subject</label></div>
								<div class='col-md-4'><input id='atsub' class='form-control mailsubject' type='text' value='<?php if(isset($as[0])) echo  htmlspecialchars($as[0],ENT_QUOTES,'UTF-8');?>' required/></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-12'><textarea class='mailmessage' id='assignedmess' rows="5" placeholder='Assigned Ticket Message' required><?php if(isset($as[1])) echo $as[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success newassignment submailbody'/>
					</div>
				</form>
				<form>
					<div class='row form-group'>
						<div class='col-md-12'><label><strong>Password Forgot</strong></label></div>
						<div class='row form-group'>
								<div class='col-md-2'><label for='pfsub'>Subject</label></div>
								<div class='col-md-4'><input id='pfsub' class='form-control mailsubject' type='text' value='<?php if(isset($fo[0])) echo  htmlspecialchars($fo[0],ENT_QUOTES,'UTF-8');?>' required/></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-12'><textarea class='mailmessage' id='forgotmess' rows="5" placeholder='Assigned Ticket Message' required><?php if(isset($fo[1])) echo $fo[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success forgotmess submailbody'/>
					</div>
				</form>
				<br/><br/>
			</div>
			<br/><br/>
		</div>
	
	<?php if(!$isMob) { ?>
		<script type="text/javascript"  src="../min/?g=js_i&amp;5259487"></script>
		<script type="text/javascript"  src="../lib/ckeditor/ckeditor.js"></script>
	<?php }else { ?>
		<script type="text/javascript"  src="../min/?g=js_i&amp;5259487"></script>
		<script type="text/javascript"  src="../min/?g=js_m&amp;5259487"></script>
	<?php } ?>
	
	<script>
	 $(document).ready(function() {
		
		<?php if(!$isMob) { ?>
			CKEDITOR.replace('newmememess');CKEDITOR.replace('newreplymess');CKEDITOR.replace('newticketmess');CKEDITOR.replace('assignedmess');CKEDITOR.replace('forgotmess');				
		<?php }else { ?>
			$("#newmememess").wysihtml5(),$("#newreplymess").wysihtml5(),$("#newticketmess").wysihtml5(),$("#assignedmess").wysihtml5(),$("#forgotmess").wysihtml5();
		<?php } ?>
		
		<?php if(isset($stmpserv)){ ?>
			$('#stmpsec').val(<?php echo $stmpserv; ?>);
		<?php }if(isset($smailssl)){ ?>
			$('#stmpsec').val(<?php echo $smailssl; ?>);
		<?php } if(isset($smailauth)){ ?>
			$('#stmpaut').val(<?php echo $smailauth; ?>);
		<?php } ?>
		
		setInterval(function(){
			$.ajax({
				type: 'POST',
				url: '../php/admin_function.php',
				async : 'false',
				data: {<?php echo $_SESSION['token']['act']; ?>:'timeout_update'}
			}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
		},1200000);
		
		$(document).on('click','.submailbody',function(){
			var subject=$(this).parent().find(".mailsubject").val().replace(/\s+/g," ");
			<?php if(!$isMob) { ?>
				if($(this).hasClass("newuser"))var message=CKEDITOR.instances.newmememess.getData().replace(/\s+/g," "),sec=0;else $(this).hasClass("newreply")?(message=CKEDITOR.instances.newreplymess.getData().replace(/\s+/g," "),sec=1):$(this).hasClass("newticket")?(message=CKEDITOR.instances.newticketmess.getData().replace(/\s+/g," "),sec=2):$(this).hasClass("newassignment")?(message=CKEDITOR.instances.assignedmess.getData().replace(/\s+/g," "),sec=3):$(this).hasClass("forgotmess")&&(message=CKEDITOR.instances.forgotmess.getData().replace(/\s+/g, " "),sec=4);			
			<?php }else { ?>
				if($(this).hasClass('newuser')){
					var message=$("#newmememess").val().replace(/\s+/g,' ');
					var sec=0;
				}
				else if($(this).hasClass('newreply')){
					var message=$("#newreplymess").val().replace(/\s+/g,' ');
					var sec=1;
				}
				else if($(this).hasClass('newticket')){
					var message=$("#newticketmess").val().replace(/\s+/g,' ');
					var sec=2;
				}
				else if($(this).hasClass('newassignment')){
					var message=$("#assignedmess").val().replace(/\s+/g,' ');
					var sec=3;
				}
				else if($(this).hasClass('forgotmess')){
					var message=$("#forgotmess").val().replace(/\s+/g,' ');
					var sec=4;
				}			
			<?php } ?>
			
			if(""!=subject.replace(/\s+/g,"")&&""!=message.replace(/\s+/g,"")){
				$.ajax({
					type:"POST",
					url:"../php/admin_function.php",
					data:{<?php echo $_SESSION['token']['act']; ?>:"save_mail_body",sec:sec,sub:subject,message:message},
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
				}).fail(function(a,b){noty({text:"Request Error:"+b,type:"error",timeout:9E3})})}
			else 
				noty({text:"Empty Field",type:"error",timeout:9E3});
			return !1;
		});
		
		$("#savestmp").click(function(){
			var a=$("#stmpserv").val(),c=$("#stmpname").val(),d=$("#stmphost").val(),e=$("#stmpport").val(),f=$("#stmpsec > option:selected").val(),g=$("#stmpmail").val(),h=$("#stmpaut > option:selected").val(),k=$("#stmpusr").val(),l=$("#stmppas").val();
			$.ajax({
				type:"POST",
				url:"../php/admin_function.php",
				data:{<?php echo $_SESSION['token']['act']; ?>:"save_stmp",serv:a,name:c,host:d,port:e,ssl:f,mail:g,auth:h,usr:k,pass:l},
				dataType:"json",
				success:function(b){
					if("Saved"==b[0])
						noty({text:"STMP Information Saved",type:"success", timeout:9E3})
					else if(b[0]=='sessionerror'){
						switch(b[1]){
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
						noty({text:b[0],type:"error",timeout:9E3})}
			}).fail(function(b,a){noty({text:a,type:"error",timeout:9E3})})
		});
		
		$(document).on("change","#stmpaut",function(){1==$("#stmpaut > option:checked").val()?($("#stmpusr").attr("required","required"),$("#stmppas").attr("required","required")):($("#stmpusr").removeAttr("required"),$("#stmppas").removeAttr("required"))});

		$(document).on("change","#stmpserv",function(){1==$("#stmpserv > option:checked").val()?($("#stmphost").attr("required","required"),$("#stmpport").attr("required","required")):($("#stmphost").removeAttr("required"),$("#stmpport").removeAttr("required"))});
	});

	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>