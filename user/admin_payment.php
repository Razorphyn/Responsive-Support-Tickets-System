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
	header("location: ../index.php?e=expired");
	exit();
}
else if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	header("location: ../index.php?e=local");
	exit();
}
else if(!isset($_POST[$_SESSION['token']['act']]) && !isset($_POST['act']) && $_POST['act']!='faq_rating' || $_POST['token']!=$_SESSION['token']['faq']){
	session_unset();
	session_destroy();
	header("location: ../index.php?e=token");
	exit();
}
else if(!isset($_SESSION['status']) || $_SESSION['status']!=2){
	header('Content-Type: application/json; charset=utf-8');
	header("location: ../index.php");
	exit();
}

if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);

if(is_file('../php/config/payment/paypal.txt')) $ppsetting=file('../php/config/payment/moneybooker.txt',FILE_IGNORE_NEW_LINES);
if(is_file('../php/config/payment/moneybooker.txt')) $mbsetting=file('../php/config/payment/moneybooker.txt',FILE_IGNORE_NEW_LINES);

$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

include_once '../php/config/database.php';
try{
	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

	$query = "SELECT 
				`id`,
				`gateway`,
				CASE `status` WHEN '0' THEN '<span class=\'label label-warning\'>Pending</span>'  WHEN '1' THEN '<span class=\'label label-important\'>Cancelled</span>'  WHEN '2' THEN '<span class=\'label label-success\'>Processed</span>'  WHEN '3' THEN '<span class=\'label label-inverse\'>Refund</span>'  ELSE `status` END AS sale_stat,
				`payer_mail`,
				`transaction_id`,
				`tk_id`,
				`amount`,
				`support_time`,
				`payment_date`,
			FROM ".$SupportSalesTable." LIMIT 700 ORDER BY `payment_date`";
			
	$STH = $DBH->prepare($query);
	$STH->execute();

	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$c=0;
	$a = $STH->fetch();
	if(!empty($a)){
		$users=array();
		do{
			$a['id']=$a['id']+1;
			$users[]=array(	'ID'=>$a['id'],
							'gateway'=>htmlspecialchars($a['gateway'],ENT_QUOTES,'UTF-8'),
							'payer_mail'=>htmlspecialchars($a['payer_mail'],ENT_QUOTES,'UTF-8'),
							'status'=>$a['sale_stat'],
							'transaction_id'=>$a['transaction_id'],
							'tk_id'=>$a['tk_id'],
							'amount'=>$a['amount'],
							'support_time'=>$a['support_time'],
							'payment_date'=>$a['payment_date'],
							'action'=>'<div class="btn-group"><button class="btn btn-info edituser" value="'.$a['id'].'"><i class="icon-edit"></i></button><button class="btn btn-danger remuser" value="'.$a['id'].'"><i class="icon-remove"></i></button></div>'
						);
			$c++;
		}while ($a = $STH->fetch());
	}
}
catch(PDOException $e){  
	file_put_contents('../php/PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
	$error='An Error has occurred, please read the PDOErrors file and contact a programmer';
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
											<a href="admin_payment.php" tabindex="-1" role="menuitem"><i class="icon-exclamation-sign"></i> Payment Setting/List</a>
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
					<h2 class='pagefun'>Administration - Payment Setting</h2>
				</div>
				<hr>
				<form id='paypal_setting' action=''>
					<h3 class='sectname'>Paypal Setting</h3>
					<div class='row-fluid'>
						<div class='span2'><label>Enabled</label></div>
						<div class='span4'>
							<select name='enpp' id='enpp'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Merchant Mail</label></div>
						<div class='span4'><input type="email" name='ppmail' id="ppmail" <?php if(isset($ppsetting[1])) echo 'value="'.$ppsetting[1].'"';?> placeholder="Merchant Email" required /></div>
						<div class='span2'><label>Currency</label></div>
						<div class='span4'><input type="text" name='ppcurrency' id="ppcurrency" <?php if(isset($ppsetting[2])) echo 'value="'.$ppsetting[2].'"';?> placeholder="Currency" required /></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Enable Sandbox</label></div>
						<div class='span4'>
							<select name='enppsand' id='enppsand'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
						<div class='span2'><label>Enable CURL</label></div>
						<div class='span4'>
							<select name='enppcurl' id='encurl'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
					</div>
					<input type="submit" class="btn btn-success" value='Save' id='saveoptpay'/>
				</form>
				<br/><br/>
				<hr>
				<form id='moneybookers_payment' action=''>
					<h3 class='sectname'>MoneyBookers Setting</h3>
					<div class='row-fluid'>
						<div class='span2'><label>Enabled</label></div>
						<div class='span4'>
							<select name='enmb' id='enmb'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Merchant ID</label></div>
						<div class='span4'><input type="text" name='mbmercid' id="mbmercid" <?php if(isset($mbsetting[1])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Merchant ID" required /></div>
						<div class='span2'><label>Merchant Mail</label></div>
						<div class='span4'><input type="email" name='mbmail' id="mbmail" <?php if(isset($mbsetting[2])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Merchant Email" required /></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Secret Word</label></div>
						<div class='span4'><input type="text" name='mbsword' id="mbsword" <?php if(isset($mbsetting[5])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Secret Word" required /></div>
					</div>
					<div class='row-fluid'>
						<div class='span2'><label>Currency</label></div>
						<div class='span4'><input type="text" name='mbcurrency' id="mbcurrency" <?php if(isset($mbsetting[3])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Currency" required /></div>
						<div class='span2'><label>Company Name</label></div>
						<div class='span4'><input type="text" name='mbcompanyname' id="mbcompanyname" <?php if(isset($mbsetting[4])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Company Name" /></div>
					</div>
					<input type="submit" class="btn btn-success" value='Save' id='saveoptmoney'/>
				</form>
				<br/><br/>
				<hr>
				<div class="jumbotron" >
					<h2 class='pagefun'>Administration - Payment List</h2>
				</div>
				<hr>
				<div class='row-fluid'>
					<img id='loading' class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
					<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="payment_table">
						<tbody>
							<?php
								for($i=0;$i<$c;$i++)
									echo '<tr><td>'.$users[$i]['id'].'</td><td>'.$users[$i]['payment_date'].'</td><td>'.$users[$i]['gateway'].'</td><td>'.$users[$i]['status'].'</td><td>'.$users[$i]['payer_mail'].'</td><td>'.$users[$i]['transaction_id'].'</td><td>'.$users[$i]['amount'].'</td><td>'.$users[$i]['support_time'].'</td></tr>';
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<iframe name='hidden_frame' style='display:none;width:0;height:0' src="about:blank" ></iframe>
	
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>

	<script type="text/javascript">
	$(document).ready(function() {

		<?php if(isset($ppsetting[0])){?>
			$("#enpp > option[value='<?php echo $ppsetting[0];?>']").attr('selected','selected');
		<?php } if(isset($ppsetting[3])){?>
			$("#enppsand > option[value='<?php echo $ppsetting[3];?>']").attr('selected','selected');
		<?php } if(isset($ppsetting[4])){?>
			$("#enppcurl > option[value='<?php echo $ppsetting[4];?>']").attr('selected','selected');
		<?php } if(isset($mbsetting[0])){?>
			$("#enmb > option[value='<?php echo $mbsetting[0];?>']").attr('selected','selected');
		<?php } ?>

		var table = $("#payment_table").dataTable({
						sDom: "<<'span6'l><'span6'f>r>t<<'span6'i><'span6'p>>",
						sWrapper: "dataTables_wrapper form-inline",
						bProcessing: !0,
						oLanguage: {sEmptyTable: "No Payments"},
						aoColumns: [
							{sTitle: "ID",			mDataProp: "id",				sWidth: "50px",										fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>ID: </strong></span><span>" + $(nTd).html() + '</span>');}}, 
							{sTitle: "Date",		mDataProp: "payment_date",		sWidth: "80px",										fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Date: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Gateway",		mDataProp: "gateway",																fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Gateway: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Status",		mDataProp: "status",			sWidth: "50px",												fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Status: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Mail",		mDataProp: "payer_mail",															fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Mail: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Payment ID",	mDataProp: "transaction_id",														fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Payment ID: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Amount",		mDataProp: "amount",			sWidth: "60px",										fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Amount: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle: "Time",		mDataProp: "support_time",		sWidth: "60px",										fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Time: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle: "Tooggle",		mDataProp: "action",			sWidth: "60px",	bSortable: !1,	bSearchable: !1,	fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}}
						]
					});
		$('.loading').remove();
		$('table:hidden').each(function(){
			$(this).show(400);
		});
		
		$("#payment_table").on("click", ".edituser", function () {
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
				data: {<?php echo $_SESSION['token']['act']; ?>: "edit_sale_info",id: b,name: e,mail: f,status: g,holiday: c,seldepa: h},
				dataType: "json",
				success: function (d) {
					if("Updated" == d[0]){
						d[1]['action'] = '<div class="btn-group"><button class="btn btn-info edituser" value="' + b + '"><i class="icon-edit"></i></button><button class="btn btn-danger remuser" value="' + b + '"><i class="icon-remove"></i></button></div>', 
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
							data: {<?php echo $_SESSION['token']['act']; ?>:'del_sale',id:id},
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

		$("#saveoptmoney").click(function(){
			var a=$("#enmb").val(),
				c=$("#mbmercid").val().replace(/\s+/g,""),
				d=$("#mbmail").val().replace(/\s+/g,""),
				e=$("#mbcurrency").val().replace(/\s+/g,""),
				f=$("#mbcompanyname").val();
			if(""!=a && ""!=c &&""!=d &&""!=e){
				$.ajax({
					type:"POST",
					url:"../php/admin_function.php",
					data:{<?php echo $_SESSION['token']['act']; ?>:"save_moneybookers",en:a,mer_id:c,mail:d,currency:e,compname:f},
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
							noty({text:"Options cannot be saved. Error: "+ b[0],type:"error",timeout:9E3})
					}
				}).fail(function(b,a){noty({text:a,type:"error",timeout:9E3})});
			}
			else
				noty({text:"Please complete all the required fields",type:"error",timeout:9E3})
			return!1
		});
		
		$("#saveoptpay").click(function(){
			var a=$("#enpp").val().replace(/\s+/g,""),
				c=$("#ppmail").val().replace(/\s+/g,""),
				d=$("#ppcurrency").val().replace(/\s+/g,""),
				e=$("#enppsand").val(),
				f=$("#enppcurl").val();
			if(""!=a && ""!=c &&""!=d &&""!=e){
				$.ajax({
					type:"POST",
					url:"../php/admin_function.php",
					data:{<?php echo $_SESSION['token']['act']; ?>:"save_moneybookers",en:a,mer_id:c,mail:d,currency:e,compname:f},
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
							noty({text:"Options cannot be saved. Error: "+ b[0],type:"error",timeout:9E3})
					}
				}).fail(function(b,a){noty({text:a,type:"error",timeout:9E3})});
			}
			else
				noty({text:"Please complete all the required fields",type:"error",timeout:9E3})
			return!1
		});

	});

	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():noty({text: a[0],type:'error',timeout:9E3})}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>