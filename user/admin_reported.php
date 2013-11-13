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
							'action'=>'<div class="btn-group"><button class="btn btn-info read" value="'.$a['id'].'"><i class="icon-eye-open"></i></button><button class="btn btn-danger solved" value="'.$a['id'].'"><i class="icon-remove"></i></button></div>'
						);
		}
		while ($a = $STH->fetch());
	}
}
catch(PDOException $e){  
	file_put_contents('../php/PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
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
										<li role="presentation">
											<a href="admin_departments.php" tabindex="-1" role="menuitem"><i class="icon-briefcase"></i> Deaprtments Managment</a>
										</li>
										<li role="presentation">
											<a href="admin_mail.php" tabindex="-1" role="menuitem"><i class="icon-envelope"></i> Mail Settings</a>
										</li>
										<li role="presentation">
											<a href="admin_faq.php" tabindex="-1" role="menuitem"><i class="icon-comment"></i> FAQs Managment</a>
										</li>
										<li role="presentation" class='active'>
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
					<h2 class='pagefun'>Reported Tickets</h2>
				</div>
				<hr>
				<?php if(!isset($error)){ ?>
					<div class='row-fluid' id='deplist'>
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

	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
	<script>
	 $(document).ready(function() {
		
		var table=$("#deptable").dataTable({
						sDom:"<<'span6'l><'span6'f>r>t<<'span6'i><'span6'p>>",
						sWrapper:"dataTables_wrapper form-inline",
						bDestroy:!0,
						bProcessing:!0,
						oLanguage:{sEmptyTable:"No Complaints"},
						aoColumns:[
							{sTitle:"ID",mDataProp:"id",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>ID: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Reference ID",mDataProp:"ref_id",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Reference ID: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Reporter Mail",mDataProp:"mail",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Reporter Mail: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Reporter Role",mDataProp:"role",sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Reporter Role: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Comment",mDataProp:"reason",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Comment: </strong></span><span> " + $(nTd).html() + '</span>');}},
							{sTitle:"Toogle",mDataProp:"action",bSortable:!1,bSearchable:!1, sWidth:"120px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}}
						]
					});
		$("#loading").remove();
		$("#deptable").show(800);
					

		$("#deptable").on("click",".read",function(){
			var b=$(this).val(),
			c=$(this).val().replace(/\./g,"_"),
			a=this.parentNode.parentNode.parentNode.parentNode;
			
			table.fnGetPosition(a,null,!0);
			a=table.fnGetData(a);
			if(0<$("#"+a.id).length)
				$("html,body").animate({scrollTop:$("#"+a.id).offset().top},1500)
			else{	
				b="<hr><div id='"+c+"' ><span>Reference <strong>"+a.ref_id+"</strong> submitted by <strong>"+a.role+"</strong></span><button class='btn btn-link btn_close_form'>Close</button><div class='row-fluid'><div class='span2'><label><strong>Reference ID</strong></label></div><div class='span4'><p>"+ a.ref_id+"</p></div><div class='span2'><label><strong>Reporter mail</strong></label></div><div class='span4'><p>"+a.mail+"</p></div></div><div class='row-fluid'><div class='span2'><label><strong>Complaint Reason</strong></label></div></div><div class='row-fluid'><div class='span12 flagcont'>"+a.reason+"</div></div><div class='row-fluid'><div class='span2 offset5'><a href='view.php?id="+b+"' class='btn btn-info' title='Read Tciket'>View Ticket</a></div></div></div>",
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