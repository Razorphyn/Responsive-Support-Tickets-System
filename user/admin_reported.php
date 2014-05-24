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
	$query = "SELECT 
					a.id,
					a.ref_id,
					CASE b.status WHEN '0' THEN 'User' WHEN '1' THEN 'Operator' WHEN '2' THEN 'Adminsitrator' ELSE 'Useless' END AS urole,
					a.reason,
					b.mail  
			FROM ".$SupportFlagTable." a
			LEFT JOIN ".$SupportUserTable." b
				ON b.id=a.usr_id";
	$STH = $DBH->prepare($query);
	$STH->execute();
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$list=array();
	$a = $STH->fetch();
	if(!empty($a)){
		do{
			$a['id']=$a['id']-14;
			$list[]=array(	'id'=>$a['id'],
							'ref_id'=>$a['ref_id'],
							'role'=>$a['urole'],
							'reason'=>htmlspecialchars($a['reason'],ENT_QUOTES,'UTF-8'),
							'mail'=>htmlspecialchars($a['mail'],ENT_QUOTES,'UTF-8'),
							'action'=>'<div class="btn-group"><button class="btn btn-info read" value="'.$a['id'].'"><i class="glyphicon glyphicon-eye-open"></i></button><button class="btn btn-danger solved" value="'.$a['id'].'"><i class="glyphicon glyphicon-remove"></i></button></div>'
						);
		}
		while ($a = $STH->fetch());
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
									<li class="dropdown active" role='button'>
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
											<li class="active" role="presentation">
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
					<h1 class='pagefun'>Reported Tickets</h1>
				</div>
				<hr>
				<?php if(!isset($error)){ ?>
					<div class='row form-group' id='deplist'>
						<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
						<table style='display:none' cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="deptable">
						<tbody>
							<?php 
								$c=count($list);
								for($i=0;$i<$c;$i++)
									echo'<tr><td>'.$list[$i]['id'].'</td><td>'.$list[$i]['ref_id'].'</td><td>'.$list[$i]['mail'].'</td><td>'.$list[$i]['role'].'</td><td>'.$list[$i]['reason'].'</td><td>'.$list[$i]['action'].'</td></tr>';
							?>
						</table>
					</div>
				<?php
					}
					else
						echo '<p>'.$error.'</p>';
				?>
				<br/><br/>
				<hr>
			</div>
		</div>
		<div id='delcat' style='display:none' title="Delete Department?">
			<p>Irreversible Operation</p>
		</div>

	<script type="text/javascript"  src="../min/?g=js_i&amp;5259487"></script>
	<script type="text/javascript"  src="../min/?g=js_d&amp;5259487"></script>
	<script>
	 $(document).ready(function() {
		
		var table=$("#deptable").dataTable({
						bDestroy:!0,
						bProcessing:!0,
						oLanguage:{sEmptyTable:"No Complaints"},
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
							{sTitle:"Reference ID",mDataProp:"ref_id",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Reference ID: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Reporter Mail",mDataProp:"mail",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Reporter Mail: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Reporter Role",mDataProp:"role",sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Reporter Role: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Comment",mDataProp:"reason",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Comment: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Toogle",mDataProp:"action",bSortable:!1,bSearchable:!1, sWidth:"150px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-xs'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}}
						]
					});
		$("#loading").remove();
		$("#deptable").show(800);
					
		setInterval(function(){
			$.ajax({
				type: 'POST',
				url: '../php/admin_function.php',
				async : 'false',
				data: {<?php echo $_SESSION['token']['act']; ?>:'timeout_update'}
			}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
		},1200000);

		$("#deptable").on("click",".read",function(){
			var b=$(this).val(),
			c=$(this).val().replace(/\./g,"_"),
			a=this.parentNode.parentNode.parentNode.parentNode;
			
			table.fnGetPosition(a,null,!0);
			a=table.fnGetData(a);
			if(0<$("#"+a.id).length)
				$("html,body").animate({scrollTop:$("#"+a.id).offset().top},1500)
			else{	
				b="<hr><div id='"+c+"' ><span>Reference <strong>"+a.ref_id+"</strong> submitted by <strong>"+a.role+"</strong></span><button class='btn btn-link btn_close_form'>Close</button><div class='row form-group'><div class='col-md-2'><label><strong>Reference ID</strong></label></div><div class='col-md-4'><p>"+ a.ref_id+"</p></div><div class='col-md-2'><label><strong>Reporter mail</strong></label></div><div class='col-md-4'><p>"+a.mail+"</p></div></div><div class='row form-group'><div class='col-md-2'><label><strong>Complaint Reason</strong></label></div></div><div class='row form-group'><div class='col-md-12 flagcont'>"+a.reason+"</div></div><div class='row form-group'><div class='col-md-2 col-md-offset-5'><a href='view.php?id="+b+"' class='btn btn-info' title='Read Tciket'>View Ticket</a></div></div></div>",
				$("#deplist").after(b),
				b="Yes"== a["public"]?1:0,
				$('select[name="edit_depa_active"]:first option[value='+("Yes"==a.active?1:0)+"]").attr("selected","selected"),
				$('select[name="edit_depa_public"]:first option[value='+b+"]").attr("selected","selected")
			}
		});

		$('#deptable').on('click','.solved',function(){
			var id=$(this).val();
			var fid=$(this).val().replace(/\./g, '_');
			if(id.replace(/\s+/g,'')!=''){
				var pos=table.fnGetPosition(this.parentNode.parentNode.parentNode.parentNode,null,true);
				$( "#delcat" ).dialog({
					resizable: true,
					height:140,
					modal: true,
					buttons: {
						"Sign as Solved": function() {
							$.ajax({
								type: 'POST',
								url: '../php/admin_function.php',
								data: {<?php echo $_SESSION['token']['act']; ?>:'rem_flag',id:id},
								dataType : 'json',
								success : function (data) {
									if(data[0]=='Deleted'){
										$('#'+fid).remove();
										table.fnDeleteRow(pos);
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
										noty({text: 'Cannot sign as solved. Error: '+data[0],type:'error',timeout:9000});
								}
							}).fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
							$(this).dialog( "close" );
						},
						'Close': function() {
							$(this).dialog( "close" );
						}
					}
					
				});
				$(window).resize(function() {
						$("#delcat").dialog("option", "position", "center");
				});
			}
			else
				noty({text: 'Empty ID',type:'error',timeout:9000});
		});
		
		$(document).on("click",".btn_close_form",function(){confirm("Do you want to proceed?")&&($(this).parent().prev().remove(),$(this).parent().remove());return!1});
	});

	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>