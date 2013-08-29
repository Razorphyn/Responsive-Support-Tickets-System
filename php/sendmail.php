<?php
/**
 * Razorphyn
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future.
 *
 * @copyright  Copyright (c) 2013 Razorphyn
 *
 * Extended Coming Soon Countdown
 *
 * @author     	Razorphyn
 * @Site		http://razorphyn.com/
 */
if(isset($argv[0]) && isset($argv[1]) && isset($argv[2])){
	include_once '../php/config/database.php';
	require_once '../lib/Swift/lib/swift_required.php';
	if(is_file('../php/config/mail/stmp.txt')){
		$stmp=file('../php/config/mail/stmp.txt',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$string='<?php'."\n".'$smailservice='.$stmp[0].";\n".'$smailname=\''.$stmp[1]."';\n".'$settingmail=\''.$stmp[2]."';\n".'$smailhost=\''.$stmp[3]."';\n".'$smailport='.$stmp[4].";\n".'$smailssl='.$stmp[5].";\n".'$smailauth='.$stmp[6].";\n".'$smailuser=\''.$stmp[7]."';\n".'$smailpassword=\''.$stmp[8]."';\n ?>";
		file_put_contents('../php/config/mail/stmp.php',$string);
		file_put_contents('../php/config/mail/stmp.txt','');
		unlink('../php/config/mail/stmp.txt');
	}
	if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
	if(is_file('../php/config/mail/stmp.php')) include_once('../php/config/mail/stmp.php');
	
	switch ($argv[1]){
		case 'NewMem':
			if(is_file('../php/config/mail/newuser.txt')){
				$file=file('../php/config/mail/newuser.txt',FILE_IGNORE_NEW_LINES);
				$query = "SELECT `mail`,`name`,`reg_key` FROM ".$SupportUserTable." WHERE `id`=? LIMIT 1";
			}
			else
				exit();
			break;
		
		case 'NewRep':
			if(is_file('../php/config/mail/newreply.txt')){
				$file=file('../php/config/mail/newreply.txt',FILE_IGNORE_NEW_LINES);
				if($argv[2]==1)//utente
					$query="SELECT a.ref_id,CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE 'Error' END, CASE a.ticket_status WHEN '0' THEN 'Closed' WHEN '1' THEN 'Open' WHEN '2' THEN 'To Assign' ELSE 'Error' END ,a.title, b.name ,c.department_name, tu.mail,tu.name FROM ".$SupportTicketsTable." a, ".$SupportUserTable." b, ".$SupportDepaTable." c,".$SupportTicketsTable." lt,".$SupportUserTable." tu WHERE a.enc_id=? AND b.id=a.user_id AND c.id=a.department_id  AND tu.id=lt.operator_id LIMIT 1;";
				else//operatore
					$query="SELECT a.ref_id,CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE 'Error' END, CASE a.ticket_status WHEN '0' THEN 'Closed' WHEN '1' THEN 'Open' WHEN '2' THEN 'To Assign' ELSE 'Error' END ,a.title, b.name , b.mail_alert ,c.department_name, b.mail,tu.name FROM ".$SupportTicketsTable." a, ".$SupportUserTable." b, ".$SupportDepaTable." c,".$SupportTicketsTable." lt,".$SupportUserTable." tu WHERE a.enc_id=? AND b.id=a.user_id AND c.id=a.department_id  AND tu.id=lt.operator_id LIMIT 1;";
			}
			else 
				exit();
			break;
		case 'NewTick':
			if(is_file('../php/config/mail/newticket.txt')){
				$file=file('../php/config/mail/newticket.txt',FILE_IGNORE_NEW_LINES);
				$query="SELECT a.ref_id,CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE 'Error' END, CASE a.ticket_status WHEN '0' THEN 'Closed' WHEN '1' THEN 'Open' WHEN '2' THEN 'To Assign' ELSE 'Error' END ,a.title, b.name ,c.department_name, b.mail,tu.name FROM ".$SupportTicketsTable." a, ".$SupportUserTable." b, ".$SupportDepaTable." c, ".$SupportTicketsTable." lt, ".$SupportUserTable." tu WHERE a.enc_id=? AND b.id=a.user_id AND c.id=a.department_id  AND tu.id=lt.operator_id LIMIT 1;";
			}
			else 
				exit();
			break;

		case 'AssTick':
			if(is_file('../php/config/mail/assigned.txt')){
				$file=file('../php/config/mail/assigned.txt',FILE_IGNORE_NEW_LINES);
				$query="SELECT a.ref_id,CASE a.priority WHEN '0' THEN 'Low' WHEN '1' THEN 'Medium' WHEN '2' THEN 'High' WHEN '3' THEN 'Urgent' WHEN '4' THEN 'Critical' ELSE 'Error' END, CASE a.ticket_status WHEN '0' THEN 'Closed' WHEN '1' THEN 'Open' WHEN '2' THEN 'To Assign' ELSE 'Error' END ,a.title, b.name ,c.department_name, tu.mail,tu.name FROM ".$SupportTicketsTable." a, ".$SupportUserTable." b, ".$SupportDepaTable." c, ".$SupportTicketsTable." lt, ".$SupportUserTable." tu WHERE a.enc_id=? AND b.id=a.user_id AND c.id=a.department_id  AND tu.id=lt.operator_id LIMIT 1;";
			}
			else 
				exit();
			break;

		case 'Forgot':
			if(is_file('../php/config/mail/forgotten.txt')){
				$file=file('../php/config/mail/forgotten.txt',FILE_IGNORE_NEW_LINES);
				$query = "SELECT `mail`,`name` FROM ".$SupportUserTable." WHERE `id`= ? LIMIT 1";
			}
			else 
				exit();
			break;
			
		default:
			exit();
			break;
	}
		
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
		$prepared = $stmt->prepare($query);
		if($prepared){
			if($argv[1]=='NewMem' || $argv[1]=='Forgot')
				$bind=$stmt->bind_param('i', $argv[2]);
			else
				$bind=$stmt->bind_param('s', $argv[2]);

			if($bind){
				if($stmt->execute()){
					$stmt->store_result();
					if($argv[1]=='NewMem')
						$result = $stmt->bind_result($tomail, $gmane, $reg_key);
					else if($argv[1]=='Forgot')
						$result = $stmt->bind_result($tomail, $gmane);
					else if($argv[1]=='NewRep' && $argv[2]==0)
						$result = $stmt->bind_result($refid,$prio, $stat, $tit, $gmane, $allow, $dpname, $tomail, $opname);
					else
						$result = $stmt->bind_result($refid,$prio, $stat, $tit, $gmane, $dpname, $tomail, $opname);
					
					if($stmt->num_rows>0){
						switch ($argv[1]){
							case 'NewMem':
								while (mysqli_stmt_fetch($stmt)){
									$gnmail=$tomail;
									$allow=$allow;
									$rep=array('{USER_NAME}'=>$gmane,'{USER_ACTIVATION_LINK}'=>"<a href='".(dirname(dirname(curPageURL())))."/index.php?act=activate&reg=".$reg_key."'>Activate your Account</a>",'{USER_EMAIL}'=>$gnmail);
								}
								break;
							
							case 'Forgot':
								while (mysqli_stmt_fetch($stmt)){
									$gnmail=$tomail;
									$rep=array('{USER_NAME}'=>$gmane,'{USER_EMAIL}'=>$gnmail,'{USER_RESET_LINK}'=>"<a href='".dirname(dirname(curPageURL())).'/user/reset.php?act=resetpass&key='.$argv[3]."'>Reset Password </a>");
								}
								break;
							
							default:
								while (mysqli_stmt_fetch($stmt)){
									$gnmail=$tomail;
									$rep=array('{TICKET_REFERENCE_ID}'=>$refid,'{TICKET_OPERATOR_NAME}'=>$opname,'{TICKET_CREATOR_NAME}'=>$gmane,'{TICKET_PRIORITY}'=>$prio,'{TICKET_STATUS}'=>$stat,'{TICKET_DEPARTMENT}'=>$dpname,'{TICKET_URL}'=>dirname(dirname(curPageURL())).'/user/view.php?id='.$argv[2],'{TICKET_TITLE}'=>$tit);
								}
								break;
						}
						
						if(isset($allow) && $allow=='no')
							exit();
						else{
							if(isset($setting[0]))
								$rep['{SITE_NAME}']=$setting[0];
							$rep['{SITE_ADDRESS}']=domain_name();
							$file[1]=html_entity_decode($file[1]);
							foreach($rep as $find => $sost)
								$file[1]=str_replace($find,$sost,$file[1]);
							$plain=convert_html_to_text(str_replace('&','&amp;',str_replace('&nbsp;',' ',$file[1])));
							//Send Mail
							$message = Swift_Message::newInstance();
							$message->setFrom($settingmail);
							$message->setReplyTo($settingmail);
							$message->setSubject($file[0]);
							$message->setContentType("text/plain; charset=UTF-8");
							$message->setBody($plain,'text/plain');						
							$message->addPart($file[1],'text/html');
							
							$message->setTo($gnmail);

							if($smailservice==0)
								$transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -t');
							else if($smailservice==1){
								if($smailssl==0)
									$transport = Swift_SmtpTransport::newInstance($settingmail,$smailport);
								else if($smailssl==1)
									$transport = Swift_SmtpTransport::newInstance($settingmail,$smailport,'ssl');
								else if($smailssl==2)
									$transport = Swift_SmtpTransport::newInstance($settingmail,$smailport,'tls');
								else
									exit();
								if($smailauth==1){
									$transport->setUsername($smailuser);
									$crypttable=array('X'=>'a','k'=>'b','Z'=>'c',2=>'d','d'=>'e',6=>'f','o'=>'g','R'=>'h',3=>'i','M'=>'j','s'=>'k','j'=>'l',8=>'m','i'=>'n','L'=>'o','W'=>'p',0=>'q',9=>'r','G'=>'s','C'=>'t','t'=>'u',4=>'v',7=>'w','U'=>'x','p'=>'y','F'=>'z','q'=>0,'a'=>1,'H'=>2,'e'=>3,'N'=>4,1=>5,5=>6,'B'=>7,'v'=>8,'y'=>9,'K'=>'A','Q'=>'B','x'=>'C','u'=>'D','f'=>'E','T'=>'F','c'=>'G','w'=>'H','D'=>'I','b'=>'J','z'=>'K','V'=>'L','Y'=>'M','A'=>'N','n'=>'O','r'=>'P','O'=>'Q','g'=>'R','E'=>'S','I'=>'T','J'=>'U','P'=>'V','m'=>'W','S'=>'X','h'=>'Y','l'=>'Z');
									$smailpassword=str_split($smailpassword, ENT_QUOTES, 'UTF-8');
									$c=count($smailpassword);
									for($i=0;$i<$c;$i++){
										if(array_key_exists($smailpassword[$i],$crypttable))
											$smailpassword[$i]=$crypttable[$crypttable[$smailpassword[$i]]];
									}
									$smailpassword=implode('',$smailpassword);
									$transport->setPassword($smailpassword);
								}
							}
							else
								exit();

							$gnmailer = Swift_Mailer::newInstance($transport);

							if(!$gnmailer->send($message,$failure))
								file_put_contents('send_mail_Send_error',$argv[1]."\n".print_r($failure,true),FILE_APPEND | LOCK_EX);

							//End mail
						}
					}
					else{
						exit();
					}
				}
				else
					file_put_contents('send_mail_sql_error',$argv[1]."\n".mysqli_stmt_error($stmt)."\n",FILE_APPEND | LOCK_EX);
			}
			else
				file_put_contents('send_mail_sql_error',$argv[1]."\n".mysqli_stmt_error($stmt)."\n",FILE_APPEND | LOCK_EX);
		}
		else
			file_put_contents('send_mail_sql_error',$argv[1]."\n".mysqli_stmt_error($stmt)."\n",FILE_APPEND | LOCK_EX);
	}
	else
		file_put_contents('send_mail_sql_error',$argv[1]."\n".mysqli_stmt_error($stmt)."\n",FILE_APPEND | LOCK_EX);
}
else{
	exit();
}
function domain_name() {$pageURL = 'http';if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if ($_SERVER["SERVER_PORT"] != "80")$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];else$pageURL .= $_SERVER["SERVER_NAME"];return $pageURL;}
function curPageURL() {$pageURL = 'http';if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $pageURL .= "s";$pageURL .= "://";if ($_SERVER["SERVER_PORT"] != "80")$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];else$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];return $pageURL;}
/******************************************************************************
 * Copyright (c) 2010 Jevon Wright and others.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Jevon Wright - initial API and implementation
 ****************************************************************************/

