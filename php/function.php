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

//Function
if(isset($_POST['act']) && $_POST['act']=='register'){
	if($_POST['pwd']==$_POST['rpwd']){
		$mustang=(preg_replace('/\s+/','',$_POST['name'])!='') ? (string)$_POST['name']:exit();
		$viper= preg_replace('/\s+/','',$_POST['mail']);
		$viper=($viper!='') ? $viper:exit();
		$pass=hash('whirlpool',crypt($_POST['pwd'],'$#%H4!df84a$%#RZ@�'));
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			$query = "INSERT INTO ".$SupportUserTable." (`name`,`reg_key`,`mail`,`password`,`ip_address`) VALUES (?,?,?,?,?) ";
			if($prepared = $stmt->prepare($query)){
				$ip=retrive_ip();
				$reg=get_random_string(60);
				if($stmt->bind_param('sssss',$mustang, $reg,$viper,$pass,$ip)){
					if($stmt->execute()){
						$_SESSION['id']=$stmt->insert_id;
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
						echo json_encode(array(0=>'Registred'));
					}
					else{
						if((int)$stmt->errno==1062)
							echo json_encode(array(0=>"User with mail: ".$viper." is already registred"));
						else
							echo json_encode(array(0=>mysqli_stmt_error($stmt)));
					}
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
	}
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']==3 && $_POST['act']=='send_again'){
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			$query = "UPDATE ".$SupportUserTable." SET  reg_key=? WHERE id=? ";
			if($prepared = $stmt->prepare($query)){
				$ip=retrive_ip();
				$reg=get_random_string(60);
				if($stmt->bind_param('si',$reg,$_SESSION['id'])){
					if($stmt->execute()){
						$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
						$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php NewMem ".$_SESSION['id']." ";
						if(substr(php_uname(), 0, 7) == "Windows")
							pclose(popen("start /B ".$ex,"r")); 
						else
							shell_exec($ex." > /dev/null 2>/dev/null &");
						echo json_encode(array(0=>'Sent'));
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

else if(isset($_POST['act']) && !isset($_SESSION['status']) && $_POST['act']=='login'){
	$viper=(preg_replace('/\s+/','',$_POST['mail'])!='') ? (string)$_POST['mail']:exit();
	$pass=hash('whirlpool',crypt($_POST['pwd'],'$#%H4!df84a$%#RZ@�'));
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
    if($stmt){
		$query = "SELECT `id`,`name`,`mail`,`status`,`mail_alert` FROM ".$SupportUserTable." WHERE `mail`=?  AND `password`= ? LIMIT 1";
		$prepared = $stmt->prepare($query);
		if($prepared){
			if($stmt->bind_param('ss', $viper,$pass)){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result($sessionid, $aventador, $rmail, $st, $alert);
					if($stmt->num_rows>0){
						while (mysqli_stmt_fetch($stmt)) {
							$_SESSION['time']=time();
							$_SESSION['id']=$sessionid;
							$_SESSION['name']=$aventador;
							$_SESSION['mail']=$rmail;
							$_SESSION['status']=$st;
							$_SESSION['mail_alert']=$alert;
							$_SESSION['ip']=retrive_ip();
						}
						echo json_encode(array(0=>'Logged'));
					}
					else
						echo json_encode(array(0=>'Wrong Credentials'));
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

else if(isset($_POST['act']) && $_SESSION['status']<3 && $_POST['act']=='delete_ticket'){
		$encid=preg_replace('/\s+/','',$_POST['enc']);
		$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			$query = "UPDATE ".$SupportTicketsTable." a
						INNER JOIN ".$SupportUserTable." b
							ON b.id=a.operator_id
						SET b.assigned_tickets= CASE  WHEN a.ticket_status!='0' THEN (b.assigned_tickets-1) ELSE b.assigned_tickets END  
					WHERE a.enc_id=?";
			if($prepared = $stmt->prepare($query)){
				if($stmt->bind_param('s', $encid)){
					if($stmt->execute()){
						$query = "DELETE FROM ".$SupportMessagesTable." WHERE `ticket_id`=(SELECT `id` FROM ".$SupportTicketsTable." WHERE `enc_id`=?) ";
						if($prepared = $stmt->prepare($query)){
							if($stmt->bind_param('s', $encid)){
								if($stmt->execute()){
									$query = "SELECT enc FROM ".$SupportUploadTable." WHERE `ticket_id`=?";
									if($prepared = $stmt->prepare($query)){
										if($stmt->bind_param('s', $encid)){
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
												$query = "DELETE FROM ".$SupportUploadTable." WHERE `ticket_id`=?";
												if($prepared = $stmt->prepare($query)){
													if($stmt->bind_param('s', $encid)){
														if($stmt->execute()){
															$query = "DELETE FROM ".$SupportFlagTable." WHERE `enc_id`=?";
															if($prepared = $stmt->prepare($query)){
																if($stmt->bind_param('s', $encid)){
																	if($stmt->execute()){
																		$query = "DELETE FROM ".$SupportTicketsTable." WHERE `enc_id`=?";
																		if($prepared = $stmt->prepare($query)){
																			if($stmt->bind_param('s', $encid)){
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
	exit();
}
	
else if(isset($_POST['act']) && isset($_POST['key']) && $_POST['act']=='activate_account'){
	$key=preg_replace('/\s+/','',$_POST['key']);
	if(60!=strlen($key)){
		echo json_encode(array(0=>'Invalid Key'));
		exit();
	}
	else{
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			$query = "SELECT `id`,`name`,`mail`,`mail_alert` FROM ".$SupportUserTable." WHERE `reg_key`=? LIMIT 1";
			$prepared = $stmt->prepare($query);
			if($prepared){
				if($stmt->bind_param('s', $key)){
					if($stmt->execute()){
						$stmt->store_result();
						$result = $stmt->bind_result($sessionid,$aventador,$rmail,$alert);
						if($stmt->num_rows>0){
							while (mysqli_stmt_fetch($stmt)) {
								$_SESSION['id']=$sessionid;
								$_SESSION['name']=$aventador;
								$_SESSION['mail']=$rmail;
								$_SESSION['mail_alert']=$alert;
								$_SESSION['ip']=retrive_ip();
							}
							$query = "UPDATE ".$SupportUserTable." SET status='0',reg_key='' WHERE `id`=?";
							$prepared = $stmt->prepare($query);
							if($prepared){
								if($stmt->bind_param('i', $_SESSION['id'])){
									if($stmt->execute()){
										$_SESSION['status']=0;
										$_SESSION['time']=time();
										echo json_encode(array(0=>'Activated'));
									}
									else
										echo json_encode(array(0=>'Cannot update Status, please contact the administrator. Error: '.mysqli_stmt_error($stmt)));
								}
								else
									echo json_encode(array(0=>mysqli_stmt_error($stmt)));
							}
							else
								echo json_encode(array(0=>mysqli_stmt_error($stmt)));
						}
						else
							echo json_encode(array(0=>'No Key Match'));
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
	}
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']>2 && $_POST['act']=='verify'){
	if(!isset($_SESSION['cktime']) || ($_SESSION['cktime']-time())>300){
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			$query = "SELECT `status` FROM ".$SupportUserTable." WHERE `mail`=?  AND `id`= ? LIMIT 1";
			$prepared = $stmt->prepare($query);
			if($prepared){
				if($stmt->bind_param('si', $_SESSION['mail'],$_SESSION['id'])){
					if($stmt->execute()){
						$stmt->store_result();
						$result = $stmt->bind_result($st);
						if($stmt->num_rows>0){
							while(mysqli_stmt_fetch($stmt)){
								if($st!=$_SESSION['status']){
									$_SESSION['status']=$st;
									echo json_encode(array(0=>"Load"));
								}
								else{
									$_SESSION['cktime']=time();
									echo json_encode(array(0=>'Time'));
								}
							}
						}
						else
							echo json_encode(array(0=>'Wrong Credentials'));
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
	}
	exit();
}

else if(isset($_POST['act']) && $_POST['act']=='forgot'){
	$viper=$_POST['mail'];
	$mustang=$_POST['name'];
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
    if($stmt = $mysqli->stmt_init()){
		$query = "SELECT `id` FROM ".$SupportUserTable." WHERE mail=? AND name=? LIMIT 1";
		if($prepared = $stmt->prepare($query)){
			if($stmt->bind_param('ss', $viper,$mustang)){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result($camaro);
					if($stmt->num_rows>0){
						while(mysqli_stmt_fetch($stmt))
							$camaro=$camaro;
						$query = "UPDATE ".$SupportUserTable." SET tmp_password=? WHERE id=?";
						if($prepared = $stmt->prepare($query)){
							$rands=uniqid(hash('sha256',get_random_string(60)),true);
							if($stmt->bind_param('si', $rands,$camaro)){
								if($stmt->execute()){
									$setting[8]=(isset($setting[8]))? $setting[8]:'php5-cli';
									$ex=$setting[8]." ".dirname(__FILE__)."/sendmail.php Forgot ".$camaro." ".$rands;
									if(substr(php_uname(), 0, 7) == "Windows")
										pclose(popen("start /B ".$ex,"r")); 
									else
										shell_exec($ex." > /dev/null 2>/dev/null &");
									echo json_encode(array(0=>'Reset'));
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
						echo json_encode(array(0=>'Wrong Credential'));
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

else if(isset($_POST['act']) && $_POST['act']=='reset_password'){
	$npwd=(string)$_POST['npass'];
	$rpwd=(string)$_POST['rnpass'];
	$rmail=(string)$_POST['rmail'];
	if(preg_replace('/\s+/','',$rpwd)!='' && $rpwd==$npwd){
		$pass=hash('whirlpool',crypt($rpwd,'$#%H4!df84a$%#RZ@�'));
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		if($stmt = $mysqli->stmt_init()){
			$query = "UPDATE ".$SupportUserTable." SET password=?,tmp_password=NULL WHERE mail=? AND tmp_password=?";
			if($prepared = $stmt->prepare($query)){
				if($stmt->bind_param('sss', $pass,$rmail,$_POST['key'])){
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
			echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		$stmt->close();
		$mysqli->close();
	}
	else
		echo json_encode(array(0=>'Password Mismatch'));
	exit();
}

else if(isset($_POST['createtk']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['createtk']=='Create New Ticket'){
	$letarr=array('M','d','C','f','K','w','p','T','B','X');
	$error=array();
	if(preg_replace('/\s+/','',strip_tags($_POST['message']))!='')
		$message=preg_replace('/\s+/',' ',preg_replace('/\r\n|[\r\n]/','<br/>',$_POST['message']));
		//$message=preg_replace('/\s+/',' ',$_POST['message']);
	else
		$error[]='Empty Message';

	if(preg_replace('/\s+/','',$_POST['title'])!='')
		$tit=preg_replace('/\s+/',' ',$_POST['title']);
	else
		$error[]='Empty Title';
		
	if(is_numeric($_POST['dep']))
		$dep=(int)$_POST['dep'];
	else
		$error[]='Error ';

	if(is_numeric($_POST['priority']))
		$prio=$_POST['priority'];
	else
		$error[]='Error ';

	if(!isset($error[0])){
		$wsurl=(preg_replace('/\s+/','',$_POST['wsurl'])!='')? preg_replace('/\s+/',' ',$_POST['wsurl']):'';
		$contype=(is_numeric($_POST['contype']))? (int)$_POST['contype']:exit();
		$ftppass=(preg_replace('/\s+/','',$_POST['ftppass'])!='')? $_POST['ftppass']:'';
		$ftpus=(preg_replace('/\s+/','',$_POST['ftpus'])!='')? preg_replace('/\s+/',' ',$_POST['ftpus']):'';
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
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			//Create Ticket
			$query = "INSERT INTO ".$SupportTicketsTable."(`department_id`,`user_id`,`title`,`priority`,`website`,`contype`,`ftp_user`,`ftp_password`,`created_time`,`last_reply`) VALUES (?,?,?,?,?,?,?,?,?,?)";
			$prepared = $stmt->prepare($query);
			if($prepared){
				$date=date("Y-m-d H:i:s");
				if($stmt->bind_param('iisissssss', $dep,$_SESSION['id'],$tit,$prio,$wsurl,$contype,$ftpus,$ftppass,$date,$date)){
					if($stmt->execute()){
						echo '<script>parent.$(".main").nimbleLoader("show", {position : "fixed",loaderClass : "loading_bar_body",debug : true,hasBackground : true,zIndex : 999,backgroundColor : "#fff",backgroundOpacity : 0.9});</script>';
						//Assign Reference Number
						$tkid=$stmt->insert_id;
						$ip=retrive_ip();
						$refid=uniqid(hash('sha256',$tkid.$tit.$_SESSION['id']),true);
						$randomref=get_random_string(6);
						$spadd=str_split(strrev($_SESSION['id'].''));
						$lll=count($spadd);
						for($i=0;$i<$lll;$i++) $spadd[$i]=$letarr[$spadd[$i]];
						$randomref=implode('',$spadd).$randomref;
						$query = "UPDATE ".$SupportTicketsTable." SET enc_id=?,ref_id=? WHERE id=? ";
						if($prepared = $stmt->prepare($query)){
							if($stmt->bind_param('ssi', $refid,$randomref,$tkid)){
								if($stmt->execute()){
									//Insert Message
									$query = "INSERT INTO ".$SupportMessagesTable."(`user_id`,`message`,`ticket_id`,`ip_address`,`created_time`) VALUES (?,?,?,?,?);";
									if($prepared = $stmt->prepare($query)){
										if($stmt->bind_param('isiss', $_SESSION['id'],$message,$tkid,$ip,$date)){
											if($stmt->execute()){
												//File Upload
												if(isset($setting[5]) && $setting[5]==1){
													$msid=$stmt->insert_id;
													if(isset($_FILES['filename'])){
														$count=count($_FILES['filename']['name']);
														if($count>0){
															echo '<script>parent.noty({text: "File Upload Started",type:"information",timeout:2000});</script>';
															if(!is_dir('../upload')) mkdir('../upload');
															$query="INSERT INTO ".$SupportUploadTable." (`name`,`enc`,`uploader`,`num_id`,`ticket_id`,`message_id`,`upload_date`) VALUES ";
															$date=date("Y-m-d H:i:s");
															$uploadarr=array();
															$movedfiles=array();
															for($i=0;$i<$count;$i++){
																if($_FILES['filename']['error'][$i]==0){
																	if($_FILES['filename']['size'][$i]<=$maxsize && $_FILES['filename']['size'][$i]!=0){
																		if(count(array_keys($movedfiles,$_FILES['filename']['name'][$i]))==0){
																			$encname=uniqid(hash('sha256',$msid.$_FILES['filename']['name'][$i]),true);
																			$target_path = "../upload/".$encname;
																			if(move_uploaded_file($_FILES['filename']['tmp_name'][$i], $target_path)){
																				if(CryptFile("../upload/".$encname)){
																					$movedfiles[]=$_FILES['filename']['name'][$i];
																					$uploadarr[]=array($encid,$encname,$_FILES['filename']['name'][$i]);
																					$query.='("'.$_FILES['filename']['name'][$i].'","'.$encname.'","'.$_SESSION['id'].'",'.$tkid.',"'.$refid.'","'.$msid.'","'.$date.'"),';
																					echo '<script>parent.noty({text: "'.$_FILES['filename']['name'][$i].' has been uploaded",type:"success",timeout:2000});</script>';
																				}
																			}
																		}
																	}
																	else
																		echo '<script>parent.noty({text: "The file '.$_FILES['filename']['name'][$i].' is too big or null. Max file size: '.ini_get('upload_max_filesize').'",type:"error",timeout:9000});</script>';
																}
																else if($_FILES['filename']['error'][$i]!=4)
																		echo '<script>parent.noty({text: "File Name:'.$_FILES['filename']['name'][$i].' Error Code:'.$_FILES['filename']['error'][$i].'",type:"error",timeout:9000});</script>';
																}
																if(isset($uploadarr[0])){
																	$query=substr_replace($query,'',-1);
																	if($stmt->prepare($query)){
																		if($stmt->execute()){
																			$query="UPDATE ".$SupportMessagesTable." SET attachment='1' WHERE id=?";
																			$prepared = $stmt->prepare($query);
																			if($prepared){
																				if($stmt->bind_param('i', $msid)){
																					if($stmt->execute()){
																						echo json_encode(array(0=>'Updated',1=>$tit));
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
																		else{
																			echo '<script>parent.noty({text: "Upload Execute Insert Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
																		}
																	}
																	else
																		echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "Prepare Upload Insert Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
																}
																else
																	echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: Prepare Upload Error: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
																echo '<script>parent.noty({text: "File Upload Finished",type:"information",timeout:2000});</script>';
															}
														}
														
													}
													//Assign Ticket
													$selopid=retrive_avaible_operator($Hostname, $Username, $Password, $DatabaseName, $SupportUserPerDepaTable, $SupportUserTable, $dep,$_SESSION['id']);
													$selopid=(is_numeric($selopid))?$selopid:null;
													if(is_numeric($selopid)){
														$query = "UPDATE ".$SupportTicketsTable." a ,".$SupportUserTable." b SET a.operator_id=?,a.ticket_status='1',b.assigned_tickets=(b.assigned_tickets+1) WHERE a.id=? AND b.id=? ";
														if($prepared = $stmt->prepare($query)){
															if($stmt->bind_param('iii', $selopid,$tkid,$selopid)){
																if($stmt->execute()){
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
																	echo "<script>parent.$('.main').nimbleLoader('hide');parent.created();</script>";
																}
																else
																	echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
															}
															else
																echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
														}
														else
															echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
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
												}
												else
													echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
											}
											else
												echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
										}
										else
											echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
									}
									else
										echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
								}
								else
									echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
							}
							else
								echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
						}
						else{
							if((int)$stmt->errno==1062)
								echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "You have already created a Ticket named: '.$tit.'",type:"error",timeout:9000});</script>';
							else
								echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "Ticket Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
						}
					}
					else
						echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "Ticket Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
				}
				else
					echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "Ticket Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
			}
			else
				echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "Ticket Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
			$mysqli->close();
	}
	else
		echo '<script>parent.$(".main").nimbleLoader("hide");parent.noty({text: "'.implode(',',$error).'",type:"error",timeout:9000})</script>';
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_POST['act']=='logout'){
	session_unset();
	session_destroy();
	echo json_encode(array(0=>'logout'));
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='retrive_depart'){
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
		if($_POST['sect']=='new' && $_SESSION['status']==0)
			$query = "SELECT * FROM ".$SupportDepaTable." WHERE active='1' AND public_view='1'";
		else if($_POST['sect']=='new' && $_SESSION['status']!=0)
			$query = "SELECT * FROM ".$SupportDepaTable." WHERE active='1' ";
		else if($_POST['sect']=='admin' && $_SESSION['status']==2)
			$query = "SELECT id,department_name,CASE active WHEN '1' THEN 'Yes' ELSE 'No' END, CASE public_view WHEN '1' THEN 'Yes' ELSE 'No' END FROM ".$SupportDepaTable;
		else
			exit();
		$prepared = $stmt->prepare($query);
		if($prepared){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result($dpaidn, $dname, $active, $public);
					if($stmt->num_rows>0){
						$dn=array('response'=>'ret','information'=>array());
						if($_POST['sect']=='new'){
							while (mysqli_stmt_fetch($stmt))
								$dn['information'][]="<option value='".$dpaidn."'>".$dname."</option>";
						}
						else if($_POST['sect']=='admin' && $_SESSION['status']==2){
							while (mysqli_stmt_fetch($stmt))
								$dn['information'][]=array('id'=>$dpaidn,'name'=>$dname,'active'=>$active,'public'=>$public);
						}
						echo json_encode($dn);
					}
					else
						echo json_encode(array('response'=>array('empty'),'information'=>array()));
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

else if(isset($_POST['act']) && isset($_SESSION['status'])  && $_SESSION['status']<3 && $_POST['act']=='retrive_tickets'){
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
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
			$prepared = $stmt->prepare($query);
			if($prepared){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result($encid, $depname, $opname, $title, $priority, $dat, $last, $tkstat);
					$list=array('response'=>'ret','tickets'=>array('user'=>array()));
					if($stmt->num_rows>0){
						while (mysqli_stmt_fetch($stmt)) {
							$list['tickets']['user'][]=array('id'=>$encid,'dname'=>$depname,'opname'=>$opname,'title'=>$title,'priority'=>$priority,'date'=>$dat,'reply'=>$last,'status'=>$tkstat);
						}
					}
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
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
			if($stmt->prepare($query)){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result( $encid, $depname, $opname,$opid, $title, $priority, $dat, $last, $tkstat);
					$list=array('response'=>'ret','tickets'=>array('user'=>array(),'op'=>array()));
					if($stmt->num_rows>0){
						while (mysqli_stmt_fetch($stmt)) {
							if($opid==$_SESSION['id'])
								$list['tickets']['op'][]=array('id'=>$encid,'dname'=>$depname,'opname'=>$opname,'title'=>$title,'priority'=>$priority,'date'=>$dat,'reply'=>$last,'status'=>$tkstat);
							else
								$list['tickets']['user'][]=array('id'=>$encid,'dname'=>$depname,'opname'=>$opname,'title'=>$title,'priority'=>$priority,'date'=>$dat,'reply'=>$last,'status'=>$tkstat);
						}
					}
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		}
		else if($_SESSION['status']==2){
			$query = "SELECT 
							a.user_id,
							a.enc_id,
							IF(b.department_name IS NOT NULL, b.department_name,'Unknown') ,
							CASE WHEN a.operator_id=".$_SESSION['id']." THEN '".$_SESSION['name']."' ELSE ( IF(c.name IS NOT NULL, c.name,IF(a.ticket_status='2','Not Assigned','Unknown')) ) END,
							a.operator_id,
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
						ORDER BY a.last_reply DESC 
						LIMIT 350";
			if($stmt->prepare($query)){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result($userid,$encid, $depname, $opname, $opid, $title, $priority, $dat, $last, $tkstat);
					$list=array('response'=>'ret','tickets'=>array('user'=>array(),'op'=>array(),'admin'=>array()));
					if($stmt->num_rows>0){
						while (mysqli_stmt_fetch($stmt)){
							if($opid==$_SESSION['id'])
								$list['tickets']['op'][]=array('id'=>$encid,'dname'=>$depname,'opname'=>$opname,'title'=>$title,'priority'=>$priority,'date'=>$dat,'reply'=>$last,'status'=>$tkstat);
							else if($userid==$_SESSION['id'])
								$list['tickets']['user'][]=array('id'=>$encid,'dname'=>$depname,'opname'=>$opname,'title'=>$title,'priority'=>$priority,'date'=>$dat,'reply'=>$last,'status'=>$tkstat);
							else
								$list['tickets']['admin'][]=array('id'=>$encid,'dname'=>$depname,'opname'=>$opname,'title'=>$title,'priority'=>$priority,'date'=>$dat,'reply'=>$last,'status'=>$tkstat);
						}
					}
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		}
		if(isset($list))
			echo json_encode($list);
	}
	else
		echo json_encode(array(0=>mysqli_stmt_error($stmt)));
	$mysqli->close();
	exit();
}

else if(isset($_POST['action']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['action']=='scrollpagination'){
	
	$offset = is_numeric($_POST['offset']) ? $_POST['offset'] : exit();
	$postnumbers = is_numeric($_POST['number']) ? $_POST['number'] : exit();
	$encid=preg_replace('/\s+/','',$_POST['id']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:'';
	if(isset($_SESSION[$encid]['id']) && $encid!='' ){
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		$query = "SELECT 
						a.id,
						IF(b.name IS NOT NULL,b.name,'Unknown'),
						a.message,
						a.created_time,
						a.attachment 
					FROM ".$SupportMessagesTable." a
					LEFT JOIN ".$SupportUserTable." b
						ON b.id=a.user_id
					WHERE `ticket_id`=? ORDER BY `created_time` DESC LIMIT ".$offset.",".$postnumbers;
		$prepared = $stmt->prepare($query);
		if($prepared){
			if($stmt->bind_param('s', $_SESSION[$encid]['id'])){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result($msid,$camaro, $message, $time, $attch);
					if($stmt->num_rows>0){
						$ret=array('ret'=>'Entry','messages'=>array());
						$messageid=array();
						$count=0;
						while (mysqli_stmt_fetch($stmt)){
							$ret['messages'][$msid]=array($camaro,$message,$time);
							if($attch==1)
								$messageid[]=$msid;
							$count++;
						}
						if(count($messageid)>0){
							$messageid=implode(',',$messageid);
							$query = "SELECT `name`,`enc`,`message_id` FROM ".$SupportUploadTable." WHERE message_id IN (".$messageid.")";
							$prepared = $stmt->prepare($query);
							if($prepared){
								if($stmt->execute()){
									$stmt->store_result();
									$result = $stmt->bind_result($mustang, $enc, $msid);
									if($stmt->num_rows>0){
										while (mysqli_stmt_fetch($stmt))
											$ret['messages'][$msid][]='<div class="row-fluid"><div class="span2 offset2"><form method="POST" action="../php/function.php" target="hidden_upload" enctype="multipart/form-data"><input type="hidden" name="ticket_id" value="'.$encid.'"/><input type="hidden" name="file_download" value="'.$enc.'"/><input type="submit" class="btn btn-link download" value="'.$mustang.'"></form></div></div>';
									}
								}
								else
									$error=mysqli_stmt_error($stmt);
							}
							else
								$error=mysqli_stmt_error($stmt);
						}
						$ret['messages']=array_values($ret['messages']);
						echo json_encode($ret);
					}
					else
						echo json_encode(array('ret'=>'End'));
				}
				else
					echo json_encode(array('ret'=>mysqli_stmt_error($stmt)));
			}
		}
		else
			echo json_encode(array('ret'=>mysqli_stmt_error($stmt)));
		$mysqli->close();
	}
	else
		echo json_encode(array('Error'=>'FATAL ERROR'));
	unset($_POST['action']);
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='save_setting'){
	$mustang=(preg_replace('/\s+/','',$_POST['name'])!='') ? (string)$_POST['name']:exit();
	$alert=($_POST['almail']!='no') ? 'yes':'no';
	$dfmail=(preg_replace('/\s+/','',$_POST['mail'])!='') ? (string)$_POST['mail']:exit();
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
		if(isset($_POST['oldpwd']) && isset($_POST['nldpwd']) && isset($_POST['rpwd']) && $_POST['nldpwd']==$_POST['rpwd']){
			$opass=hash("whirlpool",crypt($_POST['oldpwd'],'$#%H4!df84a$%#RZ@�'));
			$query = "SELECT `id` FROM ".$SupportUserTable." WHERE `password`= ? LIMIT 1";
			$prepared = $stmt->prepare($query);
			if($prepared){
				if($stmt->bind_param('s', $opass)){
					if($stmt->execute()){
						$stmt->store_result();
						$result = $stmt->bind_result($camaro);
						if($stmt->num_rows>0){
							while (mysqli_stmt_fetch($stmt)) {
								$camaroret=$camaro;
							}
							if($camaroret==$_SESSION['id']){
								$pass=hash("whirlpool",crypt($_POST['nldpwd'],'$#%H4!df84a$%#RZ@�'));
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
					else
						echo json_encode(array(0=>mysqli_stmt_error($stmt)));
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		}
		else{
			$query = "UPDATE ".$SupportUserTable." SET `name`=?, `mail`=?, `mail_alert`=? WHERE id=".$_SESSION['id'];
			$check=true;
		}
		if(isset($check) && $check==true){
			$prepared = $stmt->prepare($query);
			if($prepared){
				if(isset($passupd) && $passupd==true){
					unset($passupd);
					$bind=$stmt->bind_param('ssss', $mustang,$dfmail,$alert,$pass);
				}
				else
					$bind=$stmt->bind_param('sss', $mustang,$dfmail,$alert);
				if($bind){
					if($stmt->execute()){
						$_SESSION['name']=$mustang;
						$_SESSION['mail_alert']=$alert;
						$_SESSION['mail']=$dfmail;
						if(isset($_SESSION['operators']))$_SESSION['operators'][$_SESSION['id']]=$mustang;
						echo json_encode(array(0=>'Saved'));
					}
					else{
						if((int)$stmt->errno==1062)
							echo json_encode(array(0=>"User with mail: ".$dfmail." is already registred"));
						else
							echo json_encode(array(0=>mysqli_stmt_error($stmt)));
					}
				}
				else
					echo json_encode(array(0=>mysqli_stmt_error($stmt)));
			}
			else
				echo json_encode(array(0=>mysqli_stmt_error($stmt)));
		}
		else if(isset($wrongpass) && $wrongpass==true)
			echo json_encode(array(0=>'Wrong Old Password'));
		else
			echo json_encode(array(0=>'New Passwords Mismatch'));
	}
	$mysqli->close();
	exit();
}

else if(isset($_POST['post_reply']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['post_reply']=='Post Reply'){
	$encid=preg_replace('/\s+/','',$_POST['id']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$error=array();
	if(preg_replace('/\s+/','',$_POST['message'])!='')
		$message=preg_replace('/\s+/',' ',preg_replace('/\r\n|[\r\n]/','<br/>',$_POST['message']));
		//$message=preg_replace('/\s+/',' ',$_POST['message']);
	else
		$error[]='Empty Message';

	if(!isset($error[0])){
		if(isset($_SESSION[$encid]['id'])){
			$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
			$stmt = $mysqli->stmt_init();
			if($stmt){
				if($_SESSION[$encid]['status']==0 && $_SESSION['id']==$_SESSION[$encid]['usr_id']){
					$query = "UPDATE ".$SupportTicketsTable." a ,".$SupportUserTable." b SET a.ticket_status= CASE WHEN a.operator_id=0 THEN '2' ELSE '1' END, b.assigned_tickets= CASE WHEN a.ticket_status='0' THEN (b.assigned_tickets+1) ELSE b.assigned_tickets END,b.solved_tickets= CASE WHEN a.ticket_status='0' AND b.solved_tickets>=1 THEN (b.solved_tickets-1) ELSE b.solved_tickets END,a.ticket_status= CASE WHEN a.operator_id='0' THEN '2' ELSE '1' END WHERE a.enc_id=? OR b.id=a.operator_id";
					if($stmt->prepare($query)){
						if($stmt->bind_param('s', $encid)){
							if($stmt->execute()){
								$query = "SELECT ticket_status FROM ".$SupportTicketsTable." WHERE id=? LIMIT 1";
								if($stmt->prepare($query)){
									if($stmt->bind_param('i', $_SESSION[$encid]['id'])){
										if($stmt->execute()){
											$stmt->store_result();
											$result = $stmt->bind_result($tkst);
											if($stmt->num_rows>0){
												while (mysqli_stmt_fetch($stmt)) 
													$_SESSION[$encid]['status']=$tkst;
												echo '<script>parent.$("#statustk").val(\'1\').change();</script>';
											}
											else
												echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket.No Matches.",type:"error",timeout:9000});</script>';
										}
										else
											echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket.Select Execute Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
									}
									else
										echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket.Select Bind Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
								}
								else
									echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket.Select Prepare Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
							}
							else
								echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket.Update Prepare Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
						}
						else
							echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket.Update Bind Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
					}
					else
						echo '<script>parent.noty({text: "Cannot Automatically Reopen ticket.Update Prepare Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
				}
				$ip=retrive_ip();
				$date=date("Y-m-d H:i:s");
				$query = "INSERT INTO ".$SupportMessagesTable."(`user_id`,`message`,`ticket_id`,`ip_address`,`created_time`) VALUES (?,?,?,?,?);";
				if($stmt->prepare($query)){
					if($stmt->bind_param('isiss', $_SESSION['id'],$message,$_SESSION[$encid]['id'],$ip,$date)){
						if($stmt->execute()){
							if(isset($setting[5]) && $setting[5]==1){
								$msid=$stmt->insert_id;
								//Upload File
								if(isset($_FILES['filename'])){
									$count=count($_FILES['filename']['name']);
									if($count>0){
										echo '<script>parent.noty({text: "File Upload Started",type:"information",timeout:2000});</script>';
										if(!is_dir('../upload')) mkdir('../upload');
										$maxsize=covert_size(ini_get('upload_max_filesize'));
										if(isset($setting[6]) && $setting[6]!=null)
											$maxsize=($setting[6]<=$maxsize)? $setting[6]:$maxsize;
										$query="INSERT INTO ".$SupportUploadTable." (`name`,`enc`,`uploader`,`num_id`,`ticket_id`,`message_id`,`upload_date`) VALUES ";
										$date=date("Y-m-d H:i:s");
										$uploadarr=array();
										$movedfiles=array();
										for($i=0;$i<$count;$i++){
											if($_FILES['filename']['error'][$i]==0){
												if($_FILES['filename']['size'][$i]<=$maxsize && $_FILES['filename']['size'][$i]!=0){
													if(count(array_keys($movedfiles,$_FILES['filename']['name'][$i]))==0){
														$encname=uniqid(hash('sha256',$msid.$_FILES['filename']['name'][$i]),true);
														if(move_uploaded_file($_FILES['filename']['tmp_name'][$i], "../upload/".$encname)){
															if(CryptFile("../upload/".$encname)){
																$movedfiles[]=$_FILES['filename']['name'][$i];
																$uploadarr[]=array($encid,$encname,$_FILES['filename']['name'][$i]);
																$query.='("'.$_FILES['filename']['name'][$i].'","'.$encname.'","'.$_SESSION['id'].'","'.$_SESSION[$encid]['id'].'","'.$encid.'","'.$msid.'","'.$date.'"),';
																echo '<script>parent.noty({text: "'.$_FILES['filename']['name'][$i].' has been uploaded",type:"success",timeout:2000});</script>';
															}
														}
													}
												}
												else
													echo '<script>parent.noty({text: "The file '.$_FILES['filename']['name'][$i].' is too big or null. Max file size: '.ini_get('upload_max_filesize').'",type:"error",timeout:9000});</script>';
											}
											else if($_FILES['filename']['error'][$i]!=4)
												echo '<script>parent.noty({text: "Error:'.$_FILES['filename']['name'][$i].' Code:'.$_FILES['filename']['error'][$i].'",type:"error",timeout:9000});</script>';
										}
										if(isset($uploadarr[0])){
											$query=substr_replace($query,'',-1);
											if($stmt->prepare($query)){
												if($stmt->execute()){
													$query="UPDATE ".$SupportMessagesTable." SET attachment='1' WHERE id=?";
													$prepared = $stmt->prepare($query);
													if($prepared){
														if($stmt->bind_param('i', $msid)){
															if($stmt->execute()){
																echo json_encode(array(0=>'Updated',1=>$tit));
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
												else{
													echo '<script>parent.noty({text: "Upload Execute Insert Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
												}
											}
											else
												echo '<script>parent.noty({text: "Prepare Upload Insert Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
										}
										echo '<script>parent.noty({text: "File Upload Finished",type:"information",timeout:2000});</script>';
									}
								}
							}
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
							if(isset($uploadarr[0])){
								$json=json_encode($uploadarr);
								echo "<script>parent.$('#formreply').nimbleLoader('hide');parent.post_reply('".addslashes($message)."','".$date."','".$_SESSION['name']."',".$json.");</script>";
							}
							else
								echo "<script>parent.$('#formreply').nimbleLoader('hide');parent.post_reply('".addslashes($message)."','".$date."','".$_SESSION['name']."',null);</script>";
						}
						else
							echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
					}
					else
						echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
				}
				else
					echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
			}
			else
				echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "'.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
			$mysqli->close();
		}
		else
			echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "No Identification Founded",type:"error",timeout:9000});</script>';
	}
	else
		echo '<script>parent.$("#formreply").nimbleLoader("hide");parent.noty({text: "'.implode(',',$error).'",type:"error",timeout:9000});</script>';
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='update_status'){//controllare
	if($_SESSION['status']==0)
		$charger=($_POST['status']==1 || $_POST['status']==2)? 1:0;
	else
		$charger=($_POST['status']==0 || $_POST['status']==1 || $_POST['status']==2)? $_POST['status']:0;
	$encid=preg_replace('/\s+/','',$_POST['id']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
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
		if($stmt->prepare($fquery)){
			if($stmt->bind_param('s', $encid)){
				if($stmt->execute()){
					if($stmt->prepare($lquery)){
						if($stmt->bind_param('s', $encid)){
							if($stmt->execute()){
								$query = "SELECT ticket_status FROM ".$SupportTicketsTable." WHERE enc_id=?";
								if($prepared = $stmt->prepare($query)){
									if($stmt->bind_param('s', $encid)){
										if($stmt->execute()){
											$stmt->store_result();
											$result = $stmt->bind_result($tkst);
											if($stmt->num_rows>0){
												while (mysqli_stmt_fetch($stmt))
													$_SESSION[$encid]['status']=$tkst;
												echo json_encode(array(0=>'Saved'));
											}
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']==1 && $_POST['act']=='move_opera_ticket'){//controllare
	$dpid=(is_numeric($_POST['dpid'])) ? $_POST['dpid']:exit();
	$encid=preg_replace('/\s+/','',$_POST['id']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
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
		$prepared = $stmt->prepare($query);
		if($prepared){
			if($stmt->bind_param('iiiis', $dpid,$opid,$opid,$opid,$encid)){
				if($stmt->execute()){
					echo json_encode(array(0=>'Moved'));
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='update_ticket_title'){
	$tit=(preg_replace('/\s+/','',$_POST['tit'])!='')? htmlentities(preg_replace('/\s+/',' ',$_POST['tit'])):exit();
	$encid=preg_replace('/\s+/','',$_POST['id']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
		$query="UPDATE ".$SupportTicketsTable." SET title=? WHERE enc_id=?";
		$prepared = $stmt->prepare($query);
		if($prepared){
			if($stmt->bind_param('ss', $tit,$encid)){
				if($stmt->execute()){
					echo json_encode(array(0=>'Updated',1=>$tit));
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='update_ticket_connection'){
	$encid=preg_replace('/\s+/','',$_POST['id']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$con=(is_numeric($_POST['contype']))? $_POST['contype']:exit();
	$usr=(preg_replace('/\s+/','',$_POST['user'])!='')? $_POST['user']:'';
	$pass=(preg_replace('/\s+/','',$_POST['pass'])!='')? $_POST['pass']:'';
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
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
		$query="UPDATE ".$SupportTicketsTable." SET contype=?,ftp_user=?,ftp_password=? WHERE enc_id=?";
		$prepared = $stmt->prepare($query);
		if($prepared){
			if($stmt->bind_param('ssss', $con,$usr,$pass,$encid)){
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
		echo json_encode(array(0=>mysqli_stmt_error($stmt)));
	$mysqli->close();
	exit();
}

else if(isset($_POST['file_download']) && isset($_SESSION['status']) && $_SESSION['status']<3){
	$encid=preg_replace('/\s+/','',$_POST['ticket_id']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$file=preg_replace('/\s+/','',$_POST['file_download']);
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
		$query="SELECT name FROM ".$SupportUploadTable." WHERE ticket_id=? AND enc=? LIMIT 1";
		$prepared = $stmt->prepare($query);
		if($prepared){
			if($stmt->bind_param('ss', $encid,$file)){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result($mustang);
					if($stmt->num_rows>0){
						while (mysqli_stmt_fetch($stmt)){
							$enc='../upload/'.$file;
							if(DecryptFile($enc)){
								$mime=retrive_mime($enc,$mustang);
								if($mime!='Error'){
									header("Content-Type: ".$mime);
									header("Cache-Control: no-store, no-cache");
									header("Content-Description: ".$mustang);
									header("Content-Disposition: attachment;filename=".$mustang);
									header("Content-Transfer-Encoding: binary");
									readfile($enc);
									CryptFile($enc);
									echo '<script>parent.noty({text: "Your download will start soon",type:"information",timeout:9000});</script>';
								}
								else
									echo '<script>parent.noty({text: "Can\'t retrive Content-Type",type:"error",timeout:9000});</script>';
							}
						}
					}
					else
						echo '<script>parent.noty({text: "No matches",type:"error",timeout:9000});</script>';
				}
				else
					echo '<script>parent.noty({text: "Executing Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});</script>';
			}
			else
				echo '<script>parent.noty({text: "Binding Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});';
		}
		else
			echo '<script>parent.noty({text: "Preparring Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});';
	}
	else
		echo '<script>parent.noty({text: "Initialization Error: '.mysqli_stmt_error($stmt).'",type:"error",timeout:9000});';
	$mysqli->close();
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='update_ticket_index'){//controllare
	$encid=preg_replace('/\s+/','',$_POST['id']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$tit=(preg_replace('/\s+/','',$_POST['title'])!='')? preg_replace('/\s+/',' ',$_POST['title']):exit();
	$prio = (is_numeric($_POST['priority']))? $_POST['priority']:0;

	if($_SESSION['status']==0)
		$charger=($_POST['status']==1 || $_POST['status']==2)? 1:0;
	else
		$charger=($_POST['status']==0 || $_POST['status']==1 || $_POST['status']==2)? $_POST['status']:0;

	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
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
		if($prepared = $stmt->prepare($fquery)){
			if($stmt->bind_param('s',$encid)){
				if($stmt->execute()){
					if($prepared = $stmt->prepare($lquery)){
						if($stmt->bind_param('ssss',$tit,$prio,$charger, $encid)){
							if($stmt->execute()){
								echo json_encode(array(0=>'Saved'));
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='rating'){
	$rate=(is_numeric($_POST['rate']))? $_POST['rate']:0;
	$GT86=(is_numeric($_POST['idBox']))? $_POST['idBox']/3823:0;
	$encid=preg_replace('/\s+/','',$_POST['tkid']);
	$encid=($encid!='' && strlen($encid)==87) ? $encid:exit();
	$note=preg_replace('/\s+/',' ',$_POST['comment']);
	if(isset($_SESSION[$encid]['status']) && $_SESSION[$encid]['status']==0){
		$query = "UPDATE ".$SupportUserTable." a
					INNER JOIN ".$SupportTicketsTable." b 
						ON b.operator_id=a.id
					SET a.rating=ROUND(((a.number_rating * a.rating - (CASE WHEN b.operator_rate>0 THEN b.operator_rate ELSE 0 END) + ?)/(CASE WHEN a.number_rating=0 THEN 1 ELSE a.number_rating+1 END)),2),
						a.number_rating=CASE WHEN b.operator_rate>0 THEN a.number_rating ELSE a.number_rating+1 END,
						b.operator_rate=? 
					WHERE  b.enc_id=?";
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			if($stmt->prepare($query)){
				if($stmt->bind_param('dds', $rate,$rate,$encid)){
					if($stmt->execute()){
						$query = "INSERT INTO ".$SupportRateTable." (`ref_id`,`enc_id`,`usr_id`,`rate`,`note`) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE `rate`=?,`note`=?";
						if($stmt->prepare($query)){
							if($stmt->bind_param('ssiisis',$_SESSION[$encid]['ref_id'],$encid,$_SESSION['id'],$rate,$note,$rate,$note)){
								if($stmt->execute()){
									echo json_encode(array(0=>'Voted'));
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
		echo json_encode(array(0=>'You must close the ticket before rate the operator!'));
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='faq_rating'){
	$rate=(is_numeric($_POST['rate']))? $_POST['rate']:0;
	$GT86=(is_numeric($_POST['idBox']))? $_POST['idBox']/3823:0;
	if($GT86>10 && $rate>0){
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			$query = "INSERT INTO ".$SupportRateFaqTable." (`faq_id`,`usr_id`,`rate`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `updated`='1'";
			if($stmt->prepare($query)){
				if($stmt->bind_param('iii',$GT86,$_SESSION['id'],$rate)){
					if($stmt->execute()){
						$query = "UPDATE ".$SupportFaqTable." a
									INNER JOIN ".$SupportRateFaqTable." b 
										ON b.faq_id=a.id
									SET 
										a.rate=CASE WHEN b.updated='1' THEN ROUND(((a.num_rate * a.rate - b.rate) + ?)/(a.num_rate),2) ELSE ROUND ((a.rate + ?)/(a.num_rate+1),2) END,
										a.num_rate=CASE WHEN b.updated='1' THEN a.num_rate ELSE a.num_rate+1 END,
										b.updated='0',
										b.rate=?
									WHERE  a.id=?";
						if($stmt->prepare($query)){
							if($stmt->bind_param('iiii', $rate,$rate,$rate,$GT86)){
								if($stmt->execute()){
									echo json_encode(array(0=>'Voted'));
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
		echo json_encode(array(0=>'Invalid Information'));
	exit();
}

else if(isset($_POST['act']) && isset($_SESSION['status'])  && $_SESSION['status']<3 && $_POST['act']=='search_ticket'){
	$enid=preg_replace('/\s+/','',$_POST['enid']);
	$tit=preg_replace('/\s+/',' ',$_POST['title']);
	$dep=(is_numeric($_POST['dep']))? (int)$_POST['dep']:'';
	$statk=(is_numeric($_POST['statk']))? (int)$_POST['statk']:'';
	$from=preg_replace('/\s+/','',$_POST['from']);
	$to=preg_replace('/\s+/','',$_POST['to']);
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
		$op=preg_replace('/\s+/',' ',$_POST['op']);
	if($_SESSION['status']==2){
		$id=(is_numeric($_POST['id']))? (int)$_POST['id']:'';
		$opid=(is_numeric($_POST['opid']))? (int)$_POST['opid']:'';
		$usmail=preg_replace('/\s+/','',$_POST['mail']);
	}
	$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
	$stmt = $mysqli->stmt_init();
	if($stmt){
		$query = "SELECT 
					a.enc_id,
						b.department_name,
						c.name,
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
					WHERE " ;
		$merge=array('type'=>array(),'val'=>array());
		if($_SESSION['status']==0){
			$query.=' `user_id`='.$_SESSION['id'];
			if($enid!=''){
				$query.=' AND a.ref_id=?';
				$merge['type'][]='s';
				$merge['val'][]=$enid;
			}
			if($tit!=''){
				$query.=' AND a.title LIKE ?';
				$merge['type'][]='s';
				$merge['val'][]='%'.$tit.'%';
			}
			if($dep!=''){
				$query.=' AND a.department_id=?';
				$merge['type'][]='i';
				$merge['val'][]=$dep;
			}
			if($op!=''){
				$query.=' AND a.operator_id IN (SELECT `id` FROM '.$SupportUserTable.' WHERE `name`=? AND 0!=`status`)';
				$merge['type'][]='s';
				$merge['val'][]='%'.$op.'%';
			}
			if($from!=''){
				$query.=' AND a.created_time >= ?';
				$merge['type'][]='s';
				$merge['val'][]=$from;
			}
			if($to!=''){
				$query.=' AND a.created_time =< ?';
				$merge['type'][]='s';
				$merge['val'][]=$to;
			}
		}
		else if($_SESSION['status']==1){
			$query.=' `user_id`='.$_SESSION['id'].' OR `operator_id`='.$_SESSION['id'];
			if($enid!=''){
				$query.=' AND a.ref_id=?';
				$merge['type'][]='s';
				$merge['val'][]=$enid;
			}
			if($tit!=''){
				$query.=' AND a.title LIKE ?';
				$merge['type'][]='s';
				$merge['val'][]='%'.$tit.'%';
			}
			if($dep!=''){
				$query.=' AND a.department_id=?';
				$merge['type'][]='i';
				$merge['val'][]=$dep;
			}
			if($from!=''){
				$query.=' AND a.created_time >= ?';
				$merge['type'][]='s';
				$merge['val'][]=$from;
			}
			if($to!=''){
				$query.=' AND a.created_time <= ?';
				$merge['type'][]='s';
				$merge['val'][]=$to;
			}
		}
		else if($_SESSION['status']==2){
			$tail=array();
			if($id!=''){
				$tail[]='a.user_i`=?';
				$merge['type'][]='i';
				$merge['val'][]=$id;
			}
			if($enid!=''){
				$tail[]='a.ref_id=?';
				$merge['type'][]='s';
				$merge['val'][]=$enid;
			}
			if($tit!=''){
				$tail[]='a.title LIKE ?';
				$merge['type'][]='s';
				$merge['val'][]='%'.$tit.'%';
			}
			if($dep!=''){
				$tail[]='a.department_id=?';
				$merge['type'][]='i';
				$merge['val'][]=$dep;
			}
			if($opid!=''){
				$tail[]='a.operator_id=?';
				$merge['type'][]='i';
				$merge['val'][]=$opid;
			}
			if($op!=''){
				$tail[]='a.operator_id IN (SELECT `id` FROM '.$SupportUserTable.' WHERE `name`=? AND 0!=`status`)';
				$merge['type'][]='s';
				$merge['val'][]='%'.$op.'%';
			}
			if($from!=''){
				$tail[]='a.created_time >= ?';
				$merge['type'][]='s';
				$merge['val'][]=$from;
			}
			if($to!=''){
				$tail[]='a.created_time <= ?';
				$merge['type'][]='s';
				$merge['val'][]=$to;
			}
			if($usmail!=''){
				$tail[]='(a.user_id=(SELECT `id` FROM '.$SupportUserTable.' WHERE `mail`=? LIMIT 1) OR operator_id=(SELECT `id` FROM '.$SupportUserTable.' WHERE `mail`=? LIMIT 1))';
				$merge['type'][]='ss';
				$merge['val'][]='%'.$usmail.'%';
				$merge['val'][]='%'.$usmail.'%';
			}
			$query.=implode(' AND ',$tail);
		}
		$query.=' ORDER BY `last_reply` DESC';
		$bind_names = array();
		$bind_names[] = implode('',$merge['type']);    
		$journey=count($merge['val']);
		for ($i=0; $i<$journey;$i++) {
			$bind_name = 'bind'.$i;
			$$bind_name = $merge['val'][$i];
			$bind_names[] = &$$bind_name;
		}
		$prepared = $stmt->prepare($query);
		if($prepared){
			if(call_user_func_array(array($stmt, "bind_param"), $bind_names)){
				if($stmt->execute()){
					$stmt->store_result();
					$result = $stmt->bind_result($encid, $depname, $opname, $title, $priority, $dat, $last, $tkstat);
					$list=array('response'=>'ret','search'=>array());
					if($stmt->num_rows>0){
						while (mysqli_stmt_fetch($stmt)) 
							$list['search'][]=array('id'=>$encid,'dname'=>$depname,'opname'=>$opname,'title'=>$title,'priority'=>$priority,'date'=>$dat,'reply'=>$last,'status'=>$tkstat);
					}
					echo json_encode($list);
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

else if(isset($_POST['act']) && isset($_SESSION['status']) && $_SESSION['status']<3 && $_POST['act']=='report_ticket'){

	if(preg_replace('/\s+/','',strip_tags($_POST['message']))!='')
		$message=preg_replace('/\s+/',' ',preg_replace('/\r\n|[\r\n]/','<br/>',$_POST['message']));
	else
		$error[]='Empty Message';
	
	$encid=preg_replace('/\s+/','',$_POST['id']);
	if($encid=='' && strlen($encid)==87)
		$error[]='Incorrect ID';

	if(!isset($_SESSION[$encid]))
		$error[]='No information has been found about you and the ticket';

	if(!isset($error[0])){
		$side=($_SESSION[$encid]['usr_id']==$_SESSION['id'])? 'User':'Operator';
		$query = "INSERT INTO ".$SupportFlagTable." (ref_id,enc_id,usr_id,side,reason) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE reason=?";
		$mysqli = new mysqli($Hostname, $Username, $Password, $DatabaseName);
		$stmt = $mysqli->stmt_init();
		if($stmt){
			if($prepared = $stmt->prepare($query)){
				if($stmt->bind_param('ssisss',$_SESSION[$encid]['ref_id'],$encid,$_SESSION['id'],$side,$message,$message)){
					if($stmt->execute()){
						$_SESSION[$_GET['id']]['reason']=$message;
						echo json_encode(array(0=>'Submitted'));
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
		echo json_encode(array(0=>'Error/s: '.implode(', ',$error)));
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

function retrive_mime($encname,$mustang){
	$mime_types = array(
		'txt' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		'php' => 'text/html',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'json' => 'application/json',
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