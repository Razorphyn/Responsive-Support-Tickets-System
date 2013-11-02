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
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'sessionerror',0));
	}
	else
		echo '<script>top.window.location.replace("'.curPageURL().'?e=invalid");</script>';
	exit();
}
session_start(); 

//Session Check
if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800)
	$_SESSION['time']=time();
else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
	session_unset();
	session_destroy();
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'sessionerror',1));
	}
	else
		echo '<script>top.window.location.replace("'.curPageURL().'?e=expired");</script>';
	exit();
}
else if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'sessionerror',2));
	}
	else
		echo '<script>top.window.location.replace("'.curPageURL().'?e=local");</script>';
	exit();
}
else if(!isset($_POST[$_SESSION['token']['act']]) && !isset($_POST['act']) && $_POST['act']!='faq_rating' || $_POST['token']!=$_SESSION['token']['faq']){
	session_unset();
	session_destroy();
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'sessionerror',3));
	}
	else
		echo '<script>top.window.location.replace("'.curPageURL().'?e=token");</script>';
	exit();
}
else if(!isset($_SESSION['status']) || $_SESSION['status']!=2){
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('Access Denied'));
	exit();
}

include_once '../php/mobileESP.php';
$uagent_obj = new uagent_info();
$isMob=$uagent_obj->DetectMobileQuick();

