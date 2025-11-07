<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: quizzes.php");
    exit;
}

$quiz_id = (int)$_GET['id'];

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

// Kullanıcı bu quizi daha önce çözmüş mü kontrol et
$stmt = $pdo->prepare("SELECT * FROM user_scores WHERE user_id = ? AND quiz_id = ?");
$stmt->execute([$_SESSION['user_id'], $quiz_id]);
$previous_attempt = $stmt->fetch();

// Soruları al - Rastgele sıralama ve maksimum 10 soru
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY RAND() LIMIT 10");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Her soru için cevapları al
foreach ($questions as &$question) {
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC");
    $stmt->execute([$question['id']]);
    $question['answers'] = $stmt->fetchAll();
}

// Quiz'in soru sayısını güncelle
if (count($questions) != $quiz['question_count']) {
    $update_stmt = $pdo->prepare("UPDATE quizzes SET question_count = ? WHERE id = ?");
    $update_stmt->execute([count($questions), $quiz_id]);
    $quiz['question_count'] = count($questions);
}

$pageTitle = htmlspecialchars($quiz['title']) . " - QuizMeto";
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Quiz Başlangıç Ekranı -->
    <div id="quiz-start-screen" class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Quiz Başlık ve Açıklama -->
        <div class="bg-gradient-to-r from-primary-600 to-primary-800 text-white p-8">
            <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <div class="flex flex-wrap gap-3 mt-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20">
                    <i class="fas fa-folder mr-1"></i> <?php echo htmlspecialchars($quiz['category_name']); ?>
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20">
                    <i class="fas fa-question-circle mr-1"></i> <?php echo count($questions); ?> Soru
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                      <?php 
                        echo $quiz['difficulty'] === 'kolay' ? 'bg-green-500/30' : 
                            ($quiz['difficulty'] === 'orta' ? 'bg-yellow-500/30' : 'bg-red-500/30'); 
                      ?>">
                    <i class="fas fa-signal mr-1"></i> <?php echo ucfirst($quiz['difficulty']); ?>
                </span>
                <?php if ($quiz['time_limit'] > 0): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20">
                        <i class="fas fa-clock mr-1"></i> <?php echo format_time($quiz['time_limit']); ?>
                    </span>
                <?php endif; ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20">
                    <i class="fas fa-users mr-1"></i> <?php echo $quiz['participants']; ?> Katılımcı
                </span>
            </div>
        </div>
        
        <div class="p-8">
            <?php if ($previous_attempt): ?>
                <!-- Önceki Sonuç Bilgisi -->
                <div class="mb-8 bg-gray-50 border-l-4 border-primary-500 p-5 rounded-lg">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Önceki Sonucunuz</h3>
                    <div class="flex items-center">
                        <div class="w-16 h-16 flex items-center justify-center rounded-full bg-primary-100 text-primary-700 font-bold text-xl">
                            <?php echo $previous_attempt['score']; ?>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600"><i class="fas fa-clock mr-1"></i> Tamamlama Süresi: <?php echo format_time($previous_attempt['completion_time']); ?></p>
                            <p class="text-gray-600"><i class="fas fa-calendar mr-1"></i> Tamamlama Tarihi: <?php echo date('d.m.Y H:i', strtotime($previous_attempt['completed_at'])); ?></p>
                            <p class="text-sm text-gray-500 mt-1">Bu quizi tekrar çözmek size yeni bir puan kazandırır ve önceki skorunuzun üzerine yazılır.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Quiz Açıklaması -->
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-3">Quiz Açıklaması</h3>
                <div class="prose max-w-none text-gray-600">
                    <?php echo nl2br(htmlspecialchars($quiz['description'])); ?>
                </div>
            </div>
            
            <!-- Quiz Kuralları -->
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-3">Quiz Kuralları</h3>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>Her soru için bir cevap seçmelisiniz.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>Geri dönüp cevaplarınızı değiştirebilirsiniz.</span>
                    </li>
                    <?php if ($quiz['time_limit'] > 0): ?>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Quizi tamamlamak için <?php echo format_time($quiz['time_limit']); ?> süreniz var.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Süre bitiminde, o ana kadar verdiğiniz cevaplar otomatik olarak gönderilir.</span>
                        </li>
                    <?php endif; ?>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>Quizi tamamladıktan sonra sonuçlarınızı görebilirsiniz.</span>
                    </li>
                </ul>
            </div>
            
            <button id="start-quiz" class="w-full py-3 px-6 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fas fa-play mr-2"></i> Quize Başla
            </button>
        </div>
    </div>
    
    <!-- Quiz İçerik -->
    <div id="quiz-content" class="bg-white rounded-xl shadow-lg overflow-hidden" style="display: none;">
        <!-- Quiz İlerleme Çubuğu -->
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <div class="flex justify-between items-center mb-2">
                <div id="question-counter" class="text-sm font-medium text-gray-700">Soru 1/<?php echo count($questions); ?></div>
                <?php if ($quiz['time_limit'] > 0): ?>
                    <div id="time-counter" class="text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-clock mr-2 text-primary-500"></i>
                        <span><?php echo format_time($quiz['time_limit']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div id="progress-fill" class="bg-primary-600 h-2.5 rounded-full transition-all duration-300" style="width: <?php echo (1 / count($questions)) * 100; ?>%"></div>
            </div>
        </div>
        
        <!-- Quiz Form -->
        <form id="quiz-form" action="submit-quiz.php" method="post" class="pb-6">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
            <?php if ($quiz['time_limit'] > 0): ?>
                <input type="hidden" id="completion-time" name="completion_time" value="0">
            <?php endif; ?>
            
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card" id="question-<?php echo $index + 1; ?>" <?php echo $index > 0 ? 'style="display: none;"' : ''; ?>>
                    <!-- Soru Başlık -->
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-bold text-gray-900">Soru <?php echo $index + 1; ?></h3>
                            <div class="px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-sm font-medium">
                                <?php echo $question['points']; ?> Puan
                            </div>
                        </div>
                    </div>
                    
                    <!-- Soru İçerik -->
                    <div class="px-6 py-5">
                        <!-- Soru Metni -->
                        <div class="prose max-w-none text-gray-800 mb-6">
                            <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                        </div>
                        
                        <!-- Soru Görseli (Varsa) -->
                        <?php if (!empty($question['image_url'])): ?>
                            <div class="mb-6">
                                <img src="<?php echo htmlspecialchars($question['image_url']); ?>" alt="Soru Görseli" 
                                    class="rounded-lg max-h-80 mx-auto shadow-md">
                            </div>
                        <?php endif; ?>
                        
                        <!-- Cevap Seçenekleri -->
                        <div class="space-y-3">
                            <?php foreach ($question['answers'] as $answer): ?>
                                <div class="answer-option relative">
                                    <input type="radio" 
                                           id="answer-<?php echo $answer['id']; ?>" 
                                           name="answers[<?php echo $question['id']; ?>]" 
                                           value="<?php echo $answer['id']; ?>"
                                           class="peer hidden">
                                    <label for="answer-<?php echo $answer['id']; ?>" 
                                           class="flex items-center p-4 rounded-lg border border-gray-200 cursor-pointer transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 hover:bg-gray-50">
                                        <div class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center mr-3 peer-checked:border-primary-500 peer-checked:bg-primary-600">
                                            <div class="peer-checked:block hidden w-3 h-3 bg-white rounded-full"></div>
                                        </div>
                                        <span class="text-gray-700 peer-checked:text-primary-700 peer-checked:font-medium">
                                            <?php echo htmlspecialchars($answer['answer_text']); ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Soru Navigasyon -->
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-between">
                        <?php if ($index > 0): ?>
                            <button type="button" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors prev-question" data-question="<?php echo $index; ?>">
                                <i class="fas fa-chevron-left mr-1"></i> Önceki Soru
                            </button>
                        <?php else: ?>
                            <div></div> <!-- Boş spacer -->
                        <?php endif; ?>
                        
                        <?php if ($index < count($questions) - 1): ?>
                            <button type="button" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors next-question" data-question="<?php echo $index + 2; ?>">
                                Sonraki Soru <i class="fas fa-chevron-right ml-1"></i>
                            </button>
                        <?php else: ?>
                            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                <i class="fas fa-check mr-1"></i> Quizi Bitir
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
        
        <!-- Sorular Arası Navigasyon Noktaları -->
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex justify-center">
            <div class="flex space-x-2 overflow-x-auto py-2 px-4">
                <?php for ($i = 0; $i < count($questions); $i++): ?>
                    <button class="question-dot w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-xs font-medium <?php echo $i === 0 ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 hover:bg-gray-100'; ?> transition-colors" data-question="<?php echo $i + 1; ?>">
                        <?php echo $i + 1; ?>
                    </button>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startQuizBtn = document.getElementById('start-quiz');
    const quizStartScreen = document.getElementById('quiz-start-screen');
    const quizContent = document.getElementById('quiz-content');
    const quizForm = document.getElementById('quiz-form');
    const questionCards = document.querySelectorAll('.question-card');
    const questionDots = document.querySelectorAll('.question-dot');
    const questionCounter = document.getElementById('question-counter');
    const progressFill = document.getElementById('progress-fill');
    const prevButtons = document.querySelectorAll('.prev-question');
    const nextButtons = document.querySelectorAll('.next-question');
    
    // Soru noktalarının durumunu takip et
    let questionStatus = Array(questionCards.length).fill(false);
    
    // Cevap seçildiğinde
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const questionId = this.name.match(/\d+/)[0];
            const questionIndex = Array.from(questionCards).findIndex(card => 
                card.querySelector(`input[name="answers[${questionId}]"]`)
            );
            
            // Soru noktasını işaretle ve cevaplanmış olarak kaydet
            questionStatus[questionIndex] = true;
            updateQuestionDots();
        });
    });
    
    // Quiz başlatma
    startQuizBtn.addEventListener('click', function() {
        quizStartScreen.style.display = 'none';
        quizContent.style.display = 'block';
        
        <?php if ($quiz['time_limit'] > 0): ?>
        // Geri sayım başlat
        const timeCounter = document.getElementById('time-counter').querySelector('span');
        const completionTime = document.getElementById('completion-time');
        let timeLeft = <?php echo $quiz['time_limit']; ?>;
        let elapsedTime = 0;
        
        const timer = setInterval(function() {
            timeLeft--;
            elapsedTime++;
            completionTime.value = elapsedTime;
            
            // Kalan süreyi formatlayıp göster
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timeCounter.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Tehlikeli süre uyarısı
            if (timeLeft <= 30) {
                timeCounter.parentElement.classList.add('text-red-600', 'animate-pulse');
            }
            
            // Süre bittiğinde formu otomatik gönder
            if (timeLeft <= 0) {
                clearInterval(timer);
                quizForm.submit();
            }
        }, 1000);
        <?php endif; ?>
    });
    
    // Soru navigasyonu
    function showQuestion(questionIndex) {
        questionCards.forEach(card => card.style.display = 'none');
        document.getElementById(`question-${questionIndex}`).style.display = 'block';
        
        // Soru sayacını güncelle
        questionCounter.textContent = `Soru ${questionIndex}/${questionCards.length}`;
        
        // İlerleme çubuğunu güncelle
        progressFill.style.width = `${(questionIndex / questionCards.length) * 100}%`;
        
        // Aktif nokta göstergesini güncelle
        questionDots.forEach((dot, index) => {
            dot.classList.remove('bg-primary-600', 'text-white', 'border-primary-600');
            if (index + 1 === questionIndex) {
                dot.classList.add('bg-primary-600', 'text-white', 'border-primary-600');
            }
        });
    }
    
    // Soru noktası durumunu güncelle
    function updateQuestionDots() {
        questionStatus.forEach((status, index) => {
            const dot = questionDots[index];
            if (status) {
                // Cevaplanmış soru
                dot.classList.add('bg-green-100', 'text-green-800', 'border-green-500');
                // Aktif soru ise bu sınıfları öncelikli kullan
                if (document.getElementById(`question-${index + 1}`).style.display !== 'none') {
                    dot.classList.add('bg-primary-600', 'text-white', 'border-primary-600');
                }
            }
        });
    }
    
    // Önceki/sonraki düğmeleri
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionIndex = parseInt(this.dataset.question);
            showQuestion(questionIndex);
        });
    });
    
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionIndex = parseInt(this.dataset.question);
            showQuestion(questionIndex);
        });
    });
    
    // Nokta navigasyonu
    questionDots.forEach(dot => {
        dot.addEventListener('click', function() {
            const questionIndex = parseInt(this.dataset.question);
            showQuestion(questionIndex);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
