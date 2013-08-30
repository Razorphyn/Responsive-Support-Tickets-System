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

ini_set('session.hash_function', 'sha512');
ini_set('session.gc_maxlifetime', '1800');
ini_set('session.hash_bits_per_character', '5');
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.entropy_length', '512');
ini_set('session.gc_probability', '20');
ini_set('session.gc_divisor', '100');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.save_path', 'config/session');
session_name("RazorphynSupport");
session_start();
include_once 'config/database.php';
if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
if(isset($setting[4])) date_default_timezone_set($setting[4]);

//Session Check
if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800)
	$_SESSION['time']=time();
else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
	session_unset();
	session_destroy();
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Your Session has Expired, please reload the page and log in again'));
	}
	else
		echo '<script>alert("Your Session has Expired, please reload the page and log in again");</script>';
	exit();
}

else if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
	session_unset();
	session_destroy();
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Session, please reload the page and log in again'));
	}
	else
		echo '<script>alert("Invalid Session, please reload the page and log in again");</script>';
	exit();
}

//Function

if(isset($_POST['act']) && $_POST['act']=='register'){//check
	if($_POST['pwd']==$_POST['rpwd']){
		if(trim(preg_replace('/\s+/','',$_POST['name']))!='' && preg_match('/^[A-Za-z0-9\/\s\'-]+$/',$_POST['name'])) 
			$mustang=trim(preg_replace('/\s+/',' ',$_POST['name']));
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Name: only alphanumeric and single quote allowed'));
			exit();
		}
		$viper= trim(preg_replace('/\s+/','',$_POST['mail']));
		if($viper=='' && filter_var($viper, FILTER_VALIDATE_EMAIL)!=true){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Mail'));
			exit();
		}
		$pass= trim(preg_replace('/\s+/','',$_POST['pwd']));
		if($pass==''){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Password'));
			exit();
		}
		$pass=hash('whirlpool',crypt($_POST['pwd'],'$#%H4!df84a$%#RZ@£'));

		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "INSERT INTO ".$SupportUserTable." (`name`,`reg_key`,`mail`,`password`,`ip_address`) VALUES (?,?,?,?,?) ";
			$ip=retrive_ip();
			$reg=get_random_string(60);
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$mustang,PDO::PARAM_STR);
			$STH->bindParam(2,$reg,PDO::PARAM_STR);
			$STH->bindParam(3,$viper,PDO::PARAM_STR);
			$STH->bindParam(4,$pass,PDO::PARAM_STR);
			$STH->bindParam(5,$ip,PDO::PARAM_STR);
			$STH->execute();
				$_SESSION['id']=$DBH->lastInsertId();;
				$_SESSION['name']=$mustang;
				$_SESSION['mail']=$viper;
				$_SESSION['status']=3;
				$_SESSION['time']=time();
				$_SESSION['ip']=retrive_ip();
				$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
				$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewMem ".$_SESSION['id']." ";
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
				echo json_encode(array(0=>"User with mail: ".$viper." is already registred"));
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']==3 && $_POST['act']=='send_again'){//check
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
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	$DBH=null;
	exit();
}

else if(isset($_POST['act']) && !isset($_SESSION['status']) && $_POST['act']=='login'){
	$viper= trim(preg_replace('/\s+/','',$_POST['mail']));
	$viper=($viper!='' && filter_var($viper, FILTER_VALIDATE_EMAIL)) ? $viper:exit();
	$pass= trim(preg_replace('/\s+/','',$_POST['pwd']));
	$pass=($pass!='') ? $pass:exit();
	$pass=hash('whirlpool',crypt($_POST['pwd'],'$#%H4!df84a$%#RZ@£'));

	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$query = "SELECT `id`,`name`,`mail`,`status`,`mail_alert` FROM ".$SupportUserTable." WHERE `mail`=?  AND `password`= ? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$viper,PDO::PARAM_STR);
		$STH->bindParam(2,$pass,PDO::PARAM_STR);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				$_SESSION['time']=time();
				$_SESSION['id']=$a['id'];
				$_SESSION['name']=$a['name'];
				$_SESSION['mail']=$a['mail'];
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
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	$DBH=null;
	exit();
}

else if(isset($_POST['act']) && $_SESSION['status']<3 && $_POST['act']=='delete_ticket'){
	$encid=trim(preg_replace('/\s+/','',$_POST['enc']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$query="UPDATE ".$SupportTicketsTable." a
					INNER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET b.assigned_tickets= CASE  WHEN b.assigned_tickets!='0' THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END  
				WHERE a.enc_id=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->execute();
		
		$query = "DELETE FROM ".$SupportMessagesTable." WHERE `ticket_id`=(SELECT `id` FROM ".$SupportTicketsTable." WHERE `enc_id`=?) ";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->execute();

		$query = "SELECT enc FROM ".$SupportUploadTable." WHERE `ticket_id`=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
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
			$query = "DELETE FROM ".$SupportUploadTable." WHERE `ticket_id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
			$STH->execute();
		}
		
		$query = "DELETE FROM ".$SupportFlagTable." WHERE `enc_id`=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->execute();
		
		$query = "DELETE FROM ".$SupportTicketsTable." WHERE `enc_id`=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->execute();
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Deleted'));
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		exit();
	}
	exit();
}
	
else if(isset($_POST['act']) && isset($_POST['key']) && $_POST['act']=='activate_account'){//check
	$key=trim(preg_replace('/\s+/','',$_POST['key']));
	if(60!=strlen($key)){
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
			$STH->bindParam(1,$key,PDO::PARAM_STR,60);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$path='../upload/';
				do{
					$_SESSION['id']=$a['id'];
					$_SESSION['name']=$a['name'];
					$_SESSION['mail']=$a['mail'];
					$_SESSION['mail_alert']=$a['mail_alert'];
					$_SESSION['ip']=retrive_ip();
				}while ($a = $STH->fetch());

				$query = "UPDATE ".$SupportUserTable." SET status='0',reg_key='' WHERE `id`=?";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT,87);
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
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	}
	$DBH=null;
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']>2 && $_POST['act']=='verify'){//check
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
					if($st!=$_SESSION['status']){
						$_SESSION['status']=$st;
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
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	}
	$DBH=null;
	exit();
}

else if(isset($_POST['act']) && $_POST['act']=='forgot'){//check
	$viper= trim(preg_replace('/\s+/','',$_POST['mail']));
	$viper=($viper!='' && filter_var($viper, FILTER_VALIDATE_EMAIL)) ? $viper:exit();
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
		$STH->bindParam(1,$viper,PDO::PARAM_STR);
		$STH->bindParam(2,$mustang,PDO::PARAM_STR);
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
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	$DBH=null;
	exit();
}

