<?php

//Second Table Update 05/11/2013

include_once '../php/config/database.php';

if(is_file('../php/config/database_version') && file_get_contents('../php/config/database_version')>='1.0.2'){
	echo 'You have already installed the latest version';
	if(is_file('update-1.php'))unlink('update-1.php');
	unlink('update-2.php');
	exit();
}
try{
	if(is_file('../php/config/database_version') && file_get_contents('../php/config/database_version')<'1.0.1'){
		echo 'Updating to 1.0.1';
		include_once 'update-1.php';
	}
	echo "\nUpdating to 1.0.2";
	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
	$query="
		CREATE TABLE IF NOT EXISTS `razorphyn_support_sales` (
			`id` 				BIGINT(11) 		UNSIGNED	NOT NULL 	AUTO_INCREMENT,
			`gateway` 			VARCHAR(60) 				NOT NULL,
			`payer_mail` 		VARCHAR(50) 				NOT NULL,
			`status` 			ENUM('0','1','2','3') 		NOT NULL 	DEFAULT '2',
			`transaction_id` 	VARCHAR(40) 				NOT NULL,
			`tk_id` 			BIGINT(15) 		UNSIGNED,
			`user_id` 			BIGINT(15) 		UNSIGNED,
			`amount` 			FLOAT(10,2),
			`support_time` 		VARCHAR(25) 				NOT NULL,
			`payment_date` 		DATETIME  					NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY (`gateway`,`transaction_id`),
			INDEX `sales_index` (`user_id`,`tk_id`,`support_time`,`status`,`transaction_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;


		ALTER TABLE ".$SupportTicketsTable." ADD COLUMN `enabled` ENUM('0','1') NOT NULL DEFAULT '1';

		ALTER TABLE ".$SupportTicketsTable." ADD COLUMN `closed_date` DATETIME NULL;
		UPDATE ".$SupportTicketsTable." SET closed_date=last_reply WHERE ticket_status='0';

		ALTER TABLE ".$SupportTicketsTable." ADD COLUMN `support_time` INT(5) UNSIGNED NULL;
		
		ALTER TABLE ".$SupportTicketsTable." DROP INDEX `user_id`;
		ALTER TABLE ".$SupportTicketsTable." ADD UNIQUE KEY (`user_id`,`title`,`department_id`);

		ALTER TABLE ".$SupportDepaTable." ADD COLUMN `free` ENUM('0','1') NOT NULL DEFAULT '1';
	";

	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$STH = $DBH->prepare($query);
	$STH->execute();
	file_put_contents('../php/config/database_version','1.0.2');
	echo "\n\nDatabase Updated, this file has been deleted";
	if(is_file('update-1.php'))unlink('update-1.php');
	unlink('update-2.php');
}
catch(PDOException $e){
	file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
	echo 'Check PDOErrors file';
	exit();
}
?>