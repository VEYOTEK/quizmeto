<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Hata ayıklama
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Admin yetkisi kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Settings tablosunun varlığını kontrol et ve gerekirse oluştur
try {
    $tableExists = $pdo->query("SHOW TABLES LIKE 'settings'")->rowCount() > 0;
    if (!$tableExists) {
        $pdo->exec("
            CREATE TABLE settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(255) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Varsayılan ayarları ekle
        $defaultSettings = [
            ['site_title', 'Quiz Meto'],
            ['site_description', 'Online Test ve Quiz Platformu'],
            ['items_per_page', '12'],
            ['enable_registration', '1'],
            ['enable_leaderboard', '1'],
            ['footer_text', '© ' . date('Y') . ' Quiz Meto. Tüm hakları saklıdır.'],
            ['primary_color', '#4F46E5'],
            ['enable_dark_mode', '0'],
            ['maintenance_mode', '0']
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaultSettings as $setting) {
            $insertStmt->execute($setting);
        }
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Mevcut ayarları veritabanından al
function get_settings() {
    global $pdo;
    
    try {
        $settings = [];
        $stmt = $pdo->query("SELECT * FROM settings");
        
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log("Ayarları alırken hata: " . $e->getMessage());
        return [];
    }
}

// Tek bir ayarı güncelle
function update_setting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
        return true;
    } catch (PDOException $e) {
        error_log("Ayar güncellenirken hata: " . $e->getMessage());
        return false;
    }
}

// Mevcut ayarları al
$settings = get_settings();
$csrf_token = generate_csrf_token();

// Ayarlar içinde bulunmuyorsa varsayılan değerleri kullan
$site_title = $settings['site_title'] ?? 'Quiz Meto';
$site_description = $settings['site_description'] ?? 'Online Test ve Quiz Platformu';
$items_per_page = $settings['items_per_page'] ?? '12';
$enable_registration = $settings['enable_registration'] ?? '1';
$enable_leaderboard = $settings['enable_leaderboard'] ?? '1';
$footer_text = $settings['footer_text'] ?? '© ' . date('Y') . ' Quiz Meto. Tüm hakları saklıdır.';
$primary_color = $settings['primary_color'] ?? '#4F46E5';
$facebook_url = $settings['facebook_url'] ?? '';
$twitter_url = $settings['twitter_url'] ?? '';
$instagram_url = $settings['instagram_url'] ?? '';
$youtube_url = $settings['youtube_url'] ?? '';
$contact_email = $settings['contact_email'] ?? '';
$enable_dark_mode = $settings['enable_dark_mode'] ?? '0';
$maintenance_mode = $settings['maintenance_mode'] ?? '0';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // CSRF token kontrolü
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['admin_error'] = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        try {
            // Genel Ayarlar
            update_setting('site_title', sanitize_input($_POST['site_title']));
            update_setting('site_description', sanitize_input($_POST['site_description']));
            update_setting('items_per_page', (int)$_POST['items_per_page']);
            update_setting('enable_registration', isset($_POST['enable_registration']) ? '1' : '0');
            update_setting('enable_leaderboard', isset($_POST['enable_leaderboard']) ? '1' : '0');
            update_setting('footer_text', sanitize_input($_POST['footer_text']));
            
            // Görünüm Ayarları
            update_setting('primary_color', sanitize_input($_POST['primary_color']));
            update_setting('enable_dark_mode', isset($_POST['enable_dark_mode']) ? '1' : '0');
            
            // Sosyal Medya
            update_setting('facebook_url', sanitize_input($_POST['facebook_url']));
            update_setting('twitter_url', sanitize_input($_POST['twitter_url']));
            update_setting('instagram_url', sanitize_input($_POST['instagram_url']));
            update_setting('youtube_url', sanitize_input($_POST['youtube_url']));
            
            // İletişim ve Diğer
            update_setting('contact_email', sanitize_input($_POST['contact_email']));
            update_setting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
            
            // Logo yükleme işlemi (eğer dosya seçildiyse)
            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === 0) {
                $upload_dir = '../assets/images/';
                
                // Upload dizininin varlığını kontrol et, yoksa oluştur
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = 'site-logo-' . time() . '.png';
                $upload_path = $upload_dir . $file_name;
                
                // Resim doğrulama
                $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
                $file_type = $_FILES['site_logo']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception("Yalnızca PNG, JPEG ve GIF formatındaki resimler yüklenebilir.");
                }
                
                if (!move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_path)) {
                    throw new Exception("Logo yüklenirken bir hata oluştu.");
                }
                
                // Logo path'ini veritabanına kaydet
                update_setting('site_logo', 'assets/images/' . $file_name);
            }
            
            // Favicon yükleme işlemi (eğer dosya seçildiyse)
            if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
                $upload_dir = '../assets/images/';
                
                // Upload dizininin varlığını kontrol et, yoksa oluştur
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = 'favicon-' . time() . '.ico';
                $upload_path = $upload_dir . $file_name;
                
                // Favicon doğrulama
                $allowed_types = ['image/x-icon', 'image/png', 'image/jpeg', 'image/jpg'];
                $file_type = $_FILES['favicon']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception("Yalnızca ICO, PNG ve JPEG formatındaki resimler yüklenebilir.");
                }
                
                if (!move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_path)) {
                    throw new Exception("Favicon yüklenirken bir hata oluştu.");
                }
                
                // Favicon path'ini veritabanına kaydet
                update_setting('favicon', 'assets/images/' . $file_name);
            }
            
            $_SESSION['admin_success'] = "Ayarlar başarıyla güncellendi.";
            
            // Ayarları yeniden yükle
            $settings = get_settings();
            $site_title = $settings['site_title'] ?? 'Quiz Meto';
            $site_description = $settings['site_description'] ?? 'Online Test ve Quiz Platformu';
            $items_per_page = $settings['items_per_page'] ?? '12';
            $enable_registration = $settings['enable_registration'] ?? '1';
            $enable_leaderboard = $settings['enable_leaderboard'] ?? '1';
            $footer_text = $settings['footer_text'] ?? '© ' . date('Y') . ' Quiz Meto. Tüm hakları saklıdır.';
            $primary_color = $settings['primary_color'] ?? '#4F46E5';
            $facebook_url = $settings['facebook_url'] ?? '';
            $twitter_url = $settings['twitter_url'] ?? '';
            $instagram_url = $settings['instagram_url'] ?? '';
            $youtube_url = $settings['youtube_url'] ?? '';
            $contact_email = $settings['contact_email'] ?? '';
            $enable_dark_mode = $settings['enable_dark_mode'] ?? '0';
            $maintenance_mode = $settings['maintenance_mode'] ?? '0';
            
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Ayarlar güncellenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}

