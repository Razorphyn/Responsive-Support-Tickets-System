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
include_once '../php/mobileESP.php';
$uagent_obj = new uagent_info();
$isMob=$uagent_obj->DetectMobileQuick();
if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);

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
										<li role="presentation">
											<a href="admin_mail.php" tabindex="-1" role="menuitem"><i class="icon-envelope"></i> Mail Settings</a>
										</li>
										<li role="presentation" class='active'>
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
					<h2 class='pagefun'>Administration - FAQs</h2>
				</div>
				<hr>
				<h3 class='sectname'>FAQs</h3>
				<div class='row-fluid' id='deplist'>
					<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
					<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="faqtable">
					</table>
				</div>
				<br/><br/>
				<div id='faq_div' style='display:none;'>
					<hr>
					<form action='' method='post' class='submit_changes_depa' id='edit_faq'>
						Edit FAQ with ID:<span id='faq_id'></span><button  class='btn btn-link btn_close_form'>Close</button>
						<input type='hidden' id='faq_edit_id' name='faq_edit_id'/>
						<input type='hidden' id='faq_edit_pos' name='faq_edit_pos' />
						<div class='row-fluid'>
							<div class='span2'><label for='edit_faq_question'>Question:</label></div>
							<div class='span9'><input type='text' id='edit_faq_question' /></div>
						</div>
						<div class='row-fluid'>
							<div class='span2'><label for='edit_faq_answer'>Answer:</label></div>
							<div class='span9'><textarea type='text' id='edit_faq_answer' name='edit_faq_answer' placeholder='Answer'></textarea></div>
						</div>
						<br/>
						<div class='row-fluid'>
							<div class='span2'><label for='edit_faq_position'>Position</label></div>
							<div class='span4'><input type='number' id='edit_faq_position' name='edit_faq_position' placeholder='Position, leave blank for last'/></div>
							<div class='span2'><label for='activedep'>Is Active?</label></div>
							<div class='span4'><select name='edit_faq_active' id='activedep'><option value='1'>Yes</option><option value='0'>No</option></select></div>
						</div>
						<input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' />
					</form>
					<br/><br/>
				</div>
				<hr>
				<h4 class='sectname'>Add New FAQ</h4>
				<form id='newfaqform' action='' method='post'>
						<div class='row-fluid'>
							<div class='span2'><label for='question'>Question:</label></div>
							<div class='span4'><input type="text" name='question' id="question" placeholder="Question" required /></div>
						</div>
						<div class='row-fluid'>
							<div class='span2'><label for='answer'>Answer:</label></div>
							<div class='span9'><textarea class='mailmessage' id='answer' rows="5" placeholder='Answer' required></textarea></div>	
						</div>
						<br/>
						<div class='row-fluid'>
							<div class='span2'><label for='position'>Position:</label></div>
							<div class='span4'><input type="number" name='position' id="position" placeholder="Position, leave blank for last"/></div>
							<div class='span2'><label for='activefaq'>Is Active?</label></div>
							<div class='span4'><select name='activefaq' id='activefaq'><option value='1'>Yes</option><option value='0'>No</option></select>
							</div>
						</div>
					<input type="submit" class="btn btn-success" value='Add New FAQ' onclick='javascript:return false;' id='btnaddfaq'/>
				</form>
				<br/><br/>
			</div>
		</div>
		<?php if(!$isMob) { ?>
			<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
			<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
			<script type="text/javascript"  src="../lib/ckeditor/ckeditor.js"></script>
		<?php }else { ?>
			<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
			<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
			<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_m&amp;5259487' ?>"></script>
		<?php } ?>
	
	<script>
	 $(document).ready(function() {
		var table;
		var request = $.ajax({type:"POST", url:"../php/admin_function.php", data:{<?php echo $_SESSION['token']['act']; ?>:"retrive_faq"}, dataType:"json", success:function(b) { if("ret" == b.response || "empty" == b.response) { if("ret" == b.response) { var f = b.faq.length; for(i = 0;i < f;i++) { b.faq[i].action = '<div class="btn-group"><button class="btn btn-info editdep" value="' + b.faq[i].id + '"><i class="icon-edit"></i></button><button class="btn btn-danger remdep" value="' + b.faq[i].id + '"><i class="icon-remove"></i></button></div>' } } $("#loading").remove(); table = $("#faqtable").dataTable({sDom:"<<'span6'l><'span6'f>r>t<<'span6'i><'span6'p>>", sWrapper:"dataTables_wrapper form-inline", bProcessing:!0, aaData:b.faq, oLanguage:{sEmptyTable:"No FAQs"}, aoColumns:[{sTitle:"ID", mDataProp:"id", sWidth:"60px", fnCreatedCell:function(a, b, c, d, e) { $(a).html("<span><strong class='visible-phone'>ID: </strong></span><span> " + $(a).html() + "</span>") }}, {sTitle:"Question", mDataProp:"question", fnCreatedCell:function(a, b, c, d, e) { $(a).html("<span><strong class='visible-phone'>Question: </strong></span><span> " + $(a).html() + "</span>") }}, {sTitle:"Position", mDataProp:"position", sWidth:"50px", fnCreatedCell:function(a, b, c, d, e) { $(a).html("<span><strong class='visible-phone'>Position: </strong></span><span> " + $(a).html() + "</span>") }}, {sTitle:"Active", mDataProp:"active", sWidth:"60px", fnCreatedCell:function(a, b, c, d, e) { $(a).html("<span><strong class='visible-phone'>Active: </strong></span><span> " + $(a).html() + "</span>") }}, {sTitle:"Rate", mDataProp:"rate", sWidth:"40px", fnCreatedCell:function(a, b, c, d, e) { $(a).html("<span><strong class='visible-phone'>Rate: </strong></span><span> " + $(a).html() + "</span>") }}, {sTitle:"Toogle", mDataProp:"action", bSortable:!1, bSearchable:!1, sWidth:"60px", fnCreatedCell:function(a, b, c, d, e) { $(a).html("<span><strong class='visible-phone'>Toogle: </strong></span><span> " + $(a).html() + "</span>") }}]}) }else { noty({text:b[0], type:"error", timeout:9E3}) } }}); request.fail(function(b, f) { noty({text:f, type:"error", timeout:9E3}) });

		<?php if(!$isMob) {?>
			CKEDITOR.replace('answer');
			CKEDITOR.replace('edit_faq_answer');
		<?php }else { ?>
			$("#answer").wysihtml5(), $("#edit_faq_answer").wysihtml5();
		<?php }?>
		
		$("#faqtable").on("click", ".editdep", function() { var b = $(this).val(), d = this.parentNode.parentNode.parentNode.parentNode, e = table.fnGetPosition(d, null, !0), a = table.fnGetData(d); $("#edit_faq").hasClass("open") ? confirm("Do you want to close the already opened edit form?") && (b = $.ajax({type:"POST", url:"../php/admin_function.php", data:{<?php echo $_SESSION['token']['act']; ?>:"retrive_faq_answer", id:b}, dataType:"json", success:function(c) { "ret" == c[0] ? ($("#faq_id").html(a.id), $("#faq_id").html(a.id), $("#faq_edit_id").val(a.id), $("#faq_edit_pos").val(e), $("#edit_faq_question").val(a.question), $("#edit_faq_position").val(a.position), <?php if(!$isMob) { ?>CKEDITOR.instances.edit_faq_answer.setData(data[1]) <?php }else { ?> $("#edit_faq_answer").val(data[1])<?php } ?>, $('select[name="edit_faq_active"]:first option[value=' + ("Yes" == a.active ? 1 : 0) + "]").attr("selected", "selected")) : noty({text:"Cannot retrieve Answer. Error: " + c[0], type:"error", timeout:9E3}) }}), b.fail(function(a, b) { noty({text:b, type:"error", timeout:9E3}) })) : (b = $.ajax({type:"POST", url:"../php/admin_function.php", data:{<?php echo $_SESSION['token']['act']; ?>:"retrive_faq_answer", id:b}, dataType:"json", success:function(b) { "ret" == b[0] ? ($("#edit_faq").addClass("open"), $("#faq_id").html(a.id), $("#faq_edit_id").val(a.id), $("#faq_edit_pos").val(e), $("#edit_faq_question").val(a.question), $("#edit_faq_position").val(a.position), <?php if(!$isMob) { ?>CKEDITOR.instances.edit_faq_answer.setData(b[1]) <?php }else { ?> $("#edit_faq_answer").val(b[1])<?php } ?>, $("#activedep option[value=" + ("Yes" == a.active ? 1 : 0) + "]").attr("selected", "selected"), $("#faq_div").slideToggle(600)) : noty({text:"Cannot retrieve Answer. Error: " + b[0], type:"error", timeout:9E3}) }}), b.fail(function(b, a) { noty({text:a, type:"error", timeout:9E3}) })) });

		$("#faqtable").on("click", ".remdep", function() { var a = $(this).val(), c = table.fnGetPosition(this.parentNode.parentNode.parentNode.parentNode, null, !0); confirm("Do you realy want to delete this FAQ?") && $.ajax({type:"POST", url:"../php/admin_function.php", data:{<?php echo $_SESSION['token']['act']; ?>:"del_faq", id:a}, dataType:"json", success:function(b) { "Deleted" == b[0] ? table.fnDeleteRow(c) : noty({text:"FAQ cannot be deleted. Error: " + b[0], type:"error", timeout:9E3}) }}).fail(function(b, a) { noty({text:a, type:"error", timeout:9E3}) }) });

		$("#btnaddfaq").click(function () {var b = $("#question").val().replace(/\s+/g, " "),<?php if (!$isMob) { ?> c = CKEDITOR.instances.answer.getData().replace(/\s+/g, " ") <?php } else { ?> c = $("#answer").val().replace(/\s+/g, ' ') <?php } ?>, d = $("#position").val().replace(/\s+/g, ""); e = $("#activefaq").val(); "" != b.replace(/\s+/g, "") && "" != c.replace(/\s+/g, "") ? $.ajax({type:"POST", url:"../php/admin_function.php", data:{<?php echo $_SESSION['token']['act']; ?>:"add_faq", question:b, answer:c, pos:d, active:e}, dataType:"json", success:function(a) { "Added" == a.response ? ($("#question").val(""), a.information.rate = "Unrated", a.information.action = '<div class="btn-group"><button class="btn btn-info editdep" value="' + a.information.id + '"><i class="icon-edit"></i></button><button class="btn btn-danger remdep" value="' + a.information.id + '"><i class="icon-remove"></i></button></div>', table.fnAddData(a.information), $("#faqtable").val(""),<?php if(!$isMob) { ?> CKEDITOR.instances.answer.setData('') <?php }else { ?>$("#answer").val('') <?php } ?>) : noty({text:a[0], type:"error", timeout:9E3}) }}).fail(function(a, f) { noty({text:f, type:"error", timeout:9E3}) }) : noty({text:"Form Error - Empty Field", type:"error", timeout:9E3});});		
		
		$(document).on("click",".btn_close_form",function(){confirm("Do you want to close this edit form?")&&($('#faq_div').slideToggle(600),$('#edit_faq').removeClass('open'));return!1});

		$(document).on('click','.submit_changes',function(){
			var dom=$(this).parent();
			var id= $("#faq_edit_id").val();
			var pos= $("#faq_edit_pos").val();
			var q= $("#edit_faq_question").val().replace(/\s+/g,' ');
			var p= $("#edit_faq_position").val();
			var ac= $("#activedep").val();
			<?php if(!$isMob) { ?>
				var a=CKEDITOR.instances.edit_faq_answer.getData().replace(/\s+/g," ");
			<?php }else { ?>
				var a=$("#edit_faq_answer").val().replace(/\s+/g,' ');
			<?php } ?>
			if(q.replace(/\s+/g,'')!='' && a.replace(/\s+/g,'')!=''){
				var request= $.ajax({
					type: 'POST',
					url: '../php/admin_function.php',
					data: {<?php echo $_SESSION['token']['act']; ?>:'edit_faq',id:id,question:q,answer:a,active:ac,position:p},
					dataType : 'json',
					success : function (a){
						if(a[0]=='Succeed'){
							a[1]['action']='<div class="btn-group"><button class="btn btn-info editdep" value="'+a[1]['id']+'"><i class="icon-edit"></i></button><button class="btn btn-danger remdep" value="'+a[1]['id']+'"><i class="icon-remove"></i></button></div>';

							table.fnDeleteRow(pos, function(){table.fnAddData(a[1])});
							<?php if(!$isMob) { ?>
								CKEDITOR.instances.edit_faq_answer.setData("")
							<?php }else { ?>
								$("#edit_faq_answer").val("");
							<?php } ?>
							$('#faq_div').slideToggle(600);
							$('#edit_faq').removeClass('open');
						}
						else
							noty({text: a[0],type:'error',timeout:9000});
					}
				});
				request.fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
			}
			else
				noty({text: 'Form Error - Empty Fields',type:'error',timeout:9000});
		});
		
	});

	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():noty({text: a[0],type:'error',timeout:9E3});}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>