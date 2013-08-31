<?php 
ini_set('session.auto_start', '0');
ini_set('session.hash_function', 'sha512');
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.entropy_length', '512');
ini_set('session.gc_maxlifetime', '1800');
ini_set('session.save_path', '../php/config/session');
ini_set('session.gc_probability', '20');
ini_set('session.gc_divisor', '100');
ini_set('session.cookie_httponly', '1');

ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_name("RazorphynSupport");
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
if(is_file('../php/config/logo.txt')) $logo=file_get_contents('../php/config/logo.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/stmp.txt')) $stmp=file('../php/config/mail/stmp.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if(is_file('../php/config/mail/newuser.txt')) $nu=file('../php/config/mail/newuser.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/newreply.txt')) $nr=file('../php/config/mail/newreply.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/newticket.txt')) $nt=file('../php/config/mail/newticket.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/assigned.txt')) $as=file('../php/config/mail/assigned.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(is_file('../php/config/mail/forgotten.txt')) $fo=file('../php/config/mail/forgotten.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$siteurl=dirname(dirname(curPageURL()));
$siteurl=explode('?',$siteurl);
$siteurl=$siteurl[0];
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

$crypttable=array_flip(array('a'=>'X','b'=>'k','c'=>'Z','d'=>2,'e'=>'d','f'=>6,'g'=>'o','h'=>'R','i'=>3,'j'=>'M','k'=>'s','l'=>'j','m'=>8,'n'=>'i','o'=>'L','p'=>'W','q'=>0,'r'=>9,'s'=>'G','t'=>'C','u'=>'t','v'=>4,'w'=>7,'x'=>'U','y'=>'p','z'=>'F',0=>'q',1=>'a',2=>'H',3=>'e',4=>'N',5=>1,6=>5,7=>'B',8=>'v',9=>'y','A'=>'K','B'=>'Q','C'=>'x','D'=>'u','E'=>'f','F'=>'T','G'=>'c','H'=>'w','I'=>'D','J'=>'b','K'=>'z','L'=>'V','M'=>'Y','N'=>'A','O'=>'n','P'=>'r','Q'=>'O','R'=>'g','S'=>'E','T'=>'I','U'=>'J','V'=>'P','W'=>'m','X'=>'S','Y'=>'h','Z'=>'l'));
		
$stmp[8]=str_split($stmp[8]);
$c=count($stmp[8]);
for($i=0;$i<$c;$i++){
	if(array_key_exists($stmp[8][$i],$crypttable))
		$stmp[8][$i]=$crypttable[$stmp[8][$i]];
}
$stmp[8]=implode('',$stmp[8]);

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
					<h2 class='pagefun'>Reported Tickets</h2>
				</div>
				<hr>
				<div class='row-fluid' id='deplist'>
					<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
					<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="deptable">
					</table>
				</div>
				<br/><br/>
				<hr>
			</div>
		</div>
		<div id='delcat' style='display:none' title="Delete Department?">
			<p>Irreversible Operation</p>
		</div>
	<iframe name='hidden_frame' style='display:none;width:0;height:0'></iframe>

	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
	<script>
	 $(document).ready(function() {
		
		var table;
		var request=$.ajax({type:"POST",url:"../php/admin_function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"retrive_reported_ticket"},dataType:"json",success:function(a){if("ret"==a.response||"empty"==a.response){if("ret"==a.response){var b=a.ticket.length;for(i=0;i<b;i++)a.ticket[i].action='<div class="btn-group"><button class="btn btn-info read" value="'+a.ticket[i].encid+'"><i class="icon-eye-open"></i></button><button class="btn btn-danger solved" value="'+a.ticket[i].encid+'"><i class="icon-remove"></i></button></div>'}$("#loading").remove(); table=$("#deptable").dataTable({sDom:"<<'span6'l><'span6'f>r>t<<'span6'i><'span6'p>>",sWrapper:"dataTables_wrapper form-inline",bDestroy:!0,bProcessing:!0,aaData:a.ticket,oLanguage:{sEmptyTable:"No Complaints"},aoColumns:[{sTitle:"ID",mDataProp:"id",sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>ID: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Reference ID",mDataProp:"ref_id",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Reference ID: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Reporter Mail",mDataProp:"mail",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Reporter Mail: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Reporter Role",mDataProp:"role",sWidth:"100px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Reporter Role: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Comment",mDataProp:"reason",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Comment: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Toogle",mDataProp:"action",bSortable:!1,bSearchable:!1, sWidth:"120px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Toogle: </strong></span><span> " + $(nTd).html() + '</span>');}}]})}else noty({text:a[0],type:"error",timeout:9E3})}});request.fail(function(a,b){alert("Ajax Error: "+b)});
		
		$("#deptable").on("click",".read",function(){var b=$(this).val(),c=$(this).val().replace(/\./g,"_");var a=this.parentNode.parentNode.parentNode.parentNode;table.fnGetPosition(a,null,!0);a=table.fnGetData(a);0<$("#"+a.id).length?$("html,body").animate({scrollTop:$("#"+a.id).offset().top},1500):(b="<hr><div id='"+c+"' ><span>Reference <strong>"+a.ref_id+"</strong> submitted by <strong>"+a.role+"</strong></span><button class='btn btn-link btn_close_form'>Close</button><div class='row-fluid'><div class='span2'><label><strong>Reference ID</strong></label></div><div class='span4'><p>"+ a.ref_id+"</p></div><div class='span2'><label><strong>Reporter mail</strong></label></div><div class='span4'><p>"+a.mail+"</p></div></div><div class='row-fluid'><div class='span2'><label><strong>Complaint Reason</strong></label></div></div><div class='row-fluid'><div class='span12 flagcont'>"+a.reason+"</div></div><div class='row-fluid'><div class='span2 offset5'><a href='view.php?id="+b+"' class='btn btn-info' title='Read Tciket'>View Ticket</a></div></div></div>",$("#deplist").after(b),b="Yes"== a["public"]?1:0,$('select[name="edit_depa_active"]:first option[value='+("Yes"==a.active?1:0)+"]").attr("selected","selected"),$('select[name="edit_depa_public"]:first option[value='+b+"]").attr("selected","selected"))});
		
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
							var request= $.ajax({
								type: 'POST',
								url: '../php/admin_function.php',
								data: {<?php echo $_SESSION['token']['act']; ?>:'rem_flag',id:id},
								dataType : 'json',
								success : function (data) {
									if(data[0]=='Deleted'){
										$('#'+fid).remove();
										table.fnDeleteRow(pos);
									}
									else
										noty({text: 'Cannot sign as solved. Error: '+data[0],type:'error',timeout:9000});
								}
							});
							request.fail(function(jqXHR, textStatus){noty({text: textStatus,type:'error',timeout:9000});});
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