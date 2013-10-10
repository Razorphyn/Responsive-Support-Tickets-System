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
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?> - Users</title>
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
								<li><a href="setting.php"><i class="icon-edit"></i>Settings</a></li>
								<li class="active"><a href="users.php"><i class="icon-user"></i>Users</a></li>
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
								<li><a href='#' onclick='javascript:logout();return false;'><i class="icon-off"></i>Logout</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class='daddy'>
				<hr>
				<div class="jumbotron" >
					<h2 class='pagefun'>Users Administration Tools</h2>
				</div>
				<hr>
				<h3 class='sectname'>Users</h3>
				<img class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
				<div class='row-fluid' id='userlist'>
					<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="usertable">
					</table>
				</div>
				<br/><br/>
				<hr>
				<p class='cif'><i class='icon-plus-sign'></i> Create New User</p>
				<form style='display:none'>
					<h3 class='sectname'>New Users</h3>
					<small><p>Every created user through this function is automatically activated</p></small>
					<div class='row-fluid'>
						<div class='span2'><label for='new_rname'>Name</label></div>
						<div class='span4'><input type="text" id="new_rname" placeholder="Name" required></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label for='new_rmail'>Email</label></div>
						<div class='span4'><input type="email" id="new_rmail" placeholder="Email" required></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label for='new_rmail'>User Role/Status</label></div>
						<div class='span4'>
							<select id='new_usr_role'>
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
			</div>
		</div>
		<div id='delusr' style='display:none;height:40px' title="Delete this User?">
			<p>Every information will be irreversibly deleted.</p>
		</div>
	<iframe name='hidden_frame' style='display:none;width:0;height:0'></iframe>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?f=lib/DataTables/js/jquery.dataTables.min.js,js/jquery-ui-1.10.3.custom.min.js&amp;5259487' ?>"></script>
	<script>
	 $(document).ready(function() {
		var table;
		var request = $.ajax({
			type: "POST",
			url: "../php/admin_function.php",
			data: {<?php echo $_SESSION['token']['act']; ?>: "retrive_users"},
			dataType: "json",
			success: function (a) {
				if ("ret" == a.response || "empty" == a.response) {
					var b = a.information.length;
					for (i = 0; i < b; i++) 
						a.information[i].action = '<div class="btn-group"><button class="btn btn-info edituser" value="' + a.information[i].num + '"><i class="icon-edit"></i></button><button class="btn btn-danger remuser" value="' + a.information[i].num + '"><i class="icon-remove"></i></button></div>';
					$(".loading:first").remove();
					table = $("#usertable").dataTable({
						sDom: "<<'span6'l><'span6'f>r>t<<'span6'i><'span6'p>>",
						sWrapper: "dataTables_wrapper form-inline",
						bProcessing: !0,
						aaData: a.information,
						oLanguage: {sEmptyTable: "No Users"},
						aoColumns: [
							{sTitle: "Number",mDataProp: "num",sWidth: "60px",sClass: "visible-desktop",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Number: </strong></span><span>" + $(nTd).html() + '</span>');}}, 
							{sTitle: "Name",mDataProp: "name",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Name: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Mail",mDataProp: "mail",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Mail: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Status/Role",mDataProp: "status",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Status/Role: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Holiday",mDataProp: "holiday",sWidth: "60px",sClass: "hidden-phone",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Holiday: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Rating",mDataProp: "rating",sWidth: "60px",sClass: "hidden-phone",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Rating: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Tooggle",mDataProp: "action",bSortable: !1,bSearchable: !1,sWidth: "60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}
						}]
					})
				}
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
					noty({text: a[0],type: "error",timeout: 9E3})
			}
		});
		request.fail(function (a, b) {noty({text: "Ajax Error: " + b,type: "error",timeout: 9E3})});	
		
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
							a[1]['action'] = '<div class="btn-group"><button class="btn btn-info edituser" value="' + a[1]['num'] + '"><i class="icon-edit"></i></button><button class="btn btn-danger remuser" value="' + a[1]['num'] + '"><i class="icon-remove"></i></button></div>';
							table.fnAddData(a[1]);
						}
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
				var b = "<hr><form action='' method='post' id='" + a.num + "'><span>Edit " + a.name + "</span><button class='btn btn-link btn_close_form'>Close</button><input type='hidden' name='usr_edit_id' value='" + a.num + "'/><input type='hidden' name='usr_rate' value='" + a.rating + "'/><input type='hidden' name='usr_edit_pos' value='" + c + "'/><input type='hidden' name='usr_old_stat' value='"+a.status+"'/><div class='row-fluid'><div class='span2'><label>Name</label></div><div class='span4'><input type='text' name='usr_edit_name' placeholder='Department Name' value='" + a.name + "'required /></div><div class='span2'>Role/Status</div><div class='span4'><select class='usr_role' name='usr_role'><option value='0'>User</option><option value='1'>Operator</option><option value='2'>Administrator</option><option value='3'>Activation</option><option value='4'>Banned</option></select></div></div><div class='row-fluid'><div class='span2'>Mail</div><div class='span4'><input type='text' name='usr_edit_mail' value='" + a.mail + "' required/></div><div class='span2'><label>On Holiday?</label></div><div class='span4'><select name='usr_holiday'><option value='0'>No</option><option value='1'>Yes</option></select></div></div><button style='display:none' class='btn btn-info load_usr_depa' value='" + a.num + "' onclick='javascript:return false;'>Load Departments</button><br/><button style='display:none' class='btn btn-info load_usr_rate' value='" + a.num + "' onclick='javascript:return false;'>Load Rates</button><br/><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>"; 
				$("#userlist").after(b);
				$('select[name="usr_role"]:first option').filter(function(){return $(this).html() == a.status}).attr("selected", "selected");
				$('select[name="usr_holiday"]:first option').filter(function () {return $(this).html() == a.holiday}).attr("selected", "selected");
				if("Operator" == a.status) $(".load_usr_depa:first").css("display", "block");
				if("Operator" == a.status || "Administrator" == a.status) $(".load_usr_rate:first").css("display", "block");
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
						d[1]['action'] = '<div class="btn-group"><button class="btn btn-info edituser" value="' + b + '"><i class="icon-edit"></i></button><button class="btn btn-danger remuser" value="' + b + '"><i class="icon-remove"></i></button></div>', 
						d[1]['rating']=($.isNumeric(l))? l:'Unrated',
						table.fnDeleteRow(k, function(){table.fnAddData(d[1])}),
						a.prev().remove(),
						a.remove()
					}
					else if(d[0]=='sessionex'){
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
						a.children("input").each(function () {$(this).removeAttr("disabled", "disabled")}), 
						a.children("select").each(function () {$(this).removeAttr("disabled", "disabled")}), 
						noty({text: d[0],type: "error",timeout: 9E3}))
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
								else if(data[0]=='sessionex'){
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
						}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
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
									tail.push("<div class='row-fluid'><div class='span3'>"+b.rate[i][3]+"</div><div class='span3'><a href='view?id="+b.rate[i][2]+"'>View Ticket</a></div><div class='span3'>"+b.rate[i][0]+"</div></div><div class='row-fluid info_rate'><div class='span11'>"+b.rate[i][1]+"</div></div>");
								tail=tail.join("");
								a.after("<br/><div class='rate_container'>"+tail+"</div>");
							}
							else
								a.after("<br/><p>This user hasn't got any rating");
						}
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
			if(el.hasClass('icon-plus-sign')){
				el.removeClass('icon-plus-sign');
				el.addClass('icon-minus-sign');
				$(this).next('form').slideToggle(800);
			}
			else{
				el.removeClass('icon-minus-sign');
				el.addClass('icon-plus-sign');
				$(this).next('form').slideToggle(800);
			}
		});
		
		$(document).on('click','.btn_close_form',function(){if(confirm('Do you want to close this edit form?')){$(this).parent().prev().remove();$(this).parent().remove();}return false;});
		
	});
	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>