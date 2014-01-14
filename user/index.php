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
else if(!isset($_SESSION['status']) || $_SESSION['status']>2){
	 header("location: ../index.php");
	 exit();
}
include_once '../php/config/database.php';
try{
	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	if($_SESSION['status']==0){
		$query = "SELECT 
					a.id,
					IF(b.department_name IS NOT NULL, b.department_name,'Unknown') AS dname,
					IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown')) AS opname,
					a.title,
					CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END as prio,
					a.created_time,
					a.last_reply
				FROM ".$SupportTicketsTable." a
				LEFT JOIN ".$SupportDepaTable." b
					ON	b.id=a.department_id
				LEFT JOIN ".$SupportUserTable." c
					ON c.id=a.operator_id
				WHERE a.user_id=".$_SESSION['id']."  AND a.ticket_status='1'
				ORDER BY a.last_reply DESC 
				LIMIT 350";
		$STH = $DBH->prepare($query);
		$STH->execute();
		$list=array('response'=>'ret','tickets'=>array('user'=>array()));
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				$list['tickets']['user'][]=array(
													'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),
													'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),
													'title'=>'<button class="btn btn-link viewtk" value="'.$a['id'].'">'.htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8').'</button>',
													'priority'=>$a['prio'],
													'date'=>$a['created_time'],
													'reply'=>$a['last_reply'],
													'free'=>$a['last_reply'],
													'action'=>'<div class="btn-group"><button class="btn btn-warning editusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
												);
			}while ($a = $STH->fetch());
		}
	}
	else if($_SESSION['status']==1){
		$query = "SELECT 
					a.id,
					IF(b.department_name IS NOT NULL, b.department_name,'Unknown') AS dname,
					CASE WHEN a.operator_id=".$_SESSION['id']." THEN '".$_SESSION['name']."' ELSE (IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown'))) END AS opname,
					a.operator_id,
					a.title,
					CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END AS prio,
					a.created_time,
					a.last_reply
				FROM ".$SupportTicketsTable." a
				JOIN ".$SupportDepaTable." b
					ON	b.id=a.department_id
				JOIN ".$SupportUserTable." c
					ON c.id=a.operator_id
				WHERE (a.operator_id='".$_SESSION['id']."' OR a.user_id='".$_SESSION['id']."') AND a.ticket_status='1' AND a.enabled=(CASE WHEN (a.operator_id=".$_SESSION['id'].") THEN 1 ELSE a.enabled END)
				ORDER BY a.last_reply DESC 
				LIMIT 350";
		$STH = $DBH->prepare($query);
		$STH->execute();
		$list=array('response'=>'ret','tickets'=>array('user'=>array(),'op'=>array()));
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				if($a['operator_id']==$_SESSION['id'])
					$list['tickets']['op'][]=array(	
													'title'=>'<button class="btn btn-link viewtk" value="'.$a['id'].'">'.htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8').'</button>',
													'date'=>$a['created_time'],
													'reply'=>$a['last_reply'],
													'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),
													'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),
													'priority'=>$a['prio'],
													'action'=>'<div class="btn-group"><button class="btn btn-warning editusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
												);
				else
					$list['tickets']['user'][]=array(
														'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),
														'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),
														'title'=>'<button class="btn btn-link viewtk" value="'.$a['id'].'">'.htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8').'</button>',
														'priority'=>$a['prio'],
														'date'=>$a['created_time'],
														'reply'=>$a['last_reply'],
														'action'=>'<div class="btn-group"><button class="btn btn-warning editusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
													);
			}while ($a = $STH->fetch());
		}
	}
	else if($_SESSION['status']==2){
		$query = "SELECT 
						a.user_id,
						a.id,
						IF(b.department_name IS NOT NULL, b.department_name,'Unknown') AS dname,
						CASE WHEN a.operator_id=".$_SESSION['id']." THEN '".$_SESSION['name']."' ELSE ( IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown')) ) END AS opname,
						a.operator_id,
						a.title,
						CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END AS prio,
						a.created_time,
						a.last_reply
					FROM ".$SupportTicketsTable." a
					LEFT JOIN ".$SupportDepaTable." b
						ON	b.id=a.department_id
					LEFT JOIN ".$SupportUserTable." c
						ON c.id=a.operator_id
					WHERE a.ticket_status='1' AND a.enabled=(CASE WHEN (a.operator_id=".$_SESSION['id'].") THEN 1 ELSE a.enabled END)
					ORDER BY a.last_reply DESC 
					LIMIT 350";
		$STH = $DBH->prepare($query);
		$STH->execute();
		$list=array('response'=>'ret','tickets'=>array('user'=>array(),'op'=>array(),'admin'=>array()));
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				if($a['operator_id']==$_SESSION['id'])
					$list['tickets']['op'][]=array(	
														'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),
														'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),
														'title'=>'<button class="btn btn-link viewtk" value="'.$a['id'].'">'.htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8').'</button>',
														'priority'=>$a['prio'],
														'date'=>$a['created_time'],
														'reply'=>$a['last_reply'],
														'action'=>'<div class="btn-group"><button class="btn btn-warning editusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
												);
				else if($a['user_id']==$_SESSION['id'])
					$list['tickets']['user'][]=array(
														'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),
														'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),
														'title'=>'<button class="btn btn-link viewtk" value="'.$a['id'].'">'.htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8').'</button>',
														'priority'=>$a['prio'],
														'date'=>$a['created_time'],
														'reply'=>$a['last_reply'],
														'action'=>'<div class="btn-group"><button class="btn btn-warning editusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
													);
				else
					$list['tickets']['admin'][]=array(
														'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),
														'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),
														'title'=>'<button class="btn btn-link viewtk" value="'.$a['id'].'">'.htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8').'</button>',
														'priority'=>$a['prio'],
														'date'=>$a['created_time'],
														'reply'=>$a['last_reply'],
														'action'=>'<div class="btn-group"><button class="btn btn-warning editusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
													);
			}while ($a = $STH->fetch());
		}
	}
}
catch(PDOException $e){
	file_put_contents('../php/PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
	$error='We are sorry, but an error has occurred, please contact the administrator if it persist';
}
$DBH=null;
	
if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);

