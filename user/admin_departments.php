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
include_once '../php/config/database.php';
try{
	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$query = "SELECT id,
					department_name,
					CASE active WHEN '1' THEN 'Yes' ELSE 'No' END AS active, 
					CASE public_view WHEN '1' THEN 'Yes' ELSE 'No' END AS public,
					CASE free WHEN '1' THEN 'Yes' ELSE 'No' END AS free
				FROM ".$SupportDepaTable;
		
	$STH = $DBH->prepare($query);
	$STH->execute();
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$a = $STH->fetch();
	$dn=array();
	if(!empty($a)){
		do{
			if($a['free']=='No' && is_file('../php/config/price/'.$a['id'])){
				$rule=file('../php/config/price/'.$a['id'],FILE_IGNORE_NEW_LINES);
				$rule=$rule[0];
			}
			else
				$rule='e';
			switch($rule){
				case 'e':
					$rule='Unnecessary';
					break;
				case 1:
					$rule='Pay per Minute';
					break;
				case 0:
					$rule='Fixed Minute Quantity';
					break;
				default:
					$rule='Error';
			}
			$dn[]=array('id'=>$a['id'],
						'name'=>htmlspecialchars($a['department_name'],ENT_QUOTES,'UTF-8'),
						'active'=>$a['active'],
						'public'=>$a['public'],
						'free'=>$a['free'],
						'rule'=>$rule,
						'action'=>'<div class="btn-group"><button class="btn btn-info editdep" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remdep" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
			);
		}while ($a = $STH->fetch());
	}
}
catch(PDOException $e){
	file_put_contents('../php/PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
	$error='We are sorry, but an error has occurred, please contact the administrator if it persist';
}

if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);

