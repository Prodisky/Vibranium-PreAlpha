長照隊長 汎合金圓盾 App 技術文件 Pre-alpha 版

# 簡介
這份文件用來記錄並隨時更新 長照隊長 汎合金圓盾 App 開發的技術資訊。

## 目標
- 長照隊長 汎合金圓盾 App 後端系統資料庫與 API 設計。

## 主要內容
- Data
- API
- Web
- App

## 系統架構
- 資料存放於雲端主機，後台網頁或 App 以直接檔案存取與 API 呼叫進行操作。
- PHP
- MySQL



# Data
資料庫的資料結構設計與檔案、目錄結構設計，由於有可能讓 App 離線新增資料，所以資料 ID 都使用 UUID 型態。

由於本案使用 MySQL 資料庫，所以用 MySQL 的 SQL 語法，並輔以註解來說明資料庫結構，以下資料庫的說明部分可直接複製貼上，用以建立資料表格。
/**************************************************
## control 系統控制資料
儲存於 MySQL 資料庫系統的控制資料表。
相關資料可在 Web 後台建立或維護。
**************************************************/

/* 使用者 */
/* 以 SHA512 編碼進行密碼保護，資料庫存放的 password 是 loginid@vibranium@使用者輸入密碼 的 SHA512 編碼，登入時再使用每個 session 產生的隨機的 token 作保護，為 password@token 字串的 SHA512 編碼。 */
CREATE TABLE `user` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串，亦作為使用者照片主檔名用',
	`name`  varchar(60)  NOT NULL  DEFAULT '' COMMENT '姓名',
	`call`  varchar(60)  NOT NULL  DEFAULT '' COMMENT '稱呼',
	`loginid`  varchar(60)  NOT NULL  DEFAULT '' COMMENT '登入帳號',
	`password`  varchar(150)  NOT NULL  DEFAULT '' COMMENT '編碼後之登入密碼，以 loginid@vibranium@使用者輸入密碼 組合成字串再以 SHA512 編碼。',
	`type`  tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '使用者類型 0:無 1:隊長 2:隊員 3:客戶 7:系統',
	`available`  tinyint(1)  NOT NULL  DEFAULT '1' COMMENT '有效帳號 0:無效 1:有效',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	`permission_control` tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '系統控制權限，包含使用者的權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)',
	`permission_captain` tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '隊長資料權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)',
	`permission_crew` tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '隊員資料權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)',
	`permission_customer` tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '客戶資料權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)',
	`permission_system` tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '系統資料權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)',
	PRIMARY KEY (`uuid`),
	UNIQUE KEY `user_loginid` (`loginid`),
	KEY `user_name` (`name`),
	KEY `user_update_time` (`update_time`),
	KEY `user_available` (`available`),
	KEY `user_type` (`type`),
	KEY `user_create_time` (create_time))
COMMENT '使用者';

DELIMITER ;;
CREATE TRIGGER `user_BEFORE_INSERT` BEFORE INSERT ON `user` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;



/**************************************************
## captain 隊長層級資料：只有隊長可以進行管理及存取資料
包含團隊建立與團隊成員設定。
**************************************************/

