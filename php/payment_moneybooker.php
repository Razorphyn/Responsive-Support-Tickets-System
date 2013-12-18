<?php

if(!is_file('config/payment/moneybooker.txt') || !is_file('../php/config/setting.txt') || !isset($_POST['transaction_id'])) exit();

$moneybooker_setting=file('config/payment/moneybooker.txt');
//0=>merchant_id, 1=>payment_mail, 2=>currency,3=>company, 4=>secret word
$adminmail=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);

// Validate the Moneybookers signature
$concatFields = $_POST['merchant_id'].$_POST['transaction_id'].strtoupper(md5($moneybooker_setting[4])).$_POST['mb_amount'].$_POST['mb_currency'].$_POST['status'];

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";
$headers .= 'From: '.$adminmail[1]."\r\n";

if (strtoupper(md5($concatFields)) == $_POST['md5sig'] && $_POST['pay_to_email'] == $moneybooker_setting[1])
{
	if($file[0]==0){
		$prices=json_decode($file[1]);
		$amount=round($prices[$_POST['minutes']],2);
	}
	else if($file[0]==1){
		$amount=round($file[1]*$_POST['minutes'],2);
	}
	if($amount==$_POST['mb_amount'] && $_POST['mb_currency']==$moneybooker_setting[2] && check_txnid($_POST['transaction_id'])){
		$date= date("Y-m-d H:i:s");
		$query = "INSERT INTO ".$SupportSalesTable."(`gateway`,`payer_mail`,`status`,`transaction_id`,`tk_id`,`user_id`,`amount`,`support_time`,`payment_date`) VALUES ('Moneybookers',?,?,?,?,(SELECT user_id FROM ".$SupportUserTable." WHERE id=?),?,?,?)";
		switch($_POST['status']){
			case '2':
				$st=2;
				break;
			case '-2':
				$st=1;
				break;
			case '0':
				$st=0;
				break;
			default:
				$st=1;
				$message="An error has been occurred during payment elaboration(PAYMENT_STATUS_ERROR).\nInformation:\nGateway: Moneybooker\nTransition ID:".$_POST['transaction_id']."\nTicket ID: ".$_POST['tkid']."\nPayer Mail: ".$_POST['payer_email'];
				mail($adminmail[10],'Payment Error',$message,$headers);
		}
		try{
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$_POST['payer_email'],PDO::PARAM_STR);
			$STH->bindParam(2,$st,PDO::PARAM_STR);
			$STH->bindParam(3,$_POST['transaction_id'],PDO::PARAM_STR);
			$STH->bindParam(4,$_POST['tkid'],PDO::PARAM_STR);
			$STH->bindParam(5,$_POST['tkid'],PDO::PARAM_INT);
			$STH->bindParam(6,$_POST['mb_amount'],PDO::PARAM_STR);
			$STH->bindParam(7,$minutes,PDO::PARAM_INT);
			$STH->bindParam(8,$date,PDO::PARAM_STR);
			$STH->execute();

			if($st==2){
				$query = "UPDATE ".$SupportTicketsTable." SET enabled='1' WHERE `id`=? LIMIT 1";
				$STH = $DBH->prepare($query);
				$STH->bindParam(1,$_POST['tkid'],PDO::PARAM_INT);
				$STH->execute();
			}
			exit();
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			$message="An error has been occurred during payment elaboration(PDO_ERROR).\n PDO ERROR:\nFile: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage()."\nPayment Information:\nGateway: Moneybooker\nTransition ID:".$_POST['transaction_id']."\nTicket ID: ".$_POST['tkid']."\nPayer Mail: ".$_POST['payer_email'];
			mail($adminmail[10],'Payment Error',$message,$headers);
			exit(),
		}
	}
	else{
		$message="An error has been occurred during payment elaboration(USER_INFORMATION_ERROR).\nInformation:\nGateway: Moneybooker\nTransition ID:".$_POST['transaction_id']."\nTicket ID: ".$_POST['tkid']."\nPayer Mail: ".$_POST['payer_email'];
		mail($adminmail[10],'Payment Error',$message,$headers);
		exit();
	}
}
else
{
	$message="An error has been occurred during payment elaboration(FIELD_OR_RECEIVER_MAIL_ERROR).\nInformation:\nGateway: Moneybooker\nTransition ID:".$_POST['transaction_id']."\nTicket ID: ".$_POST['tkid']."\nPayer Mail: ".$_POST['payer_email'];
	mail($adminmail[10],'Payment Error',$message,$headers);
    exit();
}

function check_txnid($tnxid){
	global $DBH;
	$query = "SELECT * FROM ".$SupportSalesTable." WHERE `transaction_id`= ? AND `gateway`='Moneybookers' LIMIT 1";
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
?>