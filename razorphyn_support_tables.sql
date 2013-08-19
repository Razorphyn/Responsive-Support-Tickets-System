CREATE TABLE IF NOT EXISTS `razorphyn_support_departments` (
	`id` 				BIGINT(11) 	UNSIGNED	NOT NULL 	AUTO_INCREMENT,
	`department_name` 	VARCHAR(70) 			NOT NULL,
	`active` 			ENUM('0','1') 			NOT NULL 	DEFAULT '1',
	`public_view` 		ENUM('0','1') 			NOT NULL 	DEFAULT '1',
	PRIMARY KEY 	(`id`),
	UNIQUE KEY		(`department_name`),
	INDEX (`id`,`department_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

CREATE TABLE IF NOT EXISTS `razorphyn_support_user_departments` (
	`id` 				BIGINT(11) 	UNSIGNED 	NOT NULL 	AUTO_INCREMENT,
	`department_id` 	BIGINT(11) 	UNSIGNED	NOT NULL,
	`department_name` 	VARCHAR(70) 			NOT NULL,
	`user_id` 			BIGINT(11) 	UNSIGNED 	NOT NULL,
	`holiday` 			ENUM('0','1') 			NOT NULL 	DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE KEY(`department_name`,`user_id`),
	INDEX(`department_id`,`department_name`,`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

CREATE TABLE IF NOT EXISTS `razorphyn_support_list_messages` (
	`id` 				BIGINT(18) 	UNSIGNED 		NOT NULL 	AUTO_INCREMENT,
	`user_id` 			BIGINT(11) 	UNSIGNED 		NOT NULL,
	`message` 			TEXT 						NOT NULL,
	`attachment` 		ENUM('0','1') 				NOT NULL 	DEFAULT '0',
	`ticket_id`			BIGINT(11) 	UNSIGNED 		NOT NULL,
	`ip_address` 		VARCHAR(20) 				NOT NULL,
	`created_time` 		DATETIME  					NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20;

CREATE TABLE IF NOT EXISTS `razorphyn_support_list_tickets` (
	`id` 				BIGINT(15) 	UNSIGNED 		NOT NULL 	AUTO_INCREMENT,
	`enc_id` 			CHAR(87),
	`ref_id` 			VARCHAR(18),
	`department_id` 	BIGINT(11) 	UNSIGNED		NOT NULL,
	`operator_id`	 	BIGINT(11) 	UNSIGNED		NOT NULL DEFAULT 0,
	`user_id` 			BIGINT(11) 	UNSIGNED 		NOT NULL,
	`title` 			VARCHAR(255)				NOT NULL,
	`priority` 			INT(2) 		UNSIGNED		NOT NULL,
	`website` 			VARCHAR(200) 				NOT NULL,
	`contype` 			ENUM('0','1','2','3','4','5') 	NOT NULL 	DEFAULT '0',
	`ftp_user` 			VARCHAR(60) 				NOT NULL,
	`ftp_password` 		VARCHAR(60) 				NOT NULL,
	`created_time` 		DATETIME 					NOT NULL,
	`last_reply` 		DATETIME  					NOT NULL,
	`ticket_status` 	ENUM('0','1','2','3') 		NOT NULL 	DEFAULT '2',
	`operator_rate`		DECIMAL(4,2) 				UNSIGNED,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`user_id`,`title`),
	INDEX (`enc_id`,`department_id`,`operator_id`,`user_id`,`ticket_status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20;

CREATE TABLE IF NOT EXISTS `razorphyn_support_users` (
	`id` 				BIGINT(15) 		UNSIGNED		NOT NULL AUTO_INCREMENT,
	`name` 				VARCHAR(50) 					NOT NULL,
	`mail` 				VARCHAR(50) 					NOT NULL,
	`password`			VARCHAR(200) 					NOT NULL,
	`reg_key`			VARCHAR(260) 					,
	`tmp_password` 		VARCHAR(87) 					,
	`ip_address` 		VARCHAR(50) 					NOT NULL,
	`status` 			ENUM('0','1','2','3','4') 		NOT NULL 	DEFAULT '3',
	`holiday` 			ENUM('0','1') 					NOT NULL 	DEFAULT '0',
	`mail_alert` 		ENUM('no','yes') 				NOT NULL 	DEFAULT 'yes',
	`assigned_tickets` 	INT(5) 			UNSIGNED		NOT NULL	DEFAULT 0,
	`solved_tickets` 	BIGINT(11) 		UNSIGNED		NOT NULL	DEFAULT 0,
	`number_rating` 	BIGINT(6) 		UNSIGNED		NOT NULL	DEFAULT 0,
	`rating` 			DECIMAL(4,2) 	UNSIGNED		NOT NULL	DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY(`mail`),
	INDEX (`name`,`mail`,`status`,`holiday`,`assigned_tickets`,`solved_tickets`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=55;

INSERT INTO `razorphyn_support_users` (`id`, `name`, `mail`, `password`, `reg_key`, `tmp_password`, `ip_address`, `status`, `holiday`, `mail_alert`, `assigned_tickets`, `solved_tickets`, `number_rating`, `rating`) VALUES
(54, 'Admin', 'admin@admin.com', 'd16a2f5a824df504eafae57bae3ed217c5be8ffc15d8c576b536910e19b30ca27a3abe8f42e01222ec15e5f81a471f79428dbb940106e279d9cf45e50379c81e', NULL , NULL, '127.0.0.1', '2', '0', 'yes', 0, 0, 0, 0.00);


CREATE TABLE IF NOT EXISTS `razorphyn_support_uploaded_file`(
	`id` 				BIGINT(15) 	UNSIGNED		NOT NULL AUTO_INCREMENT,
	`name` 				VARCHAR(50) 				NOT NULL,
	`uploader` 			BIGINT(11) 	UNSIGNED		NOT NULL, 
	`enc`				CHAR(87) 					NOT NULL,
	`num_id` 			BIGINT(15) 	UNSIGNED 		NOT NULL,
	`ticket_id` 		CHAR(87),
	`message_id` 		BIGINT(18) 	UNSIGNED		NOT NULL, 
	`upload_date` 		DATETIME 					NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY(`message_id`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10;

CREATE TABLE IF NOT EXISTS `razorphyn_support_flag_tickets`(
	`id` 				BIGINT(15) 		UNSIGNED		NOT NULL AUTO_INCREMENT,
	`ref_id` 			VARCHAR(18)						NOT NULL,
	`enc_id` 			CHAR(87)	 					NOT NULL,
	`usr_id` 			BIGINT(15) 		UNSIGNED		NOT NULL,
	`side` 				VARCHAR(20) 					NOT NULL,
	`reason` 			VARCHAR(200) 					NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY(`ref_id`,`enc_id`,`usr_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15;

CREATE TABLE IF NOT EXISTS `razorphyn_support_operator_rate`(
	`id` 				BIGINT(15) 		UNSIGNED		NOT NULL AUTO_INCREMENT,
	`ref_id` 			VARCHAR(18)						NOT NULL,
	`enc_id` 			CHAR(87)	 					NOT NULL,
	`usr_id` 			BIGINT(15) 		UNSIGNED		NOT NULL,
	`rate` 				DECIMAL(4,2) 	UNSIGNED		NOT NULL	DEFAULT 0,
	`note` 				VARCHAR(200) 					,
	PRIMARY KEY (`id`),
	UNIQUE KEY(`enc_id`),
	INDEX (`ref_id`,`enc_id`,`rate`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15;

CREATE TABLE IF NOT EXISTS `razorphyn_support_faq` (
	`id` 				INT(5)			UNSIGNED		NOT NULL AUTO_INCREMENT,
	`question` 			VARCHAR(200)					NOT NULL,
	`answer` 			VARCHAR(2000) 					NOT NULL,
	`position` 			INT(4) 			UNSIGNED		NOT NULL	DEFAULT 0,
	`active` 			ENUM('0','1') 					NOT NULL 	DEFAULT '1',
	`num_rate` 			INT(8) 			UNSIGNED		NOT NULL	DEFAULT 0,
	`rate` 				DECIMAL(4,2) 	UNSIGNED		NOT NULL	DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY(`question`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15;

CREATE TABLE IF NOT EXISTS `razorphyn_support_faq_rate`(
	`id` 				BIGINT(15) 		UNSIGNED		NOT NULL AUTO_INCREMENT,
	`faq_id` 			INT(5)			UNSIGNED		NOT NULL,
	`usr_id` 			BIGINT(15) 		UNSIGNED		NOT NULL,
	`rate` 				DECIMAL(4,2) 	UNSIGNED		NOT NULL	DEFAULT 0,
	`updated` 			ENUM('0','1') 					NOT NULL 	DEFAULT '0',
	`note` 				VARCHAR(200) 					,
	PRIMARY KEY (`id`),
	UNIQUE KEY(`faq_id`,`usr_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15;