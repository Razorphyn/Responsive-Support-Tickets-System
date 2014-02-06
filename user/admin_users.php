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
if(isset($_COOKIE['RazorphynSupport']) && !is_string($_COOKIE['RazorphynSupport']) || !preg_match('/^[^[:^ascii:];,\s]{26,40}$/',$_COOKIE['RazorphynSupport'])){
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
include_once '../php/config/database.php';
try{
	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

	$query = "SELECT 
				`id`,
				`name`,
				`mail`,
				CASE `status` WHEN '0' THEN 'User'  WHEN '1' THEN 'Operator'  WHEN '2' THEN 'Administrator'  WHEN '3' THEN 'Activation'  WHEN '4' THEN 'Banned' ELSE 'Error' END AS ustat,
				CASE `holiday` WHEN '0' THEN 'No' ELSE 'Yes' END AS hol, 
				CASE WHEN `number_rating`='0' THEN 'No Rating' WHEN `number_rating`!='0' THEN `rating` ELSE 'Error' END AS rt
			FROM ".$SupportUserTable." LIMIT 700";
			
	$STH = $DBH->prepare($query);
	$STH->execute();

	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$c=0;
	$a = $STH->fetch();
	if(!empty($a)){
		$users=array();
		do{
			$a['id']=$a['id']-54;
			$users[]=array(	'num'=>$a['id'],
							'name'=>htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8'),
							'mail'=>htmlspecialchars($a['mail'],ENT_QUOTES,'UTF-8'),
							'status'=>$a['ustat'],
							'holiday'=>$a['hol'],
							'rating'=>$a['rt'],
							'action'=>'<div class="btn-group"><button class="btn btn-info edituser" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remuser" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
						);
			$c++;
		}while ($a = $STH->fetch());
	}
}
catch(PDOException $e){  
	file_put_contents('../php/PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
	$error='An Error has occurred, please read the PDOErrors file and contact a programmer';
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
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?> - Users</title>
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">

		<!--[if lt IE 9]><script src="../js/html5shiv-printshiv.js"></script><![endif]-->
		<link rel="stylesheet" type="text/css" href="../min/?g=css_i&amp;5259487"/>
		<link rel="stylesheet" type="text/css" href="../min/?g=css_d&amp;5259487"/>
		
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
									<li class="active" ><a href="users.php"><i class="glyphicon glyphicon-user"></i>Users</a></li>
									<li class="dropdown" role='button'>
										<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
											<i class="glyphicon glyphicon-eye-open"></i> Administration<b class="caret"></b>
										</a>
										<ul class="active dropdown-menu" aria-labelledby="drop1" role="menu">
											<li role="presentation">
												<a href="admin_setting.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-globe"></i> Site Managment</a>
											</li>
											<li class='active'>
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
											<li role="presentation">
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
					<h1 class='pagefun'>Users Administration Tools</h1>
				</div>
				<hr>
				<?php if(!isset($error)){ ?>
					<h3 class='sectname'>Users</h3>
					<img class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
					<div class='row' id='userlist'>
						<div class='col-md-12'>
							<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="usertable">
								<tbody>
									<?php
										for($i=0;$i<$c;$i++)
											echo '<tr><td>'.$users[$i]['num'].'</td><td>'.$users[$i]['name'].'</td><td>'.$users[$i]['mail'].'</td><td>'.$users[$i]['status'].'</td><td>'.$users[$i]['holiday'].'</td><td>'.$users[$i]['rating'].'</td><td>'.$users[$i]['action'].'</td></tr>';

									?>
								</tbody>
							</table>
						</div>
					</div>
					<br/><br/>
					<hr>
					<p class='cif'><i class='glyphicon glyphicon-plus-sign'></i> Create New User</p>
					<form style='display:none'>
						<h3 class='sectname'>New Users</h3>
						<small><p>Every created user through this function is automatically activated</p></small>
						<div class='row form-group'>
							<div class='col-md-2'><label for='new_rname'>Name</label></div>
							<div class='col-md-4'><input type="text" class='form-control'  id="new_rname" placeholder="Name" required></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-2'><label for='new_rmail'>Email</label></div>
							<div class='col-md-4'><input type="email" class='form-control'  id="new_rmail" placeholder="Email" required></div>
						</div>
						<div class='row form-group'>
							<div class='col-md-2'><label for='new_rmail'>User Role/Status</label></div>
							<div class='col-md-4'>
								<select class='form-control'  id='new_usr_role'>
									<option value='0'>User</option>
									<option value='1'>Operator</option>
									<option value='2'>Administrator</option>
								</select>
							</div>
						</div>
						<input type="submit" id='new_user' onclick='javascript:return !1;' class="btn btn-success" value='Register'/>
					</form>
					<br/>
					<hr>
				<?php 
					} 
					else
						echo '<p>'.$error.'</p>';
				?>
			</div>
		</div>
		<div id='delusr' style='display:none;height:40px' title="Delete this User?">
			<p>Every information will be irreversibly deleted.</p>
		</div>
	
	<script type="text/javascript"  src="../min/?g=js_i&amp;5259487"></script>
	<script type="text/javascript"  src="../min/?g=js_d&amp;5259487"></script>
	<script>
	 $(document).ready(function() {
		var table = $("#usertable").dataTable({
						bProcessing: !0,
						oLanguage: {sEmptyTable: "No Users"},
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
							{sTitle: "Number",mDataProp:"num",sWidth: "60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Number: </strong></span><span>" + $(nTd).html() + '</span>');}}, 
							{sTitle: "Name",mDataProp:"name",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Name: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Mail",mDataProp:"mail",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Mail: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Status/Role",mDataProp:"status",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Status/Role: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Holiday",mDataProp:"holiday",sWidth: "60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Holiday: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Rating",mDataProp:"rating",sWidth: "90px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Rating: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Tooggle",mDataProp:"action",bSortable: !1,bSearchable: !1,sWidth: "100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}
						}]
					});
			
			$('.loading').remove(),$('#usertable').show(800);
		
		<?php if(isset($setting[2])){?>
		$("#senrep option[value="+<?php echo $setting[2];?>+"]").attr('selected','selected');
		<?php } if(isset($setting[3])){?>
		$("#senope option[value="+<?php echo $setting[3];?>+"]").attr('selected','selected');
		<?php } ?>
		
		$('#new_user').click(function(){
			$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});
			var name=$('#new_rname').val();
			var mail=$('#new_rmail').val();
			var role=$('#new_usr_role').val();
			if(name.replace(/\s+/g,'')!='' && mail.replace(/\s+/g,'')!=''){
				$.ajax({
					type: 'POST',
					url: '../php/admin_function.php',
					data: {<?php echo $_SESSION['token']['act']; ?>:'admin_user_add',name: name,mail: mail,role:role},
					dataType : 'json',
					success : function (a) {
						$(".main").nimbleLoader("hide");
						if(a[0]=='Registred'){
							a[1]['action'] = '<div class="btn-group"><button class="btn btn-info edituser" value="' + a[1]['num'] + '"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remuser" value="' + a[1]['num'] + '"><i class="glyphicon glyphicon-remove"></i></button></div>';
							table.fnAddData(a[1]);
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
							noty({text: data[0],type:'error',timeout:9E3});
					}
				}).fail(function(jqXHR, textStatus){$(".main").nimbleLoader("hide");noty({text: textStatus,type:'error',timeout:9E3});});
			}
			else{
				$(".main").nimbleLoader("hide");
				noty({text: 'Empty Field or Password mismatch',type:'error',timeout:9E3});
			}
		});
		
		$("#usertable").on("click", ".edituser", function () {
			$(this).val();
			var b = this.parentNode.parentNode.parentNode.parentNode,
				c = table.fnGetPosition(b, null, !0),
				a = table.fnGetData(b);
			if(0 < $("#" + a.num).length)
				$("html,body").animate({scrollTop: $("#" + a.num).offset().top}, 1500)
			else{
				var b="<hr><form action='' method='post' id='" + a.num + "'><span>Edit " + a.name + "</span><button class='btn btn-link btn_close_form'>Close</button><input type='hidden' name='usr_edit_id' value='" + a.num + "'/><input type='hidden' name='usr_rate' value='" + a.rating + "'/><input type='hidden' name='usr_edit_pos' value='" + c + "'/><input type='hidden' name='usr_old_stat' value='"+a.status+"'/><div class='row form-group'><div class='col-md-2'><label>Name</label></div><div class='col-md-4'><input type='text' class='form-control' name='usr_edit_name' placeholder='User Name' value='" + a.name + "'required /></div><div class='col-md-2'><label>Role/Status</label></div><div class='col-md-4'><select class='form-control'  class='usr_role' name='usr_role'><option value='0'>User</option><option value='1'>Operator</option><option value='2'>Administrator</option><option value='3'>Activation</option><option value='4'>Banned</option></select></div></div><div class='row form-group'><div class='col-md-2'><label>Mail</label></div><div class='col-md-4'><input type='text' class='form-control' name='usr_edit_mail' value='" + a.mail + "' placeholder='User Email' required/></div><div class='col-md-2'><label>On Holiday?</label></div><div class='col-md-4'><select class='form-control'  name='usr_holiday'><option value='0'>No</option><option value='1'>Yes</option></select></div></div><button style='display:none' class='btn btn-info load_usr_depa' value='" + a.num + "' onclick='javascript:return false;'>Load Departments</button><br/><button style='display:none' class='btn btn-info load_usr_rate' value='" + a.num + "' onclick='javascript:return false;'>Load Rates</button><br/><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>";				$("#userlist").after(b);
				$('select[name="usr_role"]:first option').filter(function(){return $(this).html() == a.status}).attr("selected", "selected");
				$('select[name="usr_holiday"]:first option').filter(function () {return $(this).html() == a.holiday}).attr("selected", "selected");
				if("Operator" == a.status) $(".load_usr_depa:first").css("display", "block");
				if("Operator" == a.status || "Administrator" == a.status) $(".load_usr_rate:first").css("display", "block");
				$("html,body").animate({scrollTop: $("#" + a.num).offset().top}, 1500)
			}
		});

		$(document).on("click", ".submit_changes", function () {
			var a = $(this).parent();
			a.children("input").each(function () {
				$(this).attr("disabled", "disabled")
			});
			a.children("select").each(function () {
				$(this).attr("disabled", "disabled")
			});
			var b = a.children('input[name="usr_edit_id"]').val(),
				k = parseInt(a.children('input[name="usr_edit_pos"]').val()),
				e = a.find('input[name="usr_edit_name"]').val().replace(/\s+/g, " "),
				f = a.find('input[name="usr_edit_mail"]').val().replace(/\s+/g, " "),
				g = a.find('select[name="usr_role"]').val(),
				c = a.find('select[name="usr_holiday"]').val(),
				l = a.find('input[name="usr_rate"]').val(),
				h = [];
			"1" == g && a.find('input[name="ass_usr_depa"]:checked').each(function () {
				h.push($(this).val())
			});
			"" != e.replace(/\s+/g, "") && "" != f.replace(/\s+/g, "") ? ($.ajax({
				type: "POST",
				url: "../php/admin_function.php",
				data: {<?php echo $_SESSION['token']['act']; ?>: "update_user_info",id: b,name: e,mail: f,status: g,holiday: c,seldepa: h},
				dataType: "json",
				success: function (d) {
					if("Updated" == d[0]){
						d[1]['action'] = '<div class="btn-group"><button class="btn btn-info edituser" value="' + b + '"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remuser" value="' + b + '"><i class="glyphicon glyphicon-remove"></i></button></div>', 
						d[1]['rating']=($.isNumeric(l))? l:'Unrated',
						table.fnDeleteRow(k, function(){table.fnAddData(d[1])}),
						a.prev().remove(),
						a.remove()
					}
					else if(d[0]=='sessionerror'){
						switch(d[1]){
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
					else{
						a.children("input").each(function () {$(this).removeAttr("disabled", "disabled")});
						a.children("select").each(function () {$(this).removeAttr("disabled", "disabled")});
						noty({text: d[0],type: "error",timeout: 9E3})
					}
				}
			}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})
			})) : noty({text: data[0],type: "Empty Field",timeout: 9E3});
			return !1
		});	
		
		$('#usertable').on('click','.remuser',function(){
			var id=$(this).val();
			var pos=table.fnGetPosition(this.parentNode.parentNode.parentNode.parentNode,null,true);
			$( "#delusr" ).dialog({
				resizable: true,
				height:140,
				modal: true,
				buttons: {
					'Remove User': function() {
						$.ajax({
							type: 'POST',
							url: '../php/admin_function.php',
							data: {<?php echo $_SESSION['token']['act']; ?>:'del_usr',id:id},
							dataType : 'json',
							success : function (data) {
								if(data[0]=='Deleted')
									table.fnDeleteRow(pos);
								else if(data[0]=='sessionerror'){
									switch(data[1]){
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
									noty({text: 'Cannot delete department. Error: '+data[0],type:'error',timeout:9000});
							}
						}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000})});
						$( this ).dialog( "close" );
					},
					'Close': function() {
						$( this ).dialog( "close" );
					}
				}
				
			});
			$(window).resize(function() {
					$("#delusr").dialog("option", "position", "center");
			});
		});
		
		$(document).on("change",'select[name="usr_role"]',function(){"1"==$(this).children("option:selected").val()?$(this).parent().parent().parent().find(".load_usr_depa").css("display","block"):($(this).parent().parent().parent().find(".load_usr_depa").css("display","none"),$(this).parent().parent().parent().find(".user_depa_container").remove())});

		$(document).on("click", ".load_usr_depa", function () {
			$(this).attr('disabled','disabled');
			$(this).after("<img class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>");
			var c = $(this).val(),
				a = $(this);
			$.ajax({
				type: "POST",
				url: "../php/admin_function.php",
				data: {<?php echo $_SESSION['token']['act']; ?>: "select_depa_usr",id: c},
				dataType: "json",
				success: function (b) {
					if("ok" == b.res){
						a.parent().find(".loading").remove(), 
						a.after(b.depa.join(""))
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
						noty({text: b[0],type: "error",timeout: 9E3})
				}
			}).fail(function (b, a) {noty({text: a,type: "error",timeout: 9E3});$(this).removeAttr('disabled');})
		});
		
		$(document).on("click", ".load_usr_rate", function () {
			var a=$(this),
				e = a.parent().find('input[name="usr_old_stat"]').val().replace(/\s+/g,"");
			$(this).attr('disabled','disabled');
			if(e=='Operator' || e=='Administrator'){
				$(this).after("<img class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>");
				var c = $(this).val();
				$.ajax({
					type: "POST",
					url: "../php/admin_function.php",
					data: {<?php echo $_SESSION['token']['act']; ?>: "select_usr_rate",id: c},
					dataType: "json",
					success: function (b) {
						if(b.res=='ok'){
							a.parent().find(".loading").remove();
							var count=b.rate.length;
							if(count>0){
								var tail=new Array();
								for(i=0;i<count;i++)
									tail.push("<div class='row'><div class='col-md-3'>"+b.rate[i][3]+"</div><div class='col-md-3'><a href='view?id="+b.rate[i][2]+"'>View Ticket</a></div><div class='col-md-3'>"+b.rate[i][0]+"</div></div><div class='row info_rate'><div class='col-md-11'>"+b.rate[i][1]+"</div></div>");
								tail=tail.join("");
								a.after("<br/><div class='rate_container'>"+tail+"</div>");
							}
							else
								a.after("<br/><p>This user hasn't got any rating");
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
						else{
							noty({text: b[0],type: "error",timeout: 9E3})
						}
					}
				}).fail(function (b, a) {noty({text: a,type: "error",timeout: 9E3});$(this).removeAttr('disabled');})
			}
			else{
				a.css('display','none');
			}
		});
		
		$('.cif').click(function(){
			el=$(this).children('i');
			if(el.hasClass('glyphicon glyphicon-plus-sign')){
				el.removeClass('glyphicon glyphicon-plus-sign');
				el.addClass('glyphicon glyphicon-minus-sign');
				$(this).next('form').slideToggle(800);
			}
			else{
				el.removeClass('glyphicon glyphicon-minus-sign');
				el.addClass('glyphicon glyphicon-plus-sign');
				$(this).next('form').slideToggle(800);
			}
		});
		
		$(document).on('click','.btn_close_form',function(){if(confirm('Do you want to close this edit form?')){$(this).parent().prev().remove();$(this).parent().remove();}return false;});
		
	});
	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>