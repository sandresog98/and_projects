        </div>
    </main>
    
    <!-- Footer -->
    <footer class="py-4 text-center position-relative" style="border-top: 1px solid rgba(255,255,255,0.08); z-index: 1;">
        <div class="container">
            <small style="color: #333; letter-spacing: 1px;">
                © <?= date('Y') ?> <?= APP_NAME ?>
            </small>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Inicializar partículas de fondo
        tsParticles.load("tsparticles-bg", {
            fullScreen: { enable: false },
            background: { color: { value: "transparent" } },
            fpsLimit: 60,
            particles: {
                color: { value: "#ffffff" },
                move: {
                    direction: "none",
                    enable: true,
                    outModes: { default: "out" },
                    random: true,
                    speed: { min: 0.05, max: 0.2 },
                    straight: false
                },
                number: {
                    density: { enable: true, area: 1200 },
                    value: 60
                },
                opacity: {
                    value: { min: 0.05, max: 0.3 },
                    animation: {
                        enable: true,
                        speed: 0.5,
                        sync: false,
                        startValue: "random"
                    }
                },
                shape: { type: "circle" },
                size: {
                    value: { min: 0.5, max: 1.5 }
                }
            },
            detectRetina: true
        });
        
        // Toast notifications
        function showToast(message, type = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: '#0a0a0a',
                color: '#fff',
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            
            Toast.fire({
                icon: type,
                title: message
            });
        }
        
        // Formatear fechas
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
    </script>
</body>
</html>
