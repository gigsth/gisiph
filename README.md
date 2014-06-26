# GISIPH
ระบบสาสนเทศทางภูมิศาสตร์เพื่อการบูรณการสาธารณสุข เป็นเว็บแอพพลิเคชั่นที่พัฒนาเพื่อสนองนโยบายของทางกระทรวงสารธารณสุขเรื่อง *ปิงปองจราจรชิวิต 7สี* ที่ว่าด้วยเรื่องของการติดตามอาการของผู้ป่วยโรคเบาหวานและความดันโลหิตสูง


## Require
- MySQL  *v5.0.51b*
- PHP *v5.2.6*
- jhcisdb


## Getting started
1. ติดตั้ง *MySQL* และ *PHP*
2. ทำการ clone โปรเจ็คมาไว้ในเครื่องด้วยคำสั่ง `git clone git@github.com:gigsth/gisiph.git` หรือ [ดาวน์โหลด](https://codeload.github.com/gigsth/gisiph/zip/v1.0)ไฟล์ .zip แล้วแตกไฟล์ไปที่ root ของเซิฟเวอร์


## Create database
ลงชื่อเข้าใช้ MySQL ด้วยคำสั่ง `mysql -u <username> -p -d jhcisdb`

จากนั้นใช้คำสั่งต่อไปนี้เพื่อสร้างตารางในฐานข้อมูล

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
สามารถเข้าไปแก้ไขค่าเริ่มต้อนของการเชื่อต่อกับฐานข้อมูลได้ที่ `/path/to/you/workspace/gisiph/dist/php/configure.database.php`

## Wiki
รายละเอียดเอกสารสามารถอ่านได้[ที่นี่](https://github.com/gigsth/gisiph/wiki)
