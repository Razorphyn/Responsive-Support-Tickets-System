<!DOCTYPE html>
<html lang="en">
<head>
	<title>Database Update</title>
</head>
<body>
	<?php
		//Third Table Update 05/11/2013

		include_once '../php/config/database.php';
		try{
			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$query = "SHOW TABLES LIKE '".$SupportVersionTable."';";
			
			$STH = $DBH->prepare($query);
			$STH->execute();

			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$a = $STH->fetch();
			if(!empty($a)){
				echo '<p>You have already installed the latest version</p>';
				exit();
			}

			if(is_file('../php/config/database_version') && file_get_contents('../php/config/database_version')<'1.0.1'){
				echo '<p>Updating to 1.0.1</p>';
				include_once 'update-1.php';
			}
			if(is_file('../php/config/database_version') && file_get_contents('../php/config/database_version')<'1.0.2'){
				echo '<p>Updating to 1.0.2</p>';
				include_once 'update-2.php';
			}
			echo '<p>Updating to 1.0.3</p>';
			$query="
				CREATE TABLE IF NOT EXISTS `".$SupportVersionTable."` (
					`id` 					INT(1) 				UNSIGNED	NOT NULL DEFAULT 1,
					`db_version` 			VARCHAR(11) 		NOT NULL,
					PRIMARY KEY (`id`),
					INDEX `info` (`db_version`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

				INSERT INTO ".$SupportVersionTable." (`db_version`) VALUES ('1.0.3');
				
				ALTER TABLE ".$SupportSalesTable." CHANGE `status` `status` ENUM('0','1','2','3','4') NOT NULL;
				
				DROP TABLE IF EXISTS `razorphyn_support_moneybooker`;
			";

			$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
			$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$STH = $DBH->prepare($query);
			$STH->execute();
			echo "<p>Database Updated, this file has been deleted</p>";
			if(is_file('../php/config/database_version'))unlink('../php/config/database_version');
			if(is_file('update-1.php'))unlink('update-1.php');
			if(is_file('update-2.php'))unlink('update-2.php');
			unlink('update-3.php');
		}
		catch(PDOException $e){
			file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
			echo '<p>Check PDOErrors file</p>';
			exit();
		}
	?>
</body>
</html>