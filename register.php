<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Site ayarlarını al
$site_settings = get_site_settings();
$enable_registration = ($site_settings['enable_registration'] ?? '1') === '1';

// Kayıt kapalıysa mesaj göster
if (!$enable_registration) {
    $_SESSION['error'] = "Kullanıcı kaydı şu anda kapalıdır. Lütfen daha sonra tekrar deneyin.";
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token kontrolü
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Girişleri doğrula
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "Tüm alanlar zorunludur.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Geçerli bir e-posta adresi giriniz.";
        } elseif (strlen($password) < 6) {
            $error = "Şifre en az 6 karakter olmalıdır.";
        } elseif ($password !== $confirm_password) {
            $error = "Şifreler eşleşmiyor.";
        } else {
            // Kullanıcı adı veya e-posta zaten kayıtlı mı kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
            } else {
                // Şifreyi hashe çevir
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Kullanıcıyı veritabanına ekle
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $result = $stmt->execute([$username, $email, $hashed_password]);
                
                if ($result) {
                    $success = "Hesabınız başarıyla oluşturuldu. Şimdi giriş yapabilirsiniz.";
                } else {
                    $error = "Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.";
                }
            }
        }
    }
}

$pageTitle = "Kayıt Ol - QuizMeto";
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 pt-12 pb-12 md:pt-20 md:pb-24">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:max-w-2xl">
        <div class="md:flex">
            <div class="md:shrink-0 hidden md:block">
                <div class="h-full w-48 bg-gradient-to-br from-indigo-600 to-purple-600 p-8 flex items-center justify-center">
                    <div class="text-center text-white">
                        <i class="fas fa-user-plus text-5xl mb-4 opacity-75"></i>
                        <h3 class="text-xl font-bold mb-2">Bize Katıl</h3>
                        <p class="text-sm opacity-75">Hemen kayıt ol ve quizlere başla!</p>
                    </div>
                </div>
            </div>
            
            <div class="p-8 w-full">
                <div class="mb-6 text-center md:text-left">
                    <h2 class="text-2xl font-bold text-gray-900">Hesap Oluştur</h2>
                    <p class="text-gray-600 mt-1">Zaten hesabınız var mı? <a href="login.php" class="text-primary-600 hover:text-primary-800 font-medium">Giriş yapın</a></p>
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
                                <p class="text-sm mt-1">5 saniye içinde yönlendiriliyorsunuz...</p>
                            </div>
                        </div>
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 5000);
                    </script>
                <?php else: ?>
                    <form action="register.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-4">
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Kullanıcı Adı</label>
                            <input type="text" id="username" name="username" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-posta Adresi</label>
                            <input type="email" id="email" name="email" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Şifre</label>
                            <div class="relative" x-data="{ show: false }">
                                <input 
                                    :type="show ? 'text' : 'password'" 
                                    id="password" 
                                    name="password" 
                                    required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    minlength="6">
                                <button 
                                    type="button" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center" 
                                    @click="show = !show">
                                    <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">Şifreniz en az 6 karakter olmalıdır.</div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Şifre Tekrar</label>
                            <div class="relative" x-data="{ show: false }">
                                <input 
                                    :type="show ? 'text' : 'password'" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    minlength="6">
                                <button 
                                    type="button" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center" 
                                    @click="show = !show">
                                    <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <div class="flex items-center">
                                <input id="terms" name="terms" type="checkbox" required
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <label for="terms" class="ml-2 block text-sm text-gray-700">
                                    <a href="terms.php" target="_blank" class="text-primary-600 hover:text-primary-800">Kullanım Şartları</a>'nı ve
                                    <a href="privacy.php" target="_blank" class="text-primary-600 hover:text-primary-800">Gizlilik Politikası</a>'nı kabul ediyorum
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                                Kayıt Ol
                            </button>
                        </div>
                        
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">veya şununla kayıt ol</span>
                            </div>
                        </div>
                        
                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <a href="#" class="flex justify-center items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <i class="fab fa-google text-red-500 mr-2"></i>
                                <span>Google</span>
                            </a>
                            <a href="#" class="flex justify-center items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <i class="fab fa-facebook-f text-blue-600 mr-2"></i>
                                <span>Facebook</span>
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
