<?php
/**
 * Plugin Name: IP Banned Management
 * Plugin URI: https://github.com/heroesoebekti/IP-Banned-Management
 * Description: A basic security system to prevent Brute-Force Attacks. 
 * Version: 1.0.0
 * Author: Heru Subekti
 * Author URI: https://github.com/heroesoebekti
 */

use SLiMS\DB;
use SLiMS\Plugins;

// Get the SLiMS database instance
$db = DB::getInstance();
$plugin = Plugins::getInstance();

function getUserIpAddr() {
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function getPluginSettings($db) {
    $settings = [];
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM banned_ip_settings");
        if ($stmt) {
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (\Exception $e) {
    }
    
    return array_merge([
        'MAX_ATTEMPTS' => 5,
        'TIME_WINDOW_MINUTES' => 30,
        'BLOCKING_ENABLED' => 1,
        'JAIL_ENABLED' => 1,
        'JAIL_ATTEMPTS_LIMIT' => 15
    ], $settings);
}


Plugins::hook(Plugins::CONTENT_BEFORE_LOAD, function() use ($db) {
    
    $user_ip = getUserIpAddr();
    $settings = getPluginSettings($db);
    $log_data = null;
    $last_time = 0;

    if (empty($settings['BLOCKING_ENABLED']) || (int)$settings['BLOCKING_ENABLED'] === 0) {
        return;
    }

    try {
        $stmt_whitelist = $db->prepare("SELECT 1 FROM ip_whitelist WHERE ip_address = :ip_address LIMIT 1");
        $stmt_whitelist->execute(['ip_address' => $user_ip]);
        if ($stmt_whitelist->fetchColumn()) {
            return;
        }
    } catch (\Exception $e) {}

    if ((int)$settings['JAIL_ENABLED'] === 1) {
        try {
            $stmt_jail = $db->prepare("SELECT reason FROM ip_jail WHERE ip_address = :ip_address LIMIT 1");
            $stmt_jail->execute(['ip_address' => $user_ip]);
            $jail_reason = $stmt_jail->fetchColumn();
            
            if ($jail_reason) {
                header('HTTP/1.0 403 Forbidden');
                echo '<h1>403 Forbidden</h1>';
                echo '<p>Your access has been permanently denied. Reason: ' . htmlspecialchars($jail_reason) . '</p>';
                exit(); 
            }
        } catch (\Exception $e) {}
    }

    $max_attempts = (int)$settings['MAX_ATTEMPTS'];
    $time_window = (int)$settings['TIME_WINDOW_MINUTES'];
    $time_limit = strtotime('-' . $time_window . ' minutes');

    try {
        $stmt_fetch = $db->prepare("SELECT attempt_count, last_attempt FROM ip_log WHERE ip_address = :ip_address");
        $stmt_fetch->execute(['ip_address' => $user_ip]);
        $log_data = $stmt_fetch->fetch(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {}

    $is_blocked = false;
    $total_cumulative_attempts = 0;

    if ($log_data) {
        $count = (int)$log_data['attempt_count'];
        $last_time = strtotime($log_data['last_attempt']);
        $total_cumulative_attempts = $count;

        if ($last_time < $time_limit) {
            $count = 0; 
        }
        
        if ($count >= $max_attempts) {
            $is_blocked = true;
        }
    }
    
    if ($is_blocked) {
        header('HTTP/1.0 403 Forbidden');
        echo '<h1>403 Forbidden</h1>';
        echo '<p>Your access has been temporarily blocked due to too many failed login attempts. Please try again after ' . $time_window . ' minutes.</p>';
        exit();
    }
    
    $is_public_failed = (
        isset($_GET['p']) && $_GET['p'] === 'login' &&
        isset($_GET['wrongpass']) && $_GET['wrongpass'] === 'true'
    );
    
    $is_admin_failed = (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        strpos($_SERVER['REQUEST_URI'], '/admin/') !== false &&
        isset($_POST['username']) && isset($_POST['password']) &&
        !isset($_SESSION['uid']) 
    );

    if($is_public_failed || $is_admin_failed){
        
        $current_time = date('Y-m-d H:i:s');
        $request_url = $_SERVER['REQUEST_URI'];
        
        if ((int)$settings['JAIL_ENABLED'] === 1) {
            $jail_limit = (int)$settings['JAIL_ATTEMPTS_LIMIT'];
            if ($total_cumulative_attempts + 1 >= $jail_limit) {
                try {
                    $stmt_jail_insert = $db->prepare("
                        INSERT INTO ip_jail (ip_address, reason, banned_at)
                        VALUES (:ip_address, :reason, :banned_at)
                        ON DUPLICATE KEY UPDATE banned_at = VALUES(banned_at)
                    ");
                    $stmt_jail_insert->execute([
                        'ip_address' => $user_ip, 
                        'reason' => 'Exceeded ' . $jail_limit . ' failed attempts limit.',
                        'banned_at' => $current_time
                    ]);
                    header('HTTP/1.0 403 Forbidden');
                    echo '<h1>403 Forbidden</h1>';
                    echo '<p>Your access has been permanently denied due to repeated brute-force attempts.</p>';
                    exit(); 
                } catch (\Exception $e) {}
            }
        }
        
        $new_count = ($log_data && $last_time >= $time_limit) ? (int)$log_data['attempt_count'] + 1 : 1; 

        $stmt_upsert = $db->prepare("
            INSERT INTO ip_log (ip_address, request_url, created_at, last_attempt, attempt_count) 
            VALUES (:ip_address, :request_url, :created_at, :last_attempt, :attempt_count)
            ON DUPLICATE KEY UPDATE 
                request_url = VALUES(request_url), 
                last_attempt = VALUES(last_attempt), 
                attempt_count = attempt_count + 1
        ");
        
        $params = [
            'ip_address' => $user_ip, 
            'request_url' => $request_url, 
            'created_at' => $current_time, 
            'last_attempt' => $current_time,
            'attempt_count' => $new_count
        ];

        try {
            $stmt_upsert->execute($params);
        } catch (\Exception $e) {}
    }
});

$plugin->registerMenu('system', __('Banned IP Settings'), __DIR__ . '/settings.php');
$plugin->registerMenu('system', __('Login Attempt Log'), __DIR__ . '/login_attempt.php');
$plugin->registerMenu('system', __('IP Banned'), __DIR__ . '/ip_jail.php');
$plugin->registerMenu('system', __('IP Whitelist'), __DIR__ . '/ip_whitelist.php');