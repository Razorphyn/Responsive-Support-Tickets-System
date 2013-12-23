<?php

if(!is_file('config/payment/paypal.txt') || !is_file('../php/config/setting.txt') || !isset($_POST['txn_id'])) exit();

include_once 'config/database.php';

$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

$adminmail=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);	
$paypal_setting=(is_file('config/payment/paypal.txt'))? file('config/payment/paypal.txt',FILE_IGNORE_NEW_LINES):exit();
//0=>enabled,1=>mail,2=>currency,3=>sandbox,4=>curl

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";
$headers .= 'From: '.$adminmail[1]."\r\n";

$req = 'cmd=_notify-validate';
foreach ($_POST as $key => $value){
	$value = urlencode(stripslashes($value));
	$value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i','${1}%0D%0A${3}',$value);// IPN fix
	$req .= "&$key=$value";
}

$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: ".strlen($req)."\r\n\r\n";

if(isset($paypal_setting[4]) && $paypal_setting[4]==1){
	if($paypal_setting[3]==1)
		$fp = curl_init('https://www.sandbox.paypal.com/cgi-bin/webscr');
	else
		$fp = curl_init('https://www.paypal.com/cgi-bin/webscr');
	curl_setopt($fp, CURLOPT_POST, true);
	curl_setopt($fp, CURLOPT_POSTFIELDS, $_POST);
	curl_setopt($fp, CURLOPT_RETURNTRANSFER, true);

	$response = curl_exec($fp);

	$response_code = curl_getinfo($fp, CURLINFO_HTTP_CODE);
}
else{
	if($paypal_setting[3]==1)
		$fp = fsockopen ('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
	else
		$fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);
}

if(!$fp){
	$message="An error has been occurred during payment elaboration(PAYPAL_CONNECTION_ERROR).";
	mail($adminmail[10],'Payment Error',$message,$headers);
}
else {
	list($minutes,$userid)=explode(':',$_POST['custom']);
	$minutes=(int)$minutes;
	$userid=(int)$userid;
	fputs ($fp, $header.$req);
	while (!feof($fp)){
		$res = fgets ($fp, 1024);
		if (strcmp ($res, 'VERIFIED') == 0) {
			if(check_txnid($_POST['txn_id']) && check_price($_POST['mc_gross'],$_POST['custom']) && $paypal_setting[2]==$_POST['mc_currency'] && $_POST['receiver_email']==$paypal_setting[1]){
					$date= date("Y-m-d H:i:s");
					switch(strtolower($_POST['payment_status'])){
						case 'completed':
							$st=2;
							break;
						case 'failed':
							$st=1;
							break;
						case 'expired':
							$st=1;
							break;
						case 'pending':
							$st=0;
							break;
						case 'partially_refunded':
						case 'refunded':
							$st=3;
							break;
						default:
							$st=1;
							$message="An error has been occurred during payment elaboration(PAYMENT_STATUS_ERROR).\nInformation:\nGateway: Paypal\nTransition ID:".$_POST['txn_id']."\nTicket ID: ".$_POST['tkid']."\nUser ID: ".$userid."\nPayer Mail: ".$_POST['payer_email']."\nStatus: ".$_POST['payment_status'];
							mail($adminmail[10],'Payment Error',$message,$headers);
					}
					try{
						if($t
							$query = "INSERT INTO ".$SupportSalesTable."
													(`gateway`,`payer_mail`,`status`,`transaction_id`,`tk_id`,`user_id`,`amount`,`support_time`,`payment_date`) 
												VALUES 
													('PayPal',?,?,?,?,(SELECT user_id FROM ".$SupportTicketsTable." WHERE id=?),?,?,?)";
							$STH = $DBH->prepare($query);
							$STH->bindParam(1,$_POST['payer_email'],PDO::PARAM_STR);
							$STH->bindParam(2,$st,PDO::PARAM_STR);
							$STH->bindParam(3,$_POST['txn_id'],PDO::PARAM_STR);
							$STH->bindParam(4,$_POST['item_number'],PDO::PARAM_STR);
							$STH->bindParam(5,$userid,PDO::PARAM_INT);
							$STH->bindParam(6,$_POST['mc_gross'],PDO::PARAM_STR);
							$STH->bindParam(7,$minutes,PDO::PARAM_INT);
							$STH->bindParam(8,$date,PDO::PARAM_STR);
							$STH->execute();

							if($st==2){
								$query = "UPDATE ".$SupportTicketsTable." SET enabled='1' WHERE `id`=? LIMIT 1";
								$STH = $DBH->prepare($query);
								$STH->bindParam(1,$_POST['item_number'],PDO::PARAM_INT);
								$STH->execute();
							}
						}
						exit();
					}
					catch(PDOException $e){
						file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
						file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
						$message="An error has been occurred during payment elaboration(PDO_ERROR).\n PDO ERROR:\nFile: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\nPayment Information:\nGateway: PayPal\nTransition ID:".$_POST['transaction_id']."\nTicket ID: ".$_POST['tkid']."\nUser ID: ".$userid."\nPayer Mail: ".$_POST['payer_email'];
						mail($adminmail[10],'Payment Error',$message,$headers);
						exit();
					}
			}
			else{
				$message="An error has been occurred during payment elaboration(EDITED_INFORMATION_MAIL_ERROR).\nInformation:\nGateway: PayPal\nTransition ID:".$_POST['transaction_id']."\nTicket ID: ".$_POST['tkid']."\nUser ID: ".$userid."\nPayer Mail: ".$_POST['payer_email'];
				mail($adminmail[10],'Payment Error',$message,$headers);
				exit();
			}
		}
		else if (strcmp ($res, "INVALID") == 0) {
			$message="An error has been occurred during payment elaboration(PAYMENT_STATUS_ERROR).\nInformation:\nGateway: Moneybooker\nTransition ID:".$_POST['transaction_id']."\nTicket ID: ".$_POST['tkid']."\nUser ID: ".$userid."\nPayer Mail: ".$_POST['payer_email'];
			mail($adminmail[10],'Payment Error',$message,$headers);
			exit();
		}
	}
	fclose ($fp);
}

function check_txnid($tnxid){
	global $DBH;
	$query = "SELECT * FROM ".$SupportSalesTable." WHERE `transaction_id`= ? AND `gateway`='PayPal' LIMIT 1";
	$STH = $DBH->prepare($query);
	$STH->bindParam(1,$$tnxid,PDO::PARAM_STR);
	$STH->execute();
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$a = $STH->fetch();
	if(empty($a))
		return true;
	else
		return false;
}

function check_price($price,$minutes){
	$file=file('config/payment/price.txt');
	
	if($file[0]==0){
		$prices=json_decode($file[1]);
		if(round($prices[$minutes],2)==$price)
			return true;
		else
			return false;
	}
 	else if($file[0]==1){
		if(round($file[1]*$minutes,2)==$price)
			return true;
		else
			return false;
	}
	else
		return false;
}

?>