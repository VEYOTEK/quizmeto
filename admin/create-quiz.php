<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Admin yetkisi kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    // CSRF token kontrolü
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        $difficulty = sanitize_input($_POST['difficulty']);
        $time_limit = (int)$_POST['time_limit'];
        
        // Temel doğrulama
        if (empty($title)) {
            $error = "Quiz başlığı gereklidir.";
        } elseif (strlen($title) > 100) {
            $error = "Quiz başlığı 100 karakterden uzun olamaz.";
        } elseif (empty($description)) {
            $error = "Quiz açıklaması gereklidir.";
        } elseif (!in_array($difficulty, ['kolay', 'orta', 'zor'])) {
            $error = "Geçersiz zorluk seviyesi.";
        } else {
            try {
                // Quiz'i veritabanına ekle
                $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, category_id, difficulty, time_limit, created_by, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $description, $category_id, $difficulty, $time_limit, $_SESSION['user_id']]);
                
                $quiz_id = $pdo->lastInsertId();
                $success = "Quiz başarıyla oluşturuldu. Şimdi sorular ekleyebilirsiniz.";
                
                // Quiz sorularını eklemek için yönlendir
                header("Location: quiz-questions.php?quiz_id=$quiz_id");
                exit;
            } catch (PDOException $e) {
                $error = "Quiz oluşturulurken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

// Kategorileri getir
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

$pageTitle = "Yeni Quiz Oluştur - Admin Panel";
include './includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="quizzes.php" class="hover:text-primary-600">Quizler</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span>Yeni Quiz Oluştur</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Yeni Quiz Oluştur</h1>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $error; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $success; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <form action="create-quiz.php" method="post" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="create_quiz" value="1">
            
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Quiz Başlığı <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required maxlength="100"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    <p class="mt-1 text-xs text-gray-500">Quiz başlığı en fazla 100 karakter olabilir.</p>
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Quiz Açıklaması <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="4" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">Quiz hakkında kısa bir açıklama yazın.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select id="category_id" name="category_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="0">Kategorisiz</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="difficulty" class="block text-sm font-medium text-gray-700 mb-1">Zorluk Seviyesi <span class="text-red-500">*</span></label>
                        <select id="difficulty" name="difficulty" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="kolay" <?php echo isset($_POST['difficulty']) && $_POST['difficulty'] === 'kolay' ? 'selected' : ''; ?>>Kolay</option>
                            <option value="orta" <?php echo !isset($_POST['difficulty']) || $_POST['difficulty'] === 'orta' ? 'selected' : ''; ?>>Orta</option>
                            <option value="zor" <?php echo isset($_POST['difficulty']) && $_POST['difficulty'] === 'zor' ? 'selected' : ''; ?>>Zor</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="time_limit" class="block text-sm font-medium text-gray-700 mb-1">Zaman Sınırı (saniye)</label>
                        <input type="number" id="time_limit" name="time_limit" min="0" step="30"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               value="<?php echo isset($_POST['time_limit']) ? htmlspecialchars($_POST['time_limit']) : '300'; ?>">
                        <p class="mt-1 text-xs text-gray-500">0 = Zaman sınırı yok, 300 = 5 dakika</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <a href="quizzes.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        İptal
                    </a>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-save mr-2"></i> Quiz Oluştur ve Sorulara Geç
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
