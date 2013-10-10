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
else if(!isset($_SESSION['status']) || $_SESSION['status']!=2){
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
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

if(isset($smailpassword)){
	$crypttable=array('X'=>'a','k'=>'b','Z'=>'c',2=>'d','d'=>'e',6=>'f','o'=>'g','R'=>'h',3=>'i','M'=>'j','s'=>'k','j'=>'l',8=>'m','i'=>'n','L'=>'o','W'=>'p',0=>'q',9=>'r','G'=>'s','C'=>'t','t'=>'u',4=>'v',7=>'w','U'=>'x','p'=>'y','F'=>'z','q'=>0,'a'=>1,'H'=>2,'e'=>3,'N'=>4,1=>5,5=>6,'B'=>7,'v'=>8,'y'=>9,'K'=>'A','Q'=>'B','x'=>'C','u'=>'D','f'=>'E','T'=>'F','c'=>'G','w'=>'H','D'=>'I','b'=>'J','z'=>'K','V'=>'L','Y'=>'M','A'=>'N','n'=>'O','r'=>'P','O'=>'Q','g'=>'R','E'=>'S','I'=>'T','J'=>'U','P'=>'V','m'=>'W','S'=>'X','h'=>'Y','l'=>'Z');
	$smailpassword=str_split($smailpassword);
	$c=count($smailpassword);
	for($i=0;$i<$c;$i++){
		if(array_key_exists($smailpassword[$i],$crypttable))
			$smailpassword[$i]=$crypttable[$crypttable[$smailpassword[$i]]];
	}
	$smailpassword=implode('',$smailpassword);
}
if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);
function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}
					
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="robots" content="noindex,nofollow">
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?> - Admin</title>
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		
		<!--[if lt IE 9]><script src="../js/html5shiv-printshiv.js"></script><![endif]-->

		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_i&amp;5259487' ?>"/>
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_d&amp;5259487' ?>"/>
		<?php if($isMob) { ?>
			<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_m&amp;5259487' ?>"/>
		<?php } ?>
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
										<li role="presentation">
											<a href="search.php" tabindex="-1" role="menuitem"><i class="icon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
								<li><a href="setting.php"><i class="icon-edit"></i>Settings</a></li>
								<li><a href="users.php"><i class="icon-user"></i>Users</a></li>
								<li class="dropdown active" role='button' >
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
										<li role="presentation" class='active'>
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
								<li><a href='#' onclick='javascript:logout();return false;'><i class="icon-off"></i>Logout</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class='daddy'>
				<hr>
				<div class="jumbotron" >
					<h2 class='pagefun'>Administration - Mail</h2>
				</div>
				<hr>
				<h4 class='sectname'>Mail Setting</h4>
				<form>
					<div class='row-fluid stmpinfo' >
						<div class='span12'><label><strong>SMTP Setting</strong></label></div>
						<div class='row-fluid'>
							<div class='span2'><label>STMP Service</label></div>
							<div class='span4'><select id='stmpserv' ><option value='0'>This Server</option><option value='1'>External Service</option></select></div>
						</div>
						<div class='row-fluid'>
								<div class='span2'><label for='stmpname'>Name</label></div>
								<div class='span4'><input id='stmpname' type='text' value='<?php if(isset($smailname)) echo htmlspecialchars($smailname,ENT_QUOTES,'UTF-8');?>' required/></div>
								<div class='span2'><label for='stmpmail'>Mail Address</label></div>
								<div class='span4'><input id='stmpmail' type='email' value='<?php if(isset($settingmail)) echo htmlspecialchars($settingmail,ENT_QUOTES,'UTF-8'); ?>' required /></div>
						</div>
						<div class='row-fluid'>
								<div class='span2'><label for='stmphost'>Hostname</label></div>
								<div class='span4'><input id='stmphost' type='text' value='<?php if(isset($smailhost)) echo htmlspecialchars($smailhost,ENT_QUOTES,'UTF-8'); ?>' /></div>
								<div class='span2'><label for='stmpport'>Port</label></div>
								<div class='span4'><input id='stmpport' type='number' value='<?php if(isset($smailport)) echo $smailport; ?>' required/></div>
						</div>
						<div class='row-fluid'>
								<div class='span2'><label for='stmpsec'>SSL/TLS</label></div>
								<div class='span4'><select id='stmpsec' ><option value='0'>No</option><option value='1'>SSL</option><option value='2'>TLS</option></select></div>
						</div>
						<div class='row-fluid'>
								<div class='span2'><label for='stmpaut'>Authentication</label></div>
								<div class='span4'><select id='stmpaut' ><option value='0'>No</option><option value='1'>Yes</option></select></div>
						</div>
						<div class='row-fluid'>
								<div class='span2'><label for='stmpusr'>Username</label></div>
								<div class='span4'><input id='stmpusr' type='text' value='<?php if(isset($smailuser)) echo htmlspecialchars($smailuser,ENT_QUOTES,'UTF-8'); ?>' /></div>
								<div class='span2'><label for='stmppas'>Password</label></div>
								<div class='span4'><input id='stmppas' type='password' value='<?php if(isset($smailpassword)) echo htmlspecialchars($smailpassword,ENT_QUOTES,'UTF-8'); ?>' autocomplete="off" /></div>
						</div>
						<br/>
						<input type='submit' id='savestmp' onclick='javascript:return false;' value='Save' class='btn btn-success'/>
					</div>
				</form>
				<br/><br/>
				<h4 class='sectname'>Mail Template</h4>
				<form>
					<div class='row-fluid'>
						<div class='span12'><label><strong>New Member</strong></label></div>
						<div class='row-fluid'>
								<div class='span2'><label  for='nmsub'>Subject</label></div>
								<div class='span4'><input id='nmsub' class='mailsubject' type='text' value='<?php if(isset($nu[0])) echo htmlspecialchars($nu[0],ENT_QUOTES,'UTF-8');?>' required /></div>
						</div>
						<div class='row-fluid'>
							<div class='span12'><textarea class='mailmessage' id='newmememess' rows="5" placeholder='Welcome Message' required><?php if(isset($nu[1])) echo $nu[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success newuser submailbody'/>
					</div>
				</form>
				<br/><br/>
				<form>
					<div class='row-fluid'>
						<div class='span12'><label><strong>New Reply</strong></label></div>
						<div class='row-fluid'>
								<div class='span2'><label for='nrsub'>Subject</label></div>
								<div class='span4'><input id='nrsub' class='mailsubject' type='text' value='<?php if(isset($nr[0])) echo htmlspecialchars($nr[0],ENT_QUOTES,'UTF-8');?>' required/></div>
						</div>
						<div class='row-fluid'>
							<div class='span12'><textarea class='mailmessage' id='newreplymess' rows="5" placeholder='New Reply Message' required><?php if(isset($nr[1])) echo $nr[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success newreply submailbody'/>
					</div>
				</form>
				<br/><br/>
				<form>
					<div class='row-fluid'>
						<div class='span12'><label><strong>New Ticket</strong></label></div>
						<div class='row-fluid'>
								<div class='span2'><label for='ntsub'>Subject</label></div>
								<div class='span4'><input id='ntsub' class='mailsubject' type='text' value='<?php if(isset($nt[0])) echo  htmlspecialchars($nt[0],ENT_QUOTES,'UTF-8');?>' required/></div>
						</div>
						<div class='row-fluid'>
							<div class='span12'><textarea class='mailmessage' id='newticketmess' rows="5" placeholder='New Ticket Message' required><?php if(isset($nt[1])) echo $nt[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success newticket submailbody'/>

					</div>
				</form>
				<br/><br/>
				<form>
					<div class='row-fluid'>
						<div class='span12'><label><strong>Assigned Ticket</strong></label></div>
						<div class='row-fluid'>
								<div class='span2'><label for='atsub'>Subject</label></div>
								<div class='span4'><input id='atsub' class='mailsubject' type='text' value='<?php if(isset($as[0])) echo  htmlspecialchars($as[0],ENT_QUOTES,'UTF-8');?>' required/></div>
						</div>
						<div class='row-fluid'>
							<div class='span12'><textarea class='mailmessage' id='assignedmess' rows="5" placeholder='Assigned Ticket Message' required><?php if(isset($as[1])) echo $as[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success newassignment submailbody'/>
					</div>
				</form>
				<form>
					<div class='row-fluid'>
						<div class='span12'><label><strong>Password Forgot</strong></label></div>
						<div class='row-fluid'>
								<div class='span2'><label for='pfsub'>Subject</label></div>
								<div class='span4'><input id='pfsub' class='mailsubject' type='text' value='<?php if(isset($fo[0])) echo  htmlspecialchars($fo[0],ENT_QUOTES,'UTF-8');?>' required/></div>
						</div>
						<div class='row-fluid'>
							<div class='span12'><textarea class='mailmessage' id='forgotmess' rows="5" placeholder='Assigned Ticket Message' required><?php if(isset($fo[1])) echo $fo[1];?></textarea></div>	
						</div>
						<br/>
						<input type='submit' onclick='javascript:return false;' value='Save' class='btn btn-success forgotmess submailbody'/>
					</div>
				</form>
				<br/><br/>
			</div>
			<br/><br/>
		</div>
	<iframe name='hidden_frame' style='display:none;width:0;height:0'></iframe>
	
	<?php if(!$isMob) { ?>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="../lib/ckeditor/ckeditor.js"></script>
	<?php }else { ?>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_m&amp;5259487' ?>"></script>
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
						else if(a[0]=='sessionex'){
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
					else if(b[0]=='sessionex'){
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