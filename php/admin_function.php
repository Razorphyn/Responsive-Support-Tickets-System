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
if(!isset($_SESSION['status'])  || 2!=$_SESSION['status'])
	exit();
else{
	include_once 'config/database.php';
//Session Check
	if(is_file('../php/config/setting.txt')) $setting=file('../php/config/setting.txt',FILE_IGNORE_NEW_LINES);
	if(isset($setting[4])) date_default_timezone_set($setting[4]);

	if(isset($_SESSION['time']) && time()-$_SESSION['time']<=1800) $_SESSION['time']=time();
	
	else if(isset($_SESSION['id']) && !isset($_SESSION['time']) || isset($_SESSION['time']) && time()-$_SESSION['time']>1800){
		session_unset();
		session_destroy();
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			echo json_encode(array(0=>'Your Session has Expired, please reload the page and log in again'));
		else
			echo '<script>alert("Your Session has Expired, please reload the page and log in again");</script>';
		exit();
	}

	else if(isset($_SESSION['ip']) && $_SESSION['ip']!=retrive_ip()){
		session_unset();
		session_destroy();
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			echo json_encode(array(0=>'Invalid Session, please reload the page and log in again'));
		else
			echo '<script>alert("Invalid Session, please reload the page and log in again");</script>';
		exit();
	}

	//Functions
	if(isset($_POST['act']) && $_POST['act']=='retrive_reported_ticket'){  //check
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				$query = "SELECT 
							a.id,
							a.ref_id,
							a.enc_id,
							CASE b.status WHEN '0' THEN 'User' WHEN '1' THEN 'Operator' WHEN '2' THEN 'Adminsitrator' ELSE 'Useless' END AS urole,
							a.reason,
							b.mail  
				FROM ".$SupportFlagTable." a
				LEFT JOIN ".$SupportUserTable." b
					ON b.id=a.usr_id";
			$STH = $DBH->prepare($query);
			$STH->execute();
			$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
			$list=array('response'=>'ret','ticket'=>array());
			if(!empty($r)){
				while ($a = $STH->fetch())
					$list['ticket'][]=array('id'=>$a['id']-14,'ref_id'=>$a['ref_id'],'encid'=>$a['enc_id'],'role'=>$a['urole'],'reason'=>$a['reason'],'mail'=>$a['mail']);
			}
			echo json_encode($list);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if(isset($_POST['act'])  && $_POST['act']=='add_depart'){//check
		$mustang=(preg_replace('/\s+/','',$_POST['tit'])!='')? preg_replace('/\s+/',' ',$_POST['tit']):exit();
		$active=(is_numeric($_POST['active']))? $_POST['active']:exit();
		$public=(is_numeric($_POST['pubdep']))? $_POST['pubdep']:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "INSERT INTO ".$SupportDepaTable."(`department_name`,`active`,`public_view`) VALUES (?,?,?)";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$mustang,PDO::PARAM_STR);
			$STH->bindParam(2,$active,PDO::PARAM_STR);
			$STH->bindParam(3,$public,PDO::PARAM_STR);
			$STH->execute();
			$data=array();
			$data['response']='Added';
			$dpid=$DBH->lastInsertId();
			$active=((int)$active==0) ? 'No':'Yes';
			$public=((int)$public==0) ? 'No':'Yes';
			$data['information']=array('id'=>$dpid,'name'=>$mustang,'active'=>$active,'public'=>$public);
			echo json_encode($data);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if(isset($_POST['act'])  && $_POST['act']=='edit_depart'){//check
		$camaro=(is_numeric($_POST['id'])) ? (int)$_POST['id']:exit();
		$mustang=(preg_replace('/\s+/','',$_POST['name'])!='')? preg_replace('/\s+/',' ',$_POST['name']):exit();
		$active=(is_numeric($_POST['active'])) ? $_POST['active']:exit();
		$public=(is_numeric($_POST['pub'])) ? $_POST['pub']:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "UPDATE ".$SupportDepaTable." SET `department_name`=?,`active`=?,`public_view`=? WHERE id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$mustang,PDO::PARAM_STR);
			$STH->bindParam(2,$active,PDO::PARAM_STR);
			$STH->bindParam(3,$public,PDO::PARAM_STR);
			$STH->bindParam(4,$camaro,PDO::PARAM_INT);
			$STH->execute();
			echo json_encode(array(0=>'Succeed'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if(isset($_POST['act'])  && $_POST['act']=='del_dep'){//check
	$sub=(preg_replace('/\s+/','',$_POST['sub'])!='')? preg_replace('/\s+/',' ',$_POST['sub']):exit();
	$camaro=(is_numeric($_POST['id']))? (int)$_POST['id']:exit();
	
	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
		$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		if($sub=='del_name'){
			try{
				$sedquery="DELETE FROM ".$SupportUserPerDepaTable." WHERE `department_id`=?;";
				$delquery="DELETE FROM ".$SupportDepaTable." WHERE `id`= ?  ;";
				
				$STH = $DBH->prepare($sedquery);
				$STH->bindParam(1,$camaro,PDO::PARAM_INT);
				$STH->execute();
				
				$STH = $DBH->prepare($delquery);
				$STH->bindParam(1,$camaro,PDO::PARAM_INT);
				$STH->execute();
				echo json_encode(array(0=>'Deleted'));
			}
			catch(PDOException $e){  
				file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
				echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
			}
		}
		else if($sub=='del_every'){
			$sedquery="DELETE FROM ".$SupportUserPerDepaTable." WHERE `department_id`=?";
			$delquery="DELETE FROM ".$SupportDepaTable." WHERE `id`= ?";
			$seltk="SELECT id FROM ".$SupportTicketsTable." WHERE `department_id`= ?";
			$deltk="DELETE FROM ".$SupportTicketsTable." WHERE `department_id`= ?";
			try{
				$STH = $DBH->prepare($sedquery);
				$STH->bindParam(1,$camaro,PDO::PARAM_INT);
				$STH->execute();
			
				$STH = $DBH->prepare($delquery);
				$STH->bindParam(1,$camaro,PDO::PARAM_INT);
				$STH->execute();
				
				$STH = $DBH->prepare($seltk);
				$STH->bindParam(1,$camaro,PDO::PARAM_INT);
				$STH->execute();
				
				$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
				if(!empty($r)){
					$list=array();
					while ($a = $STH->fetch()){
						$list[]=$a['id'];
					}
					$list=implode(',',$list);
					
					$STH = $DBH->prepare($deltk);
					$STH->bindParam(1,$camaro,PDO::PARAM_INT);
					$STH->execute();
					
					$delmsg="DELETE FROM ".$SupportMessagesTable." WHERE `ticket_id` IN (".$list.")";
					$STH = $DBH->prepare($delmsg);
					$STH->execute();
					
					$selupl="SELECT enc FROM ".$SupportUploadTable." WHERE `num_id` IN (".$list.")";
					$STH = $DBH->prepare($selupl);
					$STH->execute();
					
					$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
					if(!empty($r)){
						$path='../upload/';
						while ($a = $STH->fetch()){
							file_put_contents($path.$enc,'');
							unlink($path.$enc);
						}
						
						$delup="DELETE FROM ".$SupportUploadTable." WHERE `num_id` IN (".$list.")";
						$STH = $DBH->prepare($delup);
						$STH->execute();
						echo json_encode(array(0=>'Deleted'));
					}
					else
						echo json_encode(array(0=>'Deleted'));
			
				}
				else
					echo json_encode(array(0=>'Deleted'));
			}
			catch(PDOException $e){  
				file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
				echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
			}
		}
		else
			echo json_encode(array(0=>'Cannot select sub process'));
	exit();
}

	else if(isset($_POST['act'])  && $_POST['act']=='save_options'){//check
		$senreply=(is_numeric($_POST['senrep'])) ? $_POST['senrep']:exit();
		$senope=(is_numeric($_POST['senope'])) ? $_POST['senope']:exit();
		$upload=(is_numeric($_POST['upload'])) ? $_POST['upload']:exit();
		$faq=(is_numeric($_POST['faq'])) ? $_POST['faq']:exit();
		$maxsize=(is_numeric($_POST['maxsize'])) ? ($_POST['maxsize']*1048576 ):null;
		$enrat=(is_numeric($_POST['enrat'])) ? $_POST['enrat']:exit();
		$commlop=(preg_replace('/\s+/',' ',$_POST['commlop'])=='php -f')? 'php -f':'php5-cli';
		$tit=preg_replace('/\s+/',' ',$_POST['tit']);
		$amail=preg_replace('/\s+/',' ',$_POST['mail']);
		if(file_put_contents('config/setting.txt',$tit."\n".$amail."\n".$senreply."\n".$senope."\n".$_POST['timezone']."\n".$_POST['upload']."\n".$maxsize."\n".$enrat."\n".$commlop."\n".$faq))
			echo json_encode(array(0=>'Saved'));
		else
			echo json_encode(array(0=>'Error'));
		exit();
	}

	else if(isset($_POST['act'])  && $_POST['act']=='save_stmp'){//check
		
		$serv=(is_numeric($_POST['serv'])) ? $_POST['serv']:exit();
		$mustang=(string)$_POST['name'];
		$viper=(string)$_POST['mail'];
		$host=(string)$_POST['host'];
		$port=(is_numeric($_POST['port'])) ? $_POST['port']:exit();
		$ssl=(is_numeric($_POST['ssl'])) ? $_POST['ssl']:exit();
		$auth=(is_numeric($_POST['auth'])) ? $_POST['auth']:exit();
		
		$usr=(string)$_POST['usr'];
		$pass=(string)$_POST['pass'];
		if(preg_replace('/\s+/','',$_POST['pass'])!=''){
			$crypttable=array('a'=>'X','b'=>'k','c'=>'Z','d'=>2,'e'=>'d','f'=>6,'g'=>'o','h'=>'R','i'=>3,'j'=>'M','k'=>'s','l'=>'j','m'=>8,'n'=>'i','o'=>'L','p'=>'W','q'=>0,'r'=>9,'s'=>'G','t'=>'C','u'=>'t','v'=>4,'w'=>7,'x'=>'U','y'=>'p','z'=>'F',0=>'q',1=>'a',2=>'H',3=>'e',4=>'N',5=>1,6=>5,7=>'B',8=>'v',9=>'y','A'=>'K','B'=>'Q','C'=>'x','D'=>'u','E'=>'f','F'=>'T','G'=>'c','H'=>'w','I'=>'D','J'=>'b','K'=>'z','L'=>'V','M'=>'Y','N'=>'A','O'=>'n','P'=>'r','Q'=>'O','R'=>'g','S'=>'E','T'=>'I','U'=>'J','V'=>'P','W'=>'m','X'=>'S','Y'=>'h','Z'=>'l');
			$pass=str_split($pass);
			$c=count($pass);
			for($i=0;$i<$c;$i++){
				if(array_key_exists($pass[$i],$crypttable))
					$pass[$i]=$crypttable[$crypttable[$pass[$i]]];
			}
			$pass=implode('',$pass);
		}
		if(file_put_contents('config/mail/stmp.txt',$serv."\n".$mustang."\n".$viper."\n".$host."\n".$port."\n".$ssl."\n".$auth."\n".$usr."\n".$pass))
			echo json_encode(array(0=>'Saved'));
		else
			echo json_encode(array(0=>'Error'));
		exit();
	}

	else if(isset($_POST['act'])  && $_POST['act']=='save_mail_body'){//check
		$sub=(preg_replace('/\s+/','',$_POST['sub'])!='')? preg_replace('/\s+/',' ',$_POST['sub']):exit();
		$mess=(preg_replace('/\s+/','',$_POST['message'])!='')? preg_replace('/\s+/',' ',$_POST['message']):exit();
		$act=(int)$_POST['sec'];
		if($act==0 && file_put_contents('config/mail/newuser.txt',$sub."\n".$mess))
			echo json_encode(array(0=>'Saved'));
		else if($act==1 && file_put_contents('config/mail/newreply.txt',$sub."\n".$mess))
			echo json_encode(array(0=>'Saved'));
		else if($act==2 && file_put_contents('config/mail/newticket.txt',$sub."\n".$mess))
			echo json_encode(array(0=>'Saved'));
		else if($act==3 && file_put_contents('config/mail/assigned.txt',$sub."\n".$mess))
			echo json_encode(array(0=>'Saved'));
		else if($act==4 && file_put_contents('config/mail/forgotten.txt',$sub."\n".$mess))
			echo json_encode(array(0=>'Saved'));
		else
			echo json_encode(array(0=>'Error'));
		exit();
	}

	else if(isset($_POST['upload_logo'])  && isset($_FILES['new_logo'])){//check
		$target_path = "../css/logo/".basename($_FILES['new_logo']['name']);
		if($_FILES['new_logo']['type']=='image/gif' || $_FILES['new_logo']['type']=='image/jpeg' || $_FILES['new_logo']['type']=='image/png' || $_FILES['new_logo']['type']=='image/pjpeg'){
				if(move_uploaded_file($_FILES['new_logo']['tmp_name'], $target_path)) {
					$dir=(dirname(dirname($_SERVER['REQUEST_URI']))!=rtrim('\ ')) ? dirname(dirname($_SERVER['REQUEST_URI'])):'';
					$image='//'.$_SERVER['SERVER_NAME'].$dir.'/php/config/logo/'.$_FILES['new_logo']['name'];
					file_put_contents('config/logo.txt',$image);
					echo '<script>parent.$("#cur_logo").attr("src","'.$image.'");</script>';
				}
				else
					echo "<script>parent.noty({text: 'Error during moving',type:'error',timeout:9E3});</script>";
		}
		exit();
	}

	else if(isset($_POST['act']) && $_POST['act']=='retrive_users'){
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT 
						`id`,
						`name`,
						`mail`,
						CASE `status` WHEN '0' THEN 'User'  WHEN '1' THEN 'Operator'  WHEN '2' THEN 'Administrator'  WHEN '3' THEN 'Activation'  WHEN '4' THEN 'Banned' ELSE 'Error' END AS ustat,
						CASE `holiday` WHEN '0' THEN 'No' ELSE 'Yes' END AS hol, 
						CASE WHEN `number_rating`='0' THEN 'No Rating' WHEN `number_rating`!='0' THEN `rating` ELSE 'Error' END AS rt
					FROM ".$SupportUserTable;
			
			$STH = $DBH->prepare($query);
			$STH->execute();
			
			$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
			if(!empty($r)){
				$users=array('response'=>'ret','information'=>array());
				while ($a = $STH->fetch()){
					$users['information'][]=array('num'=>$a['id']-54,'name'=>$a['name'],'mail'=>$a['mail'],'status'=>$a['ustat'],'holiday'=>$a['hol'],"rating"=>$a['rt']);
				}
				echo json_encode($users);
			}
			else
				echo json_encode(array('response'=>array('empty'),'information'=>array()));
							
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if(isset($_POST['act']) && $_POST['act']=='update_user_info'){//Add PDO
		$camaro=(is_numeric($_POST['id'])) ? ((int)$_POST['id']+54):exit();
		$mustang=(string) $_POST['name'];
		$viper=(string) $_POST['mail'];
		$charger=(is_numeric($_POST['status'])) ? (string)$_POST['status']:exit();
		$holiday=(is_numeric($_POST['holiday'])) ? (string)$_POST['holiday']:exit();
		$seldepa=$_POST['seldepa'];
		
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();	
		if($stmt){
			$query = "UPDATE ".$SupportUserTable." SET name=?,mail=?,status=?,holiday=?  WHERE id=? LIMIT 1";
			$prepared = $stmt->prepare($query);
			if($prepared){
				if($stmt->bind_param('ssssi',$mustang,$viper,$charger,$holiday, $camaro)){
					if($stmt->execute()){
						$query = "DELETE FROM ".$SupportUserPerDepaTable." WHERE user_id=?";
						$prepared = $stmt->prepare($query);
						if($prepared){
							if($stmt->bind_param('i',$camaro)){
								if($stmt->execute()){
									if($charger=='1' && count($seldepa)>0){
										$query = "INSERT INTO ".$SupportUserPerDepaTable." (`department_name`, `department_id` , `user_id`) VALUES ";
										$count=count($seldepa);
										for($i=0;$i<$count;$i++){
											if ($i!=$count-1)
												$query.='((SELECT `department_name` FROM '.$SupportDepaTable.' WHERE id='.((int)$seldepa[$i]).'),'.((int)$seldepa[$i]).','.((int)$camaro).'),';
											else
												$query.='((SELECT `department_name` FROM '.$SupportDepaTable.' WHERE id='.((int)$seldepa[$i]).'),'.((int)$seldepa[$i]).','.((int)$camaro).')';
										}
										if($stmt->prepare($query)){
											if($stmt->execute()){
												$camarolist=join(',',$seldepa);
												$query="SELECT id,department_id,user_id FROM ".$SupportTicketsTable." WHERE ticket_status='1' AND operator_id=? AND department_id NOT IN (".$camarolist.")";
												$prepared = $stmt->prepare($query);
												if($prepared){
													if($stmt->bind_param('i', $camaro)){
														if($stmt->execute()){
															$stmt->store_result();
															$result = $stmt->bind_result($camaro,$depa,$uid);
															if($stmt->num_rows>0){
																$tktoedit=array();
																while (mysqli_stmt_fetch($stmt))
																	$tktoedit[]=array($camaro,$depa,$uid);
																file_put_contents('arr',print_r($tktoedit,true));
																$query = "UPDATE ".$SupportTicketsTable." SET operator_id=0,ticket_status= CASE WHEN ticket_status='1' THEN '2' ELSE ticket_status END  WHERE department_id NOT IN (".$camarolist.")";
																if($stmt->prepare($query)){
																	if($stmt->execute()){
																		$sub=$stmt->affected_rows;
																		$query = "UPDATE ".$SupportUserTable." SET assigned_tickets=(assigned_tickets-?)  WHERE id=?";
																		if($stmt->prepare($query)){
																			if($stmt->bind_param('ii', $sub,$camaro)){
																				if($stmt->execute()){
																					foreach($tktoedit as $k=>$v){
																						$selopid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $v[1],$v[2]);
																						if(is_numeric($selopid)){
																							$fquery="UPDATE ".$SupportTicketsTable." a SET a.operator_id=IF(a.user_id=?,0,?),
																										a.ticket_status= CASE WHEN a.ticket_status='2' AND a.user_id!=? THEN '1' WHEN a.user_id=? THEN '2' ELSE a.ticket_status END  WHERE a.id=?";
																							$lquery="UPDATE ".$SupportUserTable." b SET b.assigned_tickets=IF((SELECT COUNT(*) FROM ".$SupportTicketsTable." WHERE operator_id=? LIMIT 1) IS NOT NULL,(SELECT COUNT(*) FROM ".$SupportTicketsTable." WHERE operator_id=?),0) WHERE b.id=?";
																							if($stmt->prepare($fquery)){
																								if($stmt->bind_param('iiiii', $selopid,$selopid,$selopid,$selopid,$v[0])){
																									if($stmt->execute()){;
																										if($stmt->prepare($lquery)){
																											if($stmt->bind_param('iii',$selopid,$selopid,$selopid)){
																												if(!$stmt->execute()){
																													echo json_encode(array(0=>mysqli_stmt_error($stmt)));exit();
																												}
																											}
																											else
																												{echo json_encode(array(0=>mysqli_stmt_error($stmt)));exit();}
																										}
																										else
																											{echo json_encode(array(0=>mysqli_stmt_error($stmt)));exit();}
																									}
																									else
																										{echo json_encode(array(0=>mysqli_stmt_error($stmt)));exit();}
																								}
																								else
																									{echo json_encode(array(0=>mysqli_stmt_error($stmt)));exit();}
																							}
																							else
																								{echo json_encode(array(0=>mysqli_stmt_error($stmt)));exit();}
																						}
																						else if($selopid!='No Operator Available')
																							{echo json_encode(array(0=>$selopid));exit();}
																					}
																					echo json_encode(array(0=>'Updated'));
																				}
																				else
																					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																			}
																			else
																				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																		}
																		else
																			echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																	}
																	else
																		echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																}
																else
																	echo json_encode(array(0=>mysqli_stmt_error($stmt)));
															}
															else
																echo json_encode(array(0=>'Updated'));
														}
														else
															echo json_encode(array(0=>mysqli_stmt_error($stmt)));
													}
													else
														echo json_encode(array(0=>mysqli_stmt_error($stmt)));
												}
												else
													echo json_encode(array(0=>mysqli_stmt_error($stmt)));
											}
											else
												echo json_encode(array(0=>mysqli_stmt_error($stmt)));
										}
										else
											echo json_encode(array(0=>mysqli_stmt_error($stmt)));
									}
									else if($charger!=1 && $charger!=2){
										$query = "UPDATE ".$SupportTicketsTable." SET operator_id=0,ticket_status= CASE WHEN ticket_status='1' THEN '2' ELSE ticket_status END  WHERE operator_id=?";
										$prepared = $stmt->prepare($query);
										if($prepared){
											if($stmt->bind_param('i', $camaro)){
												if($stmt->execute()){
													echo json_encode(array(0=>'Updated'));
												}
												else
													echo json_encode(array(0=>mysqli_stmt_error($stmt)));
											}
											else
												echo json_encode(array(0=>mysqli_stmt_error($stmt)));
										}
										else
											echo json_encode(array(0=>mysqli_stmt_error($stmt)));
									}
									else
										echo json_encode(array(0=>'Updated'));
								}
								else
									echo json_encode(array(0=>mysqli_stmt_error($stmt)));
							}
							else
								echo json_encode(array(0=>mysqli_stmt_error($stmt)));
						}
						else
							echo json_encode(array(0=>mysqli_stmt_error($stmt)));
					}
					else
						echo json_encode(array(0=>mysqli_stmt_error($stmt)));
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		}
		else
			echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		exit();
	}

	else if(isset($_POST['act']) && $_POST['act']=='select_depa_usr'){//check
		$camaro=(is_numeric($_POST['id'])) ? ((int)$_POST['id']+54):exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT `department_id` FROM ".$SupportUserPerDepaTable." WHERE `user_id`=? ORDER BY `department_name` ASC";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$camaro,PDO::PARAM_INT);
			$STH->execute();
			
			$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
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
			echo json_encode($ret);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if(isset($_POST['act']) && $_POST['act']=='del_usr'){//Add PDO
		$camaro=(is_numeric($_POST['id']))? (int)$_POST['id']+54:exit();
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();	
		if($stmt){
			$query = "DELETE FROM ".$SupportMessagesTable." WHERE user_id=? ";
			$prepared = $stmt->prepare($query);
			if($prepared){
				if($stmt->bind_param('i', $camaro)){
					if($stmt->execute()){
						$query = "DELETE FROM ".$SupportTicketsTable." WHERE user_id=? ";
						$prepared = $stmt->prepare($query);
						if($prepared){
							if($stmt->bind_param('i', $camaro)){
								if($stmt->execute()){
									$query = "SELECT enc FROM ".$SupportUploadTable." WHERE `uploader`=?";
									if($stmt->prepare($query)){
										if($stmt->bind_param('i', $camaro)){
											if($stmt->execute()){
												$stmt->store_result();
												$result = $stmt->bind_result($mustang);
												if($stmt->num_rows>0){
													$path='../upload/';
													while (mysqli_stmt_fetch($stmt)) {
														if(file_exists($path.$mustang)){
															file_put_contents($path.$mustang,'');
															unlink($path.$mustang);
														}
													}
												}
												$query = "DELETE FROM ".$SupportUploadTable." WHERE uploader=? ";
												$prepared = $stmt->prepare($query);
												if($prepared){
													if($stmt->bind_param('i', $camaro)){
														if($stmt->execute()){
															$query = "UPDATE ".$SupportTicketsTable." SET operator_id=0,ticket_status= CASE WHEN '1' THEN '2' ELSE ticket_status END  WHERE operator_id=?";
															$prepared = $stmt->prepare($query);
															if($prepared){
																if($stmt->bind_param('i', $camaro)){
																	if($stmt->execute()){
																		$query = "DELETE FROM ".$SupportUserPerDepaTable." WHERE user_id=? ";
																		$prepared = $stmt->prepare($query);
																		if($prepared){
																			if($stmt->bind_param('i', $camaro)){
																				if($stmt->execute()){
																					$query = "DELETE FROM ".$SupportUserTable." WHERE id=? ";
																					$prepared = $stmt->prepare($query);
																					if($prepared){
																						if($stmt->bind_param('i', $camaro)){
																							if($stmt->execute()){
																								echo json_encode(array(0=>'Deleted'));
																							}
																							else
																								echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																						}
																						else
																							echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																					}
																					else
																						echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																				}
																				else
																					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																			}
																			else
																				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																		}
																		else
																			echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																	}
																	else
																		echo json_encode(array(0=>mysqli_stmt_error($stmt)));
																}
																else
																	echo json_encode(array(0=>mysqli_stmt_error($stmt)));
															}
															else
																echo json_encode(array(0=>mysqli_stmt_error($stmt)));
														}
														else
															echo json_encode(array(0=>mysqli_stmt_error($stmt)));
													}
													else
														echo json_encode(array(0=>mysqli_stmt_error($stmt)));
												}
												else
													echo json_encode(array(0=>mysqli_stmt_error($stmt)));
											}
											else
												echo json_encode(array(0=>mysqli_stmt_error($stmt)));
										}
										else
											echo json_encode(array(0=>mysqli_stmt_error($stmt)));
									}
									else
										echo json_encode(array(0=>mysqli_stmt_error($stmt)));
								}
								else
									echo json_encode(array(0=>mysqli_stmt_error($stmt)));
							}
							else
								echo json_encode(array(0=>mysqli_stmt_error($stmt)));
						}
						else
							echo json_encode(array(0=>mysqli_stmt_error($stmt)));
					}
					else
						echo json_encode(array(0=>mysqli_stmt_error($stmt)));
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		}
		else
			echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		$mysqli->close();
		exit();
	}

	else if(isset($_POST['act']) && $_POST['act']=='automatic_assign_ticket'){//deep check
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query="SELECT id,department_id,user_id FROM ".$SupportTicketsTable." WHERE ticket_status='2'";
			$prepared = $stmt->prepare($query);
			$STH = $DBH->prepare($query);
			$STH->execute();
			
			$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
			if(!empty($r)){
				$tktoedit=array();
				while ($a = $STH->fetch()){
					$tktoedit[]=array($a['id'],$a['department_id'],$a['user_id']);
				}
				
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
				echo json_encode(array(0=>'Assigned'));
			}
			else
				echo json_encode(array(0=>'No Ticket to Assign'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if(isset($_POST['act']) && $_POST['act']=='retrive_operator_assign'){//check
		$encid=preg_replace('/\s+/','',$_POST['enc']);
		$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
		$departmentid=(is_numeric($_POST['id'])) ? $_POST['id']:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query="SELECT id,name,status FROM ((SELECT a.id,a.name,a.status  FROM ".$SupportUserTable." a WHERE  a.status='2' AND a.id!='".$_SESSION['id']."' AND a.id!='".$_SESSION[$encid]['op_id']."') UNION (SELECT a.id,a.name,a.status  FROM  ".$SupportUserTable." a LEFT JOIN  ".$SupportUserPerDepaTable." b ON a.id=b.user_id  WHERE b.department_id=? AND a.id!=".$_SESSION['id'].")) AS tab ORDER BY tab.status ASC, tab.name ASC";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$departmentid,PDO::PARAM_INT);
			$STH->execute();
			
			$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
			if(!empty($r)){
				$list=array(0=>'Ex',1=>'<option value="0">---</option>');
				while ($a = $STH->fetch()){
					$list[]='<option value="'.$a['id'].'">'.$a['name'].'</option>';
				}
				echo json_encode($list);
			}
			else
				echo json_encode(array(0=>'Unavailable'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if(isset($_POST['act']) && $_POST['act']=='move_admin_ticket'){//deep check
		$opid=(is_numeric($_POST['opid'])) ? $_POST['opid']:exit();
		$dpid=(is_numeric($_POST['dpid'])) ? $_POST['dpid']:exit();
		$encid=preg_replace('/\s+/','',$_POST['id']);
		$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			if($opid==-1){
				$opid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $dpid,$_SESSION[$encid]['usr_id']);
				if(!is_numeric($opid))
					$opid=0;
			}
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
			$STH->bindParam(1,$opid,PDO::PARAM_INT);
			$STH->bindParam(2,$dpid,PDO::PARAM_INT);
			$STH->bindParam(3,$opid,PDO::PARAM_INT);
			$STH->bindParam(4,$opid,PDO::PARAM_INT);
			$STH->bindParam(5,$opid,PDO::PARAM_INT);
			$STH->bindParam(6,$opid,PDO::PARAM_INT);
			$STH->bindParam(7,$encid,PDO::PARAM_INT);
			$STH->execute();
			
			if($opid>0)
				echo json_encode(array(0=>'AMoved'));
			else
				echo json_encode(array(0=>'No Operator Available'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}

	else if(isset($_POST['act']) && $_POST['act']=='delete_files'){//Add PDO
		$from=$_POST['from']." 00:00:00";
		$to=$_POST['to']." 23:59:59";
		$query = "SELECT enc,message_id FROM ".$SupportUploadTable." WHERE `upload_date` BETWEEN ? AND ? ";
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();	
		if($stmt){
			if($prepared = $stmt->prepare($query)){
				if($stmt->bind_param('ss', $from,$to)){
					if($stmt->execute()){
						$stmt->store_result();
						$result = $stmt->bind_result($mustang,$mush);
						if($stmt->num_rows>0){
							$path='../upload/';
							$list=array();
							while (mysqli_stmt_fetch($stmt)) {
								if(file_exists($path.$mustang)){
									file_put_contents($path.$mustang,'');
									unlink($path.$mustang);
									$list[]=$mush;
								}
							}
							$query = "DELETE FROM ".$SupportUploadTable." WHERE `upload_date` BETWEEN ? AND ?";
							if($stmt->prepare($query)){
								if($stmt->bind_param('ss', $from,$to)){
									if($stmt->execute()){
										$c=count($list);
										$list=implode(',',$list);
										$query = "UPDATE ".$SupportMessagesTable." SET attachment='0' WHERE id IN (?) LIMIT ?";
										if($stmt->prepare($query)){
											if($stmt->bind_param('si', $list,$c)){
												if($stmt->execute()){
													echo json_encode(array(0=>'Deleted'));
												}
												else
													echo json_encode(array(0=>mysqli_stmt_error($stmt)));
											}
											else
												echo json_encode(array(0=>mysqli_stmt_error($stmt)));
										}
										else
											echo json_encode(array(0=>mysqli_stmt_error($stmt)));
									}
									else
										echo json_encode(array(0=>mysqli_stmt_error($stmt)));
								}
								else
									echo json_encode(array(0=>mysqli_stmt_error($stmt)));
							}
							else
								echo json_encode(array(0=>mysqli_stmt_error($stmt)));
						}
						else
							echo json_encode(array(0=>'There is no Uploaded Files inside this period'));
					}
					else
						echo json_encode(array(0=>mysqli_stmt_error($stmt)));
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		}
		else
			echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		$mysqli->close();
		exit();
	}
	
	else if(isset($_POST['act']) && $_POST['act']=='retrive_faq'){//check
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT id,question,position,CASE active WHEN 0 THEN 'No' ELSE 'Yes' END AS ac,CASE rate WHEN 0 THEN 'Unrated' ELSE rate END AS rat FROM ".$SupportFaqTable;
			$STH = $DBH->prepare($query);
			$STH->execute();
			
			$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
			$list=array('response'=>'ret','faq'=>array());
			if(!empty($r)){
				while ($a = $STH->fetch()){
					$list['faq'][]=array('id'=>$a['id']-14,'question'=>$a['question'],'position'=>$a['position'],'active'=>$a['ac'],'rate'=>$a['rat']);
				}
			}
			echo json_encode($list);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if(isset($_POST['act'])  && $_POST['act']=='add_faq'){//check
	
		$question=(preg_replace('/\s+/','',$_POST['question'])!='')? preg_replace('/\s+/',' ',$_POST['question']):exit();
		$answer=(preg_replace('/\s+/','',$_POST['answer'])!='')? preg_replace('/\s+/',' ',$_POST['answer']):exit();
		$pos=(is_numeric($_POST['pos']))? $_POST['pos']:NULL;
		$active=(is_numeric($_POST['active']))? $_POST['active']:exit();
		
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();	
		if($stmt){
			$query = "INSERT INTO ".$SupportFaqTable." (`question`,`answer`,`active`,`position`) 
						VALUES (?,?,?,CASE WHEN ? IS NULL THEN (IF ((SELECT MAX(c.position) FROM ".$SupportFaqTable." c ) IS NOT NULL,(SELECT MAX(d.position) FROM ".$SupportFaqTable." d )+1,0)) ELSE ? END)";
			$prepared = $stmt->prepare($query);
			if($prepared){
				if($stmt->bind_param('sssii', $question,$answer,$active,$pos,$pos)){
					if($stmt->execute()){
						$data=array();
						$data['response']='Added';
						$dpid=$stmt->insert_id;
						$active=((int)$active==0) ? 'No':'Yes';
						$data['information']=array('id'=>$dpid,'question'=>$question,'position'=>$pos,'active'=>$active);
						if($pos==NULL){
							
							$query = "SELECT `position` FROM ".$SupportFaqTable." WHERE `id`='".$dpid."' LIMIT 1";
							$prepared = $stmt->prepare($query);
							if($prepared){
								if($stmt->execute()){
									$stmt->store_result();
									$result = $stmt->bind_result($p);
									while (mysqli_stmt_fetch($stmt))
										$data['information']['position']=$p;
								}
								else
									echo json_encode(array(0=>mysqli_stmt_error($stmt)));
							}
							else
								echo json_encode(array(0=>mysqli_stmt_error($stmt)));
						}
						echo json_encode($data);
					}
					else
						echo json_encode(array(0=>mysqli_stmt_error($stmt)));
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		}
		else
			echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		$mysqli->close();
		exit();
	}
	
	else if(isset($_POST['act'])  && $_POST['act']=='del_faq'){//check
		$camaro=(is_numeric($_POST['id']))? $_POST['id']+14:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query="DELETE FROM ".$SupportRateFaqTable." WHERE `faq_id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$camaro,PDO::PARAM_INT);
			$STH->execute();
			
			$query="DELETE FROM ".$SupportFaqTable." WHERE `id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(PARAM_INT);
			$STH->execute();
			
			echo json_encode(array(0=>'Deleted'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if(isset($_POST['act'])  && $_POST['act']=='edit_faq'){//check
		$camaro=(is_numeric($_POST['id']))? $_POST['id']+14:exit();
		$question=(preg_replace('/\s+/','',$_POST['question'])!='')? preg_replace('/\s+/',' ',$_POST['question']):exit();
		$answer=(preg_replace('/\s+/','',$_POST['answer'])!='')? preg_replace('/\s+/',' ',$_POST['answer']):exit();
		$pos=(is_numeric($_POST['position']))? $_POST['position']:NULL;
		$active=(is_numeric($_POST['active']))? $_POST['active']:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			if($pos==NULL){
				try{
					$query = "SELECT (IF ((SELECT c.id FROM ".$SupportFaqTable." c LIMIT 1) IS NOT NULL,(SELECT MAX(d.position) FROM ".$SupportFaqTable." d )+1,0)) AS rpos FROM ".$SupportFaqTable;
					
					$STH = $DBH->prepare($query);
					$STH->execute();
					
					$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
					if(!empty($r)){
						while ($a = $STH->fetch()){
							$pos=$a['rpos'];
						}
					}
				}
				catch(PDOException $e){
					file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
				}
			}
			$query = "UPDATE ".$SupportFaqTable."
						SET question=?,
							answer=?,
							position=?,
							active=? 
						WHERE id=? ";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$question,PDO::PARAM_STR);
			$STH->bindParam(2,$answer,PDO::PARAM_STR);
			$STH->bindParam(3,$pos,PDO::PARAM_INT);
			$STH->bindParam(4,$active,PDO::PARAM_STR);
			$STH->bindParam(5,$camaro,PDO::PARAM_INT);
			$STH->execute();
			echo json_encode(array(0=>'Succeed',1=>$pos));
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if(isset($_POST['act']) && $_POST['act']=='retrive_faq_answer'){//check
		$cs=(is_numeric($_POST['id']))? $_POST['id']+14:exit();
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$query = "SELECT answer FROM ".$SupportFaqTable." WHERE id=? LIMIT 1";
			
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$cs,PDO::PARAM_INT);
			$STH->execute();
			
			$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
			$list=array(0=>'ret');
			if(!empty($r)){
				while ($a = $STH->fetch()){
					$list[]=html_entity_decode($a['answer']);
				}
			}
			echo json_encode($list);
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		exit();
	}
	
	else if(isset($_POST['act'])  && $_POST['act']=='rem_flag'){//check
		$encid=preg_replace('/\s+/','',$_POST['id']);
		$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
		
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query="DELETE FROM ".$SupportFlagTable." WHERE `enc_id`=?";
			$STH = $DBH->prepare($query);
			$STH->bindParam(1,$encid,PDO::PARAM_STR);
			$STH->execute();
			echo json_encode(array(0=>'Deleted'));
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
			echo json_encode(array(0=>'An Error has occurred, please read the PDOErrors file and contact a programmer'));
		}
		
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
			$prepared = $stmt->prepare($query);
			$STH = $DBH->prepare($query);
			$STH->execute();
			$r=$STH->setFetchMode(PDO::FETCH_ASSOC);
			if(!empty($r)){
				$b=array();
				while ($a = $STH->fetch()){
					$b[$a['id']]=$a['department_name'];
				}
				return json_encode($b);
			}
		}
		catch(PDOException $e){  
			file_put_contents('PDOErrors', $e->getMessage()."\n", FILE_APPEND);
		}
	}
}

function retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable,$dep,$nope){// Add PDO, Switch SESSION with return
	$query = "SELECT *
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

	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	$prepared = $stmt->prepare($query);
	if($prepared){
		if($stmt->bind_param('i', $dep)){
			if($stmt->execute()){
				$stmt->store_result();
				$operator=array();
				$result = $stmt->bind_result($camaro);
				if($stmt->num_rows>0){
					while (mysqli_stmt_fetch($stmt))
						$selopid=$camaro;
					return $selopid;
				}
				else
					return 'No Operator Available';
			}
			else
				return mysqli_stmt_error($stmt);
		}
		else
			return mysqli_stmt_error($stmt);
	}
	else
		return mysqli_stmt_error($stmt);
}
?>