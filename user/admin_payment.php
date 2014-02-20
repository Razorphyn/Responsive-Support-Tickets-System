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
				`payment_date`
			FROM ".$SupportSalesTable." ORDER BY `payment_date` DESC LIMIT 700 ";
			
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
							'action'=>'<div class="btn-group"><button class="btn btn-info edituser" value="'.$a['id'].'"><i class="glyphicon glyphicon-eye-open"></i></button><button class="btn btn-danger remuser" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
						);
			$c++;
		}while ($a = $STH->fetch());
	}
}
catch(PDOException $e){  
	file_put_contents('../php/PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
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
							<a class="navbar-brand" href='../'><?php if(isset($setting[0])) echo $setting[0];?></a>
					</div>
		  
					<div class="collapse navbar-collapse" id="header-nav-collapse">
						<ul class="nav navbar-nav">
							<li><a href="../"><i class="glyphicon glyphicon-home"></i>Home</a></li>
							<li><a href="faq.php"><i class="glyphicon glyphicon-flag"></i> FAQs</a></li>
							<?php if(isset($_SESSION['name']) && isset($_SESSION['status']) && $_SESSION['status']<3){ ?>
								<li><a href="newticket.php"><i class="glyphicon glyphicon-file"></i>New Ticket</a></li>
								<li class="dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="glyphicon glyphicon-folder-close"></i> Tickets<b class="caret"></b>
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
								<li><a href="setting.php"><i class="glyphicon glyphglyphicon glyphicon-eye-open"></i> Account</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li><a href="admin_users.php"><i class="glyphicon glyphicon-user"></i>Users</a></li>
									<li class="dropdown active" role='button'>
										<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
											<i class="glyphicon glyphicon-eye-open"></i> Administration<b class="caret"></b>
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
											<li class='active' role="presentation">
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
					<h1 class='pagefun'>Administration - Payment Setting</h1>
				</div>
				<hr>
				<form id='paypal_setting' action=''>
					<h3 class='sectname'>Paypal Setting</h3>
					<div class='row form-group'>
						<div class='col-md-2'><label>Enabled</label></div>
						<div class='col-md-4'>
							<select class='form-control'  name='enpp' id='enpp'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
					</div>
					<div class='row form-group'>
						<div class='col-md-2'><label>Merchant Mail</label></div>
						<div class='col-md-4'><input type="email" class='form-control'  name='ppmail' id="ppmail" <?php if(isset($ppsetting[1])) echo 'value="'.$ppsetting[1].'"';?> placeholder="Merchant Email" required /></div>
						<div class='col-md-2'><label>Currency</label></div>
						<div class='col-md-4'><input type="text" class='form-control'  name='ppcurrency' id="ppcurrency" <?php if(isset($ppsetting[2])) echo 'value="'.$ppsetting[2].'"';?> placeholder="Currency" required /></div>
					</div>
					<div class='row form-group'>
						<div class='col-md-2'><label>Enable Sandbox</label></div>
						<div class='col-md-4'>
							<select class='form-control'  name='enppsand' id='enppsand'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
						<div class='col-md-2'><label>Enable CURL</label></div>
						<div class='col-md-4'>
							<select class='form-control'  name='enppcurl' id='encurl'>
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
					<div class='row form-group'>
						<div class='col-md-2'><label>Enabled</label></div>
						<div class='col-md-4'>
							<select class='form-control'  name='enmb' id='enmb'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</div>
					</div>
					<div class='row form-group'>
						<div class='col-md-2'><label>Merchant ID</label></div>
						<div class='col-md-4'><input type="text" class='form-control'  name='mbmercid' id="mbmercid" <?php if(isset($mbsetting[1])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Merchant ID" required /></div>
						<div class='col-md-2'><label>Merchant Mail</label></div>
						<div class='col-md-4'><input type="email" class='form-control'  name='mbmail' id="mbmail" <?php if(isset($mbsetting[2])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Merchant Email" required /></div>
					</div>
					<div class='row form-group'>
						<div class='col-md-2'><label>Secret Word</label></div>
						<div class='col-md-4'><input type="text" class='form-control'  name='mbsword' id="mbsword" <?php if(isset($mbsetting[5])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Secret Word" required /></div>
					</div>
					<div class='row form-group'>
						<div class='col-md-2'><label>Currency</label></div>
						<div class='col-md-4'><input type="text" class='form-control'  name='mbcurrency' id="mbcurrency" <?php if(isset($mbsetting[3])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Currency" required /></div>
						<div class='col-md-2'><label>Company Name</label></div>
						<div class='col-md-4'><input type="text" class='form-control'  name='mbcompanyname' id="mbcompanyname" <?php if(isset($mbsetting[4])) echo 'value="'.$mbsetting[1].'"';?> placeholder="Company Name" /></div>
					</div>
					<input type="submit" class="btn btn-success" value='Save' id='saveoptmoney'/>
				</form>
				<br/><br/>
				<hr>
				<div class="jumbotron" >
					<h1 class='pagefun'>Administration - Payment List</h1>
				</div>
				<hr>
				<div class='row form-group'>
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
				<br/><br/>
			</div>
		</div>
		<iframe name='hidden_frame' style='display:none;width:0;height:0' src="about:blank" ></iframe>
	
	<script type="text/javascript"  src="../min/?g=js_i&amp;5259487"></script>
	<script type="text/javascript"  src="../min/?g=js_d&amp;5259487"></script>

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
						bProcessing: !0,
						oLanguage: {sEmptyTable: "No Payments"},
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
							{sTitle: "ID",			mDataProp: "id",								sWidth: "25px",										fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>ID: </strong></span><span>" + $(nTd).html() + '</span>');}}, 
							{sTitle: "Date",		mDataProp: "payment_date",						sWidth: "100px",									fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Date: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Gateway",		mDataProp: "gateway",							sWidth: "120px",									fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Gateway: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Status",		mDataProp: "status",							sWidth: "50px",										fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Status: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Mail",		mDataProp: "payer_mail",		bVisible: !1,														fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Mail: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Payment ID",	mDataProp: "transaction_id",	bVisible: !1,														fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Payment ID: </strong></span><span> " + $(nTd).html() + '</span>');}}, 
							{sTitle: "Amount",		mDataProp: "amount",							sWidth: "80px",										fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Amount: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle: "Time",		mDataProp: "support_time",						sWidth: "80px",										fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Time: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle: "Tooggle",		mDataProp: "action",							sWidth: "100px",bSortable: !1,	bSearchable: !1,	fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}}
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
				var b = "<hr><form action='../php/admin_function.php' method='POST' id='" + a.id + "'><input type='hidden' name='paym_edit_id' value='"+ a.id +"' /><input type='hidden' name='paym_edit_tranid' value='"+ a.transaction_id +"' /><span>Payment ID " + a.id + "</span><button class='btn btn-link btn_close_form'>Close</button><div class='row'><div class='col-md-2'><label>Gateway</label></div><div class='col-md-4'><p>" + a.gateway + "</p></div><div class='col-md-2'><label>Date</label></div><div class='col-md-4'><p>" + a.payment_date + "</p></div></div><div class='row'><div class='col-md-2'><label>Payer Email</label></div><div class='col-md-4'><p>" + a.payer_mail + "</p></div><div class='col-md-2'><label>Transaction ID</label></div><div class='col-md-4'><p>" + a.transaction_id + "</p></div></div><div class='row'><div class='col-md-2'>Amount</div><div class='col-md-4'><input type='text' name='paym_edit_amount' value='" + a.amount + "' placeholder='Amount' required/></div><div class='col-md-2'>Support Time</div><div class='col-md-4'><input type='text' name='paym_edit_time' value='" + a.support_time + "' placeholder='Support Time' required/></div></div><div class='row'><div class='col-md-2'><label>Status</label></div><div class='col-md-4'><select class='form-control'  name='paym_edit_status'><option value='2'>Completed</option><option value='0'>Pending</option><option value='1'>Failed</option><option value='1'>Expired</option><option value='3'>Refunded</option><option value='4'>Partially Refunded</option></select></div></div><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>";
				$("#userlist").after(b);
				$('select[name="paym_edit_status"]:first option').filter(function(){return $(this).html() == a.status}).attr("selected", "selected");
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
			var b = parseInt(a.children('input[name="paym_edit_id"]').val().replace(/\s+/g, "")),
				k = a.children('input[name="paym_edit_tranid"]').val().replace(/\s+/g, ""),
				e = a.find('input[name="paym_edit_amount"]').val().replace(/\s+/g, ""),
				f = a.find('input[name="paym_edit_time"]').val().replace(/\s+/g, ""),
				g = a.find('select[name="paym_edit_status"]').val();
			if("" != e.replace(/\s+/g, "") && "" != f.replace(/\s+/g, "")) {
				$.ajax({
					type: "POST",
					url: "../php/admin_function.php",
					data: {<?php echo $_SESSION['token']['act']; ?>: "edit_sale_info",id: b,tanid:k,amount:e, time:f, status:g},
					dataType: "json",
					success: function (d) {
						if("Updated" == d[0]){
							d[1]['action'] = '<div class="btn-group"><button class="btn btn-info edituser" value="' + d[1]['id'] + '"><i class="glyphicon glyphicon-eye-open"></i></button><button class="btn btn-danger remuser" value="' + d[1]['id'] + '"><i class="glyphicon glyphicon-remove"></i></button></div>', 
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
				}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})})
			}
			else
				noty({text: data[0],type: "Empty Field",timeout: 9E3});
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
				f=$("#mbcompanyname").val(),
				g=$("#mbsword").val();
			if(""!=a && ""!=c &&""!=d &&""!=e){
				$.ajax({
					type:"POST",
					url:"../php/admin_function.php",
					data:{<?php echo $_SESSION['token']['act']; ?>:"save_payment",gate:'moneybookers',en:a,mer_id:c,mail:d,currency:e,compname:f,sword:g},
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
					data:{<?php echo $_SESSION['token']['act']; ?>:"save_payment",gate:'paypal',en:a,mail:c,currency:d,ensand:e,encurl:f},
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