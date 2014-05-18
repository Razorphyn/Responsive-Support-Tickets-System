<!DOCTYPE html>
<html lang="en">
<head>
	<title>Database Update</title>
</head>
<body>
	<?php
		//Fifth Table Update 18/05/2014

		include_once '../php/config/database.php';
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query = "SHOW TABLES LIKE '".$SupportVersionTable."';";
			
			$STH = $DBH->prepare($query);
			$STH->execute();

			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(empty($a)){
				if(is_file('../php/config/database_version') && file_get_contents('../php/config/database_version')<'1.0.1'){
					echo '<p>Updating to 1.0.1</p>';
					include_once 'update-1.php';
				}
				if(is_file('../php/config/database_version') && file_get_contents('../php/config/database_version')<'1.0.2'){
					echo '<p>Updating to 1.0.2</p>';
					include_once 'update-2.php';
				}
				if(is_file('../php/config/database_version') && file_get_contents('../php/config/database_version')<'1.0.3'){
					echo '<p>Updating to 1.0.3</p>';
					include_once 'update-2.php';
				}
			}
			else{
				$query = "SELECT `db_version` FROM ".$SupportVersionTable." WHERE `id`=1 LIMIT 1";
				$STH = $DBH->prepare($query);
				$STH->execute();
				$STH->setFetchMode(PDO::FETCH_ASSOC);
				$a = $STH->fetch();
				if($a['db_version']>='1.0.5'){
					echo '<p>You have already installed the latest version</p>';
					exit();
				}
				else if($a['db_version']<'1.0.4'){
					echo '<p>Updating to 1.0.4</p>';
					include_once 'update-4.php';
				}
			}

			echo '<p>Updating to 1.0.5</p>';
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$query="
				ALTER TABLE ".$SupportTicketsTable." CHANGE `website` `website` VARCHAR(100) DEFAULT NULL;
				ALTER TABLE ".$SupportTicketsTable." CHANGE `ftp_user` `ftp_user` VARCHAR(100) DEFAULT NULL;
				ALTER TABLE ".$SupportTicketsTable." CHANGE `ftp_password` `ftp_password` VARCHAR(255) DEFAULT NULL;
				ALTER TABLE ".$SupportTicketsTable." ADD `enc_key` VARCHAR(23) DEFAULT NULL AFTER `ftp_password`;
				UPDATE ".$SupportVersionTable." SET db_version='1.0.5' WHERE id='1';
			";

			$STH = $DBH->prepare($query);
			$STH->execute();
			echo "<p>Database Updated</p>";
			
			echo '<p>Updating passwords, this could take a while</p>';

			$query="SELECT `id`,`ftp_password` FROM ".$SupportTicketsTable." WHERE `enc_key` IS NULL AND `ftp_password` IS NOT NULL;";

			$STH = $DBH->prepare($query);
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			
			$a = $STH->fetch();
			if(!empty($a)){
				$query='';
				$list=array();
				do{
					include_once ('../php/endecrypt.php');
					$key=uniqid('',true);
					$e = new Encryption(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
					$a['ftp_password']=$e->encrypt($a['ftp_password'], $key);
					$query.='UPDATE '.$SupportTicketsTable.' SET `ftp_password`=?,`enc_key`=? WHERE id=?;';
					$list[]=array('id'=>$a['id'],'pass'=>$a['ftp_password'],'key'=>$key);
				}while ($a = $STH->fetch());
				
				$STH = $DBH->prepare($query);
				
				$c=count($list)*3;
				for($i=0;$i<$c;$i+=3){
					$STH->bindParam($i+1,$list[$i/3]['pass'],PDO::PARAM_STR);
					$STH->bindParam($i+2,$list[$i/3]['key'],PDO::PARAM_STR);
					$STH->bindParam($i+3,$list[$i/3]['id'],PDO::PARAM_INT);
				}
				$STH->execute();
			}

			if(is_file('../php/config/mail/stmp.php')){
				include_once ('../php/endecrypt.php');
				$key=uniqid('',true);
				$e = new Encryption(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
				$smailpassword=$e->encrypt($smailpassword, $key);
				
				$string='<?php $smailservice='.$smailservice.";\n".'$smailname=\''.$smailname."';\n".'$settingmail=\''.$settingmail."';\n".'$smailhost=\''.$smailhost."';\n".'$smailport='.$smailport.";\n".'$smailssl='.$smailssl.";\n".'$smailauth='.$smailauth.";\n".'$smailuser=\''.$smailuser."';\n".'$smailpassword=\''.$smailpassword."';\n".'$smailenckey=\''.$key."';\n ?>";

			}
			echo "<p>Passwords Updated</p>";
			
			echo "<p>This file has been deleted</p>";

			
			unlink('update-5.php');
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			echo '<p>Check PDOErrors file</p>';
			exit();
		}
	?>
</body>
</html>