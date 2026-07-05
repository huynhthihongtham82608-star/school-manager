-- Create only the table required for Subject period norms by grade.
-- This is an extension of Subject management, not a standalone module.

CREATE TABLE IF NOT EXISTS `subject_period_norms` (
    `id` CHAR(36) NOT NULL,
    `subject_id` VARCHAR(50) NOT NULL,
    `grade_level` TINYINT UNSIGNED NOT NULL,
    `periods_per_week` TINYINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `subject_grade_period_norm_unique` (`subject_id`, `grade_level`),
    KEY `subject_period_norms_subject_id_index` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
