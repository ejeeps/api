
        // Password confirmation validation
        document.getElementById('driverRegistrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            // Validate license images
            const licenseFront = document.getElementById('license_image').files.length;
            const licenseBack = document.getElementById('license_back_image').files.length;
            
            if (!licenseFront || !licenseBack) {
                e.preventDefault();
                alert('Please upload both front and back license images!');
                return false;
            }
        });