else if(isset($_POST['act']) && $_POST['act']=='reset_password'){//check
	$npwd=(string)$_POST['npass'];
	$rpwd=(string)$_POST['rnpass'];
	$rmail= trim(preg_replace('/\s+/','',$_POST['rmail']));
	$rmail=($rmail!='' && filter_var($rmail, FILTER_VALIDATE_EMAIL)) ? $rmail:exit();
	
	$reskey=trim(preg_replace('/\s+/','',$_POST['key']));
	$reskey=($encid!='' && strlen($encid)==87) ? $encid:exit();
	
	if(trim(preg_replace('/\s+/','',$rpwd))!='' && $rpwd==$npwd){
		$pass=hash('whirlpool',crypt($rpwd,'$#%H4!df84a$%#RZ@£'));
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "UPDATE ".$SupportUserTable." SET password=?,tmp_password=NULL WHERE mail=? AND tmp_password=?";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$pass,PDO::PARAM_STR);
			$STH->bindParam(2,$rmail,PDO::PARAM_STR);
			$STH->bindParam(3,$reskey,PDO::PARAM_STR);
			$STH->execute();
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Updated'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_POST['act']=='logout'){
	session_unset();
	session_destroy();
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(0=>'logout'));
	exit();
}

else if(isset($_POST['createtk']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['createtk']=='Create New Ticket'){
	$letarr=array('M','d','C','f','K','w','p','T','B','X');
	$error=array();
	if(trim(preg_replace('/\s+/','',$_POST['message']))!=''){
		$message=trim(preg_replace('/\s+/',' ',$_POST['message']));
		require_once 'htmlpurifier/HTMLPurifier.auto.php';
		$config = HTMLPurifier_Config::createDefault();
		$purifier = new HTMLPurifier($config);
		$message = $purifier->purify($message);
		$check=trim(strip_tags($message));
		if(empty($check)){
			$error[]='Empty Message';
		}
	}
	else
		$error[]='Empty Message';
	
	if(trim(preg_replace('/\s+/','',$_POST['title']))!='')
		$tit=trim(preg_replace('/\s+/',' ',$_POST['title']));
	else
		$error[]='Empty Title';
		
	if(is_numeric($_POST['dep']))
		$dep=(int)trim($_POST['dep']);
	else
		$error[]='Error Department';

	if(is_numeric($_POST['priority']))
		$prio=trim($_POST['priority']);
	else
		$error[]='Error ';

	if(!isset($error[0])){
		$wsurl=(trim(preg_replace('/\s+/','',$_POST['wsurl'])!=''))? trim(preg_replace('/\s+/',' ',$_POST['wsurl'])):'';
		$contype=(trim(is_numeric($_POST['contype'])))? (int)$_POST['contype']:exit();
		$ftppass=(trim(preg_replace('/\s+/','',$_POST['ftppass'])!=''))? $_POST['ftppass']:'';
		$ftpus=(trim(preg_replace('/\s+/','',$_POST['ftpus'])!=''))? trim(preg_replace('/\s+/',' ',$_POST['ftpus'])):'';
		$maxsize=covert_size(ini_get('upload_max_filesize'));
		if($ftppass!=''){
			$crypttable=array('a'=>'X','b'=>'k','c'=>'Z','d'=>2,'e'=>'d','f'=>6,'g'=>'o','h'=>'R','i'=>3,'j'=>'M','k'=>'s','l'=>'j','m'=>8,'n'=>'i','o'=>'L','p'=>'W','q'=>0,'r'=>9,'s'=>'G','t'=>'C','u'=>'t','v'=>4,'w'=>7,'x'=>'U','y'=>'p','z'=>'F',0=>'q',1=>'a',2=>'H',3=>'e',4=>'N',5=>1,6=>5,7=>'B',8=>'v',9=>'y','A'=>'K','B'=>'Q','C'=>'x','D'=>'u','E'=>'f','F'=>'T','G'=>'c','H'=>'w','I'=>'D','J'=>'b','K'=>'z','L'=>'V','M'=>'Y','N'=>'A','O'=>'n','P'=>'r','Q'=>'O','R'=>'g','S'=>'E','T'=>'I','U'=>'J','V'=>'P','W'=>'m','X'=>'S','Y'=>'h','Z'=>'l');
			$ftppass=str_split($ftppass);
			$c=count($ftppass);
			for($i=0;$i<$c;$i++){
				if(array_key_exists($ftppass[$i],$crypttable))
					$ftppass[$i]=$crypttable[$crypttable[$ftppass[$i]]];
			}
			$ftppass=implode('',$ftppass);
		}
		if(isset($setting[6]) && $setting[6]!=null && $setting[6]!='')
			$maxsize=($setting[6]<=$maxsize)? $setting[6]:$maxsize;
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
			//Create Ticket
			$query = "INSERT INTO ".$SupportTicketsTable."(`department_id`,`user_id`,`title`,`priority`,`website`,`contype`,`ftp_user`,`ftp_password`,`created_time`,`last_reply`) VALUES (?,?,?,?,?,?,?,?,?,?)";
			$STH = $DBH->prepare($query);
			$date=date("Y-m-d H:i:s");
			$STH->bindParam(1,$dep,PDO::PARAM_INT);
			$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(3,$tit,PDO::PARAM_STR);
			$STH->bindParam(4,$prio,PDO::PARAM_INT);
			$STH->bindParam(5,$wsurl,PDO::PARAM_STR);
			$STH->bindParam(6,$contype,PDO::PARAM_STR);
			$STH->bindParam(7,$ftpus,PDO::PARAM_STR);
			$STH->bindParam(8,$ftppass,PDO::PARAM_STR);
			$STH->bindParam(9,$date,PDO::PARAM_STR);
			$STH->bindParam(10,$date,PDO::PARAM_STR);
			$STH->execute();

			echo '<script>parent.$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",debug : true,hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});</script>';
			//Assign Reference Number
			
			$tkid=$DBH->lastInsertId();
			$ip=retrive_ip();
			$refid=uniqid(hash('sha256',$tkid.$tit.$_SESSION['id']),true);
			$randomref=get_random_string(6);
			$spadd=str_split(strrev($_SESSION['id'].''));
			$lll=count($spadd);
			for($i=0;$i<$lll;$i++) $spadd[$i]=$letarr[$spadd[$i]];
			
			$randomref=implode('',$spadd).$randomref;
			$query = "UPDATE ".$SupportTicketsTable." SET enc_id=?,ref_id=? WHERE id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$refid,PDO::PARAM_STR);
			$STH->bindParam(2,$randomref,PDO::PARAM_STR);
			$STH->bindParam(3,$tkid,PDO::PARAM_INT);
			$STH->execute();
		
			//Insert Message
			$query = "INSERT INTO ".$SupportMessagesTable."(`user_id`,`message`,`ticket_id`,`ip_address`,`created_time`) VALUES (?,?,?,?,?);";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(2,$message,PDO::PARAM_STR);
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
						echo '<script>parent.noty({text: "File Upload Started",type:"information",timeout:2000});</script>';
						if(!is_dir('../upload')) mkdir('../upload');
						$uploadarr=array();
						$movedfiles=array();
						$sqlname=array();
						
						$query="INSERT INTO ".$SupportUploadTable." (`name`,`enc`,`uploader`,`num_id`,`ticket_id`,`message_id`,`upload_date`) VALUES ";
						for($i=0;$i<$count;$i++){
							if($_FILES['filename']['error'][$i]==0){
								if($_FILES['filename']['size'][$i]<=$maxsize && $_FILES['filename']['size'][$i]!=0 && trim($_FILES['filename']['name'][$i])!=''){
									if(count(array_keys($movedfiles,$_FILES['filename']['name'][$i]))==0){
										$encname=uniqid(hash('sha256',$msid.$_FILES['filename']['name'][$i]),true);
										$target_path = "../upload/".$encname;
										if(move_uploaded_file($_FILES['filename']['tmp_name'][$i], $target_path)){
											if(CryptFile("../upload/".$encname)){
												$movedfiles[]=$_FILES['filename']['name'][$i];
												$uploadarr[]=array($encid,$encname,$_FILES['filename']['name'][$i]);
												$query.='(?,"'.$encname.'","'.$_SESSION['id'].'",'.$tkid.',"'.$refid.'","'.$msid.'","'.$date.'"),';
												$sqlname[]=$_FILES['filename']['name'][$i];
												echo '<script>parent.noty({text: "'.$_FILES['filename']['name'][$i].' has been uploaded",type:"success",timeout:2000});</script>';
											}
										}
									}
								}
								else
									echo '<script>parent.noty({text: "The file '.json_encode($_FILES['filename']['name'][$i],JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).' is too big or null. Max file size: '.$maxsize.'",type:"error",timeout:9000});</script>';
							}
							else if($_FILES['filename']['error'][$i]!=4)
								echo '<script>parent.noty({text: "File Name:'.json_encode($_FILES['filename']['name'][$i],JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).' Error Code:'.$_FILES['filename']['error'][$i].'",type:"error",timeout:9000});</script>';
						}
						if(isset($uploadarr[0])){
							$query=substr_replace($query,'',-1);
							try{
								$STH = $DBH->prepare($query);
								$c=count($sqlname);
								for($i=0;$i<$c;$i++)
									$STH->bindParam($i+1,$sqlname[$i],PDO::PARAM_STR);
								$STH->execute();
								
								$query="UPDATE ".$SupportMessagesTable." SET attachment='1' WHERE id=?";
								$STH = $DBH->prepare($query);
								$STH->bindParam(1,$msid,PDO::PARAM_INT);
								$STH->execute();
								
							}
							catch(PDOException $e){
								file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
								echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "An error has occurred, please contact the administrator.",type:"error",timeout:9000});</script>';
							}
						}
						echo '<script>parent.noty({text: "File Upload Finished",type:"information",timeout:2000});</script>';
					}
				}
			}

			//Assign Ticket
			$selopid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $dep,$_SESSION['id']);
			$selopid=(is_numeric($selopid))?$selopid:null;
			if(is_numeric($selopid)){
				$query = "UPDATE ".$SupportTicketsTable." a ,".$SupportUserTable." b SET a.operator_id=?,a.ticket_status='1',b.assigned_tickets=(b.assigned_tickets+1) WHERE a.id=? AND b.id=? ";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$selopid,PDO::PARAM_INT);
				$STH->bindParam(2,$tkid,PDO::PARAM_INT);
				$STH->bindParam(3,$selopid,PDO::PARAM_INT);
				$STH->execute();

				$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
				$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php AssTick ".$refid." ";
				if(substr(php_uname(), 0, 7) == "Windows")
					pclose(popen("start /B ".$ex,"r")); 
				else
					shell_exec($ex." > /dev/null 2>/dev/null &");

				if($_SESSION['mail_alert']=='yes'){
					$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
					$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewTick ".$refid." ";
					if(substr(php_uname(), 0, 7) == "Windows")
						pclose(popen("start /B ".$ex,"r")); 
					else
						shell_exec($ex." > /dev/null 2>/dev/null &");
				}
			}
			else{
				$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
				$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewTick ".$refid." ";
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
				echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "You have already created a Ticket named: '.$tit.'",timeout:2000});</script>';
			else{
				file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
				echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "We are sorry, but an error has occurred, please contact the administrator if it persist",type:"information",timeout:2000});</script>';
			}
		}
	}
	else
		echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.implode(',',$error).'",type:"error",timeout:9000})</script>';
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='retrive_depart'){
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
		$STH->bindParam(1,$viper,PDO::PARAM_STR);
		$STH->bindParam(2,$pass,PDO::PARAM_STR);
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
					$dn['information'][]=array('id'=>$$a['id'],'name'=>htmlspecialchars($a['department_name'],ENT_QUOTES,'UTF-8'),'active'=>$$a['active'],'public'=>$a['public']);
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
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status'])  && $_SESSION['status']<3 && $_POST['act']=='retrive_tickets'){
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		if($_SESSION['status']==0){
			$query = "SELECT 
						a.enc_id,
						IF(b.department_name IS NOT NULL, b.department_name,'Unknown'),
						IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown'),
						a.title,
						CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END,
						a.created_time,
						a.last_reply,
						CASE a.ticket_status WHEN '0' THEN '<span class=\'label label-success\'>Closed</span>' WHEN '1' THEN '<span class=\'label label-important\'>Open</span>' WHEN '2' THEN '<span class=\'label label-warning\'>To Assign</span>' WHEN '3' THEN '<span class=\'label label-important\'>Reported</span>' ELSE 'Error' END 
					FROM ".$SupportTicketsTable." a
					LEFT JOIN ".$SupportDepaTable." b
						ON	b.id=a.department_id
					LEFT JOIN ".$SupportUserTable." c
						ON c.id=a.operator_id
					WHERE a.user_id=".$_SESSION['id']." 
					ORDER BY a.last_reply DESC 
					LIMIT 350";
			$STH = $DBH->prepare($query);
			$STH->execute();
			$list=array('response'=>'ret','tickets'=>array('user'=>array()));
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					$list['tickets']['user'][]=array('id'=>$a['enc_id'],'dname'=>$a['dname'],'opname'=>$a['opname'],'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
				}while ($a = $STH->fetch());
			}
		}
		else if($_SESSION['status']==1){
			$query = "SELECT 
						a.enc_id,
						IF(b.department_name IS NOT NULL, b.department_name,'Unknown'),
						CASE WHEN a.operator_id=".$_SESSION['id']." THEN '".$_SESSION['name']."' ELSE (IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown'))) END,
						a.operator_id,
						a.title,
						CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END,
						a.created_time,
						a.last_reply,
						CASE a.ticket_status WHEN '0' THEN '<span class=\'label label-success\'>Closed</span>' WHEN '1' THEN '<span class=\'label label-important\'>Open</span>' WHEN '2' THEN '<span class=\'label label-warning\'>To Assign</span>' WHEN '3' THEN '<span class=\'label label-important\'>Reported</span>' ELSE 'Error' END 
					FROM ".$SupportTicketsTable." a
					JOIN ".$SupportDepaTable." b
						ON	b.id=a.department_id
					JOIN ".$SupportUserTable." c
						ON c.id=a.operator_id
					WHERE a.ticket_status='1' AND a.operator_id='".$_SESSION['id']."' OR a.user_id='".$_SESSION['id']."' 
					ORDER BY a.last_reply DESC 
					LIMIT 350" ;
			$STH = $DBH->prepare($query);
			$STH->execute();
			$list=array('response'=>'ret','tickets'=>array('user'=>array(),'op'=>array()));
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					if($opid==$_SESSION['id'])
						$list['tickets']['op'][]=array('id'=>$a['enc_id'],'dname'=>$a['dname'],'opname'=>$a['opname'],'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
					else
						$list['tickets']['user'][]=array('id'=>$a['enc_id'],'dname'=>$a['dname'],'opname'=>$a['opname'],'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
				}while ($a = $STH->fetch());
			}
		}
		else if($_SESSION['status']==2){
			$query = "SELECT 
							a.user_id,
							a.enc_id,
							IF(b.department_name IS NOT NULL, b.department_name,'Unknown') AS dname,
							CASE WHEN a.operator_id=".$_SESSION['id']." THEN '".$_SESSION['name']."' ELSE ( IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown')) ) END AS opname,
							a.operator_id,
							a.title,
							CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE priority  END AS prio,
							a.created_time,
							a.last_reply,
							CASE a.ticket_status WHEN '0' THEN '<span class=\'label label-success\'>Closed</span>' WHEN '1' THEN '<span class=\'label label-important\'>Open</span>' WHEN '2' THEN '<span class=\'label label-warning\'>To Assign</span>' WHEN '3' THEN '<span class=\'label label-important\'>Reported</span>' ELSE 'Error' END AS stat
						
						FROM ".$SupportTicketsTable." a
						LEFT JOIN ".$SupportDepaTable." b
							ON	b.id=a.department_id
						LEFT JOIN ".$SupportUserTable." c
							ON c.id=a.operator_id
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
						$list['tickets']['op'][]=array('id'=>$a['enc_id'],'dname'=>$a['dname'],'opname'=>$a['opname'],'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
					else if($a['user_id']==$_SESSION['id'])
						$list['tickets']['user'][]=array('id'=>$a['enc_id'],'dname'=>$a['dname'],'opname'=>$a['opname'],'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
					else
						$list['tickets']['admin'][]=array('id'=>$a['enc_id'],'dname'=>$a['dname'],'opname'=>$a['opname'],'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
				}while ($a = $STH->fetch());
			}
		}
		if(isset($list)){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($list);
		}
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	$DBH=null;
	exit();
}

else if(isset($_POST['action']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['action']=='scrollpagination'){//check
	$offset = is_numeric($_POST['offset']) ? $_POST['offset'] : exit();
	$postnumbers = is_numeric($_POST['number']) ? $_POST['number'] : exit();
	$encid=trim(preg_replace('/\s+/','',$_POST['id']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:'';
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
						WHERE `ticket_id`=? ORDER BY `created_time` DESC LIMIT ".$offset.",".$postnumbers;
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION[$encid]['id'],PDO::PARAM_STR);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$ret=array('ret'=>'Entry','messages'=>array());
				$messageid=array();
				$count=0;
				do{
					$ret['messages'][$msid]=array(htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8'),$a['message'],$a['created_time']);
					if($a['attachment']==1)
						$messageid[]=$a['id'];
					$count++;
				}while ($a = $STH->fetch());
				if(count($messageid)>0){
					$messageid=implode(',',$messageid);
					try{
						$query = "SELECT `name`,`enc`,`message_id` FROM ".$SupportUploadTable." WHERE message_id IN (".$messageid.")";
						$STH = $DBH->prepare($query);
						$STH->bindParam(1,$viper,PDO::PARAM_STR);
						$STH->bindParam(2,$pass,PDO::PARAM_STR);
						$STH->execute();
						$STH->setFetchMode(PDO::FETCH_ASSOC);
						$a = $STH->fetch();
						if(!empty($a)){
							do{
								$ret['messages'][$a['message_id']][]='<div class="row-fluid"><div class="span2 offset2"><form method="POST" action="../php/function.php" target="hidden_upload" enctype="multipart/form-data"><input type="hidden" name="ticket_id" value="'.$encid.'"/><input type="hidden" name="file_download" value="'.$a['enc'].'"/><input type="submit" class="btn btn-link download" value="'.htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8').'"></form></div></div>';
							}while ($a = $STH->fetch());
						}
					}
					catch(PDOException $e){
						file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
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
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='save_setting'){
	if(trim(preg_replace('/\s+/','',$_POST['name']))!='' && preg_match('/^[A-Za-z0-9\/\s\'-]+$/',$_POST['name'])) 
		$mustang=trim(preg_replace('/\s+/',' ',$_POST['name']));
	else{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Name: only alphanumeric and single quote allowed'));
		exit();
	}
	$alert=($_POST['almail']!='no') ? 'yes':'no';
	$dfmail=trim(preg_replace('/\s+/','',$_POST['mail']));
	if(empty($dfmail) || !filter_var($dfmail, FILTER_VALIDATE_EMAIL)){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Invalid Mail: empty mail or not allowed characters'));
		exit();
	}
		
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		if(isset($_POST['oldpwd']) && isset($_POST['nldpwd']) && isset($_POST['rpwd']) && $_POST['nldpwd']==$_POST['rpwd']){
			$opass=hash("whirlpool",crypt($_POST['oldpwd'],'$#%H4!df84a$%#RZ@£'));
			$query = "SELECT `id` FROM ".$SupportUserTable." WHERE `password`= ? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$opass,PDO::PARAM_STR);
			$STH->execute();	
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					$camaroret=$a['id'];
				}while ($a = $STH->fetch());
				
				if($camaroret==$_SESSION['id']){
					$pass=hash("whirlpool",crypt($_POST['nldpwd'],'$#%H4!df84a$%#RZ@£'));
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
				$STH->bindParam(1,$mustang,PDO::PARAM_STR);
				$STH->bindParam(2,$dfmail,PDO::PARAM_STR);
				$STH->bindParam(3,$alert,PDO::PARAM_STR);
				$STH->bindParam(4,$pass,PDO::PARAM_STR);
			}
			else{
				$STH->bindParam(1,$mustang,PDO::PARAM_STR);
				$STH->bindParam(2,$dfmail,PDO::PARAM_STR);
				$STH->bindParam(3,$alert,PDO::PARAM_STR);
			}
			$STH->execute();
			$_SESSION['name']=$mustang;
			$_SESSION['mail_alert']=$alert;
			$_SESSION['mail']=$dfmail;
			
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
			echo json_encode(array(0=>"User with mail: ".$dfmail." is already registred"));
		}
		else{
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
		}
	}
	$DBH=null;
	exit();
}

else if(isset($_POST['post_reply']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['post_reply']=='Post Reply'){
	$encid=trim(preg_replace('/\s+/','',$_POST['id']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$error=array();
	if(trim(preg_replace('/\s+/','',$_POST['message']))!=''){
		$message=trim(preg_replace('/\s+/',' ',$_POST['message']));
		require_once 'htmlpurifier/HTMLPurifier.auto.php';
		$config = HTMLPurifier_Config::createDefault();
		$purifier = new HTMLPurifier($config);
		$message = $purifier->purify($message);
		$check=trim(strip_tags($message));
		if(empty($check)){
			$error[]='Empty Message';
		}
	}
	else
		$error[]='Empty Message';
	
	if(!isset($error[0])){
		if(isset($_SESSION[$encid]['id'])){
			try{
				$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
				$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

				if($_SESSION[$encid]['status']==0 && $_SESSION['id']==$_SESSION[$encid]['usr_id']){
					try{
					$query = "UPDATE ".$SupportTicketsTable." a ,".$SupportUserTable." b SET a.ticket_status= CASE WHEN a.operator_id=0 THEN '2' ELSE '1' END, b.assigned_tickets= CASE WHEN a.ticket_status='0' THEN (b.assigned_tickets+1) ELSE b.assigned_tickets END,b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END,a.ticket_status= CASE WHEN a.operator_id='0' THEN '2' ELSE '1' END WHERE a.enc_id=? OR b.id=a.operator_id";
					$STH = $DBH->prepare($query);
					$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
					$STH->execute();
					
					$query = "SELECT ticket_status FROM ".$SupportTicketsTable." WHERE id=? LIMIT 1";
					$STH = $DBH->prepare($query);
					$STH->bindParam(1,$_SESSION[$encid]['id'],PDO::PARAM_INT);
					$STH->execute();
					
					$STH->setFetchMode(PDO::FETCH_ASSOC);
					$a = $STH->fetch();
					if(!empty($a)){
						do{
							$_SESSION[$encid]['status']=$tkst;
						}while ($a = $STH->fetch());
						echo '<script>parent.$("#statustk").val(\'1\').change();</script>';
					}
					echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket",type:"error",timeout:9000});</script>';
					}
					catch(PDOException $e){
						file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
						echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket",type:"error",timeout:9000});</script>';
					}
				}
				$ip=retrive_ip();
				$date=date("Y-m-d H:i:s");
				$query = "INSERT INTO ".$SupportMessagesTable."(`user_id`,`message`,`ticket_id`,`ip_address`,`created_time`) VALUES (?,?,?,?,?);";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
				$STH->bindParam(2,$message,PDO::PARAM_STR);
				$STH->bindParam(3,$_SESSION[$encid]['id'],PDO::PARAM_INT);
				$STH->bindParam(4,$ip,PDO::PARAM_STR);
				$STH->bindParam(5,$date,PDO::PARAM_STR);
				$STH->execute();

				if(isset($setting[5]) && $setting[5]==1){
					//Upload File
					if(isset($_FILES['filename'])){
						$msid=$DBH->lastInsertId();
						$count=count($_FILES['filename']['name']);
						if($count>0){
							echo '<script>parent.noty({text: "File Upload Started",type:"information",timeout:2000});</script>';
							if(!is_dir('../upload')) mkdir('../upload');
							$maxsize=covert_size(ini_get('upload_max_filesize'));
							if(isset($setting[6]) && $setting[6]!=null)
								$maxsize=($setting[6]<=$maxsize)? $setting[6]:$maxsize;
							
							$uploadarr=array();
							$movedfiles=array();
							$sqlname=array();
							
							$query="INSERT INTO ".$SupportUploadTable." (`name`,`enc`,`uploader`,`num_id`,`ticket_id`,`message_id`,`upload_date`) VALUES ";
							for($i=0;$i<$count;$i++){
								if($_FILES['filename']['error'][$i]==0){
									if($_FILES['filename']['size'][$i]<=$maxsize && $_FILES['filename']['size'][$i]!=0){
										if(count(array_keys($movedfiles,$_FILES['filename']['name'][$i]))==0){
											$encname=uniqid(hash('sha256',$msid.$_FILES['filename']['name'][$i]),true);
											if(move_uploaded_file($_FILES['filename']['tmp_name'][$i], "../upload/".$encname)){
												if(CryptFile("../upload/".$encname)){
													$movedfiles[]=$_FILES['filename']['name'][$i];
													$uploadarr[]=array($encid,$encname,$_FILES['filename']['name'][$i]);
													$query.='(?,"'.$encname.'","'.$_SESSION['id'].'","'.$_SESSION[$encid]['id'].'","'.$encid.'","'.$msid.'","'.$date.'"),';
													$sqlname[]=$_FILES['filename']['name'][$i];
													echo '<script>parent.noty({text: "'.$_FILES['filename']['name'][$i].' has been uploaded",type:"success",timeout:2000});</script>';
												}
											}
										}
									}
									else
										echo '<script>parent.noty({text: "The file '.json_encode($_FILES['filename']['name'][$i], JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).' is too big or null. Max file size: '.$maxsize.'",type:"error",timeout:9000});</script>';
								}
								else if($_FILES['filename']['error'][$i]!=4)
									echo '<script>parent.noty({text: "Error:'.json_encode($_FILES['filename']['name'][$i], JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).' Code:'.$_FILES['filename']['error'][$i].'",type:"error",timeout:9000});</script>';
							}
							if(isset($uploadarr[0])){
								$query=substr_replace($query,'',-1);
								$STH = $DBH->prepare($query);
								$c=count($sqlname);
								for($i=0;$i<$c;$i++)
									$STH->bindParam($i+1,$sqlname[$i],PDO::PARAM_STR);
								$STH->execute();
											
								$query="UPDATE ".$SupportMessagesTable." SET attachment='1' WHERE id=?";
								$STH = $DBH->prepare($query);
								$STH->bindParam(1,$msid,PDO::PARAM_INT);
								$STH->execute();
							}	
							echo '<script>parent.noty({text: "File Upload Finished",type:"information",timeout:2000});</script>';
						}
					}
				}
				//Send Mail
				if($_SESSION[$encid]['status']!=2){
					if($_SESSION['id']==$_SESSION[$encid]['usr_id']){
						$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
						$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewRep ".$encid." 0";
					}
					else if($_SESSION[$encid]['status']==1){
						$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
						$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewRep ".$encid." 1";
					}
					
					if(isset($ex)){
						if(substr(php_uname(), 0, 7) == "Windows")
							pclose(popen("start /B ".$ex,"r")); 
						else
							shell_exec($ex." > /dev/null 2>/dev/null &");
					}
				}
				//Post Reply
				if(isset($uploadarr[0])){
					$json=json_encode($uploadarr);
					echo "<script>parent.$('#formreply').nimbleLoader('hide');parent.post_reply('".addslashes($message)."','".$date."','".htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8')."',".$json.");</script>";
				}
				else
					echo "<script>parent.$('#formreply').nimbleLoader('hide');parent.post_reply('".addslashes($message)."','".$date."','".htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8')."',null);</script>";
			}
			catch(PDOException $e){
				file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='update_status'){
	if($_SESSION['status']==0)
		$charger=($_POST['status']==1 || $_POST['status']==2)? 1:0;
	else
		$charger=($_POST['status']==0 || $_POST['status']==1 || $_POST['status']==2)? $_POST['status']:0;
	$encid=trim(preg_replace('/\s+/','',$_POST['id']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	if($charger==0){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT OUTER JOIN ".$SupportUserTable." b
					ON b.id=a.operator_id
					SET 
						b.solved_tickets= CASE WHEN a.ticket_status!='0' THEN (b.solved_tickets+1) ELSE b.solved_tickets END , 
						b.assigned_tickets= CASE  WHEN a.ticket_status!='0' AND b.assigned_tickets>=1 THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END
					WHERE a.enc_id=? ";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET
						a.ticket_status='0'
					WHERE a.enc_id=? ";
	}
	else if($charger==2){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT OUTER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET 
						b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END , 
						b.assigned_tickets= CASE  WHEN a.ticket_status='1' AND b.assigned_tickets>=1 THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END
					WHERE a.enc_id=?";
		$lquery="UPDATE ".$SupportTicketsTable." a
					SET 
						a.operator_id=0,
						a.ticket_status='2'
					WHERE a.enc_id=?";
	}
	else if($charger==1){
		$fquery = "UPDATE ".$SupportTicketsTable." a 
						LEFT OUTER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET 
						b.assigned_tickets= CASE WHEN a.ticket_status='0' THEN (b.assigned_tickets+1) ELSE b.assigned_tickets END,
						b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END
					WHERE a.enc_id=?";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET
						a.ticket_status= CASE WHEN a.operator_id='0' THEN '2' ELSE '1' END
					WHERE a.enc_id=?";
	}
	else
		exit();

	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$STH = $DBH->prepare($fquery);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->execute();

		$STH = $DBH->prepare($lquery);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->execute();
		
		$query = "SELECT ticket_status FROM ".$SupportTicketsTable." WHERE enc_id=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a = $STH->fetch();
		if(!empty($a)){
			do{
				$_SESSION[$encid]['status']=$a['ticket_status'];
			}while ($a = $STH->fetch());
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Saved'));
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']==1 && $_POST['act']=='move_opera_ticket'){// deep check
	$dpid=(is_numeric($_POST['dpid'])) ? $_POST['dpid']:exit();
	$encid=trim(preg_replace('/\s+/','',$_POST['id']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$opid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $dpid,$_SESSION[$encid]['usr_id']);
		if(!is_numeric($opid))
			$opid=0;
		$query="UPDATE ".$SupportTicketsTable." a
						LEFT JOIN ".$SupportUserTable." b
							ON b.id=a.operator_id
						LEFT JOIN ".$SupportUserTable." c
							ON c.id=?
						SET
							a.department_id=?,
							a.ticket_status= CASE WHEN ?=0 AND `ticket_status`!=0 THEN 2 WHEN ?!=0 AND `ticket_status`='2' THEN '1' ELSE `ticket_status` END,
							b.assigned_tickets=IF(b.id!=?,b.assigned_tickets-1,b.assigned_tickets),
							c.assigned_tickets=IF(c.id!=a.operator_id,c.assigned_tickets+1,c.assigned_tickets),
							a.operator_id=?
						WHERE a.enc_id=? ";
		
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$dpid,PDO::PARAM_INT);
		$STH->bindParam(2,$opid,PDO::PARAM_INT);
		$STH->bindParam(3,$opid,PDO::PARAM_INT);
		$STH->bindParam(4,$opid,PDO::PARAM_INT);
		$STH->bindParam(5,$encid,PDO::PARAM_STR);
		$STH->execute();
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Moved'));
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='update_ticket_title'){
	$tit=(trim(preg_replace('/\s+/','',$_POST['tit']))!='')? trim(preg_replace('/\s+/',' ',$_POST['tit'])):exit();
	$encid=trim(preg_replace('/\s+/','',$_POST['id']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$query="UPDATE ".$SupportTicketsTable." SET title=? WHERE enc_id=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$tit,PDO::PARAM_STR);
		$STH->bindParam(2,$encid,PDO::PARAM_STR);
		$STH->execute();
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Updated',1=>htmlspecialchars($tit,ENT_QUOTES,'UTF-8')));
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='update_ticket_connection'){
	$encid=trim(preg_replace('/\s+/','',$_POST['id']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$con=(is_numeric($_POST['contype']))? $_POST['contype']:exit();
	$web=(trim(preg_replace('/\s+/','',$_POST['website']))!='')? $_POST['website']:'';
	$usr=(trim(preg_replace('/\s+/','',$_POST['user'])!=''))? $_POST['user']:'';
	$pass=(trim(preg_replace('/\s+/','',$_POST['pass'])!=''))? $_POST['pass']:'';
	if($pass!='' && $pass!=null){
		$crypttable=array('a'=>'X','b'=>'k','c'=>'Z','d'=>2,'e'=>'d','f'=>6,'g'=>'o','h'=>'R','i'=>3,'j'=>'M','k'=>'s','l'=>'j','m'=>8,'n'=>'i','o'=>'L','p'=>'W','q'=>0,'r'=>9,'s'=>'G','t'=>'C','u'=>'t','v'=>4,'w'=>7,'x'=>'U','y'=>'p','z'=>'F',0=>'q',1=>'a',2=>'H',3=>'e',4=>'N',5=>1,6=>5,7=>'B',8=>'v',9=>'y','A'=>'K','B'=>'Q','C'=>'x','D'=>'u','E'=>'f','F'=>'T','G'=>'c','H'=>'w','I'=>'D','J'=>'b','K'=>'z','L'=>'V','M'=>'Y','N'=>'A','O'=>'n','P'=>'r','Q'=>'O','R'=>'g','S'=>'E','T'=>'I','U'=>'J','V'=>'P','W'=>'m','X'=>'S','Y'=>'h','Z'=>'l');
								
		$pass=str_split($pass);
		$c=count($pass);
		for($i=0;$i<$c;$i++){
			if(array_key_exists($pass[$i],$crypttable))
				$pass[$i]=$crypttable[$crypttable[$pass[$i]]];
		}
		$pass=implode('',$pass);
	}
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$query="UPDATE ".$SupportTicketsTable." SET website=?,contype=?,ftp_user=?,ftp_password=? WHERE enc_id=?";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$web,PDO::PARAM_STR);
		$STH->bindParam(2,$con,PDO::PARAM_STR);
		$STH->bindParam(3,$usr,PDO::PARAM_STR);
		$STH->bindParam(4,$pass,PDO::PARAM_STR);
		$STH->bindParam(5,$encid,PDO::PARAM_STR,87);
		$STH->execute();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Updated'));
	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if(isset($_POST['file_download']) && isset($_SESSION['status']) && $_SESSION['status']<3){
	$encid=trim(preg_replace('/\s+/','',$_POST['ticket_id']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$file=(trim(preg_replace('/\s+/','',$_POST['file_download']))!='') ? $_POST['file_download']:exit();
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$query="SELECT name FROM ".$SupportUploadTable." WHERE ticket_id=? AND enc=? LIMIT 1";
		$STH = $DBH->prepare($query);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->bindParam(2,$file,PDO::PARAM_STR);
		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$a=$STH->fetch();
		if(!empty($a)){
			do{
				$enc='../upload/'.$file;
				if(DecryptFile($enc)){
					$mime=retrive_mime($enc,$a['name']);
					if($mime!='Error'){
						header("Content-Type: ".$mime);
						header("Cache-Control: no-store, no-cache");
						header("Content-Description: ".$a['name']);
						header("Content-Disposition: attachment;filename=".$a['name']);
						header("Content-Transfer-Encoding: binary");
						readfile($enc);
						CryptFile($enc);
						echo '<script>parent.noty({text: "Your download will start soon",type:"information",timeout:9000});</script>';
					}
					else
						echo '<script>parent.noty({text: "Can\'t retrive Content-Type",type:"error",timeout:9000});</script>';
				}
			}while($a=$STH->fetch());
		}
		else{
			echo '<script>parent.noty({text: "No matches",type:"error",timeout:9000});</script>';
		}
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		echo '<script>parent.noty({text: "We are sorry, but an error has occurred, please contact the administrator if it persist",type:"error",timeout:9000});</script>';
	}
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='update_ticket_index'){
	$encid=trim(preg_replace('/\s+/','',$_POST['id']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$tit=(trim(preg_replace('/\s+/','',$_POST['title'])!=''))? trim(preg_replace('/\s+/',' ',$_POST['title'])):exit();
	$prio = (is_numeric($_POST['priority']))? $_POST['priority']:0;

	if($_SESSION['status']==0)
		$charger=($_POST['status']==1 || $_POST['status']==2)? 1:0;
	else
		$charger=($_POST['status']==0 || $_POST['status']==1 || $_POST['status']==2)? $_POST['status']:0;

	if($charger==0){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT OUTER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET 
						b.solved_tickets= CASE WHEN a.ticket_status='1' THEN (b.solved_tickets+1) ELSE b.solved_tickets END , 
						b.assigned_tickets= CASE  WHEN ( a.ticket_status!='0' AND b.assigned_tickets>=1) THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END
					WHERE a.enc_id=?";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET 
						a.title=? , 
						a.priority=?,
						a.ticket_status=?
					WHERE a.enc_id=?";
	}
	else if($charger==1){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT OUTER JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET
						b.assigned_tickets= CASE WHEN a.ticket_status='0' THEN (b.assigned_tickets+1) ELSE b.assigned_tickets END,
						b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END
					WHERE a.enc_id=?";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET
						a.title=? , 
						a.priority=?, 
						a.ticket_status= CASE WHEN a.operator_id=0 THEN '2' ELSE ? END,
						a.ticket_status= CASE WHEN a.operator_id='0' THEN '2' ELSE '1' END 
					WHERE a.enc_id=?";
	}
	else if($charger==2){
		$fquery = "UPDATE ".$SupportTicketsTable." a
					LEFT JOIN ".$SupportUserTable." b
						ON b.id=a.operator_id
					SET
						b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END , 
						b.assigned_tickets= CASE  WHEN a.ticket_status='1' AND b.assigned_tickets>=1 THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END
					WHERE a.enc_id=?";
		$lquery = "UPDATE ".$SupportTicketsTable." a
					SET
						a.title=? , 
						a.priority=?,
						a.operator_id=0,
						a.ticket_status=?
					WHERE a.enc_id=?";
	}
	else
		exit();
		
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$STH = $DBH->prepare($fquery);
		$STH->bindParam(1,$encid,PDO::PARAM_STR,87);
		$STH->execute();
		
		$STH = $DBH->prepare($lquery);
		$STH->bindParam(1,$tit,PDO::PARAM_STR);
		$STH->bindParam(2,$prio,PDO::PARAM_STR);
		$STH->bindParam(3,$charger,PDO::PARAM_STR);
		$STH->bindParam(4,$encid,PDO::PARAM_STR);
		$STH->execute();
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'Saved'));
	}
	catch(Exception $e){
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='rating'){
	$rate=(is_numeric($_POST['rate']))? $_POST['rate']:0;
	$GT86=(is_numeric($_POST['idBox']))? $_POST['idBox']/3823:0;
	$encid=trim(preg_replace('/\s+/','',$_POST['tkid']));
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$note=trim(preg_replace('/\s+/',' ',$_POST['comment']));
	if(isset($_SESSION[$encid]['status']) && $_SESSION[$encid]['status']==0){
		try{
			$query = "UPDATE ".$SupportUserTable." a
					INNER JOIN ".$SupportTicketsTable." b 
						ON b.operator_id=a.id
					SET a.rating=ROUND(((a.number_rating * a.rating - (CASE WHEN b.operator_rate>0 THEN b.operator_rate ELSE 0 END) + ?)/(CASE WHEN a.number_rating=0 THEN 1 WHEN b.operator_rate>0 THEN  a.number_rating ELSE a.number_rating+1 END)),2),
						a.number_rating=CASE WHEN b.operator_rate>0 THEN a.number_rating ELSE a.number_rating+1 END,
						b.operator_rate=? 
					WHERE  b.enc_id=?";
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$STH = $DBH->prepare($query);
			$STH->bindValue(1,strval($rate));
			$STH->bindValue(2,strval($rate));
			$STH->bindParam(3,$encid,PDO::PARAM_STR);
			$STH->execute();
			
			$query = "INSERT INTO ".$SupportRateTable." (`ref_id`,`enc_id`,`usr_id`,`rate`,`note`) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE `rate`=?,`note`=?";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION[$encid]['ref_id'],PDO::PARAM_STR);
			$STH->bindParam(2,$encid,PDO::PARAM_STR);
			$STH->bindParam(3,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(4,$rate,PDO::PARAM_INT);
			$STH->bindParam(5,$note,PDO::PARAM_STR);
			$STH->bindParam(6,$rate,PDO::PARAM_INT);
			$STH->bindParam(7,$note,PDO::PARAM_STR);
			$STH->execute();
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Voted'));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='faq_rating'){
	$rate=(is_numeric($_POST['rate']))? $_POST['rate']:0;
	$GT86=(is_numeric($_POST['idBox']))? $_POST['idBox']/3823:0;
	if($GT86>10 && $rate>0){
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$query = "INSERT INTO ".$SupportRateFaqTable." (`faq_id`,`usr_id`,`rate`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `updated`='1'";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$GT86,PDO::PARAM_INT);
			$STH->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(3,$rate,PDO::PARAM_INT);
			$STH->execute();

			$query = "UPDATE ".$SupportFaqTable." a
						INNER JOIN ".$SupportRateFaqTable." b 
							ON b.faq_id=a.id AND b.usr_id=?
						SET 
							a.rate=CASE WHEN b.updated='1' THEN ROUND(((a.num_rate * a.rate - b.rate) + ?)/(a.num_rate),2) ELSE ROUND ((a.rate + ?)/(a.num_rate+1),2) END,
							a.num_rate=CASE WHEN b.updated='1' THEN a.num_rate ELSE a.num_rate+1 END,
							b.updated='0',
							b.rate=?
						WHERE  a.id=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(2,$rate,PDO::PARAM_INT);
			$STH->bindParam(3,$rate,PDO::PARAM_INT);
			$STH->bindParam(4,$rate,PDO::PARAM_INT);
			$STH->bindParam(5,$GT86,PDO::PARAM_INT);
			$STH->execute();
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Voted'));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
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

else if(isset($_POST['act']) && isset($_SESSION['status'])  && $_SESSION['status']<3 && $_POST['act']=='search_ticket'){
	$enid=trim(preg_replace('/\s+/','',$_POST['enid']));
	$tit=trim(preg_replace('/\s+/',' ',$_POST['title']));
	$dep=(is_numeric($_POST['dep']))? (int)$_POST['dep']:'';
	$statk=(is_numeric($_POST['statk']))? (int)$_POST['statk']:'';
	$from=trim(preg_replace('/\s+/','',$_POST['from']));
	$to=ptrim(reg_replace('/\s+/','',$_POST['to']));
	if($from!=''){
		list($yyyy,$mm,$dd) = explode('-',$from);
		if (!checkdate($mm,$dd,$yyyy))
			$from='';
		else
			$from=$from." 00:00:00";
	}
	if($to!=''){
		list($yyyy,$mm,$dd) = explode('-',$to);
		if (!checkdate($mm,$dd,$yyyy))
			$to='';
		else
			$to=$to." 23:59:59";
	}
	if($_SESSION['status']==0 || $_SESSION['status']==2)
		$op=trim(preg_replace('/\s+/',' ',$_POST['op']));
	if($_SESSION['status']==2){
		$id=(is_numeric($_POST['id']))? (int)$_POST['id']:'';
		$opid=(is_numeric($_POST['opid']))? (int)$_POST['opid']:'';
		$usmail=trim(preg_replace('/\s+/','',$_POST['mail']));
	}
	try{
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$query = "SELECT 
							a.enc_id,
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
				if($enid!=''){
					$query.=' AND a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$enid);
				}
				if($tit!=''){
					$query.=' AND a.title LIKE ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$tit.'%');
				}
				if($dep!=''){
					$query.=' AND a.department_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$dep);
				}
				if($op!=''){
					$query.=' AND a.operator_id IN (SELECT `id` FROM '.$SupportUserTable.' WHERE `name`=? AND 0!=`status`)';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$op.'%');
				}
				if($from!=''){
					$query.=' AND a.created_time >= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$from);
				}
				if($to!=''){
					$query.=' AND a.created_time =< ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$to);
				}
			}
			else if($_SESSION['status']==1){
				$query.=' a.user_id='.$_SESSION['id'].' OR a.operator_id='.$_SESSION['id'];
				if($enid!=''){
					$query.=' AND a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$enid);
				}
				if($tit!=''){
					$query.=' AND a.title LIKE ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$tit.'%');
				}
				if($dep!=''){
					$query.=' AND a.department_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$dep);
				}
				if($from!=''){
					$query.=' AND a.created_time >= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$from);
				}
				if($to!=''){
					$query.=' AND a.created_time <= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$to);
				}
			}
			else if($_SESSION['status']==2){
				$tail=array();
				if($id!=''){
					$tail[]='a.user_i`=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$id);
				}
				if($enid!=''){
					$tail[]='a.ref_id=?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$enid);
				}
				if($tit!=''){
					$tail[]='a.title LIKE ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$tit.'%');
				}
				if($dep!=''){
					$tail[]='a.department_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$dep);
				}
				if($opid!=''){
					$tail[]='a.operator_id=?';
					$merge[]=array('type'=>PDO::PARAM_INT,'val'=>$opid);
				}
				if($op!=''){
					$tail[]='a.operator_id IN (SELECT `id` FROM '.$SupportUserTable.' WHERE `name`=? AND 0!=`status`)';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$op.'%');
				}
				if($from!=''){
					$tail[]='a.created_time >= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$from);
				}
				if($to!=''){
					$tail[]='a.created_time <= ?';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>$to);
				}
				if($usmail!=''){
					$tail[]='(a.user_id=(SELECT `id` FROM '.$SupportUserTable.' WHERE `mail`=? LIMIT 1) OR operator_id=(SELECT `id` FROM '.$SupportUserTable.' WHERE `mail`=? LIMIT 1))';
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$usmail.'%');
					$merge[]=array('type'=>PDO::PARAM_STR,'val'=>'%'.$usmail.'%');
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
					$list['search'][]=array('id'=>$a['enc_id'],'dname'=>htmlspecialchars($a['department_name'],ENT_QUOTES,'UTF-8'),'opname'=>htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8'),'title'=>htmlspecialchars($a['title'],ENT_QUOTES,'UTF-8'),'priority'=>$a['prio'],'date'=>$a['created_time'],'reply'=>$a['last_reply'],'status'=>$a['stat']);
				}while ($a = $STH->fetch());
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($list);
	}
	catch(PDOException $e){
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
	}
	$DBH=null;
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='report_ticket'){

	if(trim(preg_replace('/\s+/','',strip_tags($_POST['message'])))!=''){
		$message=preg_replace('/\s+/',' ',preg_replace('/\r\n|[\r\n]/','<br/>',$_POST['message']));
		require_once 'htmlpurifier/HTMLPurifier.auto.php';
		$config = HTMLPurifier_Config::createDefault();
		$purifier = new HTMLPurifier($config);
		$message = $purifier->purify($message);
		$check=trim(strip_tags($message));
		if(empty($check)){
			$error[]='Empty Message';
		}
	}
	else
		$error[]='Empty Message';
	
	$encid=trim(preg_replace('/\s+/','',$_POST['id']));
	if($encid=='' && strlen($encid)==87)
		$error[]='Incorrect ID';

	if(!isset($_SESSION[$encid]))
		$error[]='No information has been found about you and the ticket';

	if(!isset($error[0])){
		try{
			$side=($_SESSION[$encid]['usr_id']==$_SESSION['id'])? 'User':'Operator';
			$query = "INSERT INTO ".$SupportFlagTable." (ref_id,enc_id,usr_id,side,reason) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE reason=?";
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION[$encid]['ref_id'],PDO::PARAM_STR);
			$STH->bindParam(2,$encid,PDO::PARAM_STR);
			$STH->bindParam(3,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(4,$side,PDO::PARAM_STR);
			$STH->bindParam(5,$message,PDO::PARAM_STR);	
			$STH->bindParam(6,$message,PDO::PARAM_STR);	
			$STH->execute();
					
			$_SESSION[$_GET['id']]['reason']=$message;
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Submitted'));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
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
		file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		$DBH=null;
		return $e->getMessage();
	}
}

function retrive_mime($encname,$mustang){
	$mime_types = array(
		'txt' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		'php' => 'text/html',
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

function CryptFile($InFileName){
	$password='!5s[}du#iwfg8sus6';
	if (file_exists($InFileName)){
		$InFile = file_get_contents($InFileName);
		$StrLen = strlen($InFile);
		for ($i = 0; $i < $StrLen ; $i++){
			$chr = substr($InFile,$i,1);
			$modulus = $i % strlen($password);
			$passwordchr = substr($password,$modulus, 1);
			$OutFile .= chr(ord($chr)+ord($passwordchr));
		}
		$OutFile = base64_encode($OutFile);
		file_put_contents($InFileName,$OutFile);
		return true;
	}
	else
		return false;
}

function DecryptFile($InFileName){
	$password='!5s[}du#iwfg8sus6';
	if (file_exists($InFileName)){
		$InFile = file_get_contents($InFileName);
		$InFile = base64_decode($InFile);
		$StrLen = strlen($InFile);
		for ($i = 0; $i < $StrLen ; $i++){
			$chr = substr($InFile,$i,1);
			$modulus = $i % strlen($password);
			$passwordchr = substr($password,$modulus, 1);
			$OutFile .= chr(ord($chr)-ord($passwordchr));
		}
		file_put_contents($InFileName,$OutFile);
		return true;
	}
	else
		return false;
}

function covert_size($val){if(empty($val))return 0;$val = trim($val);preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);$last = '';if(isset($matches[2]))$last = $matches[2];if(isset($matches[1]))$val = (int) $matches[1];switch (strtolower($last)){case 'g':case 'gb':$val *= 1024;case 'm':case 'mb':$val *= 1024;case 'k':case 'kb':$val *= 1024;}return (int) $val;}
function retrive_ip(){if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];}elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];}else{$ip=$_SERVER['REMOTE_ADDR'];}return $ip;}
function get_random_string($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ0123456789';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}

?>