<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Site ayarlarını al
$site_settings = get_site_settings();
$items_per_page = (int)($site_settings['items_per_page'] ?? 12);

// Kategori ve zorluk filtreleri
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$difficulty_filter = isset($_GET['difficulty']) ? sanitize_input($_GET['difficulty']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Sayfalama
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// SQL sorgu koşulları
$conditions = [];
$params = [];

if ($category_filter > 0) {
    $conditions[] = "q.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($difficulty_filter)) {
    $conditions[] = "q.difficulty = ?";
    $params[] = $difficulty_filter;
}

if (!empty($search_query)) {
    $conditions[] = "(q.title LIKE ? OR q.description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$where_clause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
$order_clause = " ORDER BY q.participants DESC";

// Toplam quiz sayısını al
$count_sql = "SELECT COUNT(*) FROM quizzes q" . $where_clause;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_quizzes = $count_stmt->fetchColumn();

// Quiz verisini çek
$sql = "SELECT q.*, c.name as category_name, COUNT(us.id) as completion_count
        FROM quizzes q
        LEFT JOIN categories c ON q.category_id = c.id
        LEFT JOIN user_scores us ON q.id = us.quiz_id
        $where_clause
        GROUP BY q.id
        $order_clause
        LIMIT $offset, $items_per_page";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll();

// Kategorileri al
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

$pagination = paginate($total_quizzes, $items_per_page, $page);

$pageTitle = "Quizler - QuizMeto";
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-500 to-teal-400 rounded-3xl p-8 md:p-12 mb-10 text-white relative overflow-hidden shadow-xl">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Quizler</h1>
            <p class="text-xl opacity-90 max-w-2xl">Seviyenizi test edin, öğrenin ve kendinizi geliştirin</p>
        </div>
        
        <div class="absolute -right-10 -bottom-10 opacity-20">
            <svg class="w-48 h-48" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
            </svg>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div class="mb-10">
        <form action="quizzes.php" method="get" class="bg-white p-6 rounded-xl shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="col-span-1 md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Arama</label>
                    <div class="relative">
                        <input type="text" id="search" name="search" placeholder="Quiz ara..." value="<?php echo $search_query; ?>"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select id="category" name="category" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="0">Tüm Kategoriler</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="difficulty" class="block text-sm font-medium text-gray-700 mb-1">Zorluk</label>
                    <select id="difficulty" name="difficulty" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Tüm Zorluklar</option>
                        <option value="kolay" <?php echo $difficulty_filter === 'kolay' ? 'selected' : ''; ?>>Kolay</option>
                        <option value="orta" <?php echo $difficulty_filter === 'orta' ? 'selected' : ''; ?>>Orta</option>
                        <option value="zor" <?php echo $difficulty_filter === 'zor' ? 'selected' : ''; ?>>Zor</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end space-x-3">
                <a href="quizzes.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                    Filtreleri Temizle
                </a>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-filter mr-1"></i> Filtrele
                </button>
            </div>
        </form>
    </div>
    
    <!-- Quizzes List -->
    <?php if (empty($quizzes)): ?>
        <div class="bg-white rounded-xl p-10 shadow-lg text-center">
            <img src="assets/images/no-quiz.svg" alt="Quiz bulunamadı" class="w-48 h-48 mx-auto mb-4 opacity-75">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Hiç quiz bulunamadı</h3>
            <p class="text-gray-600 mb-6">Arama kriterlerinize uygun quiz bulunamadı. Lütfen farklı filtreler deneyin.</p>
            <a href="quizzes.php" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                Tüm Quizleri Göster
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($quizzes as $quiz): ?>
                <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 neo-brutalism">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold px-3 py-1 bg-primary-100 text-primary-800 rounded-full">
                                <?php echo htmlspecialchars($quiz['category_name']); ?>
                            </span>
                            <span class="difficulty-<?php echo $quiz['difficulty']; ?>">
                                <?php echo ucfirst($quiz['difficulty']); ?>
                            </span>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                        <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($quiz['description'], 0, 100)) . (strlen($quiz['description']) > 100 ? '...' : ''); ?></p>
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span class="inline-flex items-center">
                                <i class="fas fa-question-circle mr-1"></i> <?php echo $quiz['question_count']; ?> Soru
                            </span>
                            <span class="inline-flex items-center">
                                <i class="fas fa-users mr-1"></i> <?php echo $quiz['participants']; ?> Katılımcı
                            </span>
                            <?php if ($quiz['time_limit'] > 0): ?>
                                <span class="inline-flex items-center">
                                    <i class="fas fa-clock mr-1"></i> <?php echo format_time($quiz['time_limit']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="block w-full text-center bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                            Quize Başla <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="mt-10 flex justify-center">
                <nav class="flex items-center">
                    <ul class="flex space-x-1">
                        <?php foreach ($pagination['links'] as $link): ?>
                            <li>
                                <a href="quizzes.php?page=<?php echo $link['page']; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $difficulty_filter ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                                   class="px-4 py-2 border <?php echo $link['active'] ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?> rounded-md transition-colors">
                                    <?php echo $link['text']; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