$siteurl=dirname(curPageURL());
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

if(!isset($_SESSION['token']['act'])) $_SESSION['token']['act']=random_token(7);
function random_token($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="robots" content="noindex,nofollow">
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title><?php if(isset($setting[0])) echo $setting[0];?> - Tickets List</title>
		<meta name="viewport" content="width=device-width">
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
		
		<!--[if lt IE 9]><script src="../js/html5shiv-printshiv.js"></script><![endif]-->
		
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_i&amp;5259487' ?>"/>
		<link rel="stylesheet" type="text/css" href="<?php echo $siteurl.'/min/?g=css_d&amp;5259487' ?>"/>

	</head>
	<body>
		<?php if(isset($_SESSION['status']) && $_SESSION['status']<3){?>
		
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
								<li class="active dropdown" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="glyphicon glyphicon-folder-close"></i> Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li class="active" role="presentation">
											<a href="index.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation">
											<a href="user/newticket.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-folder-close"></i> New Ticket</a>
										</li>
										<li role="presentation">
											<a href="search.php" tabindex="-1" role="menuitem"><i class="glyphicon glyphicon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
								<li><a href="setting.php"><i class="glyphicon glyphicon-edit"></i> Account</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li class="dropdown" role='button'>
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
					<h1 class='pagefun'>Ticket List</h1>
				</div>
				<hr>
					<?php if(!isset($error)){ ?>
						<div class='row main'>

							<ul id='tkstatnav' class="nav nav-tabs">
								<li class="active" id='tkopen' value='1' ><a href="#">Open</a></li>
								<li id='tkclosed' value='0'><a href="#">Closed</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li id='tkassi' value='2'><a href="#">To Assign</a></li>
								<?php } ?>
									<li><a href="newticket.php">New Ticket</a></li>
								<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){ ?>
									<li id='aut_ass_tk' onclick='javascript:return false;'><a href="#">Automatic Tickets Assigment</a></li>
								<?php } ?>
							</ul>

							<h3 class='sectname'>Your Tickets</h3>
							<div class='row'>
								<div class='col-md-12'>
									<img id='loading' class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
									<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="usertable">
										<tbody>
										<?php
											if(isset($list['tickets']['user'])){
												$c=count($list['tickets']['user']);
												for($i=0;$i<$c;$i++)
													echo '<tr><td>'.$list['tickets']['user'][$i]['title'].'</td><td>'.$list['tickets']['user'][$i]['date'].'</td><td>'.$list['tickets']['user'][$i]['reply'].'</td><td>'.$list['tickets']['user'][$i]['dname'].'</td><td>'.$list['tickets']['user'][$i]['opname'].'</td><td>'.$list['tickets']['user'][$i]['priority'].'</td><td>'.$list['tickets']['user'][$i]['action'].'</td></tr>';
											}
										?>
										</tbody>
									</table>
								</div>
							</div>
							<?php if($_SESSION['status']==1){ ?>
							<h3 class='sectname'>Assigned Tickets</h3>
							<div class='row'>
								<div class='col-md-12'>
									<img id='loading' class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
									<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="operatortable">
										<tbody>
										<?php
											if(isset($list['tickets']['op'])){
												$c=count($list['tickets']['op']);
												for($i=0;$i<$c;$i++)
													echo '<tr><td>'.$list['tickets']['op'][$i]['title'].'</td><td>'.$list['tickets']['op'][$i]['date'].'</td><td>'.$list['tickets']['op'][$i]['reply'].'</td><td>'.$list['tickets']['op'][$i]['dname'].'</td><td>'.$list['tickets']['op'][$i]['opname'].'</td><td>'.$list['tickets']['op'][$i]['priority'].'</td><td>'.$list['tickets']['op'][$i]['action'].'</td></tr>';
											}
										?>
										</tbody>
									</table>
								</div>
							</div>
							<?php } else if($_SESSION['status']==2){ ?>
							<h3 class='sectname'>Assigned Tickets</h3>
							<div class='row'>
								<div class='col-md-12'>
									<img id='loading' class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
									<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="operatortable">
										<tbody>
										<?php
											if(isset($list['tickets']['op'])){
												$c=count($list['tickets']['op']);
												for($i=0;$i<$c;$i++)
													echo '<tr><td>'.$list['tickets']['op'][$i]['title'].'</td><td>'.$list['tickets']['op'][$i]['date'].'</td><td>'.$list['tickets']['op'][$i]['reply'].'</td><td>'.$list['tickets']['op'][$i]['dname'].'</td><td>'.$list['tickets']['op'][$i]['opname'].'</td><td>'.$list['tickets']['op'][$i]['priority'].'</td><td>'.$list['tickets']['op'][$i]['action'].'</td></tr>';
											}
										?>
										</tbody>
									</table>
								</div>
							</div>
							<h3 class='sectname admin_ticket'>Tickets Adminsitration</h3>
							<div class='row'>
								<div class='col-md-12'>
									<img id='loading' class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
									<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="admintable">
									<tbody>
									<?php
										if(isset($list['tickets']['admin'])){
											$c=count($list['tickets']['admin']);
											for($i=0;$i<$c;$i++)
												echo '<tr><td>'.$list['tickets']['admin'][$i]['title'].'</td><td>'.$list['tickets']['admin'][$i]['date'].'</td><td>'.$list['tickets']['admin'][$i]['reply'].'</td><td>'.$list['tickets']['admin'][$i]['dname'].'</td><td>'.$list['tickets']['admin'][$i]['opname'].'</td><td>'.$list['tickets']['admin'][$i]['priority'].'</td><td>'.$list['tickets']['admin'][$i]['action'].'</td></tr>';
										}
									?>
									</tbody>
									</table>
								</div>
							</div>
							<?php } ?>
							<br/><br/>
						</div>
					<?php
						}
						else
							echo '<p>'.$error.'</p>';
					?>
				<hr>
			</div>
		</div>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
	<script>
	 $(document).ready(function() {
		//var utab,otab,atab;

					<?php if($_SESSION['status']==0){ ?>
					
						var utab=$("#usertable").dataTable({
								bDestroy:true,
								bProcessing:true,
								aaSorting:[[2,"desc"]],
								oLanguage:{sEmptyTable:"No Tickets"},
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
									{sTitle:"Title",mDataProp:"title",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Title: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Created Date",mDataProp:"date",sWidth:"140px",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Created Date: </strong></span><span> " + $(nTd).html() + '</span>');}},
									{sTitle:"Last Reply",mDataProp:"reply",sWidth:"140px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Last Reply: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Department",mDataProp:"dname",sClass:"hidden-xs",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Department: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Operator",mDataProp:"opname", fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Operator: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Priority",mDataProp:"priority",sWidth:"75px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Priority: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Toggle",mDataProp:"action",bSortable:!1,bSearchable:!1,sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toggle: </strong></span><span>" + $(nTd).html() + '</span>');}}
								]
						});
						
					<?php } else if($_SESSION['status']==1){ ?>
					
						var utab=$("#usertable").dataTable({
										bDestroy:true,
										bProcessing:true,
										aaSorting:[[2,"desc"]],
										oLanguage:{sEmptyTable:"No Tickets"},
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
											{sTitle:"Title",mDataProp:"title",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Title: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Created Date",mDataProp:"date",sWidth:"140px",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Created Date: </strong></span><span> " + $(nTd).html() + '</span>');}},
											{sTitle:"Last Reply",mDataProp:"reply",sWidth:"140px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Last Reply: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Department",mDataProp:"dname",sClass:"hidden-xs",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Department: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"User",mDataProp:"opname", fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Operator: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Priority",mDataProp:"priority",sWidth:"75px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Priority: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Toggle",mDataProp:"action",bSortable:!1,bSearchable:!1,sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toggle: </strong></span><span>" + $(nTd).html() + '</span>');}}
										]
									}),
						
							otab=$("#operatortable").dataTable({
										bDestroy:true,
										bProcessing:true,
										aaSorting:[[2,"desc"]],
										oLanguage:{sEmptyTable:"No Tickets"},
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
											{sTitle:"Title",mDataProp:"title",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Title: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Created Date",mDataProp:"date",sWidth:"140px",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Created Date: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Last Reply",mDataProp:"reply",sWidth:"140px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Last Reply: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Department",mDataProp:"dname",sClass:"hidden-xs",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Department: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Operator",mDataProp:"opname", fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Operator: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Priority",mDataProp:"priority",sWidth:"80px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Priority: </strong></span><span>" + $(nTd).html() + '</span>');}},
											{sTitle:"Toggle",mDataProp:"action",bSortable:!1,bSearchable:!1,sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toggle: </strong></span><span>" + $(nTd).html() + '</span>');}}
										]
									});
						
					<?php } else if($_SESSION['status']==2){ ?>

						var utab=$("#usertable").dataTable({
								bDestroy:true,
								bProcessing:true,
								aaSorting:[[2,"desc"]],
								oLanguage:{sEmptyTable:"No Tickets"},
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
									{sTitle:"Title",mDataProp:"title",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Title: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Created Date",mDataProp:"date",sWidth:"140px",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Created Date: </strong></span><span> " + $(nTd).html() + '</span>');}},
									{sTitle:"Last Reply",mDataProp:"reply",sWidth:"140px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Last Reply: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Department",mDataProp:"dname",sClass:"hidden-xs",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Department: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Operator",mDataProp:"opname", fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Operator: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Priority",mDataProp:"priority",sWidth:"75px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Priority: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Toggle",mDataProp:"action",bSortable:!1,bSearchable:!1,sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toggle: </strong></span><span>" + $(nTd).html() + '</span>');}}
								]
							}),
						
						otab=$("#operatortable").dataTable({
								bDestroy:true,
								bProcessing:true,
								aaSorting:[[2,"desc"]],
								oLanguage:{sEmptyTable:"No Tickets"},
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
									{sTitle:"Title",mDataProp:"title",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Title: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Created Date",mDataProp:"date",sWidth:"140px",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Created Date: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Last Reply",mDataProp:"reply",sWidth:"140px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Last Reply: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Department",mDataProp:"dname",sClass:"hidden-xs",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Department: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"User",mDataProp:"opname", fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Operator: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Priority",mDataProp:"priority",sWidth:"80px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Priority: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Toggle",mDataProp:"action",bSortable:!1,bSearchable:!1,sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toggle: </strong></span><span>" + $(nTd).html() + '</span>');}}
								]
							}),
						
						atab=$("#admintable").dataTable({
								bDestroy:true,
								bProcessing:true,
								aaSorting:[[2,"desc"]],
								oLanguage:{sEmptyTable:"No Tickets"},
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
									{sTitle:"Title",mDataProp:"title",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Title: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Created Date",mDataProp:"date",sWidth:"140px",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Created Date: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Last Reply",mDataProp:"reply",sWidth:"140px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Last Reply: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Department",mDataProp:"dname",sClass:"hidden-xs",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Department: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Operator",mDataProp:"opname", fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Operator: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Priority",mDataProp:"priority",sWidth:"80px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Priority: </strong></span><span>" + $(nTd).html() + '</span>');}},
									{sTitle:"Toggle",mDataProp:"action",bSortable:!1,bSearchable:!1,sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toggle: </strong></span><span>" + $(nTd).html() + '</span>');}}
								]
							});

						
					<?php } ?>
		$('.loading').remove();
		$('table:hidden').each(function(){
			$(this).show(400);
		});
		
		$(document).on('click','#tkopen', function(){
			$('#tkstatnav > li.active').removeClass('active');
			$(this).addClass('active');
			$('.dataTables_wrapper').each(function(){
				$(this).hide(400);
				$(this).before("<img id='loading' class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>");
			});

				$.ajax({type: 'POST',url: '../php/function.php',data: {<?php echo $_SESSION['token']['act']; ?>:'retrive_tickets',stat:1},dataType : 'json',
					success : function (a) {
						if(a.response=='ret'){
							<?php if($_SESSION['status']==0){ ?>
					
								var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
									$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
									
							<?php } else if($_SESSION['status']==1){ ?>								
							
								var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
									$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
								var l=a.tickets.op.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.op[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.op[i].id+'">'+a.tickets.op[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
									$.when(otab.fnClearTable()).then(otab.fnAddData(a.tickets.op));
							
							<?php } else if($_SESSION['status']==2){ ?>

								var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
									$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
								var l=a.tickets.op.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.op[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.op[i].id+'">'+a.tickets.op[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
									$.when(otab.fnClearTable()).then(otab.fnAddData(a.tickets.op));
								var l=a.tickets.admin.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.admin[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.admin[i].id+'">'+a.tickets.admin[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.admin[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.admin[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
									$.when(atab.fnClearTable()).then(atab.fnAddData(a.tickets.admin));
								
							<?php } ?>
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
							noty({text:a[0],type:"error",timeout:9E3});
					}
				}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
				
				$.when($('.loading').remove()).then($('.dataTables_wrapper').each(function(){$(this).show(400);}));
		});
		
		$(document).on('click','#tkclosed', function(){
			$('#tkstatnav > li.active').removeClass('active');
			$(this).addClass('active');
			$('.dataTables_wrapper').each(function(){
				$(this).hide(400);
				$(this).before("<img id='loading' class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>");
			});
				$.when(
					$.ajax({type: 'POST',url: '../php/function.php',data: {<?php echo $_SESSION['token']['act']; ?>:'retrive_tickets',stat:0},dataType : 'json',
						success : function (a) {
							if(a.response=='ret'){
								<?php if($_SESSION['status']==0){ ?>
					
									var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
									
								<?php } else if($_SESSION['status']==1){ ?>
								
									var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
									var l=a.tickets.op.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.op[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.op[i].id+'">'+a.tickets.op[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(otab.fnClearTable()).then(otab.fnAddData(a.tickets.op));
									
								<?php } else if($_SESSION['status']==2){ ?>
								
									var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
									var l=a.tickets.op.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.op[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.op[i].id+'">'+a.tickets.op[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(otab.fnClearTable()).then(otab.fnAddData(a.tickets.op));
									var l=a.tickets.admin.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.admin[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.admin[i].id+'">'+a.tickets.admin[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.admin[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.admin[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(atab.fnClearTable()).then(atab.fnAddData(a.tickets.admin));
								
								<?php } ?>
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
								noty({text:a[0],type:"error",timeout:9E3});
						}
					}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});}),

					$('.loading').remove()
				).then($('.dataTables_wrapper').each(function(){$(this).show(400);}));

		});

		<?php if($_SESSION['status']==2){ ?>
		
			$(document).on('click','#tkassi', function(){
				$('#tkstatnav > li.active').removeClass('active');
				$(this).addClass('active');
				$('.dataTables_wrapper').each(function(){
					$(this).hide(400);
					$(this).before("<img id='loading' class='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>");
				});
				
					$.ajax({type: 'POST',url: '../php/function.php',data: {<?php echo $_SESSION['token']['act']; ?>:'retrive_tickets',stat:2},dataType : 'json',
						success : function (a) {
							if(a.response=='ret'){
								<?php if($_SESSION['status']==0){ ?>
								
									var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
								
								<?php } else if($_SESSION['status']==1){ ?>
									
									var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
									var l=a.tickets.op.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.op[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.op[i].id+'">'+a.tickets.op[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(otab.fnClearTable()).then(otab.fnAddData(a.tickets.op));
								
								<?php } else if($_SESSION['status']==2){ ?>
									
									var l=a.tickets.user.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.user[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.user[i].id+'">'+a.tickets.user[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.user[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(utab.fnClearTable()).then(utab.fnAddData(a.tickets.user));
									var l=a.tickets.op.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.op[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.op[i].id+'">'+a.tickets.op[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.op[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(otab.fnClearTable()).then(otab.fnAddData(a.tickets.op));
									var l=a.tickets.admin.length;if(l>0){for(i=0;i<l;i++)$.extend(a.tickets.admin[i],{title:'<button class="btn btn-link viewtk" value="'+a.tickets.admin[i].id+'">'+a.tickets.admin[i].title+"</button>",action:'<div class="btn-group"><button class="btn btn-warning editusr" value="'+a.tickets.admin[i].id+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+a.tickets.admin[i].id+'"><i class="glyphicon glyphicon-remove"></i></button></div>'});} 
										$.when(atab.fnClearTable()).then(atab.fnAddData(a.tickets.admin));
								
								<?php } ?>
							}
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
								noty({text:a[0],type:"error",timeout:9E3});
						}
					}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
					
					$.when($('.loading').remove()).then($('.dataTables_wrapper').each(function(){$(this).show(400);}));

			});
		
			$(document).on("click", "#aut_ass_tk", function () {
				$.ajax({
					type: "POST",
					url: "../php/admin_function.php",
					data: { <?php echo $_SESSION['token']['act']; ?> : "automatic_assign_ticket"},
					dataType: "json",
					success: function (a) {
						if("Assigned" == a[0]){
							if(confirm("Do you want to refresh the page to see the changes?"))
								window.location = "<?php echo curPageURL(); ?>";
						}
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
							noty({text: a[0],type: "error",timeout: 9E3});
						
					}
				}).fail(function (a, b) {noty({text: b,type: "error",timeout: 9E3})})
			});		
		<?php } ?>
		
		$(document).on('click','.remusr',function(){
			var enc=$(this).val();
			if(!enc.match(/[0-9]{1,11}/g)){
				noty({text: 'Invalid ID',type:'error',timeout:9000});
				return;
			}
			var table=$(this).parent().parent().parent().parent().parent().parent().attr('id');
			var pos=$('#'+table).dataTable().fnGetPosition(this.parentNode.parentNode.parentNode.parentNode,null,true);
			if(confirm('Do you want to delete this tickets all the the related information?')){
				$.ajax({
					type: 'POST',
					url: '../php/function.php',
					data: {<?php echo $_SESSION['token']['act']; ?>:'delete_ticket',enc:enc},
					dataType : 'json',
					success : function (data){
						if(data[0]=='Deleted'){
							$('#'+table).dataTable().fnDeleteRow(pos);
							if($('button[value="'+enc+'"]').length >0){
								$('button[value="'+enc+'"]').each(function(){
									$('#'+table).dataTable().fnDeleteRow(pos);
								});
							}
						}
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
							noty({text: 'Ticket cannot be deleted. Error: '+data[0],type:'error',timeout:9000});
					}
				}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
			}
		});
		
		$(document).on("click",".btn_close_form",function(){confirm("Do you want to close this edit form?")&&($(this).parent().prev().remove(),$(this).parent().remove());return!1});

		<?php if($_SESSION['status']==2 || $_SESSION['status']==1){ ?>
			$(document).on("click", ".editusr", function(){
				if(!$(this).val().match(/[0-9]{1,11}/g)){
					noty({text: 'Invalid ID',type:'error',timeout:9000});
					return;
				}
				var d = $(this).val(),
					b = $(this).val().replace(/\./g, "_"),
					e = $(this).parent().parent().parent().parent().parent().parent().attr("id");
				oTable = $("#" + e).dataTable();
				var a = this.parentNode.parentNode.parentNode.parentNode,
					g = oTable.fnGetPosition(a, null, !0),
					a = oTable.fnGetData(a);
				if ($("#" + b).length) 
					$("html,body").animate({scrollTop: $("#" + b).offset().top}, 1500);
				else {
					var f = $(a.title).text(),
						d = "<hr><form action='' method='post' class='submit_changes_depa' id='"+b+"'><span>Edit " + encodeHTML(f) + "</span><button class='btn btn-link btn_close_form'>Close</button><input type='hidden' name='depa_edit_id' value='" + encodeHTML(d) + "'/><input type='hidden' name='depa_edit_pos' value='" + g + "'/><input type='hidden' id='tablename' value='" + encodeHTML(e) + "'/><div class='row form-group'><div class='col-md-2'><label>Name</label></div><div class='col-md-4'><input type='text' class='form-control' name='edit_depa_name' placeholder='Ticket Title' value='" + encodeHTML(f) + "' required /></div></div><div class='row form-group'><div class='col-md-2'><label>Status</label></div><div class='col-md-4'><select class='form-control'  name='edit_depa_active' id='activedep'><option value='0'>Closed</option><option value='1'>Open</option><option value='2'>To Assign</option></select></div><div class='col-md-2'><label>Priority</label></div><div class='col-md-4'><select class='form-control'  name='edit_depa_public'><option value='0'>Low</option><option value='1'>Medium</option><option value='2'>High</option><option value='3'>Urgent</option><option value='4'>Critical</option></select></div></div><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>";
					$("table:last").parent().parent().after(d);
					c=parseInt($('#tkstatnav > li.active').val());
					
					switch (a.priority) {case "Low":prio = 0;break;case "Medium":prio = 1;break;case "High":prio = 2;break;case "Urgent":prio = 3;break;case "Critical":prio = 4}
					
					$('select[name="edit_depa_active"]:first option[value=' + c + "]").attr("selected", "selected");
					$('select[name="edit_depa_public"]:first option[value=' + prio + "]").attr("selected", "selected");
					2 == c && $('select[name="edit_depa_active"]:first option[value=1]').remove();
					$("html,body").animate({scrollTop: $("#" + b).offset().top}, 500)
				}
			});
		<?php } else { ?>
			$(document).on("click", ".editusr", function () {
				if(!$(this).val().match(/[0-9]{1,11}/g)){
					noty({text: 'Invalid ID',type:'error',timeout:9000});
					return;
				}
				var d = $(this).val(),
					c = $(this).val().replace(/\./g, "_"),
					f = $(this).parent().parent().parent().parent().parent().parent().attr("id");
				oTable = $("#" + f).dataTable();
				var a = this.parentNode.parentNode.parentNode.parentNode,
					g = oTable.fnGetPosition(a, null, !0),
					a = oTable.fnGetData(a);
				if (0 < $("#" + c).length) 
					$("html,body").animate({scrollTop: $("#" + c).offset().top}, 500);
				else {
					var f = $(a.title).text(),
						d = "<hr><form action='' method='post' class='submit_changes_depa' id='" + c + "'><span>Edit " + encodeHTML(f) + "</span><button class='btn btn-link btn_close_form'>Close</button><input type='hidden' name='depa_edit_id' value='" + encodeHTML(d) + "'/><input type='hidden' name='depa_edit_pos' value='" + g + "'/><div class='row'><div class='col-md-2'><label>Name</label></div><div class='col-md-4'><input type='text' name='edit_depa_name' placeholder='Ticket Title' value='" + encodeHTML(f) + "' required /></div></div><div class='row'><div class='col-md-2'><label>Status</label></div><div class='col-md-4'><select class='form-control'  name='edit_depa_active' id='activedep'><option value='0'>Closed</option><option value='1'>Open</option></select></div><div class='col-md-2'><label>Priority</label></div><div class='col-md-4'><select class='form-control'  name='edit_depa_public'><option value='0'>Low</option><option value='1'>Medium</option><option value='2'>High</option><option value='3'>Urgent</option><option value='4'>Critical</option></select></div></div><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>";
					$("table:last").parent().parent().after(d);
					e=parseInt($('#tkstatnav > li.active').val());
					e=(e==0)?0:1;

					switch (a.priority) {case "Low":var b = 0;break;case "Medium":b = 1;break;case "High":b = 2;break;case "Urgent":b = 3;break;case "Critical":b = 4}
					$('select[name="edit_depa_active"]:first option[value=' + e + "]").attr("selected", "selected");
					$('select[name="edit_depa_public"]:first option[value=' + b + "]").attr("selected", "selected");
					2 == e && $('select[name="edit_depa_active"]:first option[value=1]').remove();
					$("html,body").animate({scrollTop: $("#" + c).offset().top}, 1500)
				}
			});		
		<?php } ?>

		$(document).on('click','.submit_changes',function(){
			var dom=$(this).parent();
			var id= encodeHTML(dom.children('input[name="depa_edit_id"]').val());
			
			var tit= dom.find('input[name="edit_depa_name"]').val().replace(/\s+/g,' ');
			var stat= dom.find('select[name="edit_depa_active"]').val();
			var prio= dom.find('select[name="edit_depa_public"]').val();
			if(tit.replace(/\s+/g,'')!=''){
				$.ajax({
					type: 'POST',
					url: '../php/function.php',
					data: {<?php echo $_SESSION['token']['act']; ?>:'update_ticket_index',id:id,title:tit,status:stat,priority:prio},
					dataType : 'json',
					success : function (data){
						if(data[0]=='Saved'){
							if(parseInt($('#tkstatnav > li.active').val())==data[1][3]){
								tit='<button class="btn btn-link viewtk" value="'+data[1][0]+'">'+data[1][1]+"</button>";
								switch(data[1][2]){case "0":prio="Low";break;case "1":prio="Medium";break;case "2":prio="High";break;case "3":prio="Urgent";break;case "4":prio="Critical";default:prio='Error'}
								var action='<div class="btn-group"><button class="btn btn-warning editusr" value="'+data[1][0]+'"><i class="glyphicon glyphicon-edit"></i></button><button class="btn btn-danger remusr" value="'+data[1][0]+'"><i class="glyphicon glyphicon-remove"></i></button></div>';
								$('button.editusr[value="'+data[1][0]+'"]').each(function(){
									var table=$(this).parent().parent().parent().parent().parent().parent().attr('id');
									table=$('#'+table).dataTable();
									var node=this.parentNode.parentNode.parentNode.parentNode,
										pos=table.fnGetPosition(node,null,true),
										info = table.fnGetData(node);

									info={title:tit,date:encodeHTML(info.date),reply:encodeHTML(info.reply),dname:encodeHTML(info.dname),opname:encodeHTML(info.opname),priority:encodeHTML(prio),action:action};

									if(stat==2)
										info.opname='Not Assigned';
									table.fnDeleteRow(pos, function(){table.fnAddData(info);});
								});
								dom.prev().remove();
								dom.remove();
							}
							else{
								$('button.editusr[value="'+data[1][0]+'"]').each(function(){
									var table=$(this).parent().parent().parent().parent().parent().parent().attr('id');
									table=$('#'+table).dataTable();
									var node=this.parentNode.parentNode.parentNode.parentNode,
										pos=table.fnGetPosition(node,null,true);
									table.fnDeleteRow(pos);
								});
								dom.prev().remove();
								dom.remove();
							}
								
						}
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
							noty({text: data[0],type:'error',timeout:9000});
					}
				}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
			}
			else
				noty({text: 'Form Error - Empty Title',type:'error',timeout:9000});
		});
		
		$(document).on('click',".viewtk",function(){
			var id=$(this).val();
			if(id.match(/[0-9]{1,15}/g,id))
				window.location.replace("<?php echo $siteurl.'/user/view.php?id=';?>"+id);
			else
				noty({text: 'Invalid Ticket ID',type:'error',timeout:9000});
		});
	});
	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():noty({text:a[0],type:"error",timeout:9E3})}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	
	function encodeHTML(s) {
		return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#x27;').replace(/\//g, '&#x2F;');
	}
	</script>
	<?php } else { ?>
		<script>window.location = "<?php echo dirname(dirname(curPageURL())).'/index.php'; ?>";</script>
	<?php } ?>
	
  </body>
</html>