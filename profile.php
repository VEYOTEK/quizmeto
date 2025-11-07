<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Kullanıcı bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit;
}

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // CSRF token kontrolü
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error_message = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Kullanıcı adı ve e-posta kontrolü
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->execute([$username, $email, $user_id]);
        $exists = $check_stmt->fetchColumn();
        
        if (empty($username) || empty($email)) {
            $error_message = "Kullanıcı adı ve e-posta gereklidir.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Geçerli bir e-posta adresi giriniz.";
        } elseif ($exists) {
            $error_message = "Bu kullanıcı adı veya e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Profil resmi yükleme işlemi
                $profile_image = $user['profile_image']; // Varsayılan olarak mevcut resim
                
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                        throw new Exception("Sadece JPEG, PNG veya GIF resim yükleyebilirsiniz.");
                    }
                    
                    if ($_FILES['profile_image']['size'] > $max_size) {
                        throw new Exception("Resim boyutu 2MB'den küçük olmalıdır.");
                    }
                    
                    $upload_dir = 'assets/uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $file_name = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $profile_image = $upload_path;
                    } else {
                        throw new Exception("Resim yüklenirken bir hata oluştu.");
                    }
                }
                
                // Temel bilgi güncellemesi
                $update_sql = "UPDATE users SET username = ?, email = ?, profile_image = ? WHERE id = ?";
                $update_params = [$username, $email, $profile_image, $user_id];
                
                // Şifre güncellemesi (isteğe bağlı)
                if (!empty($current_password) && !empty($new_password)) {
                    if (!password_verify($current_password, $user['password'])) {
                        throw new Exception("Mevcut şifreniz yanlış.");
                    }
                    
                    if (strlen($new_password) < 6) {
                        throw new Exception("Yeni şifre en az 6 karakter olmalıdır.");
                    }
                    
                    if ($new_password !== $confirm_password) {
                        throw new Exception("Yeni şifreler eşleşmiyor.");
                    }
                    
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE users SET username = ?, email = ?, profile_image = ?, password = ? WHERE id = ?";
                    $update_params = [$username, $email, $profile_image, $hashed_password, $user_id];
                }
                
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute($update_params);
                
                $pdo->commit();
                
                // Session bilgilerini güncelle
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['user_image'] = $profile_image;
                
                $success_message = "Profil bilgileriniz başarıyla güncellendi.";
                
                // Kullanıcı bilgilerini güncelle
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = $e->getMessage();
            }
        }
    }
}

// Kullanıcının istatistiklerini al
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT quiz_id) as total_quizzes,
        SUM(score) as total_score,
        MAX(score) as highest_score,
        AVG(score) as average_score
    FROM user_scores 
    WHERE user_id = ?
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();

