<?php
/*
Status
0	user
1	operator
2	admin
3	activation
4	banned

Tickets
0	closed
1 	open
2	assignment
*/

ini_set('session.auto_start', '0');
ini_set('session.save_path', 'config/session');
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
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'sessionerror',0));
	}
	else
		echo '<script>top.window.location.replace("'.curPageURL().'?e=invalid");</script>';
	exit();
}
session_start(); 

include_once 'config/database.php';
if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
if(isset($setting[4])) date_default_timezone_set($setting[4]);

//Logout
if($_POST[$_SESSION['token']['act']]=='logout' && isset($_SESSION['status'])){
	session_unset();
	session_destroy();
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(0=>'logout'));
	exit();
}

//Session Check
if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800)
	$_SESSION['time']=time();
else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
	session_unset();
	session_destroy();
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'sessionerror',1));
	}
	else
		echo '<script>top.window.location.replace("'.curPageURL().'?e=expired");</script>';
	exit();
}
else if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'sessionerror',2));
	}
	else
		echo '<script>top.window.location.replace("'.curPageURL().'?e=local");</script>';
	exit();
}
else if(!isset($_POST[$_SESSION['token']['act']]) && !isset($_POST['act']) && $_POST['act']!='faq_rating' || $_POST['token']!=$_SESSION['token']['faq']){
	session_unset();
	session_destroy();
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'sessionerror',3));
	}
	else
		echo '<script>top.window.location.replace("'.curPageURL().'?e=token");</script>';
	exit();
}

//Function

if($_POST[$_SESSION['token']['act']]=='register'){
	if($_POST['pwd']==$_POST['rpwd']){
		
		if(trim(preg_replace('/\s+/','',$_POST['name']))!='' && preg_match('/^[A-Za-z0-9\/\s\'-]+$/',$_POST['name'])) 
			$_POST['name']=trim(preg_replace('/\s+/',' ',$_POST['name']));
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Name: only alphanumeric and single quote allowed'));
			exit();
		}
		$_POST['mail']= trim(preg_replace('/\s+/','',$_POST['mail']));
		if(empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Mail'));
			exit();
		}
		$_POST['pwd']= trim(preg_replace('/\s+/','',$_POST['pwd']));
		if($_POST['pwd']==''){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Password'));
			exit();
		}
		$_POST['pwd']=hash('whirlpool',crypt($_POST['pwd'],'$#%H4!df84a$%#RZ@£'));

		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "INSERT INTO ".$SupportUserTable." (`name`,`reg_key`,`mail`,`password`,`ip_address`) VALUES (?,?,?,?,?) ";
			$ip=retrive_ip();
			$reg=get_random_string(60);
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['name'],PDO::PARAM_STR);
			$STH->bindParam(2,$reg,PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['mail'],PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['pwd'],PDO::PARAM_STR);
			$STH->bindParam(5,$ip,PDO::PARAM_STR);
			$STH->execute();
				$_SESSION['id']=$DBH->lastInsertId();;
				$_SESSION['name']=$_POST['name'];
				$_SESSION['mail']=$_POST['mail'];
				$_SESSION['status']=3;
				$_SESSION['time']=time();
				$_SESSION['ip']=retrive_ip();
				$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
				$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewMem ".$_SESSION['id'];
				if(substr(php_uname(), 0, 7) == "Windows")
					pclose(popen("start /B ".$ex,"r")); 
				else
					shell_exec($ex." > /dev/null 2>/dev/null &");
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Registred'));
		}
		catch(PDOException $e){
			
			if((int)$e->getCode()==1062){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>"User with mail: ".$_POST['mail']." is already registred"));
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
				echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
			}
			$DBH=null;
			exit();
		}
	}
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Password Mismatch'));
	}
	exit();
}

else if(isset($_SESSION['status']) && $_SESSION['status']==3 && $_POST[$_SESSION['token']['act']]=='send_again'){
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$query = "UPDATE ".$SupportUserTable." SET  reg_key=? WHERE id=? ";
			$STH = $DBH->prepare($query);

			$ip=retrive_ip();
			$reg=get_random_string(60);

			$STH->bindParam(1,$reg,PDO::PARAM_STR);
			$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();

			$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
			$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewMem ".$_SESSION['id']." ";
			if(substr(php_uname(), 0, 7) == "Windows")
				pclose(popen("start /B ".$ex,"r")); 
			else
				shell_exec($ex." > /dev/null 2>/dev/null &");
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Sent'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	$DBH=null;
	exit();
}

else if(!isset($_SESSION['status']) && $_POST[$_SESSION['token']['act']]=='login'){
	$_POST['mail']= trim(preg_replace('/\s+/','',$_POST['mail']));
	if(empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Mail'));
		exit();
	}

	$_POST['pwd']=(trim(preg_replace('/\s+/','',$_POST['pwd']))!='') ? $_POST['pwd']:exit();
	$_POST['pwd']=hash('whirlpool',crypt($_POST['pwd'],'$#%H4!df84a$%#RZ@£'));

	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$query = "SELECT `id`,`name`,`mail`,`status`,`mail_alert` FROM ".$SupportUserTable." WHERE `mail`=?  AND `password`= ? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['mail'],PDO::PARAM_STR);
		$STH->bindParam(2,$_POST['pwd'],PDO::PARAM_STR);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				$_SESSION['time']=time();
				$_SESSION['id']=$a['id'];
				$_SESSION['name']=$a['name'];
				$_SESSION['mail']=htmlspecialchars($a['mail'],ENT_QUOTES,'UTF-8');
				$_SESSION['status']=$a['status'];
				$_SESSION['mail_alert']=$a['mail_alert'];
				$_SESSION['ip']=retrive_ip();
			}while ($a = $STH->fetch());
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Logged'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Wrong Credentials'));
		}
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	$DBH=null;
	exit();
}
	
else if(isset($_POST['key']) && $_POST[$_SESSION['token']['act']]=='activate_account'){
	$_POST['key']=trim(preg_replace('/\s+/','',$_POST['key']));
	if(60!=strlen($_POST['key'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Key'));
		exit();
	}
	else{
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query = "SELECT `id`,`name`,`mail`,`mail_alert` FROM ".$SupportUserTable." WHERE `reg_key`=? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['key'],PDO::PARAM_STR,60);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$path='../upload/';
				do{
					$_SESSION['id']=$a['id'];
					$_SESSION['name']=$a['name'];
					$_SESSION['mail']=htmlspecialchars($a['mail'],ENT_QUOTES,'UTF-8');
					$_SESSION['mail_alert']=$a['mail_alert'];
					$_SESSION['ip']=retrive_ip();
				}while ($a = $STH->fetch());

				$query = "UPDATE ".$SupportUserTable." SET status='0',reg_key=NULL WHERE `id`=?";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
				$STH->execute();
				$_SESSION['status']=0;
				$_SESSION['time']=time();
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Activated'));
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'No Key Match'));
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	}
	$DBH=null;
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='verify' && isset($_SESSION['status']) && $_SESSION['status']>2){
	if(!isset($_SESSION['cktime']) || ($_SESSION['cktime']-time())>300){
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT `status` FROM ".$SupportUserTable." WHERE `mail`=?  AND `id`= ? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['mail'],PDO::PARAM_STR);
			$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					if($a['status']!=$_SESSION['status']){
						$_SESSION['status']=$a['status'];
						header('Content-Type: application/json; charset=utf-8');
						echo json_encode(array(0=>"Load"));
					}
					else{
						$_SESSION['cktime']=time();
						header('Content-Type: application/json; charset=utf-8');
						echo json_encode(array(0=>'Time'));
					}
				}while ($a = $STH->fetch());
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Wrong Credentials'));
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	}
	$DBH=null;
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='forgot'){
	$_POST['mail']= trim(preg_replace('/\s+/','',$_POST['mail']));
	if(empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Mail'));
		exit();
	}
	if(trim(preg_replace('/\s+/','',$_POST['name']))!='' && preg_match('/^[A-Za-z0-9\/\s\'-]+$/',$_POST['name'])) 
		$mustang=trim(preg_replace('/\s+/',' ',$_POST['name']));
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Name: only alphanumeric and single quote allowed'));
		exit();
	}
	
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		$query = "SELECT `id` FROM ".$SupportUserTable." WHERE mail=? AND name=? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['mail'],PDO::PARAM_STR);
		$STH->bindParam(2,$_POST['name'],PDO::PARAM_STR);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				$camaro=$a['id'];
			}while ($a = $STH->fetch());
			$query = "UPDATE ".$SupportUserTable." SET tmp_password=? WHERE id=?";
			$STH = $DBH->prepare($query);
			$rands=uniqid(hash('sha256',get_random_string(60)),true);
			$STH->bindParam(1,$rands,PDO::PARAM_STR);
			$STH->bindParam(2,$camaro,PDO::PARAM_INT);
			$STH->execute();

			$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
			$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php Forgot ".$camaro." ".$rands;
			if(substr(php_uname(), 0, 7) == "Windows")
				pclose(popen("start /B ".$ex,"r")); 
			else
				shell_exec($ex." > /dev/null 2>/dev/null &");
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Reset'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Wrong Credentials'));
		}
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	$DBH=null;
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='reset_password'){
	
	$_POST['rmail']= trim(preg_replace('/\s+/','',$_POST['rmail']));
	if(empty($_POST['rmail']) || filter_var($_POST['rmail'], FILTER_VALIDATE_EMAIL)!=true){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Mail'));
		exit();
	}
	
	$_POST['key']=trim(preg_replace('/\s+/','',$_POST['key']));
	if(empty($_POST['key']) || strlen($_POST['key'])!=87){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Key'));
		exit();
	}
	
	if(trim(preg_replace('/\s+/','',$_POST['rnpass']))!='' && $_POST['rnpass']==$_POST['npass']){
		$_POST['rnpass']=hash('whirlpool',crypt($_POST['rnpass'],'$#%H4!df84a$%#RZ@£'));
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "UPDATE ".$SupportUserTable." SET password=?,tmp_password=NULL WHERE mail=? AND tmp_password=?";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['rnpass'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['rmail'],PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['key'],PDO::PARAM_STR);
			$STH->execute();
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Updated'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	}
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Password Mismatch'));
	}
	$DBH=null;
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='del_account'){//check

	$_POST['pas']=(trim(preg_replace('/\s+/','',$_POST['pas']))!='') ? $_POST['pas']:exit();
	$_POST['pas']=hash('whirlpool',crypt($_POST['pas'],'$#%H4!df84a$%#RZ@£'));

	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	
		$query = "SELECT `id` FROM ".$SupportUserTable." WHERE `mail`=?  AND `password`= ? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_SESSION['mail'],PDO::PARAM_STR);
		$STH->bindParam(2,$_POST['pas'],PDO::PARAM_STR);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			if(!is_numeric($_SESSION['id']) || $a['id']!=$_SESSION['id'])
				exit();
			$query = "DELETE FROM ".$SupportMessagesTable." WHERE user_id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();

			$query = "DELETE FROM ".$SupportTicketsTable." WHERE user_id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
				
			$query = "SELECT enc FROM ".$SupportUploadTable." WHERE `uploader`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$path='../upload/';
				do{
					if(file_exists($path.$a['enc'])){
						file_put_contents($path.$a['enc'],'');
						unlink($path.$a['enc']);
					}
				}while ($a = $STH->fetch());
			}
			$query = "DELETE FROM ".$SupportUploadTable." WHERE uploader=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
															
			$query = "UPDATE ".$SupportTicketsTable." SET operator_id=0,ticket_status= CASE WHEN '1' THEN '2' ELSE ticket_status END  WHERE operator_id=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
			
			$query = "DELETE FROM ".$SupportUserPerDepaTable." WHERE user_id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
			
			$query = "DELETE FROM ".$SupportUserTable." WHERE id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
			
			session_unset();
			session_destroy();
	
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Deleted'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Wrong Credentials'));
		}
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
	}
	exit();
}

