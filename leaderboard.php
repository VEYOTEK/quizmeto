<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Site ayarlarını al
$site_settings = get_site_settings();
$enable_leaderboard = ($site_settings['enable_leaderboard'] ?? '1') === '1';

// Liderlik tablosu kapalıysa ana sayfaya yönlendir
if (!$enable_leaderboard) {
    $_SESSION['error'] = "Liderlik tablosu şu anda devre dışı bırakılmıştır.";
    header("Location: index.php");
    exit;
}

// Filtreleme opsiyonları
$quiz_filter = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$time_filter = isset($_GET['time']) ? sanitize_input($_GET['time']) : 'all';

// Sayfalama
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Zaman filtresi için tarih hesaplama
$date_filter = '';
$params = [];

if ($time_filter === 'today') {
    $date_filter = "AND DATE(us.completed_at) = CURDATE()";
} elseif ($time_filter === 'week') {
    $date_filter = "AND us.completed_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($time_filter === 'month') {
    $date_filter = "AND us.completed_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}

// Belirli bir quiz için sıralama veya genel sıralama
if ($quiz_filter > 0) {
    // Quiz geçerli mi kontrol et
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_filter]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        $quiz_filter = 0;
    }
    
    // Quiz bazlı sıralama
    $count_sql = "SELECT COUNT(*) FROM user_scores us WHERE us.quiz_id = ? $date_filter";
    $count_stmt = $pdo->prepare($count_sql);
    $count_params = [$quiz_filter];
    if (!empty($date_filter)) {
        $count_params = array_merge($count_params, $params);
    }
    $count_stmt->execute($count_params);
    $total_scores = $count_stmt->fetchColumn();
    
    $sql = "SELECT us.*, u.username, u.profile_image, q.title as quiz_title 
            FROM user_scores us 
            JOIN users u ON us.user_id = u.id 
            JOIN quizzes q ON us.quiz_id = q.id 
            WHERE us.quiz_id = ? $date_filter
            ORDER BY us.score DESC, us.completion_time ASC 
            LIMIT $offset, $per_page";
    
    $stmt = $pdo->prepare($sql);
    $sql_params = [$quiz_filter];
    if (!empty($date_filter)) {
        $sql_params = array_merge($sql_params, $params);
    }
    $stmt->execute($sql_params);
    $scores = $stmt->fetchAll();
} else {
    // Genel sıralama (tüm kullanıcıların toplam puanı)
    $count_sql = "SELECT COUNT(DISTINCT u.id) FROM users u 
                  JOIN user_scores us ON u.id = us.user_id 
                  WHERE 1=1 $date_filter";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_scores = $count_stmt->fetchColumn();
    
    $sql = "SELECT u.id, u.username, u.profile_image, 
                  SUM(us.score) as total_score, 
                  COUNT(DISTINCT us.quiz_id) as quizzes_completed,
                  MAX(us.completed_at) as last_activity
           FROM users u 
           JOIN user_scores us ON u.id = us.user_id 
           WHERE 1=1 $date_filter
           GROUP BY u.id 
           ORDER BY total_score DESC, quizzes_completed DESC 
           LIMIT $offset, $per_page";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $scores = $stmt->fetchAll();
}

// Mevcut quizleri al (filtre için)
$quizzes_stmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY title");
$quizzes = $quizzes_stmt->fetchAll();

$pagination = paginate($total_scores, $per_page, $page);

