-- 文件外链：限次、限时、可选密码、撤销与访问审计
-- 在目标库执行：mysql ... < database/schema/file_shares.sql

CREATE TABLE IF NOT EXISTS `file_shares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `user_upload_id` bigint unsigned NOT NULL,
  `token` char(64) NOT NULL COMMENT 'hex(32 bytes)',
  `password_hash` varchar(255) DEFAULT NULL,
  `max_views` int unsigned DEFAULT NULL COMMENT 'NULL 表示不限次数',
  `view_count` int unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime DEFAULT NULL COMMENT 'NULL 表示不过期',
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_file_shares_token` (`token`),
  KEY `idx_file_shares_user` (`user_id`),
  KEY `idx_file_shares_upload` (`user_upload_id`),
  CONSTRAINT `fk_file_shares_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_shares_upload` FOREIGN KEY (`user_upload_id`) REFERENCES `user_uploads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `file_share_access_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_share_id` bigint unsigned NOT NULL,
  `action` varchar(32) NOT NULL,
  `detail` varchar(64) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fslog_share_created` (`file_share_id`,`created_at`),
  CONSTRAINT `fk_fslog_share` FOREIGN KEY (`file_share_id`) REFERENCES `file_shares` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