else if($_POST['createtk']=='Create New Ticket' && isset($_POST['createtk']) && isset($_SESSION['status']) && $_SESSION['status']<3){
	$letarr=array('M','d','C','f','K','w','p','T','B','X');
	$error=array();
	if(trim(preg_replace('/\s+/','',$_POST['message']))!=''){
		$_POST['message']=trim($_POST['message']);
		require_once 'htmlpurifier/HTMLPurifier.auto.php';
		$config = HTMLPurifier_Config::createDefault();
		$purifier = new HTMLPurifier($config);
		$_POST['message'] = $purifier->purify($_POST['message']);
		$check=trim(strip_tags($_POST['message']));
		if(empty($check)){
			$error[]='Empty Message';
		}
	}
	else
		$error[]='Empty Message';
	
	if(trim(preg_replace('/\s+/','',$_POST['title']))!='')
		$_POST['title']=trim(preg_replace('/\s+/',' ',$_POST['title']));
	else
		$error[]='Empty Title';
		
	if(is_numeric($_POST['dep']))
		$_POST['dep']=(int)trim($_POST['dep']);
	else
		$error[]='Error Department';

	if(is_numeric($_POST['priority']))
		$_POST['priority']=trim($_POST['priority']);
	else
		$error[]='Error, invalid value ';

	if(!isset($error[0])){
		$_POST['wsurl']=(trim(preg_replace('/\s+/','',$_POST['wsurl'])!=''))? trim(preg_replace('/\s+/',' ',$_POST['wsurl'])):'';
		$_POST['contype']=(trim(is_numeric($_POST['contype'])))? (int)$_POST['contype']:exit();
		$_POST['ftppass']=(trim(preg_replace('/\s+/','',$_POST['ftppass'])!=''))? $_POST['ftppass']:'';
		$_POST['ftpus']=(trim(preg_replace('/\s+/','',$_POST['ftpus'])!=''))? trim($_POST['ftpus']):'';
		if($_POST['ftppass']!=''){
			$crypttable=array('a'=>'X','b'=>'k','c'=>'Z','d'=>2,'e'=>'d','f'=>6,'g'=>'o','h'=>'R','i'=>3,'j'=>'M','k'=>'s','l'=>'j','m'=>8,'n'=>'i','o'=>'L','p'=>'W','q'=>0,'r'=>9,'s'=>'G','t'=>'C','u'=>'t','v'=>4,'w'=>7,'x'=>'U','y'=>'p','z'=>'F',0=>'q',1=>'a',2=>'H',3=>'e',4=>'N',5=>1,6=>5,7=>'B',8=>'v',9=>'y','A'=>'K','B'=>'Q','C'=>'x','D'=>'u','E'=>'f','F'=>'T','G'=>'c','H'=>'w','I'=>'D','J'=>'b','K'=>'z','L'=>'V','M'=>'Y','N'=>'A','O'=>'n','P'=>'r','Q'=>'O','R'=>'g','S'=>'E','T'=>'I','U'=>'J','V'=>'P','W'=>'m','X'=>'S','Y'=>'h','Z'=>'l');
			$_POST['ftppass']=str_split($_POST['ftppass']);
			$c=count($_POST['ftppass']);
			for($i=0;$i<$c;$i++){
				if(array_key_exists($_POST['ftppass'][$i],$crypttable))
					$_POST['ftppass'][$i]=$crypttable[$crypttable[$_POST['ftppass'][$i]]];
			}
			$_POST['ftppass']=implode('',$_POST['ftppass']);
		}

		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
			//Create Ticket
			$query = "INSERT INTO ".$SupportTicketsTable."(`department_id`,`user_id`,`title`,`priority`,`website`,`contype`,`ftp_user`,`ftp_password`,`created_time`,`last_reply`) VALUES (?,?,?,?,?,?,?,?,?,?)";
			$STH = $DBH->prepare($query);
			$date=date("Y-m-d H:i:s");
			$STH->bindParam(1,$_POST['dep'],PDO::PARAM_INT);
			$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(3,$_POST['title'],PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['priority'],PDO::PARAM_INT);
			$STH->bindParam(5,$_POST['wsurl'],PDO::PARAM_STR);
			$STH->bindParam(6,$_POST['contype'],PDO::PARAM_STR);
			$STH->bindParam(7,$_POST['ftpus'],PDO::PARAM_STR);
			$STH->bindParam(8,$_POST['ftppass'],PDO::PARAM_STR);
			$STH->bindParam(9,$date,PDO::PARAM_STR);
			$STH->bindParam(10,$date,PDO::PARAM_STR);
			$STH->execute();

			echo '<script>parent.$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});</script>';
			//Assign Reference Number
			
			$tkid=$DBH->lastInsertId();
			$ip=retrive_ip();
			$randomref=get_random_string(6);
			$spadd=str_split(strrev($_SESSION['id'].''));
			$lll=count($spadd);
			for($i=0;$i<$lll;$i++) $spadd[$i]=$letarr[$spadd[$i]];
			
			$randomref=implode('',$spadd).$randomref;
			$query = "UPDATE ".$SupportTicketsTable." SET ref_id=? WHERE id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$randomref,PDO::PARAM_STR);
			$STH->bindParam(2,$tkid,PDO::PARAM_INT);
			$STH->execute();
		
			//Insert Message
			$query = "INSERT INTO ".$SupportMessagesTable."(`user_id`,`message`,`ticket_id`,`ip_address`,`created_time`) VALUES (?,?,?,?,?);";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(2,$_POST['message'],PDO::PARAM_STR);
			$STH->bindParam(3,$tkid,PDO::PARAM_INT);
			$STH->bindParam(4,$ip,PDO::PARAM_STR);
			$STH->bindParam(5,$date,PDO::PARAM_STR);
			$STH->execute();

			//File Upload
			if(isset($setting[5]) && $setting[5]==1){
				if(isset($_FILES['filename'])){
					$msid=$DBH->lastInsertId();
					$count=count($_FILES['filename']['name']);
					if($count>0){
						$maxsize=covert_size(ini_get('upload_max_filesize'));
						if(isset($setting[6]) && $setting[6]!=null && $setting[6]!='')
							$maxsize=($setting[6]<=$maxsize)? $setting[6]:$maxsize;
						echo '<script>parent.noty({text: "File Upload Started",type:"information",timeout:2000});</script>';
						if(!is_dir('../upload')) mkdir('../upload');
						$movedfiles=array();
						
						$query="INSERT INTO ".$SupportUploadTable." (`name`,`uploader`,`enc`,`tk_id`,`message_id`,`upload_date`) VALUES ";
						for($i=0;$i<$count;$i++){
							if($_FILES['filename']['error'][$i]==0){
								if($_FILES['filename']['size'][$i]<=$maxsize && $_FILES['filename']['size'][$i]!=0){
									if(!in_array($_FILES['filename']['name'][$i],$movedfiles)){
										do{
											$encname=uniqid(hash('sha256',$msid.$_FILES['filename']['name'][$i].time()),true);
											$target_path = "../upload/".$encname;
										}while(is_file($target_path));
										if(move_uploaded_file($_FILES['filename']['tmp_name'][$i], $target_path)){
											$movedfiles[]=$_FILES['filename']['name'][$i];
											$query.='(?,'.$_SESSION['id'].',"'.$encname.'",'.$tkid.',"'.$msid.'","'.$date.'"),';
											echo '<script>parent.noty({text: "'.htmlspecialchars($_FILES['filename']['name'][$i],ENT_QUOTES,'UTF-8').' has been uploaded",type:"success",timeout:2000});</script>';
										}
									}
								}
								else
									echo '<script>parent.noty({text: "The file '.htmlspecialchars($_FILES['filename']['name'][$i],ENT_QUOTES,'UTF-8').' is too big or null. Max file size: '.$maxsize.'",type:"error",timeout:9000});</script>';
							}
							else if($_FILES['filename']['error'][$i]!=4)
								echo '<script>parent.noty({text: "File Name:'.htmlspecialchars($_FILES['filename']['name'][$i],ENT_QUOTES,'UTF-8').' Error Code:'.$_FILES['filename']['error'][$i].'",type:"error",timeout:9000});</script>';
						}
						$fc=count($movedfiles);
						if($fc>0){
							$query=substr_replace($query,'',-1);
							try{
								$STH = $DBH->prepare($query);
								$c=count($movedfiles);
								for($i=0;$i<$c;$i++)
									$STH->bindParam($i+1,$movedfiles[$i],PDO::PARAM_STR);
								$STH->execute();

								$query="UPDATE ".$SupportMessagesTable." SET attachment='1' WHERE id=?";
								$STH = $DBH->prepare($query);
								$STH->bindParam(1,$msid,PDO::PARAM_INT);
								$STH->execute();
							}
							catch(PDOException $e){
								file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
								echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "An error has occurred, please contact the administrator.",type:"error",timeout:9000});</script>';
							}
						}
						echo '<script>parent.noty({text: "File Upload Finished",type:"information",timeout:2000});</script>';
					}
				}
			}

			//Assign Ticket
			$selopid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $_POST['dep'],$_SESSION['id']);
			$selopid=(is_numeric($selopid))?$selopid:null;
			if(is_numeric($selopid)){
				$query = "UPDATE ".$SupportTicketsTable." a ,".$SupportUserTable." b SET a.operator_id=?,a.ticket_status='1',b.assigned_tickets=(b.assigned_tickets+1) WHERE a.id=? AND b.id=? ";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$selopid,PDO::PARAM_INT);
				$STH->bindParam(2,$tkid,PDO::PARAM_INT);
				$STH->bindParam(3,$selopid,PDO::PARAM_INT);
				$STH->execute();

				$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
				$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php AssTick ".$tkid." ";
				if(substr(php_uname(), 0, 7) == "Windows")
					pclose(popen("start /B ".$ex,"r")); 
				else
					shell_exec($ex." > /dev/null 2>/dev/null &");

				if($_SESSION['mail_alert']=='yes'){
					$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
					$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewTick ".$tkid." ";
					if(substr(php_uname(), 0, 7) == "Windows")
						pclose(popen("start /B ".$ex,"r")); 
					else
						shell_exec($ex." > /dev/null 2>/dev/null &");
				}
			}
			else{
				$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
				$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewTick ".$tkid." ";
				if(substr(php_uname(), 0, 7) == "Windows")
					pclose(popen("start /B ".$ex,"r")); 
				else
					shell_exec($ex." > /dev/null 2>/dev/null &");
				echo "<script>parent.$('.main').nimbleLoader('hide');parent.created();</script>";
			}
			echo "<script>parent.$('.main').nimbleLoader('hide');parent.created();</script>";
		}
		catch(PDOException $e){
			if((int)$e->getCode()==1062)
				echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "You have already created a Ticket named: '.$_POST['title'].'",timeout:2000});</script>';
			else{
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
				echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "We are sorry, but an error has occurred, please contact the administrator if it persist",type:"information",timeout:2000});</script>';
			}
		}
	}
	else
		echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.implode(',',$error).'",type:"error",timeout:9000})</script>';
	exit();
}

