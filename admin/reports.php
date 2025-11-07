<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Admin yetkisi kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Zaman dilimi filtresi
$time_filter = isset($_GET['time']) ? sanitize_input($_GET['time']) : 'all';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Zaman dilimi için SQL koşulları
$time_condition = '';
switch ($time_filter) {
    case 'today':
        $time_condition = "DATE(completed_at) = CURDATE()";
        break;
    case 'yesterday':
        $time_condition = "DATE(completed_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $time_condition = "DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $time_condition = "DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    default:
        $time_condition = "1=1"; // Tüm zamanlar
}

// Kategori filtresi SQL koşulu
$category_condition = $category_filter > 0 ? "q.category_id = $category_filter" : "1=1";

// Genel özet istatistikleri
$stats = [
    'total_quizzes' => $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn(),
    'total_questions' => $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_completions' => $pdo->query("SELECT COUNT(*) FROM user_scores")->fetchColumn(),
    'active_today' => $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_scores WHERE DATE(completed_at) = CURDATE()")->fetchColumn(),
    'avg_score' => $pdo->query("SELECT AVG(us.score / (SELECT SUM(points) FROM questions WHERE quiz_id = us.quiz_id) * 100) FROM user_scores us")->fetchColumn(),
    'avg_completion_time' => $pdo->query("SELECT AVG(completion_time) FROM user_scores")->fetchColumn()
];

// En popüler 5 quiz
$popular_quizzes_sql = "
    SELECT q.id, q.title, q.difficulty, COUNT(us.id) as attempt_count,
           AVG(us.score / (SELECT SUM(points) FROM questions WHERE quiz_id = q.id) * 100) as avg_score_pct
    FROM quizzes q
    JOIN user_scores us ON q.id = us.quiz_id
    WHERE $time_condition AND $category_condition
    GROUP BY q.id
    ORDER BY attempt_count DESC
    LIMIT 5
";
$popular_quizzes = $pdo->query($popular_quizzes_sql)->fetchAll();

// En aktif 5 kullanıcı
$active_users_sql = "
    SELECT u.id, u.username, u.profile_image, COUNT(us.id) as completion_count,
           AVG(us.score / (SELECT SUM(points) FROM questions WHERE quiz_id = us.quiz_id) * 100) as avg_score_pct
    FROM users u
    JOIN user_scores us ON u.id = us.user_id
    JOIN quizzes q ON us.quiz_id = q.id
    WHERE $time_condition AND $category_condition
    GROUP BY u.id
    ORDER BY completion_count DESC
    LIMIT 5
";
$active_users = $pdo->query($active_users_sql)->fetchAll();

// Zorluk seviyesine göre tamamlama sayısı
$difficulty_stats_sql = "
    SELECT q.difficulty, COUNT(us.id) as count
    FROM user_scores us
    JOIN quizzes q ON us.quiz_id = q.id
    WHERE $time_condition AND $category_condition
    GROUP BY q.difficulty
    ORDER BY FIELD(q.difficulty, 'kolay', 'orta', 'zor')
";
$difficulty_stats = $pdo->query($difficulty_stats_sql)->fetchAll(PDO::FETCH_KEY_PAIR);

// Kategorilere göre tamamlama sayısı
$category_stats_sql = "
    SELECT c.name, COUNT(us.id) as count
    FROM user_scores us
    JOIN quizzes q ON us.quiz_id = q.id
    LEFT JOIN categories c ON q.category_id = c.id
    WHERE $time_condition AND " . ($category_filter > 0 ? "q.category_id = $category_filter" : "1=1") . "
    GROUP BY q.category_id
    ORDER BY count DESC
";
$category_stats = $pdo->query($category_stats_sql)->fetchAll(PDO::FETCH_KEY_PAIR);

// Günlük aktivite (son 30 gün)
$daily_activity_sql = "
    SELECT DATE(completed_at) as date, COUNT(*) as count
    FROM user_scores us
    JOIN quizzes q ON us.quiz_id = q.id
    WHERE completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND $category_condition
    GROUP BY DATE(completed_at)
    ORDER BY date
