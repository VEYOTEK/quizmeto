<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

if (!isset($_GET['quiz_id']) || !is_numeric($_GET['quiz_id'])) {
    header("Location: quizzes.php");
    exit;
}

$quiz_id = (int)$_GET['quiz_id'];
$user_id = $_SESSION['user_id'];

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

// Kullanıcının skor bilgisini al
$stmt = $pdo->prepare("SELECT * FROM user_scores WHERE user_id = ? AND quiz_id = ?");
$stmt->execute([$user_id, $quiz_id]);
$user_score = $stmt->fetch();

if (!$user_score) {
    header("Location: quiz.php?id=$quiz_id");
    exit;
}

// Quizin toplam puanını hesapla
$stmt = $pdo->prepare("SELECT SUM(points) as total_points FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$total_points = $stmt->fetchColumn();

// Liderlik tablosundaki sıralamayı al
$stmt = $pdo->prepare("SELECT COUNT(*) + 1 as rank FROM user_scores 
                       WHERE quiz_id = ? AND score > ?");
$stmt->execute([$quiz_id, $user_score['score']]);
$user_rank = $stmt->fetchColumn();

// En iyi 5 skoru al
$stmt = $pdo->prepare("SELECT us.*, u.username, u.profile_image 
                       FROM user_scores us 
                       JOIN users u ON us.user_id = u.id 
                       WHERE us.quiz_id = ? 
                       ORDER BY us.score DESC, us.completion_time ASC 
                       LIMIT 5");
$stmt->execute([$quiz_id]);
$top_scores = $stmt->fetchAll();

$pageTitle = "Quiz Sonucu - " . htmlspecialchars($quiz['title']);
include 'includes/header.php';

// Skorun yüzdelik değeri
$score_percentage = ($user_score['score'] / $total_points) * 100;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Result Header -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-800 rounded-3xl p-8 md:p-12 mb-10 text-white relative overflow-hidden shadow-xl">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Quiz Sonucu</h1>
            <h2 class="text-xl md:text-2xl opacity-90"><?php echo htmlspecialchars($quiz['title']); ?></h2>
            
            <div class="flex flex-wrap gap-3 mt-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20">
                    <i class="fas fa-folder mr-1"></i> <?php echo htmlspecialchars($quiz['category_name']); ?>
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                      <?php 
                        echo $quiz['difficulty'] === 'kolay' ? 'bg-green-500/30' : 
                            ($quiz['difficulty'] === 'orta' ? 'bg-yellow-500/30' : 'bg-red-500/30'); 
                      ?>">
                    <i class="fas fa-signal mr-1"></i> <?php echo ucfirst($quiz['difficulty']); ?>
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20">
                    <i class="fas fa-clock mr-1"></i> <?php echo format_time($user_score['completion_time']); ?>
                </span>
            </div>
        </div>
        
        <div class="absolute -right-10 -bottom-10 opacity-20">
            <svg class="w-48 h-48" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Score Card -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                <div class="p-6 md:p-8">
                    <div class="flex flex-col md:flex-row md:items-center">
                        <!-- Score Circle -->
                        <div class="relative mb-6 md:mb-0 md:mr-8">
                            <div class="w-36 h-36 mx-auto md:mx-0 rounded-full flex items-center justify-center 
                                        <?php echo $score_percentage >= 70 ? 'bg-green-100 text-green-700' : 
                                                ($score_percentage >= 40 ? 'bg-yellow-100 text-yellow-700' : 
                                                'bg-red-100 text-red-700'); ?>">
                                <div class="text-center">
                                    <div class="text-5xl font-bold"><?php echo $user_score['score']; ?></div>
                                    <div class="text-base font-medium">/ <?php echo $total_points; ?></div>
                                </div>
                            </div>
                            
                            <!-- Circular Progress (SVG) -->
                            <svg class="absolute top-0 left-0 w-36 h-36 transform -rotate-90" viewBox="0 0 100 100">
                                <circle class="text-gray-200" stroke-width="8" stroke="currentColor" fill="transparent" r="44" cx="50" cy="50"/>
                                <circle class="
                                    <?php echo $score_percentage >= 70 ? 'text-green-500' : 
                                            ($score_percentage >= 40 ? 'text-yellow-500' : 
                                            'text-red-500'); ?>" 
                                    stroke-width="8" 
                                    stroke="currentColor" 
                                    fill="transparent" 
                                    r="44" 
                                    cx="50" 
                                    cy="50"
                                    stroke-dasharray="276.5"
                                    stroke-dashoffset="<?php echo 276.5 - (276.5 * $score_percentage / 100); ?>"/>
                            </svg>
                        </div>
                        
                        <!-- Score Details -->
                        <div class="flex-grow space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="text-sm text-gray-500">Sıralama</div>
                                    <div class="flex items-center">
                                        <i class="fas fa-trophy text-primary-500 mr-2"></i>
                                        <span class="text-xl font-semibold text-gray-900">#<?php echo $user_rank; ?></span>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="text-sm text-gray-500">Başarı Oranı</div>
                                    <div class="flex items-center">
                                        <i class="fas fa-percent text-primary-500 mr-2"></i>
                                        <span class="text-xl font-semibold text-gray-900"><?php echo round($score_percentage); ?>%</span>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="text-sm text-gray-500">Tamamlama Süresi</div>
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-primary-500 mr-2"></i>
                                        <span class="text-xl font-semibold text-gray-900"><?php echo format_time($user_score['completion_time']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="text-sm text-gray-500">Tamamlama Tarihi</div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-alt text-primary-500 mr-2"></i>
                                        <span class="text-xl font-semibold text-gray-900"><?php echo date('d.m.Y', strtotime($user_score['completed_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Result Message -->
                    <div class="mt-8 p-5 rounded-lg 
                                <?php echo $score_percentage >= 90 ? 'bg-green-50 border-l-4 border-green-500' : 
                                        ($score_percentage >= 70 ? 'bg-blue-50 border-l-4 border-blue-500' : 
                                        ($score_percentage >= 50 ? 'bg-yellow-50 border-l-4 border-yellow-500' : 
                                        'bg-red-50 border-l-4 border-red-500')); ?>">
                        <h3 class="text-xl font-bold 
                                <?php echo $score_percentage >= 90 ? 'text-green-800' : 
                                        ($score_percentage >= 70 ? 'text-blue-800' : 
                                        ($score_percentage >= 50 ? 'text-yellow-800' : 
                                        'text-red-800')); ?>">
                            <?php
                            if ($score_percentage >= 90) {
                                echo 'Mükemmel!';
                            } elseif ($score_percentage >= 70) {
                                echo 'Çok İyi!';
                            } elseif ($score_percentage >= 50) {
                                echo 'İyi!';
                            } elseif ($score_percentage >= 30) {
                                echo 'Ortalama';
                            } else {
                                echo 'Geliştirilmesi Gerekiyor';
                            }
                            ?>
                        </h3>
                        <p class="mt-1 text-gray-600">
                            <?php
                            if ($score_percentage >= 90) {
                                echo 'Tebrikler! Harika bir performans sergiliyorsunuz. Bilginiz gerçekten etkileyici!';
                            } elseif ($score_percentage >= 70) {
                                echo 'Harika bir iş çıkardınız. Bilginizi geliştirmeye devam edin!';
                            } elseif ($score_percentage >= 50) {
                                echo 'İyi bir performans gösterdiniz. Biraz daha pratik yaparak daha iyi olabilirsiniz.';
                            } elseif ($score_percentage >= 30) {
                                echo 'Daha fazla çalışarak bilginizi geliştirebilirsiniz.';
                            } else {
                                echo 'Endişelenmeyin, pratik yaparak gelişebilirsiniz. Tekrar deneyin!';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="border-t border-gray-100 bg-gray-50 p-4 flex flex-wrap gap-3">
                    <a href="quiz.php?id=<?php echo $quiz_id; ?>" class="flex-1 md:flex-none px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white text-center font-medium rounded-lg transition-colors">
                        <i class="fas fa-redo mr-2"></i> Quizi Tekrar Çöz
                    </a>
                    <a href="quizzes.php" class="flex-1 md:flex-none px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-center font-medium rounded-lg transition-colors">
                        <i class="fas fa-list mr-2"></i> Diğer Quizlere Bak
                    </a>
                    <a href="leaderboard.php?quiz_id=<?php echo $quiz_id; ?>" class="flex-1 md:flex-none px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-center font-medium rounded-lg transition-colors">
                        <i class="fas fa-trophy mr-2"></i> Tüm Sıralamayı Gör
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Top Scorers -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900">En İyi Skorlar</h3>
                </div>
                
                <?php if (empty($top_scores)): ?>
                    <div class="p-6 text-center">
                        <p class="text-gray-500">Henüz hiç skor kaydedilmemiş.</p>
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-100">
                        <?php foreach ($top_scores as $index => $score): ?>
                            <li class="p-4 hover:bg-gray-50 transition-colors <?php echo $score['user_id'] == $user_id ? 'bg-primary-50' : ''; ?>">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 mr-3 flex items-center justify-center 
                                                <?php echo $index === 0 ? 'bg-yellow-100 text-yellow-700' : 
                                                        ($index === 1 ? 'bg-gray-100 text-gray-700' : 
                                                        ($index === 2 ? 'bg-orange-100 text-orange-700' : 'text-gray-500')); ?> 
                                                rounded-full font-bold text-sm">
                                        #<?php echo $index + 1; ?>
                                    </div>
                                    
                                    <div class="flex-shrink-0 mr-3">
                                        <img class="h-10 w-10 rounded-full object-cover border-2 <?php echo $index === 0 ? 'border-yellow-400' : ($index === 1 ? 'border-gray-400' : ($index === 2 ? 'border-orange-400' : 'border-gray-200')); ?>" 
                                            src="<?php echo !empty($score['profile_image']) ? $score['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                            alt="<?php echo htmlspecialchars($score['username']); ?>">
                                    </div>
                                    
                                    <div class="flex-grow">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($score['username']); ?></div>
                                        <div class="flex justify-between items-center text-xs text-gray-500">
                                            <span><?php echo $score['score']; ?> puan</span>
                                            <span><?php echo format_time($score['completion_time']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($index === 0): ?>
                                        <div class="flex-shrink-0 ml-2 text-yellow-500">
                                            <i class="fas fa-crown"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <a href="leaderboard.php?quiz_id=<?php echo $quiz_id; ?>" class="w-full inline-block text-center text-primary-600 hover:text-primary-700 font-medium">
                        Tüm Sıralamayı Görüntüle <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