else if(isset($_POST['post_reply']) && $_POST['post_reply']=='Post Reply' && isset($_SESSION['status']) && $_SESSION['status']<3){
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "Invalid ID",type:"error",timeout:9000});</script>';
		exit();
	}
	$error=array();
	if(trim(preg_replace('/\s+/','',$_POST['message']))!=''){
		$_POST['message']=trim($_POST['message']);
		require_once 'htmlpurifier/HTMLPurifier.auto.php';
		$config = HTMLPurifier_Config::createDefault();
		$purifier = new HTMLPurifier($config);
		$_POST['message'] = $purifier->purify($_POST['message']);
		$check=trim(strip_tags($_POST['message']));
		if(empty($check)){
			$error[]='Empty Message';
		}
	}
	else
		$error[]='Empty Message';
	
	if(!isset($error[0])){
		if(isset($_SESSION['tickets'][$_POST['id']]['id']) && $_SESSION['tickets'][$_POST['id']]['id']==$_POST['id']){
			try{
				$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
				$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				
				//User open ticket on reply
				if($_SESSION['tickets'][$_POST['id']]['status']==0 && $_SESSION['id']==$_SESSION['tickets'][$_POST['id']]['usr_id']){//check
					try{
						$query = "UPDATE ".$SupportTicketsTable." a 
									LEFT OUTER JOIN ".$SupportUserTable." b 
										ON b.id=a.operator_id
									SET a.ticket_status= CASE WHEN a.operator_id=0 THEN '2' ELSE '1' END, 
										b.assigned_tickets= CASE WHEN a.ticket_status='0' THEN (b.assigned_tickets+1) ELSE b.assigned_tickets END,
										b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END,
										a.ticket_status= CASE WHEN a.operator_id='0' THEN '2' ELSE '1' END 
									WHERE a.id=?";
						$STH = $DBH->prepare($query);
						$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
						$STH->execute();

						$query = "SELECT ticket_status FROM ".$SupportTicketsTable." WHERE id=? LIMIT 1";
						$STH = $DBH->prepare($query);
						$STH->bindParam(1,$_SESSION['tickets'][$_POST['id']]['id'],PDO::PARAM_INT);
						$STH->execute();
						
						$STH->setFetchMode(PDO::FETCH_ASSOC);
						$a = $STH->fetch();
						if(!empty($a)){
							do{
								$_SESSION['tickets'][$_POST['id']]['status']=$tkst;
							}while ($a = $STH->fetch());
							echo '<script>parent.$("#statustk").val(\'1\').change();</script>';
						}
						echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket",type:"error",timeout:9000});</script>';
					}
					catch(PDOException $e){
						file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
						echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket",type:"error",timeout:9000});</script>';
					}
				}
				$ip=retrive_ip();
				$date=date("Y-m-d H:i:s");
				//Update last reply
				$query = "UPDATE ".$SupportTicketsTable." SET last_reply=? WHERE id=?";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$date,PDO::PARAM_STR);
				$STH->bindParam(2,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();
				
				//Insert new message
				$query = "INSERT INTO ".$SupportMessagesTable."(`user_id`,`message`,`ticket_id`,`ip_address`,`created_time`) VALUES (?,?,?,?,?);";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
				$STH->bindParam(2,$_POST['message'],PDO::PARAM_STR);
				$STH->bindParam(3,$_POST['id'],PDO::PARAM_INT);
				$STH->bindParam(4,$ip,PDO::PARAM_STR);
				$STH->bindParam(5,$date,PDO::PARAM_STR);
				$STH->execute();
				
				
				if(isset($setting[5]) && $setting[5]==1){
					//Upload File
					if(isset($_FILES['filename'])){
						$msid=$DBH->lastInsertId();
						$count=count($_FILES['filename']['name']);
						if($count>0){
							$maxsize=covert_size(ini_get('upload_max_filesize'));
							if(isset($setting[6]) && $setting[6]!=null && $setting[6]!='')
								$maxsize=($setting[6]<=$maxsize)? $setting[6]:$maxsize;

							if(!is_dir('../upload')) mkdir('../upload');
							$uploadarr=array();
							$movedfiles=array();

							$query="INSERT INTO ".$SupportUploadTable." (`name`,`uploader`,`enc`,`tk_id`,`message_id`,`upload_date`) VALUES ";
							for($i=0;$i<$count;$i++){
								if($_FILES['filename']['error'][$i]==0){
									if($_FILES['filename']['size'][$i]<=$maxsize && $_FILES['filename']['size'][$i]!=0 ){
										if(count(array_keys($movedfiles,$_FILES['filename']['name'][$i]))==0){
											do{
												$encname=uniqid(hash('sha256',$msid.$_FILES['filename']['name'][$i].time()),true);
												$target_path = "../upload/".$encname;
											}while(is_file($target_path));
											if(move_uploaded_file($_FILES['filename']['tmp_name'][$i], $target_path)){
												$movedfiles[]=$_FILES['filename']['name'][$i];
												$uploadarr[]=array(0=>$_POST['id'],2=>$_FILES['filename']['name'][$i]);
												$query.='(?,'.$_SESSION['id'].',"'.$encname.'",'.$_POST['id'].',"'.$msid.'","'.$date.'"),';
												echo '<script>parent.noty({text: "'.htmlspecialchars($_FILES['filename']['name'][$i],ENT_QUOTES,'UTF-8').' has been uploaded",type:"success",timeout:2000});</script>';
											}
										}
									}
									else
										echo '<script>parent.noty({text: "The file '.htmlspecialchars($_FILES['filename']['name'][$i],ENT_QUOTES,'UTF-8').' is too big or null. Max file size: '.$maxsize.' bytes",type:"error",timeout:9000});</script>';
								}
								else if($_FILES['filename']['error'][$i]!=4)
									echo '<script>parent.noty({text: "File Name:'.htmlspecialchars($_FILES['filename']['name'][$i],ENT_QUOTES,'UTF-8').' Error Code:'.$_FILES['filename']['error'][$i].'",type:"error",timeout:9000});</script>';
							}
							$fc=count($uploadarr);
							if($fc>0){
								$query=substr_replace($query,'',-1);
								try{
									$STH = $DBH->prepare($query);
									$c=count($movedfiles);
									for($i=0;$i<$c;$i++)
										$STH->bindParam($i+1,$movedfiles[$i],PDO::PARAM_STR);
									$STH->execute();
									
									$query="UPDATE ".$SupportMessagesTable." SET attachment='1' WHERE id=?";
									$STH = $DBH->prepare($query);
									$STH->bindParam(1,$msid,PDO::PARAM_INT);
									$STH->execute();

									$query = "SELECT `id` 
												FROM ".$SupportUploadTable." 
												WHERE `message_id`=? 
												ORDER BY id ASC 
												LIMIT ?";
									$STH = $DBH->prepare($query);
									$STH->bindParam(1,$msid,PDO::PARAM_INT);
									$STH->bindParam(2,$fc,PDO::PARAM_INT);
									$STH->execute();
									$STH->setFetchMode(PDO::FETCH_ASSOC);
									$a = $STH->fetch();
									if(!empty($a)){
										$j=0;
										do{
											$uploadarr[$j][1]=$a['id'];
											$j++;
										}while ($a=$STH->fetch());
									}
								}
								catch(PDOException $e){
									file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
									echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "An error has occurred, please contact the administrator.",type:"error",timeout:9000});</script>';
								}
							}
						}
					}
				}
				//End Upload
				
				//Send Mail
				if($_SESSION['tickets'][$_POST['id']]['status']!=2){
				
					if($_SESSION['id']==$_SESSION['tickets'][$_POST['id']]['usr_id']){
						$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
						$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewRep ".$_POST['id']." 1";
					}
					else if($_SESSION['tickets'][$_POST['id']]['status']==1){
						$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
						$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewRep ".$_POST['id']." 0";
					}
					
					if(isset($ex)){
						if(substr(php_uname(), 0, 7) == "Windows")
							pclose(popen("start /B ".$ex,"r")); 
						else
							shell_exec($ex." > /dev/null 2>/dev/null &");
					}
				}
				//End Mail
				
				//Post Reply(send to javascript)
				if(isset($uploadarr[0])){
					$json=json_encode($uploadarr);
					echo "<script>parent.$('#formreply').nimbleLoader('hide');parent.post_reply(".json_encode($_POST['message']).",'".$date."','".htmlspecialchars($_SESSION['name'],ENT_QUOTES,'UTF-8')."',".$json.");</script>";
				}
				else
					echo "<script>parent.$('#formreply').nimbleLoader('hide');parent.post_reply(".json_encode($_POST['message']).",'".$date."','".htmlspecialchars($_SESSION['name'],ENT_QUOTES,'UTF-8')."',null);</script>";
				//end
			}
			catch(PDOException $e){
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
				echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "We are sorry, but an error has occured, please contact the adminsitrator",type:"error",timeout:9000});</script>';
			}
		}
		else
			echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "No Identification Founded",type:"error",timeout:9000});</script>';
	}
	else
		echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "'.implode(',',$error).'",type:"error",timeout:9000});</script>';
	exit();
}

