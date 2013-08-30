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
include_once '../php/mobileESP.php';
$uagent_obj = new uagent_info();
$isMob=$uagent_obj->DetectMobileQuick();
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
		<title><?php if(isset($setting[0])) echo $setting[0];?> - New Ticket</title>
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		
		
		<!--[if lt IE 9]><script src="../js/html5shiv-printshiv.js"></script><![endif]-->
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_i&amp;5259487' ?>"/>
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
								<li class="active"><a href="#"><i class="icon-file"></i>New Ticket</a></li>
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
							<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){?>
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
				<h2 class='pagefun'>Create New Ticket</h2>
			</div>
			<hr>
			<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
			<form style='display:none' id='createticket' method="POST" action="../php/function.php" target='hidden_upload' enctype="multipart/form-data">
				<div class='row-fluid main'>
					<div class='sect login activesec'>
							<h3 class='sectname'>Ticket Information</h3>
							<div class='row-fluid'>
								<div class='span2'><label for='title'>Title</label></div>
								<div class='span4'><input type="text" name='title' id="title" placeholder="Title" required /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label for='deplist'>Departement</label></div>
								<div class='span4' id='deplist'>

								</div>
								<div class='span2'><label for='priority'>Priority</label></div>
								<div class='span4'>
									<select name='priority' id='priority'>
										<option value='0'>Low</option>
										<option value='1'>Medium</option>
										<option value='2'>High</option>
										<option value='3'>Urgent</option>
										<option value='4'>Critical</option>
									</select>
								</div>
							</div>
							<br/><br/>
							<h3 class='sectname'>Website Information</h3>
							<div class='row-fluid'>
								<div class='span2'><label for='wsurl'>URL</label></div>
								<div class='span4'><input type="url" name='wsurl' id="wsurl" placeholder="Website URL"/></div>
							</div><div class='row-fluid'>
								<div class='span2'><label for='contype'>Connection Type</label></div><div class='span4'><select name="contype" id="contype"><option selected="" value="0">--</option><option value="1">FTP</option><option value="2">FTPS</option><option value="3">SFTP</option><option value="4">SSH</option><option value="5">Other</option></select></div>
								</div><div class='row-fluid'>
								<div class='span2'><label for='ftpus'>FTP Username</label></div>
								<div class='span4'><input type="text" name='ftpus' id="ftpus" placeholder="FTP Username"/></div>
								<div class='span2'><label for='ftppass'>FTP Password</label></div>
								<div class='span4'><input type="password" name='ftppass' id="ftppass" placeholder="FTP Password"/></div>
							</div>
							<br/><br/>
							<h3 class='sectname'>Message</h3>
							<div class='row-fluid'>
									<div class='span12 nwm'></div>
							</div>
							<br/>
							<?php if(isset($setting[5]) && $setting[5]==1){ ?>
								<h3 class='sectname'>Attachments</h3>
								<span class='attlist'></span>
								<div class='row-fluid uploadfilebox'></div>
								<br/>
								<span id='add_upload' class='btn btn-primary'>Add File Field</span>
							<?php } ?>
							<br/><br/>
							<input type="submit" class="btn btn-success" name='createtk' value='Create New Ticket' id='createtk'/>
					</div>
				</div>
			</form>
			<hr>
		</div>
		</div>
		<iframe style='display:none' name='hidden_upload' id='hidden_upload'></iframe>
		
		<?php if(!$isMob) { ?>
			<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
			<script type="text/javascript"  src="../lib/ckeditor/ckeditor.js"></script>
		<?php }else { ?>
			<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
			<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_m&amp;5259487' ?>"></script>
		<?php } ?>
		<script>
			 $(document).ready(function() {
				var request = $.ajax({
					type: "POST",
					url: "../php/function.php",
					data: {act: "retrive_depart",sect: "new"},
					dataType: "json",
					success: function (a) {
						if("ret" == a.response){
							$("#loading").remove();
							$('.nwm').append("<textarea name='message' id='message' rows='5' placeholder='Your Message'> </textarea>");
							<?php if (!$isMob) { ?> 
								CKEDITOR.replace('message'); 
							<?php } else { ?> 
							$("#message").wysihtml5(); 
							<?php } ?> 
							$("#deplist").html("<select name='dep' id='dep'>" + a.information + "</select>");
						}
						else if("empty" == a.response){
							 $("#loading").remove(), $("#createticket").html("<p>Sorry, you cannot open a new ticket because: " + a[1] + "</p>");
						}
						else
							$("#loading").remove(), $("#createticket").html("<h4>Error: " + a[0] + " <br/>Please contact the administrator.</h4>");
						$("#createticket").slideToggle(1500)
					}
				});
				request.fail(function (b, a) {noty({text: a,type: "error",timeout: 9E3})});	
				
				$("#createticket").submit(function(){<?php if(!$isMob){ ?>if(""==CKEDITOR.instances.message.getData().replace(/\s+/g,"")||""==$("#title").val().replace(/\s+/g,""))<?php }else { ?>if($("#message").val().replace(/\s+/g,'') == '' || $('#title').val().replace(/\s+/g,'')=='')<?php } ?>return noty({text:"Empty Fields. PLeasy check the title and the message",type:"error",timeout:9E3}),!1;$(".main").nimbleLoader("show",{position:"fixed",loaderClass:"loading_bar_body",debug:!0,hasBackground:!0,zIndex:999,backgroundColor:"#fff",backgroundOpacity:0.9});return!0});
				
				$("#add_upload").click(function(){$(".uploadfilebox:last").after('<div class="row-fluid uploadfilebox"><div class="span4"><div class="span9"><input type="file" name="filename[]" /></div><div class="span1"> <i class="icon-remove remupbox"></i></div></div></div>')});

				$(document).on('click','.remupbox',function(){ $(this).parent().parent().remove();});
			});
			
			function created(){window.location = "<?php echo dirname(curPageURL()); ?>";}
			function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{act:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
			
		</script>
	</body>
</html>