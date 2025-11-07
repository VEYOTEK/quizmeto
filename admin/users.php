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

// Kullanıcı silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Admin kullanıcıyı silmeye çalışıyorsa engelle
    if ($user_id === (int)$_SESSION['user_id']) {
        $_SESSION['admin_error'] = "Kendinizi silemezsiniz.";
    } else {
        // CSRF token kontrolü
        if (!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
            $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Kullanıcının skorlarını sil
                $stmt = $pdo->prepare("DELETE FROM user_scores WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Kullanıcıyı sil
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                $_SESSION['admin_success'] = "Kullanıcı başarıyla silindi.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['admin_error'] = "Kullanıcı silinirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
    
    // Yönlendirme ile sayfayı yenile
    header("Location: users.php");
    exit;
}

// Yeni kullanıcı ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = sanitize_input($_POST['role']);
        
        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['admin_error'] = "Kullanıcı adı, e-posta ve şifre alanları gereklidir.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['admin_error'] = "Geçerli bir e-posta adresi giriniz.";
        } elseif (strlen($password) < 6) {
            $_SESSION['admin_error'] = "Şifre en az 6 karakter olmalıdır.";
        } elseif ($password !== $confirm_password) {
            $_SESSION['admin_error'] = "Şifreler eşleşmiyor.";
        } elseif (!in_array($role, ['user', 'admin'])) {
            $_SESSION['admin_error'] = "Geçersiz kullanıcı rolü.";
        } else {
            try {
                // Kullanıcı adı ve e-posta kontrolü
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $check_stmt->execute([$username, $email]);
                $exists = $check_stmt->fetchColumn();
                
                if ($exists) {
                    $_SESSION['admin_error'] = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Kullanıcıyı ekle
                    $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $insert_stmt->execute([$username, $email, $hashed_password, $role]);
                    
                    $_SESSION['admin_success'] = "Kullanıcı başarıyla eklendi.";
                    // Formun yeniden gönderilmesini önlemek için yönlendirme yap
                    header("Location: users.php");
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['admin_error'] = "Kullanıcı eklenirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

// Kullanıcı düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $user_id = (int)$_POST['user_id'];
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $role = sanitize_input($_POST['role']);
        $new_password = $_POST['new_password'];
        
        if (empty($username) || empty($email)) {
            $_SESSION['admin_error'] = "Kullanıcı adı ve e-posta alanları gereklidir.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['admin_error'] = "Geçerli bir e-posta adresi giriniz.";
        } elseif (!in_array($role, ['user', 'admin'])) {
            $_SESSION['admin_error'] = "Geçersiz kullanıcı rolü.";
        } else {
            try {
                // Kullanıcı adı ve e-posta kontrolü (kendisi hariç)
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $check_stmt->execute([$username, $email, $user_id]);
                $exists = $check_stmt->fetchColumn();
                
                if ($exists) {
                    $_SESSION['admin_error'] = "Bu kullanıcı adı veya e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
                } else {
                    // Temel bilgi güncellemesi
                    $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
                    $params = [$username, $email, $role, $user_id];
                    
                    // Şifre güncellemesi (isteğe bağlı)
                    if (!empty($new_password)) {
                        if (strlen($new_password) < 6) {
                            throw new Exception("Şifre en az 6 karakter olmalıdır.");
                        }
                        
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?";
                        $params = [$username, $email, $role, $hashed_password, $user_id];
                    }
                    
                    $update_stmt = $pdo->prepare($sql);
                    $update_stmt->execute($params);
                    
                    // Eğer admin kendi hesabını güncelliyorsa session bilgilerini de güncelle
                    if ($user_id === (int)$_SESSION['user_id']) {
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $role;
                    }
                    
                    $_SESSION['admin_success'] = "Kullanıcı bilgileri başarıyla güncellendi.";
                    // Formun yeniden gönderilmesini önlemek için yönlendirme yap
                    header("Location: users.php");
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['admin_error'] = "Kullanıcı güncellenirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

// Kullanıcıları listeleme ve filtreleme
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'id';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'desc' ? 'DESC' : 'ASC';

// Arama ve filtreleme koşulları
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter) && in_array($role_filter, ['user', 'admin'])) {
    $conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

// Sıralama kontrolü
$allowed_sort_fields = ['id', 'username', 'email', 'role', 'created_at', 'quiz_count'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'id';
}

// Kullanıcıların toplam sayısını al
$count_sql = "SELECT COUNT(*) FROM users $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();

// Kullanıcı sayfalama ve sıralama
$sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM user_scores WHERE user_id = u.id) as quiz_count
        FROM users u
        $where_clause 
        ORDER BY " . ($sort_by === 'quiz_count' ? 'quiz_count' : "u.$sort_by") . " $sort_dir 
        LIMIT $offset, $per_page";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Sayfalama
$total_pages = ceil($total_users / $per_page);

// Kullanıcı istatistikleri
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'admin_count' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
    'user_count' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'active_today' => $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_scores WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn(),
    'most_active' => null
];

// En aktif kullanıcıyı bul (en çok quiz çözen)
$most_active_query = $pdo->query("
    SELECT u.id, u.username, COUNT(us.id) as completed_quizzes
    FROM users u
    JOIN user_scores us ON u.id = us.user_id
    GROUP BY u.id
    ORDER BY completed_quizzes DESC
    LIMIT 1
");
$stats['most_active'] = $most_active_query->fetch();

$pageTitle = "Kullanıcı Yönetimi - Admin Panel";
include './includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Kullanıcı Yönetimi</h1>
        <button id="add-user-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
            <i class="fas fa-user-plus mr-2"></i> Yeni Kullanıcı Ekle
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
    
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Kullanıcı Listesi (3 sütun genişliğinde) -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Arama ve Filtreleme -->
                <div class="p-4 bg-gray-50 border-b border-gray-200">
                    <form action="users.php" method="get" class="flex flex-col md:flex-row gap-4">
                        <div class="flex-grow">
                            <label for="search" class="sr-only">Ara</label>
                            <div class="relative">
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kullanıcı ara..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Tüm Roller</option>
                                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Kullanıcı</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Yönetici</option>
                            </select>
                            
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                                <i class="fas fa-filter mr-1"></i> Filtrele
                            </button>
                            
                            <a href="users.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-sync-alt mr-1"></i> Sıfırla
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Kullanıcı Tablosu -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=id&dir=<?php echo $sort_by === 'id' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="flex items-center">
                                        ID
                                        <?php if ($sort_by === 'id'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=username&dir=<?php echo $sort_by === 'username' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="flex items-center">
                                        Kullanıcı
                                        <?php if ($sort_by === 'username'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=email&dir=<?php echo $sort_by === 'email' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="flex items-center">
                                        E-posta
                                        <?php if ($sort_by === 'email'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=role&dir=<?php echo $sort_by === 'role' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="flex items-center">
                                        Rol
                                        <?php if ($sort_by === 'role'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=quiz_count&dir=<?php echo $sort_by === 'quiz_count' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="flex items-center">
                                        Quizler
                                        <?php if ($sort_by === 'quiz_count'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?sort=created_at&dir=<?php echo $sort_by === 'created_at' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="flex items-center">
                                        Kayıt Tarihi
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
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-gray-400 mb-4">
                                            <i class="fas fa-users text-5xl"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">Kullanıcı bulunamadı</h3>
                                        <p class="text-gray-500">Arama kriterlerinize uygun kullanıcı bulunamadı.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $user['id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover" 
                                                         src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../assets/images/default-avatar.png'; ?>" 
                                                         alt="<?php echo htmlspecialchars($user['username']); ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($user['username']); ?>
                                                        <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                                            <span class="ml-1 text-xs text-primary-600">(Siz)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                  <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                <?php echo $user['role'] === 'admin' ? 'Yönetici' : 'Kullanıcı'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $user['quiz_count']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button type="button" class="edit-user-btn text-blue-600 hover:text-blue-900" title="Düzenle" 
                                                        data-id="<?php echo $user['id']; ?>" 
                                                        data-username="<?php echo htmlspecialchars($user['username']); ?>" 
                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>" 
                                                        data-role="<?php echo $user['role']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <a href="users.php?delete=<?php echo $user['id']; ?>&token=<?php echo $csrf_token; ?>" 
                                                       class="text-red-600 hover:text-red-900" 
                                                       title="Sil"
                                                       onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 cursor-not-allowed" title="Kendinizi silemezsiniz">
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
                
                <!-- Sayfalama -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Önceki</span>
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<a href="?page=1&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($role_filter) ? '&role=' . urlencode($role_filter) : '') . '" 
                                                 class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        echo '<a href="?page=' . $i . '&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($role_filter) ? '&role=' . urlencode($role_filter) : '') . '" 
                                                 class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium ' . ($i == $page ? 'bg-primary-50 text-primary-600 z-10' : 'text-gray-700 hover:bg-gray-50') . '">' . $i . '</a>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                        echo '<a href="?page=' . $total_pages . '&sort=' . $sort_by . '&dir=' . $sort_dir . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($role_filter) ? '&role=' . urlencode($role_filter) : '') . '" 
                                                 class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort_by; ?>&dir=<?php echo $sort_dir; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" 
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
            <!-- Kullanıcı Formları -->
            <div id="user-form-container" class="bg-white rounded-xl shadow-lg p-6 mb-6 hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="form-title" class="text-lg font-bold text-gray-900">Yeni Kullanıcı Ekle</h3>
                    <button type="button" id="close-form" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="user-form" action="users.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="action-type" name="add_user" value="1">
                    <input type="hidden" id="user-id" name="user_id" value="">
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Kullanıcı Adı <span class="text-red-500">*</span></label>
                            <input type="text" id="username" name="username" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-posta <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                            <select id="role" name="role" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="user">Kullanıcı</option>
                                <option value="admin">Yönetici</option>
                            </select>
                        </div>
                        
                        <div id="password-container">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Şifre <span class="text-red-500">*</span></label>
                            <input type="password" id="password" name="password" minlength="6"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <p class="mt-1 text-xs text-gray-500">En az 6 karakter olmalıdır.</p>
                        </div>
                        
                        <div id="confirm-password-container">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Şifre (Tekrar) <span class="text-red-500">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="6"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div id="new-password-container" class="hidden">
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Yeni Şifre (İsteğe Bağlı)</label>
                            <input type="password" id="new_password" name="new_password" minlength="6"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <p class="mt-1 text-xs text-gray-500">Değiştirmek istemiyorsanız boş bırakın.</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" id="cancel-btn" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            İptal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <span id="form-submit-text">Kullanıcı Ekle</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Kullanıcı İstatistikleri -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Kullanıcı İstatistikleri</h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Toplam Kullanıcı</span>
                        <span class="text-lg font-bold text-primary-600"><?php echo $stats['total_users']; ?></span>
                    </div>
                    
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-primary-600 h-2.5 rounded-full" style="width: 100%"></div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-500">Yönetici</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo $stats['admin_count']; ?></div>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-500">Kullanıcı</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo $stats['user_count']; ?></div>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-500">Bugün Aktif</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo $stats['active_today']; ?></div>
                        </div>
                        
                        <?php if ($stats['most_active']): ?>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <div class="text-sm text-gray-500">En Aktif Kullanıcı</div>
                                <div class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($stats['most_active']['username']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo $stats['most_active']['completed_quizzes']; ?> quiz</div>
                            </div>
                        <?php endif; ?>
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
                    
                    <a href="categories.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Kategorileri Yönet</div>
                            <div class="text-xs text-gray-500">Kategorileri düzenle</div>
                        </div>
                    </a>
                    
                    <a href="reports.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-3">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Raporlar</div>
                            <div class="text-xs text-gray-500">İstatistikleri görüntüle</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addUserBtn = document.getElementById('add-user-btn');
    const userFormContainer = document.getElementById('user-form-container');
    const closeFormBtn = document.getElementById('close-form');
    const cancelBtn = document.getElementById('cancel-btn');
    const userForm = document.getElementById('user-form');
    const formTitle = document.getElementById('form-title');
    const formSubmitText = document.getElementById('form-submit-text');
    const actionType = document.getElementById('action-type');
    const userId = document.getElementById('user-id');
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const role = document.getElementById('role');
    const passwordContainer = document.getElementById('password-container');
    const confirmPasswordContainer = document.getElementById('confirm-password-container');
    const newPasswordContainer = document.getElementById('new-password-container');
    
    // Kullanıcı düzenleme butonlarına event listener ekle
    const editButtons = document.querySelectorAll('.edit-user-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const usernameValue = this.dataset.username;
            const emailValue = this.dataset.email;
            const roleValue = this.dataset.role;
            
            // Form alanlarını doldur
            userId.value = id;
            username.value = usernameValue;
            email.value = emailValue;
            role.value = roleValue;
            
            // Form başlık ve buton metnini güncelle
            formTitle.textContent = 'Kullanıcı Düzenle';
            formSubmitText.textContent = 'Güncelle';
            
            // Action type'ı update olarak ayarla
            actionType.name = 'update_user';
            
            // Şifre alanlarını ayarla
            passwordContainer.classList.add('hidden');
            confirmPasswordContainer.classList.add('hidden');
            newPasswordContainer.classList.remove('hidden');
            
            // Formu göster
            userFormContainer.classList.remove('hidden');
            username.focus();
            
            // Sayfayı form alanına kaydır
            window.scrollTo({
                top: userFormContainer.offsetTop - 20,
                behavior: 'smooth'
            });
        });
    });
    
    // Yeni kullanıcı ekleme butonuna tıklama
    addUserBtn.addEventListener('click', function() {
        // Form alanlarını temizle
        userForm.reset();
        userId.value = '';
        
        // Form başlık ve buton metnini güncelle
        formTitle.textContent = 'Yeni Kullanıcı Ekle';
        formSubmitText.textContent = 'Kullanıcı Ekle';
        
        // Action type'ı add olarak ayarla
        actionType.name = 'add_user';
        
        // Şifre alanlarını ayarla
        passwordContainer.classList.remove('hidden');
        confirmPasswordContainer.classList.remove('hidden');
        newPasswordContainer.classList.add('hidden');
        
        // Formu göster
        userFormContainer.classList.remove('hidden');
        username.focus();
        
        // Sayfayı form alanına kaydır
        window.scrollTo({
            top: userFormContainer.offsetTop - 20,
            behavior: 'smooth'
        });
    });
    
    // Formu kapat butonları
    closeFormBtn.addEventListener('click', hideForm);
    cancelBtn.addEventListener('click', hideForm);
    
    function hideForm() {
        userFormContainer.classList.add('hidden');
    }
    
    // Form gönderilmeden önce doğrulama yap
    userForm.addEventListener('submit', function(e) {
        const isNewUser = actionType.name === 'add_user';
        
        if (username.value.trim() === '' || email.value.trim() === '') {
            e.preventDefault();
            alert('Kullanıcı adı ve e-posta alanları gereklidir.');
            return;
        }
        
        if (isNewUser) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password.value.trim() === '') {
                e.preventDefault();
                alert('Şifre alanı gereklidir.');
                return;
            }
            
            if (password.value.length < 6) {
                e.preventDefault();
                alert('Şifre en az 6 karakter olmalıdır.');
                return;
            }
            
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor.');
                return;
            }
        } else {
            const newPassword = document.getElementById('new_password');
            
            if (newPassword.value.trim() !== '' && newPassword.value.length < 6) {
                e.preventDefault();
                alert('Yeni şifre en az 6 karakter olmalıdır.');
                return;
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
