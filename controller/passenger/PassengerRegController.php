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
            'phone_number', 'id_number', 'terms'
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

        // Check if ID number already exists
        $checkIdNumber = $pdo->prepare("SELECT id FROM passengers WHERE id_number = ?");
        $checkIdNumber->execute([$_POST['id_number']]);
        if ($checkIdNumber->rowCount() > 0) {
            throw new Exception("ID number already registered. Please use a different ID number or contact support.");
        }

        // Handle file uploads
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $profileImage = null;
        $idImageFront = null;
        $idImageBack = null;

        // Upload ID front image (required)
        if (isset($_FILES['id_image_front']) && $_FILES['id_image_front']['error'] === UPLOAD_ERR_OK) {
            $idImageFront = uploadFile($_FILES['id_image_front'], $uploadDir . 'ids/');
        } else {
            throw new Exception("Please upload the front side of your ID.");
        }

        // Upload ID back image (required)
        if (isset($_FILES['id_image_back']) && $_FILES['id_image_back']['error'] === UPLOAD_ERR_OK) {
            $idImageBack = uploadFile($_FILES['id_image_back'], $uploadDir . 'ids/');
        } else {
            throw new Exception("Please upload the back side of your ID.");
        }

        // Upload profile image if provided
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $profileImage = uploadFile($_FILES['profile_image'], $uploadDir . 'profiles/');
        }

        // Hash password
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Prepare data
        $dateOfBirth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;
        $middleName = !empty($_POST['middle_name']) ? $_POST['middle_name'] : null;
        $addressLine1 = !empty($_POST['address_line1']) ? $_POST['address_line1'] : null;
        $addressLine2 = !empty($_POST['address_line2']) ? $_POST['address_line2'] : null;
        $city = !empty($_POST['city']) ? $_POST['city'] : null;
        $province = !empty($_POST['province']) ? $_POST['province'] : null;
        $postalCode = !empty($_POST['postal_code']) ? $_POST['postal_code'] : null;

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Insert into users table
            $userSql = "INSERT INTO users (
                email, password, user_level, first_name, last_name, middle_name,
                phone_number, date_of_birth, gender, profile_image, status
            ) VALUES (?, ?, 'passenger', ?, ?, ?, ?, ?, ?, ?, 'active')";

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

            // Insert into passengers table
            $passengerSql = "INSERT INTO passengers (
                user_id, id_number, id_image_front, id_image_back, address_line1, address_line2, city, province, postal_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $passengerStmt = $pdo->prepare($passengerSql);
            $passengerStmt->execute([
                $userId,
                $_POST['id_number'],
                $idImageFront,
                $idImageBack,
                $addressLine1,
                $addressLine2,
                $city,
                $province,
                $postalCode
            ]);

            // Commit transaction
            $pdo->commit();

            // Redirect to success page
            header("Location: ../../index.php?register=passenger&success=1");
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        // Redirect back with error message
        $errorMessage = urlencode($e->getMessage());
        header("Location: ../../index.php?register=passenger&error=" . $errorMessage);
        exit();
    }
} else {
    // If not POST request, redirect to registration page
    header("Location: ../../index.php?register=passenger");
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

