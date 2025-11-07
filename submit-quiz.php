<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quiz_id']) || !isset($_POST['answers'])) {
    header("Location: quizzes.php");
    exit;
}

// CSRF token kontrolü
if (!verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    header("Location: quizzes.php");
    exit;
}

$quiz_id = (int)$_POST['quiz_id'];
$answers = $_POST['answers'];
$completion_time = isset($_POST['completion_time']) ? (int)$_POST['completion_time'] : 0;

// Quiz var mı kontrol et
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    $_SESSION['error'] = "Geçersiz quiz.";
    header("Location: quizzes.php");
    exit;
}

// Quiz puanını hesapla
$score = calculate_quiz_score($quiz_id, $answers);

// Kullanıcı puanını kaydet veya güncelle
try {
    $pdo->beginTransaction();
    
    // Katılımcı sayısını güncelle
    $user_has_taken_quiz = false;
    $stmt = $pdo->prepare("SELECT * FROM user_scores WHERE user_id = ? AND quiz_id = ?");
    $stmt->execute([$_SESSION['user_id'], $quiz_id]);
    if ($stmt->fetch()) {
        $user_has_taken_quiz = true;
    }
    
    if (!$user_has_taken_quiz) {
        $stmt = $pdo->prepare("UPDATE quizzes SET participants = participants + 1 WHERE id = ?");
        $stmt->execute([$quiz_id]);
    }
    
    // Kullanıcı puanını kaydet/güncelle
    $stmt = $pdo->prepare("INSERT INTO user_scores (user_id, quiz_id, score, completion_time) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE score = ?, completion_time = ?, completed_at = NOW()");
    $stmt->execute([
        $_SESSION['user_id'], 
        $quiz_id, 
        $score, 
        $completion_time, 
        $score, 
        $completion_time
    ]);
    
    $pdo->commit();
    
    // Sonuç sayfasına yönlendir
    header("Location: quiz-result.php?quiz_id=$quiz_id");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Skorunuz kaydedilirken bir hata oluştu. Lütfen tekrar deneyin.";
    header("Location: quizzes.php");
    exit;
}
?>
