</main>
        </div>
    </div>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const mobileSidebarContainer = document.getElementById('mobile-sidebar-container');
            const mobileSidebar = document.getElementById('mobile-sidebar');
            const closeSidebarButton = document.getElementById('close-sidebar-button');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    mobileSidebarContainer.classList.remove('hidden');
                    setTimeout(() => {
                        mobileSidebar.classList.remove('-translate-x-full');
                    }, 10);
                });
            }
            
            if (closeSidebarButton) {
                closeSidebarButton.addEventListener('click', function() {
                    mobileSidebar.classList.add('-translate-x-full');
                    setTimeout(() => {
                        mobileSidebarContainer.classList.add('hidden');
                    }, 300);
                });
            }
            
            // Close sidebar when clicking outside
            if (mobileSidebarContainer) {
                mobileSidebarContainer.addEventListener('click', function(e) {
                    if (e.target === mobileSidebarContainer) {
                        mobileSidebar.classList.add('-translate-x-full');
                        setTimeout(() => {
                            mobileSidebarContainer.classList.add('hidden');
                        }, 300);
                    }
                });
            }
            
            // Modal handling
            const modals = document.querySelectorAll('.modal');
            const modalTriggers = document.querySelectorAll('[data-modal-target]');
            const modalCloseButtons = document.querySelectorAll('.modal-close');
            
            modalTriggers.forEach(trigger => {
                trigger.addEventListener('click', () => {
                    const modalId = trigger.getAttribute('data-modal-target');
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.remove('hidden');
                        setTimeout(() => {
                            modal.classList.add('opacity-100');
                        }, 10);
                    }
                });
            });
            
            modalCloseButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const modal = button.closest('.modal');
                    modal.classList.remove('opacity-100');
                    setTimeout(() => {
                        modal.classList.add('hidden');
                    }, 300);
                });
            });
            
            // Close modals when clicked outside content
            modals.forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.classList.remove('opacity-100');
                        setTimeout(() => {
                            modal.classList.add('hidden');
                        }, 300);
                    }
                });
            });
            
            // Alerts auto-dismiss
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('opacity-0');
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
