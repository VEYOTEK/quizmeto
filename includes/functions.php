<?php
// Güvenlik fonksiyonları
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// CSRF token oluşturma ve doğrulama
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Kullanıcı oturum kontrolü
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: index.php");
        exit;
    }
}

// Sayfalama
function paginate($total, $per_page, $current_page) {
    $total_pages = ceil($total / $per_page);
    
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    $pagination = [];
    
    if ($current_page > 1) {
        $pagination[] = ['page' => $current_page - 1, 'text' => '&laquo; Önceki', 'active' => false];
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination[] = ['page' => $i, 'text' => $i, 'active' => ($i == $current_page)];
    }
    
    if ($current_page < $total_pages) {
        $pagination[] = ['page' => $current_page + 1, 'text' => 'Sonraki &raquo;', 'active' => false];
    }
    
    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'links' => $pagination
    ];
}

// Quiz puanını hesapla
function calculate_quiz_score($quiz_id, $answers) {
    global $pdo;
    
    $score = 0;
    foreach ($answers as $question_id => $answer_id) {
        $stmt = $pdo->prepare("SELECT a.is_correct, q.points 
                              FROM answers a 
                              JOIN questions q ON a.question_id = q.id 
                              WHERE a.id = ? AND a.question_id = ? AND q.quiz_id = ?");
        $stmt->execute([$answer_id, $question_id, $quiz_id]);
        $result = $stmt->fetch();
        
        if ($result && $result['is_correct']) {
            $score += $result['points'];
        }
    }
    
    return $score;
}

// Zaman formatlama
function format_time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . " saniye önce";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " dakika önce";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " saat önce";
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . " gün önce";
    } elseif ($diff < 2592000) {
        return floor($diff / 604800) . " hafta önce";
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . " ay önce";
    } else {
        return floor($diff / 31536000) . " yıl önce";
    }
}

// Saniyeyi okunabilir formata dönüştür
function format_time($seconds) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    
    return sprintf("%02d:%02d", $minutes, $seconds);
}

/**
 * Sistem ayarlarını veritabanından getirir
 *
 * @return array Site ayarları
 */
function get_site_settings() {
    global $pdo;
    
    $settings = [];
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        // Hata durumunda varsayılan ayarlar kullanılacak
        error_log("Ayarlar alınırken hata: " . $e->getMessage());
    }
    
    return $settings;
}
?>
