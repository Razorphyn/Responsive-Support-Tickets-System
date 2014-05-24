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
if(isset($_COOKIE['RazorphynSupport']) && !is_string($_COOKIE['RazorphynSupport']) || !preg_match('/^[^[:^ascii:];,\s]{22,40}$/',$_COOKIE['RazorphynSupport'])){
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
include_once '../php/config/database.php';
try{
	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	
	$query = "SELECT 	id,
						question,
						position,
						CASE active WHEN '0' THEN 'No' ELSE 'Yes' END AS ac,
						CASE rate WHEN 0 THEN 'Unrated' ELSE rate END AS rat 
				FROM ".$SupportFaqTable;
	$STH = $DBH->prepare($query);
	$STH->execute();

	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$list=array();
	$a = $STH->fetch();
	if(!empty($a)){
		do{
			$a['id']=$a['id']-14;
			$list[]=array(	'id'=>$a['id'],
							'question'=>htmlspecialchars($a['question'],ENT_QUOTES,'UTF-8'),
							'position'=>$a['position'],
							'active'=>$a['ac'],
							'rate'=>$a['rat'],
							'action'=>'<div class="btn-group"><button class="btn btn-info editdep" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remdep" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
						);
		}while ($a = $STH->fetch());
	}

}
catch(PDOException $e){  
	file_put_contents('../php/PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
	$error='An Error has occurred, please read the PDOErrors file and contact a programmer';
}
		
include_once '../php/mobileESP.php';
$uagent_obj = new uagent_info();
$isMob=$uagent_obj->DetectMobileQuick();
if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);

$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}						
if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);

