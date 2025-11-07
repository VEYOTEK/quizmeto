<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Admin yetkisi kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$csrf_token = generate_csrf_token();

// Skor silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $score_id = (int)$_GET['delete'];
    
    // CSRF token kontrolü
    if (!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        try {
            // Skoru sil
            $stmt = $pdo->prepare("DELETE FROM user_scores WHERE id = ?");
            $stmt->execute([$score_id]);
            
            $_SESSION['admin_success'] = "Skor kaydı başarıyla silindi.";
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Skor silinirken bir hata oluştu: " . $e->getMessage();
        }
    }
    
    // Yönlendirme ile sayfayı yenile
    header("Location: scores.php");
    exit;
}

// Filtreleme parametreleri
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$quiz_filter = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$date_filter = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'completed_at';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';

// Sayfalama
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// SQL sorgu koşulları
$conditions = [];
$params = [];

if ($user_filter > 0) {
    $conditions[] = "us.user_id = ?";
    $params[] = $user_filter;
}

if ($quiz_filter > 0) {
    $conditions[] = "us.quiz_id = ?";
    $params[] = $quiz_filter;
}

if (!empty($date_filter)) {
    $conditions[] = "DATE(us.completed_at) = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $conditions[] = "(u.username LIKE ? OR q.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

// Sıralama kontrolü
$allowed_sort_fields = ['id', 'username', 'quiz_title', 'score', 'completion_time', 'completed_at', 'percentage'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'completed_at';
}

// Toplam skor sayısını al
$count_sql = "SELECT COUNT(*) FROM user_scores us 
              LEFT JOIN users u ON us.user_id = u.id
              LEFT JOIN quizzes q ON us.quiz_id = q.id" . $where_clause;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_scores = $count_stmt->fetchColumn();

// Skorları getir
$sql = "SELECT us.*, 
               u.username, u.profile_image,
               q.title as quiz_title, q.difficulty, 
               (SELECT SUM(points) FROM questions WHERE quiz_id = us.quiz_id) as total_points,
               (us.score / (SELECT SUM(points) FROM questions WHERE quiz_id = us.quiz_id) * 100) as percentage
        FROM user_scores us 
        LEFT JOIN users u ON us.user_id = u.id
        LEFT JOIN quizzes q ON us.quiz_id = q.id" . 
        $where_clause . 
        " ORDER BY " . ($sort_by === 'percentage' ? 'percentage' : ($sort_by === 'username' ? 'u.username' : ($sort_by === 'quiz_title' ? 'q.title' : "us.$sort_by"))) . 
        " $sort_dir LIMIT $offset, $per_page";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$scores = $stmt->fetchAll();

// Sayfalama
$total_pages = ceil($total_scores / $per_page);

// Kullanıcıları getir (filtreleme için)
$users_stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
$users = $users_stmt->fetchAll();

// Quizleri getir (filtreleme için)
$quizzes_stmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY title");
$quizzes = $quizzes_stmt->fetchAll();

// İstatistikler
$stats = [
    'total_scores' => $pdo->query("SELECT COUNT(*) FROM user_scores")->fetchColumn(),
    'total_users_participated' => $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_scores")->fetchColumn(),
    'avg_score_percentage' => $pdo->query("SELECT AVG(us.score / (SELECT SUM(points) FROM questions WHERE quiz_id = us.quiz_id) * 100) FROM user_scores us")->fetchColumn(),
    'completed_today' => $pdo->query("SELECT COUNT(*) FROM user_scores WHERE DATE(completed_at) = CURDATE()")->fetchColumn()
];

// Son 7 günlük istatistikler
$daily_stats_stmt = $pdo->query("
    SELECT DATE(completed_at) as date, COUNT(*) as count
    FROM user_scores
    WHERE completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(completed_at)
    ORDER BY date
");
$daily_stats = $daily_stats_stmt->fetchAll();

// En popüler 5 quiz
$popular_quizzes_stmt = $pdo->query("
    SELECT q.id, q.title, COUNT(us.id) as attempt_count
    FROM quizzes q
    JOIN user_scores us ON q.id = us.quiz_id
    GROUP BY q.id
    ORDER BY attempt_count DESC
    LIMIT 5
");
$popular_quizzes = $popular_quizzes_stmt->fetchAll();

$pageTitle = "Skor Yönetimi - Admin Panel";
include './includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Skor Yönetimi</h1>
        <a href="reports.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
            <i class="fas fa-chart-bar mr-2"></i> Detaylı Raporlar
        </a>
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
    
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Skor Listesi (3 sütun genişliğinde) -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Arama ve Filtreleme -->
                <div class="p-4 bg-gray-50 border-b border-gray-200">
                    <form action="scores.php" method="get" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="md:col-span-2">
                            <label for="search" class="sr-only">Ara</label>
                            <div class="relative">
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kullanıcı veya quiz ara..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <select name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="0">Tüm Kullanıcılar</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user_filter === $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <select name="quiz_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="0">Tüm Quizler</option>
                                <?php foreach ($quizzes as $quiz): ?>
                                    <option value="<?php echo $quiz['id']; ?>" <?php echo $quiz_filter === $quiz['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($quiz['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <div class="flex gap-2">
                                <input type="date" name="date" value="<?php echo $date_filter; ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                        
                        <div class="md:col-span-5 flex justify-end gap-2">
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                                <i class="fas fa-filter mr-1"></i> Filtrele
                            </button>
                            
                            <a href="scores.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-sync-alt mr-1"></i> Sıfırla
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Skorlar Tablosu -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=id&dir=<?php echo $sort_by === 'id' && $sort_dir === 'DESC' ? 'asc' : 'desc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" class="flex items-center">
                                        ID
                                        <?php if ($sort_by === 'id'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=username&dir=<?php echo $sort_by === 'username' && $sort_dir === 'DESC' ? 'asc' : 'desc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" class="flex items-center">
                                        Kullanıcı
                                        <?php if ($sort_by === 'username'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=quiz_title&dir=<?php echo $sort_by === 'quiz_title' && $sort_dir === 'DESC' ? 'asc' : 'desc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" class="flex items-center">
                                        Quiz
                                        <?php if ($sort_by === 'quiz_title'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=score&dir=<?php echo $sort_by === 'score' && $sort_dir === 'DESC' ? 'asc' : 'desc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" class="flex items-center">
                                        Puan
                                        <?php if ($sort_by === 'score'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=percentage&dir=<?php echo $sort_by === 'percentage' && $sort_dir === 'DESC' ? 'asc' : 'desc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" class="flex items-center">
                                        Başarı
                                        <?php if ($sort_by === 'percentage'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=completion_time&dir=<?php echo $sort_by === 'completion_time' && $sort_dir === 'DESC' ? 'asc' : 'desc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" class="flex items-center">
                                        Süre
                                        <?php if ($sort_by === 'completion_time'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=completed_at&dir=<?php echo $sort_by === 'completed_at' && $sort_dir === 'DESC' ? 'asc' : 'desc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" class="flex items-center">
                                        Tarih
                                        <?php if ($sort_by === 'completed_at'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($scores)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="text-gray-400 mb-4">
                                            <i class="fas fa-trophy text-5xl"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">Skor kaydı bulunamadı</h3>
                                        <p class="text-gray-500">Arama kriterlerinize uygun skor kaydı bulunamadı.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($scores as $score): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $score['id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8">
                                                    <img class="h-8 w-8 rounded-full object-cover" 
                                                         src="<?php echo !empty($score['profile_image']) ? '../' . $score['profile_image'] : '../assets/images/default-avatar.png'; ?>" 
                                                         alt="<?php echo htmlspecialchars($score['username']); ?>">
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <a href="?user_id=<?php echo $score['user_id']; ?>" class="hover:text-primary-600">
                                                            <?php echo htmlspecialchars($score['username']); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <a href="?quiz_id=<?php echo $score['quiz_id']; ?>" class="hover:text-primary-600">
                                                    <?php echo htmlspecialchars($score['quiz_title']); ?>
                                                </a>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                                      <?php echo $score['difficulty'] === 'kolay' ? 'bg-green-100 text-green-800' : 
                                                              ($score['difficulty'] === 'orta' ? 'bg-yellow-100 text-yellow-800' : 
                                                              'bg-red-100 text-red-800'); ?>">
                                                    <?php echo ucfirst($score['difficulty']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo $score['score']; ?> / <?php echo $score['total_points']; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                                $percentage = $score['percentage'];
                                                $class = $percentage >= 80 ? 'bg-green-100 text-green-800' : 
                                                         ($percentage >= 50 ? 'bg-yellow-100 text-yellow-800' : 
                                                         'bg-red-100 text-red-800');
                                            ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo $class; ?>">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo format_time($score['completion_time']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('d.m.Y H:i', strtotime($score['completed_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <div class="flex items-center justify-center space-x-2">
                                                <a href="../quiz-result.php?quiz_id=<?php echo $score['quiz_id']; ?>&user_id=<?php echo $score['user_id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-900" title="Görüntüle">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="scores.php?delete=<?php echo $score['id']; ?>&token=<?php echo $csrf_token; ?>" 
                                                   class="text-red-600 hover:text-red-900" 
                                                   title="Sil"
                                                   onclick="return confirm('Bu skor kaydını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Sayfalama -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Önceki</span>
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<a href="?page=1&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . ($user_filter ? '&user_id=' . $user_filter : '') . ($quiz_filter ? '&quiz_id=' . $quiz_filter : '') . ($date_filter ? '&date=' . $date_filter : '') . '" 
                                                 class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        echo '<a href="?page=' . $i . '&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . ($user_filter ? '&user_id=' . $user_filter : '') . ($quiz_filter ? '&quiz_id=' . $quiz_filter : '') . ($date_filter ? '&date=' . $date_filter : '') . '" 
                                                 class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium ' . ($i == $page ? 'bg-primary-50 text-primary-600 z-10' : 'text-gray-700 hover:bg-gray-50') . '">' . $i . '</a>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                        echo '<a href="?page=' . $total_pages . '&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . ($user_filter ? '&user_id=' . $user_filter : '') . ($quiz_filter ? '&quiz_id=' . $quiz_filter : '') . ($date_filter ? '&date=' . $date_filter : '') . '" 
                                                 class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $user_filter ? '&user_id=' . $user_filter : ''; ?><?php echo $quiz_filter ? '&quiz_id=' . $quiz_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Sonraki</span>
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sağ Kenar Paneli (1 sütun genişliğinde) -->
        <div class="lg:col-span-1">
            <!-- İstatistikler -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Skor İstatistikleri</h3>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-500">Toplam Skor</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['total_scores']); ?></div>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-500">Toplam Kullanıcı</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['total_users_participated']); ?></div>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-500">Ortalama Başarı</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['avg_score_percentage'], 1); ?>%</div>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-500">Bugün Tamamlanan</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['completed_today']); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($daily_stats)): ?>
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Son 7 Gün Aktivite</h4>
                            <div class="h-24 flex items-end space-x-1">
                                <?php 
                                    $max_count = max(array_column($daily_stats, 'count'));
                                    foreach ($daily_stats as $stat):
                                        $height = $max_count > 0 ? ($stat['count'] / $max_count) * 100 : 0;
                                        $day_name = date('D', strtotime($stat['date']));
                                        switch ($day_name) {
                                            case 'Mon': $day_name = 'Pzt'; break;
                                            case 'Tue': $day_name = 'Sal'; break;
                                            case 'Wed': $day_name = 'Çar'; break;
                                            case 'Thu': $day_name = 'Per'; break;
                                            case 'Fri': $day_name = 'Cum'; break;
                                            case 'Sat': $day_name = 'Cmt'; break;
                                            case 'Sun': $day_name = 'Paz'; break;
                                        }
                                ?>
                                    <div class="flex-1 flex flex-col items-center">
                                        <div class="bg-primary-600 rounded-t w-full" style="height: <?php echo $height; ?>%"></div>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo $day_name; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- En Popüler Quizler -->
            <?php if (!empty($popular_quizzes)): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">En Popüler Quizler</h3>
                    
                    <ul class="space-y-3">
                        <?php foreach ($popular_quizzes as $quiz): ?>
                            <li class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-sm font-medium text-gray-900 truncate pr-2">
                                    <a href="?quiz_id=<?php echo $quiz['id']; ?>" class="hover:text-primary-600">
                                        <?php echo htmlspecialchars($quiz['title']); ?>
                                    </a>
                                </div>
                                <div class="text-sm text-gray-600 whitespace-nowrap">
                                    <?php echo $quiz['attempt_count']; ?> çözülme
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Hızlı Bağlantılar -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Hızlı Bağlantılar</h3>
                
                <div class="space-y-2">
                    <a href="reports.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-3">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Detaylı Raporlar</div>
                            <div class="text-xs text-gray-500">Tüm istatistikleri görüntüle</div>
                        </div>
                    </a>
                    
                    <a href="quizzes.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Quizleri Yönet</div>
                            <div class="text-xs text-gray-500">Tüm quizleri görüntüle ve düzenle</div>
                        </div>
                    </a>
                    
                    <a href="users.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Kullanıcıları Yönet</div>
                            <div class="text-xs text-gray-500">Kullanıcıları düzenle</div>
                        </div>
                    </a>
                    
                    <a href="../leaderboard.php" target="_blank" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mr-3">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Liderlik Tablosu</div>
                            <div class="text-xs text-gray-500">Genel sıralamayı görüntüle</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include './includes/footer.php'; ?>
