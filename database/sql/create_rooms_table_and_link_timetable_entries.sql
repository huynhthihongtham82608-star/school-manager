-- Create the Rooms foundation table and link timetable entries to rooms.
-- Existing timetable_entries.room text values are kept as display snapshots.

CREATE TABLE IF NOT EXISTS `rooms` (
    `id` CHAR(36) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(30) NOT NULL DEFAULT 'standard',
    `custom_type` VARCHAR(255) NULL DEFAULT NULL,
    `capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 45,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `note` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rooms_name_unique` (`name`),
    KEY `rooms_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `timetable_entries`
    ADD COLUMN IF NOT EXISTS `room_id` VARCHAR(50) NULL AFTER `room`;

CREATE INDEX IF NOT EXISTS `timetable_entries_room_id_index` ON `timetable_entries` (`room_id`);
