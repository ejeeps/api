
        // Password confirmation validation
        document.getElementById('passengerRegistrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            // Validate password length
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }

            // Validate ID images
            const idFront = document.getElementById('id_image_front').files.length;
            const idBack = document.getElementById('id_image_back').files.length;
            
            if (!idFront || !idBack) {
                e.preventDefault();
                alert('Please upload both front and back ID images!');
                return false;
            }
        });

