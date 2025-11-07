<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Admin Paneli - QuizMeto'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: {
                50: '#f0f3ff',
                100: '#e0e7ff',
                200: '#c7d2fe',
                300: '#a5b4fc',
                400: '#818cf8',
                500: '#6366f1',
                600: '#4f46e5',
                700: '#4338ca',
                800: '#3730a3',
                900: '#312e81',
                950: '#1e1b4b',
              },
              dark: {
                800: '#1f2937',
                900: '#111827',
                950: '#030712',
              }
            },
            fontFamily: {
              sans: ['Inter', 'sans-serif'],
            },
          }
        }
      }
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 bg-dark-900 text-white">
                <div class="h-14 flex items-center px-4 border-b border-dark-800">
                    <div class="text-xl font-bold">
                        <a href="../index.php" class="hover:text-gray-300 transition-colors">Quiz<span class="text-primary-500">Meto</span></a>
                    </div>
                </div>
                
                <div class="flex flex-col items-center py-4 px-2 border-b border-dark-800">
                    <img src="<?php echo !empty($_SESSION['user_image']) ? '../' . $_SESSION['user_image'] : '../assets/images/default-avatar.png'; ?>" 
                         alt="Admin" class="h-14 w-14 rounded-full object-cover border-2 border-primary-500">
                    <div class="mt-2 text-center">
                        <h3 class="text-sm font-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                        <p class="text-xs text-gray-400">Admin</p>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto py-4 px-2">
                    <nav class="space-y-1">
                        <a href="index.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                            <i class="fas fa-tachometer-alt w-6 h-6 mr-3 text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        
                        <a href="quizzes.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo in_array(basename($_SERVER['PHP_SELF']), ['quizzes.php', 'quizzes-add.php', 'quizzes-edit.php', 'questions.php']) ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                            <i class="fas fa-file-alt w-6 h-6 mr-3 text-lg"></i>
                            <span>Quizler</span>
                        </a>
                        
                        <a href="categories.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                            <i class="fas fa-folder w-6 h-6 mr-3 text-lg"></i>
                            <span>Kategoriler</span>
                        </a>
                        
                        <a href="users.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                            <i class="fas fa-users w-6 h-6 mr-3 text-lg"></i>
                            <span>Kullanıcılar</span>
                        </a>
                        
                        <a href="scores.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'scores.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                            <i class="fas fa-clipboard-check w-6 h-6 mr-3 text-lg"></i>
                            <span>Skorlar</span>
                        </a>
                        
                        <a href="reports.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                            <i class="fas fa-chart-bar w-6 h-6 mr-3 text-lg"></i>
                            <span>Raporlar</span>
                        </a>
                        
                        <a href="settings.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                            <i class="fas fa-cog w-6 h-6 mr-3 text-lg"></i>
                            <span>Ayarlar</span>
                        </a>
                    </nav>
                </div>
                
                <div class="flex-shrink-0 p-2 border-t border-dark-800">
                    <div class="flex justify-between text-sm">
                        <a href="../index.php" class="group flex items-center px-3 py-2 text-gray-300 rounded-md hover:bg-dark-800 hover:text-white">
                            <i class="fas fa-home w-5 h-5 mr-2"></i>
                            <span>Siteye Dön</span>
                        </a>
                        <a href="../logout.php" class="group flex items-center px-3 py-2 text-gray-300 rounded-md hover:bg-dark-800 hover:text-white">
                            <i class="fas fa-sign-out-alt w-5 h-5 mr-2"></i>
                            <span>Çıkış</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            <!-- Top Navigation -->
            <div class="relative z-10 flex-shrink-0 flex h-14 bg-white shadow-sm">
                <button type="button" class="md:hidden px-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500" id="sidebar-toggle">
                    <span class="sr-only">Menüyü Aç</span>
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex-1 flex items-center">
                        <div class="max-w-lg w-full lg:max-w-xs relative">
                            <label for="search" class="sr-only">Ara</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input id="search" name="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="Ara..." type="search">
                            </div>
                        </div>
                    </div>
                    
                    <div class="ml-4 flex items-center md:ml-6">
                        <!-- Notifications Dropdown -->
                        <div class="ml-3 relative" x-data="{ open: false }">
                            <div>
                                <button @click="open = !open" type="button" class="p-1 rounded-full text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500" id="notification-menu-button">
                                    <span class="sr-only">Bildirimleri Göster</span>
                                    <i class="fas fa-bell text-lg relative">
                                        <span class="absolute top-0 right-0 -mt-1 -mr-1 px-1.5 py-0.5 bg-red-500 text-white rounded-full text-xs">3</span>
                                    </i>
                                </button>
                            </div>
                            
                            <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" role="menu" aria-orientation="vertical" aria-labelledby="notification-menu-button">
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <h3 class="text-sm font-medium text-gray-900">Bildirimler</h3>
                                    <a href="#" class="text-xs text-primary-600 hover:text-primary-800">Tümünü Gör</a>
                                </div>
                                <div class="py-1 max-h-64 overflow-y-auto" role="none">
                                    <a href="#" class="block px-4 py-2 hover:bg-gray-100" role="menuitem">
                                        <div class="flex">
                                            <div class="flex-shrink-0 bg-green-100 rounded-full p-1">
                                                <i class="fas fa-user-plus text-green-600"></i>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm text-gray-900">Yeni kullanıcı kaydoldu: <span class="font-medium">Ahmet Yılmaz</span></p>
                                                <p class="text-xs text-gray-500">2 saat önce</p>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="block px-4 py-2 hover:bg-gray-100" role="menuitem">
                                        <div class="flex">
                                            <div class="flex-shrink-0 bg-blue-100 rounded-full p-1">
                                                <i class="fas fa-clipboard-check text-blue-600"></i>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm text-gray-900">Yeni quiz tamamlandı: <span class="font-medium">Genel Kültür Testi</span></p>
                                                <p class="text-xs text-gray-500">5 saat önce</p>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="block px-4 py-2 hover:bg-gray-100" role="menuitem">
                                        <div class="flex">
                                            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-1">
                                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm text-gray-900">Sistem güncellemesi planlandı</p>
                                                <p class="text-xs text-gray-500">1 gün önce</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Dropdown -->
                        <div class="ml-3 relative" x-data="{ open: false }">
                            <div>
                                <button @click="open = !open" type="button" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" id="user-menu-button">
                                    <span class="sr-only">Kullanıcı menüsünü aç</span>
                                    <img class="h-8 w-8 rounded-full" src="<?php echo !empty($_SESSION['user_image']) ? '../' . $_SESSION['user_image'] : '../assets/images/default-avatar.png'; ?>" alt="Profil">
                                </button>
                            </div>
                            
                            <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button">
                                <a href="../profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-user mr-2"></i> Profilim
                                </a>
                                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-cog mr-2"></i> Ayarlar
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Çıkış Yap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Sidebar -->
            <div class="md:hidden fixed inset-0 flex z-40 bg-black bg-opacity-50 transform transition-opacity duration-300 ease-in-out hidden" id="mobile-sidebar-container">
                <div class="relative flex-1 flex flex-col max-w-xs w-full bg-dark-900 transform transition-transform duration-300 ease-in-out -translate-x-full" id="mobile-sidebar">
                    <div class="absolute top-0 right-0 -mr-12 pt-2">
                        <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" id="close-sidebar-button">
                            <span class="sr-only">Menüyü Kapat</span>
                            <i class="fas fa-times text-white text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="mt-5 flex-1 h-0 overflow-y-auto">
                        <div class="px-4 pt-3 pb-6 border-b border-dark-800">
                            <div class="flex items-center">
                                <img class="h-12 w-12 rounded-full" src="<?php echo !empty($_SESSION['user_image']) ? '../' . $_SESSION['user_image'] : '../assets/images/default-avatar.png'; ?>" alt="Profil">
                                <div class="ml-3">
                                    <div class="text-base font-medium text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                                    <div class="text-sm font-medium text-gray-400">Admin</div>
                                </div>
                            </div>
                        </div>
                        
                        <nav class="px-2 space-y-1 mt-4">
                            <a href="index.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                                <i class="fas fa-tachometer-alt w-6 h-6 mr-3 text-lg"></i>
                                <span>Dashboard</span>
                            </a>
                            
                            <a href="quizzes.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo in_array(basename($_SERVER['PHP_SELF']), ['quizzes.php', 'quizzes-add.php', 'quizzes-edit.php', 'questions.php']) ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                                <i class="fas fa-file-alt w-6 h-6 mr-3 text-lg"></i>
                                <span>Quizler</span>
                            </a>
                            
                            <a href="categories.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                                <i class="fas fa-folder w-6 h-6 mr-3 text-lg"></i>
                                <span>Kategoriler</span>
                            </a>
                            
                            <a href="users.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                                <i class="fas fa-users w-6 h-6 mr-3 text-lg"></i>
                                <span>Kullanıcılar</span>
                            </a>
                            
                            <a href="scores.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'scores.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                                <i class="fas fa-clipboard-check w-6 h-6 mr-3 text-lg"></i>
                                <span>Skorlar</span>
                            </a>
                            
                            <a href="reports.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                                <i class="fas fa-chart-bar w-6 h-6 mr-3 text-lg"></i>
                                <span>Raporlar</span>
                            </a>
                            
                            <a href="settings.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-800 hover:text-white'; ?>">
                                <i class="fas fa-cog w-6 h-6 mr-3 text-lg"></i>
                                <span>Ayarlar</span>
                            </a>
                            
                            <div class="border-t border-dark-800 my-4"></div>
                            
                            <a href="../index.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-300 hover:bg-dark-800 hover:text-white">
                                <i class="fas fa-home w-6 h-6 mr-3 text-lg"></i>
                                <span>Siteye Dön</span>
                            </a>
                            
                            <a href="../logout.php" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-300 hover:bg-dark-800 hover:text-white">
                                <i class="fas fa-sign-out-alt w-6 h-6 mr-3 text-lg"></i>
                                <span>Çıkış Yap</span>
                            </a>
                        </nav>
                    </div>
                </div>
                
                <div class="flex-shrink-0 w-14"></div>
            </div>
            
            <!-- Main Content Area -->
            <main class="flex-1 relative overflow-y-auto focus:outline-none p-6 bg-gray-100">