$pageTitle = "Liderlik Tablosu - QuizMeto";
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-3xl p-8 md:p-12 mb-10 text-white relative overflow-hidden shadow-xl">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Liderlik Tablosu</h1>
            <p class="text-xl opacity-90 max-w-2xl">En yüksek puanları görmek için filtreleyin ve sıralayın</p>
        </div>
        
        <div class="absolute -right-10 -bottom-10 opacity-20">
            <svg class="w-48 h-48" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M5 8h2v8H5zm7 0h2v8h-2zm7 0h2v8h-2z"/>
                <path d="M3 6c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1s1-.45 1-1V7c0-.55-.45-1-1-1zm5 0c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1s1-.45 1-1V7c0-.55-.45-1-1-1zm5 0c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1s1-.45 1-1V7c0-.55-.45-1-1-1zm5 0c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1s1-.45 1-1V7c0-.55-.45-1-1-1z"/>
                <path d="M19 4h-4c-.55 0-1 .45-1 1s.45 1 1 1h4c.55 0 1-.45 1-1s-.45-1-1-1zm0 17h-4c-.55 0-1 .45-1 1s.45 1 1 1h4c.55 0 1-.45 1-1s-.45-1-1-1z"/>
            </svg>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div class="mb-10">
        <form action="leaderboard.php" method="get" class="bg-white p-6 rounded-xl shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="quiz-filter" class="block text-sm font-medium text-gray-700 mb-1">Quiz Seçimi</label>
                    <select name="quiz_id" id="quiz-filter" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="0" <?php echo $quiz_filter === 0 ? 'selected' : ''; ?>>Tüm Quizler (Genel Sıralama)</option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option value="<?php echo $quiz['id']; ?>" <?php echo $quiz_filter === $quiz['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($quiz['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="time-filter" class="block text-sm font-medium text-gray-700 mb-1">Zaman Dilimi</label>
                    <select name="time" id="time-filter" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="all" <?php echo $time_filter === 'all' ? 'selected' : ''; ?>>Tüm Zamanlar</option>
                        <option value="today" <?php echo $time_filter === 'today' ? 'selected' : ''; ?>>Bugün</option>
                        <option value="week" <?php echo $time_filter === 'week' ? 'selected' : ''; ?>>Bu Hafta</option>
                        <option value="month" <?php echo $time_filter === 'month' ? 'selected' : ''; ?>>Bu Ay</option>
                    </select>
                </div>
                
                <div class="self-end">
                    <button type="submit" class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-filter mr-1"></i> Filtrele
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (empty($scores)): ?>
        <div class="bg-white rounded-xl p-10 shadow-lg text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-trophy text-8xl opacity-50"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Hiç skor bulunamadı</h3>
            <p class="text-gray-600 mb-6">Seçtiğiniz kriterlere uygun hiç skor kaydı bulunmuyor. Lütfen filtrelerinizi değiştirin veya daha sonra tekrar deneyin.</p>
            <a href="leaderboard.php" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                Tüm Sıralamayı Göster
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sıra</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                            <?php if ($quiz_filter > 0): ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamamlama Süresi</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamamlama Tarihi</th>
                            <?php else: ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam Puan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamamlanan Quiz</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Son Aktivite</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($scores as $index => $score): ?>
                            <tr class="<?php echo isset($_SESSION['user_id']) && $_SESSION['user_id'] == $score['id'] ? 'bg-primary-50' : 'hover:bg-gray-50'; ?> transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($index < 3): ?>
                                            <div class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center 
                                                        <?php echo $index == 0 ? 'bg-yellow-100 text-yellow-700' : 
                                                                ($index == 1 ? 'bg-gray-100 text-gray-700' : 
                                                                'bg-orange-100 text-orange-700'); ?> font-bold">
                                                <?php echo $offset + $index + 1; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-500 font-medium"><?php echo $offset + $index + 1; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover" 
                                                src="<?php echo !empty($score['profile_image']) ? $score['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                                alt="Profile">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($score['username']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <?php if ($quiz_filter > 0): ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-primary-600"><?php echo $score['score']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo format_time($score['completion_time']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo date('d.m.Y H:i', strtotime($score['completed_at'])); ?></div>
                                    </td>
                                <?php else: ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-primary-600"><?php echo $score['total_score']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $score['quizzes_completed']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo format_time_ago($score['last_activity']); ?></div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php foreach ($pagination['links'] as $link): ?>
                                <?php if ($link['text'] === '&laquo;'): ?>
                                    <a href="leaderboard.php?page=<?php echo $link['page']; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $time_filter !== 'all' ? '&time=' . $time_filter : ''; ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php elseif ($link['text'] === '&raquo;'): ?>
                                    <a href="leaderboard.php?page=<?php echo $link['page']; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $time_filter !== 'all' ? '&time=' . $time_filter : ''; ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="leaderboard.php?page=<?php echo $link['page']; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $time_filter !== 'all' ? '&time=' . $time_filter : ''; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $link['active'] ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
                                        <?php echo $link['text']; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['user_id']) && $quiz_filter > 0): ?>
        <?php
        // Kullanıcının sıralamasını bul
        $stmt = $pdo->prepare("SELECT COUNT(*) + 1 as user_rank 
                               FROM user_scores 
                               WHERE quiz_id = ? AND score > (
                                   SELECT score FROM user_scores 
                                   WHERE user_id = ? AND quiz_id = ?
                               )");
        $stmt->execute([$quiz_filter, $_SESSION['user_id'], $quiz_filter]);
        $user_rank = $stmt->fetchColumn();
        
        // Kullanıcının puanını al
        $stmt = $pdo->prepare("SELECT * FROM user_scores 
                               WHERE user_id = ? AND quiz_id = ?");
        $stmt->execute([$_SESSION['user_id'], $quiz_filter]);
        $user_score = $stmt->fetch();
        
        if ($user_score):
        ?>
            <div class="mt-8">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Sizin Pozisyonunuz</h3>
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-primary-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 text-primary-600 font-bold">
                            #<?php echo $user_rank; ?>
                        </div>
                        <div class="ml-4 flex items-center flex-grow">
                            <div class="flex-shrink-0 h-10 w-10">
                                <img class="h-10 w-10 rounded-full object-cover border-2 border-primary-300" 
                                    src="<?php echo !empty($_SESSION['user_image']) ? $_SESSION['user_image'] : 'assets/images/default-avatar.png'; ?>" 
                                    alt="Your Avatar">
                            </div>
                            <div class="ml-4 flex-grow">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            </div>
                            <div class="flex space-x-6">
                                <div>
                                    <div class="text-xs text-gray-500">Puan</div>
                                    <div class="text-sm font-semibold text-primary-600"><?php echo $user_score['score']; ?></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Süre</div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo format_time($user_score['completion_time']); ?></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Tarih</div>
                                    <div class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($user_score['completed_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
