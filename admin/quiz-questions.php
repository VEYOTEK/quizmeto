<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Admin yetkisi kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['quiz_id']) || !is_numeric($_GET['quiz_id'])) {
    header("Location: quizzes.php");
    exit;
}

$quiz_id = (int)$_GET['quiz_id'];
$csrf_token = generate_csrf_token();

// Quiz bilgilerini al
$stmt = $pdo->prepare("SELECT q.*, c.name as category_name FROM quizzes q 
                      LEFT JOIN categories c ON q.category_id = c.id 
                      WHERE q.id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: quizzes.php");
    exit;
}

// Soru silme işlemi
if (isset($_GET['delete_question']) && is_numeric($_GET['delete_question'])) {
    $question_id = (int)$_GET['delete_question'];
    
    // CSRF token kontrolü
    if (!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Önce cevapları sil
            $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // Soruyu sil
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
            $stmt->execute([$question_id, $quiz_id]);
            
            // Quiz'in soru sayısını güncelle
            $stmt = $pdo->prepare("UPDATE quizzes SET question_count = (SELECT COUNT(*) FROM questions WHERE quiz_id = ?) WHERE id = ?");
            $stmt->execute([$quiz_id, $quiz_id]);
            
            $pdo->commit();
            $_SESSION['admin_success'] = "Soru başarıyla silindi.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['admin_error'] = "Soru silinirken bir hata oluştu: " . $e->getMessage();
        }
    }
    
    // Yönlendirme ile sayfayı yenile
    header("Location: quiz-questions.php?quiz_id=$quiz_id");
    exit;
}

// Yeni soru ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $question_text = sanitize_input($_POST['question_text']);
        $points = (int)$_POST['points'];
        $image_url = sanitize_input($_POST['image_url'] ?? '');
        $answers = $_POST['answers'] ?? [];
        $correct_answer = (int)$_POST['correct_answer'];
        
        if (empty($question_text)) {
            $_SESSION['admin_error'] = "Soru metni gereklidir.";
        } elseif ($points <= 0) {
            $_SESSION['admin_error'] = "Puan değeri pozitif bir sayı olmalıdır.";
        } elseif (count($answers) < 2) {
            $_SESSION['admin_error'] = "En az iki cevap seçeneği eklemelisiniz.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Soruyu ekle
                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, image_url, points) VALUES (?, ?, ?, ?)");
                $stmt->execute([$quiz_id, $question_text, $image_url, $points]);
                $question_id = $pdo->lastInsertId();
                
                // Cevapları ekle
                foreach ($answers as $index => $answer_text) {
                    if (!empty($answer_text)) {
                        $is_correct = ($index == $correct_answer) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $answer_text, $is_correct]);
                    }
                }
                
                // Quiz'in soru sayısını güncelle
                $stmt = $pdo->prepare("UPDATE quizzes SET question_count = (SELECT COUNT(*) FROM questions WHERE quiz_id = ?) WHERE id = ?");
                $stmt->execute([$quiz_id, $quiz_id]);
                
                $pdo->commit();
                $_SESSION['admin_success'] = "Soru başarıyla eklendi.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['admin_error'] = "Soru eklenirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
    
    // Yönlendirme ile sayfayı yenile
    header("Location: quiz-questions.php?quiz_id=$quiz_id");
    exit;
}

// Soru düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_question'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $question_id = (int)$_POST['question_id'];
        $question_text = sanitize_input($_POST['question_text']);
        $points = (int)$_POST['points'];
        $image_url = sanitize_input($_POST['image_url'] ?? '');
        $answers = $_POST['answers'] ?? [];
        $answer_ids = $_POST['answer_ids'] ?? [];
        $correct_answer = (int)$_POST['correct_answer'];
        
        if (empty($question_text)) {
            $_SESSION['admin_error'] = "Soru metni gereklidir.";
        } elseif ($points <= 0) {
            $_SESSION['admin_error'] = "Puan değeri pozitif bir sayı olmalıdır.";
        } elseif (count($answers) < 2) {
            $_SESSION['admin_error'] = "En az iki cevap seçeneği eklemelisiniz.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Soruyu güncelle
                $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, image_url = ?, points = ? WHERE id = ? AND quiz_id = ?");
                $stmt->execute([$question_text, $image_url, $points, $question_id, $quiz_id]);
                
                // Mevcut cevapları sil
                $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
                $stmt->execute([$question_id]);
                
                // Cevapları ekle
                foreach ($answers as $index => $answer_text) {
                    if (!empty($answer_text)) {
                        $is_correct = ($index == $correct_answer) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $answer_text, $is_correct]);
                    }
                }
                
                $pdo->commit();
                $_SESSION['admin_success'] = "Soru başarıyla güncellendi.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['admin_error'] = "Soru güncellenirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
    
    // Yönlendirme ile sayfayı yenile
    header("Location: quiz-questions.php?quiz_id=$quiz_id");
    exit;
}

