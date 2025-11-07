<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Admin yetkisi kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $quiz_id = (int)$_GET['delete'];
    
    // CSRF token kontrolü
    if (!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // İlişkili kullanıcı skorlarını sil
            $stmt = $pdo->prepare("DELETE FROM user_scores WHERE quiz_id = ?");
            $stmt->execute([$quiz_id]);
            
            // İlişkili soruların cevaplarını sil (alt sorgular)
            $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id IN (SELECT id FROM questions WHERE quiz_id = ?)");
            $stmt->execute([$quiz_id]);
            
            // İlişkili soruları sil
            $stmt = $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?");
            $stmt->execute([$quiz_id]);
            
            // Quiz'i sil
            $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
            $stmt->execute([$quiz_id]);
            
            $pdo->commit();
            $_SESSION['admin_success'] = "Quiz başarıyla silindi.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['admin_error'] = "Quiz silinirken bir hata oluştu: " . $e->getMessage();
        }
    }
    
    // Yönlendirme ile sayfayı yenile (GET parametrelerini temizle)
    header("Location: quizzes.php");
    exit;
}

// Quizleri listeleme (sayfalama ve sıralama ile)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'id';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'desc' ? 'DESC' : 'ASC';

// Arama koşulu
$search_condition = '';
$params = [];
if (!empty($search)) {
    $search_condition = "WHERE q.title LIKE ? OR q.description LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Sıralama kontrolü
$allowed_sort_fields = ['id', 'title', 'category_id', 'difficulty', 'participants', 'created_at'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'id';
}

// Quizlerin toplam sayısını al
$count_sql = "SELECT COUNT(*) FROM quizzes q $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_quizzes = $count_stmt->fetchColumn();

// Quizleri getir
$sql = "SELECT q.*, c.name as category_name, u.username as creator_name, 
               (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count 
        FROM quizzes q 
        LEFT JOIN categories c ON q.category_id = c.id 
        LEFT JOIN users u ON q.created_by = u.id 
        $search_condition 
        ORDER BY q.$sort_by $sort_dir 
        LIMIT $offset, $per_page";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll();

// Sayfalama
$total_pages = ceil($total_quizzes / $per_page);

// Kategorileri getir
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

// Düzenleme ve yeni quiz bağlantıları için CSRF token
$csrf_token = generate_csrf_token();

// Admin paneli başlık
$pageTitle = "Quiz Yönetimi - Admin Panel";
include './includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Quiz Yönetimi</h1>
        <a href="create-quiz.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
            <i class="fas fa-plus mr-2"></i> Yeni Quiz Ekle
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
    
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <!-- Arama ve Filtreleme -->
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <form action="quizzes.php" method="get" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <label for="search" class="sr-only">Ara</label>
                    <div class="relative">
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Quiz ara..." 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-search mr-1"></i> Ara
                    </button>
                    <a href="quizzes.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-sync-alt mr-1"></i> Sıfırla
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Tablo -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=id&dir=<?php echo $sort_by === 'id' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="flex items-center">
                                ID
                                <?php if ($sort_by === 'id'): ?>
                                    <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort ml-1 text-gray-300"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=title&dir=<?php echo $sort_by === 'title' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="flex items-center">
                                Başlık
                                <?php if ($sort_by === 'title'): ?>
                                    <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort ml-1 text-gray-300"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=category_id&dir=<?php echo $sort_by === 'category_id' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="flex items-center">
                                Kategori
                                <?php if ($sort_by === 'category_id'): ?>
                                    <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort ml-1 text-gray-300"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=difficulty&dir=<?php echo $sort_by === 'difficulty' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="flex items-center">
                                Zorluk
                                <?php if ($sort_by === 'difficulty'): ?>
                                    <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort ml-1 text-gray-300"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Soru Sayısı
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=participants&dir=<?php echo $sort_by === 'participants' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="flex items-center">
                                Katılımcı
                                <?php if ($sort_by === 'participants'): ?>
                                    <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort ml-1 text-gray-300"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=created_at&dir=<?php echo $sort_by === 'created_at' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="flex items-center">
                                Oluşturulma
                                <?php if ($sort_by === 'created_at'): ?>
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
                    <?php if (empty($quizzes)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-gray-400 mb-4">
                                    <i class="fas fa-search text-5xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-1">Quiz bulunamadı</h3>
                                <p class="text-gray-500">Arama kriterlerinize uygun quiz bulunamadı.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $quiz['id']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></div>
                                    <div class="text-xs text-gray-500 max-w-xs truncate"><?php echo htmlspecialchars($quiz['description']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($quiz['category_name'] ?? 'Kategorisiz'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?php echo $quiz['difficulty'] === 'kolay' ? 'bg-green-100 text-green-800' : 
                                                  ($quiz['difficulty'] === 'orta' ? 'bg-yellow-100 text-yellow-800' : 
                                                  'bg-red-100 text-red-800'); ?>">
                                        <?php echo ucfirst($quiz['difficulty']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $quiz['question_count']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $quiz['participants']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo date('d.m.Y H:i', strtotime($quiz['created_at'])); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($quiz['creator_name'] ?? 'Bilinmiyor'); ?> tarafından
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="../quiz.php?id=<?php echo $quiz['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-quiz.php?id=<?php echo $quiz['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="quiz-questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Soruları Yönet">
                                            <i class="fas fa-question-circle"></i>
                                        </a>
                                        <a href="quizzes.php?delete=<?php echo $quiz['id']; ?>&token=<?php echo $csrf_token; ?>" 
                                           class="text-red-600 hover:text-red-900" 
                                           title="Sil"
                                           onclick="return confirm('Bu quiz\'i silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')">
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
                                <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Önceki</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<a href="?page=1&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . '" 
                                         class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<a href="?page=' . $i . '&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . '" 
                                         class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium ' . ($i == $page ? 'bg-primary-50 text-primary-600 z-10' : 'text-gray-700 hover:bg-gray-50') . '">' . $i . '</a>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                echo '<a href="?page=' . $total_pages . '&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . '" 
                                         class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
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
    
    <!-- Bilgi Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <i class="fas fa-clipboard-list text-xl"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Toplam Quiz</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo $total_quizzes; ?></div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Toplam Katılımcı</div>
                    <div class="text-2xl font-bold text-gray-900">
                        <?php
                        $total_participants = $pdo->query("SELECT SUM(participants) FROM quizzes")->fetchColumn();
                        echo number_format($total_participants);
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <i class="fas fa-question-circle text-xl"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Toplam Soru</div>
                    <div class="text-2xl font-bold text-gray-900">
                        <?php
                        $total_questions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
                        echo number_format($total_questions);
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                    <i class="fas fa-trophy text-xl"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Toplam Tamamlama</div>
                    <div class="text-2xl font-bold text-gray-900">
                        <?php
                        $total_completions = $pdo->query("SELECT COUNT(*) FROM user_scores")->fetchColumn();
                        echo number_format($total_completions);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hızlı Aksiyon Butonları -->
    <div class="flex flex-wrap gap-4 justify-center mb-8">
        <a href="create-quiz.php" class="p-4 bg-white rounded-lg shadow-md text-center flex-1 md:flex-none min-w-[200px] hover:shadow-lg transition-shadow">
            <div class="text-green-600 text-3xl mb-2">
                <i class="fas fa-plus-circle"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-1">Yeni Quiz</h3>
            <p class="text-sm text-gray-500">Yeni bir quiz oluşturun</p>
        </a>
        
        <a href="categories.php" class="p-4 bg-white rounded-lg shadow-md text-center flex-1 md:flex-none min-w-[200px] hover:shadow-lg transition-shadow">
            <div class="text-blue-600 text-3xl mb-2">
                <i class="fas fa-folder"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-1">Kategoriler</h3>
            <p class="text-sm text-gray-500">Kategorileri yönetin</p>
        </a>
        
        <a href="users.php" class="p-4 bg-white rounded-lg shadow-md text-center flex-1 md:flex-none min-w-[200px] hover:shadow-lg transition-shadow">
            <div class="text-purple-600 text-3xl mb-2">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-1">Kullanıcılar</h3>
            <p class="text-sm text-gray-500">Kullanıcıları yönetin</p>
        </a>
        
        <a href="reports.php" class="p-4 bg-white rounded-lg shadow-md text-center flex-1 md:flex-none min-w-[200px] hover:shadow-lg transition-shadow">
            <div class="text-teal-600 text-3xl mb-2">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-1">Raporlar</h3>
            <p class="text-sm text-gray-500">İstatistikleri görüntüleyin</p>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
