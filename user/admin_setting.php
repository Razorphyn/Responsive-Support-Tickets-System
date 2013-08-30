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
else if(!isset($_SESSION['status']) || $_SESSION['status']!=2){
	 header("location: ../index.php");
	 exit();
}

if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
if(is_file('../php/config/logo.txt')) $logo=file_get_contents('../php/config/logo.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

$crypttable=array('X'=>'a','k'=>'b','Z'=>'c',2=>'d','d'=>'e',6=>'f','o'=>'g','R'=>'h',3=>'i','M'=>'j','s'=>'k','j'=>'l',8=>'m','i'=>'n','L'=>'o','W'=>'p',0=>'q',9=>'r','G'=>'s','C'=>'t','t'=>'u',4=>'v',7=>'w','U'=>'x','p'=>'y','F'=>'z','q'=>0,'a'=>1,'H'=>2,'e'=>3,'N'=>4,1=>5,5=>6,'B'=>7,'v'=>8,'y'=>9,'K'=>'A','Q'=>'B','x'=>'C','u'=>'D','f'=>'E','T'=>'F','c'=>'G','w'=>'H','D'=>'I','b'=>'J','z'=>'K','V'=>'L','Y'=>'M','A'=>'N','n'=>'O','r'=>'P','O'=>'Q','g'=>'R','E'=>'S','I'=>'T','J'=>'U','P'=>'V','m'=>'W','S'=>'X','h'=>'Y','l'=>'Z');
		
$stmp[8]=str_split($stmp[8]);
$c=count($stmp[8]);
for($i=0;$i<$c;$i++){
	if(array_key_exists($stmp[8][$i],$crypttable))
		$stmp[8][$i]=$crypttable[$crypttable[$stmp[8][$i]]];
}
$stmp[8]=implode('',$stmp[8]);
							
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
								<li><a href="setting.php"><i class="icon-cog"></i>Settings</a></li>
								<li><a href="users.php"><i class="icon-user"></i>Users</a></li>
								<li class="dropdown active" role='button' >
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="icon-eye-open"></i>Administration<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation" class='active'>
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
								<li><a href='#' onclick='javascript:logout();return false;'><i class="icon-off"></i>Logout</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class='daddy'>
				<hr>
				<div class="jumbotron" >
					<h2 class='pagefun'>Administration - Site Managment</h2>
				</div>
				<hr>
				<form id='adminset' action=''>
					<h3 class='sectname'>Site Information & Settings</h3>
					<div class='row-fluid'>
						<div class='span2'><label>Title</label></div>
						<div class='span4'><input type="text" name='titsite' id="titsite" <?php if(isset($setting[0])) echo 'value="'.$setting[0].'"';?> ifplaceholder="Title" required/></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Time Zone</label></div>
						<div class='span4'><input type="text" name='timezone' id="timezone" <?php if(isset($setting[4])) echo 'value="'.$setting[4].'"';?> ifplaceholder="Title" required/></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Notifier Mail</label></div>
						<div class='span4'><input type="text" name='notmail' id="notmail" <?php if(isset($setting[1])) echo 'value="'.$setting[1].'"';?> placeholder="Notifier Email" required /></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Send Message on Reply</label></div>
						<div class='span4'>
							<select name='senrep' id='senrep'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
						<div class='span2'><label>Advise operator on New Assignment</label></div>
						<div class='span4'>
							<select name='senope' id='senope'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Enable FAQs?</label></div>
						<div class='span4'>
							<select name='allfaq' id='allfaq'>
								<option value='1'>Yes</option>
								<option value='0'>No</option>
							</select>
						</div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Allow Upload?</label></div>
						<div class='span4'>
							<select name='allup' id='allup'>
								<option value='1'>Yes</option>
								<option value='0'>No</option>
							</select>
						</div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Max File Size Allowed</label></div>
						<div class='span4'>
							<?php echo ini_get('upload_max_filesize'); ?>
						</div>
						<div class='span2'><label>Lower Max Size(MB)</label></div>
						<div class='span4'><input type="text" name='maxsize' id="maxsize" <?php if(isset($setting[6])) echo 'value="'.($setting[6]/1048576).'"';?> placeholder="Lower File Size" /></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Allow Opeartor Rating?</label></div>
						<div class='span4'>
							<select name='allrat' id='allrat'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Command Line Option</label></div>
						<div class='span3'>Usually <strong>php5-cli</strong> or <strong>php -f</strong></div><br/>
					</div>
					<div class='row-fluid'>
						<div class='span4'><input type="text" name='commlop' id="commlop" value="<?php if(isset($setting[8])) echo $setting[8]; else echo 'php5-cli'?>" placeholder="Command Line Option" required/></div>
					</div>
					<input type="submit" class="btn btn-success" value='Save' id='saveopt'/>
				</form>
				<br/><br/>
				<hr>
				<form action='../php/admin_function.php' method='POST' target='hidden_frame' enctype="multipart/form-data">
					<h3 class='sectname'>Logo</h3>
					<div class='row-fluid'>
						<div class='span2'><label>Current Logo</label></div>
						<div class='span12'><img src='<?php if(isset($logo) && rtrim($logo)!='') echo $logo;else echo "../css/logo/def/logo.png"; ?>' alt='Logo' id='cur_logo'/><br/><br/></div>
					</div>
					<br/><br/>
					<div class='row-fluid'>
						<div class='span2'><label>Select New Logo:</label></div><div class='span6'><input id="new_logo" name="new_logo" type="file" /></div>
					</div>
					<input type="submit" class="btn btn-success" value='Upload Logo' name='upload_logo'/>
				</form>
				<br/><br/>
				<hr>
				<form action='' method='POST'>
					<h3 class='sectname'>Delete Uploaded File</h3>
					<div class='row-fluid'>
						<div class='span2'><label>From Date</label></div>
						<div class='span3'><input type="text" id="delfromdate" placeholder="Delete from Date" /></div>
						<div class='span1'><label>to Date</label></div>
						<div class='span3'><input type="text" id="todeldate" placeholder="to Date" /></div>
					</div>
					<input type="submit" class="btn btn-success" onclick='javascript:return !1;' value='Delete Files' id='deleteupload'/>
				</form>
				<br/><br/>
			</div>
		</div>
		<iframe name='hidden_frame' style='display:none;width:0;height:0'></iframe>

	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?f=js/timezoneautocomplete.js&amp;5259487' ?>"></script>

	<script>
	 $(document).ready(function() {
		var today=new Date();
		var dateObject=new Date(today.getFullYear(),today.getMonth(),today.getDate());
		$('#delfromdate').datepicker({dateFormat:'yy-mm-dd'});
		$('#todeldate').datepicker({dateFormat:'yy-mm-dd'});
		$("#delfromdate").datepicker("option","maxDate",dateObject);
		$("#todeldate").datepicker("option","maxDate",dateObject);

		<?php if(isset($setting[2])){?>
			$("#senrep > option[value='<?php echo $setting[2];?>']").attr('selected','selected');
		<?php } if(isset($setting[3])){?>
			$("#senope > option[value='<?php echo $setting[3];?>']").attr('selected','selected');
		<?php } if(isset($setting[5])){?>
			$("#allup > option[value='<?php echo $setting[5];?>']").attr('selected','selected');
		<?php } if(isset($setting[7])){?>
			$("#allrat > option[value='<?php echo $setting[7];?>']").attr('selected','selected');
		<?php } if(isset($setting[9])){?>
			$("#allfaq > option[value='<?php echo $setting[8];?>']").attr('selected','selected');
		<?php } ?>
		
		$("#deleteupload").click(function() { if(confirm("Do you want to delete all the files inside this period?")) { var a = $("#delfromdate").val(), c = $("#todeldate").val(); "" != a.replace(/\s+/g, "") && "" != c.replace(/\s+/g, "") ? $.ajax({type:"POST", url:"../php/admin_function.php", data:{act:"delete_files", from:a, to:c}, dataType:"json", success:function(b) { "Deleted" == b[0] ? ($("#delfromdate").val(""), $("#todeldate").val("")) : noty({text:b[0], type:"error", timeout:9E3}) }}).fail(function(b, a) { noty({text:"Request Error:" + a, type:"error", timeout:9E3}) }) : noty({text:"Complete both the date", type:"error", timeout:9E3}) } return!1 });
		
		$("#saveopt").click(function(){var a=$("#titsite").val().replace(/\s+/g," "),c=$("#notmail").val(),d=$("#senrep").val(),e=$("#senope").val(),f=$("#timezone").val(),g=$("#maxsize").val(),h=$("#allup > option:checked").val(),k=$("#allrat").val(),q=$("#commlop").val(),r=$("#allfaq").val();$.ajax({type:"POST",url:"../php/admin_function.php",data:{act:"save_options",tit:a,mail:c,senrep:d,senope:e,timezone:f,upload:h,maxsize:g,enrat:k,commlop:q,faq:r},dataType:"json",success:function(b){"Saved"==b[0]?noty({text:"Saved",type:"success",timeout:9E3}):noty({text:"Options cannot be saved. Error: "+ b[0],type:"error",timeout:9E3})}}).fail(function(b,a){noty({text:a,type:"error",timeout:9E3})});return!1});		
	});

	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{act:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():noty({text: a[0],type:'error',timeout:9E3})}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>