else if( $_POST[$_SESSION['token']['act']]=='delete_ticket' && $_SESSION['status']<3){
	$_POST['enc']=trim(preg_replace('/\s+/','',$_POST['enc']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['enc'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		if($_SESSION['status']!=2){
			if($_SESSION['status']==0)
				$query = "SELECT id FROM ".$SupportTicketsTable." WHERE `id`=? AND user_id=? LIMIT 1";
			else if($_SESSION['status']==1)
				$query = "SELECT id FROM ".$SupportTicketsTable." WHERE `id`=? AND operator_id=? LIMIT 1";
				
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['enc'],PDO::PARAM_INT);
			$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
			$a = $STH->fetch();
			if(empty($a)){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Access Denied'));
				exit();
			}
		}

		$query="UPDATE ".$SupportTicketsTable." a
				INNER JOIN ".$SupportUserTable." b
					ON b.id=a.operator_id
				SET b.assigned_tickets= CASE  WHEN b.assigned_tickets!='0' THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END  
				WHERE a.id=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['enc'],PDO::PARAM_INT);
		$STH->execute();

		$query = "DELETE FROM ".$SupportMessagesTable." WHERE `ticket_id`=? ";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['enc'],PDO::PARAM_INT);
		$STH->execute();

		$query = "SELECT enc FROM ".$SupportUploadTable." WHERE `tk_id`=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['enc'],PDO::PARAM_INT);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			$path='../upload/';
			do{
				if(file_exists($path.$a['enc'])){
					file_put_contents($path.$a['enc'],'');
					unlink($path.$a['enc']);
				}
			}while ($a = $STH->fetch());
			$query = "DELETE FROM ".$SupportUploadTable." WHERE `tk_id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['enc'],PDO::PARAM_INT);
			$STH->execute();
		}
		
		$query = "DELETE FROM ".$SupportFlagTable." WHERE `tk_id`=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['enc'],PDO::PARAM_INT);
		$STH->execute();
		
		$query = "DELETE FROM ".$SupportTicketsTable." WHERE `id`=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['enc'],PDO::PARAM_INT);
		$STH->execute();
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Deleted'));
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		exit();
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='retrive_depart' && isset($_SESSION['status']) && $_SESSION['status']<3){
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		if($_POST['sect']=='new' && $_SESSION['status']==0)
			$query = "SELECT * FROM ".$SupportDepaTable." WHERE active='1' AND public_view='1'";
		else if($_POST['sect']=='new' && $_SESSION['status']!=0)
			$query = "SELECT * FROM ".$SupportDepaTable." WHERE active='1' ";
		else if($_POST['sect']=='admin' && $_SESSION['status']==2)
			$query = "SELECT id,department_name,
			CASE active WHEN '1' THEN 'Yes' ELSE 'No' END AS active, CASE public_view WHEN '1' THEN 'Yes' ELSE 'No' END AS public FROM ".$SupportDepaTable;
		else
			exit();
			
		$STH = $DBH->prepare($query);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			$dn=array('response'=>'ret','information'=>array());
			if($_POST['sect']=='new'){
				do{
					$dn['information'][]="<option value='".$a['id']."'>".$a['department_name']."</option>";
				}while ($a = $STH->fetch());
			}
			else if($_POST['sect']=='admin' && $_SESSION['status']==2){
				do{
					$dn['information'][]=array('id'=>$a['id'],'name'=>htmlspecialchars($a['department_name'],ENT_QUOTES,'UTF-8'),'active'=>$a['active'],'public'=>$a['public']);
				}while ($a = $STH->fetch());
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($dn);
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('response'=>array('empty'),'information'=>array()));
		}
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='retrive_tickets' && isset($_SESSION['status'])  && $_SESSION['status']<3){
	if(!is_numeric($_POST['stat']) || $_POST['stat']>2 || $_POST['stat']<0){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Wrong Status Code'));
		exit();
	}

	try{
		
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		if($_SESSION['status']==0){
			$_POST['stat']=($_POST['stat']==0)? 0:1;
			$query = "SELECT 
						a.id,
						IF(b.department_name IS NOT NULL, b.department_name,'Unknown'),
						IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown')),
						a.title,
						CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END,
						a.created_time,
						a.last_reply
					FROM ".$SupportTicketsTable." a
					LEFT JOIN ".$SupportDepaTable." b
						ON	b.id=a.department_id
					LEFT JOIN ".$SupportUserTable." c
						ON c.id=a.operator_id
					WHERE a.user_id=".$_SESSION['id']."  AND a.ticket_status=?
					ORDER BY a.last_reply DESC 
					LIMIT 350";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['stat'],PDO::PARAM_STR);
			$STH->execute();
			$list=array('response'=>'ret','tickets'=>array('user'=>array()));
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					$list['tickets']['user'][]=array('id'=>$a['id'],'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
				}while ($a = $STH->fetch());
			}
		}
		else if($_SESSION['status']==1){
			$_POST['stat']=($_POST['stat']==0)? 0:1;
			$query = "SELECT 
						a.id,
						IF(b.department_name IS NOT NULL, b.department_name,'Unknown') as dname,
						CASE WHEN a.operator_id=".$_SESSION['id']." THEN '".$_SESSION['name']."' ELSE (IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown'))) END as opname,
						a.operator_id,
						a.title,
						CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END as prio,
						a.created_time,
						a.last_reply
					FROM ".$SupportTicketsTable." a
					JOIN ".$SupportDepaTable." b
						ON	b.id=a.department_id
					JOIN ".$SupportUserTable." c
						ON c.id=a.operator_id
					WHERE (a.operator_id='".$_SESSION['id']."' OR a.user_id='".$_SESSION['id']."') AND a.ticket_status=?
					ORDER BY a.last_reply DESC
					LIMIT 350" ;
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['stat'],PDO::PARAM_STR);
			$STH->execute();
			$list=array('response'=>'ret','tickets'=>array('user'=>array(),'op'=>array()));
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					if($a['operator_id']==$_SESSION['id'])
						$list['tickets']['op'][]=array(	'id'=>$a['id'],
														'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),
														'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),
														'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),
														'priority'=>$a['prio'],
														'date'=>$a['created_time'],
														'reply'=>$a['last_reply']
													);
					else
						$list['tickets']['user'][]=array(	'id'=>$a['id'],
															'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),
															'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),
															'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),
															'priority'=>$a['prio'],
															'date'=>$a['created_time'],
															'reply'=>$a['last_reply']
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
						WHERE a.ticket_status='?'
						ORDER BY a.last_reply DESC 
						LIMIT 350";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['stat'],PDO::PARAM_STR);
			$STH->execute();
			$list=array('response'=>'ret','tickets'=>array('user'=>array(),'op'=>array(),'admin'=>array()));
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					if($a['operator_id']==$_SESSION['id'])
						$list['tickets']['op'][]=array('id'=>$a['id'],'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
					else if($a['user_id']==$_SESSION['id'])
						$list['tickets']['user'][]=array('id'=>$a['id'],'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
					else
						$list['tickets']['admin'][]=array('id'=>$a['id'],'dname'=>htmlspecialchars($a['dname'],ENT_QUOTES,'UTF-8'),'opname'=>htmlspecialchars($a['opname'],ENT_QUOTES,'UTF-8'),'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
				}while ($a = $STH->fetch());
			}
		}
		if(isset($list)){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($list);
		}
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	$DBH=null;
	exit();
}

else if($_POST['action']=='scrollpagination' && isset($_POST['action']) && isset($_SESSION['status']) && $_SESSION['status']<3){
	$_POST['offset'] = is_numeric($_POST['offset']) ? $_POST['offset'] : exit();
	$_POST['number'] = is_numeric($_POST['number']) ? $_POST['number'] : exit();
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	
	if(isset($_SESSION[$encid]['id']) && $encid!='' ){
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$query = "SELECT 
							a.id,
							IF(b.name IS NOT NULL,b.name,'Unknown') as name,
							a.message,
							a.created_time,
							a.attachment 
						FROM ".$SupportMessagesTable." a
						LEFT JOIN ".$SupportUserTable." b
							ON b.id=a.user_id
						WHERE a.ticket_id=? ORDER BY a.created_time DESC LIMIT ?,?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->bindParam(2,$_POST['offset'],PDO::PARAM_INT);
			$STH->bindParam(3,$_POST['number'],PDO::PARAM_INT);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$ret=array('ret'=>'Entry','messages'=>array());
				$messageid=array();
				$count=0;
				do{
					$ret['messages'][$a['id']]=array(htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8'),$a['message'],$a['created_time']);
					if($a['attachment']==1)
						$messageid[]=$a['id'];
					$count++;
				}while ($a = $STH->fetch());
				if(count($messageid)>0){
					$messageid=implode(',',$messageid);
					try{
						$query = "SELECT `id`,`uploader`,`name`,`message_id` FROM ".$SupportUploadTable." WHERE message_id IN (".$messageid.")";
						$STH = $DBH->prepare($query);
						$STH->execute();
						$STH->setFetchMode(PDO::FETCH_ASSOC);
						$a = $STH->fetch();
						if(!empty($a)){
							do{
								if($_SESSION['id']==$a['uploader'])
									$ret['messages'][$a['message_id']][]='<form method="POST" action="../php/function.php" target="hidden_upload" enctype="multipart/form-data"><input type="hidden" name="ticket_id" value="'.$encid.'"/><input type="hidden" name="file_download" value="'.$a['id'].'"/><input type="submit" class="btn btn-link download" value="'.htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8').'"> &nbsp;&nbsp; <i class="icon-remove-sign remfile" title="Delete File" alt="Delete File"></i></form>';
								else
									$ret['messages'][$a['message_id']][]='<form method="POST" action="../php/function.php" target="hidden_upload" enctype="multipart/form-data"><input type="hidden" name="ticket_id" value="'.$encid.'"/><input type="hidden" name="file_download" value="'.$a['id'].'"/><input type="submit" class="btn btn-link download" value="'.htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8').'"></form>';
							}while ($a = $STH->fetch());
						}
					}
					catch(PDOException $e){
						file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
					}
					
				}
				$ret['messages']=array_values($ret['messages']);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode($ret);
			}
			else
				echo json_encode(array('ret'=>'End'));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
		$DBH=null;
		exit();
	}
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('Error'=>'FATAL ERROR'));
	}
	unset($_POST['action']);
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='save_setting' && isset($_SESSION['status']) && $_SESSION['status']<3){
	if(trim(preg_replace('/\s+/','',$_POST['name']))!='' && preg_match('/^[A-Za-z0-9\/\s\'-]+$/',$_POST['name'])) 
		$_POST['name']=trim(preg_replace('/\s+/',' ',$_POST['name']));
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Name: only alphanumeric and single quote allowed'));
		exit();
	}
	$_POST['almail']=($_POST['almail']!='no') ? 'yes':'no';
	$_POST['mail']=trim(preg_replace('/\s+/','',$_POST['mail']));
	if(empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Mail: empty mail or not allowed characters'));
		exit();
	}
		
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$pass=trim(preg_replace('/\s+/','',$_POST['nldpwd']));
		if(isset($_POST['oldpwd']) && isset($_POST['nldpwd']) && isset($_POST['rpwd']) && !empty($pass) && $_POST['nldpwd']==$_POST['rpwd']){
			$_POST['oldpwd']=hash("whirlpool",crypt($_POST['oldpwd'],'$#%H4!df84a$%#RZ@£'));
			$query = "SELECT `id` FROM ".$SupportUserTable." WHERE `password`= ? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['oldpwd'],PDO::PARAM_STR);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					$camaroret=$a['id'];
				}while ($a = $STH->fetch());
				
				if($camaroret==$_SESSION['id']){
					$_POST['nldpwd']=hash("whirlpool",crypt($_POST['nldpwd'],'$#%H4!df84a$%#RZ@£'));
					$query = "UPDATE ".$SupportUserTable." SET `name`=?, `mail`=?, `mail_alert`=?, `password`=? WHERE id=".$_SESSION['id'];
					$passupd=true;
					$check=true;
				}
				else
					$wrongpass=true;
			}
			else
				$wrongpass=true;
		}
		else{
			$query = "UPDATE ".$SupportUserTable." SET `name`=?, `mail`=?, `mail_alert`=? WHERE id=".$_SESSION['id'];
			$check=true;
		}
		if(isset($check) && $check==true){
			$STH = $DBH->prepare($query);
			if(isset($passupd) && $passupd==true){
				unset($passupd);
				$STH->bindParam(1,$_POST['name'],PDO::PARAM_STR);
				$STH->bindParam(2,$_POST['mail'],PDO::PARAM_STR);
				$STH->bindParam(3,$_POST['almail'],PDO::PARAM_STR);
				$STH->bindParam(4,$_POST['nldpwd'],PDO::PARAM_STR);
			}
			else{
				$STH->bindParam(1,$_POST['name'],PDO::PARAM_STR);
				$STH->bindParam(2,$_POST['mail'],PDO::PARAM_STR);
				$STH->bindParam(3,$_POST['almail'],PDO::PARAM_STR);
			}
			$STH->execute();
			$_SESSION['name']=$_POST['name'];
			$_SESSION['mail_alert']=$_POST['almail'];
			$_SESSION['mail']=$_POST['mail'];
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Saved'));
		}
		else if(isset($wrongpass) && $wrongpass==true){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Wrong Old Password'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'New Passwords Mismatch'));
		}
	}
	catch(PDOException $e){
		if((int)$e->getCode()==1062){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>"User with mail: ".$_POST['mail']." is already registred"));
		}
		else{
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	}
	$DBH=null;
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='update_status' && isset($_SESSION['status']) && $_SESSION['status']<3){
	if($_SESSION['status']==0)
		$_POST['status']=($_POST['status']==1 || $_POST['status']==2)? 1:0;
	else
		$_POST['status']=($_POST['status']==0 || $_POST['status']==1 || $_POST['status']==2)? $_POST['status']:0;
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	if(!isset($_SESSION['tickets'][$_POST['id']]['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Access Denied'));
		exit();
	}
	if($_POST['status']==0){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT OUTER JOIN ".$SupportUserTable." b
					ON b.id=a.operator_id
					SET 
						b.solved_tickets= CASE WHEN a.ticket_status!='0' THEN (b.solved_tickets+1) ELSE b.solved_tickets END , 
						b.assigned_tickets= CASE  WHEN a.ticket_status!='0' AND b.assigned_tickets>=1 THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END
					WHERE a.id=? ";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET
						a.ticket_status='0'
					WHERE a.id=? ";
	}
	else if($_POST['status']==2){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT OUTER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET 
						b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END , 
						b.assigned_tickets= CASE  WHEN a.ticket_status='1' AND b.assigned_tickets>=1 THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END
					WHERE a.id=?";
		$lquery="UPDATE ".$SupportTicketsTable." a
					SET 
						a.operator_id=0,
						a.ticket_status='2'
					WHERE a.id=?";
	}
	else if($_POST['status']==1){
		$fquery = "UPDATE ".$SupportTicketsTable." a 
						LEFT OUTER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET 
						b.assigned_tickets= CASE WHEN a.ticket_status='0' THEN (b.assigned_tickets+1) ELSE b.assigned_tickets END,
						b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END
					WHERE a.id=?";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET
						a.ticket_status= CASE WHEN a.operator_id='0' THEN '2' ELSE '1' END
					WHERE a.id=?";
	}
	else
		exit();

	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$STH = $DBH->prepare($fquery);
		$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
		$STH->execute();

		$STH = $DBH->prepare($lquery);
		$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
		$STH->execute();
		
		$query = "SELECT ticket_status FROM ".$SupportTicketsTable." WHERE id=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				$_SESSION['tickets'][$_POST['id']]['status']=$a['ticket_status'];
			}while ($a = $STH->fetch());
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Saved'));
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='move_opera_ticket' && isset($_SESSION['status']) && $_SESSION['status']==1){//check
	$_POST['dpid']=(is_numeric($_POST['dpid'])) ? $_POST['dpid']:exit();
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$query = "SELECT department_id,operator_id FROM ".$SupportTicketsTable." WHERE `id`=? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if($a['department_id']==$_POST['dpid']){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'The ticket is already assigned to this department'));
			exit();
		}
		$oldop=$a['operator_id'];
		$opid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $_POST['dpid'],$_SESSION['tickets'][$_POST['id']]['usr_id']);
		if(!is_numeric($opid))
			$opid=0;
		if($oldop!=$opid){
			$query="UPDATE ".$SupportTicketsTable." a
							SET
								a.department_id=?,
								a.ticket_status= CASE WHEN ?=0 AND a.ticket_status!=0 THEN 2 WHEN ?!=0 AND a.ticket_status='2' THEN '1' ELSE a.ticket_status END,
								a.operator_id=?
							WHERE a.id=? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['dpid'],PDO::PARAM_INT);
			$STH->bindParam(2,$opid,PDO::PARAM_INT);
			$STH->bindParam(3,$opid,PDO::PARAM_INT);
			$STH->bindParam(4,$opid,PDO::PARAM_INT);
			$STH->bindParam(5,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();

			$query="UPDATE ".$SupportUserTable." b
							SET
								b.assigned_tickets=b.assigned_tickets-1
							WHERE b.id=? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$oldop,PDO::PARAM_INT);
			$STH->execute();

			if($opid!=0){
				$query="UPDATE ".$SupportUserTable." c
								SET
									c.assigned_tickets=c.assigned_tickets+1
								WHERE c.id=? LIMIT 1";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$opid,PDO::PARAM_INT);
				$STH->execute();
			}
		}
		else{
			$query="UPDATE ".$SupportTicketsTable." a
							SET
								a.department_id=?
							WHERE a.id=? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['dpid'],PDO::PARAM_INT);
			$STH->bindParam(5,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
		}
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Moved'));
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='update_ticket_title' && isset($_SESSION['status']) && $_SESSION['status']<3){
	$_POST['tit']=(trim(preg_replace('/\s+/','',$_POST['tit']))!='')? trim(preg_replace('/\s+/',' ',$_POST['tit'])):exit();
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	if(!isset($_SESSION['tickets'][$_POST['id']]['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Access Denied'));
		exit();
	}
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$query="UPDATE ".$SupportTicketsTable." SET title=? WHERE id=? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['tit'],PDO::PARAM_STR);
		$STH->bindParam(2,$_POST['id'],PDO::PARAM_STR);
		$STH->execute();
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Updated',1=>htmlspecialchars($_POST['tit'],ENT_QUOTES,'UTF-8')));
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='update_ticket_connection' && isset($_SESSION['status']) && $_SESSION['status']<3){
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	if(!isset($_SESSION['tickets'][$_POST['id']]['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Access Denied'));
		exit();
	}
	$_POST['contype']=(is_numeric($_POST['contype']))? $_POST['contype']:exit();
	$_POST['website']=(trim(preg_replace('/\s+/','',$_POST['website']))!='')?trim(preg_replace('/\s+/','',$_POST['website'])):'';
	$_POST['user']=(trim(preg_replace('/\s+/','',$_POST['user'])!=''))? trim($_POST['user']):'';
	$_POST['pass']=(trim(preg_replace('/\s+/','',$_POST['pass'])!=''))? $_POST['pass']:'';
	if($_POST['pass']!='' && $_POST['pass']!=null){
		$crypttable=array('a'=>'X','b'=>'k','c'=>'Z','d'=>2,'e'=>'d','f'=>6,'g'=>'o','h'=>'R','i'=>3,'j'=>'M','k'=>'s','l'=>'j','m'=>8,'n'=>'i','o'=>'L','p'=>'W','q'=>0,'r'=>9,'s'=>'G','t'=>'C','u'=>'t','v'=>4,'w'=>7,'x'=>'U','y'=>'p','z'=>'F',0=>'q',1=>'a',2=>'H',3=>'e',4=>'N',5=>1,6=>5,7=>'B',8=>'v',9=>'y','A'=>'K','B'=>'Q','C'=>'x','D'=>'u','E'=>'f','F'=>'T','G'=>'c','H'=>'w','I'=>'D','J'=>'b','K'=>'z','L'=>'V','M'=>'Y','N'=>'A','O'=>'n','P'=>'r','Q'=>'O','R'=>'g','S'=>'E','T'=>'I','U'=>'J','V'=>'P','W'=>'m','X'=>'S','Y'=>'h','Z'=>'l');
								
		$_POST['pass']=str_split($_POST['pass']);
		$c=count($_POST['pass']);
		for($i=0;$i<$c;$i++){
			if(array_key_exists($_POST['pass'][$i],$crypttable))
				$_POST['pass'][$i]=$crypttable[$crypttable[$_POST['pass'][$i]]];
		}
		$_POST['pass']=implode('',$_POST['pass']);
	}
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$query="UPDATE ".$SupportTicketsTable." SET website=?,contype=?,ftp_user=?,ftp_password=? WHERE id=? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['website'],PDO::PARAM_STR);
		$STH->bindParam(2,$_POST['contype'],PDO::PARAM_STR);
		$STH->bindParam(3,$_POST['user'],PDO::PARAM_STR);
		$STH->bindParam(4,$_POST['pass'],PDO::PARAM_STR);
		$STH->bindParam(5,$_POST['id'],PDO::PARAM_INT);
		$STH->execute();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Updated'));
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if(isset($_POST['file_download']) && isset($_SESSION['status']) && $_SESSION['status']<3){
	$_POST['ticket_id']=trim(preg_replace('/\s+/','',$_POST['ticket_id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['ticket_id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	if(!isset($_SESSION['tickets'][$_POST['ticket_id']]['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Access Denied'));
		exit();
	}
	$_POST['file_download']=(is_numeric($_POST['file_download'])) ? (int)$_POST['file_download']:exit();
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$query="SELECT name,enc FROM ".$SupportUploadTable." WHERE ticket_id=? AND id=? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_SESSION['tickets'][$_POST['ticket_id']]['id'],PDO::PARAM_INT);
		$STH->bindParam(2,$_POST['file_download'],PDO::PARAM_INT);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a=$STH->fetch();
		if(!empty($a)){
			do{
				$enc='../upload/'.$a['enc'];
					$mime=retrive_mime($enc,$a['name']);
					if($mime!='Error'){
						header("Content-Type: ".$mime);
						header("Cache-Control: no-store, no-cache");
						header("Content-Description: ".$a['name']);
						header("Content-Disposition: attachment;filename=".$a['name']);
						header("Content-Transfer-Encoding: binary");
						readfile($enc);
					}
					else
						echo '<script>parent.noty({text: "Can\'t retrive Content-Type",type:"error",timeout:9000});</script>';
			}while($a=$STH->fetch());
		}
		else{
			echo '<script>parent.noty({text: "No matches",type:"error",timeout:9000});</script>';
		}
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		echo '<script>parent.noty({text: "We are sorry, but an error has occurred, please contact the administrator if it persist",type:"error",timeout:9000});</script>';
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='update_ticket_index' && isset($_SESSION['status']) && $_SESSION['status']<3){
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	
	if($_SESSION['status']!=2){
		if($_SESSION['status']==0)
			$query = "SELECT id FROM ".$SupportTicketsTable." WHERE `id`=? AND user_id=? LIMIT 1";
		else if($_SESSION['status']==1)
			$query = "SELECT id FROM ".$SupportTicketsTable." WHERE `id`=? AND operator_id=? LIMIT 1";

		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['enc'],PDO::PARAM_INT);
		$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
		$STH->execute();
		$a = $STH->fetch();
		if(empty($a)){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Access Denied'));
			exit();
		}
	}
	$_POST['title']=(trim(preg_replace('/\s+/','',$_POST['title'])!=''))? trim(preg_replace('/\s+/',' ',$_POST['title'])):exit();
	$_POST['priority'] = (is_numeric($_POST['priority']))? (int)$_POST['priority']:0;

	if($_SESSION['status']==0)
		$_POST['status']=($_POST['status']==1 || $_POST['status']==2)? 1:0;
	else
		$_POST['status']=($_POST['status']==0 || $_POST['status']==1 || $_POST['status']==2)? $_POST['status']:0;
	
	if($_POST['status']==0){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT OUTER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET 
						b.solved_tickets= CASE WHEN a.ticket_status='1' THEN (b.solved_tickets+1) ELSE b.solved_tickets END , 
						b.assigned_tickets= CASE  WHEN ( a.ticket_status!='0' AND b.assigned_tickets>=1) THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END
					WHERE a.id=?";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET 
						a.title=? , 
						a.priority=?,
						a.ticket_status=?
					WHERE a.id=?";
	}
	else if($_POST['status']==1){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT OUTER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET
						b.assigned_tickets= CASE WHEN a.ticket_status='0' THEN (b.assigned_tickets+1) ELSE b.assigned_tickets END,
						b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END
					WHERE a.id=?";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET
						a.title=? , 
						a.priority=?, 
						a.ticket_status= CASE WHEN a.operator_id=0 THEN '2' ELSE ? END,
						a.ticket_status= CASE WHEN a.operator_id='0' THEN '2' ELSE '1' END 
					WHERE a.id=?";
	}
	else if($_POST['status']==2){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET
						b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END , 
						b.assigned_tickets= CASE  WHEN a.ticket_status='1' AND b.assigned_tickets>=1 THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END
					WHERE a.id=?";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET
						a.title=? , 
						a.priority=?,
						a.operator_id=0,
						a.ticket_status=?
					WHERE a.id=?";
	}
	else
		exit();
		
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$STH = $DBH->prepare($fquery);
		$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
		$STH->execute();

		$STH = $DBH->prepare($lquery);
		$STH->bindParam(1,$_POST['title'],PDO::PARAM_STR);
		$STH->bindParam(2,$_POST['priority'],PDO::PARAM_STR);
		$STH->bindParam(3,$_POST['status'],PDO::PARAM_STR);
		$STH->bindParam(4,$_POST['id'],PDO::PARAM_INT);
		$STH->execute();
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Saved',1=>array($_POST['id'],htmlspecialchars($_POST['title'],ENT_QUOTES,'UTF-8'),$_POST['priority'],$_POST['status'])));
	}
	catch(Exception $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='rating' && isset($_SESSION['status']) && $_SESSION['status']<3){//deep check
	$_POST['rate']=(is_numeric($_POST['rate']))? $_POST['rate']:0;
	$_POST['tkid']=trim(preg_replace('/\s+/','',$_POST['tkid']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['tkid'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	$_POST['comment']=trim(preg_replace('/\s+/',' ',$_POST['comment']));
	if(isset($_SESSION['tickets'][$_POST['tkid']]['status']) && $_SESSION['tickets'][$_POST['tkid']]['status']==0){
		try{
			$query = "UPDATE ".$SupportUserTable." a
						INNER JOIN ".$SupportTicketsTable." b 
							ON b.operator_id=a.id
						SET a.rating=ROUND(((a.number_rating * a.rating - (CASE WHEN b.operator_rate>0 THEN b.operator_rate ELSE 0 END) + ?)/(CASE WHEN a.number_rating=0 THEN 1 WHEN b.operator_rate>0 THEN  a.number_rating ELSE a.number_rating+1 END)),2),
							a.number_rating=CASE WHEN b.operator_rate>0 THEN a.number_rating ELSE a.number_rating+1 END,
							b.operator_rate=? 
						WHERE  b.id=?";
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$STH = $DBH->prepare($query);
			$STH->bindValue(1,strval($_POST['rate']));
			$STH->bindValue(2,strval($_POST['rate']));
			$STH->bindParam(3,$_POST['tkid'],PDO::PARAM_INT);
			$STH->execute();
			
			$query = "INSERT INTO ".$SupportRateTable." (`ref_id`,`tk_id`,`usr_id`,`rate`,`note`) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE `rate`=?,`note`=?";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['tickets'][$_POST['tkid']]['ref_id'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['tkid'],PDO::PARAM_INT);
			$STH->bindParam(3,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(4,$_POST['rate'],PDO::PARAM_INT);
			$STH->bindParam(5,$_POST['comment'],PDO::PARAM_STR);
			$STH->bindParam(6,$_POST['rate'],PDO::PARAM_INT);
			$STH->bindParam(7,$_POST['comment'],PDO::PARAM_STR);
			$STH->execute();
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Voted'));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	}
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'You must close the ticket before rate the operator!'));
	}
	$DBH=null;
	exit();
}

else if($_POST['act']=='faq_rating' && isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3){
	$_POST['rate']=(is_numeric($_POST['rate']))? $_POST['rate']:0;
	$_POST['idBox']=(is_numeric($_POST['idBox']))? $_POST['idBox']/3823:0;
	if($GT86>10 && $rate>0){
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query = "SELECT `rate` FROM ".$SupportRateFaqTable." WHERE `faq_id`=?  AND `usr_id`= ? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['idBox'],PDO::PARAM_INT);
			$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					$orate=$a['rate'];
				}while ($a = $STH->fetch());
			}
			
			$orate=(is_numeric($orate) && !empty($orate))? $orate:0;
			$query = "INSERT INTO ".$SupportRateFaqTable." (`faq_id`,`usr_id`,`rate`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE updated='1',`rate`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['idBox'],PDO::PARAM_INT);
			$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(3,$_POST['rate'],PDO::PARAM_INT);
			$STH->bindParam(4,$_POST['rate'],PDO::PARAM_INT);
			$STH->execute();
			
			$query = "UPDATE ".$SupportFaqTable." a
						INNER JOIN ".$SupportRateFaqTable." b 
							ON b.faq_id=a.id AND b.usr_id=?
						SET 
							a.rate=ROUND((((a.num_rate * a.rate) - ?) + ?)/(CASE WHEN b.updated='1' THEN a.num_rate ELSE a.num_rate+1 END),2),
							a.num_rate=CASE WHEN b.updated='1' THEN a.num_rate ELSE a.num_rate+1 END,
							b.updated='0'
						WHERE  a.id=?";

			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(2,$orate,PDO::PARAM_INT);
			$STH->bindParam(3,$_POST['rate'],PDO::PARAM_INT);
			$STH->bindParam(4,$_POST['idBox'],PDO::PARAM_INT);
			$STH->execute();

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Voted'));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			$DBH=null;
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
			exit();
		}
	}
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Information'));
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='search_ticket' && isset($_SESSION['status'])  && $_SESSION['status']<3){
	$_POST['enid']=trim(preg_replace('/\s+/','',$_POST['enid']));
	$_POST['title']=trim(preg_replace('/\s+/',' ',$_POST['title']));
	$_POST['dep']=(is_numeric($_POST['dep']))? (int)$_POST['dep']:'';
	$_POST['statk']=(is_numeric($_POST['statk']))? (int)$_POST['statk']:'';//add
	$_POST['from']=trim(preg_replace('/\s+/','',$_POST['from']));
	$_POST['to']=trim(preg_replace('/\s+/','',$_POST['to']));
	if($_POST['from']!=''){
		list($yyyy,$mm,$dd) = explode('-',$_POST['from']);
		if (!checkdate($mm,$dd,$yyyy))
			$_POST['from']='';
		else
			$_POST['from']=$_POST['from']." 00:00:00";
	}
	if($_POST['to']!=''){
		list($yyyy,$mm,$dd) = explode('-',$_POST['to']);
		if (!checkdate($mm,$dd,$yyyy))
			$_POST['to']='';
		else
			$_POST['to']=$_POST['to']." 23:59:59";
	}
	
	if($_SESSION['status']==0 || $_SESSION['status']==2)
		$_POST['op']=trim(preg_replace('/\s+/',' ',$_POST['op']));

	if($_SESSION['status']==2){
		$_POST['id']=(is_numeric($_POST['id']))? (int)$_POST['id']:'';
		$_POST['opid']=(is_numeric($_POST['opid']))? (int)$_POST['opid']:'';
		$_POST['mail']=trim(preg_replace('/\s+/','',$_POST['mail']));
		$_POST['mail']=trim(preg_replace('/\s+/','',$_POST['mail']));
		$_POST['mail']=(empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL))? '':$_POST['mail'];
	}
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$query = "SELECT 
							a.id,
							b.department_name,
							c.name,
							a.title,
							CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END AS prio,
							a.created_time,
							a.last_reply,
							CASE a.ticket_status WHEN '0' THEN '<span class=\'label label-success\'>Closed</span>' WHEN '1' THEN '<span class=\'label label-important\'>Open</span>' WHEN '2' THEN '<span class=\'label label-warning\'>To Assign</span>' WHEN '3' THEN '<span class=\'label label-important\'>Reported</span>' ELSE 'Error' END AS stat
						FROM ".$SupportTicketsTable." a 
						JOIN ".$SupportDepaTable." b
							ON	b.id=a.department_id
						JOIN ".$SupportUserTable." c
							ON c.id=a.operator_id
						WHERE " ;
			$merge=array();
			if($_SESSION['status']==0){
				$query.=' a.user_id='.$_SESSION['id'];
				if($_POST['enid']!=''){
					$query.=' AND a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['enid']);
				}
				if($_POST['title']!=''){
					$query.=' AND a.title LIKE ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$_POST['title'].'%');
				}
				if($_POST['statk']!=''){
					$tail[]=' AND a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$_POST['statk']);
				}
				if($_POST['dep']!=''){
					$query.=' AND a.department_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$_POST['dep']);
				}
				if($_POST['op']!=''){
					$query.=' AND a.operator_id IN (SELECT `id` FROM '.$SupportUserTable.' WHERE `name`=? AND 0!=`status`)';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$_POST['op'].'%');
				}
				if($_POST['from']!=''){
					$query.=' AND a.created_time >= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['from']);
				}
				if($_POST['to']!=''){
					$query.=' AND a.created_time =< ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['to']);
				}
			}
			else if($_SESSION['status']==1){
				$query.=' a.user_id='.$_SESSION['id'].' OR a.operator_id='.$_SESSION['id'];
				if($_POST['enid']!=''){
					$query.=' AND a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['enid']);
				}
				if($_POST['title']!=''){
					$query.=' AND a.title LIKE ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$_POST['title'].'%');
				}
				if($_POST['statk']!=''){
					$tail[]=' AND a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$_POST['statk']);
				}
				if($_POST['dep']!=''){
					$query.=' AND a.department_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$_POST['dep']);
				}
				if($_POST['from']!=''){
					$query.=' AND a.created_time >= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['from']);
				}
				if($_POST['to']!=''){
					$query.=' AND a.created_time <= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['to']);
				}
			}
			else if($_SESSION['status']==2){
				$tail=array();
				if($_POST['id']!=''){
					$tail[]='a.user_i`=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$_POST['id']);
				}
				if($_POST['enid']!=''){
					$tail[]='a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['enid']);
				}
				if($_POST['title']!=''){
					$tail[]='a.title LIKE ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$_POST['title'].'%');
				}
				if($_POST['statk']!=''){
					$tail[]='a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$_POST['statk']);
				}
				if($_POST['dep']!=''){
					$tail[]='a.department_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$_POST['dep']);
				}
				if($_POST['opid']!=''){
					$tail[]='a.operator_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$_POST['opid']);
				}
				if($_POST['op']!=''){
					$tail[]='a.operator_id IN (SELECT `id` FROM '.$SupportUserTable.' WHERE `name`=? AND 0!=`status`)';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$_POST['op'].'%');
				}
				if($_POST['from']!=''){
					$tail[]='a.created_time >= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['from']);
				}
				if($_POST['to']!=''){
					$tail[]='a.created_time <= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$_POST['to']);
				}
				if($_POST['mail']!=''){
					$tail[]='(a.user_id=(SELECT `id` FROM '.$SupportUserTable.' WHERE `mail`=? LIMIT 1) OR operator_id=(SELECT `id` FROM '.$SupportUserTable.' WHERE `mail`=? LIMIT 1))';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$_POST['mail'].'%');
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$_POST['mail'].'%');
				}
				$query.=implode(' AND ',$tail);
			}
			$query.=' ORDER BY a.last_reply DESC';

			$STH = $DBH->prepare($query);

			$journey=count($merge);
			for ($i=0; $i<$journey;$i++) {
				$STH->bindParam($i+1,$merge[$i]['val'],$merge[$i]['type']);
			}
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$list=array('response'=>'ret','search'=>array());
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					$list['search'][]=array('id'=>$a['id'],'dname'=>htmlspecialchars($a['department_name'],ENT_QUOTES,'UTF-8'),'opname'=>htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8'),'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
				}while ($a = $STH->fetch());
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($list);
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	$DBH=null;
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='report_ticket' && isset($_SESSION['status']) && $_SESSION['status']<3){
	if(trim(preg_replace('/\s+/','',strip_tags($_POST['message'])))!=''){
		$_POST['message']=preg_replace('/\s+/',' ',preg_replace('/\r\n|[\r\n]/','<br/>',$_POST['message']));
		require_once 'htmlpurifier/HTMLPurifier.auto.php';
		$config = HTMLPurifier_Config::createDefault();
		$purifier = new HTMLPurifier($config);
		$_POST['message'] = $purifier->purify($_POST['message']);
		$check=trim(strip_tags($_POST['message']));
		if(empty($check)){
			$error[]='Empty Message';
		}
	}
	else
		$error[]='Empty Message';
	
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		$error[]='Incorrect ID';
	}

	if(!isset($_SESSION['tickets'][$_POST['id']]))
		$error[]='No information has been found about you and the ticket';

	if(!isset($error[0])){
		try{
			$side=($_SESSION['tickets'][$_POST['id']]['usr_id']==$_SESSION['id'])? 'User':'Operator';
			
			$query = "INSERT INTO ".$SupportFlagTable." (ref_id,tk_id,usr_id,side,reason) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE reason=?";
			
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['tickets'][$_POST['id']]['ref_id'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['id'],PDO::PARAM_INT);
			$STH->bindParam(3,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(4,$side,PDO::PARAM_STR);
			$STH->bindParam(5,$_POST['message'],PDO::PARAM_STR);	
			$STH->bindParam(6,$_POST['message'],PDO::PARAM_STR);	
			$STH->execute();

			$_SESSION[$_GET['id']]['reason']=$_POST['message'];
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Submitted'));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
			$DBH=null;
			exit();
		}
	}
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Error/s: '.implode(', ',$error)));
	}
	exit();
}

