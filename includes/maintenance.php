<!DOCTYPE html>
<html lang="tr" class="<?php echo isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakım Modu | <?php echo htmlspecialchars($site_settings['site_title'] ?? 'Quiz Meto'); ?></title>
    
    <!-- Favicon -->
    <?php if (!empty($site_settings['favicon'])): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($site_settings['favicon']); ?>" type="image/x-icon">
    <?php endif; ?>
    
    <!-- Fontawesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        /* Dark mode styles - improved */
        .dark body {
            background-color: #111827;
            color: #f3f4f6;
        }
        
        .dark .bg-white {
            background-color: #1f2937;
        }
        
        .dark .text-gray-600 {
            color: #e5e7eb;
        }
        
        .dark .text-gray-800 {
            color: #f9fafb;
        }
        
        .dark .text-gray-500 {
            color: #d1d5db;
        }
        
        /* Font awesome ikonları için düzeltmeler */
        .fa, .fas, .far, .fab, .fal, .fad {
            display: inline-block !important;
            color: currentColor;
        }
        
        /* Açık temada ikonlar için varsayılan renk */
        .maintenance-icon i {
            color: <?php echo $site_settings['primary_color'] ?? '#4F46E5'; ?>;
        }
        
        /* Karanlık temada ikonlar için renk */
        .dark .maintenance-icon i {
            color: <?php echo $site_settings['primary_color'] ?? '#4F46E5'; ?>;
        }
        
        /* Light mode text improvements */
        body {
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
            color: #374151;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #111827;
        }
        
        p {
            color: #4b5563;
        }
        
        .text-gray-800 {
            color: #1f2937;
        }
        
        .text-gray-600 {
            color: #4b5563;
        }
        
        .text-gray-500 {
            color: #6b7280;
        }
        
        /* Transition effect */
        html.dark {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .maintenance-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 50px 20px;
        }
        
        .maintenance-icon {
            font-size: 3rem;
            color: <?php echo $site_settings['primary_color'] ?? '#4F46E5'; ?>;
        }
        
        /* Ensure text is visible in both themes */
        .dark-text-fix {
            color: #1f2937;
        }
        
        .dark .dark-text-fix {
            color: #f3f4f6;
        }
        
        /* Fix for icons */
        .dark .fa, 
        .dark .fas, 
        .dark .far, 
        .dark .fab {
            color: currentColor;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen dark:bg-gray-900">
    <div class="maintenance-container text-center">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg">
            <?php if (!empty($site_settings['site_logo'])): ?>
                <img src="<?php echo htmlspecialchars($site_settings['site_logo']); ?>" alt="Logo" class="h-16 mx-auto mb-6">
            <?php else: ?>
                <div class="maintenance-icon mb-6">
                    <i class="fas fa-tools"></i>
                </div>
            <?php endif; ?>
            
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">Bakım Modu</h1>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Sitemiz şu anda bakım için geçici olarak kapalıdır. Kısa sürede tekrar aktif olacağız. 
                Anlayışınız için teşekkür ederiz.
            </p>
            
            <div class="mt-8 text-gray-500 dark:text-gray-400 text-sm">
                <?php 
                $footer_text = $site_settings['footer_text'] ?? '© ' . date('Y') . ' Quiz Meto. Tüm hakları saklıdır.'; 
                echo $footer_text;
                ?>
            </div>
        </div>
    </div>
</body>
</html>