/* 團隊基本資料 */
CREATE TABLE `team` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`captain`  char(32)  NOT NULL  DEFAULT '' COMMENT '隊長',
	`name`  varchar(60)  NOT NULL  DEFAULT '' COMMENT '團隊名稱',
	`memo`  longtext  NOT NULL  COMMENT '備註',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `team_captain` (`captain`),
	KEY `team_name` (`name`),
	KEY `team_update_time` (`update_time`),
	KEY `team_create_time` (create_time),
	KEY `team_update_user` (`update_user`),
	CONSTRAINT `team_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '團隊基本資料';

DELIMITER ;;
CREATE TRIGGER `team_BEFORE_INSERT` BEFORE INSERT ON `team` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 團隊成員 */
CREATE TABLE `team_user` (
	`team`  char(32)  NOT NULL  DEFAULT '' COMMENT '團隊',
	`user`  char(32)  NOT NULL  DEFAULT '' COMMENT '使用者',
	`type`  tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '加入類型 0:無 1:隊長 2:隊員 3:其他 7:系統',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`team`,`user`),
	KEY `team_user_type` (`type`),
	KEY `team_user_create_time` (create_time),
	KEY `team_user_update_user` (`update_user`),
	KEY `team_user_team_user` (`team`),
	KEY `team_user_user` (`user`),
	CONSTRAINT `team_user_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `team_user_team_user` FOREIGN KEY (`team`) REFERENCES `team` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `team_user_user` FOREIGN KEY (`user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '團隊成員';

DELIMITER ;;
CREATE TRIGGER `team_user_BEFORE_INSERT` BEFORE INSERT ON `team_user` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;



/**************************************************
## crew 隊員層級資料：隊長及隊員可存取資料
包含會議討論。
**************************************************/

/* 會議基本資料 */
CREATE TABLE `meeting` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`name`  varchar(60)  NOT NULL  DEFAULT '' COMMENT '會議名稱',
	`memo`  longtext  NOT NULL  COMMENT '備註',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `meeting_name` (`name`),
	KEY `meeting_update_time` (`update_time`),
	KEY `meeting_create_time` (create_time),
	KEY `meeting_update_user` (`update_user`),
	CONSTRAINT `meeting_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '會議基本資料';

DELIMITER ;;
CREATE TRIGGER `meeting_BEFORE_INSERT` BEFORE INSERT ON `meeting` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 會議紀錄 */
CREATE TABLE `meeting_log` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`meeting`  char(32)  NOT NULL  DEFAULT '' COMMENT '會議',
	`log`  longtext  NOT NULL  COMMENT '紀錄',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `meeting_log_meeting` (`meeting`),
	KEY `meeting_log_create_time` (create_time),
	KEY `meeting_log_update_user` (`update_user`),
	CONSTRAINT `meeting_log_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '會議紀錄';

DELIMITER ;;
CREATE TRIGGER `meeting_log_BEFORE_INSERT` BEFORE INSERT ON `meeting_log` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;



/**************************************************
## customer 客戶層級資料：隊長、隊員及客戶可存取資料
包含照護計畫案。
**************************************************/

/* 照護計畫案基本資料 */
CREATE TABLE `project` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`name`  varchar(60)  NOT NULL  DEFAULT '' COMMENT '照護計畫案名稱',
	`memo`  longtext  NOT NULL  COMMENT '備註',
	`startdate`  date  NOT NULL  DEFAULT '0000-00-00' COMMENT '開始日期',
	`enddate`  date  NOT NULL  DEFAULT '0000-00-00' COMMENT '結案日期',
	`captain_note`  longtext  NOT NULL  COMMENT '隊長給的注意事項',
	`customer_note`  longtext  NOT NULL  COMMENT '客戶給的注意事項',
	`from_customer`  char(32)  NOT NULL  DEFAULT '' COMMENT '最後修改注意事項的客戶',
	`finished`  tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '是否結案 0:未結案 1:已結案',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `project_name` (`name`),
	KEY `project_update_time` (`update_time`),
	KEY `project_create_time` (create_time),
	KEY `project_update_user` (`update_user`),
	CONSTRAINT `project_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '照護計畫案基本資料';

DELIMITER ;;
CREATE TRIGGER `project_BEFORE_INSERT` BEFORE INSERT ON `project` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 照護計畫案成員 */
/* 包含隊長、隊員及客戶。 */
CREATE TABLE `project_user` (
	`project`  char(32)  NOT NULL  DEFAULT '' COMMENT '照護計畫案',
	`user`  char(32)  NOT NULL  DEFAULT '' COMMENT '使用者',
	`type`  tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '加入類型 0:無 1:隊長 2:隊員 3:客戶 7:系統',
	`memo`  longtext  NOT NULL  COMMENT '備註',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`project`,`user`),
	KEY `project_user_type` (`type`),
	KEY `project_user_update_time` (`update_time`),
	KEY `project_user_create_time` (create_time),
	KEY `project_user_project` (`project`),
	KEY `project_user_user` (`user`),
	KEY `project_user_update_user` (`update_user`),
	CONSTRAINT `project_user_project` FOREIGN KEY (`project`) REFERENCES `project` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `project_user_user` FOREIGN KEY (`user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `project_user_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '照護計畫案成員';

DELIMITER ;;
CREATE TRIGGER `project_user_BEFORE_INSERT` BEFORE INSERT ON `project_user` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 照護任務 */
/* 隊長分派給隊員的任務與時數 */
CREATE TABLE `project_task` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`project`  char(32)  NOT NULL  DEFAULT '' COMMENT '照護計畫案',
	`user`  char(32)  NOT NULL  DEFAULT '' COMMENT '使用者',
	`name`  varchar(60)  NOT NULL  DEFAULT '' COMMENT '任務名稱',
	`time`  double  NOT NULL  DEFAULT '0' COMMENT '時數',
	`memo`  longtext  NOT NULL  COMMENT '備註',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `project_task_name` (`name`),
	KEY `project_task_update_time` (`update_time`),
	KEY `project_task_create_time` (create_time),
	KEY `project_task_project` (`project`),
	KEY `project_task_user` (`user`),
	KEY `project_task_update_user` (`update_user`),
	CONSTRAINT `project_task_project` FOREIGN KEY (`project`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `project_task_user` FOREIGN KEY (`user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `project_task_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '照護任務';

DELIMITER ;;
CREATE TRIGGER `project_task_BEFORE_INSERT` BEFORE INSERT ON `project_task` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 照護紀錄 */
/* 包含時程排定與執行時數與執行狀況。 */
CREATE TABLE `project_log` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`name`  varchar(60)  NOT NULL  DEFAULT '' COMMENT '名稱',
	`project_task`  char(32)  NOT NULL  DEFAULT '' COMMENT '照護任務',
	`plan_starttime`  datetime  NOT NULL  DEFAULT '0000-00-00 00:00:00' COMMENT '計畫開始時間',
	`plan_endtime`  datetime  NOT NULL  DEFAULT '0000-00-00 00:00:00' COMMENT '計畫完成時間',
	`plan_time`  double  NOT NULL  DEFAULT '0' COMMENT '計畫執行時數',
	`real_starttime`  datetime  NOT NULL  DEFAULT '0000-00-00 00:00:00' COMMENT '實際開始時間',
	`real_endtime`  datetime  NOT NULL  DEFAULT '0000-00-00 00:00:00' COMMENT '實際完成時間',
	`real_time`  double  NOT NULL  DEFAULT '0' COMMENT '實際執行時數',
	`note2crew`  longtext  NOT NULL  COMMENT '給隊員的注意事項',
	`note2customer`  longtext  NOT NULL  COMMENT '給客戶的注意事項',
	`feel_from_crew`  tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '隊員此次工作感受',
	`score_from_customer`  tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '客戶評分',
	`from_customer`  char(32)  NOT NULL  DEFAULT '' COMMENT '最後評分的客戶',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `project_log_name` (`name`),
	KEY `project_log_update_time` (`update_time`),
	KEY `project_log_create_time` (create_time),
	KEY `project_log_update_user` (`update_user`),
	CONSTRAINT `project_log_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '照護紀錄';

DELIMITER ;;
CREATE TRIGGER `project_log_BEFORE_INSERT` BEFORE INSERT ON `project_log` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 每週可工作時間的週期設定 */
CREATE TABLE `workday_week` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`user`  char(32)  NOT NULL  DEFAULT '' COMMENT '使用者',
	`weekday`  tinyint(1)  NOT NULL  DEFAULT '0' COMMENT '星期 0:日 1:一 2:二 3:三 4:四 5:五 6:六',
	`starttime`  time  NOT NULL  DEFAULT '00:00:00' COMMENT '開始時間',
	`endtime`  time  NOT NULL  DEFAULT '00:00:00' COMMENT '結束時間',
	`memo`  longtext  NOT NULL  COMMENT '備註',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `workday_week_update_time` (`update_time`),
	KEY `workday_week_create_time` (create_time),
	KEY `workday_week_user` (`user`),
	KEY `workday_week_update_user` (`update_user`),
	CONSTRAINT `workday_week_user` FOREIGN KEY (`user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `workday_week_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '每週可工作時間的週期設定';

DELIMITER ;;
CREATE TRIGGER `workday_week_BEFORE_INSERT` BEFORE INSERT ON `workday_week` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 實際可工作時間設定 */
CREATE TABLE `workday_real` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`user`  char(32)  NOT NULL  DEFAULT '' COMMENT '使用者',
	`starttime`  datetime  NOT NULL  DEFAULT '0000-00-00 00:00:00' COMMENT '可開始工作時間',
	`endtime`  datetime  NOT NULL  DEFAULT '0000-00-00 00:00:00' COMMENT '需結束工作時間',
	`memo`  longtext  NOT NULL  COMMENT '備註',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `workday_real_update_time` (`update_time`),
	KEY `workday_real_create_time` (create_time),
	KEY `workday_real_user` (`user`),
	KEY `workday_real_update_user` (`update_user`),
	CONSTRAINT `workday_real_user` FOREIGN KEY (`user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `workday_real_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '實際可工作時間設定';

DELIMITER ;;
CREATE TRIGGER `workday_real_BEFORE_INSERT` BEFORE INSERT ON `workday_real` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 聯絡人 */
CREATE TABLE `contact` (
	`user`  char(32)  NOT NULL  DEFAULT '' COMMENT '使用者',
	`contact`  char(32)  NOT NULL  DEFAULT '' COMMENT '聯絡人',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`user`,`contact`),
	KEY `contact_update_time` (`update_time`),
	KEY `contact_create_time` (create_time),
	KEY `contact_user` (`user`),
	KEY `contact_contact` (`contact`),
	KEY `contact_update_user` (`update_user`),
	CONSTRAINT `contact_user` FOREIGN KEY (`user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `contact_contact` FOREIGN KEY (`contact`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `contact_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '聯絡人';

DELIMITER ;;
CREATE TRIGGER `contact_BEFORE_INSERT` BEFORE INSERT ON `contact` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;


/* 訊息 */
CREATE TABLE `message` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`from`  char(32)  NOT NULL  DEFAULT '' COMMENT '來源使用者',
	`to`  char(32)  NOT NULL  DEFAULT '' COMMENT '目的使用者',
	`message`  longtext  NOT NULL  COMMENT '訊息',
	`update_user`  char(32)  NOT NULL  DEFAULT '' COMMENT '更新使用者的 UUID',
	`update_time`  timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
	`create_time`  TIMESTAMP  NOT NULL ,
	PRIMARY KEY (`uuid`),
	KEY `message_update_time` (`update_time`),
	KEY `message_create_time` (create_time),
	KEY `message_from` (`from`),
	KEY `message_to` (`to`),
	KEY `message_update_user` (`update_user`),
	CONSTRAINT `message_from` FOREIGN KEY (`from`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `message_to` FOREIGN KEY (`to`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT `message_update_user` FOREIGN KEY (`update_user`) REFERENCES `user` (`uuid`) ON DELETE RESTRICT ON UPDATE RESTRICT)
COMMENT '訊息';

DELIMITER ;;
CREATE TRIGGER `message_BEFORE_INSERT` BEFORE INSERT ON `message` FOR EACH ROW
BEGIN
    SET NEW.create_time = CURRENT_TIMESTAMP;
END;;
DELIMITER ;



/**************************************************
## system 系統資料
系統運作需要的資料表
**************************************************/

/* 操作記錄 */
/* 用於系統連線運作機制，不提供 API 進行資料操作。 */
CREATE TABLE `log` (
	`uuid`  char(32)  NOT NULL  DEFAULT '' COMMENT 'UUID 去除連字號 - 的 UUID 字串',
	`ip`  varchar(40)  NOT NULL  DEFAULT '' COMMENT '使用的 IP，可能是 IPv6 所以用 40 字元長度。',
	`log`  longtext  NOT NULL  COMMENT '操作記錄',
	`user`  char(32)  NOT NULL  DEFAULT '' COMMENT '登入使用者 UUID',
	`device`  char(32)  NOT NULL  DEFAULT '' COMMENT '登入裝置 UUID',
	PRIMARY KEY (`uuid`),
	KEY `log_user_device` (`user`,`device`),
	KEY `log_ip` (`ip`))
COMMENT '操作記錄';





# API
前端 App 與後台 Web 介面操作資料皆透過 API 呼叫來進行。

## 系統控制資料
儲存於 MySQL 資料庫系統的控制資料表。
相關資料可在 Web 後台建立或維護。

### /user/ 使用者
以 SHA512 編碼進行密碼保護，資料庫存放的 password 是 loginid@vibranium@使用者輸入密碼 的 SHA512 編碼，登入時再使用每個 session 產生的隨機的 token 作保護，為 password@token 字串的 SHA512 編碼。

#### login 登入
	- Web 端登入需要帳號、密碼
	- App 端登入需要帳號、密碼、裝置 ID
	- 裝置 ID 不在裝置設定中會新增裝置設定，如為有 Apple 用戶之使用者登入，則會自動信任裝置，否則需自後台進行裝置信任動作。
	- 如果使用者帳號被設定為需要變更密碼，則需要詢問使用者新密碼。

#### logout 登出

#### add 新增使用者資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串，亦作為使用者照片主檔名用
	name - 姓名
	call - 稱呼
	loginid - 登入帳號
	password - 編碼後之登入密碼，以 loginid@vibranium@使用者輸入密碼 組合成字串再以 SHA512 編碼。
	type - 使用者類型 0:無 1:隊長 2:隊員 3:客戶 7:系統
	available - 有效帳號 0:無效 1:有效
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得使用者資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, name, call, loginid, password, type, available, update_time 欄位資料或錯誤訊息。

#### set 設定使用者資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
傳入資料欄位：
	name - 姓名
	call - 稱呼
	loginid - 登入帳號
	password - 編碼後之登入密碼，以 loginid@vibranium@使用者輸入密碼 組合成字串再以 SHA512 編碼。
	type - 使用者類型 0:無 1:隊長 2:隊員 3:客戶 7:系統
	available - 有效帳號 0:無效 1:有效
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除使用者資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。






## 隊長層級資料：只有隊長可以進行管理及存取資料
包含團隊建立與團隊成員設定。

### /team/ 團隊基本資料

#### add 新增團隊基本資料資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	captain - 隊長
	name - 團隊名稱
	memo - 備註
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得團隊基本資料資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, captain, name, memo, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定團隊基本資料資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	captain - 隊長
	name - 團隊名稱
	memo - 備註
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除團隊基本資料資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /team_user/ 團隊成員

#### add 新增團隊成員資料
傳入新增資料欄位：
	team - 團隊
	user - 使用者
	type - 加入類型 0:無 1:隊長 2:隊員 3:其他 7:系統
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得團隊成員資料
傳入過濾欄位參數：
	無參數：取得所有資料
	team, user
回傳資訊：
	執行結果、資料筆數與 team, user, type, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定團隊成員資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	team - 團隊
	user - 使用者
	type - 加入類型 0:無 1:隊長 2:隊員 3:其他 7:系統
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除團隊成員資料
傳入刪除條件參數：
	team, user
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。






## 隊員層級資料：隊長及隊員可存取資料
包含會議討論。

### /meeting/ 會議基本資料

#### add 新增會議基本資料資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	name - 會議名稱
	memo - 備註
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得會議基本資料資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, name, memo, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定會議基本資料資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	name - 會議名稱
	memo - 備註
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除會議基本資料資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /meeting_log/ 會議紀錄

#### add 新增會議紀錄資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	meeting - 會議
	log - 紀錄
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得會議紀錄資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, meeting, log, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定會議紀錄資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	meeting - 會議
	log - 紀錄
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除會議紀錄資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。






## 客戶層級資料：隊長、隊員及客戶可存取資料
包含照護計畫案。

### /project/ 照護計畫案基本資料

#### add 新增照護計畫案基本資料資料
	檢查是否被鎖定，是的話要鎖定者使用鎖定裝置才允許新增。
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	name - 照護計畫案名稱
	memo - 備註
	captain_note - 隊長給的注意事項
	customer_note - 客戶給的注意事項
	from_customer - 最後修改注意事項的客戶
	finished - 是否結案 0:未結案 1:已結案
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得照護計畫案基本資料資料
傳入過濾欄位參數：
	finished
	uuid, finished
回傳資訊：
	執行結果、資料筆數與 uuid, name, memo, startdate, enddate, captain_note, customer_note, from_customer, finished, update_user, update_time 欄位資料或錯誤訊息。

#### search 搜尋照護計畫案基本資料資料
參數：
	q 搜尋所包含之字串, finished
搜尋欄位：
	name, memo
回傳資訊：
	執行結果、資料筆數與 uuid, name, memo, startdate, enddate, captain_note, customer_note, from_customer, finished, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定照護計畫案基本資料資料
	檢查是否被鎖定，是的話要鎖定者使用鎖定裝置才允許更新。
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	name - 照護計畫案名稱
	memo - 備註
	startdate - 開始日期
	enddate - 結案日期
	captain_note - 隊長給的注意事項
	customer_note - 客戶給的注意事項
	from_customer - 最後修改注意事項的客戶
	finished - 是否結案 0:未結案 1:已結案
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除照護計畫案基本資料資料
	檢查是否被鎖定，是的話要鎖定者使用鎖定裝置才允許刪除。
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /project_user/ 照護計畫案成員
包含隊長、隊員及客戶。

#### add 新增照護計畫案成員資料
傳入新增資料欄位：
	project - 照護計畫案
	user - 使用者
	type - 加入類型 0:無 1:隊長 2:隊員 3:客戶 7:系統
	memo - 備註
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得照護計畫案成員資料
傳入過濾欄位參數：
	無參數：取得所有資料
	project, user
回傳資訊：
	執行結果、資料筆數與 project, user, type, memo, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定照護計畫案成員資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	project - 照護計畫案
	user - 使用者
	type - 加入類型 0:無 1:隊長 2:隊員 3:客戶 7:系統
	memo - 備註
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除照護計畫案成員資料
傳入刪除條件參數：
	project, user
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /project_task/ 照護任務
隊長分派給隊員的任務與時數

#### add 新增照護任務資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	project - 照護計畫案
	user - 使用者
	name - 任務名稱
	time - 時數
	memo - 備註
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得照護任務資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, project, user, name, time, memo, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定照護任務資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	project - 照護計畫案
	user - 使用者
	name - 任務名稱
	time - 時數
	memo - 備註
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除照護任務資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /project_log/ 照護紀錄
包含時程排定與執行時數與執行狀況。

#### add 新增照護紀錄資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	name - 名稱
	project_task - 照護任務
	plan_time - 計畫執行時數
	real_time - 實際執行時數
	note2crew - 給隊員的注意事項
	note2customer - 給客戶的注意事項
	feel_from_crew - 隊員此次工作感受
	score_from_customer - 客戶評分
	from_customer - 最後評分的客戶
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得照護紀錄資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, name, project_task, plan_starttime, plan_endtime, plan_time, real_starttime, real_endtime, real_time, note2crew, note2customer, feel_from_crew, score_from_customer, from_customer, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定照護紀錄資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	name - 名稱
	project_task - 照護任務
	plan_starttime - 計畫開始時間
	plan_endtime - 計畫完成時間
	plan_time - 計畫執行時數
	real_starttime - 實際開始時間
	real_endtime - 實際完成時間
	real_time - 實際執行時數
	note2crew - 給隊員的注意事項
	note2customer - 給客戶的注意事項
	feel_from_crew - 隊員此次工作感受
	score_from_customer - 客戶評分
	from_customer - 最後評分的客戶
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除照護紀錄資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /workday_week/ 每週可工作時間的週期設定

#### add 新增每週可工作時間的週期設定資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	user - 使用者
	weekday - 星期 0:日 1:一 2:二 3:三 4:四 5:五 6:六
	memo - 備註
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得每週可工作時間的週期設定資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, user, weekday, starttime, endtime, memo, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定每週可工作時間的週期設定資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	user - 使用者
	weekday - 星期 0:日 1:一 2:二 3:三 4:四 5:五 6:六
	starttime - 開始時間
	endtime - 結束時間
	memo - 備註
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除每週可工作時間的週期設定資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /workday_real/ 實際可工作時間設定

#### add 新增實際可工作時間設定資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	user - 使用者
	memo - 備註
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得實際可工作時間設定資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, user, starttime, endtime, memo, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定實際可工作時間設定資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	user - 使用者
	starttime - 可開始工作時間
	endtime - 需結束工作時間
	memo - 備註
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除實際可工作時間設定資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /contact/ 聯絡人

#### add 新增聯絡人資料
傳入新增資料欄位：
	user - 使用者
	contact - 聯絡人
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得聯絡人資料
傳入過濾欄位參數：
	無參數：取得所有資料
	user, contact
回傳資訊：
	執行結果、資料筆數與 user, contact, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定聯絡人資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	user - 使用者
	contact - 聯絡人
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除聯絡人資料
傳入刪除條件參數：
	user, contact
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。



### /message/ 訊息

#### add 新增訊息資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	from - 來源使用者
	to - 目的使用者
	message - 訊息
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得訊息資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, from, to, message, update_user, update_time 欄位資料或錯誤訊息。

#### set 設定訊息資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
傳入資料欄位：
	from - 來源使用者
	to - 目的使用者
	message - 訊息
	update_time - 更新時間
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除訊息資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。






## 系統資料
系統運作需要的資料表

### /log/ 操作記錄
用於系統連線運作機制，不提供 API 進行資料操作。

#### add 新增操作記錄資料
傳入新增資料欄位：
	uuid - UUID 去除連字號 - 的 UUID 字串
	ip - 使用的 IP，可能是 IPv6 所以用 40 字元長度。
	log - 操作記錄
	user - 登入使用者 UUID
	device - 登入裝置 UUID
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### get 取得操作記錄資料
傳入過濾欄位參數：
	無參數：取得所有資料
	uuid
回傳資訊：
	執行結果、資料筆數與 uuid, ip, log, user, device 欄位資料或錯誤訊息。

#### set 設定操作記錄資料
傳入資料欄位：
	ip - 使用的 IP，可能是 IPv6 所以用 40 字元長度。
	log - 操作記錄
	user - 登入使用者 UUID
	device - 登入裝置 UUID
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。

#### del 刪除操作記錄資料
傳入刪除條件參數：
	uuid
回傳資訊：
	執行結果與影響資料筆數或錯誤訊息。








# Web
後台功能，以 Bootstrap 框架與 Vue 進行前端資料操作 UI 設計，並透過 API 呼叫來操作系統。


# App
iOS Universal App，支援 iOS 11 及以上版本，使用 Swift 4 開發。


