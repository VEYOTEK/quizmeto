<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = "Hakkımızda - QuizMeto";
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-3xl p-8 md:p-12 mb-16 text-white relative overflow-hidden shadow-xl">
        <div class="absolute inset-0 bg-pattern opacity-10"></div>
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Hakkımızda</h1>
            <p class="text-xl opacity-90 max-w-2xl">QuizMeto'nun hikayesi ve misyonu hakkında bilgi edinin</p>
        </div>

        <div class="absolute -right-10 -bottom-10 opacity-20">
            <svg class="w-48 h-48" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-9h10v2H7z"/>
            </svg>
        </div>
    </div>
    
    <!-- About Content -->
    <div class="bg-white rounded-xl shadow-lg p-8 md:p-10 mb-16">
        <div class="flex flex-col lg:flex-row items-center gap-10 mb-12">
            <div class="lg:w-1/2">
                <!-- QuizMeto Logo -->
                <div class="bg-white p-4 rounded-lg shadow-lg inline-flex items-center justify-center mb-6">
                    <div class="relative flex items-center">
                        <svg class="w-20 h-20 text-primary-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/>
                            <path d="M9.5 9.5h5v5h-5z" fill="currentColor"/>
                            <path d="M7 7v10h10V7H7zm8 8h-6V9h6v6z" fill="currentColor"/>
                        </svg>
                        <div class="ml-2">
                            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600">QuizMeto</h1>
                            <p class="text-gray-500 text-sm">Bilgini test et!</p>
                        </div>
                    </div>
                </div>

            </div>
            <div class="lg:w-1/2">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">QuizMeto Nedir?</h2>
                <div class="prose prose-lg text-gray-600">
                    <p class="mb-4">QuizMeto, eğitimi eğlenceli hale getirmeyi amaçlayan interaktif bir quiz platformudur. Kullanıcılarımıza çeşitli kategorilerde quizler sunarak bilgilerini test etme ve geliştirme imkanı sağlıyoruz.</p>
                    <p class="mb-4">2023 yılında başlayan yolculuğumuzda, bilgiyi paylaşmanın ve öğrenmeyi eğlenceli hale getirmenin en iyi yollarından birinin etkileşimli sınavlar olduğuna inandık.</p>
                    <p>Platformumuz, geniş bir konu yelpazesinde binlerce soru içeren yüzlerce quiz ile herkesin ilgi alanına uygun içerikler sunmayı hedeflemektedir.</p>
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Misyonumuz ve Değerlerimiz</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-xl">
                    <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center mb-4">
                        <i class="fas fa-brain text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Eğlenerek Öğren</h3>
                    <p class="text-gray-600">Öğrenme sürecini eğlenceli hale getirerek bilginin kalıcı olmasını sağlamak için çalışıyoruz.</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-xl">
                    <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center mb-4">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Topluluk Oluştur</h3>
                    <p class="text-gray-600">Bilgi paylaşımını teşvik eden ve birbirinden öğrenen aktif bir kullanıcı topluluğu oluşturmayı amaçlıyoruz.</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-xl">
                    <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Sürekli Gelişim</h3>
                    <p class="text-gray-600">Kullanıcılarımızın geri bildirimleri doğrultusunda sürekli olarak platformumuzu geliştirmeyi taahhüt ediyoruz.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Team Section -->
    <div class="bg-white rounded-xl shadow-lg p-8 md:p-10 mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Ekibimiz</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="relative w-32 h-32 mx-auto mb-4">
                    <div class="absolute inset-0 bg-gradient-to-r from-primary-400 to-primary-600 rounded-full opacity-50 transform -rotate-6"></div>
                    <img src="https://randomuser.me/api/portraits/men/<?php echo rand(1, 99); ?>.jpg" alt="Takım Üyesi"
                         class="absolute inset-0 w-full h-full object-cover rounded-full border-4 border-white"
                         onerror="this.onerror=null; this.src='assets/images/default-avatar.png'">
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-1">Ahmet Yılmaz</h3>
                <p class="text-primary-600 mb-3">Kurucu & Geliştirici</p>
                <p class="text-gray-600 mb-3">Ahmet, yazılım geliştirme konusundaki 10 yıllık deneyimiyle QuizMeto'nun teknik altyapısından sorumludur.</p>
                <div class="flex justify-center space-x-3">
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
            
            <div class="text-center">
                <div class="relative w-32 h-32 mx-auto mb-4">
                    <div class="absolute inset-0 bg-gradient-to-r from-primary-400 to-primary-600 rounded-full opacity-50 transform -rotate-6"></div>
                    <img src="https://randomuser.me/api/portraits/women/<?php echo rand(1, 99); ?>.jpg" alt="Takım Üyesi" 
                         class="absolute inset-0 w-full h-full object-cover rounded-full border-4 border-white"
                         onerror="this.onerror=null; this.src='assets/images/default-avatar.png'">
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-1">Ayşe Demir</h3>
                <p class="text-primary-600 mb-3">İçerik Editörü</p>
                <p class="text-gray-600 mb-3">Ayşe, eğitim alanındaki uzun yıllar deneyimiyle içerik kalitesini ve doğruluğunu sağlamaktadır.</p>
                <div class="flex justify-center space-x-3">
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
            
            <div class="text-center">
                <div class="relative w-32 h-32 mx-auto mb-4">
                    <div class="absolute inset-0 bg-gradient-to-r from-primary-400 to-primary-600 rounded-full opacity-50 transform -rotate-6"></div>
                    <img src="https://randomuser.me/api/portraits/men/<?php echo rand(1, 99); ?>.jpg" alt="Takım Üyesi" 
                         class="absolute inset-0 w-full h-full object-cover rounded-full border-4 border-white"
                         onerror="this.onerror=null; this.src='assets/images/default-avatar.png'">
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-1">Mehmet Kaya</h3>
                <p class="text-primary-600 mb-3">Kullanıcı Deneyimi Tasarımcısı</p>
                <p class="text-gray-600 mb-3">Mehmet, kullanıcı deneyimi ve arayüz tasarımı konusundaki uzmanlığıyla QuizMeto'nun kullanıcı dostu olmasını sağlar.</p>
                <div class="flex justify-center space-x-3">
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="bg-white rounded-xl shadow-lg p-8 md:p-10 mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Sıkça Sorulan Sorular</h2>
        
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="border border-gray-200 rounded-lg" x-data="{ open: false }">
                <button class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 focus:outline-none" @click="open = !open">
                    <span>QuizMeto'yu nasıl kullanabilirim?</span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div class="px-6 pb-4" x-show="open" x-transition>
                    <p class="text-gray-600">
                        QuizMeto'yu kullanmak için öncelikle kayıt olmanız gerekmektedir. Kayıt olduktan sonra, ana sayfadan veya Quiz sayfasından ilgilendiğiniz kategorideki quizleri seçebilir ve çözmeye başlayabilirsiniz. Her quiz sonunda puanınızı görebilir ve liderlik tablosundaki yerinizi kontrol edebilirsiniz.
                    </p>
                </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg" x-data="{ open: false }">
                <button class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 focus:outline-none" @click="open = !open">
                    <span>QuizMeto tamamen ücretsiz mi?</span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div class="px-6 pb-4" x-show="open" x-transition>
                    <p class="text-gray-600">
                        Evet, QuizMeto platformu tamamen ücretsizdir. Tüm quizlere ücretsiz olarak erişebilir ve çözebilirsiniz. İlerleyen dönemlerde gelişmiş özellikler içeren premium üyelik seçenekleri sunabiliriz.
                    </p>
                </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg" x-data="{ open: false }">
                <button class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 focus:outline-none" @click="open = !open">
                    <span>Kendi quizimi oluşturabilir miyim?</span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div class="px-6 pb-4" x-show="open" x-transition>
                    <p class="text-gray-600">
                        Evet, platformumuzdaki "Quiz Oluştur" özelliği ile kendi quizlerinizi hazırlayabilirsiniz. Oluşturduğunuz quizleri arkadaşlarınızla paylaşabilir veya platformda yayınlanması için bizimle iletişime geçebilirsiniz.
                    </p>
                </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg" x-data="{ open: false }">
                <button class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 focus:outline-none" @click="open = !open">
                    <span>Bir hatayı nasıl bildiririm?</span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div class="px-6 pb-4" x-show="open" x-transition>
                    <p class="text-gray-600">
                        Platformda karşılaştığınız herhangi bir hatayı veya yanlış bilgi içeren soruları iletişim sayfamızdan bize bildirebilirsiniz. Ekibimiz en kısa sürede geri dönüş yapacak ve gerekli düzeltmeleri yapacaktır.
                    </p>
                </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg" x-data="{ open: false }">
                <button class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 focus:outline-none" @click="open = !open">
                    <span>QuizMeto'ya nasıl katkıda bulunabilirim?</span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div class="px-6 pb-4" x-show="open" x-transition>
                    <p class="text-gray-600">
                        Platformumuza katkıda bulunmak için yeni quiz önerileri gönderebilir, mevcut quizleri değerlendirebilir veya içerik editörü olarak ekibimize katılmak istediğinizi belirtebilirsiniz. Tüm katkılarınız platformumuzun gelişmesine yardımcı olacaktır.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="mt-8 text-center">
            <p class="text-gray-600 mb-4">Başka sorularınız mı var?</p>
            <a href="contact.php" class="inline-flex items-center justify-center px-6 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors">
                <i class="fas fa-envelope mr-2"></i> Bizimle İletişime Geçin
            </a>
        </div>
    </div>
    
    <!-- Stats Section -->
    <div class="mb-16">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="text-4xl font-bold text-primary-600 mb-2">
                    <?php 
                    // Quiz sayısını getir
                    $quiz_count = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
                    echo number_format($quiz_count);
                    ?>+
                </div>
                <p class="text-gray-600">Quiz</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="text-4xl font-bold text-primary-600 mb-2">
                    <?php 
                    // Kullanıcı sayısını getir
                    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    echo number_format($user_count);
                    ?>+
                </div>
                <p class="text-gray-600">Kullanıcı</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="text-4xl font-bold text-primary-600 mb-2">
                    <?php 
                    // Tamamlanan quiz sayısını getir
                    $completion_count = $pdo->query("SELECT COUNT(*) FROM user_scores")->fetchColumn();
                    echo number_format($completion_count);
                    ?>+
                </div>
                <p class="text-gray-600">Tamamlanan Quiz</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="text-4xl font-bold text-primary-600 mb-2">
                    <?php 
                    // Kategori sayısını getir
                    $category_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
                    echo number_format($category_count);
                    ?>+
                </div>
                <p class="text-gray-600">Kategori</p>
            </div>
        </div>
    </div>
    
    <!-- Contact CTA -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-3xl p-8 md:p-12 text-white relative overflow-hidden shadow-xl">
        <div class="absolute inset-0 bg-pattern opacity-10"></div>
        <div class="relative z-10 max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4">Bizimle İletişime Geçin</h2>
            <p class="text-xl opacity-90 mb-8">Sorularınız, önerileriniz veya geri bildirimleriniz için bize ulaşın. Size yardımcı olmaktan memnuniyet duyarız.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="contact.php" class="inline-flex items-center justify-center px-6 py-3 bg-white text-primary-600 rounded-lg font-medium shadow-md hover:shadow-lg transition-all">
                    <i class="fas fa-envelope mr-2"></i> Mesaj Gönderin
                </a>
                <a href="mailto:info@quizmeto.com" class="inline-flex items-center justify-center px-6 py-3 bg-transparent border-2 border-white text-white rounded-lg font-medium hover:bg-white/10 transition-all">
                    <i class="fas fa-at mr-2"></i> info@quizmeto.com
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