// Kullanıcının en son tamamladığı quizleri al
$recent_quizzes_stmt = $pdo->prepare("
    SELECT us.*, q.title, q.difficulty, c.name as category_name
    FROM user_scores us
    JOIN quizzes q ON us.quiz_id = q.id
    LEFT JOIN categories c ON q.category_id = c.id
    WHERE us.user_id = ? 
    ORDER BY us.completed_at DESC
    LIMIT 5
");
$recent_quizzes_stmt->execute([$user_id]);
$recent_quizzes = $recent_quizzes_stmt->fetchAll();

// Kategori bazlı performans
$category_stats_stmt = $pdo->prepare("
    SELECT c.name as category_name, 
           COUNT(us.id) as quiz_count, 
           AVG(us.score) as avg_score
    FROM user_scores us
    JOIN quizzes q ON us.quiz_id = q.id
    JOIN categories c ON q.category_id = c.id
    WHERE us.user_id = ?
    GROUP BY c.id
    ORDER BY avg_score DESC
");
$category_stats_stmt->execute([$user_id]);
$category_stats = $category_stats_stmt->fetchAll();

$pageTitle = "Profilim - QuizMeto";
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Profil Başlık -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-800 rounded-3xl p-8 md:p-12 mb-10 text-white relative overflow-hidden shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
            <div class="flex items-center mb-6 md:mb-0">
                <div class="relative">
                    <div class="w-24 h-24 md:w-32 md:h-32 rounded-full bg-white/20 p-1">
                        <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                             alt="Profile" 
                             class="w-full h-full object-cover rounded-full">
                    </div>
                    <div class="absolute -bottom-2 -right-2 bg-green-500 text-white rounded-full w-8 h-8 flex items-center justify-center border-2 border-white">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="ml-6">
                    <h1 class="text-3xl md:text-4xl font-bold"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="text-white/80 mt-1">Üyelik Tarihi: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="flex space-x-3">
                <button id="edit-profile-btn" class="px-5 py-2 bg-white text-primary-600 rounded-lg font-medium hover:bg-gray-100 transition-colors">
                    <i class="fas fa-user-edit mr-2"></i> Profili Düzenle
                </button>
            </div>
        </div>
        
        <div class="absolute -right-10 -bottom-10 opacity-20">
            <svg class="w-48 h-48" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm4.17-9.83l-4.6 4.6-2.83-2.83-1.41 1.41 4.24 4.24 6.01-6.01-1.41-1.41z"/>
            </svg>
        </div>
    </div>
    
    <?php if (!empty($success_message)): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $success_message; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $error_message; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sol Sütun - Kullanıcı Bilgileri ve İstatistikleri -->
        <div class="lg:col-span-1">
            <!-- Kullanıcı Bilgileri -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900">Kullanıcı Bilgileri</h3>
                </div>
                
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm text-gray-500 mb-1">Kullanıcı Adı</div>
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-500 mb-1">E-posta</div>
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-500 mb-1">Üyelik Tarihi</div>
                            <div class="font-medium text-gray-900"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-500 mb-1">Rol</div>
                            <div class="font-medium text-gray-900">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Yönetici
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Kullanıcı
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- İstatistikler -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900">İstatistikler</h3>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="text-3xl font-bold text-primary-600"><?php echo number_format($stats['total_quizzes'] ?? 0); ?></div>
                            <div class="text-sm text-gray-500">Tamamlanan Quiz</div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="text-3xl font-bold text-primary-600"><?php echo number_format($stats['total_score'] ?? 0); ?></div>
                            <div class="text-sm text-gray-500">Toplam Puan</div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="text-3xl font-bold text-primary-600"><?php echo number_format($stats['highest_score'] ?? 0); ?></div>
                            <div class="text-sm text-gray-500">En Yüksek Puan</div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <div class="text-3xl font-bold text-primary-600"><?php echo number_format($stats['average_score'] ?? 0, 1); ?></div>
                            <div class="text-sm text-gray-500">Ortalama Puan</div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <a href="leaderboard.php" class="w-full inline-block text-center text-primary-600 hover:text-primary-700 font-medium">
                            Liderlik Tablosunu Görüntüle <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Kategori Bazlı Performans -->
            <?php if (!empty($category_stats)): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900">Kategori Bazlı Performans</h3>
                </div>
                
                <div class="p-6">
                    <ul class="space-y-4">
                        <?php foreach ($category_stats as $index => $cat): ?>
                            <li>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                                    <span class="text-sm text-gray-500"><?php echo round($cat['avg_score']); ?> / <?php echo $cat['quiz_count']; ?> quiz</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full" style="width: <?php echo min(100, round($cat['avg_score'])); ?>%"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sağ Sütun - Profil Düzenleme ve Quiz Geçmişi -->
        <div class="lg:col-span-2">
            <!-- Profil Düzenleme Formu -->
            <div id="edit-profile-form" class="bg-white rounded-xl shadow-lg overflow-hidden mb-8" style="display: none;">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900">Profili Düzenle</h3>
                </div>
                
                <form action="profile.php" method="post" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="mb-6">
                        <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-1">Profil Resmi</label>
                        <div class="flex items-center">
                            <div class="w-16 h-16 rounded-full overflow-hidden mr-4">
                                <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                     alt="Current profile" 
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="flex-grow">
                                <input type="file" id="profile_image" name="profile_image" accept="image/*" 
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                <p class="mt-1 text-xs text-gray-500">PNG, JPG veya GIF. Maksimum 2MB.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Kullanıcı Adı</label>
                            <input type="text" id="username" name="username" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                   value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-posta Adresi</label>
                            <input type="email" id="email" name="email" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-2">Şifre Değiştir (isteğe bağlı)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mevcut Şifre</label>
                                <input type="password" id="current_password" name="current_password" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Yeni Şifre</label>
                                <input type="password" id="new_password" name="new_password" minlength="6" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Yeni Şifre (Tekrar)</label>
                                <input type="password" id="confirm_password" name="confirm_password" minlength="6" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" id="cancel-edit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            İptal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            Değişiklikleri Kaydet
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Son Tamamlanan Quizler -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900">Son Tamamlanan Quizler</h3>
                </div>
                
                <?php if (empty($recent_quizzes)): ?>
                    <div class="p-6 text-center">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-clipboard-list text-5xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Henüz quiz tamamlamadınız</h3>
                        <p class="text-gray-600 mb-4">Quizleri tamamladıkça burada listelenecek.</p>
                        <a href="quizzes.php" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors">
                            <i class="fas fa-play mr-2"></i> Quizlere Başla
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skor</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Süre</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_quizzes as $quiz): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                      <?php echo $quiz['difficulty'] === 'kolay' ? 'bg-green-100 text-green-800' : 
                                                              ($quiz['difficulty'] === 'orta' ? 'bg-yellow-100 text-yellow-800' : 
                                                              'bg-red-100 text-red-800'); ?>">
                                                    <?php echo ucfirst($quiz['difficulty']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($quiz['category_name'] ?? 'Kategorisiz'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-primary-600"><?php echo $quiz['score']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo format_time($quiz['completion_time']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?php echo date('d.m.Y H:i', strtotime($quiz['completed_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <a href="quiz-result.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="text-primary-600 hover:text-primary-900">
                                                Detay <i class="fas fa-arrow-right ml-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        <a href="history.php" class="inline-block text-primary-600 hover:text-primary-700 font-medium">
                            Tüm Quiz Geçmişini Görüntüle <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Rozetler ve Başarılar (Gelecekte Eklenebilir) -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900">Rozetler ve Başarılar</h3>
                </div>
                
                <div class="p-6">
                    <div class="text-center p-10">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-medal text-5xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Yakında Geliyor!</h3>
                        <p class="text-gray-600">Rozetler ve başarılar çok yakında burada görünecek.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editProfileBtn = document.getElementById('edit-profile-btn');
        const editProfileForm = document.getElementById('edit-profile-form');
        const cancelEditBtn = document.getElementById('cancel-edit');
        
        // Profili düzenle butonuna tıklama
        editProfileBtn.addEventListener('click', function() {
            editProfileForm.style.display = 'block';
            window.scrollTo({
                top: editProfileForm.offsetTop - 20,
                behavior: 'smooth'
            });
        });
        
        // İptal butonuna tıklama
        cancelEditBtn.addEventListener('click', function() {
            editProfileForm.style.display = 'none';
        });
        
        // Sayfa yüklendiğinde formun görünürlüğünü kontrol et
        if (window.location.hash === '#edit') {
            editProfileForm.style.display = 'block';
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
