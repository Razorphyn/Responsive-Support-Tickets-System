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
		<title><?php if(isset($setting[0])) echo $setting[0];?> - Ticket Search</title>
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
								<li class="dropdown active" role='button'>
									<a id="drop1" class="dropdown-toggle" role='button' data-toggle="dropdown" href="#">
										<i class="icon-folder-close"></i>Tickets<b class="caret"></b>
									</a>
									<ul class="dropdown-menu" aria-labelledby="drop1" role="menu">
										<li role="presentation">
											<a href="index.php" tabindex="-1" role="menuitem"><i class="icon-th-list"></i> Tickets List</a>
										</li>
										<li role="presentation" class='active'>
											<a href="search.php" tabindex="-1" role="menuitem"><i class="icon-search"></i> Search Tickets</a>
										</li>
									</ul>
								</li>
								<li><a href="setting.php"><i class="icon-edit"></i>Settings</a></li>
							<?php if(isset($_SESSION['status']) && $_SESSION['status']==2){?>
								<li><a href="users.php"><i class="icon-user"></i>Users</a></li>
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
							<?php } if(isset($_SESSION['status'])){ ?>
								<li><a href='#' onclick='javascript:logout();return false;'><i class="icon-off"></i>Logout</a></li>
							<?php } ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class='daddy'>
				<hr>
				<div class="jumbotron" >
					<h2 class='pagefun'>Search Ticket</h2>
				</div>
				<hr>
				<?php if($_SESSION['status']==0){ ?>
					<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
					<form style='display:none' id='searchform' action=''>
						<div class='row-fluid main'>
							<div class='row-fluid'>
								<div class='span2'><label>Reference ID</label></div>
								<div class='span4'><input type="text" name='rid' id="rid" placeholder="Reference ID" /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>Ticket Title</label></div>
								<div class='span4'><input type="text" name='title' id="title" placeholder="Ticket Title" /></div>
								<div class='span2'><label>Ticket Status</label></div>
								<div class='span4'><select type="text" name='statk' id="statk"><option value=''></option><option value='0'>Closed</option><option value='1'>Open</option><option value='2'>To Assing</option></select></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>Department</label></div>
								<div class='span4'><select type="text" name='dep' id="dep" placeholder="Department" ><option value=''></option></select></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>Operator Name</label></div>
								<div class='span4'><input type="text" name='operatorn' id="operatorn" placeholder="Operator Name" /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>From date</label></div>
								<div class='span4'><input type="text" name='from' id="from" placeholder="From Date" pattern='[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' /></div>
								<div class='span2'><label>To</label></div>
								<div class='span4'><input type="text" name='to' id="to" placeholder="To date" pattern='[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2 offset5'><input id='searchtk' type='submit' class='btn btn-success' onclick='javascript:return false;' value='Search Ticket' /></div>
							</div>
						</div>
					</form>
				<?php } else if ($_SESSION['status']==1) { ?>
					<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
					<form style='display:none' id='searchform' action=''>
						<div class='row-fluid main'>
							<div class='row-fluid'>
								<div class='span2'><label>Reference ID</label></div>
								<div class='span4'><input type="text" name='rid' id="rid" placeholder="Reference ID" /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>Ticket Title</label></div>
								<div class='span4'><input type="text" name='title' id="title" placeholder="Ticket Title" /></div>
								<div class='span2'><label>Ticket Status</label></div>
								<div class='span4'><select type="text" name='statk' id="statk"><option value=''></option><option value='0'>Closed</option><option value='1'>Open</option><option value='2'>To Assing</option></select></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>Department</label></div>
								<div class='span4'><select type="text" name='dep' id="dep" placeholder="Department" ><option value=''></option></select></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>From date</label></div>
								<div class='span4'><input type="text" name='from' id="from" placeholder="From Date" pattern='[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' /></div>
								<div class='span2'><label>To</label></div>
								<div class='span4'><input type="text" name='to' id="to" placeholder="To date" pattern='[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2 offset5'><input id='searchtk' type='submit' class='btn btn-success' onclick='javascript:return false;' value='Search Ticket' /></div>
							</div>
						</div>
					</form>
				<?php } else if ($_SESSION['status']==2) { ?>
					<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>
					<form style='display:none' id='searchform' action=''>
						<div class='row-fluid main'>
							<div class='row-fluid'>
								<div class='span2'><label>ID Number</label></div>
								<div class='span4'><input type="text" name='id' id="id" placeholder="ID Number" pattern='[0-9]+' /></div>
								<div class='span2'><label>Reference ID</label></div>
								<div class='span4'><input type="text" name='rid' id="rid" placeholder="Reference ID" /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>Ticket Title</label></div>
								<div class='span4'><input type="text" name='title' id="title" placeholder="Ticket Title" /></div>
								<div class='span2'><label>Ticket Status</label></div>
								<div class='span4'><select type="text" name='statk' id="statk"><option value=''></option><option value='0'>Closed</option><option value='1'>Open</option><option value='2'>To Assing</option></select></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>User Mail</label></div>
								<div class='span4'><input type="text" name='umail' id="umail" placeholder="User Mail" /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>Department</label></div>
								<div class='span4'><select type="text" name='dep' id="dep" placeholder="Department"><option value=''></option></select></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>Operator Name</label></div>
								<div class='span4'><input type="text" name='operatorn' id="operatorn" placeholder="Operator Name" /></div>
								<div class='span2'><label>Operator ID</label></div>
								<div class='span4'><input type="text" name='operatori' id="operatori" placeholder="Operator ID" pattern='[0-9]+' /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2'><label>From Date (yyyy-mm-dd)</label></div>
								<div class='span4'><input type="date" name='from' id="from" placeholder="From Date" pattern='[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' /></div>
								<div class='span2'><label>To (yyyy-mm-dd)</label></div>
								<div class='span4'><input type="date" name='to' id="to" placeholder="To date" pattern='[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' /></div>
							</div>
							<div class='row-fluid'>
								<div class='span2 offset5'><input id='searchtk' type='submit' class='btn btn-success' onclick='javascript:return false;' value='Search Ticket' /></div>
							</div>
						</div>
					</form>
				
				<?php } ?>
				<div class='rescont'>
					<hr>
					<h3 class='sectname'>Results</h3>
					<div class='row-fluid' id='userlist'>
						<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="restable">
						</table>
					</div>
				</div>
				<hr/>
				<div class='formcontainer'>
				<form></form>
				</div>
				<br/><br/>
				<hr>
			</div>
		</div>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_i&amp;5259487' ?>"></script>
	<script type="text/javascript"  src="<?php echo $siteurl.'/min/?g=js_d&amp;5259487' ?>"></script>
	<script>
	 $(document).ready(function() {
		$('#from').datepicker({dateFormat:'yy-mm-dd'});
		$('#to').datepicker({dateFormat:'yy-mm-dd'});
		
		var dateArray = new String("<?php echo date("Y-m-d");?>").split('-');
		var dateObject = new Date(dateArray[0],dateArray[1]-1,dateArray[2]);
		$("#from").datepicker("option","maxDate",dateObject);
		$("#to").datepicker("option","maxDate",dateObject);
		
		var table;
		$.ajax({
			type:"POST",
			url:"../php/function.php",
			data:{<?php echo $_SESSION['token']['act']; ?>:"retrive_depart",sect:"new"},
			dataType:"json",
			success:function(a){
				if("ret"==a.response){
					$("#loading").remove(),
					$("#dep").append(a.information)
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
				else if("empty"==a.response){
					$("#loading").remove(),
					$("#searchform").html("<p>Sorry, no ticket could exists</p>")
				}
				else{
					$("#loading").remove(),
					$("#searchform").html("<h4>Error: "+a[0]+" <br/>Please contact the administrator.</h4>")
				}
				$("#searchform").slideToggle(1000)
			}
		}).fail(function(jqXHR, textStatus){noty({text: 'Ajax Error: '+ textStatus,type:'error',timeout:9000});});
		
		$(document).on('click','.remusr',function(){
			var enc=$(this).val();
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
			$(document).on("click", ".editusr", function () {
				var d = $(this).val(),
					b = $(this).val().replace(/\./g, "_"),
					e = $(this).parent().parent().parent().parent().parent().parent().attr("id");
				oTable = $("#" + e).dataTable();
				var a = this.parentNode.parentNode.parentNode.parentNode,
					g = oTable.fnGetPosition(a, null, !0),
					a = oTable.fnGetData(a);
				if ($("#" + b).length) $("html,body").animate({
					scrollTop: $("#" + b).offset().top
				}, 1500);
				else {
					var f = $(a.title).text(),
						d = "<hr><form action='' method='post' class='submit_changes_depa' id='" + b + "'><span>Edit " + f + "</span><button class='btn btn-link btn_close_form'>Close</button><input type='hidden' name='depa_edit_id' value='" + d + "'/><input type='hidden' name='depa_edit_pos' value='" + g + "'/><input type='hidden' id='tablename' value='" + e + "'/><div class='row-fluid'><div class='span2'><label>Name</label></div><div class='span4'><input type='text' name='edit_depa_name' placeholder='Ticket Title' value='" + f + "' required /></div></div><div class='row-fluid'><div class='span2'><label>Status</label></div><div class='span4'><select name='edit_depa_active' id='activedep'><option value='0'>Closed</option><option value='1'>Open</option><option value='2'>To Assign</option></select></div><div class='span2'><label>Priority</label></div><div class='span4'><select name='edit_depa_public'><option value='0'>Low</option><option value='1'>Medium</option><option value='2'>High</option><option value='3'>Urgent</option><option value='4'>Critical</option></select></div></div><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>";
					$(".formcontainer").children('form:first').before(d);
					if (-1 < a.status.search("Closed")) var c = 0;
					else -1 < a.status.search("Open") ? c = 1 : -1 < a.status.search("Assign") && (c = 2);
					switch (a.priority) {
						case "Low":
							prio = 0;
							break;
						case "Medium":
							prio = 1;
							break;
						case "High":
							prio = 2;
							break;
						case "Urgent":
							prio = 3;
							break;
						case "Critical":
							prio = 4
					}
					$('select[name="edit_depa_active"]:first option[value=' + c + "]").attr("selected", "selected");
					$('select[name="edit_depa_public"]:first option[value=' + prio + "]").attr("selected", "selected");
					2 == c && $('select[name="edit_depa_active"]:first option[value=1]').remove();
					$("html,body").animate({
						scrollTop: $("#" + b).offset().top
					}, 500)
				}
			});
		<?php } else { ?>
			$(document).on("click",".editusr",function(){var d=$(this).val(),c=$(this).val().replace(/\./g,"_"),f=$(this).parent().parent().parent().parent().parent().parent().attr("id");oTable=$("#"+f).dataTable();var a=this.parentNode.parentNode.parentNode.parentNode,g=oTable.fnGetPosition(a,null,!0),a=oTable.fnGetData(a);if(0<$("#"+c).length)$("html,body").animate({scrollTop:$("#"+c).offset().top},500);else{var f=$(a.title).text(),d="<hr><form action='' method='post' class='submit_changes_depa' id='"+c+"'><span>Edit "+f+"</span><button class='btn btn-link btn_close_form'>Close</button><input type='hidden' name='depa_edit_id' value='"+ d+"'/><input type='hidden' id='tablename' value='"+f+"'/><input type='hidden' name='depa_edit_pos' value='"+g+"'/><div class='row-fluid'><div class='span2'><label>Name</label></div><div class='span4'><input type='text' name='edit_depa_name' placeholder='Ticket Title' value='"+f+"' required /></div></div><div class='row-fluid'><div class='span2'><label>Status</label></div><div class='span4'><select name='edit_depa_active' id='activedep'><option value='0'>Closed</option><option value='1'>Open</option></select></div><div class='span2'><label>Priority</label></div><div class='span4'><select name='edit_depa_public'><option value='0'>Low</option><option value='1'>Medium</option><option value='2'>High</option><option value='3'>Urgent</option><option value='4'>Critical</option></select></div></div><input type='submit' class='btn btn-success submit_changes' value='Submit Changes' onclick='javascript:return false;' /></form>"; $(".formcontainer").html('<form></form>');;if(-1<a.status.search("Closed"))var e=0;else-1<a.status.search("Open")&&(e=1);switch(a.priority){case "Low":var b=0;break;case "Medium":b=1;break;case "High":b=2;break;case "Urgent":b=3;break;case "Critical":b=4}$('select[name="edit_depa_active"]:first option[value='+e+"]").attr("selected","selected");$('select[name="edit_depa_public"]:first option[value='+b+"]").attr("selected","selected");2==e&&$('select[name="edit_depa_active"]:first option[value=1]').remove(); $("html,body").animate({scrollTop:$("#"+c).offset().top},1500)}});
		<?php } ?>
		$(document).on('click','.submit_changes',function(){
			var dom=$(this).parent();
			var id= dom.children('input[name="depa_edit_id"]').val();
			
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
						tit='<a href="view.php?id='+id+'" alt="View Ticket" title="View Ticket">'+tit+'</a>';
						if(data[0]=='Saved'){
							switch(prio){case "0":prio="Low";break;case "1":prio="Medium";break;case "2":prio="High";break;case "3":prio="Urgent";break;case "4":prio="Critical"}switch(stat){case "0":stat='<span class="label label-success">Closed</span>';break;case "1":stat='<span class="label label-important">Open</span>';break;case "2":stat='<span class="label label-warning">To Assign</span>';break;case "3":stat='<span class="label label-info">Flagged</span>'};
							$('button.editusr [value="'+id+'"]').each(function(){
								var table=$(this).parent().parent().parent().parent().parent().parent().attr('id');
								table=$('#'+table).dataTable();
								var node=this.parentNode.parentNode.parentNode.parentNode;
								var pos=table.fnGetPosition(node,null,true);
								var info = table.fnGetData(node);
								info.title=tit;
								info.priority=prio;
								if(info['opname']=='Not Assigned' && stat!='<span class="label label-success">Closed</span>')
									info.status='<span class="label label-warning">To Assign</span>';
								else
									info.status=stat;
								if(stat=='<span class="label label-warning">To Assign</span>')
									info.opname='Not Assigned';
								table.fnDeleteRow(pos, function(){
									table.fnAddData(info)
								});
							});
							dom.prev().remove();
							dom.remove();
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
		
		$('#searchtk').click(function(){
			$(".formcontainer").html('<form></form>');
			$(".rescont").before("<img id='loading' src='../css/images/loader.gif' alt='Loading' title='Loading'/>");
			var enid=$("#rid").val().replace(/\s+/g,""),
				title=$("#title").val(),
				dep=$("#dep").val().replace(/\s+/g,""),
				statk=$("#statk").val().replace(/\s+/g,""),
				from=$("#from").val().replace(/\s+/g,""),
				to=$("#to").val().replace(/\s+/g,"");
			<?php if($_SESSION['status']==0){ ?>
			var operator=$('#operatorn').val();
			<?php }if($_SESSION['status']==2){ ?>
			var operator=$('#operatorn').val(), 
				id=$('#id').val().replace(/\s+/g,""),
				mail=$('#umail').val().replace(/\s+/g,""), 
				opid=$('#operatori').val().replace(/\s+/g,"");
			<?php }  if($_SESSION['status']==1){ ?>
					if(""!=enid||""!=title.replace(/\s+/g,"")||""!=dep||""!=from.replace(/\s+/g,"")||to.replace(/\s+/g,"")){
					<?php } if($_SESSION['status']==0){ ?>
					if(""!=enid||""!=title.replace(/\s+/g,"")||""!=dep||""!=from.replace(/\s+/g,"")||""!=to.replace(/\s+/g,"")||operator.replace(/\s+/g,"")){
					<?php } if($_SESSION['status']==2){ ?>
					if(""!=id||""!=mail||""!=opid||""!=enid||""!=title.replace(/\s+/g,"")||""!=dep||""!=from.replace(/\s+/g,"")||""!=to.replace(/\s+/g,"")||operator.replace(/\s+/g,"")){
					<?php } ?>
						$.ajax({
							type: 'POST',
							url: '../php/function.php',
							<?php if($_SESSION['status']==1){ ?>
							data: {<?php echo $_SESSION['token']['act']; ?>:'search_ticket',enid:enid,title:title,dep:dep,from:from,to:to,statk:statk},
							<?php } if($_SESSION['status']==0){ ?>
							data: {<?php echo $_SESSION['token']['act']; ?>:'search_ticket',enid:enid,title:title,dep:dep,from:from,to:to,op:operator,statk:statk},
							<?php } if($_SESSION['status']==2){ ?>
							data: {<?php echo $_SESSION['token']['act']; ?>:'search_ticket',id:id,mail:mail,opid:opid,enid:enid,title:title,dep:dep,from:from,to:to,op:operator,statk:statk},
							<?php } ?>
							dataType : 'json',
							success : function (data) {
								var l=data.search.length;
								if(l>0){
									for(i=0;i<l;i++)
										$.extend(data.search[i],{title:'<a href="view.php?id='+data.search[i].id+'" alt="View Ticket" title="View Ticket">'+data.search[i].title+"</a>",action:'<div class="btn-group"><button class="btn btn-warning editusr hidden-phone" value="'+data.search[i].id+'"><i class="icon-edit"></i></button><button class="btn btn-danger remusr" value="'+data.search[i].id+'"><i class="icon-remove"></i></button></div>'});
								}
								$(".loading:first").remove(); 
								if("ret"==data.response||"empty"==data.response){
									table=$("#restable").dataTable({
												sDom:"<<'span6'l><'span6'f>r>t<<'span6'i><'span6'p>>",
												sWrapper:"dataTables_wrapper form-inline",
												bDestroy:!0,
												bProcessing:!0,
												aaData:data.search,
												oLanguage:{sEmptyTable:"No Results"},
												aoColumns:[{sTitle:"Title",mDataProp:"title",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Title: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Created Date",mDataProp:"date",sWidth:"140px",sClass:"visible-desktop",bVisible:!1,fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Created Date: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Last Reply",mDataProp:"reply",sWidth:"140px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Last Reply: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Department",mDataProp:"dname",sClass:"hidden-phone",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Department: </strong></span><span> " + $(nTd).html() + '</span>');}}, {sTitle:"Operator",mDataProp:"opname",sClass:"visible-desktop",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Operator: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Priority",mDataProp:"priority",sWidth:"80px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Priority: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Status",mDataProp:"status",sWidth:"80px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Status: </strong></span><span> " + $(nTd).html() + '</span>');}},{sTitle:"Tooggle",mDataProp:"action",bSortable:!1,bSearchable:!1,sWidth:"60px",fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {$(nTd).html("<span><strong class='visible-phone'>Tooggle: </strong></span><span> " + $(nTd).html() + '</span>');}}]
											})
									$(".rescont").css("display","block")
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
									noty({text:data[0],type:"error",timeout:9E3});
								$('#restable').css('width', '')
							}
						}).fail(function(jqXHR, textStatus){noty({text: 'Ajax Error: '+ textStatus,type:'error',timeout:9000});});
					}
					else
						noty({text: 'Please,compleate at least one field',type:'error',timeout:9000});
			$('#loading').remove();
		});
		
	});
	function logout(){$.ajax({type:"POST",url:"../php/function.php",data:{<?php echo $_SESSION['token']['act']; ?>:"logout"},dataType:"json",success:function(a){"logout"==a[0]?window.location.reload():alert(a[0])}}).fail(function(a,b){noty({text:b,type:"error",timeout:9E3})})};
	</script>
  </body>
</html>