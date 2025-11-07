<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$active_tab = isset($_GET['tab']) ? sanitize_input($_GET['tab']) : 'participated';

// Kullanıcının çözdüğü quizleri al
$participated_stmt = $pdo->prepare("
    SELECT us.*, q.title, q.difficulty, q.question_count, q.time_limit, c.name as category_name
    FROM user_scores us
    JOIN quizzes q ON us.quiz_id = q.id
    LEFT JOIN categories c ON q.category_id = c.id
    WHERE us.user_id = ?
    ORDER BY us.completed_at DESC
");
$participated_stmt->execute([$user_id]);
$participated_quizzes = $participated_stmt->fetchAll();

// Kullanıcının oluşturduğu quizleri al (eğer admin/eğitmen ise)
$created_quizzes = [];
if ($_SESSION['role'] === 'admin') {
    $created_stmt = $pdo->prepare("
        SELECT q.*, c.name as category_name, 
               (SELECT COUNT(*) FROM user_scores WHERE quiz_id = q.id) as attempt_count
        FROM quizzes q
        LEFT JOIN categories c ON q.category_id = c.id
        WHERE q.created_by = ?
        ORDER BY q.created_at DESC
    ");
    $created_stmt->execute([$user_id]);
    $created_quizzes = $created_stmt->fetchAll();
}

$pageTitle = "Quizlerim - QuizMeto";
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-3xl p-8 md:p-12 mb-10 text-white relative overflow-hidden shadow-xl">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Quizlerim</h1>
            <p class="text-xl opacity-90 max-w-2xl">Çözdüğünüz ve oluşturduğunuz tüm quizleri görüntüleyin</p>
        </div>
        
        <div class="absolute -right-10 -bottom-10 opacity-20">
            <svg class="w-48 h-48" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M3 5H9V11H3V5ZM5 7V9H7V7H5ZM11 7H21V9H11V7ZM11 15H21V17H11V15ZM5 20L8 17H2L5 20ZM3 13H9V19H3V13ZM5 15V17H7V15H5Z"/>
            </svg>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="mb-8 border-b border-gray-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <a href="?tab=participated" 
               class="<?php echo $active_tab === 'participated' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Çözdüğüm Quizler
                <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"><?php echo count($participated_quizzes); ?></span>
            </a>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="?tab=created" 
                   class="<?php echo $active_tab === 'created' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Oluşturduğum Quizler
                    <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"><?php echo count($created_quizzes); ?></span>
                </a>
            <?php endif; ?>
        </nav>
    </div>
    
    <!-- Çözdüğüm Quizler -->
    <?php if ($active_tab === 'participated'): ?>
        <?php if (empty($participated_quizzes)): ?>
            <div class="bg-white rounded-xl p-10 shadow-lg text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-clipboard-list text-6xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Henüz hiç quiz çözmediniz</h3>
                <p class="text-gray-600 mb-6">Quiz çözdükçe burada listelenecek ve sonuçlarınızı görebileceksiniz.</p>
                <a href="quizzes.php" class="inline-flex items-center justify-center px-5 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors">
                    <i class="fas fa-play mr-2"></i> Quizlere Başla
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($participated_quizzes as $quiz): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-semibold px-3 py-1 bg-primary-100 text-primary-800 rounded-full">
                                    <?php echo htmlspecialchars($quiz['category_name'] ?? 'Kategorisiz'); ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                      <?php echo $quiz['difficulty'] === 'kolay' ? 'bg-green-100 text-green-800' : 
                                              ($quiz['difficulty'] === 'orta' ? 'bg-yellow-100 text-yellow-800' :
                                              'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($quiz['difficulty']); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold mb-2 text-gray-900 line-clamp-1"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            
                            <div class="mt-4 bg-gray-50 p-3 rounded-lg">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Sonucunuz</h4>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-primary-100 text-primary-700 font-bold text-sm">
                                            <?php echo $quiz['score']; ?>
                                        </div>
                                        <div class="ml-2">
                                            <div class="text-xs text-gray-500">Puan</div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end">
                                        <div class="text-sm text-gray-900"><?php echo format_time($quiz['completion_time']); ?></div>
                                        <div class="text-xs text-gray-500">Tamamlama süresi</div>
                                    </div>
                                </div>
                                
                                <div class="text-xs text-gray-500 mt-2">
                                    <?php echo date('d.m.Y H:i', strtotime($quiz['completed_at'])); ?> tarihinde tamamlandı
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500 mt-4 mb-4">
                                <span class="inline-flex items-center">
                                    <i class="fas fa-question-circle mr-1"></i> <?php echo $quiz['question_count']; ?> Soru
                                </span>
                                <?php if ($quiz['time_limit'] > 0): ?>
                                    <span class="inline-flex items-center">
                                        <i class="fas fa-clock mr-1"></i> <?php echo format_time($quiz['time_limit']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="flex-1 text-center px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm">
                                    <i class="fas fa-redo mr-1"></i> Tekrar Çöz
                                </a>
                                <a href="quiz-result.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="flex-1 text-center px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg transition-colors text-sm">
                                    <i class="fas fa-chart-bar mr-1"></i> Sonuç
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Oluşturduğum Quizler -->
    <?php if ($active_tab === 'created' && $_SESSION['role'] === 'admin'): ?>
        <?php if (empty($created_quizzes)): ?>
            <div class="bg-white rounded-xl p-10 shadow-lg text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-plus-circle text-6xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Henüz quiz oluşturmadınız</h3>
                <p class="text-gray-600 mb-6">İlk quizinizi oluşturun ve kullanıcıların bilgilerini test etmelerine yardımcı olun.</p>
                <a href="create-quiz.php" class="inline-flex items-center justify-center px-5 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Quiz Oluştur
                </a>
            </div>
        <?php else: ?>
            <div class="flex justify-end mb-4">
                <a href="admin/create-quiz.php" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Yeni Quiz Oluştur
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($created_quizzes as $quiz): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-semibold px-3 py-1 bg-primary-100 text-primary-800 rounded-full">
                                    <?php echo htmlspecialchars($quiz['category_name'] ?? 'Kategorisiz'); ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                      <?php echo $quiz['difficulty'] === 'kolay' ? 'bg-green-100 text-green-800' : 
                                              ($quiz['difficulty'] === 'orta' ? 'bg-yellow-100 text-yellow-800' : 
                                              'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($quiz['difficulty']); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold mb-2 text-gray-900 line-clamp-1"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($quiz['description'], 0, 100)) . (strlen($quiz['description']) > 100 ? '...' : ''); ?></p>
                            
                            <div class="flex flex-wrap gap-2 mb-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-question-circle mr-1"></i> <?php echo $quiz['question_count']; ?> Soru
                                </div>
                                <?php if ($quiz['time_limit'] > 0): ?>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="fas fa-clock mr-1"></i> <?php echo format_time($quiz['time_limit']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-users mr-1"></i> <?php echo $quiz['participants']; ?> Katılımcı
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-pen mr-1"></i> <?php echo $quiz['attempt_count']; ?> Çözülme
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="edit-quiz.php?id=<?php echo $quiz['id']; ?>" class="flex-1 text-center px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm">
                                    <i class="fas fa-edit mr-1"></i> Düzenle
                                </a>
                                <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="flex-1 text-center px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg transition-colors text-sm">
                                    <i class="fas fa-eye mr-1"></i> Görüntüle
                                </a>
                                <a href="leaderboard.php?quiz_id=<?php echo $quiz['id']; ?>" class="flex-1 text-center px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg transition-colors text-sm">
                                    <i class="fas fa-trophy mr-1"></i> Sıralama
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
