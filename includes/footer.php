</main>
    <footer class="bg-gray-900 text-white pt-12 pb-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-1">
                    <h2 class="text-2xl font-bold mb-4">Quiz<span class="text-primary-400">Meto</span></h2>
                    <p class="text-gray-400 mb-4">Modern ve minimalist quiz platformu</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-span-1">
                    <h3 class="text-lg font-semibold mb-4 text-gray-200">Hızlı Bağlantılar</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-white transition-colors">Ana Sayfa</a></li>
                        <li><a href="quizzes.php" class="text-gray-400 hover:text-white transition-colors">Quizler</a></li>
                        <li><a href="leaderboard.php" class="text-gray-400 hover:text-white transition-colors">Liderlik Tablosu</a></li>
                        <li><a href="about.php" class="text-gray-400 hover:text-white transition-colors">Hakkında</a></li>
                    </ul>
                </div>
                
                <div class="col-span-1">
                    <h3 class="text-lg font-semibold mb-4 text-gray-200">Yardım</h3>
                    <ul class="space-y-2">
                        <li><a href="contact.php" class="text-gray-400 hover:text-white transition-colors">İletişim</a></li>
                        <li><a href="faq.php" class="text-gray-400 hover:text-white transition-colors">SSS</a></li>
                        <li><a href="privacy.php" class="text-gray-400 hover:text-white transition-colors">Gizlilik Politikası</a></li>
                        <li><a href="terms.php" class="text-gray-400 hover:text-white transition-colors">Kullanım Şartları</a></li>
                    </ul>
                </div>
                
                <div class="col-span-1">
                    <h3 class="text-lg font-semibold mb-4 text-gray-200">Bültenimize Abone Olun</h3>
                    <p class="text-gray-400 mb-4">En son quiz'ler ve güncellemeler hakkında bilgi alın</p>
                    <form action="subscribe.php" method="post" class="flex">
                        <input type="email" name="email" placeholder="E-posta adresiniz" required 
                               class="w-full px-4 py-2 rounded-l-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <button type="submit" 
                                class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-r-md transition-colors">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400">&copy; <?php echo date('Y'); ?> QuizMeto. Tüm hakları saklıdır.</p>
                <p class="text-gray-500 mt-2 md:mt-0 text-sm">
                    <a href="#" class="hover:text-gray-400 transition-colors">Gizlilik</a> &middot; 
                    <a href="#" class="hover:text-gray-400 transition-colors">Koşullar</a> &middot; 
                    <a href="#" class="hover:text-gray-400 transition-colors">Çerezler</a>
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
        
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>
