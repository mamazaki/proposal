-- --------------------------------------------------------
-- Database: `working_group_system`
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- --------------------------------------------------------
-- 1. Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT 'รหัสผู้ใช้ / ชื่อผู้ใช้',
  `full_name` varchar(100) NOT NULL COMMENT 'ชื่อ-นามสกุลจริง',
  `password_hash` varchar(255) NOT NULL,
  `agency_name` varchar(100) NOT NULL COMMENT 'หน่วยงานต้นสังกัด',
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- แทรกข้อมูล Admin เริ่มต้น (รหัสผ่านคือ: password)
INSERT INTO `users` (`id`, `username`, `full_name`, `password_hash`, `agency_name`, `role`, `created_at`) VALUES
(1, 'admin', 'ผู้ดูแลระบบสูงสุด', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สำนักงานศึกษาธิการจังหวัดอุดรธานี', 'admin', CURRENT_TIMESTAMP);

-- --------------------------------------------------------
-- 2. Table structure for table `main_topics`
-- --------------------------------------------------------
CREATE TABLE `main_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'ชื่อหัวข้อเรื่องหลัก',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=เปิดใช้งาน, 0=ปิดใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- แทรกข้อมูล Default: หัวข้อเรื่องหลัก (MOU)
INSERT INTO `main_topics` (`id`, `title`, `is_active`) VALUES
(1, 'การขับเคลื่อนข้อเสนอการบริหารงานเชิงพื้นที่แบบบูรณาการด้านการศึกษา ภายใต้ MOU การยกระดับประสิทธิภาพและประสิทธิผลการจัดการศึกษา', 1);

-- --------------------------------------------------------
-- 3. Table structure for table `sub_groups`
-- --------------------------------------------------------
CREATE TABLE `sub_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `main_topic_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'ชื่อประเด็น/คณะทำงานย่อย',
  `sort_order` int(11) NOT NULL DEFAULT '99' COMMENT 'ลำดับการจัดเรียง',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `main_topic_id` (`main_topic_id`),
  CONSTRAINT `fk_sub_group_main` FOREIGN KEY (`main_topic_id`) REFERENCES `main_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- แทรกข้อมูล Default: 11 ประเด็นย่อย
INSERT INTO `sub_groups` (`id`, `main_topic_id`, `title`, `sort_order`) VALUES
(1, 1, 'ประเด็นที่ 1: การยกระดับคุณภาพการศึกษา', 1),
(2, 1, 'ประเด็นที่ 2: การพัฒนาหลักสูตรและการจัดการเรียนรู้แบบ Active Learning', 2),
(3, 1, 'ประเด็นที่ 3: การขับเคลื่อนระบบธนาคารหน่วยกิต (Credit Bank)', 3),
(4, 1, 'ประเด็นที่ 4: การส่งเสริมสนับสนุนกิจกรรมเรียนได้ทุกที่ ทุกเวลา (Anywhere Anytime)', 4),
(5, 1, 'ประเด็นที่ 5: การส่งเสริมสุขภาพจิตและดูแลความปลอดภัยของผู้เรียน', 5),
(6, 1, 'ประเด็นที่ 6: การแก้ไขปัญหาหนี้สินครูและบุคลากรทางการศึกษา', 6),
(7, 1, 'ประเด็นที่ 7: การประเมินและการประกันคุณภาพสถานศึกษา', 7),
(8, 1, 'ประเด็นที่ 8: การมีรายได้ระหว่างเรียน จบแล้วมีงานทำ (Learn to Earn)', 8),
(9, 1, 'ประเด็นที่ 9: การส่งเสริมทักษะอาชีพและการแข่งขันทางการศึกษา', 9),
(10, 1, 'ประเด็นที่ 10: การพัฒนาระบบข้อมูลสารสนเทศและเทคโนโลยีดิจิทัลด้านการศึกษา', 10),
(11, 1, 'ประเด็นที่ 11: การสร้างโอกาสให้คนทุกช่วงวัยเข้าถึงการศึกษาและการฝึกอบรมอย่างเท่าเทียมและมีคุณภาพ (SDG4)', 11);

-- --------------------------------------------------------
-- 4. Table structure for table `committee_members`
-- --------------------------------------------------------
CREATE TABLE `committee_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sub_group_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'ID ผู้เพิ่มข้อมูล',
  `wg_position` varchar(100) NOT NULL COMMENT 'ตำแหน่งในคณะทำงาน',
  `sort_order` int(11) NOT NULL DEFAULT '50' COMMENT 'ลำดับบุคคลในคณะ',
  `prefix` varchar(50) NOT NULL COMMENT 'คำนำหน้า',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `job_position` varchar(150) NOT NULL COMMENT 'ตำแหน่งหน้าที่ (ทางสายงาน)',
  `agency` varchar(150) NOT NULL COMMENT 'สังกัด',
  `phone_number` varchar(50) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP ผู้บันทึก',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Soft Delete (1=ปกติ, 0=ลบ)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sub_group_id` (`sub_group_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_committee_sub` FOREIGN KEY (`sub_group_id`) REFERENCES `sub_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_committee_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table structure for table `audit_logs`
-- --------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL COMMENT 'INSERT, UPDATE, DELETE, LOGIN',
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL COMMENT 'ID ของข้อมูลที่ถูกกระทำ',
  `detail` text DEFAULT NULL COMMENT 'เก็บข้อมูล JSON Diff',
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;