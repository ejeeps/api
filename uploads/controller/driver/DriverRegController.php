<?php
// Database connection using PDO
require_once __DIR__ . '/../../config/connection.php';

// Note: connection.php provides $pdo variable

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $requiredFields = [
            'email', 'password', 'confirm_password', 'first_name', 'last_name',
            'phone_number', 'address_line1', 'city', 'province', 'postal_code',
            'license_number', 'license_type', 'license_expiry_date', 'terms', 'background_check'
        ];

        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Validate password match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match.");
        }

        // Validate password length
        if (strlen($_POST['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }

        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if email already exists
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$_POST['email']]);
        if ($checkEmail->rowCount() > 0) {
            throw new Exception("Email already registered. Please use a different email or login.");
        }

        // Handle file uploads
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $profileImage = null;
        $licenseImage = null;
        $licenseBackImage = null;

        // Upload profile image if provided
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $profileImage = uploadFile($_FILES['profile_image'], $uploadDir . 'profiles/');
        }

        // Upload license front image (required)
        if (isset($_FILES['license_image']) && $_FILES['license_image']['error'] === UPLOAD_ERR_OK) {
            $licenseImage = uploadFile($_FILES['license_image'], $uploadDir . 'licenses/');
        } else {
            throw new Exception("Please upload the front side of your driver's license.");
        }

        // Upload license back image (required)
        if (isset($_FILES['license_back_image']) && $_FILES['license_back_image']['error'] === UPLOAD_ERR_OK) {
            $licenseBackImage = uploadFile($_FILES['license_back_image'], $uploadDir . 'licenses/');
        } else {
            throw new Exception("Please upload the back side of your driver's license.");
        }

        // Hash password
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Prepare data
        $dateOfBirth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;
        $middleName = !empty($_POST['middle_name']) ? $_POST['middle_name'] : null;
        $addressLine2 = !empty($_POST['address_line2']) ? $_POST['address_line2'] : null;

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Insert into users table
            $userSql = "INSERT INTO users (
                email, password, user_level, first_name, last_name, middle_name,
                phone_number, date_of_birth, gender, profile_image, status
            ) VALUES (?, ?, 'driver', ?, ?, ?, ?, ?, ?, ?, 'pending_verification')";

            $userStmt = $pdo->prepare($userSql);
            $userStmt->execute([
                $_POST['email'],
                $hashedPassword,
                $_POST['first_name'],
                $_POST['last_name'],
                $middleName,
                $_POST['phone_number'],
                $dateOfBirth,
                $gender,
                $profileImage
            ]);

            $userId = $pdo->lastInsertId();

            // Insert into drivers table
            $driverSql = "INSERT INTO drivers (
                user_id, address_line1, address_line2, city, province, postal_code,
                license_number, license_type, license_expiry_date, license_image, license_back_image,
                driver_status, background_check_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')";

            $driverStmt = $pdo->prepare($driverSql);
            $driverStmt->execute([
                $userId,
                $_POST['address_line1'],
                $addressLine2,
                $_POST['city'],
                $_POST['province'],
                $_POST['postal_code'],
                $_POST['license_number'],
                $_POST['license_type'],
                $_POST['license_expiry_date'],
                $licenseImage,
                $licenseBackImage
            ]);

            // Commit transaction
            $pdo->commit();

            // Redirect to success page
            header("Location: ../../index.php?register=driver&success=1");
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        // Redirect back with error message
        $errorMessage = urlencode($e->getMessage());
        header("Location: ../../index.php?register=driver&error=" . $errorMessage);
        exit();
    }
} else {
    // If not POST request, redirect to registration page
    header("Location: ../../index.php?register=driver");
    exit();
}

// Helper function to handle file uploads
function uploadFile($file, $targetDir) {
    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Invalid file type. Only JPEG, PNG, and GIF images are allowed.");
    }

    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        throw new Exception("File size exceeds 5MB limit.");
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to upload file. Please try again.");
    }

    // Return relative path from project root (for database storage)
    $relativePath = str_replace(__DIR__ . '/../../', '', $targetPath);
    return $relativePath;
}
?>

