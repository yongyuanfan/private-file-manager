SET NAMES utf8mb4;

ALTER TABLE `file_shares`
  ADD COLUMN `purpose` enum('share','retention') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'share' COMMENT 'share=外链分享 retention=文件有效期控制' AFTER `user_upload_id`;

ALTER TABLE `file_shares`
  ADD KEY `idx_file_shares_purpose_token` (`purpose`,`token`),
  ADD KEY `idx_file_shares_upload_purpose` (`user_upload_id`,`purpose`);

CREATE TABLE IF NOT EXISTS `user_external_upload_auths` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA256(hex) 哈希，不存明文 token',
  `status` enum('active','disabled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `default_subdir` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `retention_ttl_days` int(10) unsigned DEFAULT NULL COMMENT 'NULL 表示永久有效',
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `last_used_at` datetime(3) DEFAULT NULL,
  `revoked_at` datetime(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_external_auth_token_hash` (`token_hash`),
  KEY `idx_external_auth_user_status` (`user_id`,`status`),
  CONSTRAINT `fk_external_auth_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='第三方上传授权';

CREATE TABLE IF NOT EXISTS `external_upload_auth_access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_upload_auth_id` bigint(20) unsigned NOT NULL,
  `user_upload_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eual_auth_created` (`external_upload_auth_id`,`created_at`),
  KEY `idx_eual_upload` (`user_upload_id`),
  CONSTRAINT `fk_eual_auth` FOREIGN KEY (`external_upload_auth_id`) REFERENCES `user_external_upload_auths` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eual_upload` FOREIGN KEY (`user_upload_id`) REFERENCES `user_uploads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='第三方上传授权审计日志';

-- 示例：为用户 id=1 创建一个永久有效授权（请先替换 token_hash）
-- INSERT INTO `user_external_upload_auths`
-- (`user_id`, `name`, `token_hash`, `status`, `default_subdir`, `retention_ttl_days`, `created_at`)
-- VALUES
-- (1, '第三方系统A', '<sha256_hex_token>', 'active', 'external', NULL, NOW(3));
