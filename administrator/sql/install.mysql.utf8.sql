CREATE TABLE IF NOT EXISTS `#__imc_issues` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`title` VARCHAR(255)  NOT NULL ,
`stepid` TEXT NOT NULL ,
`catid` INT(11)  NOT NULL ,
`description` TEXT NOT NULL ,
`address` TEXT NOT NULL ,
`latitude` VARCHAR(255)  NOT NULL ,
`longitude` VARCHAR(255)  NOT NULL ,
`photo` TEXT  NOT NULL ,
`ordering` INT(11)  NOT NULL ,
`state` TINYINT(1)  NOT NULL ,
`checked_out` INT(11)  NOT NULL ,
`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`access` INT(11)  NOT NULL ,
`created` DATETIME NOT NULL ,
`updated` DATETIME NOT NULL ,
`created_by` INT(11)  NOT NULL ,
`language` VARCHAR(255)  NOT NULL ,
`hits` MEDIUMINT(8)  NOT NULL ,
`note` VARCHAR(512)  NOT NULL ,
`votes` MEDIUMINT(8)  NOT NULL ,
`modality` SMALLINT(6)  NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__imc_steps` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`title` VARCHAR(255)  NOT NULL ,
`description` TEXT NOT NULL ,
`stepcolor` VARCHAR(10) NOT NULL ,
`ordering` INT(11)  NOT NULL ,
`state` TINYINT(1)  NOT NULL ,
`created` DATETIME NOT NULL ,
`updated` DATETIME NOT NULL ,
`checked_out` INT(11)  NOT NULL ,
`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`created_by` INT(11)  NOT NULL ,
`language` VARCHAR(255)  NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__imc_keys` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`title` VARCHAR(255)  NOT NULL ,
`skey` VARCHAR(16)  NOT NULL ,
`ordering` INT(11)  NOT NULL ,
`state` TINYINT(1)  NOT NULL ,
`checked_out` INT(11)  NOT NULL ,
`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`created_by` INT(11)  NOT NULL ,
`created` DATETIME NOT NULL ,
`updated` DATETIME NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__imc_evolution` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`issueid` INT NOT NULL ,
`stepid` TEXT NOT NULL ,
`description` TEXT NOT NULL ,
`created` DATETIME NOT NULL ,
`updated` DATETIME NOT NULL ,
`ordering` INT(11)  NOT NULL ,
`state` TINYINT(1)  NOT NULL ,
`checked_out` INT(11)  NOT NULL ,
`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`created_by` INT(11)  NOT NULL ,
`language` VARCHAR(255)  NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__imc_votes` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`issueid` INT NOT NULL ,
`created` DATETIME NOT NULL ,
`updated` DATETIME NOT NULL ,
`ordering` INT(11)  NOT NULL ,
`state` TINYINT(1)  NOT NULL ,
`checked_out` INT(11)  NOT NULL ,
`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`created_by` INT(11)  NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__imc_comments` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`issueid` INT NOT NULL ,
`parentid` INT(11)  NOT NULL ,
`description` TEXT NOT NULL ,
`photo` VARCHAR(2048)  NOT NULL ,
`created` DATETIME NOT NULL ,
`updated` DATETIME NOT NULL ,
`ordering` INT(11)  NOT NULL ,
`state` TINYINT(1)  NOT NULL ,
`checked_out` INT(11)  NOT NULL ,
`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`created_by` INT(11)  NOT NULL ,
`language` VARCHAR(255)  NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

