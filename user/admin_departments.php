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
if(isset($_COOKIE['RazorphynSupport']) && !empty($_COOKIE['RazorphynSupport']) && !preg_match('/^[a-z0-9]{26,40}$/',$_COOKIE['RazorphynSupport'])){
	unset($_COOKIE['RazorphynSupport']);
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

if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);

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
										<li role="presentation" class='active'>
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
					<h2 class='pagefun'>Administration - Departments</h2>
				</div>
				<hr>
				<h3 class='sectname'>Departments</h3>
				<div class='row-fluid' id='deplist'>
					<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
					<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="deptable">
					</table>
				</div>
				<br/><br/>
				<hr>
				<h4 class='sectname'>Add New Department</h4>
				<form action='' method='post'>
						<div class='row-fluid'>
							<div class='span2'><label for='depname'>Name</label></div>
							<div class='span4'><input type="text" name='depname' id="depname" placeholder="Department Name" required /></div>
						</div>
						<div class='row-fluid'>
							<div class='span2'><label for='activedep'>Is Active?</label></div>
							<div class='span4'>
								<select name='activedep' id='activedep'>
									<option value='1'>Yes</option>
									<option value='0'>No</option>
								</select>
							</div>
							<div class='span2'><label for='publicdep'>Is Public?</label></div>
							<div class='span4'>
								<select name='publicdep' id='publicdep'>
									<option value='1'>Yes</option>
									<option value='0'>No</option>
								</select>
							</div>
						</div>
					<input type="submit" class="btn btn-success" value='Add New Department' onclick='javascript:return false;' id='btnadddep'/>
				</form>
				<br/><br/>
			</div>
		</div>
		<div id='delcat' style='display:none' title="Delete Department?">
			<p>Irriversible Operation</p>
		</div>
	<iframe name='hidden_frame' style='display:none;width:0;height:0'></iframe>

		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
	
	<script>
	 $(document).ready(function() {
		var table;
		var request=$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"retrive_depart",sect:"admin"},dataType:"json",success:function(a){if("ret"==a.response||"empty"==a.response){if("ret"==a.response){var b=a.information.length;for(i=0;i<b;i++)a.information[i].action='<div class="btn-group"><button class="btn btn-info editdep" value="'+a.information[i].id+'"><i class="icon-edit"></i></button><button class="btn btn-danger remdep" value="'+a.information[i].id+'"><i class="icon-remove"></i></button></div>'}$("#loading").remove(); table=$("#deptable").dataTable({sDom:"<<'span6'l><'span6'f>r>t<<'span6'i><'span6'p>>",sWrapper:"dataTables_wrapper form-inline",bDestroy:!0,bProcessing:!0,aaData:a.information,oLanguage:{sEmptyTable:"No Departments"},aoColumns:[{sTitle:"ID",mDataProp:"id",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>ID: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Name",mDataProp:"name",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Name: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Active",mDataProp:"active",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Active: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Public",mDataProp:"public",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Public: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Toogle",mDataProp:"action",bSortable:!1,bSearchable:!1,sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}}]})}else noty({text:a[0], type:"error",timeout:9E3})}});request.fail(function(a,b){alert("Ajax Error: "+b)});		
				
		$("#deptable").on("click", ".editdep", function () {
			$(this).val();
			var a = this.parentNode.parentNode.parentNode.parentNode,
				b = table.fnGetPosition(a, null, !0),
				a = table.fnGetData(a);
			if(0 < $("#" + a.id).length){
				$("html,body").animate({scrollTop: $("#" + a.id).offset().top}, 1500)
			}
			else{
				b = "<hr><form action='' method='post' class='submit_changes_depa' id='" + a.id + "'><span>Edit " + a.name + "</span><button class='btn btn-link btn_close_form'>Close</button><input type='hidden' name='depa_edit_id' value='" + a.id + "'/><input type='hidden' name='depa_edit_pos' value='" + b + "'/><div class='row-fluid'><div class='span2'><label>Name</label></div><div class='span4'><input type='text' name='edit_depa_name' placeholder='Department Name' value='" + a.name + "'required /></div></div><div class='row-fluid'><div class='span2'><label>Is Active?</label></div><div class='span4'><select name='edit_depa_active' id='activedep'><option value='1'>Yes</option><option value='0'>No</option></select></div><div class='span2'><label>Is Public?</label></div><div class='span4'><select name='edit_depa_public'><option value='1'>Yes</option><option value='0'>No</option></select></div></div><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>",
				$("#deplist").after(b),
				b = "Yes" == a["public"] ? 1 : 0,
				$('select[name="edit_depa_active"]:first option[value=' + ("Yes" == a.active ? 1 : 0) + "]").attr("selected", "selected"),
				$('select[name="edit_depa_public"]:first option[value=' + b + "]").attr("selected", "selected")
			}
		});
		
		$('#deptable').on('click','.remdep',function(){
			var id=$(this).val();
			var pos=table.fnGetPosition(this.parentNode.parentNode.parentNode.parentNode,null,true);
			$( "#delcat" ).dialog({
				resizable: true,
				height:200,
				modal: true,
				buttons: {
					"Keep Related Tickets": function() {
						var request= $.ajax({
							type: 'POST',
							url: '../php/admin_function.php',
							data: {<?php echo $_SESSION['token']['act']; ?>:'del_dep',sub:'del_name',id:id},
							dataType : 'json',
							success : function (data) {
								if(data[0]=='Deleted')
									table.fnDeleteRow(pos);
								else
									noty({text: 'Department cannot be deleted. Error: '+data[0],type:'error',timeout:9000});
							}
						});
						request.fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
						$( this ).dialog( "close" );
					},
					"Every Information": function() {
						var request= $.ajax({
							type: 'POST',
							url: '../php/admin_function.php',
							data: {<?php echo $_SESSION['token']['act']; ?>:'del_dep',sub:'del_every',id:id},
							dataType : 'json',
							success : function (data) {
								if(data[0]=='Deleted')
									table.fnDeleteRow(pos);
								else
									noty({text: 'Department cannot be deleted. Error: '+data[0],type:'error',timeout:9000});
							}
						});
						request.fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
						$( this ).dialog( "close" );
					},
					'Close': function() {
						$( this ).dialog( "close" );
					}
				}
				
			});
			$(window).resize(function() {$("#delcat").dialog("option", "position", "center");});
		});
		
		$("#btnadddep").click(function () {
			var b = $("#depname").val().replace(/\s+/g, " "),
				c = $("#activedep").val(),
				d = $("#publicdep").val();
			"" != b.replace(/\s+/g, "") ? $.ajax({
				type: "POST",
				url: "../php/admin_function.php",
				data: { <?php echo $_SESSION['token']['act']; ?> : "add_depart",tit: b,active: c,pubdep: d},
				dataType: "json",
				success: function (a) {
					if("Added" == a.response){
						a.information.action = '<div class="btn-group"><button class="btn btn-info editdep" value="' + a.information.id + '"><i class="icon-edit"></i></button><button class="btn btn-danger remdep" value="' + a.information.id + '"><i class="icon-remove"></i></button></div>',
						table.fnAddData(a.information),
						$("#depname").val("")
					}
					else
						noty({text: a[0],type: "error",timeout: 9E3})
				}
			}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})}) : noty({text: "Form Error - Empty Field",type: "error",timeout: 9E3})
		});
		
		$(document).on("click", ".submit_changes",function (){
			var a = $(this).parent(),
				b = a.children('input[name="depa_edit_id"]').val(),
				g = parseInt(a.children('input[name="depa_edit_pos"]').val()),
				f = a.find('input[name="edit_depa_name"]').val().replace(/\s+/g, " "),
				c = a.find('select[name="edit_depa_active"]').val(),
				d = a.find('select[name="edit_depa_public"]').val();
			"" != f.replace(/\s+/g, "") ? $.ajax({
				type: "POST",
				url: "../php/admin_function.php",
				data: {<?php echo $_SESSION['token']['act']; ?>: "edit_depart",id: b,name: f,active: c,pub: d},
				dataType: "json",
				success: function (e) {
					if("Succeed" == e[0]){
						e[1]['action'] = '<div class="btn-group"><button class="btn btn-info editdep" value="' + e[1]['id'] + '"><i class="icon-edit"></i></button><button class="btn btn-danger remdep" value="' + e[1]['id'] + '"><i class="icon-remove"></i></button></div>', 
						table.fnDeleteRow(g, function () {table.fnAddData(e[1])}), 
						a.prev().remove(), 
						a.remove()
					}
					else
						noty({text: e[0],type: "error",timeout: 9E3})
				}
			}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})}) : noty({text: "Form Error - Empty Fields",type: "error",timeout: 9E3})
		});

		$("#btnadddep").click(function () {
			var b = $("#depname").val().replace(/\s+/g, " "),
				c = $("#activedep").val(),
				d = $("#publicdep").val();
			"" != b.replace(/\s+/g, "") ? $.ajax({
				type: "POST",
				url: "../php/admin_function.php",
				data: {<?php echo $_SESSION['token']['act']; ?>: "add_depart",tit: b,active: c,pubdep: d},
				dataType: "json",
				success: function (a) {
					if("Added" == a.response){
						a.information.action = '<div class="btn-group"><button class="btn btn-info editdep" value="' + a.information.id + '"><i class="icon-edit"></i></button><button class="btn btn-danger remdep" value="' + a.information.id + '"><i class="icon-remove"></i></button></div>',
						table.fnAddData(a.information),
						$("#depname").val("")
					}
					else
						noty({text: a[0],type: "error",timeout: 9E3})
				}
			}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})}) : noty({text: "Form Error - Empty Field",type: "error",timeout: 9E3})
		});
		
		$(document).on("click",".btn_close_form",function(){confirm("Do you want to close this edit form?")&&($(this).parent().prev().remove(),$(this).parent().remove());return!1});
	});

	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>