function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}
function retrive_ip(){if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];}elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];}else{$ip=$_SERVER['REMOTE_ADDR'];}return $ip;}

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

		<link rel="stylesheet" type="text/css" href="../min/?g=css_i&amp;5259487"/>
		<link rel="stylesheet" type="text/css" href="../min/?g=css_d&amp;5259487"/>
		<?php if($isMob) { ?>
			<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_m&amp;5259487' ?>"/>
		<?php } ?>
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
							<li><a href="index.php"><i class="glyphicon glyphicon-home"></i> Home</a></li>
							<li><a href="faq.php"><i class="glyphicon glyphicon-flag"></i> FAQs</a></li>
							<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
								<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="glyphicon glyphicon-folder-close"></i> Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="index.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation">
											<a href="newticket.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-folder-close"></i> New Ticket</a>
										</li>
										<li role="presentation">
											<a href="search.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
								<li><a href="setting.php"><i class="glyphicon glyphicon-edit"></i> Account</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li class="active dropdown" role='button'>
										<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
											<i class="glyphicon glyphicon-eye-open"></i> Administration<b class="caret"></b>
										</a>
										<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
											<li role="presentation">
												<a href="admin_setting.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-globe"></i> Site Managment</a>
											</li>
											<li>
												<a href="admin_users.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-user"></i> Users</a>
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
											<li class="active" role="presentation">
												<a href="admin_faq.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-comment"></i> FAQs Managment</a>
											</li>
											<li role="presentation">
												<a href="admin_reported.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-exclamation-sign"></i> Reported Tickets</a>
											</li>
										</ul>
									</li>
								<?php }} if(isset($_SESSION['name'])){ ?>
									<li><a href='#' onclick='javascript:logout();return false;'><i class="glyphicon glyphicon-off"></i> Logout</a></li>
								<?php } ?>
						</ul>
					</div>
				</div>
			</nav>
			<div class='daddy'>
				<hr>
				<div class="jumbotron" >
					<h1 class='pagefun'>Administration - FAQs</h1>
				</div>
				<hr>
				<?php if(!isset($error)){ ?>
					<h3 class='sectname'>FAQs</h3>
					<div id='deplist'>
						<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
						<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="faqtable">
							<tbody>
								<?php 
									$c=count($list);
									for($i=0;$i<$c;$i++)
										echo '<tr><td>'.$list[$i]['id'].'</td><td>'.$list[$i]['question'].'</td><td>'.$list[$i]['position'].'</td><td>'.$list[$i]['active'].'</td><td>'.$list[$i]['rate'].'</td><td>'.$list[$i]['action'].'</td></tr>';
								?>
							</tbody>
						</table>
					</div>
					<br/><br/>
					<div id='faq_div' style='display:none;'>
						<hr>
						<form action='' method='post' class='submit_changes_depa' id='edit_faq'>
							Edit FAQ with ID:<span id='faq_id'></span><button  class='btn btn-link btn_close_form'>Close</button>
							<input type='hidden' id='faq_edit_id' name='faq_edit_id'/>
							<input type='hidden' id='faq_edit_pos' name='faq_edit_pos' />
							<div class='row form-group'>
								<div class='col-md-2'><label for='edit_faq_question'>Question:</label></div>
								<div class='col-md-9'><input type='text' id='edit_faq_question' /></div>
							</div>
							<div class='row form-group'>
								<div class='col-md-2'><label for='edit_faq_answer'>Answer:</label></div>
								<div class='col-md-9'><textarea type='text' id='edit_faq_answer' name='edit_faq_answer' placeholder='Answer'></textarea></div>
							</div>
							<br/>
							<div class='row form-group'>
								<div class='col-md-2'><label for='edit_faq_position'>Position</label></div>
								<div class='col-md-4'><input class='form-control' type='number' id='edit_faq_position' name='edit_faq_position' placeholder='Position, leave blank for last'/></div>
								<div class='col-md-2'><label for='activedep'>Is Active?</label></div>
								<div class='col-md-4'><select class='form-control'  name='edit_faq_active' id='activedep'><option value='1'>Yes</option><option value='0'>No</option></select></div>
							</div>
							<input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' />
						</form>
						<br/><br/>
					</div>
					<hr>
					<h4 class='sectname'>Add New FAQ</h4>
					<form id='newfaqform' action='' method='post'>
							<div class='row form-group'>
								<div class='col-md-2'><label for='question'>Question:</label></div>
								<div class='col-md-10'><input type="text" class='form-control'  name='question' id="question" placeholder="Question" required /></div>
							</div>
							<div class='row form-group'>
								<div class='col-md-2'><label for='answer'>Answer:</label></div>
								<div class='col-md-10'><textarea class='mailmessage form-control' id='answer' rows="5" placeholder='Answer' required></textarea></div>	
							</div>
							<br/>
							<div class='row form-group'>
								<div class='col-md-2'><label for='position'>Position:</label></div>
								<div class='col-md-4'><input type="number" class='form-control'  name='position' id="position" placeholder="Position, leave blank for last"/></div>
								<div class='col-md-2'><label for='activefaq'>Is Active?</label></div>
								<div class='col-md-4'><select class='form-control'  name='activefaq' id='activefaq'><option value='1'>Yes</option><option value='0'>No</option></select>
								</div>
							</div>
						<input type="submit" class="btn btn-success" value='Add New FAQ' onclick='javascript:return false;' id='btnaddfaq'/>
					</form>
				<?php
					}
					else
						echo '<p>'.$error.'</p>';
				?>
				<br/><br/>
			</div>
		</div>
		<?php if(!$isMob) { ?>
			<script type="text/javascript"  src="../min/?g=js_i&amp;5259487"></script>
			<script type="text/javascript"  src="../min/?g=js_d&amp;5259487"></script>
			<script type="text/javascript"  src="../lib/ckeditor/ckeditor.js"></script>
		<?php }else { ?>
			<script type="text/javascript"  src="../min/?g=js_i&amp;5259487"></script>
			<script type="text/javascript"  src="../min/?g=js_d&amp;5259487"></script>
			<script type="text/javascript"  src="../min/?g=js_m&amp;5259487"></script>
		<?php } ?>
	
	<script>
	 $(document).ready(function() {
		var table = $("#faqtable").dataTable({
									bProcessing: !0,
									oLanguage: {sEmptyTable: "No FAQs"},
									fnPreDrawCallback: function(oSettings, json) {
										$('.dataTables_filter').addClass('col-xs-12'),
										$('.dataTables_filter input').addClass('form-control'),
										$('.dataTables_filter input').unwrap(),
										$('.dataTables_filter input').parent().contents().filter(function() {return this.nodeType === 3;}).wrap( "<div class='col-xs-3'></div>"),
										$('.dataTables_filter input').parent().contents().filter(function() {return this.nodeType === 3;}).remove(),
										$('.dataTables_filter input').wrap('<div class="col-xs-9"></div>'),
										$('.dataTables_length').addClass('col-xs-12'),
										$('.dataTables_length select').addClass('form-control'),
										$('.dataTables_length select').unwrap(),
										$('.dataTables_length select').parent().contents().filter(function() {return this.nodeType === 3;}).wrap( "<div class='col-xs-3'></div>"),
										$('.dataTables_length select').parent().contents().filter(function() {return this.nodeType === 3;}).remove(),
										$('.dataTables_length select').wrap('<div class="col-xs-9"></div>')
									},
									aoColumns: [
										{sTitle: "ID",mDataProp: "id",sWidth: "60px",fnCreatedCell: function (a, b, c, d, e) {$(a).html("<span><strong class='visible-xs'>ID: </strong></span><span> " + $(a).html() + "</span>")}}, 
										{sTitle: "Question",mDataProp: "question",fnCreatedCell: function (a, b, c, d, e) {$(a).html("<span><strong class='visible-xs'>Question: </strong></span><span> " + $(a).html() + "</span>")}}, 
										{sTitle: "Position",mDataProp: "position",sWidth: "50px",fnCreatedCell: function (a, b, c, d, e) {$(a).html("<span><strong class='visible-xs'>Position: </strong></span><span> " + $(a).html() + "</span>")}}, 
										{sTitle: "Active",mDataProp: "active",sWidth: "60px",fnCreatedCell: function (a, b, c, d, e) {$(a).html("<span><strong class='visible-xs'>Active: </strong></span><span> " + $(a).html() + "</span>")}}, 
										{sTitle: "Rate",mDataProp: "rate",sWidth: "40px",fnCreatedCell: function (a, b, c, d, e) {$(a).html("<span><strong class='visible-xs'>Rate: </strong></span><span> " + $(a).html() + "</span>")}}, 
										{sTitle: "Toogle",mDataProp: "action",sWidth: "100px",bSortable: !1,bSearchable: !1,fnCreatedCell: function (a, b, c, d, e) {$(a).html("<span><strong class='visible-xs'>Toogle: </strong></span><span> " + $(a).html() + "</span>")}}
									]
								});
		$("#loading").remove();
		$("#faqtable").show(800);
		<?php if(!$isMob) {?>
			CKEDITOR.replace('answer');
			CKEDITOR.replace('edit_faq_answer');
		<?php }else { ?>
			$("#answer").wysihtml5(), $("#edit_faq_answer").wysihtml5();
		<?php }?>
		
		setInterval(function(){
			$.ajax({
				type: 'POST',
				url: '../php/admin_function.php',
				async : 'false',
				data: {<?php echo $_SESSION['token']['act']; ?>:'timeout_update'}
			}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
		},1200000);
		
		$("#faqtable").on("click", ".editdep", function () {
			var b = $(this).val(),
				d = this.parentNode.parentNode.parentNode.parentNode,
				e = table.fnGetPosition(d, null, !0),
				a = table.fnGetData(d);
			$("#edit_faq").hasClass("open") ? confirm("Do you want to close the already opened edit form?") && (b = $.ajax({
				type: "POST",
				url: "../php/admin_function.php",
				data: { <?php echo $_SESSION['token']['act']; ?> : "retrive_faq_answer",id: b},
				dataType: "json",
				success: function (c) {
					if("ret" == c[0]){
						$("#faq_id").html(a.id), 
						$("#faq_id").html(a.id), 
						$("#faq_edit_id").val(a.id), 
						$("#faq_edit_pos").val(e), 
						$("#edit_faq_question").val(a.question), 
						$("#edit_faq_position").val(a.position), 
						<?php if (!$isMob) { ?> CKEDITOR.instances.edit_faq_answer.setData(data[1]) <?php } else { ?> $("#edit_faq_answer").val(data[1]) <?php } ?> , 
						$('select[name="edit_faq_active"]:first option[value=' + ("Yes" == a.active ? 1 : 0) + "]").attr("selected", "selected")
						
					}
					else if(c[0]=='sessionerror'){
							switch(c[1]){
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
						noty({text: "Cannot retrieve Answer. Error: " + c[0],type: "error",timeout: 9E3})
				}
			}), b.fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})
			})) : (b = $.ajax({
				type: "POST",
				url: "../php/admin_function.php",
				data: { <?php echo $_SESSION['token']['act']; ?> : "retrive_faq_answer",id: b},
				dataType: "json",
				success: function (b) {
					if("ret" == b[0]){
						$("#edit_faq").addClass("open"), 
						$("#faq_id").html(a.id), 
						$("#faq_edit_id").val(a.id), 
						$("#faq_edit_pos").val(e), 
						$("#edit_faq_question").val(a.question),
						$("#edit_faq_position").val(a.position), 
						<?php if (!$isMob) { ?> CKEDITOR.instances.edit_faq_answer.setData(b[1]) <?php } else { ?> $("#edit_faq_answer").val(b[1]) <?php } ?> , 
						$("#activedep option[value=" + ("Yes" == a.active ? 1 : 0) + "]").attr("selected", "selected"), 
						$("#faq_div").slideToggle(600)
						
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
						noty({text: "Cannot retrieve Answer. Error: " + b[0],type: "error",timeout: 9E3})
				}
			}), b.fail(function (b, a) {noty({text: a,type: "error",timeout: 9E3})}))
		});
		
		//Add redirect
		$("#faqtable").on("click", ".remdep", function() { var a = $(this).val(), c = table.fnGetPosition(this.parentNode.parentNode.parentNode.parentNode, null, !0); confirm("Do you realy want to delete this FAQ?") && $.ajax({type:"POST", url:"../php/admin_function.php", data:{<?php echo $_SESSION['token']['act']; ?>:"del_faq", id:a}, dataType:"json", success:function(b) { "Deleted" == b[0] ? table.fnDeleteRow(c) : noty({text:"FAQ cannot be deleted. Error: " + b[0], type:"error", timeout:9E3}) }}).fail(function(b, a) { noty({text:a, type:"error", timeout:9E3}) }) });

		//Add redirect
		$("#btnaddfaq").click(function () {var b = $("#question").val().replace(/\s+/g, " "),<?php if (!$isMob) { ?> c = CKEDITOR.instances.answer.getData().replace(/\s+/g, " ") <?php } else { ?> c = $("#answer").val().replace(/\s+/g, ' ') <?php } ?>, d = $("#position").val().replace(/\s+/g, ""); e = $("#activefaq").val(); "" != b.replace(/\s+/g, "") && "" != c.replace(/\s+/g, "") ? $.ajax({type:"POST", url:"../php/admin_function.php", data:{<?php echo $_SESSION['token']['act']; ?>:"add_faq", question:b, answer:c, pos:d, active:e}, dataType:"json", success:function(a) { "Added" == a.response ? ($("#question").val(""), a.information.rate = "Unrated", a.information.action = '<div class="btn-group"><button class="btn btn-info editdep" value="' + a.information.id + '"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remdep" value="' + a.information.id + '"><i class="glyphicon glyphicon-remove"></i></button></div>', table.fnAddData(a.information), $("#faqtable").val(""),<?php if(!$isMob) { ?> CKEDITOR.instances.answer.setData('') <?php }else { ?>$("#answer").val('') <?php } ?>) : noty({text:a[0], type:"error", timeout:9E3}) }}).fail(function(a, f) { noty({text:f, type:"error", timeout:9E3}) }) : noty({text:"Form Error - Empty Field", type:"error", timeout:9E3});});		
		
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
							a[1]['action']='<div class="btn-group"><button class="btn btn-info editdep" value="'+a[1]['id']+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remdep" value="'+a[1]['id']+'"><i class="glyphicon glyphicon-remove"></i></button></div>';

							table.fnDeleteRow(pos, function(){table.fnAddData(a[1])});
							<?php if(!$isMob) { ?>
								CKEDITOR.instances.edit_faq_answer.setData("")
							<?php }else { ?>
								$("#edit_faq_answer").val("");
							<?php } ?>
							$('#faq_div').slideToggle(600);
							$('#edit_faq').removeClass('open');
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