";
$daily_activity = $pdo->query($daily_activity_sql)->fetchAll();

// JSON formatında günlük aktivite verisi hazırlama
$daily_activity_labels = [];
$daily_activity_data = [];
foreach ($daily_activity as $day) {
    $daily_activity_labels[] = date('d.m', strtotime($day['date']));
    $daily_activity_data[] = $day['count'];
}

// Saatlik aktivite dağılımı
$hourly_activity_sql = "
    SELECT HOUR(completed_at) as hour, COUNT(*) as count
    FROM user_scores us
    JOIN quizzes q ON us.quiz_id = q.id
    WHERE $time_condition AND $category_condition
    GROUP BY HOUR(completed_at)
    ORDER BY hour
";
$hourly_activity = $pdo->query($hourly_activity_sql)->fetchAll(PDO::FETCH_KEY_PAIR);

// En düşük başarı oranına sahip 5 quiz
$hardest_quizzes_sql = "
    SELECT q.id, q.title, q.difficulty,
           COUNT(us.id) as attempt_count,
           AVG(us.score / (SELECT SUM(points) FROM questions WHERE quiz_id = q.id) * 100) as avg_score_pct
    FROM quizzes q
    JOIN user_scores us ON q.id = us.quiz_id
    WHERE $time_condition AND $category_condition
    GROUP BY q.id
    HAVING attempt_count >= 5
    ORDER BY avg_score_pct ASC
    LIMIT 5
";
$hardest_quizzes = $pdo->query($hardest_quizzes_sql)->fetchAll();

// Tüm kategorileri getir (filtre için)
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

