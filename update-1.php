<?php

//First Table Update 16/10/2013

include_once 'php/config/database.php';
try{
	$query="DROP PROCEDURE IF EXISTS `schema_change`;

	DELIMITER ;;

	CREATE PROCEDURE `schema_change`()
	BEGIN
		IF EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'razorphyn_support_uploaded_file' AND column_name = 'ticket_id') 
			THEN
				ALTER TABLE razorphyn_support_uploaded_file DROP COLUMN `ticket_id`;
		END IF;

		IF EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'razorphyn_support_list_tickets' AND column_name = 'enc_id') 
			THEN
				ALTER TABLE razorphyn_support_list_tickets DROP INDEX `enc_id`;
				ALTER TABLE razorphyn_support_list_tickets DROP COLUMN `enc_id`;
				ALTER TABLE razorphyn_support_list_tickets ADD INDEX `ticket_index` (`id`, `department_id`, `operator_id`, `user_id`, `ticket_status`);
		END IF;

		/*Rename Columns*/
		IF EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'razorphyn_support_uploaded_file' AND column_name = 'num_id') THEN
				ALTER TABLE razorphyn_support_uploaded_file CHANGE `num_id` `tk_id` BIGINT(15) UNSIGNED NOT NULL;
		END IF;

		IF EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'razorphyn_support_operator_rate' AND column_name = 'enc_id') 
			THEN
				ALTER TABLE razorphyn_support_operator_rate DROP INDEX `enc_id`;
				ALTER TABLE razorphyn_support_operator_rate CHANGE `enc_id` `tk_id` BIGINT(15) UNSIGNED NOT NULL;
				ALTER TABLE razorphyn_support_operator_rate ADD INDEX `op_rate_index` (`ref_id`, `tk_id`, `rate`);
				ALTER TABLE razorphyn_support_operator_rate ADD UNIQUE KEY(`tk_id`);
		END IF;

		IF EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'razorphyn_support_flag_tickets' AND column_name = 'enc_id') 
			THEN
				ALTER TABLE razorphyn_support_flag_tickets DROP INDEX `ref_id`;
				ALTER TABLE razorphyn_support_flag_tickets CHANGE `enc_id` `tk_id` BIGINT(15) UNSIGNED NOT NULL;
				ALTER TABLE razorphyn_support_flag_tickets ADD INDEX `flag_index` (`ref_id`, `tk_id`, `usr_id`);
		END IF;

		ALTER TABLE razorphyn_support_list_messages MODIFY COLUMN `ticket_id` BIGINT(15) UNSIGNED NOT NULL;
	END;;

	DELIMITER ;

	CALL `schema_change`();
	DROP PROCEDURE IF EXISTS `schema_change`;";

	$DBH = new PDO("mysql:host=$Hostname;dbname=$DatabaseName", $Username, $Password);  
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$STH = $DBH->prepare($query);
	$STH->execute();
	echo "Database Updated, this file has been deleted";
	unlink(__FILE__);
}
catch(PDOException $e){
	file_put_contents('PDOErrors', "File: ".$e->getFile().' on line '.$e->getLine()."\nError: ".$e->getMessage(), FILE_APPEND);
	echo 'Check PDOErrors file';
	exit();
}
?>