function convert_html_to_text($html) {
	//libxml_use_internal_errors(true);
	$html = fix_newlines($html);

	$doc = new DOMDocument();
	if (!$doc->loadHTML($html))
		throw new Html2TextException("Could not load HTML - badly formed?", $html);

	$doc->loadHTML($html);
	$output = iterate_over_node($doc);

	// remove leading and trailing spaces on each line
	$output = preg_replace("/[ \t]*\n[ \t]*/im", "\n", $output);

	// remove leading and trailing whitespace
	$output = trim($output);

	return $output;
}
function fix_newlines($text) {
	// replace \r\n to \n
	$text = str_replace("\r\n", "\n", $text);
	// remove \rs
	$text = str_replace("\r", "\n", $text);

	return $text;
}
function next_child_name($node) {
	// get the next child
	$nextNode = $node->nextSibling;
	while ($nextNode != null) {
		if ($nextNode instanceof DOMElement)
			break;
		$nextNode = $nextNode->nextSibling;
	}
	$nextName = null;
	if ($nextNode instanceof DOMElement && $nextNode != null)
		$nextName = strtolower($nextNode->nodeName);

	return $nextName;
}
function prev_child_name($node) {
	// get the previous child
	$nextNode = $node->previousSibling;
	while ($nextNode != null) {
		if ($nextNode instanceof DOMElement) {break;}
		$nextNode = $nextNode->previousSibling;
	}
	$nextName = null;
	if ($nextNode instanceof DOMElement && $nextNode != null) {$nextName = strtolower($nextNode->nodeName);}
	return $nextName;
}
function iterate_over_node($node) {
	if ($node instanceof DOMText) {return preg_replace("/\\s+/im", " ", $node->wholeText);}
	if ($node instanceof DOMDocumentType) {return "";}
	$nextName = next_child_name($node);
	$prevName = prev_child_name($node);
	$gmane = strtolower($node->nodeName);

	// start whitespace
	switch ($gmane) {
		case "hr":
			return "------\n";
		case "style":case "head":case "title":case "meta":case "script":
			return "";
		case "h1":case "h2":case "h3":case "h4":case "h5":case "h6":
			$output = "\n";
			break;
		case "p":case "div":
			$output = "\n";
			break;
		default:
			$output = "";
			break;
	}

	// debug
	//$output .= "[$gmane,$nextName]";

	for ($i = 0; $i < $node->childNodes->length; $i++) {$n = $node->childNodes->item($i);$text = iterate_over_node($n);$output .= $text;}

	// end whitespace
	switch ($gmane) {
		case "style":case "head":case "title":case "meta":case "script":
			return "";

		case "h1":case "h2":case "h3":case "h4":case "h5":case "h6":
			$output .= "\n";
			break;

		case "p":case "br":
			if ($nextName != "div")
				$output .= "\n";
			break;

		case "div":
			// add one line only if the next child isn't a div
			if ($nextName != "div" && $nextName != null)
				$output .= "\n";
			break;

		case "a":
			// links are returned in [text](link) format
			$href = $node->getAttribute("href");
			if ($href == null) {
				// it doesn't link anywhere
				if ($node->getAttribute("name") != null) {
					$output = "[$output]";
				}
			} else {
				if ($href == $output) {
					// link to the same address: just use link
					$output;
				} else {
					// replace it
					$output = "[$output]($href)";
				}
			}
			// does the next node require additional whitespace?
			switch ($nextName) {
				case "h1": case "h2": case "h3": case "h4": case "h5": case "h6":
					$output .= "\n";
					break;
			}
		default:
			break;
	}
	return $output;
}
class Html2TextException extends Exception {
	var $more_info;
	public function __construct($message = "", $more_info = "") {parent::__construct($message);$this->more_info = $more_info;}
}
?>