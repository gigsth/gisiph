# GISIPH
ระบบสาสนเทศทางภูมิศาสตร์เพื่อการบูรณการสาธารณสุข เป็นเว็บแอพพลิเคชั่นที่พัฒนาเพื่อสนองนโยบายของทางกระทรวงสารธารณสุขเรื่อง *ปิงปองจราจรชิวิต 7สี* ที่ว่าด้วยเรื่องของการติดตามอาการของผู้ป่วยโรคเบาหวานและความดันโลหิตสูง


## Require
- MySQL  *v5.0.51b*
- PHP *v5.2.6*
- jhcisdb


## Getting started
1. Install *MySQL* and *PHP*
2. Clone this `git clone git@github.com:gigsth/gisiph.git` project or [Download scource code](https://codeload.github.com/gigsth/gisiph/zip/v1.0)
3. Let's fun :D


## Create database
	USE jhcisdb;

	DROP TABLE `gisiph_gps_house`;
	CREATE TABLE `gisiph_gps_house` (
		`hcode` int(11) NOT NULL,
		`latitude` double NOT NULL,
		`longitude` double NOT NULL,
		`uedit` varchar(20) NOT NULL,
		`status` varchar(10) NOT NULL,
		`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update 	CURRENT_TIMESTAMP,
		PRIMARY KEY  (`hcode`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;


	DROP TABLE `gisiph_photo_house`;
	CREATE TABLE `gisiph_photo_house` (
		`phcode` int(11) NOT NULL auto_increment,
		`hcode` int(11) NOT NULL,
		`path` varchar(256) NOT NULL,
		`uedit` varchar(20) NOT NULL,
		`status` varchar(10) NOT NULL,
		`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`phcode`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


	DROP TABLE `gisiph_photo_pchronic`;
	CREATE TABLE `gisiph_photo_pchronic` (
		`pccode` int(11) NOT NULL auto_increment,
		`pid` int(11) NOT NULL,
		`chroniccode` char(7) NOT NULL,
		`path` varchar(256) NOT NULL,
		`uedit` varchar(20) NOT NULL,
		`status` varchar(10) NOT NULL,
		`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`pccode`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


	ALTER TABLE `jhcisdb`.`visit` DROP KEY `gisiph_pid`;
	ALTER TABLE `jhcisdb`.`visit` ADD KEY `gisiph_pid` (`pid`);
	ALTER TABLE `jhcisdb`.`visit` DROP KEY `gisiph_visitno`;
	ALTER TABLE `jhcisdb`.`visit` ADD KEY `gisiph_visitno` (`visitno`);
	ALTER TABLE `jhcisdb`.`visit` DROP KEY `gisiph_pid_visitno`;
	ALTER TABLE `jhcisdb`.`visit` ADD KEY `gisiph_pid_visitno` (`pid`, `visitno`);
	ALTER TABLE `jhcisdb`.`visitlabsugarblood` DROP KEY `gisiph_visitno`;
	ALTER TABLE `jhcisdb`.`visitlabsugarblood` ADD KEY `gisiph_visitno` (`visitno`);



## Configuration
You can change default database connection in `/you/workspace/path/gisiph/dist/php/configure.database.php`
