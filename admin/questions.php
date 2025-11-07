<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
require_admin();

if (!isset($_GET['quiz_id']) || !is_numeric($_GET['quiz_id'])) {
    header("Location: quizzes.php");
    exit;
}

$quiz_id = (int)$_GET['quiz_id'];

// Quiz var mı kontrol et
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: quizzes.php");
    exit;
}

// Soru ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $question_text = sanitize_input($_POST['question_text']);
        $points = (int)$_POST['points'];
        
        // Soru görselini işle
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/questions/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = 'uploads/questions/' . $file_name;
            }
        }
        
        // Soruyu veritabanına ekle
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, image_url, points) VALUES (?, ?, ?, ?)");
            $stmt->execute([$quiz_id, $question_text, $image_url, $points]);
            $question_id = $pdo->lastInsertId();
            
            // Cevapları ekle
            foreach ($_POST['answers'] as $index => $answer_text) {
                if (!empty($answer_text)) {
                    $is_correct = isset($_POST['correct_answer']) && $_POST['correct_answer'] == $index;
                    
                    $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                    $stmt->execute([$question_id, $answer_text, $is_correct]);
                }
            }
            
            // Quiz'in soru sayısını güncelle
            $stmt = $pdo->prepare("UPDATE quizzes SET question_count = (SELECT COUNT(*) FROM questions WHERE quiz_id = ?) WHERE id = ?");
            $stmt->execute([$quiz_id, $quiz_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Soru başarıyla eklendi.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Soru eklenirken bir hata oluştu: " . $e->getMessage();
        }
    }
    
    header("Location: questions.php?quiz_id=$quiz_id");
    exit;
}

// Soru silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $question_id = (int)$_POST['question_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Cevapları sil
            $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // Soruyu sil
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
            $stmt->execute([$question_id, $quiz_id]);
            
            // Quiz'in soru sayısını güncelle
            $stmt = $pdo->prepare("UPDATE quizzes SET question_count = (SELECT COUNT(*) FROM questions WHERE quiz_id = ?) WHERE id = ?");
            $stmt->execute([$quiz_id, $quiz_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Soru başarıyla silindi.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Soru silinirken bir hata oluştu: " . $e->getMessage();
        }
    }
    
    header("Location: questions.php?quiz_id=$quiz_id");
    exit;
}

// Soruları al
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Her soru için cevapları al
foreach ($questions as &$question) {
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id");
    $stmt->execute([$question['id']]);
    $question['answers'] = $stmt->fetchAll();
}

$pageTitle = "Sorular - " . htmlspecialchars($quiz['title']) . " - Admin Paneli";
include 'includes/header.php';
?>