if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
if(is_file('../php/config/privacy.txt')) $privacy=file('../php/config/privacy.txt',FILE_IGNORE_NEW_LINES);
if(is_file('../php/config/logo.txt')) $logo=file_get_contents('../php/config/logo.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}


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
											<a href="admin_reported.php" tabindex="-1" role="menuitem"><i class="icon-exclamation-sign"></i> Reported Tickets</a>
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
					<input type='hidden' name='<?php echo $_SESSION['token']['act']; ?>' value='Doom' />
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
					<h3 class='sectname'>Privacy Policy</h3>
					<div class='row-fluid'>
						<div class='span3'><label>Enable "Accept Privacy Policy"?</label></div>
						<div class='span4'>
							<select name='enprivacy' id='enprivacy'>
								<option value='1'>Yes</option>
								<option value='0'>No</option>
							</select>
						</div>
					</div>
					<div class='row-fluid'>
						<div class='span12'><textarea class='privacytext' id='privacytext' rows="5" placeholder='Privacy Policy Text' required><?php if(isset($privacy[1])) echo $privacy[1];?></textarea></div>	
					</div>
					<br/>
					<input type="submit" class="btn btn-success" onclick='javascript:return !1;' value='Save' id='saveprivacyc'/>
				</form>
				<br/><br/>
				<hr>
				<form action='' method='POST'>
					<h3 class='sectname'>Delete Tickets</h3>
					<div class='row-fluid'>
						<div class='span3'><label>Delete by</label></div>
						<div class='span4'>
							<select name='delby' id='delby'>
								<option value='1'>Last Reply</option>
								<option value='0'>Opened Date</option>
							</select>
						</div>
					</div>
					<p>Tickets Status:</p>
					<div class='row-fluid'>
						<div class='span3'><input type="checkbox" name="stat[]" value="1"> Open</div>
						<div class='span3'><input type="checkbox" name="stat[]" value="0"> Closed</div>
						<div class='span3'><input type="checkbox" name="stat[]" value="2"> To Assign</div>
					</div>
					<br/>
					<div class='row-fluid'>
						<div class='span2'><label>From Date</label></div>
						<div class='span3'><input type="text" id="deltkfromdate" placeholder="Delete from Date" /></div>
						<div class='span1'><label>to Date</label></div>
						<div class='span3'><input type="text" id="deltktodeldate" placeholder="to Date" /></div>
					</div>
					<input type="submit" class="btn btn-success" onclick='javascript:return !1;' value='Delete Tickects' id='deleteticket'/>
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
		<iframe name='hidden_frame' style='display:none;width:0;height:0' src="about:blank" ></iframe>
	
	<?php if(!$isMob) { ?>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?f=js/timezoneautocomplete.js&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="../lib/ckeditor/ckeditor.js"></script>
	<?php }else { ?>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?f=js/timezoneautocomplete.js&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_m&amp;5259487' ?>"></script>
	<?php } ?>
	
	<script>
	 $(document).ready(function() {
		var today=new Date();
		var dateObject=new Date(today.getFullYear(),today.getMonth(),today.getDate());
		$('#delfromdate').datepicker({dateFormat:'yy-mm-dd'});
		$('#todeldate').datepicker({dateFormat:'yy-mm-dd'});
		$("#delfromdate").datepicker("option","maxDate",dateObject);
		$("#todeldate").datepicker("option","maxDate",dateObject);
		
		$('#deltkfromdate').datepicker({dateFormat:'yy-mm-dd'});
		$('#deltktodeldate').datepicker({dateFormat:'yy-mm-dd'});
		$("#deltkfromdate").datepicker("option","maxDate",dateObject);
		$("#deltktodeldate").datepicker("option","maxDate",dateObject);
		
		<?php if(!$isMob) { ?>
			CKEDITOR.replace('privacytext');
		<?php }else { ?>
			$("#privacytext").wysihtml5();
		<?php } ?>
		
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
		<?php } if(isset($privacy[0])){?>
			$("#enprivacy > option[value='<?php echo $privacy[0];?>']").attr('selected','selected');
		<?php } ?>
		
		$("#deleteupload").click(function() {
			if(confirm("Do you want to delete all the files inside this period?")) {
				var a = $("#delfromdate").val(), 
					c = $("#todeldate").val(); 
				if("" != a.replace(/\s+/g, "") && "" != c.replace(/\s+/g, "")){
					$.ajax({
						type:"POST", 
						url:"../php/admin_function.php", 
						data:{<?php echo $_SESSION['token']['act']; ?>:"delete_files", from:a, to:c}, 
						dataType:"json", 
						success:function(b) {
							if("Deleted" == b[0]){
								$("#delfromdate").val("");
								$("#todeldate").val("");
								noty({text:b[1]+" files has been deleted", type:"success", timeout:9E3});
							}
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
								noty({text:b[0], type:"error", timeout:9E3}) 
						}
					}).fail(function(b, a) { noty({text:"Request Error:" + a, type:"error", timeout:9E3})});
				}
				else
					noty({text:"Complete both the date", type:"error", timeout:9E3})
			}
			return!1
		});
		
		$("#deleteticket").click(function() {
			if(confirm("Do you want to delete all the tickets inside this period?")) {
				var a = $("#deltkfromdate").val(), 
					c = $("#deltktodeldate").val(),
					s = $("input[name='stat[]']:checked").map(function(){return $(this).val();}).get(),
					h = $("#delby > option:checked").val();
				if("" != a.replace(/\s+/g, "") && "" != c.replace(/\s+/g, "")){
					$.ajax({
						type:"POST", 
						url:"../php/admin_function.php", 
						data:{<?php echo $_SESSION['token']['act']; ?>:"delete_tickets_period", from:a, to:c, stat:s, by:h}, 
						dataType:"json", 
						success:function(b) {
							if("Deleted" == b[0]){
								$("#delfromdate").val("");
								$("#todeldate").val("");
								noty({text: b[1]+" tickets has been deleted", type:"success", timeout:9E3});
							}
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
								noty({text:b[0], type:"error", timeout:9E3}) 
						}
					}).fail(function(b, a) { noty({text:"Request Error:" + a, type:"error", timeout:9E3})});
				}
				else
					noty({text:"Complete both the date", type:"error", timeout:9E3})
			}
			return!1
		});
		
		$("#saveopt").click(function(){
			var a=$("#titsite").val().replace(/\s+/g," "),
				c=$("#notmail").val(),d=$("#senrep").val(),
				e=$("#senope").val(),
				f=$("#timezone").val(),
				g=$("#maxsize").val(),
				h=$("#allup > option:checked").val(),
				k=$("#allrat").val(),
				q=$("#commlop").val(),
				r=$("#allfaq").val();
			$.ajax({
				type:"POST",
				url:"../php/admin_function.php",
				data:{<?php echo $_SESSION['token']['act']; ?>:"save_options",tit:a,mail:c,senrep:d,senope:e,timezone:f,upload:h,maxsize:g,enrat:k,commlop:q,faq:r},
				dataType:"json",
				success:function(b){
					if("Saved"==b[0])
						noty({text:"Saved",type:"success",timeout:9E3})
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
						noty({text:"Options cannot be saved. Error: "+ b[0],type:"error",timeout:9E3})}
			}).fail(function(b,a){noty({text:a,type:"error",timeout:9E3})});
			return!1
		});	
		
		$("#saveprivacyc").click(function(){
			<?php if(!$isMob) { ?>
				var text=CKEDITOR.instances.privacytext.getData().replace(/\s+/g," "),
			<?php }else { ?>
				var text=$("#privacytext").val().replace(/\s+/g,' '),
			<?php } ?>
				h=$("#enprivacy > option:checked").val();
			if(""!=text.replace(/\s+/g,"")){
				$.ajax({
					type:"POST",
					url:"../php/admin_function.php",
					data:{<?php echo $_SESSION['token']['act']; ?>:"save_privacy",text:text,en:h},
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
				}).fail(function(a,b){noty({text:"Request Error:"+b,type:"error",timeout:9E3})})
			}
			else 
				noty({text:"Empty Field",type:"error",timeout:9E3});
			return !1;
		});
		
	});

	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():noty({text: a[0],type:'error',timeout:9E3})}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>