$pageTitle = "Site Ayarları - Admin Panel";
include './includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Site Ayarları</h1>
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
    
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <form action="settings.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="save_settings" value="1">
            
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="mr-2">
                        <a href="#general" class="settings-tab inline-block p-4 border-b-2 border-primary-500 rounded-t-lg active text-primary-600" aria-current="page">
                            <i class="fas fa-cogs mr-2"></i> Genel
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="#appearance" class="settings-tab inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300">
                            <i class="fas fa-paint-brush mr-2"></i> Görünüm
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="#social" class="settings-tab inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300">
                            <i class="fas fa-share-alt mr-2"></i> Sosyal Medya
                        </a>
                    </li>
                    <li>
                        <a href="#other" class="settings-tab inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300">
                            <i class="fas fa-sliders-h mr-2"></i> Diğer
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Genel Ayarlar -->
            <div id="general-tab" class="settings-tab-content p-6 md:p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-5">Genel Ayarlar</h2>
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="site_title" class="block text-sm font-medium text-gray-700 mb-1">Site Başlığı</label>
                        <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($site_title); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Sitenizin tarayıcıda görünen başlığı.</p>
                    </div>
                    
                    <div>
                        <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">Site Açıklaması</label>
                        <textarea id="site_description" name="site_description" rows="2" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"><?php echo htmlspecialchars($site_description); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Meta açıklaması ve arama sonuçlarında görünecek kısa açıklama.</p>
                    </div>
                    
                    <div>
                        <label for="site_logo" class="block text-sm font-medium text-gray-700 mb-1">Site Logosu</label>
                        <div class="flex items-center">
                            <?php if (!empty($settings['site_logo'])): ?>
                                <div class="mr-4">
                                    <img src="../<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Site Logo" class="h-12 object-contain">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="site_logo" name="site_logo" accept="image/*" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Önerilen boyut: 200x60 piksel. PNG formatı tercih edilir.</p>
                    </div>
                    
                    <div>
                        <label for="favicon" class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                        <div class="flex items-center">
                            <?php if (!empty($settings['favicon'])): ?>
                                <div class="mr-4">
                                    <img src="../<?php echo htmlspecialchars($settings['favicon']); ?>" alt="Favicon" class="h-8 w-8 object-contain">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="favicon" name="favicon" accept=".ico,image/*" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Tarayıcı sekmelerinde görünen küçük ikon. Önerilen boyut: 32x32 piksel.</p>
                    </div>
                    
                    <div>
                        <label for="items_per_page" class="block text-sm font-medium text-gray-700 mb-1">Sayfa Başına Öğe Sayısı</label>
                        <input type="number" id="items_per_page" name="items_per_page" value="<?php echo htmlspecialchars($items_per_page); ?>" min="4" max="100" 
                               class="w-full md:w-1/4 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Quiz listeleme sayfasında görüntülenecek öğe sayısı.</p>
                    </div>
                    
                    <div>
                        <label for="footer_text" class="block text-sm font-medium text-gray-700 mb-1">Altbilgi Metni</label>
                        <textarea id="footer_text" name="footer_text" rows="2" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"><?php echo htmlspecialchars($footer_text); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Sayfanın altında görünen telif hakkı veya bilgi metni. HTML kullanabilirsiniz.</p>
                    </div>
                    
                    <div>
                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="enable_registration" name="enable_registration" <?php echo $enable_registration == '1' ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="enable_registration" class="font-medium text-gray-700">Kullanıcı Kaydını Etkinleştir</label>
                                <p class="text-gray-500">Yeni kullanıcı kayıtlarına izin ver.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="enable_leaderboard" name="enable_leaderboard" <?php echo $enable_leaderboard == '1' ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="enable_leaderboard" class="font-medium text-gray-700">Liderlik Tablosunu Etkinleştir</label>
                                <p class="text-gray-500">Kullanıcıların skorlarını ve sıralamalarını görüntüle.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Görünüm Ayarları -->
            <div id="appearance-tab" class="settings-tab-content p-6 md:p-8 hidden">
                <h2 class="text-xl font-bold text-gray-800 mb-5">Görünüm Ayarları</h2>
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-1">Ana Renk</label>
                        <div class="flex items-center">
                            <input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($primary_color); ?>" 
                                   class="h-10 w-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" id="primary_color_text" value="<?php echo htmlspecialchars($primary_color); ?>" 
                                   class="ml-3 w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Sitenin ana rengi (butonlar, vurgular, vb.)</p>
                    </div>
                    
                    <div>
                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="enable_dark_mode" name="enable_dark_mode" <?php echo $enable_dark_mode == '1' ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="enable_dark_mode" class="font-medium text-gray-700">Karanlık Modu Etkinleştir</label>
                                <p class="text-gray-500">Kullanıcılara karanlık tema seçeneği sun.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Tema Önizlemesi</h3>
                        <div class="flex items-center space-x-4 mb-4">
                            <button type="button" id="preview-light" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                                <i class="fas fa-sun mr-2"></i> Açık Tema
                            </button>
                            <button type="button" id="preview-dark" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition-colors">
                                <i class="fas fa-moon mr-2"></i> Koyu Tema
                            </button>
                        </div>
                        
                        <div id="theme-preview" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Tema Önizlemesi</h4>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">Sitenizin görünümü bu şekilde olacaktır.</p>
                            
                            <div class="flex gap-2 mb-4">
                                <button type="button" class="primary-btn px-4 py-2 rounded-lg text-white">
                                    Ana Buton
                                </button>
                                <button type="button" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg">
                                    İkincil Buton
                                </button>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Örnek Form Alanı</label>
                                <input type="text" value="Form girdisi örneği" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
                            </div>
                            
                            <div class="flex items-center">
                                <div class="w-4 h-4 primary-bg rounded-full mr-2"></div>
                                <span class="text-gray-800 dark:text-gray-200">Ana renginiz burada kullanılır</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sosyal Medya Ayarları -->
            <div id="social-tab" class="settings-tab-content p-6 md:p-8 hidden">
                <h2 class="text-xl font-bold text-gray-800 mb-5">Sosyal Medya Ayarları</h2>
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-4">Sosyal medya bağlantılarınızı ekleyin. Boş bıraktığınız alanlar sitede gösterilmeyecektir.</label>
                    </div>
                    
                    <div>
                        <label for="facebook_url" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fab fa-facebook text-blue-600 mr-2"></i> Facebook URL
                        </label>
                        <input type="url" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($facebook_url); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="twitter_url" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fab fa-twitter text-blue-400 mr-2"></i> Twitter URL
                        </label>
                        <input type="url" id="twitter_url" name="twitter_url" value="<?php echo htmlspecialchars($twitter_url); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="instagram_url" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fab fa-instagram text-pink-600 mr-2"></i> Instagram URL
                        </label>
                        <input type="url" id="instagram_url" name="instagram_url" value="<?php echo htmlspecialchars($instagram_url); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fab fa-youtube text-red-600 mr-2"></i> YouTube URL
                        </label>
                        <input type="url" id="youtube_url" name="youtube_url" value="<?php echo htmlspecialchars($youtube_url); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Sosyal Medya Önizlemesi</h3>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex flex-wrap gap-3">
                                <a id="facebook-preview" href="#" class="text-2xl <?php echo !empty($facebook_url) ? 'text-blue-600' : 'text-gray-400'; ?>">
                                    <i class="fab fa-facebook-square"></i>
                                </a>
                                <a id="twitter-preview" href="#" class="text-2xl <?php echo !empty($twitter_url) ? 'text-blue-400' : 'text-gray-400'; ?>">
                                    <i class="fab fa-twitter-square"></i>
                                </a>
                                <a id="instagram-preview" href="#" class="text-2xl <?php echo !empty($instagram_url) ? 'text-pink-600' : 'text-gray-400'; ?>">
                                    <i class="fab fa-instagram-square"></i>
                                </a>
                                <a id="youtube-preview" href="#" class="text-2xl <?php echo !empty($youtube_url) ? 'text-red-600' : 'text-gray-400'; ?>">
                                    <i class="fab fa-youtube-square"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Diğer Ayarlar -->
            <div id="other-tab" class="settings-tab-content p-6 md:p-8 hidden">
                <h2 class="text-xl font-bold text-gray-800 mb-5">Diğer Ayarlar</h2>
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">İletişim E-postası</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($contact_email); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p class="mt-1 text-xs text-gray-500">İletişim formundan gelen mesajların gönderileceği e-posta adresi.</p>
                    </div>
                    
                    <div>
                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $maintenance_mode == '1' ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="maintenance_mode" class="font-medium text-gray-700">Bakım Modu</label>
                                <p class="text-gray-500">Siteyi bakım moduna alır. Sadece yöneticiler erişebilir.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Sistem Bilgileri</h3>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-500">PHP Versiyonu</div>
                                    <div class="text-sm text-gray-900"><?php echo phpversion(); ?></div>
                                </div>
                                
                                <div>
                                    <div class="text-sm font-medium text-gray-500">MySQL Versiyonu</div>
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                            $version = $pdo->query('select version()')->fetchColumn();
                                            echo $version;
                                        ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Toplam Quiz</div>
                                    <div class="text-sm text-gray-900">
                                        <?php
                                            $quiz_count = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
                                            echo number_format($quiz_count);
                                        ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Toplam Kullanıcı</div>
                                    <div class="text-sm text-gray-900">
                                        <?php
                                            $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                                            echo number_format($user_count);
                                        ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Sunucu</div>
                                    <div class="text-sm text-gray-900"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
                                </div>
                                
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Zaman Dilimi</div>
                                    <div class="text-sm text-gray-900"><?php echo date_default_timezone_get(); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Butonları -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i> Ayarları Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab değiştirme işlevselliği
    const tabLinks = document.querySelectorAll('.settings-tab');
    const tabContents = document.querySelectorAll('.settings-tab-content');
    
    tabLinks.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            const target = this.getAttribute('href').substring(1);
            
            // Tüm tab linklerini pasif yap
            tabLinks.forEach(link => {
                link.classList.remove('active', 'text-primary-600', 'border-primary-500');
                link.classList.add('hover:text-gray-600', 'hover:border-gray-300', 'border-transparent');
            });
            
            // Aktif tab linkini işaretle
            this.classList.add('active', 'text-primary-600', 'border-primary-500');
            this.classList.remove('hover:text-gray-600', 'hover:border-gray-300', 'border-transparent');
            
            // Tüm tab içeriklerini gizle
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // İlgili tab içeriğini göster
            document.getElementById(target + '-tab').classList.remove('hidden');
        });
    });
    
    // Renk ayarı senkronizasyonu
    const primaryColor = document.getElementById('primary_color');
    const primaryColorText = document.getElementById('primary_color_text');
    const themePreview = document.getElementById('theme-preview');
    const primaryButtons = document.querySelectorAll('.primary-btn');
    const primaryBgElements = document.querySelectorAll('.primary-bg');
    
    primaryColor.addEventListener('input', function() {
        primaryColorText.value = this.value;
        updatePreviewColors(this.value);
    });
    
    primaryColorText.addEventListener('input', function() {
        if (this.value.startsWith('#') && (this.value.length === 4 || this.value.length === 7)) {
            primaryColor.value = this.value;
            updatePreviewColors(this.value);
        }
    });
    
    function updatePreviewColors(color) {
        // Butonları güncelle
        primaryButtons.forEach(button => {
            button.style.backgroundColor = color;
        });

        // Arka plan renklerini güncelle
        primaryBgElements.forEach(element => {
            element.style.backgroundColor = color;
        });
    }
    
    // İlk yüklemede renkleri ayarla
    updatePreviewColors(primaryColor.value);
    
    // Tema önizleme
    const previewLight = document.getElementById('preview-light');
    const previewDark = document.getElementById('preview-dark');
    
    previewLight.addEventListener('click', function() {
        themePreview.classList.remove('dark:bg-gray-800', 'dark:border-gray-700');
        themePreview.querySelectorAll('.dark\\:text-gray-100, .dark\\:text-gray-200, .dark\\:text-gray-300').forEach(el => {
            el.classList.add('text-gray-hidden');
        });
        themePreview.querySelectorAll('.dark\\:bg-gray-600, .dark\\:bg-gray-700').forEach(el => {
            el.classList.add('bg-gray-hidden');
        });
        themePreview.querySelectorAll('.dark\\:border-gray-600').forEach(el => {
            el.classList.add('border-gray-hidden');
        });
    });
    
    previewDark.addEventListener('click', function() {
        themePreview.classList.add('dark:bg-gray-800', 'dark:border-gray-700');
        themePreview.querySelectorAll('.dark\\:text-gray-100, .dark\\:text-gray-200, .dark\\:text-gray-300').forEach(el => {
            el.classList.remove('text-gray-hidden');
        });
        themePreview.querySelectorAll('.dark\\:bg-gray-600, .dark\\:bg-gray-700').forEach(el => {
            el.classList.remove('bg-gray-hidden');
        });
        themePreview.querySelectorAll('.dark\\:border-gray-600').forEach(el => {
            el.classList.remove('border-gray-hidden');
        });
    });
    
    // Sosyal medya önizlemesi
    const facebookUrl = document.getElementById('facebook_url');
    const twitterUrl = document.getElementById('twitter_url');
    const instagramUrl = document.getElementById('instagram_url');
    const youtubeUrl = document.getElementById('youtube_url');
    
    const facebookPreview = document.getElementById('facebook-preview');
    const twitterPreview = document.getElementById('twitter-preview');
    const instagramPreview = document.getElementById('instagram-preview');
    const youtubePreview = document.getElementById('youtube-preview');
    
    facebookUrl.addEventListener('input', function() {
        facebookPreview.classList.toggle('text-gray-400', this.value === '');
        facebookPreview.classList.toggle('text-blue-600', this.value !== '');
    });
    
    twitterUrl.addEventListener('input', function() {
        twitterPreview.classList.toggle('text-gray-400', this.value === '');
        twitterPreview.classList.toggle('text-blue-400', this.value !== '');
    });
    
    instagramUrl.addEventListener('input', function() {
        instagramPreview.classList.toggle('text-gray-400', this.value === '');
        instagramPreview.classList.toggle('text-pink-600', this.value !== '');
    });
    
    youtubeUrl.addEventListener('input', function() {
        youtubePreview.classList.toggle('text-gray-400', this.value === '');
        youtubePreview.classList.toggle('text-red-600', this.value !== '');
    });
});
</script>

<?php include './includes/footer.php'; ?>
