        </div>
        </div>
    </div>
    
    <script>
        // Confirm delete
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this record?');
        }
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
        
        // Calculate age from birthdate
        function calculateAge(birthdateId, ageId) {
            const birthdateInput = document.getElementById(birthdateId);
            const ageInput = document.getElementById(ageId);
            
            if (birthdateInput && ageInput) {
                birthdateInput.addEventListener('change', function() {
                    if (this.value) {
                        const today = new Date();
                        const birth = new Date(this.value);
                        let age = today.getFullYear() - birth.getFullYear();
                        const monthDiff = today.getMonth() - birth.getMonth();
                        
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                            age--;
                        }
                        
                        ageInput.value = age;
                    }
                });
            }
        }
    </script>
</body>
</html>