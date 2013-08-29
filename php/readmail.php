<?php
	

	include_once('config/mail/imap.php'); //$imapserver, $imapuser, $imappass, $imapport, $imapencryption
	switch ($imapencryption){
		case 0:
			$imapencryption='{'.$imapserver.'/notls}';
			break;
		case 1:
			$imapencryption='{'.$imapserver.'/ssl}';
			break;
		case 2:
			$imapencryption='{'.$imapserver.'/tls}';
			break;
		default:
			exit();
	}
	$conn =imap_open($imapencryption, $imapuser, $imappass);
	$c = imap_num_msg($connection);
	for($msgno = 1; $msgno <= $c; $msgno++) {
		$headers = imap_headerinfo($connection, $msgno);
		if($headers->Unseen == 'U') {
		  //Read Reply
		}
		imap_delete($mbox, $msgno);
	}
	imap_expunge($mbox);
	imap_close($conn);
?>