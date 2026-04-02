-- ============================================================
-- Run this SQL in your InfinityFree database (phpMyAdmin)
-- BEFORE deploying to Vercel
-- ============================================================

-- 1. Sessions table (required for login to work on Vercel)
CREATE TABLE IF NOT EXISTS `sessions` (
    `session_id`     VARCHAR(128)  NOT NULL,
    `session_data`   TEXT          NOT NULL,
    `session_expiry` INT UNSIGNED  NOT NULL,
    PRIMARY KEY (`session_id`),
    KEY `idx_expiry` (`session_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Expand photo/image columns to hold base64 data URLs
--    (Vercel has no persistent filesystem, so images are stored in the DB)
ALTER TABLE `users`    MODIFY `photo` MEDIUMTEXT;
ALTER TABLE `projects` MODIFY `image` MEDIUMTEXT;
