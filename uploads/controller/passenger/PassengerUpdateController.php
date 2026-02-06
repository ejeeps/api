<?php
// Database connection using PDO
require_once __DIR__ . '/../../config/connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'passenger') {
    header("Location: ../../index.php?login=1&error=" . urlencode("Please login to access this page."));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $userId = $_SESSION['user_id'];

        // Validate required fields
        $requiredFields = ['first_name', 'last_name', 'phone_number'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Validate phone number format (basic validation)
        if (!preg_match('/^[0-9+\-\s()]+$/', $_POST['phone_number'])) {
            throw new Exception("Invalid phone number format.");
        }

        // Handle file uploads
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $profileImage = null;

        // Upload profile image if provided
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $profileImage = uploadFile($_FILES['profile_image'], $uploadDir . 'profiles/');
        }

        // Prepare data
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $middleName = !empty($_POST['middle_name']) ? trim($_POST['middle_name']) : null;
        $phoneNumber = trim($_POST['phone_number']);
        $dateOfBirth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;
        $addressLine1 = !empty($_POST['address_line1']) ? trim($_POST['address_line1']) : null;
        $addressLine2 = !empty($_POST['address_line2']) ? trim($_POST['address_line2']) : null;
        $city = !empty($_POST['city']) ? trim($_POST['city']) : null;
        $province = !empty($_POST['province']) ? trim($_POST['province']) : null;
        $postalCode = !empty($_POST['postal_code']) ? trim($_POST['postal_code']) : null;

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Update users table
            if ($profileImage) {
                // Get old profile image to delete it later
                $oldImageStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                $oldImageStmt->execute([$userId]);
                $oldImage = $oldImageStmt->fetchColumn();

                $userSql = "UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    middle_name = ?, 
                    phone_number = ?, 
                    date_of_birth = ?, 
                    gender = ?, 
                    profile_image = ?
                    WHERE id = ?";

                $userStmt = $pdo->prepare($userSql);
                $userStmt->execute([
                    $firstName,
                    $lastName,
                    $middleName,
                    $phoneNumber,
                    $dateOfBirth,
                    $gender,
                    $profileImage,
                    $userId
                ]);

                // Delete old profile image if it exists
                if ($oldImage && file_exists(__DIR__ . '/../../' . $oldImage)) {
                    @unlink(__DIR__ . '/../../' . $oldImage);
                }
            } else {
                $userSql = "UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    middle_name = ?, 
                    phone_number = ?, 
                    date_of_birth = ?, 
                    gender = ?
                    WHERE id = ?";

                $userStmt = $pdo->prepare($userSql);
                $userStmt->execute([
                    $firstName,
                    $lastName,
                    $middleName,
                    $phoneNumber,
                    $dateOfBirth,
                    $gender,
                    $userId
                ]);
            }

            // Update passengers table
            $passengerSql = "UPDATE passengers SET 
                address_line1 = ?, 
                address_line2 = ?, 
                city = ?, 
                province = ?, 
                postal_code = ?
                WHERE user_id = ?";

            $passengerStmt = $pdo->prepare($passengerSql);
            $passengerStmt->execute([
                $addressLine1,
                $addressLine2,
                $city,
                $province,
                $postalCode,
                $userId
            ]);

            // Commit transaction
            $pdo->commit();

            // Determine redirect path
            if (isset($_POST['dashboard_view']) || isset($_GET['dashboard_view'])) {
                header("Location: index.php?page=settings&success=1");
            } else {
                header("Location: ../../view/passenger/settings.php?success=1");
            }
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        // Redirect back with error message
        $errorMessage = urlencode($e->getMessage());
        if (isset($_POST['dashboard_view']) || isset($_GET['dashboard_view'])) {
            header("Location: index.php?page=settings&error=" . $errorMessage);
        } else {
            header("Location: ../../view/passenger/settings.php?error=" . $errorMessage);
        }
        exit();
    }
} else {
    // If not POST request, redirect to settings page
    if (isset($_GET['dashboard_view'])) {
        header("Location: index.php?page=settings");
    } else {
        header("Location: ../../view/passenger/settings.php");
    }
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

