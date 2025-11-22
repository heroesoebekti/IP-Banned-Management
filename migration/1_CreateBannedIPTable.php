<?php

use SLiMS\DB;
use SLiMS\Migration\Migration;

class CreateBannedIPTable extends Migration {
    function up(){
        DB::getInstance()->query("
            DROP TABLE IF EXISTS `ip_log`;
                CREATE TABLE `ip_log` (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    ip_address VARCHAR(45) NOT NULL,
                    ip_detail TEXT NOT NULL,
                    attempt_count INT(11) UNSIGNED DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    last_attempt DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_ip (ip_address)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                DROP TABLE IF EXISTS `banned_ip_settings`;
                CREATE TABLE `banned_ip_settings` (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    setting_key VARCHAR(100) NOT NULL,
                    setting_value VARCHAR(255) NOT NULL,
                    description TEXT,
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_setting_key (setting_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                INSERT INTO banned_ip_settings (setting_key, setting_value, description) VALUES
                ('MAX_ATTEMPTS', '5', 'Maximum threshold for failed login attempts before the IP is temporarily blocked (403).'),
                ('TIME_WINDOW_MINUTES', '30', 'Time window (in minutes) for counting failed attempts.'),
                ('BLOCKING_ENABLED', '1', 'Blocking status (1=Active, 0=Inactive).'),
                ('JAIL_ENABLED', '1', 'Permanent jail status (1=Active, 0=Inactive).'),
                ('JAIL_ATTEMPTS_LIMIT', '15', 'Total failed attempts allowed before permanent jail (ip_jail).');
                
                DROP TABLE IF EXISTS `ip_whitelist`;
                CREATE TABLE `ip_whitelist` (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    ip_address VARCHAR(45) NOT NULL,
                    notes VARCHAR(255),
                    created_at DATETIME NOT NULL,
                    
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_whitelist_ip (ip_address)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                DROP TABLE IF EXISTS `ip_jail`;
                CREATE TABLE `ip_jail` (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    ip_address VARCHAR(45) NOT NULL,
                    reason VARCHAR(255),
                    banned_at DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_jail_ip (ip_address)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
    }
    function down(){
        DB::getInstance()->query("
            DROP TABLE IF EXISTS `ip_log`;
            DROP TABLE IF EXISTS `banned_ip_settings`;
            DROP TABLE IF EXISTS `ip_whitelist`;
            DROP TABLE IF EXISTS `ip_jail`;
        ");
    }
}