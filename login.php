<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Kullanıcı zaten giriş yapmışsa anasayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token kontrolü
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    } else {
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = "Kullanıcı adı ve şifre gereklidir.";
        } else {
            // Kullanıcıyı veritabanında ara
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Kullanıcı bilgilerini session'a kaydet
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_image'] = $user['profile_image'];
                
                // Kullanıcıyı ana sayfaya yönlendir
                header("Location: index.php");
                exit;
            } else {
                $error = "Geçersiz kullanıcı adı veya şifre.";
            }
        }
    }
}

$pageTitle = "Giriş Yap - QuizMeto";
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 pt-12 pb-12 md:pt-20 md:pb-24">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:max-w-2xl">
        <div class="md:flex">
            <div class="md:shrink-0 hidden md:block">
                <div class="h-full w-48 bg-gradient-to-br from-primary-600 to-primary-800 p-8 flex items-center justify-center">
                    <div class="text-center text-white">
                        <i class="fas fa-brain text-5xl mb-4 opacity-75"></i>
                        <h3 class="text-xl font-bold mb-2">Bilgini Test Et</h3>
                        <p class="text-sm opacity-75">Quizlere katıl, eğlen ve öğren!</p>
                    </div>
                </div>
            </div>
            
            <div class="p-8 w-full">
                <div class="mb-8 text-center md:text-left">
                    <h2 class="text-2xl font-bold text-gray-900">QuizMeto'ya Giriş Yap</h2>
                    <p class="text-gray-600 mt-1">Hesabınız yok mu? <a href="register.php" class="text-primary-600 hover:text-primary-800 font-medium">Kayıt olun</a></p>
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
                
                <form action="login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Kullanıcı Adı / E-posta</label>
                        <input type="text" id="username" name="username" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Şifre</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <button type="button" class="toggle-password absolute inset-y-0 right-0 px-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember"
                                  class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">Beni hatırla</label>
                        </div>
                        <a href="forgot-password.php" class="text-sm text-primary-600 hover:text-primary-800">Şifremi unuttum</a>
                    </div>
                    
                    <div class="mb-6">
                        <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                            Giriş Yap
                        </button>
                    </div>
                    
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">veya şununla giriş yapın</span>
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
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
