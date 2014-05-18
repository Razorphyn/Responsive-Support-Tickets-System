<!DOCTYPE html>
<html lang="en">
<head>
	<title>Database Update</title>
</head>
<body>
	<?php
		//Fourth Table Update

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
				if($a['db_version']>='1.0.4'){
					echo '<p>You have already installed the latest version</p>';
					exit();
				}
			}

			echo '<p>Updating to 1.0.4</p>';
			$query="
				ALTER TABLE ".$SupportUploadTable." CHANGE `name` `name` VARCHAR(255) NOT NULL;
				UPDATE ".$SupportVersionTable." SET db_version='1.0.4' WHERE id='1';
			";

			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$STH = $DBH->prepare($query);
			$STH->execute();
			echo "<p>Database Updated, this file has been deleted</p>";
			unlink('update-4.php');
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			echo '<p>Check PDOErrors file</p>';
			exit();
		}
	?>
</body>
</html>