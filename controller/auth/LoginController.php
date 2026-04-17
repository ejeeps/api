<?php
require_once __DIR__ . '/../../config/session.php';

// Database connection using PDO
try {
    require_once __DIR__ . '/../../config/connection.php';
    if (!isset($pdo)) {
        throw new Exception("Database connection not initialized.");
    }
} catch (PDOException $e) {
    // Catch PDOException first (more specific)
    error_log("PDO connection error in LoginController: " . $e->getMessage());
    // Redirect to login page with error
    header('Location: ' . app_index_url(['login' => '1', 'error' => 'Database connection failed. Please try again later.']));
    exit();
} catch (Exception $e) {
    // Catch any other exceptions
    error_log("Database connection error in LoginController: " . $e->getMessage());
    // Redirect to login page with error
    header('Location: ' . app_index_url(['login' => '1', 'error' => 'Database connection failed. Please try again later.']));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        // Validate required fields
        if ($email === '' || $password === '') {
            throw new Exception("Please enter both email and password.");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Get user from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists
        if (!$user) {
            throw new Exception("Invalid email or password.");
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password.");
        }

        // Check account status
        if ($user['status'] === 'suspended') {
            throw new Exception("Your account has been suspended. Please contact support.");
        }

        if ($user['status'] === 'inactive') {
            throw new Exception("Your account is inactive. Please contact support.");
        }

        // Update last login timestamp
        $updateLogin = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateLogin->execute([$user['id']]);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_level'] = strtolower(trim((string) $user['user_level']));
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_status'] = $user['status'];
        $_SESSION['is_verified'] = $user['is_verified'];

        // Set remember me cookie if checked
        if (isset($_POST['remember_me']) && $_POST['remember_me'] === 'on') {
            $cookieValue = base64_encode($user['id'] . ':' . hash('sha256', $user['email'] . $user['password']));
            setcookie('remember_me', $cookieValue, time() + (30 * 24 * 60 * 60), app_session_cookie_path()); // 30 days
        }

        // Redirect based on user level
        $level = strtolower(trim((string) $user['user_level']));
        if ($level === 'driver') {
            // Check if driver profile exists
            $driverCheck = $pdo->prepare("SELECT * FROM drivers WHERE user_id = ?");
            $driverCheck->execute([$user['id']]);
            $driver = $driverCheck->fetch(PDO::FETCH_ASSOC);

            if ($driver) {
                $_SESSION['driver_id'] = $driver['id'];
                $_SESSION['driver_status'] = $driver['driver_status'];
                header('Location: ' . app_index_url());
            } else {
                // Driver profile not complete, redirect to registration
                header('Location: ' . app_index_url(['register' => 'driver', 'error' => 'Please complete your driver profile.']));
            }
        } else if ($level === 'passenger') {
            // Check if passenger profile exists
            $passengerCheck = $pdo->prepare("SELECT * FROM passengers WHERE user_id = ?");
            $passengerCheck->execute([$user['id']]);
            $passenger = $passengerCheck->fetch(PDO::FETCH_ASSOC);

            if ($passenger) {
                $_SESSION['passenger_id'] = $passenger['id'];
                header('Location: ' . app_index_url());
            } else {
                // Passenger profile not complete, redirect to registration
                header('Location: ' . app_index_url(['register' => 'passenger', 'error' => 'Please complete your passenger profile.']));
            }
        } else if ($level === 'admin') {
            header('Location: ' . app_index_url());
        } else {
            header('Location: ' . app_index_url(['error' => 'Invalid user level.']));
        }
        exit();

    } catch (Exception $e) {
        // Redirect back with error message
        header('Location: ' . app_index_url(['login' => '1', 'error' => $e->getMessage()]));
        exit();
    }
} else {
    // If not POST request, redirect to login page
    header('Location: ' . app_index_url(['login' => '1']));
    exit();
}
?>

