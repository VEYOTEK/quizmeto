<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = "QuizMeto - Anasayfa";
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-3xl p-8 md:p-12 mb-16 text-white relative overflow-hidden shadow-xl">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 opacity-80"></div>
        <div class="absolute -right-20 -bottom-20">
            <svg class="w-64 h-64 text-white/10" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-9h10v2H7z"/>
            </svg>
        </div>
        <div class="relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 animate-fade-in">QuizMeto'ya Hoş Geldiniz</h1>
            <p class="text-xl md:text-2xl mb-8 opacity-90 max-w-2xl animate-slide-up delay-100">Bilginizi test edin, yeni şeyler öğrenin ve liderlik tablosunda yerinizi alın.</p>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="flex flex-col sm:flex-row gap-4 animate-slide-up delay-200">
                    <a href="login.php" class="inline-flex items-center justify-center px-6 py-3 bg-white text-primary-600 rounded-lg font-medium shadow-md hover:shadow-lg transition-all">
                        <i class="fas fa-sign-in-alt mr-2"></i> Giriş Yap
                    </a>
                    <a href="register.php" class="inline-flex items-center justify-center px-6 py-3 bg-transparent border-2 border-white text-white rounded-lg font-medium hover:bg-white/10 transition-all">
                        <i class="fas fa-user-plus mr-2"></i> Kayıt Ol
                    </a>
                </div>
            <?php else: ?>
                <div class="flex flex-col sm:flex-row gap-4 animate-slide-up delay-200">
                    <a href="quizzes.php" class="inline-flex items-center justify-center px-6 py-3 bg-white text-primary-600 rounded-lg font-medium shadow-md hover:shadow-lg transition-all">
                        <i class="fas fa-play mr-2"></i> Quizlere Başla
                    </a>
                    <a href="leaderboard.php" class="inline-flex items-center justify-center px-6 py-3 bg-transparent border-2 border-white text-white rounded-lg font-medium hover:bg-white/10 transition-all">
                        <i class="fas fa-trophy mr-2"></i> Liderlik Tablosu
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Popular Quizzes -->
    <div class="mb-16">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Popüler Quizler</h2>
            <a href="quizzes.php" class="text-primary-600 hover:text-primary-800 font-medium inline-flex items-center">
                Tümünü Gör <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            $stmt = $pdo->query("SELECT q.*, c.name as category_name FROM quizzes q LEFT JOIN categories c ON q.category_id = c.id ORDER BY q.participants DESC LIMIT 3");
            while ($quiz = $stmt->fetch(PDO::FETCH_ASSOC)):
            ?>
                <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300">
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
                        <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($quiz['description']); ?></p>
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
                            Quize Başla
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-center mb-12 text-gray-900">Neden QuizMeto?</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 text-primary-600 rounded-full mb-4">
                    <i class="fas fa-brain text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Bilginizi Geliştirin</h3>
                <p class="text-gray-600">Çeşitli kategorilerde yüzlerce quiz ile bilginizi test edin ve yeni şeyler öğrenin.</p>
            </div>
            
            <div class="text-center p-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 text-primary-600 rounded-full mb-4">
                    <i class="fas fa-trophy text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Rekabet Edin</h3>
                <p class="text-gray-600">Arkadaşlarınızla yarışın ve liderlik tablosunda üst sıralara yükselin.</p>
            </div>
            
            <div class="text-center p-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 text-primary-600 rounded-full mb-4">
                    <i class="fas fa-cogs text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Özelleştirilebilir</h3>
                <p class="text-gray-600">Kendinize özel profil oluşturun ve tercihlerinize göre quizleri filtreleyip bulun.</p>
            </div>
        </div>
    </div>
    
    <!-- Top Users -->
    <div>
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">En İyi Kullanıcılar</h2>
            <a href="leaderboard.php" class="text-primary-600 hover:text-primary-800 font-medium inline-flex items-center">
                Tüm Sıralamayı Gör <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <?php
            $stmt = $pdo->query("SELECT users.id, users.username, users.profile_image, SUM(user_scores.score) as total_score 
                                FROM users 
                                JOIN user_scores ON users.id = user_scores.user_id 
                                GROUP BY users.id 
                                ORDER BY total_score DESC 
                                LIMIT 5");
            $rank = 1;
            ?>
            
            <ul class="divide-y divide-gray-200">
                <?php while ($user = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <li class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors <?php echo isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id'] ? 'bg-primary-50' : ''; ?>">
                        <div class="flex items-center">
                            <div class="<?php echo $rank <= 3 ? 'w-10 h-10 flex items-center justify-center rounded-full font-bold' : 'mr-3 text-gray-500'; ?> 
                                     <?php echo $rank == 1 ? 'bg-yellow-100 text-yellow-700' : ($rank == 2 ? 'bg-gray-100 text-gray-700' : ($rank == 3 ? 'bg-orange-100 text-orange-700' : '')); ?>">
                                <?php echo "#" . $rank; ?>
                            </div>
                            <div class="ml-3 flex items-center">
                                <img src="<?php echo empty($user['profile_image']) ? 'assets/images/default-avatar.png' : $user['profile_image']; ?>" 
                                    alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 <?php echo $rank == 1 ? 'border-yellow-400' : ($rank == 2 ? 'border-gray-400' : ($rank == 3 ? 'border-orange-400' : 'border-gray-200')); ?>">
                                <div class="ml-3">
                                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo number_format($user['total_score']); ?> puan</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($rank == 1): ?>
                            <div class="text-yellow-500">
                                <i class="fas fa-crown text-xl animate-bounce-slow"></i>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php 
                $rank++;
                endwhile; 
                ?>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