$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}
							
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
							<li><a href="index.php"><i class="glyphicon glyphicon-home"></i>Home</a></li>
							<li><a href="faq.php"><i class="glyphicon glyphicon-flag"></i>FAQs</a></li>
							<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
								<li><a href="newticket.php"><i class="glyphicon glyphicon-file"></i>New Ticket</a></li>
								<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="glyphicon glyphicon-folder-close"></i>Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="index.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation">
											<a href="search.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
								<li><a href="setting.php"><i class="glyphicon glyphicon-edit"></i>Settings</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li><a href="users.php"><i class="glyphicon glyphicon-user"></i>Users</a></li>
									<li class="dropdown" role='button'>
										<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
											<i class="glyphicon glyphicon-eye-open"></i>Administration<b class="caret"></b>
										</a>
										<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
											<li role="presentation">
												<a href="admin_setting.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-globe"></i> Site Managment</a>
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
									<li><a href='#' onclick='javascript:logout();return false;'><i class="glyphicon glyphicon-off"></i>Logout</a></li>
								<?php } ?>
						</ul>
					</div>
				</div>
			</nav>
			<div class='daddy'>
					<hr>
					<div class="jumbotron" >
						<h1 class='pagefun'>Administration - Departments</h1>
					</div>
					<hr>
					<?php if(!isset($error)){ ?>
						<h3 class='sectname'>Departments</h3>
						<div class='row' id='deplist'>
							<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
							<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="deptable">
							<tbody>
							<?php
								$c=count($dn);
								for($i=0;$i<$c;$i++)
									echo '<tr><td>'.$dn[$i]['id'].'</td><td>'.$dn[$i]['name'].'</td><td>'.$dn[$i]['active'].'</td><td>'.$dn[$i]['public'].'</td><td>'.$dn[$i]['free'].'</td><td>'.$dn[$i]['rule'].'</td><td>'.$dn[$i]['action'].'</td></tr>';
							?>
							</tbody>
							</table>
						</div>
						<br/><br/>
						<hr>
						<h4 class='sectname'>Add New Department</h4>
						<form action='' method='post'>
								<div class='row form-group'>
									<div class='col-md-2'><label for='depname'>Name</label></div>
									<div class='col-md-4'><input type="text" class='form-control'  name='depname' id="depname" placeholder="Department Name" required /></div>
								</div>
								
								<div class='row form-group'>
									<div class='col-md-2'><label for='activedep'>Is Active?</label></div>
									<div class='col-md-4'>
										<select class='form-control'  name='activedep' id='activedep'>
											<option value='1'>Yes</option>
											<option value='0'>No</option>
										</select>
									</div>
									<div class='col-md-2'><label for='publicdep'>Is Public?</label></div>
									<div class='col-md-4'>
										<select class='form-control'  name='publicdep' id='publicdep'>
											<option value='1'>Yes</option>
											<option value='0'>No</option>
										</select>
									</div>
								</div>
								
								<div class='row form-group'>
									<div class='col-md-2'><label for='freedep'>Is Free?</label></div>
									<div class='col-md-4'>
										<select class='form-control'  name='freedep' id='freedep'>
											<option value='1'>Yes</option>
											<option value='0'>No</option>
										</select>
									</div>
									<div class='optprem'>
										<div class='col-md-2'><label for='depratetab'>Rate Rules</label></div>
										<div class='col-md-4'>
											<select class='form-control'  name='depratetab' id='depratetab'>
												<option value='1'>Pay per Minute</option>
												<option value='0'>Fixed Minute Quantity</option>
											</select>
										</div>
									</div>
								</div>
								
							<input type="submit" class="btn btn-success" value='Add New Department' onclick='javascript:return false;' id='btnadddep'/>
						</form>
					<?php
						}
						else
							echo '<p>'.$error.'</p>';
					?>
					<br/><br/>
			</div>
		</div>
		<div id='delcat' style='display:none' title="Delete Department?">
			<p>Irriversible Operation</p>
		</div>

		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
		<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
	
	<script>
	$('.optprem').css('display','none');
	$(document).ready(function() {
		var table=$("#deptable").dataTable({
											bDestroy:!0,
											bProcessing:!0,
											oLanguage:{sEmptyTable:"No Departments"},
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
											aoColumns:[
												{sTitle:"ID",mDataProp:"id",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>ID: </strong></span><span> " + $(nTd).html() + '</span>');}},
												{sTitle:"Name",mDataProp:"name",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Name: </strong></span><span> " + $(nTd).html() + '</span>');}},
												{sTitle:"Active",mDataProp:"active",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Active: </strong></span><span> " + $(nTd).html() + '</span>');}},
												{sTitle:"Public",mDataProp:"public",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Public: </strong></span><span> " + $(nTd).html() + '</span>');}},
												{sTitle:"Free",mDataProp:"free",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Free: </strong></span><span> " + $(nTd).html() + '</span>');}},
												{sTitle:"Price Rule",mDataProp:"rule",sWidth:"120px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Price Rule: </strong></span><span> " + $(nTd).html() + '</span>');}},
												{sTitle:"Toogle",mDataProp:"action",sWidth:"100px",bSortable:!1,bSearchable:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}}
											]
								});
		$("#loading").remove(),
		$("#deptable").show(800);

		$('#freedep').change(function(){
			if($('#freedep').val()==0){
				$('.optprem').slideToggle(800),
				$('#freedep').parent().parent().after('<div class="row form-group"><div class="col-xs-2"><label for="depratelist">Price</label><p>[minute]:[label]:[price]<br/>Charset: [0-9]:[a-zA-Z0-9 -]:[0-9 with 2 decimals]<br/>"Fixed Minute Quantity" each row is an option</p></div><div class="col-xs-10"><textarea id="depratelist" name="depratelist" class="form-control"></textarea></div></div> ')
			}
			else{
				$('.optprem').slideToggle(800)
			}
		});

		$("#deptable").on("click", ".editdep", function () {
			$(this).val();
			var a = this.parentNode.parentNode.parentNode.parentNode,
				b = table.fnGetPosition(a, null, !0),
				a = table.fnGetData(a);
			if(0 < $("#" + a.id).length){
				$("html,body").animate({scrollTop: $("#" + a.id).offset().top}, 1500)
			}
			else{
				var b = "<hr><form action='' method='post' class='submit_changes_depa' id='" + a.id + "'><span>Edit " + a.name + "</span><button class='btn btn-link btn_close_form'>Close</button><input type='hidden' name='depa_edit_id' value='" + a.id + "'/><input type='hidden' name='depa_edit_pos' value='" + b + "'/><div class='row form-group'><div class='col-md-2'><label>Name</label></div><div class='col-md-4'><input type='text' class='form-control' name='edit_depa_name' placeholder='Department Name' value='" + a.name + "'required /></div></div><div class='row form-group'><div class='col-md-2'><label>Is Active?</label></div><div class='col-md-4'><select class='form-control'  name='edit_depa_active' id='activedep'><option value='1'>Yes</option><option value='0'>No</option></select></div><div class='col-md-2'><label>Is Public?</label></div><div class='col-md-4'><select class='form-control'  name='edit_depa_public'><option value='1'>Yes</option><option value='0'>No</option></select></div></div><div class='row form-group'><div class='col-md-2'><label>Is Free?</label></div><div class='col-md-4'><select class='form-control'  name='edit_depa_free'><option value='1'>Yes</option><option value='0'>No</option></select></div><div class='optprem'><div class='col-md-2'><label>Rate Rules</label></div><div class='col-md-4'><select class='form-control'  name='edit_depa_rate_rule' ><option value='1'>Pay per Minute</option><option value='0'>Fixed Minute Quantity</option></select></div></div></div><div class='row form-group'><div class='form-group'><button class='lrate btn btn-info'>Load Rates</button></div><div class='row form-group'><div class='col-xs-2'><label >Price</label><p>[minute]:[label]:[price]<br/>Charset: [0-9]:[a-zA-Z0-9 -]:[0-9 with 2 decimals]<br/>'Fixed Minute Quantity' each row is an option</p></div><div class='col-xs-10'><textarea name='edit_depa_rate_table' class='form-control'></textarea></div></div></div><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>";
				$("#deplist").after(b),
				$('select[name="edit_depa_active"]:first option[value=' + ("Yes" == a.active ? 1 : 0) + "]").attr("selected", "selected"),
				$('select[name="edit_depa_free"]:first option[value=' + ("Yes" == a.free ? 1 : 0) + "]").attr("selected", "selected"),
				$('select[name="edit_depa_public"]:first option[value=' + ("Yes" == a.public ? 1 : 0) + "]").attr("selected", "selected"),
				$("html,body").animate({scrollTop: $("#" + a.id).offset().top}, 1500)
			}
		});
		
		$('select[name="edit_depa_free"]').change(function(){
			if($('select[name="edit_depa_free"]').val()==0){
				var p=$('select[name="edit_depa_free"]').parent().parent().parent().parent(),
					id=p.attr('id');
				p.find('.optprem').show(800),
				$.ajax({
						type: 'POST',
						url: '../php/admin_function.php',
						data: {<?php echo $_SESSION['token']['act']; ?>:'retrieve_price_tab',id:id},
						dataType : 'json',
						success : function (data) {
							if(data[0]=='ret')
								p.find('textarea[name="edit_depa_rate_table"]').html(data[1])
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
								noty({text: 'Error: '+data[0],type:'error',timeout:9000});
						}
					}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
			}
			else if($('select[name="edit_depa_free"]').val()==1){
				p.find('.optprem').hide(800)
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
						$.ajax({
							type: 'POST',
							url: '../php/admin_function.php',
							data: {<?php echo $_SESSION['token']['act']; ?>:'del_dep',sub:'del_name',id:id},
							dataType : 'json',
							success : function (data) {
								if(data[0]=='Deleted')
									table.fnDeleteRow(pos)
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
									noty({text: 'Department cannot be deleted. Error: '+data[0],type:'error',timeout:9000});
							}
						}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
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
									table.fnDeleteRow(pos)
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
				d = $("#publicdep").val(),
				e = $("#freedep").val(),
				f = $("#depratetab").val(),
				g = $("#depratelist").val();
			if(b.replace(/\s+/g, "")!=""){
				$.ajax({
					type: "POST",
					url: "../php/admin_function.php",
					data: { <?php echo $_SESSION['token']['act']; ?> : "add_depart",tit: b,active: c,pubdep: d,freedep: e, ratetype:f, ratetable:g},
					dataType: "json",
					success: function (a) {
						if("Added" == a.response){
							a.information.action = '<div class="btn-group"><button class="btn btn-info editdep" value="' + a.information.id + '"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remdep" value="' + a.information.id + '"><i class="glyphicon glyphicon-remove"></i></button></div>',
							table.fnAddData(a.information),
							$("#depname").val("")
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
							noty({text: a[0],type: "error",timeout: 9E3})
					}
				}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})})
			}
			else
				noty({text: "Form Error - Empty Field",type: "error",timeout: 9E3})
		});

		$(document).on("click", ".submit_changes",function (){
			var a = $(this).parent(),
				b = a.children('input[name="depa_edit_id"]').val(),
				g = parseInt(a.children('input[name="depa_edit_pos"]').val()),
				f = a.find('input[name="edit_depa_name"]').val().replace(/\s+/g, " "),
				c = a.find('select[name="edit_depa_active"]').val(),
				d = a.find('select[name="edit_depa_public"]').val(),
				h = a.find('select[name="edit_depa_free"]').val(),
				k = a.find('select[name="edit_depa_rate_rule"]').val(),
				l = a.find('textarea[name="edit_depa_rate_table"]').val();
			"" != f.replace(/\s+/g, "") ? $.ajax({
				type: "POST",
				url: "../php/admin_function.php",
				data: {<?php echo $_SESSION['token']['act']; ?>: "edit_depart",id: b,name: f,active: c,pub: d,freedep:h,ratetype:k,ratetable:l},
				dataType: "json",
				success: function (e) {
					if("Succeed" == e[0]){
						e[1]['action'] = '<div class="btn-group"><button class="btn btn-info editdep" value="' + e[1]['id'] + '"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remdep" value="' + e[1]['id'] + '"><i class="glyphicon glyphicon-remove"></i></button></div>', 
						table.fnDeleteRow(g, function () {table.fnAddData(e[1])}), 
						a.prev().remove(), 
						a.remove()
					}
					else if(e[0]=='sessionerror'){
						switch(e[1]){
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
						noty({text: e[0],type: "error",timeout: 9E3})
				}
			}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})}) : noty({text: "Form Error - Empty Fields",type: "error",timeout: 9E3})
		});
		
		$(document).on("click",".btn_close_form",function(){confirm("Do you want to close this edit form?")&&($(this).parent().prev().remove(),$(this).parent().remove());return!1});
	});

	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>