// Soruları getir
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Her soru için cevapları getir
foreach ($questions as &$question) {
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id");
    $stmt->execute([$question['id']]);
    $question['answers'] = $stmt->fetchAll();
}

$pageTitle = htmlspecialchars($quiz['title']) . " - Sorular Yönetimi";
include './includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="quizzes.php" class="hover:text-primary-600">Quizler</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span><?php echo htmlspecialchars($quiz['title']); ?></span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Soru Yönetimi</h1>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <a href="edit-quiz.php?id=<?php echo $quiz_id; ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-edit mr-2"></i> Quizi Düzenle
            </a>
            <a href="../quiz.php?id=<?php echo $quiz_id; ?>" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                <i class="fas fa-eye mr-2"></i> Quizi Görüntüle
            </a>
        </div>
    </div>
    
    <!-- Quiz Bilgileri Kartı -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <div class="text-sm font-medium text-gray-500 mb-1">Quiz Adı</div>
                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 mb-1">Kategori</div>
                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($quiz['category_name'] ?? 'Kategorisiz'); ?></div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 mb-1">Zorluk</div>
                <div class="font-semibold">
                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                          <?php echo $quiz['difficulty'] === 'kolay' ? 'bg-green-100 text-green-800' : 
                                  ($quiz['difficulty'] === 'orta' ? 'bg-yellow-100 text-yellow-800' : 
                                  'bg-red-100 text-red-800'); ?>">
                        <?php echo ucfirst($quiz['difficulty']); ?>
                    </span>
                </div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 mb-1">Soru Sayısı</div>
                <div class="font-semibold text-gray-900"><?php echo count($questions); ?></div>
            </div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['admin_success'])): ?>
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $_SESSION['admin_success']; ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['admin_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $_SESSION['admin_error']; ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>
    
    <!-- Sorular Listesi -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-900">Sorular (<?php echo count($questions); ?>)</h2>
            <button type="button" id="add-question-btn" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i> Yeni Soru Ekle
            </button>
        </div>
        
        <?php if (empty($questions)): ?>
            <div class="p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-question-circle text-6xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Henüz soru eklenmemiş</h3>
                <p class="text-gray-600 mb-4">Bu quize henüz soru eklenmemiş. İlk soruyu eklemek için "Yeni Soru Ekle" butonuna tıklayın.</p>
            </div>
        <?php else: ?>
            <div class="p-6 space-y-6">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="border border-gray-200 rounded-lg overflow-hidden question-item">
                        <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                            <div class="flex items-center">
                                <span class="rounded-full bg-primary-100 text-primary-800 w-8 h-8 flex items-center justify-center font-medium mr-3">
                                    <?php echo $index + 1; ?>
                                </span>
                                <h3 class="font-medium text-gray-900">Soru <?php echo $index + 1; ?></h3>
                                <?php if ($question['points'] > 0): ?>
                                    <span class="ml-3 px-2 py-1 bg-primary-100 text-primary-800 rounded-full text-xs font-medium">
                                        <?php echo $question['points']; ?> Puan
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" class="edit-question-btn p-1 text-blue-600 hover:text-blue-900" data-question-id="<?php echo $question['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="quiz-questions.php?quiz_id=<?php echo $quiz_id; ?>&delete_question=<?php echo $question['id']; ?>&token=<?php echo $csrf_token; ?>" 
                                   class="p-1 text-red-600 hover:text-red-900" 
                                   onclick="return confirm('Bu soruyu silmek istediğinize emin misiniz?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="prose max-w-none text-gray-800 mb-4">
                                <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                            </div>
                            
                            <?php if (!empty($question['image_url'])): ?>
                                <div class="mb-4">
                                    <img src="<?php echo htmlspecialchars($question['image_url']); ?>" alt="Soru Görseli" class="max-h-60 rounded-lg">
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($question['answers'] as $answer_index => $answer): ?>
                                    <div class="flex items-center p-3 rounded-lg <?php echo $answer['is_correct'] ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200'; ?>">
                                        <div class="flex-shrink-0 mr-3">
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full <?php echo $answer['is_correct'] ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800'; ?> text-xs font-medium">
                                                <?php echo chr(65 + $answer_index); ?> <!-- A, B, C, D... -->
                                            </span>
                                        </div>
                                        <div class="<?php echo $answer['is_correct'] ? 'text-green-800 font-medium' : 'text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($answer['answer_text']); ?>
                                        </div>
                                        <?php if ($answer['is_correct']): ?>
                                            <div class="ml-auto">
                                                <span class="text-green-600"><i class="fas fa-check-circle"></i></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Düzenleme Formu (başlangıçta gizli) -->
                        <div class="edit-form hidden p-4 border-t border-gray-200 bg-gray-50">
                            <form action="quiz-questions.php?quiz_id=<?php echo $quiz_id; ?>" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="edit_question" value="1">
                                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                
                                <div class="mb-4">
                                    <label for="question_text_<?php echo $question['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Soru Metni</label>
                                    <textarea id="question_text_<?php echo $question['id']; ?>" name="question_text" rows="3" required
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="image_url_<?php echo $question['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Görsel URL (İsteğe Bağlı)</label>
                                        <input type="text" id="image_url_<?php echo $question['id']; ?>" name="image_url" value="<?php echo htmlspecialchars($question['image_url']); ?>"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    
                                    <div>
                                        <label for="points_<?php echo $question['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Puan</label>
                                        <input type="number" id="points_<?php echo $question['id']; ?>" name="points" value="<?php echo $question['points']; ?>" min="1" required
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cevap Seçenekleri</label>
                                    <p class="text-xs text-gray-500 mb-3">Doğru cevabı seçmeyi unutmayın. En az 2 seçenek gereklidir.</p>
                                    
                                    <div class="space-y-3">
                                        <?php foreach ($question['answers'] as $answer_index => $answer): ?>
                                            <div class="flex items-center">
                                                <input type="radio" name="correct_answer" value="<?php echo $answer_index; ?>" <?php echo $answer['is_correct'] ? 'checked' : ''; ?>
                                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full">
                                                <input type="hidden" name="answer_ids[]" value="<?php echo $answer['id']; ?>">
                                                <input type="text" name="answers[]" value="<?php echo htmlspecialchars($answer['answer_text']); ?>" required
                                                       class="ml-3 flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Ekstra boş cevap seçeneği -->
                                        <div class="flex items-center extra-answer">
                                            <input type="radio" name="correct_answer" value="<?php echo count($question['answers']); ?>"
                                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full">
                                            <input type="text" name="answers[]" placeholder="Yeni cevap seçeneği..."
                                                   class="ml-3 flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="cancel-edit px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                                        İptal
                                    </button>
                                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                                        Güncelle
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Yeni Soru Ekleme Modalı (başlangıçta gizli) -->
    <div id="add-question-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900">Yeni Soru Ekle</h3>
                <button type="button" id="close-modal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="quiz-questions.php?quiz_id=<?php echo $quiz_id; ?>" method="post" class="p-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="add_question" value="1">
                
                <div class="mb-4">
                    <label for="question_text" class="block text-sm font-medium text-gray-700 mb-1">Soru Metni</label>
                    <textarea id="question_text" name="question_text" rows="3" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Görsel URL (İsteğe Bağlı)</label>
                        <input type="text" id="image_url" name="image_url"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="points" class="block text-sm font-medium text-gray-700 mb-1">Puan</label>
                        <input type="number" id="points" name="points" value="10" min="1" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cevap Seçenekleri</label>
                    <p class="text-xs text-gray-500 mb-3">Doğru cevabı seçmeyi unutmayın. En az 2 seçenek gereklidir.</p>
                    
                    <div id="answer-options" class="space-y-3">
                        <div class="flex items-center">
                            <input type="radio" name="correct_answer" value="0" checked
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full">
                            <input type="text" name="answers[]" placeholder="Cevap seçeneği..." required
                                   class="ml-3 flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="radio" name="correct_answer" value="1"
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full">
                            <input type="text" name="answers[]" placeholder="Cevap seçeneği..." required
                                   class="ml-3 flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="radio" name="correct_answer" value="2"
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full">
                            <input type="text" name="answers[]" placeholder="Cevap seçeneği..."
                                   class="ml-3 flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="radio" name="correct_answer" value="3"
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full">
                            <input type="text" name="answers[]" placeholder="Cevap seçeneği..."
                                   class="ml-3 flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    
                    <button type="button" id="add-answer" class="mt-3 flex items-center text-primary-600 hover:text-primary-700 text-sm font-medium">
                        <i class="fas fa-plus-circle mr-1"></i> Cevap Seçeneği Ekle
                    </button>
                </div>
                
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" id="cancel-add" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                        İptal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        Soruyu Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addQuestionBtn = document.getElementById('add-question-btn');
    const closeModalBtn = document.getElementById('close-modal');
    const cancelAddBtn = document.getElementById('cancel-add');
    const addQuestionModal = document.getElementById('add-question-modal');
    const addAnswerBtn = document.getElementById('add-answer');
    const answerOptions = document.getElementById('answer-options');
    
    // Düzenleme butonlarını seç
    const editQuestionBtns = document.querySelectorAll('.edit-question-btn');
    const cancelEditBtns = document.querySelectorAll('.cancel-edit');
    
    // Yeni soru ekleme modalını aç
    addQuestionBtn.addEventListener('click', function() {
        addQuestionModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Sayfa kaydırmayı devre dışı bırak
    });
    
    // Yeni soru ekleme modalını kapat
    closeModalBtn.addEventListener('click', closeModal);
    cancelAddBtn.addEventListener('click', closeModal);
    
    function closeModal() {
        addQuestionModal.classList.add('hidden');
        document.body.style.overflow = ''; // Sayfa kaydırmayı etkinleştir
    }
    
    // Modal dışına tıklayınca kapat
    addQuestionModal.addEventListener('click', function(e) {
        if (e.target === addQuestionModal) {
            closeModal();
        }
    });
    
    // Cevap seçeneği ekle
    addAnswerBtn.addEventListener('click', function() {
        const optionCount = answerOptions.children.length;
        const newOption = document.createElement('div');
        newOption.className = 'flex items-center';
        newOption.innerHTML = `
            <input type="radio" name="correct_answer" value="${optionCount}"
                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full">
            <input type="text" name="answers[]" placeholder="Cevap seçeneği..."
                   class="ml-3 flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <button type="button" class="ml-2 text-red-500 hover:text-red-700 remove-answer">
                <i class="fas fa-times"></i>
            </button>
        `;
        answerOptions.appendChild(newOption);
        
        // Silme düğmesine olay dinleyicisi ekle
        newOption.querySelector('.remove-answer').addEventListener('click', function() {
            answerOptions.removeChild(newOption);
            // Kalan seçeneklerin değerlerini güncelle
            updateAnswerValues();
        });
    });
    
    // Cevap seçeneği değerlerini güncelle
    function updateAnswerValues() {
        const radioButtons = answerOptions.querySelectorAll('input[type="radio"]');
        radioButtons.forEach((radio, index) => {
            radio.value = index;
        });
    }
    
    // Soru düzenleme formunu göster
    editQuestionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const questionId = this.dataset.questionId;
            const questionItem = this.closest('.question-item');
            const editForm = questionItem.querySelector('.edit-form');
            
            editForm.classList.toggle('hidden');
        });
    });
    
    // Soru düzenleme formunu gizle
    cancelEditBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const editForm = this.closest('.edit-form');
            editForm.classList.add('hidden');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