$pageTitle = "Raporlar - Admin Panel";
include './includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Detaylı Raporlar & Analizler</h1>
        
        <!-- Filtreler -->
        <div class="flex space-x-3">
            <form action="reports.php" method="get" class="flex items-center gap-3">
                <div>
                    <select name="time" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="all" <?php echo $time_filter === 'all' ? 'selected' : ''; ?>>Tüm Zamanlar</option>
                        <option value="today" <?php echo $time_filter === 'today' ? 'selected' : ''; ?>>Bugün</option>
                        <option value="yesterday" <?php echo $time_filter === 'yesterday' ? 'selected' : ''; ?>>Dün</option>
                        <option value="week" <?php echo $time_filter === 'week' ? 'selected' : ''; ?>>Son 7 Gün</option>
                        <option value="month" <?php echo $time_filter === 'month' ? 'selected' : ''; ?>>Son 30 Gün</option>
                    </select>
                </div>
                
                <div>
                    <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="0">Tüm Kategoriler</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter === (int)$category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-filter mr-1"></i> Filtrele
                </button>
                
                <a href="reports.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-sync-alt mr-1"></i> Sıfırla
                </a>
            </form>
        </div>
    </div>
    
    <!-- Özet Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <i class="fas fa-clipboard-list text-xl"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Toplam Quiz</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_quizzes']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Toplam Kullanıcı</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                    <i class="fas fa-trophy text-xl"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Toplam Tamamlama</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_completions']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <i class="fas fa-question-circle text-xl"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Toplam Soru</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_questions']); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Günlük Aktivite Grafiği -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Günlük Aktivite (Son 30 Gün)</h2>
            <div>
                <canvas id="dailyActivityChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Saatlik Aktivite Grafiği -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Saatlik Aktivite Dağılımı</h2>
            <div>
                <canvas id="hourlyActivityChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Zorluk Seviyesine Göre Dağılım -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Zorluk Seviyesine Göre Dağılım</h2>
            <div class="h-64">
                <canvas id="difficultyChart"></canvas>
            </div>
        </div>
        
        <!-- Kategorilere Göre Dağılım -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Kategorilere Göre Dağılım</h2>
            <div class="h-64">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- En Popüler Quizler -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">En Popüler Quizler</h2>
            </div>
            <div class="p-6">
                <?php if (empty($popular_quizzes)): ?>
                    <div class="text-center py-4 text-gray-500">
                        <p>Seçilen filtrelere uygun veri bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($popular_quizzes as $index => $quiz): ?>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center">
                                        <span class="text-lg font-bold text-gray-700 mr-3">#<?php echo $index + 1; ?></span>
                                        <div>
                                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                  <?php echo $quiz['difficulty'] === 'kolay' ? 'bg-green-100 text-green-800' : 
                                                          ($quiz['difficulty'] === 'orta' ? 'bg-yellow-100 text-yellow-800' : 
                                                          'bg-red-100 text-red-800'); ?>">
                                                <?php echo ucfirst($quiz['difficulty']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo $quiz['attempt_count']; ?> tamamlama
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-primary-600 h-2.5 rounded-full" style="width: <?php echo min(100, max(10, $quiz['avg_score_pct'])); ?>%"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1 text-xs text-gray-500">
                                    <span>Ortalama Başarı</span>
                                    <span><?php echo number_format($quiz['avg_score_pct'], 1); ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- En Zor Quizler -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">En Zor Quizler (Düşük Başarı Oranı)</h2>
            </div>
            <div class="p-6">
                <?php if (empty($hardest_quizzes)): ?>
                    <div class="text-center py-4 text-gray-500">
                        <p>Seçilen filtrelere uygun veri bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($hardest_quizzes as $index => $quiz): ?>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center">
                                        <span class="text-lg font-bold text-gray-700 mr-3">#<?php echo $index + 1; ?></span>
                                        <div>
                                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                  <?php echo $quiz['difficulty'] === 'kolay' ? 'bg-green-100 text-green-800' : 
                                                          ($quiz['difficulty'] === 'orta' ? 'bg-yellow-100 text-yellow-800' : 
                                                          'bg-red-100 text-red-800'); ?>">
                                                <?php echo ucfirst($quiz['difficulty']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo $quiz['attempt_count']; ?> tamamlama
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-red-500 h-2.5 rounded-full" style="width: <?php echo min(100, max(10, $quiz['avg_score_pct'])); ?>%"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1 text-xs text-gray-500">
                                    <span>Ortalama Başarı</span>
                                    <span><?php echo number_format($quiz['avg_score_pct'], 1); ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- En Aktif Kullanıcılar ve Performans İstatistikleri -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- En Aktif Kullanıcılar -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">En Aktif Kullanıcılar</h2>
            </div>
            <div class="p-6">
                <?php if (empty($active_users)): ?>
                    <div class="text-center py-4 text-gray-500">
                        <p>Seçilen filtrelere uygun veri bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($active_users as $user): ?>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center mb-3">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full object-cover" 
                                             src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../assets/images/default-avatar.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($user['username']); ?>">
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></h3>
                                        <div class="text-sm text-gray-500"><?php echo $user['completion_count']; ?> quiz tamamlama</div>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-primary-600 h-2.5 rounded-full" style="width: <?php echo min(100, max(10, $user['avg_score_pct'])); ?>%"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1 text-xs text-gray-500">
                                    <span>Ortalama Başarı</span>
                                    <span><?php echo number_format($user['avg_score_pct'], 1); ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Performans İstatistikleri -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Performans İstatistikleri</h2>
            </div>
            <div class="p-6">
                <dl class="divide-y divide-gray-200">
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Ortalama Başarı Oranı</dt>
                        <dd class="text-sm font-semibold text-right text-gray-900"><?php echo number_format($stats['avg_score'], 1); ?>%</dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Ortalama Tamamlama Süresi</dt>
                        <dd class="text-sm font-semibold text-right text-gray-900"><?php echo format_time($stats['avg_completion_time']); ?></dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Bugün Aktif Kullanıcı</dt>
                        <dd class="text-sm font-semibold text-right text-gray-900"><?php echo number_format($stats['active_today']); ?></dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Soru Başına Ortalama Tamamlama</dt>
                        <dd class="text-sm font-semibold text-right text-gray-900">
                            <?php 
                                $avg_completions_per_question = $stats['total_questions'] > 0 ? $stats['total_completions'] / $stats['total_questions'] : 0;
                                echo number_format($avg_completions_per_question, 1); 
                            ?>
                        </dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Kişi Başı Ortalama Quiz</dt>
                        <dd class="text-sm font-semibold text-right text-gray-900">
                            <?php 
                                $avg_quizzes_per_user = $stats['total_users'] > 0 ? $stats['total_completions'] / $stats['total_users'] : 0;
                                echo number_format($avg_quizzes_per_user, 1); 
                            ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
    
    <!-- Hızlı Bağlantılar -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="scores.php" class="flex items-center bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-trophy"></i>
            </div>
            <div>
                <h3 class="font-medium text-gray-900">Skor Yönetimi</h3>
                <p class="text-sm text-gray-500">Tüm skorları görüntüle ve yönet</p>
            </div>
        </a>
        
        <a href="quizzes.php" class="flex items-center bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <h3 class="font-medium text-gray-900">Quiz Yönetimi</h3>
                <p class="text-sm text-gray-500">Quizleri düzenle ve yeni ekle</p>
            </div>
        </a>
        
        <a href="users.php" class="flex items-center bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <h3 class="font-medium text-gray-900">Kullanıcı Yönetimi</h3>
                <p class="text-sm text-gray-500">Kullanıcıları görüntüle ve yönet</p>
            </div>
        </a>
        
        <a href="../leaderboard.php" target="_blank" class="flex items-center bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-medal"></i>
            </div>
            <div>
                <h3 class="font-medium text-gray-900">Liderlik Tablosu</h3>
                <p class="text-sm text-gray-500">Genel sıralamayı görüntüle</p>
            </div>
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown işlevselliği - Daha güvenilir yaklaşım
    function setupDropdowns() {
        // Tüm dropdown toggle butonlarını bul
        const dropdownToggleButtons = document.querySelectorAll('[data-dropdown-toggle]');
        
        // Her butona click listener ekle
        dropdownToggleButtons.forEach(button => {
            const targetId = button.getAttribute('data-dropdown-toggle');
            const targetDropdown = document.getElementById(targetId);
            
            if (!targetDropdown) return;
            
            // Mevcut event listener'ları temizle (duplicate önlemek için)
            button.removeEventListener('click', toggleDropdown);
            
            // Yeni event listener ekle
            button.addEventListener('click', toggleDropdown);
            
            function toggleDropdown(e) {
                e.stopPropagation();
                
                // Önce diğer tüm dropdown'ları kapat
                document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
                    if (dropdown.id !== targetId) {
                        dropdown.classList.add('hidden');
                    }
                });
                
                // Hedef dropdown'ı aç/kapat
                targetDropdown.classList.toggle('hidden');
            }
        });
        
        // Dropdown dışına tıklandığında kapat
        document.addEventListener('click', function(e) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        });
    }
    
    // Sayfadaki dropdown'ları ayarla
    setupDropdowns();
    
    // Grafiklerin yüklendiğinden emin olmak için gecikme ekleyelim
    setTimeout(function() {
        // Canvas elementlerinin varlığını kontrol et
        if (!document.getElementById('dailyActivityChart') || 
            !document.getElementById('hourlyActivityChart') ||
            !document.getElementById('difficultyChart') ||
            !document.getElementById('categoryChart')) {
            console.error('Chart canvas elements not found');
            return;
        }
        
        try {
            // Renk paletleri
            const primaryColors = [
                'rgba(59, 130, 246, 0.8)',   // Blue
                'rgba(16, 185, 129, 0.8)',   // Green
                'rgba(217, 119, 6, 0.8)',    // Orange
                'rgba(124, 58, 237, 0.8)',   // Purple
                'rgba(239, 68, 68, 0.8)',    // Red
                'rgba(236, 72, 153, 0.8)',   // Pink
                'rgba(79, 70, 229, 0.8)',    // Indigo
                'rgba(245, 158, 11, 0.8)',   // Amber
            ];
            
            // Günlük Aktivite Grafiği
            const dailyActivityCtx = document.getElementById('dailyActivityChart').getContext('2d');
            new Chart(dailyActivityCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($daily_activity_labels); ?>,
                    datasets: [{
                        label: 'Tamamlanan Quiz Sayısı',
                        data: <?php echo json_encode($daily_activity_data); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderColor: 'rgba(59, 130, 246, 0.8)',
                        tension: 0.3,
                        borderWidth: 2,
                        fill: true,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    return tooltipItems[0].label + ' tarihinde';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Saatlik Aktivite Grafiği
            const hourlyData = Array(24).fill(0);
            const hourlyLabels = Array(24).fill().map((_, i) => (i < 10 ? '0' + i : i) + ':00');
            
            <?php foreach ($hourly_activity as $hour => $count): ?>
            hourlyData[<?php echo $hour; ?>] = <?php echo $count; ?>;
            <?php endforeach; ?>
            
            const hourlyActivityCtx = document.getElementById('hourlyActivityChart').getContext('2d');
            new Chart(hourlyActivityCtx, {
                type: 'bar',
                data: {
                    labels: hourlyLabels,
                    datasets: [{
                        label: 'Tamamlanan Quiz Sayısı',
                        data: hourlyData,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Zorluk Seviyesine Göre Dağılım
            const difficultyLabels = [];
            const difficultyData = [];
            const difficultyColors = {
                'kolay': 'rgba(16, 185, 129, 0.8)',  // Green
                'orta': 'rgba(245, 158, 11, 0.8)',   // Amber
                'zor': 'rgba(239, 68, 68, 0.8)'      // Red
            };
            const difficultyBackgroundColors = [];
            
            <?php foreach (['kolay', 'orta', 'zor'] as $difficulty): ?>
                difficultyLabels.push('<?php echo ucfirst($difficulty); ?>');
                difficultyData.push(<?php echo isset($difficulty_stats[$difficulty]) ? $difficulty_stats[$difficulty] : 0; ?>);
                difficultyBackgroundColors.push(difficultyColors['<?php echo $difficulty; ?>']);
            <?php endforeach; ?>
            
            const difficultyCtx = document.getElementById('difficultyChart').getContext('2d');
            new Chart(difficultyCtx, {
                type: 'pie',
                data: {
                    labels: difficultyLabels,
                    datasets: [{
                        data: difficultyData,
                        backgroundColor: difficultyBackgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
            
            // Kategorilere Göre Dağılım
            const categoryLabels = [];
            const categoryData = [];
            const categoryColors = [];
            
            <?php 
            $i = 0;
            foreach ($category_stats as $category => $count): 
                $color_index = $i % count(array_values($primaryColors));
            ?>
                categoryLabels.push('<?php echo $category ? htmlspecialchars($category) : "Kategorisiz"; ?>');
                categoryData.push(<?php echo $count; ?>);
                categoryColors.push(primaryColors[<?php echo $color_index; ?>]);
            <?php 
                $i++;
            endforeach; 
            ?>
            
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: categoryColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15,
                                padding: 10
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating charts:', error);
        }
    }, 100); // 100ms gecikme
});
</script>

<!-- Eski/CDN Chart.js ile alternatif yükleme yöntemi (gerekirse) -->
<script>
// Chart.js CDN'den yüklenmediyse, direkt olarak alternatif bir kaynaktan yükleme dene
if (typeof Chart === 'undefined') {
    console.log('Chart.js not loaded, loading alternative source');
    var chartScript = document.createElement('script');
    chartScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
    chartScript.onload = function() {
        console.log('Alternative Chart.js loaded successfully');
        // Chart.js yüklendikten sonra grafikleri oluştur
        setupCharts();
    };
    document.head.appendChild(chartScript);
}

function setupCharts() {
    // Grafikleri oluşturmak için kod buraya...
    // Yukarıdaki try-catch bloğu içindeki kodun aynısı buraya kopyalanabilir gerekirse
}
</script>

<?php include './includes/footer.php'; ?>