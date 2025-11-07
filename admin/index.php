<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
require_admin();

// İstatistikleri al
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_quizzes' => $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn(),
    'total_questions' => $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
    'total_attempts' => $pdo->query("SELECT COUNT(*) FROM user_scores")->fetchColumn(),
    'recent_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)")->fetchColumn(),
    'recent_attempts' => $pdo->query("SELECT COUNT(*) FROM user_scores WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)")->fetchColumn(),
];

// Son aktiviteleri al
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_scores = $pdo->query("SELECT us.*, u.username, q.title as quiz_title 
                             FROM user_scores us 
                             JOIN users u ON us.user_id = u.id 
                             JOIN quizzes q ON us.quiz_id = q.id 
                             ORDER BY us.completed_at DESC LIMIT 10")->fetchAll();

$pageTitle = "Admin Paneli - QuizMeto";
include 'includes/header.php';
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500">QuizMeto platformu genel bakışı</p>
        </div>
        <div class="flex space-x-2">
            <span class="text-xs font-medium px-2 py-1 rounded-full bg-primary-100 text-primary-800"><?php echo date('d.m.Y'); ?></span>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-xl neo-brutalism">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-lg bg-primary-100 text-primary-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Toplam Kullanıcı</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_users']; ?></div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <i class="fas fa-arrow-up"></i>
                                    <span class="sr-only">Artış</span>
                                    <?php echo $stats['recent_users']; ?>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="users.php" class="font-medium text-primary-600 hover:text-primary-800">Tümünü Görüntüle</a>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm rounded-xl neo-brutalism">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-lg bg-indigo-100 text-indigo-600">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Toplam Quiz</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_quizzes']; ?></div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="quizzes.php" class="font-medium text-primary-600 hover:text-primary-800">Tümünü Görüntüle</a>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm rounded-xl neo-brutalism">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-lg bg-purple-100 text-purple-600">
                        <i class="fas fa-question-circle text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Toplam Soru</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_questions']; ?></div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="questions-all.php" class="font-medium text-primary-600 hover:text-primary-800">Tümünü Görüntüle</a>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm rounded-xl neo-brutalism">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-lg bg-green-100 text-green-600">
                        <i class="fas fa-clipboard-check text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Quiz Çözümleri</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_attempts']; ?></div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <i class="fas fa-arrow-up"></i>
                                    <span class="sr-only">Artış</span>
                                    <?php echo $stats['recent_attempts']; ?>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="scores.php" class="font-medium text-primary-600 hover:text-primary-800">Tümünü Görüntüle</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Users -->
        <div class="bg-white shadow-md rounded-xl overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900">Son Kullanıcılar</h2>
                <a href="users.php" class="text-sm font-medium text-primary-600 hover:text-primary-800">Tümünü Gör</a>
            </div>
            <div class="bg-white overflow-hidden">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($recent_users as $user): ?>
                        <li>
                            <div class="px-6 py-4 flex items-center">
                                <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../assets/images/default-avatar.png'; ?>" alt="Avatar" class="h-10 w-10 rounded-full object-cover border border-gray-200">
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
                                    </div>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($recent_users)): ?>
                        <li class="px-6 py-4 text-center text-gray-500">Henüz kullanıcı bulunmuyor.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Recent Scores -->
        <div class="bg-white shadow-md rounded-xl overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900">Son Quiz Çözümleri</h2>
                <a href="scores.php" class="text-sm font-medium text-primary-600 hover:text-primary-800">Tümünü Gör</a>
            </div>
            <div class="overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_scores as $score): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($score['username']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 truncate max-w-[200px]"><?php echo htmlspecialchars($score['quiz_title']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium"><?php echo $score['score']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo format_time_ago($score['completed_at']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_scores)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Henüz quiz çözümü bulunmuyor.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white shadow-md rounded-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Hızlı İşlemler</h2>
        </div>
        <div class="p-6 bg-white">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="quizzes-add.php" class="group relative bg-white p-6 focus:outline-none rounded-xl border border-gray-200 hover:border-primary-300 hover:shadow-md transition-all duration-300">
                    <div>
                        <span class="inline-flex items-center justify-center p-3 bg-primary-100 text-primary-600 rounded-lg">
                            <i class="fas fa-plus-circle text-xl"></i>
                            <span class="sr-only">Quiz Ekle</span>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Yeni Quiz Ekle
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Yeni bir quiz oluşturun
                        </p>
                    </div>
                    <span class="absolute inset-0 pointer-events-none" aria-hidden="true"></span>
                </a>
                
                <a href="categories.php" class="group relative bg-white p-6 focus:outline-none rounded-xl border border-gray-200 hover:border-primary-300 hover:shadow-md transition-all duration-300">
                    <div>
                        <span class="inline-flex items-center justify-center p-3 bg-blue-100 text-blue-600 rounded-lg">
                            <i class="fas fa-folder-plus text-xl"></i>
                            <span class="sr-only">Kategoriler</span>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Kategorileri Yönet
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Quiz kategorilerini düzenleyin
                        </p>
                    </div>
                    <span class="absolute inset-0 pointer-events-none" aria-hidden="true"></span>
                </a>
                
                <a href="users.php" class="group relative bg-white p-6 focus:outline-none rounded-xl border border-gray-200 hover:border-primary-300 hover:shadow-md transition-all duration-300">
                    <div>
                        <span class="inline-flex items-center justify-center p-3 bg-green-100 text-green-600 rounded-lg">
                            <i class="fas fa-user-cog text-xl"></i>
                            <span class="sr-only">Kullanıcılar</span>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Kullanıcıları Yönet
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Kullanıcı hesaplarını yönetin
                        </p>
                    </div>
                    <span class="absolute inset-0 pointer-events-none" aria-hidden="true"></span>
                </a>
                
                <a href="reports.php" class="group relative bg-white p-6 focus:outline-none rounded-xl border border-gray-200 hover:border-primary-300 hover:shadow-md transition-all duration-300">
                    <div>
                        <span class="inline-flex items-center justify-center p-3 bg-purple-100 text-purple-600 rounded-lg">
                            <i class="fas fa-chart-bar text-xl"></i>
                            <span class="sr-only">Raporlar</span>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Raporlar
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            İstatistikleri ve raporları görüntüleyin
                        </p>
                    </div>
                    <span class="absolute inset-0 pointer-events-none" aria-hidden="true"></span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