else if($_POST[$_SESSION['token']['act']]=='del_post_file' && isset($_SESSION['status']) && $_SESSION['status']<3){
	
	$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
	if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid ID'));
		exit();
	}
	
	$_POST['file_id']=(is_numeric($_POST['file_id']))? (int)$_POST['file_id']:exit();
	
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$query = "SELECT message_id,enc FROM ".$SupportUploadTable." WHERE `tk_id`=? AND `id`=? AND `uploader`=? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$_POST['id'],PDO::PARAM_STR);
		$STH->bindParam(2,$_POST['file_id'],PDO::PARAM_STR);
		$STH->bindParam(3,$_SESSION['id'],PDO::PARAM_STR);
		$STH->execute();

		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			$msid=$a['message_id'];
			$path='../upload/'.$a['enc'];
			file_put_contents($path,'');
			unlink($path);

			$query = "DELETE FROM ".$SupportUploadTable." WHERE `tk_id`=? AND `id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['file_id'],PDO::PARAM_STR);
			$STH->execute();
			if($STH->rowCount()>0){
				$query = "SELECT COUNT(*) AS qta FROM ".$SupportUploadTable." WHERE `message_id`=?";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$msid,PDO::PARAM_INT);
				$STH->execute();
				$STH->setFetchMode(PDO::FETCH_ASSOC);

				$a = $STH->fetch();
				if(empty($a) || $a['qta']<1){
					$query = "UPDATE ".$SupportMessagesTable." SET attachment='0' WHERE id=? LIMIT 1";
					$STH = $DBH->prepare($query);
					$STH->bindParam(1,$msid,PDO::PARAM_INT);
					$STH->execute();
				}
			}

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Deleted'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An error has occured'));
		}
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
	}
	exit();

}

