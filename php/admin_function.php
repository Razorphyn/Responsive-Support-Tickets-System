<?php
/*
User Status
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
if(isset($_COOKIE['RazorphynSupport']) && !is_string($_COOKIE['RazorphynSupport']) || !preg_match('/^[^[:^ascii:];,\s]{22,128}$/',$_COOKIE['RazorphynSupport'])){
	setcookie(session_name(),'invalid',time()-3600);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(0=>'Invalid Session ID, please reload the page'));
	exit();
}
session_start(); 


if(!isset($_SESSION['status'])  || 2!=$_SESSION['status'])
	exit();
else{
	include_once 'config/database.php';
//Session Check
	if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
	if(isset($setting[4])) date_default_timezone_set($setting[4]);

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
			echo '<script>window.location.replace("'.curPageURL().'?e=expired");</script>';
		exit();
	}
	if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
		session_unset();
		session_destroy();
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'sessionerror',2));
		}
		else
			echo '<script>window.location.replace("'.curPageURL().'?e=local");</script>';
		exit();
	}
	if(!isset($_POST[$_SESSION['token']['act']]) && !isset($_POST['act']) && $_POST['act']!='faq_rating' || $_POST['token']!=$_SESSION['token']['faq']){
		session_unset();
		session_destroy();
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'sessionerror',3));
		}
		else
			echo '<script>window.location.replace("'.curPageURL().'?e=token");</script>';
		exit();
	}

	//Functions

	if($_POST[$_SESSION['token']['act']]=='admin_user_add'){
		if(trim(preg_replace('/\s+/','',$_POST['name']))!='' && preg_match('/^[A-Za-z0-9À-ÿ\/\s\'-]+$/',$_POST['name'])) 
			$_POST['name']=trim(preg_replace('/\s+/',' ',$_POST['name']));
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Name: only alphanumeric and single quote allowed'));
			exit();
		}
		$_POST['mail']= trim(preg_replace('/\s+/','',$_POST['mail']));
		$_POST['mail']=($_POST['mail']!='' && filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) ? $_POST['mail']:exit();
		$pass=get_random_string(5);
		$dpass=hash('whirlpool',crypt($pass,'$#%H4!df84a$%#RZ@£'));
		$_POST['role']=(is_numeric($_POST['role']))? $_POST['role']:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				
			$query = "INSERT INTO ".$SupportUserTable." (`name`,`mail`,`password`,`status`,`ip_address`) VALUES (?,?,?,?,?) ";
			$STH = $DBH->prepare($query);
			$ip='127.0.0.1';
			$STH->bindParam(1,$_POST['name'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['mail'],PDO::PARAM_STR);
			$STH->bindParam(3,$dpass,PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['role'],PDO::PARAM_STR);
			$STH->bindParam(5,$ip,PDO::PARAM_STR);
			$STH->execute();
			$uid=$DBH->lastInsertId();
			switch($_POST['role']){
				case 0:
					$_POST['role']='User';
					break;
				case 1:
					$_POST['role']='Operator';
					break;
				case 2:
					$_POST['role']='Administrator';
					break;
				default:
					$_POST['role']='Error';
			}

			$site=curPageURL();
			$headers   = array();
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "Content-type: text/plain; charset=utf-8";
			if(isset($setting[1]))
				$headers[] = "From: ".$setting[1];
			$headers[] = "X-Mailer: PHP/".phpversion();

			$body="Hi,\r\n\r\nan account has been just created at this site: ".$site." \r\nThese are the information:\r\n Name: ".$_POST['name']."\r\n Mail: ".$_POST['mail']." \r\n Password: ".$pass." \r\n\r\nBest Regards, \r\n ".$_SESSION['name']." Site Administrator";
			if(!mail($_POST['mail'],'Account created by Administrator',$body,implode("\r\n", $headers)))
				file_put_contents('mailsendadminerror','Couldn\'t send mail to: '.$_POST['mail']);
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Registred',1=>array('num'=>$uid-54,'name'=>htmlspecialchars($_POST['name'],ENT_QUOTES,'UTF-8'),'mail'=>htmlspecialchars($_POST['mail'],ENT_QUOTES,'UTF-8'),'status'=>$_POST['role'],'holiday'=>'No','rating'=>'Unrated')));
		}
		catch(PDOException $e){
			if ($e->errorInfo[1] == 1062) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>"User with mail: ".htmlspecialchars($_POST['mail'],ENT_QUOTES,'UTF-8')." is already registred"));
			}
			else{
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'We are sorry, but an error has occurred, please contact the administrator if it persist'));
			}
			$DBH=null;
			exit();
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='add_depart'){
		$_POST['tit']=trim(filter_var(preg_replace('/\s+/',' ',$_POST['tit']),FILTER_SANITIZE_STRING));
		if(empty($_POST['tit'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Name: only alphanumeric and single quote allowed'));
			exit();
		}
		$_POST['active']=($_POST['active']==1)? 1:0;
		$_POST['pubdep']=($_POST['pubdep']==1)? 1:0;
		$_POST['freedep']=($_POST['freedep']==1)? 1:0;
		if($_POST['freedep']==0){
			$_POST['ratetype']=($_POST['ratetype']==1)? 1:0;
			$_POST['ratetable']=trim(str_replace(',','.',$_POST['ratetable']));

			preg_match_all('/^(\d+)(:[A-Z0-9-]+(?: [A-Z0-9-]+)*)?:(\d+(?:\.\d{1,2})?)$/mi', $_POST['ratetable'], $out);
			if($out[0]!=explode("\n",$_POST['ratetable'])){
				array_unshift($out[0], "shift");
				$_POST['ratetable']=explode("\n",$_POST['ratetable']);
				array_unshift($_POST['ratetable'], "shift");
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Invalid price table at line: '.implode(", ", array_diff_key(array_flip(explode("\n",$_POST['ratetable'])),array_flip($out[0])))));
				exit();
			}
		}
		else
			$_POST['ratetype']=null;

		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "INSERT INTO ".$SupportDepaTable."(`department_name`,`active`,`public_view`,`free`) VALUES (?,?,?,?)";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['tit'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['active'],PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['pubdep'],PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['freedep'],PDO::PARAM_STR);
			$STH->execute();

			$data=array();
			$data['response']='Added';
			$dpid=$DBH->lastInsertId();
			
			if($_POST['freedep']==0)
				file_put_contents('config/price/'.$dpid,$_POST['ratetype']."\n".$_POST['ratetable']);

			$_POST['active']=($_POST['active']==0) ? 'No':'Yes';
			$_POST['pubdep']=($_POST['pubdep']==0) ? 'No':'Yes';
			$_POST['freedep']=($_POST['freedep']==0) ? 'No':'Yes';
			switch($_POST['ratetype']){
				case 0:
					$_POST['ratetype']='Fixed Minute Quantity';
					break;
				case 1:
					$_POST['ratetype']='Pay per Minute';
					break;
				default:
					$_POST['ratetype']='Unnecessary';
			}
			$data['information']=array('id'=>$dpid,'name'=>htmlspecialchars($_POST['tit'],ENT_QUOTES,'UTF-8'),'active'=>$_POST['active'],'public'=>$_POST['pubdep'],'free'=>$_POST['freedep'],'rule'=>$_POST['ratetype']);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($data);
		}
		catch(PDOException $e){
			if ($e->errorInfo[1] == 1062) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>"Department name: ".htmlspecialchars($_POST['tit'],ENT_QUOTES,'UTF-8')." already exist"));
			}
			else{
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
			}
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='edit_depart'){
		$_POST['id']=(is_numeric($_POST['id'])) ? (int)$_POST['id']:exit();
		$_POST['name']=trim(filter_var(preg_replace('/\s+/',' ',$_POST['name']),FILTER_SANITIZE_STRING));
		if(empty($_POST['name'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Name: only alphanumeric and single quote allowed'));
			exit();
		}
		$_POST['active']=($_POST['active']==1) ? 1:0;
		$_POST['pub']=($_POST['pub']==1) ? 1:0;
		$_POST['freedep']=($_POST['freedep']==1) ? 1:0;
		if($_POST['freedep']==0){
			$_POST['ratetype']=($_POST['ratetype']==1)? 1:0;
			$_POST['ratetable']=trim(str_replace(',','.',$_POST['ratetable']));
			preg_match_all('/^(\d+)(:[A-Z0-9-]+(?: [A-Z0-9-]+)*)?:(\d+(?:\.\d{1,2})?)$/mi', $_POST['ratetable'], $out);
			if($out[0]!=explode("\n",$_POST['ratetable'])){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Invalid price table at line: '.implode(", ", array_diff_key(array_flip(explode("\n",$_POST['ratetable'])),array_flip($out[0])))));
				exit();
			}
		}
		else
			$_POST['ratetype']=null;

		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "UPDATE ".$SupportDepaTable." SET `department_name`=?,`active`=?,`public_view`=?,`free`=? WHERE id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['name'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['active'],PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['pub'],PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['freedep'],PDO::PARAM_STR);
			$STH->bindParam(5,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();

			if($_POST['freedep']==0)
				file_put_contents('config/price/'.$_POST['id'],$_POST['ratetype']."\n".$_POST['ratetable']);

			$_POST['active']=($_POST['active']==0) ? 'No':'Yes';
			$_POST['pub']=($_POST['pub']==0) ? 'No':'Yes';
			$_POST['freedep']=($_POST['freedep']==0) ? 'No':'Yes';
			switch($_POST['ratetype']){
				case 0:
					$_POST['ratetype']='Fixed Minute Quantity';
					break;
				case 1:
					$_POST['ratetype']='Pay per Minute';
					break;
				default:
					$_POST['ratetype']='Unnecessary';
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Succeed',1=>array('id'=>$_POST['id'],'name'=>htmlspecialchars($_POST['name'],ENT_QUOTES,'UTF-8'),'active'=>$_POST['active'],'public'=>$_POST['pub'],'free'=>$_POST['freedep'],'rule'=>$_POST['ratetype'])));
		}
		catch(PDOException $e){
			if ($e->errorInfo[1] == 1062) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>"Department name: ".json_encode($_POST['name'])." already exist"));
			}
			else{
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
			}
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='del_dep'){
		$_POST['sub']=(trim(preg_replace('/\s+/','',$_POST['sub']))!='')? trim(preg_replace('/\s+/',' ',$_POST['sub'])):exit();
		$_POST['id']=(is_numeric($_POST['id']))? (int)$_POST['id']:exit();
		
		$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		if($_POST['sub']=='del_name'){
			try{
				$sedquery="DELETE FROM ".$SupportUserPerDepaTable." WHERE `department_id`=?;";
				$delquery="DELETE FROM ".$SupportDepaTable." WHERE `id`= ?  ;";
				
				$STH = $DBH->prepare($sedquery);
				$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();
				
				$STH = $DBH->prepare($delquery);
				$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();
				
				if(is_file('config/price/'.$_POST['id'])) unlink('config/price/'.$_POST['id']);

				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Deleted'));
			}
			catch(PDOException $e){  
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
			}
		}
		else if($_POST['sub']=='del_every'){
			$sedquery="DELETE FROM ".$SupportUserPerDepaTable." WHERE `department_id`=?";
			$delquery="DELETE FROM ".$SupportDepaTable." WHERE `id`= ?";
			$seltk="SELECT id FROM ".$SupportTicketsTable." WHERE `department_id`= ?";
			$deltk="DELETE FROM ".$SupportTicketsTable." WHERE `department_id`= ?";
			try{
				$STH = $DBH->prepare($sedquery);
				$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();
			
				$STH = $DBH->prepare($delquery);
				$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();
				
				$STH = $DBH->prepare($seltk);
				$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();
				
				$STH->setFetchMode(PDO::FETCH_ASSOC);
				$a = $STH->fetch();
				if(!empty($a)){
					$list=array();
					do{
						$list[]=$a['id'];
					}
					while ($a = $STH->fetch());
					
					$list=implode(',',$list);
					
					$STH = $DBH->prepare($deltk);
					$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
					$STH->execute();
					
					$delmsg="DELETE FROM ".$SupportMessagesTable." WHERE `ticket_id` IN (".$list.")";
					$STH = $DBH->prepare($delmsg);
					$STH->execute();
					
					$selupl="SELECT enc FROM ".$SupportUploadTable." WHERE `tk_id` IN (".$list.")";
					$STH = $DBH->prepare($selupl);
					$STH->execute();
					
					$STH->setFetchMode(PDO::FETCH_ASSOC);
					$a = $STH->fetch();
					if(!empty($a)){
						$path='../upload/';
						do{
							file_put_contents($path.$a['enc'],'');
							unlink($path.$a['enc']);
						}while ($a = $STH->fetch());
						
						$delup="DELETE FROM ".$SupportUploadTable." WHERE `tk_id` IN (".$list.")";
						$STH = $DBH->prepare($delup);
						$STH->execute();

						if(is_file('config/price/'.$_POST['id'])) unlink('config/price/'.$_POST['id']);
						header('Content-Type: application/json; charset=utf-8');
						echo json_encode(array(0=>'Deleted'));
					}
					else{
						if(is_file('config/price/'.$_POST['id'])) unlink('config/price/'.$_POST['id']);
						header('Content-Type: application/json; charset=utf-8');
						echo json_encode(array(0=>'Deleted'));
					}
				}
				else{
					if(is_file('config/price/'.$_POST['id'])) unlink('config/price/'.$_POST['id']);
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode(array(0=>'Deleted'));
				}
			}
			catch(PDOException $e){  
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
			}
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Cannot select sub process'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='save_options'){

		$_POST['senrep']=(is_numeric($_POST['senrep']) && $_POST['senrep']==1) ? 1:0;
		$_POST['senope']=(is_numeric($_POST['senope']) && $_POST['senope']==1) ? 1:0;
		$_POST['upload']=(is_numeric($_POST['upload']) && $_POST['upload']==1) ? 1:0;
		$_POST['check_extension']=(is_numeric($_POST['check_extension']) && $_POST['check_extension']==1) ? 1:0;
		$_POST['faq']=(is_numeric($_POST['faq'])  && $_POST['enrat']==1) ? (int)$_POST['faq']:exit();
		$_POST['maxsize']=(is_numeric($_POST['maxsize'])) ? ($_POST['maxsize']*1048576 ):null;
		$_POST['enrat']=(is_numeric($_POST['enrat']) && $_POST['enrat']==1) ? 1:0;
		$_POST['commlop']=(trim(preg_replace('/\s+/',' ',$_POST['commlop']))=='php -f')? 'php -f':'php5-cli';
		$_POST['tit']=trim(filter_var(preg_replace('/\s+/',' ',$_POST['tit']),FILTER_SANITIZE_STRING));
		
		$_POST['allowed_exentions']=trim(str_replace('.','',str_replace(' ','',$_POST['allowed_exentions'])));
		if(!empty($_POST['allowed_exentions'])){
			preg_match_all('/^([a-zA-Z0-9+-]*)$/mi', $_POST['allowed_exentions'], $out);
			if(count($out[0])!=count(explode("\n",$_POST['allowed_exentions']))){
				array_unshift($out[0], "shift");
				$_POST['allowed_exentions']=explode("\n",$_POST['allowed_exentions']);
				array_unshift($_POST['allowed_exentions'], "shift");
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Invalid extension at line: '.implode(", ", array_diff_key(array_flip($_POST['allowed_exentions']),array_flip($out[0]))).'. Only letters, numbers, "+" and "-" are allowed'));
				exit();
			}
		}

		if(empty($_POST['tit'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Title'));
			exit();
		}
		$_POST['mail']= trim(preg_replace('/\s+/','',$_POST['mail']));
		if(empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Mail'));
			exit();
		}
		$_POST['error_mail']= trim(preg_replace('/\s+/','',$_POST['error_mail']));
		if(empty($_POST['error_mail']) || !filter_var($_POST['error_mail'], FILTER_VALIDATE_EMAIL)){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid "Error Mail"'));
			exit();
		}
		if(file_put_contents('config/allowedext.txt',$_POST['check_extension']."\n".$_POST['allowed_exentions']) && file_put_contents('config/setting.txt',$_POST['tit']."\n".$_POST['mail']."\n".$_POST['senrep']."\n".$_POST['senope']."\n".$_POST['timezone']."\n".$_POST['upload']."\n".$_POST['maxsize']."\n".$_POST['enrat']."\n".$_POST['commlop']."\n".$_POST['faq']."\n".$_POST['error_mail'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Saved'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Error'));
		}
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='save_payment'){
		if($_POST['gate']=='paypal'){
			$currency=array("AUD",
						"BRL",
						"CAD",
						"CZK",
						"DKK",
						"EUR",
						"HKD",
						"HUF",
						"ILS",
						"JPY",
						"MYR",
						"MXN",
						"NOK",
						"NZD",
						"PHP",
						"PLN",
						"GBP",
						"SGD",
						"SEK",
						"CHF",
						"TWD",
						"THB",
						"TRY",
						"USD"
					);
			$_POST['en']=(is_numeric($_POST['en'])) ? (($_POST['en']==1)? 1:0):exit();
			$_POST['ensand']=(is_numeric($_POST['ensand'])) ? (($_POST['ensand']==1)? 1:0):exit();
			$_POST['encurl']=(is_numeric($_POST['encurl'])) ? (($_POST['encurl']==1)? 1:0):exit();
			$_POST['currency']=trim(strtoupper($_POST['currency']));
			if(!in_array($_POST['currency'], $currency) || strlen($_POST['currency'])!=3){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Invalid Currency Code'));
				exit();
			}

			$_POST['mail']= trim(preg_replace('/\s+/','',$_POST['mail']));
			if(empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Invalid Mail'));
				exit();
			}
			$info=array($_POST['en'],$_POST['mail'],$_POST['currency'],$_POST['ensand'],$_POST['encurl']);
			$file='paypal.txt';
		}
		else if($_POST['gate']=='moneybookers'){
			$currency=array("USD",
							"CZK",
							"EUR",
							"GBP",
							"HKD",
							"THB",
							"TWD"
						);
			$_POST['en']=(is_numeric($_POST['en'])) ? (($_POST['en']==1)? 1:0):exit();
			$_POST['mer_id']=(is_numeric($_POST['mer_id']) && !empty($_POST['mer_id'])) ? $_POST['mer_id']:exit();
			$_POST['currency']=trim(strtoupper($_POST['currency']));
			if(!in_array($_POST['currency'], $currency) || strlen($_POST['currency'])!=3){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Invalid Currency Code'));
				exit();
			}

			$_POST['mail']= trim(preg_replace('/\s+/','',$_POST['mail']));
			if(empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Invalid Mail'));
				exit();
			}
			$info=array($_POST['en'],$_POST['mer_id'],$_POST['mail'],$_POST['currency'],$_POST['compname'],$_POST['encurl'],$_POST['mbsword']);
			$file='paypal.txt';
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Wrong gateway Name'));
			exit();
		}
		if(file_put_contents('config/payment/'.$file,implode("\n",$info))){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Saved'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Cannot write on file, please check the path'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='save_stmp'){
		if(is_file('config/mail/stmp.txt')){
			file_put_contents('config/mail/stmp.txt','');
			unlink('config/mail/stmp.txt');
		}
		$_POST['serv']=(is_numeric($_POST['serv'])) ? (int)$_POST['serv']:exit();
		$_POST['name']=trim(filter_var(preg_replace('/\s+/',' ',$_POST['name']),FILTER_SANITIZE_STRING));
		if(empty($_POST['name'])){
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
		$_POST['host']=(trim(preg_replace('/\s+/','',$_POST['host']))!='')? trim(preg_replace('/\s+/','',$_POST['host'])):exit();
		$_POST['port']=(is_numeric(filter_var($_POST['port'], FILTER_SANITIZE_NUMBER_INT))) ? filter_var($_POST['port'], FILTER_SANITIZE_NUMBER_INT):exit();
		$_POST['ssl']=(is_numeric($_POST['ssl'])) ? $_POST['ssl']:exit();
		$_POST['auth']=(is_numeric($_POST['auth'])) ? $_POST['auth']:exit();
		
		$_POST['usr']=(string)$_POST['usr'];
		$_POST['pass']=(string)$_POST['pass'];
		if(!empty($_POST['pass'])){
			include_once ('endecrypt.php');
			$key=uniqid('',true);
			$e = new Encryption(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
			$_POST['pass'] = $e->encrypt($_POST['pass'], $key);
		}
		$string='<?php $smailservice='.$_POST['serv'].";\n".'$smailname=\''.$_POST['name']."';\n".'$settingmail=\''.$_POST['mail']."';\n".'$smailhost=\''.$_POST['host']."';\n".'$smailport='.$_POST['port'].";\n".'$smailssl='.$_POST['ssl'].";\n".'$smailauth='.$_POST['auth'].";\n".'$smailuser=\''.$_POST['usr']."';\n".'$smailpassword=\''.$_POST['pass']."';\n".'$smailenckey=\''.$key."';\n ?>";
		if(file_put_contents('config/mail/stmp.php',$string)){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Saved'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Error'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='save_mail_body'){
		$_POST['sub']=(trim(preg_replace('/\s+/','',$_POST['sub']))!='')? trim(preg_replace('/\s+/',' ',$_POST['sub'])):exit();
		if(trim(preg_replace('/\s+/','',$_POST['message']))!=''){
			$_POST['message']=trim(preg_replace('/\s+/',' ',$_POST['message']));
			require_once 'htmlpurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$purifier = new HTMLPurifier($config);
			$_POST['message'] = $purifier->purify($_POST['message']);
			$check=trim(strip_tags($_POST['message']));
			if(empty($check)){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Empty Message'));
				exit();
			}
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Empty Message'));
			exit();
		}
		$act=(is_numeric($_POST['sec']))? $_POST['sec']:exit();
		if($act==0 && file_put_contents('config/mail/newuser.txt',$_POST['sub']."\n".$_POST['message']))
			$saved=true;
		else if($act==1 && file_put_contents('config/mail/newreply.txt',$_POST['sub']."\n".$_POST['message']))
			$saved=true;
		else if($act==2 && file_put_contents('config/mail/newticket.txt',$_POST['sub']."\n".$_POST['message']))
			$saved=true;
		else if($act==3 && file_put_contents('config/mail/assigned.txt',$_POST['sub']."\n".$_POST['message']))
			$saved=true;
		else if($act==4 && file_put_contents('config/mail/forgotten.txt',$_POST['sub']."\n".$_POST['message']))
			$saved=true;
		else
			$saved=false;

		if(isset($saved) && $saved==true){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Saved'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Error'));
		}
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='save_privacy'){
		$_POST['en']=($_POST['en']==0) ? 0:1;
		if(trim(preg_replace('/\s+/','',$_POST['text']))!=''){
			$_POST['text']=trim(preg_replace('/\s+/',' ',$_POST['text']));
			require_once 'htmlpurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$purifier = new HTMLPurifier($config);
			$_POST['text'] = $purifier->purify($_POST['text']);
			$check=trim(strip_tags($_POST['text']));
			if(empty($check)){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Empty Message'));
				exit();
			}
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Empty Message'));
			exit();
		}
		
		if(file_put_contents('config/privacy.txt',$_POST['en']."\n".$_POST['text'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Saved'));
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Error'));
		}
		exit();
	}
	
	else if(isset($_POST['upload_logo'])  && isset($_FILES['new_logo'])){
		$target_path = "../css/logo/".$_FILES['new_logo']['name'];
		if($_FILES['new_logo']['type']=='image/gif' || $_FILES['new_logo']['type']=='image/jpeg' || $_FILES['new_logo']['type']=='image/png' || $_FILES['new_logo']['type']=='image/pjpeg'){
				if(move_uploaded_file($_FILES['new_logo']['tmp_name'], $target_path)) {
					$dir=(dirname(dirname($_SERVER['REQUEST_URI']))!=trim('\ ')) ? dirname(dirname($_SERVER['REQUEST_URI'])):'';
					$image='//'.$_SERVER['SERVER_NAME'].$dir.'/css/logo/'.$_FILES['new_logo']['name'];
					file_put_contents('config/logo.txt',$image);
					echo '<script>parent.$("#cur_logo").attr("src","'.$image.'");</script>';
				}
				else
					echo "<script>parent.noty({text: 'Error during moving',type:'error',timeout:9E3});</script>";
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='update_user_info'){
		$_POST['id']=(is_numeric($_POST['id'])) ? ((int)$_POST['id']+54):exit();
		$_POST['name']=trim(filter_var(preg_replace('/\s+/',' ',$_POST['name']),FILTER_SANITIZE_STRING));
		if(empty($_POST['name'])){
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
		$_POST['status']=(is_numeric($_POST['status'])) ? (string)$_POST['status']:exit();
		$_POST['holiday']=(is_numeric($_POST['holiday'])) ? (string)$_POST['holiday']:exit();
		
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
			$query = "UPDATE ".$SupportUserTable." SET name=?,mail=?,status=?,holiday=?  WHERE id=? LIMIT 1";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['name'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['mail'],PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['status'],PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['holiday'],PDO::PARAM_STR);
			$STH->bindParam(5,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			
			$query = "DELETE FROM ".$SupportUserPerDepaTable." WHERE user_id=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			
			if($_POST['status']=='1' && count($_POST['seldepa'])>0){
				$_POST['seldepa']=array_filter($_POST['seldepa'],'is_numeric');

				$query = "INSERT INTO ".$SupportUserPerDepaTable." (`department_name`, `department_id` , `user_id`) VALUES ";
				$count=count($_POST['seldepa']);
				$a=array();
				for($i=0;$i<$count;$i++){
					if ($i!=$count-1)
						$query.='((SELECT `department_name` FROM '.$SupportDepaTable.' WHERE id=?),?,?),';
					else
						$query.='((SELECT `department_name` FROM '.$SupportDepaTable.' WHERE id=?),?,?)';
					$a[$i]=array($_POST['seldepa'][$i],$_POST['id']);
				}
				$STH = $DBH->prepare($query);
				$count=count($a);
				for($i=0;$i<$count;$i++){
					$STH->bindParam(($i*3+1),$a[$i][0],PDO::PARAM_INT);
					$STH->bindParam(($i*3+2),$a[$i][0],PDO::PARAM_INT);
					$STH->bindParam(($i*3+3),$a[$i][1],PDO::PARAM_INT);
				}
				$STH->execute();
				$camarolist=join(',',$_POST['seldepa']);

				$query="SELECT id,department_id,user_id FROM ".$SupportTicketsTable." WHERE ticket_status='1' AND operator_id=? AND department_id NOT IN (".$camarolist.")";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();
				
				$STH->setFetchMode(PDO::FETCH_ASSOC);
				$a = $STH->fetch();
				if(!empty($a)){
					$tktoedit=array();
					do{
						$tktoedit[]=array($a['id'],$a['department_id'],$a['user_id']);
					}while ($a = $STH->fetch());
					
					$query = "UPDATE ".$SupportTicketsTable." SET operator_id=0,ticket_status= CASE WHEN ticket_status='1' THEN '2' ELSE ticket_status END  WHERE department_id NOT IN (".$camarolist.")";
					$STH = $DBH->prepare($query);
					$STH->execute();
					$sub=$STH->rowCount();
					
					$query = "UPDATE ".$SupportUserTable." SET assigned_tickets=(assigned_tickets-?)  WHERE id=?";
					$STH = $DBH->prepare($query);
					$STH->bindParam(1,$sub,PDO::PARAM_INT);
					$STH->bindParam(2,$_POST['id'],PDO::PARAM_INT);
					$STH->execute();
					
					foreach($tktoedit as $k=>$v){
						$selopid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $v[1],$v[2]);
						if(is_numeric($selopid)){
							$fquery="UPDATE ".$SupportTicketsTable." a 
										SET a.operator_id=IF(a.user_id=?,0,?),
											a.ticket_status= CASE WHEN a.ticket_status='2' AND a.user_id!=? THEN '1' WHEN a.user_id=? THEN '2' ELSE a.ticket_status END  
										WHERE a.id=?";
							$lquery="UPDATE ".$SupportUserTable." b 
										SET b.assigned_tickets=IF((SELECT COUNT(*) FROM ".$SupportTicketsTable." WHERE operator_id=? LIMIT 1) IS NOT NULL,(SELECT COUNT(*) FROM ".$SupportTicketsTable." WHERE operator_id=?),0) 
										WHERE b.id=?";
							
							$STH = $DBH->prepare($fquery);
							$STH->bindParam(1,$selopid,PDO::PARAM_INT);
							$STH->bindParam(2,$selopid,PDO::PARAM_INT);
							$STH->bindParam(3,$selopid,PDO::PARAM_INT);
							$STH->bindParam(4,$selopid,PDO::PARAM_INT);
							$STH->bindParam(5,$v[0],PDO::PARAM_INT);
							$STH->execute();

							$STH = $DBH->prepare($lquery);
							$STH->bindParam(1,$selopid,PDO::PARAM_INT);
							$STH->bindParam(2,$selopid,PDO::PARAM_INT);
							$STH->bindParam(3,$selopid,PDO::PARAM_INT);
							$STH->execute();
						}
					}
					$_POST['holiday']=($_POST['holiday']==1)? 'Yes':'No';
					switch($_POST['status']){
						case 0:
							$_POST['status']='User';
							break;
						case 1:
							$_POST['status']='Operator';
							break;
						case 2:
							$_POST['status']='Administrator';
							break;
						case 3:
							$_POST['status']='Activation';
							break;
						case 4:
							$_POST['status']='Banned';
							break;
						default:
							$_POST['status']='Error';
					}
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode(array(0=>'Updated',1=>array('num'=>($_POST['id']-54),'name'=>htmlspecialchars($_POST['name'],ENT_QUOTES,'UTF-8'),'mail'=>$_POST['mail'],'status'=>$_POST['status'],'holiday'=>$_POST['holiday'])));
				}
				else{
					
					$_POST['holiday']=($_POST['holiday']==1)? 'Yes':'No';
					switch($_POST['status']){
						case 0:
							$_POST['status']='User';
							break;
						case 1:
							$_POST['status']='Operator';
							break;
						case 2:
							$_POST['status']='Administrator';
							break;
						case 3:
							$_POST['status']='Activation';
							break;
						case 4:
							$_POST['status']='Banned';
							break;
						default:
							$_POST['status']='Error';
					}
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode(array(0=>'Updated',1=>array('num'=>($_POST['id']-54),'name'=>htmlspecialchars($_POST['name'],ENT_QUOTES,'UTF-8'),'mail'=>$_POST['mail'],'status'=>$_POST['status'],'holiday'=>$_POST['holiday'])));
				}
			}
			else if($_POST['status']!=1 && $_POST['status']!=2){
				$query = "UPDATE ".$SupportTicketsTable." SET operator_id=0,ticket_status= CASE WHEN ticket_status='1' THEN '2' ELSE ticket_status END  WHERE operator_id=?";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();

				$_POST['holiday']=($_POST['holiday']==1)? 'Yes':'No';
				switch($_POST['status']){
					case 0:
						$_POST['status']='User';
						break;
					case 1:
						$_POST['status']='Operator';
						break;
					case 2:
						$_POST['status']='Administrator';
						break;
					case 3:
						$_POST['status']='Activation';
						break;
					case 4:
						$_POST['status']='Banned';
						break;
					default:
						$_POST['status']='Error';
				}
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Updated',1=>array('num'=>($_POST['id']-54),'name'=>htmlspecialchars($_POST['name'],ENT_QUOTES,'UTF-8'),'mail'=>$_POST['mail'],'status'=>$_POST['status'],'holiday'=>$_POST['holiday'])));
			}
			else{
				$_POST['holiday']=($_POST['holiday']==1)? 'Yes':'No';
				switch($_POST['status']){
					case 0:
						$_POST['status']='User';
						break;
					case 1:
						$_POST['status']='Operator';
						break;
					case 2:
						$_POST['status']='Administrator';
						break;
					case 3:
						$_POST['status']='Activation';
						break;
					case 4:
						$_POST['status']='Banned';
						break;
					default:
						$_POST['status']='Error';
				}
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Updated',1=>array('num'=>($_POST['id']-54),'name'=>htmlspecialchars($_POST['name'],ENT_QUOTES,'UTF-8'),'mail'=>$_POST['mail'],'status'=>$_POST['status'],'holiday'=>$_POST['holiday'])));
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='select_depa_usr'){
		$_POST['id']=(is_numeric($_POST['id'])) ? ((int)$_POST['id']+54):exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT `department_id` FROM ".$SupportUserPerDepaTable." WHERE `user_id`=? ORDER BY `department_name` ASC";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$ret=array('res'=>'ok','depa'=>array(0=>'<div class="user_depa_container">'));
			$camaros=array();
			while ($a = $STH->fetch()){
				$camaros[$a['department_id']]=$a['department_id'];
			}
			$b=json_decode(retrive_depa_names($Hostname, $Username, $Password, $DatabaseName, $SupportDepaTable));
			if($b!=false){
				foreach($b as $k=>$n){
					if(array_key_exists($k,$camaros))
						$ret['depa'][]='<label class="checkbox inline"><input type="checkbox" name="ass_usr_depa" value="'.$k.'" checked />'.$n.'</label>';
					else
						$ret['depa'][]='<label class="checkbox inline"><input type="checkbox" name="ass_usr_depa" value="'.$k.'" />'.$n.'</label>';
				}
			}
			$ret['depa'][]='</div>';
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($ret);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='select_usr_rate'){
		$_POST['id']=(is_numeric($_POST['id'])) ? ((int)$_POST['id']+54):exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT 
							b.rate,
							b.note,
							c.mail
						FROM ".$SupportTicketsTable." a
						LEFT JOIN ".$SupportRateTable." b
							ON b.ref_id=a.ref_id
						LEFT JOIN ".$SupportUserTable." c
							ON c.id=b.usr_id
						WHERE a.operator_id=? ORDER BY b.id ASC LIMIT 700";

			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();

			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			$ret=array('res'=>'ok','rate'=>array());
			if(!empty($a)){
				while ($a = $STH->fetch()){
					$ret['rate'][]=array($a['rate'],$a['note'],htmlspecialchars($a['mail'],ENT_QUOTES,'UTF-8'));
				}
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($ret);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='del_usr'){//check
		$_POST['id']=(is_numeric($_POST['id']))? (int)$_POST['id']+54:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT enc FROM ".$SupportUploadTable." WHERE `uploader`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
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
			
			$query = "	DELETE FROM ".$SupportMessagesTable." WHERE user_id=?;
						DELETE FROM ".$SupportTicketsTable." WHERE user_id=?;
						DELETE FROM ".$SupportUploadTable." WHERE uploader=?;
						DELETE FROM ".$SupportUserPerDepaTable." WHERE user_id=?;
						DELETE FROM ".$SupportUserTable." WHERE id=?;
					";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->bindParam(2,$_POST['id'],PDO::PARAM_INT);
			$STH->bindParam(3,$_POST['id'],PDO::PARAM_INT);
			$STH->bindParam(4,$_POST['id'],PDO::PARAM_INT);
			$STH->bindParam(5,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
															
			$query = "UPDATE ".$SupportTicketsTable." SET operator_id=0,ticket_status= CASE WHEN '1' THEN '2' ELSE ticket_status END  WHERE operator_id=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Deleted'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='automatic_assign_ticket'){
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query="SELECT id,department_id,user_id FROM ".$SupportTicketsTable." WHERE ticket_status='2'";
			$STH = $DBH->prepare($query);
			$STH->execute();
			
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a=$STH->fetch();
			if(!empty($a)){
				$tktoedit=array();
				do{
					$tktoedit[]=array($a['id'],$a['department_id'],$a['user_id']);
				}while($a=$STH->fetch());

				foreach($tktoedit as $k=>$v){
					$selopid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $v[1], $v[2]);
					if(is_numeric($selopid)){
						$query = "UPDATE 
										".$SupportTicketsTable." a ,
										".$SupportUserTable." b 
									SET 
										b.assigned_tickets=(b.assigned_tickets+1) ,
										a.operator_id=?,
										a.ticket_status= CASE WHEN a.ticket_status='2' THEN '1' ELSE a.ticket_status END  
									WHERE a.id=? AND b.id=?";
						$STH = $DBH->prepare($query);
						$STH->bindParam(1,$selopid,PDO::PARAM_INT);
						$STH->bindParam(2,$v[0],PDO::PARAM_INT);
						$STH->bindParam(3,$selopid,PDO::PARAM_INT);
						$STH->execute();
					}
				}
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Assigned'));
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'No Ticket to Assign'));
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='retrive_operator_assign'){
		$_POST['enc']=trim(preg_replace('/\s+/','',$_POST['enc']));
		if(!preg_match('/^[0-9]{1,15}$/',$_POST['enc'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid ID'));
			exit();
		}
		$_POST['id']=(is_numeric($_POST['id'])) ? $_POST['id']:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query="SELECT
							id,
							name,
							status 
						FROM (	
								(
									SELECT 
										a.id,
										a.name,
										a.status  
									FROM ".$SupportUserTable." a 
									WHERE  a.status='2' AND a.id!=? AND a.id!=?
								) 
								UNION (
									SELECT a.id,
											a.name,
											a.status  
									FROM  ".$SupportUserTable." a
									LEFT JOIN  ".$SupportUserPerDepaTable." b ON a.id=b.user_id  
									WHERE b.department_id=? AND a.id!=?
								)
							) AS tab 
					ORDER BY tab.status ASC, tab.name ASC";

			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_SESSION['id'],PDO::PARAM_INT);
			$STH->bindParam(2,$_SESSION['tickets'][$_POST['enc']]['op_id'],PDO::PARAM_INT);
			$STH->bindParam(3,$_POST['id'],PDO::PARAM_INT);
			$STH->bindParam(4,$_SESSION['id'],PDO::PARAM_INT);
			$STH->execute();
			
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$list=array(0=>'Ex',1=>'<option value="0">---</option>');
				do{
					$list[]='<option value="'.$a['id'].'">'.htmlspecialchars($a['name'],ENT_QUOTES,'UTF-8').'</option>';
				}while ($a = $STH->fetch());
				
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode($list);
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Unavailable'));
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='move_admin_ticket'){
		$_POST['opid']=(is_numeric($_POST['opid'])) ? $_POST['opid']:exit();
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
		
			if($_POST['opid']==-1){
				$f=true;
				$_POST['opid']=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $_POST['dpid'],$_SESSION['tickets'][$_POST['id']]['usr_id']);
				if(!is_numeric($_POST['opid']))
					$_POST['opid']=0;
			}
			
			if($oldop!=$opid){
				$query="UPDATE ".$SupportTicketsTable." a
								SET
									a.department_id=?,
									a.ticket_status= CASE WHEN ?=0 AND a.ticket_status!=0 THEN 2 WHEN ?!=0 AND a.ticket_status='2' THEN '1' ELSE a.ticket_status END,
									a.operator_id=?
								WHERE a.id=? LIMIT 1";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_POST['dpid'],PDO::PARAM_INT);
				$STH->bindParam(2,$_POST['opid'],PDO::PARAM_INT);
				$STH->bindParam(3,$_POST['opid'],PDO::PARAM_INT);
				$STH->bindParam(4,$_POST['opid'],PDO::PARAM_INT);
				$STH->bindParam(5,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();

				$query="UPDATE ".$SupportUserTable." b
								SET
									b.assigned_tickets=b.assigned_tickets-1
								WHERE b.id=? LIMIT 1";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$oldop,PDO::PARAM_INT);
				$STH->execute();

				if($_POST['opid']!=0){
					$query="UPDATE ".$SupportUserTable." c
									SET
										c.assigned_tickets=c.assigned_tickets+1
									WHERE c.id=? LIMIT 1";
					$STH = $DBH->prepare($query);
					$STH->bindParam(1,$_POST['opid'],PDO::PARAM_INT);
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
		
			if($_POST['opid']>0 || !isset($f)){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'AMoved'));
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'No Operator Available'));
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if($_POST[$_SESSION['token']['act']]=='delete_files'){
		$_POST['from']=(trim(preg_replace('/\s+/','',$_POST['from']))!='')? trim(preg_replace('/\s+/','',$_POST['from']))." 00:00:00":exit();
		$_POST['to']=(trim(preg_replace('/\s+/','',$_POST['to']))!='')? trim(preg_replace('/\s+/','',$_POST['to']))." 23:59:59":exit();
		
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query = "SELECT enc,message_id FROM ".$SupportUploadTable." WHERE `upload_date` BETWEEN ? AND ? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['from'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['to'],PDO::PARAM_STR);
			$STH->execute();

			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$path='../upload/';
				$list=array();
				do{
					if(file_exists($path.$a['enc'])){
						file_put_contents($path.$a['enc'],'');
						unlink($path.$a['enc']);
						$list[]=$a['message_id'];
					}
				}while ($a = $STH->fetch());
				$total=count($list);
				$query = "DELETE FROM ".$SupportUploadTable." WHERE `upload_date` BETWEEN ? AND ?";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_POST['from'],PDO::PARAM_STR);
				$STH->bindParam(2,$_POST['to'],PDO::PARAM_STR);
				$STH->execute();
				
				$c=count($list);
				$list=implode(',',$list);

				$query = "UPDATE ".$SupportMessagesTable." SET attachment='0' WHERE id IN (".$list.") LIMIT ?";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$c,PDO::PARAM_INT);
				$STH->execute();

				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Deleted',1=>$total));
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'There is no Uploaded Files inside this period'));
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='delete_tickets_period'){
		$_POST['from']=(trim(preg_replace('/\s+/','',$_POST['from']))!='')? trim(preg_replace('/\s+/','',$_POST['from']))." 00:00:00":exit();
		$_POST['to']=(trim(preg_replace('/\s+/','',$_POST['to']))!='')? trim(preg_replace('/\s+/','',$_POST['to']))." 23:59:59":exit();
		if(!array_filter($_POST['stat'], 'is_numeric')){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Delete by'));
			exit();
		}
		$_POST['stat']=array_unique($_POST['stat']);
		$_POST['by']=($_POST['by']==0)? 0:1;
		$c=count($_POST['stat']);
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			if($_POST['by']==0){
				$query = "SELECT id,operator_id,ticket_status FROM ".$SupportTicketsTable." WHERE `created_time` BETWEEN ? AND ?";
				if($c<3){
					for($i=0;$i<$c;$i++){
						if($i=0)
							$query.="AND ticket_status=? ";
						else
							$query.="OR ticket_status=? ";
					}
				}
			}
			else{
				$query = "SELECT id,operator_id,ticket_status FROM ".$SupportTicketsTable." WHERE `last_reply` BETWEEN ? AND ?";
				if($c<3){
					for($i=0;$i<$c;$i++){
						if($i=0)
							$query.="AND ticket_status=? ";
						else
							$query.="OR ticket_status=? ";
					}
				}
			}
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['from'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['to'],PDO::PARAM_STR);
			if($c<3){
				for($i=0;$i<$c;$i++){
					$STH->bindParam($i+3,$_POST['stat'][$i],PDO::PARAM_STR);
				}
			}
			$STH->execute();

			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$ids=array();
				$op=array();
				do{
					$ids[]=$a['id'];
					if($a['ticket_status']==1){
						if(isset($op[$a['operator_id']]))
							$op[$a['operator_id']]=$op[$a['operator_id']]++;
						else
							$op[$a['operator_id']]=1;
					}
				}while ($a = $STH->fetch());
				
				$total=count($ids);
				$list=implode(',',$ids);
				$query = "SELECT id FROM ".$SupportMessagesTable." WHERE ticket_id IN (".$list.") AND attachment='1' ";
				$STH = $DBH->prepare($query);
				$STH->execute();

				$STH->setFetchMode(PDO::FETCH_ASSOC);
				$a = $STH->fetch();
				if(!empty($a)){
					unset($ids);
					$ids=array();
					do{
						$ids[]=$a['id'];
					}while ($a = $STH->fetch());
					$ids=implode(',',$ids);
					
					$query = "SELECT enc FROM ".$SupportUploadTable." WHERE message_id IN (".$ids.")";
					$STH = $DBH->prepare($query);
					$STH->execute();
					
					$path='../upload/';
					$a = $STH->fetch();
					if(!empty($a)){
						do{
							if(is_file($path.$a['enc'])){
								file_put_contents($path.$a['enc'],'');
								unlink($path.$a['enc']);
							}
						}while ($a = $STH->fetch());

						$query = "DELETE FROM ".$SupportUploadTable." WHERE `message_id` IN (".$ids.")";
						$STH = $DBH->prepare($query);
						$STH->execute();
					}
				}
				
				$query = "DELETE FROM ".$SupportMessagesTable." WHERE `ticket_id` IN (".$list.")";
				$STH = $DBH->prepare($query);
				$STH->execute();

				$query = "DELETE FROM ".$SupportTicketsTable." WHERE `id` IN (".$list.")";
				$STH = $DBH->prepare($query);
				$STH->execute();
				if(count($op)>0){
					foreach($op as $k=>$v){
						$query="UPDATE ".$SupportUserTable." b
								SET b.assigned_tickets= CASE  WHEN b.assigned_tickets!='0' AND b.assigned_tickets-?>=0 THEN (b.assigned_tickets-?) ELSE '0' END  
								WHERE b.id=?";
						$STH = $DBH->prepare($query);
						$STH->bindParam(1,$v,PDO::PARAM_INT);
						$STH->bindParam(2,$v,PDO::PARAM_INT);
						$STH->bindParam(3,$k,PDO::PARAM_INT);
						$STH->execute();
					}
				}

				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Deleted',1=>$total));
				exit();
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>"There aren't tickets inside this period"));
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='add_faq'){
	
		$_POST['question']=(trim(preg_replace('/\s+/','',$_POST['question']))!='')? trim(preg_replace('/\s+/',' ',$_POST['question'])):exit();

		$_POST['answer']=trim(preg_replace('/\s+/',' ',$_POST['answer']));
		if(trim(preg_replace('/\s+/','',$_POST['answer']))!=''){
			require_once 'htmlpurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$purifier = new HTMLPurifier($config);
			$_POST['answer'] = $purifier->purify($_POST['answer']);
			$check=trim(strip_tags($_POST['answer']));
			if(empty($check)){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Empty Answer'));
				exit();
			}
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Empty Answer'));
			exit();
		}

		$_POST['pos']=(is_numeric($_POST['pos']))? $_POST['pos']:NULL;
		$_POST['active']=(is_numeric($_POST['active']))? $_POST['active']:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query = "INSERT INTO ".$SupportFaqTable." (`question`,`answer`,`active`,`position`) 
						VALUES (?,?,?,CASE WHEN ? IS NULL THEN (IF ((SELECT MAX(c.position) FROM ".$SupportFaqTable." c ) IS NOT NULL,(SELECT MAX(d.position) FROM ".$SupportFaqTable." d )+1,0)) ELSE ? END)";
				
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['question'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['answer'],PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['active'],PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['pos'],PDO::PARAM_INT);
			$STH->bindParam(5,$_POST['pos'],PDO::PARAM_INT);
			$STH->execute();

			$data=array('response'=>'Added');

			$dpid=$DBH->lastInsertId();

			$_POST['active']=((int)$_POST['active']==0) ? 'No':'Yes';
			$data['information']=array('id'=>$dpid,'question'=>htmlspecialchars($_POST['question'],ENT_QUOTES,'UTF-8'),'position'=>$_POST['pos'],'active'=>$_POST['active']);

			if($_POST['pos']==NULL){
				$query = "SELECT `position` FROM ".$SupportFaqTable." WHERE `id`='".$dpid."' LIMIT 1";
				$STH = $DBH->prepare($query);
				$STH->execute();
				$STH->setFetchMode(PDO::FETCH_ASSOC);
				$a = $STH->fetch();
				if(!empty($a)){
					do{
						$data['information']['position']=$a['position'];
					}while ($a = $STH->fetch());
				}
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($data);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='del_faq'){
		$_POST['id']=(is_numeric($_POST['id']))? $_POST['id']+14:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query="DELETE FROM ".$SupportRateFaqTable." WHERE `faq_id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			
			$query="DELETE FROM ".$SupportFaqTable." WHERE `id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Deleted'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='edit_faq'){
		$_POST['id']=(is_numeric($_POST['id']))? $_POST['id']+14:exit();
		$_POST['question']=(trim(preg_replace('/\s+/','',$_POST['question']))!='')? trim(preg_replace('/\s+/',' ',$_POST['question'])):exit();
		$_POST['answer']=trim(preg_replace('/\s+/',' ',$_POST['answer']));
		$_POST['rate']=(is_numeric($_POST['rate']))? $_POST['rate']:exit();
		if(trim(preg_replace('/\s+/','',$_POST['answer']))!=''){
			require_once 'htmlpurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$purifier = new HTMLPurifier($config);
			$_POST['answer'] = $purifier->purify($_POST['answer']);
			$check=trim(strip_tags($_POST['answer']));
			if(empty($check)){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'Empty Answer'));
				exit();
			}
		}
		else{
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Empty Answer'));
			exit();
		}
		
		$_POST['position']=(is_numeric($_POST['position']))? $_POST['position']:NULL;
		$_POST['active']=(is_numeric($_POST['active']))? $_POST['active']:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			if($_POST['position']==NULL){
				try{
					$query = "SELECT (IF ((SELECT c.id FROM ".$SupportFaqTable." c LIMIT 1) IS NOT NULL AND (SELECT COUNT(*) FROM ".$SupportFaqTable." LIMIT 3) > 1,(SELECT MAX(d.position) FROM ".$SupportFaqTable." d )+1,0)) AS rpos FROM ".$SupportFaqTable;
					
					$STH = $DBH->prepare($query);
					$STH->execute();
					
					$STH->setFetchMode(PDO::FETCH_ASSOC);
					$a = $STH->fetch();
					if(!empty($a)){
						do{
							$_POST['position']=$a['rpos'];
						}while ($a = $STH->fetch());
					}
				}
				catch(PDOException $e){
					file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
				}
			}
			$query = "UPDATE ".$SupportFaqTable."
						SET question=?,
							answer=?,
							position=?,
							active=? 
						WHERE id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['question'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['answer'],PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['position'],PDO::PARAM_INT);
			$STH->bindParam(4,$_POST['active'],PDO::PARAM_STR);
			$STH->bindParam(5,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			
			$_POST['active']=($_POST['active']==0)?'No':'Yes';
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Succeed',1=>array('id'=>($_POST['id']-14),'question'=>htmlspecialchars($_POST['question'],ENT_QUOTES,'UTF-8'),'position'=>$_POST['position'],'active'=>$_POST['active'],'rate'=>'Unrated')));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='retrive_faq_answer'){
		$_POST['id']=(is_numeric($_POST['id']))? $_POST['id']+14:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT answer FROM ".$SupportFaqTable." WHERE id=? LIMIT 1";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$list=array(0=>'ret');
			$a = $STH->fetch();
			if(!empty($a)){
				do{
					$list[]=$a['answer'];
				}while ($a = $STH->fetch());
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($list);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='rem_flag'){
		$_POST['id']=trim(preg_replace('/\s+/','',$_POST['id']));
		if(!preg_match('/^[0-9]{1,15}$/',$_POST['id'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid ID'));
			exit();
		}
		$_POST['id']=$_POST['id']+14;
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query="DELETE FROM ".$SupportFlagTable." WHERE `id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
			$STH->execute();
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Deleted'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		
	}
	
	else if($_POST[$_SESSION['token']['act']]=='edit_sale_info'){
		$_POST['id']=(is_numeric($_POST['id'])) ? (int)$_POST['id']-1:exit();
		$_POST['tanid']=trim(preg_replace('/\s+/','',$_POST['tanid']));
		if(empty($_POST['name'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Transaction ID'));
			exit();
		}
		$_POST['amount']=trim(str_replace(',', '.', $_POST['amount']));
		if(!preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Amount'));
			exit();
		}
		if(!is_numeric($_POST['time'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Support Time'));
			exit();
		}
		else
			$_POST['time']=round($_POST['time'],0);

		if(!is_numeric($_POST['status']) || !filter_var($_POST['status'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 4)))){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid Status'));
			exit();
		}

		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "UPDATE ".$SupportDepaTable." SET `amount`=?,`support_time`=?,`status`=? WHERE `id`=? AND `transaction_id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['amount'],PDO::PARAM_STR);
			$STH->bindParam(2,$_POST['time'],PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['status'],PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['id'],PDO::PARAM_INT);
			$STH->bindParam(5,$_POST['tanid'],PDO::PARAM_STR);
			$STH->execute();
			if($STH->rowCount()>0){
				$query = "SELECT 
							`gateway`,
							CASE `status` WHEN '0' THEN '<span class=\'label label-warning\'>Pending</span>'  WHEN '1' THEN '<span class=\'label label-important\'>Cancelled</span>'  WHEN '2' THEN '<span class=\'label label-success\'>Processed</span>'  WHEN '3' THEN '<span class=\'label label-inverse\'>Refund</span>'  ELSE `status` END AS sale_stat,
							`payer_mail`,
							`transaction_id`,
							`tk_id`,
							`amount`,
							`support_time`,
							`payment_date`
						FROM ".$SupportSalesTable." WHERE id=? LIMIT 1";
						
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_POST['id'],PDO::PARAM_INT);
				$STH->execute();

				$STH->setFetchMode(PDO::FETCH_ASSOC);
				$a = $STH->fetch();
				$users=array(0=>'Updated');
				$users[1]=array('ID'=>$_POST['id']+1,
								'gateway'=>htmlspecialchars($a['gateway'],ENT_QUOTES,'UTF-8'),
								'payer_mail'=>htmlspecialchars($a['payer_mail'],ENT_QUOTES,'UTF-8'),
								'status'=>$a['sale_stat'],
								'transaction_id'=>$a['transaction_id'],
								'tk_id'=>$a['tk_id'],
								'amount'=>$a['amount'],
								'support_time'=>$a['support_time'],
								'payment_date'=>$a['payment_date'],
							);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode($users);
				exit();
			}
			else{
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode('No Match Found');
				exit();
			}
		}
		catch(PDOException $e){
			if ($e->errorInfo[1] == 1062){
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>"Department name: ".json_encode($_POST['name'])." already exist"));
			}
			else{
				file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
			}
		}
		exit();
	}
	
	else if($_POST[$_SESSION['token']['act']]=='retrieve_price_tab'){
		if(!is_numeric($_POST['id'])){
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array(0=>'Invalid ID'));
			exit();
		}
		if(is_file('../php/config/price/'.$_POST['id'])){
			$price=file('../php/config/price/'.$_POST['id'],FILE_IGNORE_NEW_LINES);
			unset($price[0]);
		}
		else
			$price=array();

		$price=array('ret',implode("\n",$price));

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($price);
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

}

function retrive_depa_names($Hostname, $Username, $Password, $DatabaseName, $SupportDepaTable){
	if(isset($_SESSION['status']) && $_SESSION['status']<3){
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query = "SELECT `id`,`department_name` FROM ".$SupportDepaTable;
			$STH = $DBH->prepare($query);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				$b=array();
				do{
					$b[$a['id']]=$a['department_name'];
				}while ($a = $STH->fetch());
				return json_encode($b);
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
		}
	}
}

function retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable,$dep,$nope){
	$query = "SELECT 
				id
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
				$selopid=$a['id'];
			}while ($a = $STH->fetch());
			return $selopid;
		}
		else
			return 'No Operator Available';

	}
	catch(PDOException $e){  
		file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\n", FILE_APPEND);
		return 'An Error has occurred, please read the PDOErrors file and contact a programmer';
	}
}

function get_random_string($length){$valid_chars='abcdefghilmnopqrstuvzkjwxyABCDEFGHILMNOPQRSTUVZKJWXYZ0123456789';$random_string = "";$num_valid_chars = strlen($valid_chars);for($i=0;$i<$length;$i++){$random_pick=mt_rand(1, $num_valid_chars);$random_char = $valid_chars[$random_pick-1];$random_string .= $random_char;}return $random_string;}
function retrive_ip(){if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];}elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];}else{$ip=$_SERVER['REMOTE_ADDR'];}return $ip;}
function curPageURL() {$pageURL= "//";if (isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}

?>