<?php
// This file closes the main content divs opened in header.php
?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirm delete
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this record?');
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 500);
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
                        
                        ageInput.value = Math.max(0, age);
                    } else {
                        ageInput.value = '';
                    }
                });
            }
        }
        
        // Format currency input
        function formatCurrency(inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('blur', function() {
                    const value = parseFloat(this.value) || 0;
                    this.value = value.toFixed(2);
                });
            }
        }
    </script>
</body>
</html>