else{
	if(!isset($_SESSION['id']))
		$error='You are logged out, please reload the page and log in';
	else
		$error='No Action Selected';

	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
		echo json_encode(array(0=>$error));
	else
		echo '<script>alert("'.$error.'");</script>';
	exit();
}


function retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable,$dep,$nope){
	$query = "SELECT id
				FROM(
						(SELECT b.id  
							FROM ".$SupportUserTable." b
							INNER JOIN ".$SupportUserPerDepaTable." a
								ON b.id=a.user_id
							WHERE a.department_id=? AND b.holiday='0' AND a.user_id!='".$nope."'
							ORDER BY b.assigned_tickets,b.solved_tickets ASC LIMIT 1)
					UNION
						(SELECT id  
						FROM ".$SupportUserTable."
						WHERE  status='2' AND id!='".$nope."'
						ORDER BY assigned_tickets,solved_tickets ASC LIMIT 1)
					) tab
				LIMIT 1";
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$dep,PDO::PARAM_INT);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				return $a['id'];
			}
			while ($a = $STH->fetch());
		}
		else
			return 'No Operator Available';
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
		$DBH=null;
		return $e->getMessage();
	}
}

function retrive_mime($encname,$mustang){
	$mime_types = array(
		'txt' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		'php' => 'text/php',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'json' => 'application/json; charset=utf-8',
		'xml' => 'application/xml',
		'swf' => 'application/x-shockwave-flash',
		'flv' => 'video/x-flv',
		'c' => 'text/plain',
		'h' => 'text/plain',
		'cpp' => 'text/plain',
		'cxx' => 'text/plain',
		'csv' => 'text/csv',
		'dwg' => 'image/x-dwg',
		'jar' => 'application/java-archive',
		'java' => 'text/x-java',
		'sql' => 'text/x-sql',
		'py' => 'text/x-python',

		// images
		'png' => 'image/png',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'bmp' => 'image/bmp',
		'ico' => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',

		// archives
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		'exe' => 'application/x-msdownload',
		'msi' => 'application/x-msdownload',
		'cab' => 'application/vnd.ms-cab-compressed',
		'gz' => 'application/gzip',
		'tgz' => 'application/gzip',
		

		// audio/video
		'mp3' => 'audio/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',

		// adobe
		'pdf' => 'application/pdf',
		'psd' => 'image/vnd.adobe.photoshop',
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'ps' => 'application/postscript',
		'swf' => 'application/x-shockwave-flash',

		// ms office
		'doc' => 'application/msword',
		'rtf' => 'application/rtf',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',
		'docx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'docm'=> 'application/vnd.ms-word.document.macroEnabled.12',
		'dotx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
		'dotm'=> 'application/vnd.ms-word.template.macroEnabled.12',
		'xlsx'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xlsm'=> 'application/vnd.ms-excel.sheet.macroEnabled.12',
		'xltx'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
		'xltm'=> 'application/vnd.ms-excel.template.macroEnabled.12',
		'xlsb'=> 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
		'xlam'=> 'application/vnd.ms-excel.addin.macroEnabled.12',
		'pptx'=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'pptm'=> 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
		'ppsx'=> 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		'ppsm'=> 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
		'potx'=> 'application/vnd.openxmlformats-officedocument.presentationml.template',
		'potm'=> 'application/vnd.ms-powerpoint.template.macroEnabled.12',
		'ppam'=> 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
		'sldx'=> 'application/vnd.openxmlformats-officedocument.presentationml.slide',
		'sldm'=> 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
		'one'=> 'application/msonenote',
		'onetoc2'=> 'application/msonenote',
		'onetmp'=> 'application/msonenote',
		'onepkg'=> 'application/msonenote',
		'thmx'=> 'application/vnd.ms-officetheme',

		// open office
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		'ott' => 'application/vnd.oasis.opendocument.text-template',
		'oth' => 'application/vnd.oasis.opendocument.text-web',
		'odm' => 'application/vnd.oasis.opendocument.text-master',
		'odg' => 'application/vnd.oasis.opendocument.graphics',
		'otg' => 'application/vnd.oasis.opendocument.graphics-template',
		'odp' => 'application/vnd.oasis.opendocument.presentation',
		'otp' => 'application/vnd.oasis.opendocument.presentation-template',
		'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
		'odc' => 'application/vnd.oasis.opendocument.chart',
		'odf' => 'application/vnd.oasis.opendocument.formula',
		'odb' => 'application/vnd.oasis.opendocument.database',
		'odi' => 'application/vnd.oasis.opendocument.image',
		'oxt' => 'application/vnd.openofficeorg.extension',
        );

	$ext = explode('.',$mustang);
	$count=count($ext)-1;
	$ext=strtolower($ext[$count]);
	if (function_exists('finfo_open')) {
		$finfo = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $encname);
		finfo_close($finfo);
		return $mimetype;
	}
	else if(function_exists('mime_content_type')) {
		$mimetype = mime_content_type($mustang);
		return $mimetype;
	}
	else if (isset($mime_types[$ext]))
		return $mime_types[$ext];
	else
		return 'Error';
}

function covert_size($val){if(empty($val))return 0;$val = trim($val);preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);$last = '';if(isset($matches[2]))$last = $matches[2];if(isset($matches[1]))$val = (int) $matches[1];switch (strtolower($last)){case 'g':case 'gb':$val *= 1024;case 'm':case 'mb':$val *= 1024;case 'k':case 'kb':$val *= 1024;}return (int) $val;}
function retrive_ip(){if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];}elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];}else{$ip=$_SERVER['REMOTE_ADDR'];}return $ip;}
function get_random_string($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ0123456789';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}
function curPageURL() {$pageURL = 'http';if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return dirname(dirname($pageURL));}						

?>