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

// Kategori silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // CSRF token kontrolü
    if (!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        try {
            // Önce bu kategoriye ait quiz sayısını kontrol et
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE category_id = ?");
            $check_stmt->execute([$category_id]);
            $quiz_count = $check_stmt->fetchColumn();
            
            if ($quiz_count > 0) {
                $_SESSION['admin_error'] = "Bu kategori $quiz_count quiz tarafından kullanılıyor. Silmeden önce bu quizlerin kategorisini değiştirin.";
            } else {
                // Kategoriyi sil
                $delete_stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $delete_stmt->execute([$category_id]);
                
                $_SESSION['admin_success'] = "Kategori başarıyla silindi.";
            }
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Kategori silinirken bir hata oluştu: " . $e->getMessage();
        }
    }
    
    // Yönlendirme ile sayfayı yenile
    header("Location: categories.php");
    exit;
}

// Kategori ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        
        if (empty($name)) {
            $_SESSION['admin_error'] = "Kategori adı gereklidir.";
        } else {
            try {
                // Kategori adının benzersiz olup olmadığını kontrol et
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
                $check_stmt->execute([$name]);
                $exists = $check_stmt->fetchColumn();
                
                if ($exists) {
                    $_SESSION['admin_error'] = "Bu kategori adı zaten kullanılıyor.";
                } else {
                    // Kategoriyi ekle
                    $insert_stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $insert_stmt->execute([$name, $description]);
                    
                    $_SESSION['admin_success'] = "Kategori başarıyla eklendi.";
                    // Formun yeniden gönderilmesini önlemek için yönlendirme yap
                    header("Location: categories.php");
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['admin_error'] = "Kategori eklenirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

// Kategori güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $category_id = (int)$_POST['category_id'];
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        
        if (empty($name)) {
            $_SESSION['admin_error'] = "Kategori adı gereklidir.";
        } else {
            try {
                // Kategori adının benzersiz olup olmadığını kontrol et (kendisi hariç)
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
                $check_stmt->execute([$name, $category_id]);
                $exists = $check_stmt->fetchColumn();
                
                if ($exists) {
                    $_SESSION['admin_error'] = "Bu kategori adı zaten kullanılıyor.";
                } else {
                    // Kategoriyi güncelle
                    $update_stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    $update_stmt->execute([$name, $description, $category_id]);
                    
                    $_SESSION['admin_success'] = "Kategori başarıyla güncellendi.";
                    // Formun yeniden gönderilmesini önlemek için yönlendirme yap
                    header("Location: categories.php");
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['admin_error'] = "Kategori güncellenirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

// Kategorileri getir
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'name';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'desc' ? 'DESC' : 'ASC';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Arama koşulu
$search_condition = '';
$params = [];
if (!empty($search)) {
    $search_condition = "WHERE name LIKE ? OR description LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Sıralama kontrolü
$allowed_sort_fields = ['id', 'name', 'quiz_count'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'name';
}

// Kategorilerin toplam sayısını al
$count_sql = "SELECT COUNT(*) FROM categories $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_categories = $count_stmt->fetchColumn();

// Her kategoriye ait quiz sayısını da getir
$sql = "SELECT c.*, 
              (SELECT COUNT(*) FROM quizzes WHERE category_id = c.id) as quiz_count 
        FROM categories c 
        $search_condition 
        ORDER BY " . ($sort_by === 'quiz_count' ? 'quiz_count' : "c.$sort_by") . " $sort_dir";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

$pageTitle = "Kategoriler - Admin Panel";
include './includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Kategori Yönetimi</h1>
        <button id="add-category-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
            <i class="fas fa-plus mr-2"></i> Yeni Kategori Ekle
        </button>
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
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Kategori Listesi -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-4 bg-gray-50 border-b border-gray-200">
                    <form action="categories.php" method="get" class="flex gap-4">
                        <div class="flex-grow">
                            <label for="search" class="sr-only">Ara</label>
                            <div class="relative">
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kategori ara..." 
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
                            <a href="categories.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
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
                                    <a href="?sort=name&dir=<?php echo $sort_by === 'name' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="flex items-center">
                                        Kategori Adı
                                        <?php if ($sort_by === 'name'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Açıklama
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=quiz_count&dir=<?php echo $sort_by === 'quiz_count' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="flex items-center">
                                        Quiz Sayısı
                                        <?php if ($sort_by === 'quiz_count'): ?>
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
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="text-gray-400 mb-4">
                                            <i class="fas fa-folder-open text-5xl"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">Kategori bulunamadı</h3>
                                        <p class="text-gray-500">Arama kriterlerinize uygun kategori bulunamadı.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $category['id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500 max-w-xs truncate"><?php echo htmlspecialchars($category['description'] ?: 'Açıklama yok'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $category['quiz_count']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button type="button" class="edit-category-btn text-blue-600 hover:text-blue-900" title="Düzenle" data-id="<?php echo $category['id']; ?>" data-name="<?php echo htmlspecialchars($category['name']); ?>" data-description="<?php echo htmlspecialchars($category['description']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($category['quiz_count'] == 0): ?>
                                                    <a href="categories.php?delete=<?php echo $category['id']; ?>&token=<?php echo $csrf_token; ?>" 
                                                       class="text-red-600 hover:text-red-900" 
                                                       title="Sil"
                                                       onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 cursor-not-allowed" title="Bu kategori quizlerde kullanıldığı için silinemez">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 bg-gray-50 border-t border-gray-200">
                    <div class="text-sm text-gray-500">
                        Toplam <?php echo $total_categories; ?> kategori
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hızlı Bilgiler Panel -->
        <div class="lg:col-span-1">
            <div id="category-form-container" class="bg-white rounded-xl shadow-lg p-6 mb-6 hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="form-title" class="text-lg font-bold text-gray-900">Yeni Kategori Ekle</h3>
                    <button type="button" id="close-form" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="category-form" action="categories.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="action-type" name="add_category" value="1">
                    <input type="hidden" id="category-id" name="category_id" value="">
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Kategori Adı <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label>
                        <textarea id="description" name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancel-btn" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            İptal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <span id="form-submit-text">Kategori Ekle</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- İstatistikler -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Kategori İstatistikleri</h3>
                
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Toplam Kategori</span>
                            <span class="text-lg font-bold text-primary-600"><?php echo $total_categories; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary-600 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Toplam Quiz</span>
                            <span class="text-lg font-bold text-primary-600">
                                <?php
                                    $total_quizzes = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
                                    echo $total_quizzes;
                                ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary-600 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">En Çok Quize Sahip Kategori</span>
                            <span class="text-lg font-bold text-primary-600">
                                <?php
                                    if (!empty($categories)) {
                                        // En çok quiz sayısına sahip kategoriyi bul
                                        usort($categories, function($a, $b) {
                                            return $b['quiz_count'] - $a['quiz_count'];
                                        });
                                        echo $categories[0]['name'] . ' (' . $categories[0]['quiz_count'] . ')';
                                    } else {
                                        echo "Yok";
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Kategorisiz Quiz</span>
                            <span class="text-lg font-bold text-primary-600">
                                <?php
                                    $uncategorized = $pdo->query("SELECT COUNT(*) FROM quizzes WHERE category_id IS NULL OR category_id = 0")->fetchColumn();
                                    echo $uncategorized;
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hızlı Bağlantılar -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Hızlı Bağlantılar</h3>
                
                <div class="space-y-2">
                    <a href="quizzes.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Quizleri Yönet</div>
                            <div class="text-xs text-gray-500">Tüm quizleri görüntüle ve düzenle</div>
                        </div>
                    </a>
                    
                    <a href="create-quiz.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Yeni Quiz Ekle</div>
                            <div class="text-xs text-gray-500">Yeni bir quiz oluştur</div>
                        </div>
                    </a>
                    
                    <a href="../quizzes.php" target="_blank" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-3">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Quizleri Görüntüle</div>
                            <div class="text-xs text-gray-500">Kullanıcı tarafında quizleri görüntüle</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addCategoryBtn = document.getElementById('add-category-btn');
    const categoryFormContainer = document.getElementById('category-form-container');
    const closeFormBtn = document.getElementById('close-form');
    const cancelBtn = document.getElementById('cancel-btn');
    const categoryForm = document.getElementById('category-form');
    const formTitle = document.getElementById('form-title');
    const formSubmitText = document.getElementById('form-submit-text');
    const actionType = document.getElementById('action-type');
    const categoryId = document.getElementById('category-id');
    const categoryName = document.getElementById('name');
    const categoryDescription = document.getElementById('description');
    
    // Kategori düzenleme butonlarına event listener ekle
    const editButtons = document.querySelectorAll('.edit-category-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            
            // Form alanlarını doldur
            categoryId.value = id;
            categoryName.value = name;
            categoryDescription.value = description;
            
            // Form başlık ve buton metnini güncelle
            formTitle.textContent = 'Kategori Düzenle';
            formSubmitText.textContent = 'Güncelle';
            
            // Action type'ı update olarak ayarla
            actionType.name = 'update_category';
            
            // Formu göster
            categoryFormContainer.classList.remove('hidden');
            categoryName.focus();
            
            // Sayfayı form alanına kaydır
            window.scrollTo({
                top: categoryFormContainer.offsetTop - 20,
                behavior: 'smooth'
            });
        });
    });
    
    // Yeni kategori ekleme butonuna tıklama
    addCategoryBtn.addEventListener('click', function() {
        // Form alanlarını temizle
        categoryForm.reset();
        categoryId.value = '';
        
        // Form başlık ve buton metnini güncelle
        formTitle.textContent = 'Yeni Kategori Ekle';
        formSubmitText.textContent = 'Kategori Ekle';
        
        // Action type'ı add olarak ayarla
        actionType.name = 'add_category';
        
        // Formu göster
        categoryFormContainer.classList.remove('hidden');
        categoryName.focus();
        
        // Sayfayı form alanına kaydır
        window.scrollTo({
            top: categoryFormContainer.offsetTop - 20,
            behavior: 'smooth'
        });
    });
    
    // Formu kapat butonları
    closeFormBtn.addEventListener('click', hideForm);
    cancelBtn.addEventListener('click', hideForm);
    
    function hideForm() {
        categoryFormContainer.classList.add('hidden');
    }
    
    // Form gönderilmeden önce doğrulama yap
    categoryForm.addEventListener('submit', function(e) {
        if (categoryName.value.trim() === '') {
            e.preventDefault();
            alert('Kategori adı gereklidir.');
            categoryName.focus();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
