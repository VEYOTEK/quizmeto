<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'QuizMeto'; ?></title>
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
              secondary: {
                50: '#ecfdf5',
                100: '#d1fae5',
                200: '#a7f3d0',
                300: '#6ee7b7',
                400: '#34d399',
                500: '#10b981',
                600: '#059669',
                700: '#047857',
                800: '#065f46',
                900: '#064e3b',
                950: '#022c22',
              },
            },
            fontFamily: {
              sans: ['Inter', 'sans-serif'],
            },
            animation: {
              'fade-in': 'fadeIn 0.5s ease-in-out',
              'slide-up': 'slideUp 0.5s ease-in-out',
              'bounce-slow': 'bounce 3s infinite',
            },
            keyframes: {
              fadeIn: {
                '0%': { opacity: '0' },
                '100%': { opacity: '1' },
              },
              slideUp: {
                '0%': { transform: 'translateY(20px)', opacity: '0' },
                '100%': { transform: 'translateY(0)', opacity: '1' },
              },
            },
            boxShadow: {
              'neo': '5px 5px 0px 0px rgba(0,0,0,0.75)',
              'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.37)',
            }
          }
        }
      }
    </script>
    <style type="text/tailwindcss">
      @layer utilities {
        .glass-effect {
          @apply bg-white/60 backdrop-blur-md border border-white/20 shadow-glass;
        }
        .neo-brutalism {
          @apply bg-white border-2 border-black shadow-neo transition-all hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[7px_7px_0px_0px_rgba(0,0,0,0.8)];
        }
        .difficulty-easy {
          @apply bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-medium;
        }
        .difficulty-orta {
          @apply bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full text-xs font-medium;
        }
        .difficulty-zor {
          @apply bg-red-100 text-red-800 px-2 py-0.5 rounded-full text-xs font-medium;
        }
      }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900 antialiased">
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center justify-between h-16">
                <div class="flex-shrink-0">
                    <a href="index.php" class="text-2xl font-bold text-gray-900">
                        Quiz<span class="text-primary-600">Meto</span>
                    </a>
                </div>
                <div class="hidden md:block">
                    <ul class="flex space-x-8">
                        <li><a href="index.php" class="text-gray-700 hover:text-primary-600 font-medium transition-colors">Ana Sayfa</a></li>
                        <li><a href="quizzes.php" class="text-gray-700 hover:text-primary-600 font-medium transition-colors">Quizler</a></li>
                        <li><a href="leaderboard.php" class="text-gray-700 hover:text-primary-600 font-medium transition-colors">Liderlik Tablosu</a></li>
                        <li><a href="about.php" class="text-gray-700 hover:text-primary-600 font-medium transition-colors">Hakkında</a></li>
                    </ul>
                </div>
                <div class="hidden md:block">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative inline-block text-left" x-data="{ open: false }">
                            <button @click="open = !open" type="button" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 focus:outline-none">
                                <img src="<?php echo !empty($_SESSION['user_image']) ? $_SESSION['user_image'] : 'assets/images/default-avatar.png'; ?>" alt="Profil" class="h-8 w-8 rounded-full object-cover border-2 border-primary-100">
                                <span class="font-medium"><?php echo $_SESSION['username']; ?></span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 w-48 mt-2 py-2 bg-white rounded-lg shadow-xl z-20">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-user mr-2"></i> Profilim</a>
                                <a href="my-quizzes.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-history mr-2"></i> Quiz Geçmişim</a>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <a href="admin/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-cog mr-2"></i> Admin Paneli</a>
                                <?php endif; ?>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış Yap</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center space-x-4">
                            <a href="login.php" class="text-gray-700 hover:text-primary-600 font-medium px-3 py-2 rounded-md transition-colors">Giriş Yap</a>
                            <a href="register.php" class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-4 py-2 rounded-md transition-colors">Kayıt Ol</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="md:hidden">
                    <button type="button" class="text-gray-600 hover:text-gray-900 focus:outline-none" id="mobile-menu-button">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </nav>
        </div>
        
        <!-- Mobile menu -->
        <div class="hidden md:hidden bg-white border-b border-gray-200" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="index.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Ana Sayfa</a>
                <a href="quizzes.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Quizler</a>
                <a href="leaderboard.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Liderlik Tablosu</a>
                <a href="about.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Hakkında</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="border-t border-gray-200 my-2"></div>
                    <a href="profile.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Profilim</a>
                    <a href="my-quizzes.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Quiz Geçmişim</a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/index.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Admin Paneli</a>
                    <?php endif; ?>
                    <a href="logout.php" class="block px-3 py-2 text-red-600 hover:bg-gray-100 rounded-md">Çıkış Yap</a>
                <?php else: ?>
                    <div class="border-t border-gray-200 my-2"></div>
                    <a href="login.php" class="block px-3 py-2 bg-gray-100 text-gray-700 rounded-md">Giriş Yap</a>
                    <a href="register.php" class="block px-3 py-2 bg-primary-600 text-white rounded-md mt-2">Kayıt Ol</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="min-h-screen">
