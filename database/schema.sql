-- WorkShift Database Schema
-- Product: WorkShift by Studio Click Up
-- Target: MySQL 8.0+ / MariaDB 10.3+

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users (Freelancers)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `company_name` VARCHAR(191) NULL DEFAULT 'Studio Click Up Freelance',
  `plan` ENUM('basic', 'pro') NOT NULL DEFAULT 'basic',
  `scheduling_link` VARCHAR(255) NULL,
  `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 500.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'PHP',
  `storage_used_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Password Resets
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(191) NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pw_resets_email_token` (`email`, `token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Login Attempts (Rate Limiting)
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_login_attempts_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Clients (CRM records)
DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `company` VARCHAR(191) NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `notes` TEXT NULL,
  `billing_type` ENUM('hourly', 'fixed') NOT NULL DEFAULT 'hourly',
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `pipeline_stage` ENUM('inquiry', 'active', 'completed', 'archived') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_clients_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_clients_user_stage` (`user_id`, `pipeline_stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Boards (One dedicated board per client)
DROP TABLE IF EXISTS `boards`;
CREATE TABLE `boards` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_boards_client_id` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_boards_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Stages (Board columns: To Do, In Progress, In Review, Done)
DROP TABLE IF EXISTS `stages`;
CREATE TABLE `stages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `is_review_stage` TINYINT(1) NOT NULL DEFAULT 0,
  `is_done_stage` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_stages_board_id` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE,
  INDEX `idx_stages_board_pos` (`board_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tasks (Task cards)
DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id` INT UNSIGNED NOT NULL,
  `stage_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `due_date` DATE NULL,
  `position` INT NOT NULL DEFAULT 0,
  `review_status` ENUM('pending', 'approved', 'changes_requested') NOT NULL DEFAULT 'pending',
  `review_notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tasks_board_id` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_stage_id` FOREIGN KEY (`stage_id`) REFERENCES `stages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_tasks_stage_pos` (`stage_id`, `position`),
  INDEX `idx_tasks_board` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Task Blockers (Signature feature: ONE blocker per task)
DROP TABLE IF EXISTS `task_blockers`;
CREATE TABLE `task_blockers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT UNSIGNED NOT NULL UNIQUE,
  `type` ENUM('feedback', 'content', 'payment', 'scheduling') NOT NULL,
  `reason` TEXT NOT NULL,
  `waiting_on` ENUM('client', 'freelancer') NOT NULL DEFAULT 'client',
  `waiting_since` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_blockers_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  INDEX `idx_blockers_waiting` (`waiting_on`, `waiting_since`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Time Entries (Time tracker)
DROP TABLE IF EXISTS `time_entries`;
CREATE TABLE `time_entries` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `entry_date` DATE NOT NULL,
  `duration_minutes` INT NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `is_private` TINYINT(1) NOT NULL DEFAULT 0,
  `is_running` TINYINT(1) NOT NULL DEFAULT 0,
  `timer_started_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_time_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_time_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_time_client_id` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  INDEX `idx_time_user_date` (`user_id`, `entry_date`),
  INDEX `idx_time_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Files (Uploaded deliverables and proofs)
DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT UNSIGNED NOT NULL,
  `uploaded_by_user_id` INT UNSIGNED NULL,
  `uploaded_by_client` TINYINT(1) NOT NULL DEFAULT 0,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_filename` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_files_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  INDEX `idx_files_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Comments (Communication thread on task)
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `client_id` INT UNSIGNED NULL,
  `author_name` VARCHAR(191) NOT NULL,
  `is_client` TINYINT(1) NOT NULL DEFAULT 0,
  `content` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comments_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  INDEX `idx_comments_task` (`task_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Portal Tokens (Secure unguessable links for clients)
DROP TABLE IF EXISTS `portal_tokens`;
CREATE TABLE `portal_tokens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL UNIQUE,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_accessed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_portal_client_id` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  INDEX `idx_portal_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Invoices (Invoicing system)
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'PHP',
  `status` ENUM('draft', 'sent', 'paid', 'overdue') NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `paid_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_invoices_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoices_client_id` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  INDEX `idx_invoices_user_status` (`user_id`, `status`),
  INDEX `idx_invoices_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Invoice Items (Line items)
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_items_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  INDEX `idx_items_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Payments (Payment records)
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT UNSIGNED NOT NULL,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'mock',
  `reference_number` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'completed',
  `payload` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  INDEX `idx_payments_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Notifications (In-app notifications for freelancer)
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link_url` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notifs_user_read` (`user_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