<div class="admin-container">
    <div class="admin-content">
        <div class="page-header">
            <div>
                <h1><?php echo htmlspecialchars($quiz['title']); ?> - Sorular</h1>
                <p>Quiz için soru ve cevapları yönetin</p>
            </div>
            <div class="page-actions">
                <a href="quizzes.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Quizlere Dön
                </a>
                <a href="quizzes-edit.php?id=<?php echo $quiz_id; ?>" class="btn btn-outline">
                    <i class="fas fa-edit"></i> Quizi Düzenle
                </a>
                <button class="btn btn-primary" id="add-question-btn">
                    <i class="fas fa-plus"></i> Yeni Soru Ekle
                </button>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="card-header">
                <h2>Quiz Bilgileri</h2>
            </div>
            <div class="card-content">
                <div class="quiz-info">
                    <div class="info-group">
                        <span class="info-label">Kategori:</span>
                        <span class="info-value"><?php echo htmlspecialchars($quiz['category_id']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Zorluk:</span>
                        <span class="info-value difficulty <?php echo $quiz['difficulty']; ?>"><?php echo ucfirst($quiz['difficulty']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Süre Limiti:</span>
                        <span class="info-value"><?php echo $quiz['time_limit'] > 0 ? format_time($quiz['time_limit']) : 'Limitsiz'; ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Soru Sayısı:</span>
                        <span class="info-value"><?php echo count($questions); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Katılımcı Sayısı:</span>
                        <span class="info-value"><?php echo $quiz['participants']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Soru Ekleme Formu Modal -->
        <div id="add-question-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Yeni Soru Ekle</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="questions.php?quiz_id=<?php echo $quiz_id; ?>" method="post" enctype="multipart/form-data" id="add-question-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="add_question">
                        
                        <div class="form-group">
                            <label for="question_text">Soru Metni</label>
                            <textarea id="question_text" name="question_text" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Soru Görseli (İsteğe Bağlı)</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <div class="image-preview" id="image-preview"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="points">Puan Değeri</label>
                            <input type="number" id="points" name="points" min="1" value="10" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Cevap Seçenekleri</label>
                            <p class="help-text">Doğru cevabı işaretlemeyi unutmayın.</p>
                            
                            <div id="answers-container">
                                <div class="answer-item">
                                    <input type="radio" name="correct_answer" value="0" id="correct_0" checked>
                                    <input type="text" name="answers[]" placeholder="Cevap metni" required>
                                </div>
                                <div class="answer-item">
                                    <input type="radio" name="correct_answer" value="1" id="correct_1">
                                    <input type="text" name="answers[]" placeholder="Cevap metni" required>
                                </div>
                                <div class="answer-item">
                                    <input type="radio" name="correct_answer" value="2" id="correct_2">
                                    <input type="text" name="answers[]" placeholder="Cevap metni" required>
                                </div>
                                <div class="answer-item">
                                    <input type="radio" name="correct_answer" value="3" id="correct_3">
                                    <input type="text" name="answers[]" placeholder="Cevap metni" required>
                                </div>
                            </div>
                            
                            <button type="button" id="add-answer-btn" class="btn btn-text">
                                <i class="fas fa-plus"></i> Seçenek Ekle
                            </button>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-outline close-modal">İptal</button>
                            <button type="submit" class="btn btn-primary">Soruyu Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sorular Listesi -->
        <div class="admin-card questions-list">
            <div class="card-header">
                <h2>Sorular (<?php echo count($questions); ?>)</h2>
            </div>
            <div class="card-content">
                <?php if (empty($questions)): ?>
                    <div class="empty-state">
                        <img src="../assets/images/no-questions.svg" alt="Soru yok">
                        <h3>Henüz soru eklenmemiş</h3>
                        <p>Bu quize henüz soru eklenmemiş. Yukarıdaki "Yeni Soru Ekle" butonuna tıklayarak soru ekleyebilirsiniz.</p>
                        <button class="btn btn-primary" id="add-question-empty">
                            <i class="fas fa-plus"></i> Yeni Soru Ekle
                        </button>
                    </div>
                <?php else: ?>
                    <div class="accordion">
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <div class="question-info">
                                        <span class="question-number"><?php echo $index + 1; ?></span>
                                        <span class="question-text"><?php echo htmlspecialchars(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : ''); ?></span>
                                    </div>
                                    <div class="question-actions">
                                        <span class="badge"><?php echo $question['points']; ?> Puan</span>
                                        <button class="btn btn-sm btn-outline accordion-toggle">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="accordion-content">
                                    <div class="question-details">
                                        <div class="question-full-text">
                                            <h3>Soru Metni:</h3>
                                            <p><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                                        </div>
                                        
                                        <?php if (!empty($question['image_url'])): ?>
                                            <div class="question-image">
                                                <h3>Soru Görseli:</h3>
                                                <img src="../<?php echo htmlspecialchars($question['image_url']); ?>" alt="Soru Görseli">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="question-answers">
                                            <h3>Cevap Seçenekleri:</h3>
                                            <ul>
                                                <?php foreach ($question['answers'] as $answer): ?>
                                                    <li class="<?php echo $answer['is_correct'] ? 'correct-answer' : ''; ?>">
                                                        <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                        <?php if ($answer['is_correct']): ?>
                                                            <span class="correct-badge"><i class="fas fa-check"></i> Doğru Cevap</span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        
                                        <div class="question-actions-footer">
                                            <a href="questions-edit.php?id=<?php echo $question['id']; ?>&quiz_id=<?php echo $quiz_id; ?>" class="btn btn-sm btn-outline">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                            <form method="post" class="delete-form" onsubmit="return confirm('Bu soruyu silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="action" value="delete_question">
                                                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Accordion işlevselliği
    const accordionToggles = document.querySelectorAll('.accordion-toggle');
    accordionToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const accordionItem = this.closest('.accordion-item');
            accordionItem.classList.toggle('active');
            
            const icon = this.querySelector('i');
            if (accordionItem.classList.contains('active')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });
    });
    
    // Modal işlevselliği
    const modal = document.getElementById('add-question-modal');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const addQuestionEmptyBtn = document.getElementById('add-question-empty');
    const closeButtons = document.querySelectorAll('.close-modal');
    
    function openModal() {
        modal.style.display = 'block';
    }
    
    function closeModal() {
        modal.style.display = 'none';
    }
    
    addQuestionBtn.addEventListener('click', openModal);
    if (addQuestionEmptyBtn) {
        addQuestionEmptyBtn.addEventListener('click', openModal);
    }
    
    closeButtons.forEach(button => {
        button.addEventListener('click', closeModal);
    });
    
    // Modal dışına tıklayınca kapatma
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Yeni cevap seçeneği ekle
    const addAnswerBtn = document.getElementById('add-answer-btn');
    const answersContainer = document.getElementById('answers-container');
    let answerCount = 4;
    
    addAnswerBtn.addEventListener('click', function() {
        const newAnswer = document.createElement('div');
        newAnswer.className = 'answer-item';
        newAnswer.innerHTML = `
            <input type="radio" name="correct_answer" value="${answerCount}" id="correct_${answerCount}">
            <input type="text" name="answers[]" placeholder="Cevap metni" required>
            <button type="button" class="btn btn-sm btn-danger remove-answer">
                <i class="fas fa-times"></i>
            </button>
        `;
        answersContainer.appendChild(newAnswer);
        
        // Silme butonu işlevselliği ekle
        const removeBtn = newAnswer.querySelector('.remove-answer');
        removeBtn.addEventListener('click', function() {
            answersContainer.removeChild(newAnswer);
        });
        
        answerCount++;
    });
    
    // Görsel önizleme
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    
    imageInput.addEventListener('change', function() {
        imagePreview.innerHTML = '';
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                imagePreview.appendChild(img);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
