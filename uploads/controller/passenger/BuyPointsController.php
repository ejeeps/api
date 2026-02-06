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
        if (empty($_POST['amount']) || empty($_POST['payment_method'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $amount = floatval($_POST['amount']);

        // Validate amount
        if ($amount < 50) {
            throw new Exception("Minimum reload amount is ₱50.00.");
        }

        if ($amount > 10000) {
            throw new Exception("Maximum reload amount is ₱10,000.00.");
        }

        $paymentMethod = $_POST['payment_method'];

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Get current balance
            $stmt = $pdo->prepare("SELECT card_balance FROM passengers WHERE user_id = ?");
            $stmt->execute([$userId]);
            $currentBalance = $stmt->fetchColumn() ?? 0.00;

            // Update balance
            $newBalance = $currentBalance + $amount;
            $updateStmt = $pdo->prepare("UPDATE passengers SET card_balance = ? WHERE user_id = ?");
            $updateStmt->execute([$newBalance, $userId]);

            // TODO: Create a transactions table and insert transaction record here
            // Example:
            // $transactionStmt = $pdo->prepare("INSERT INTO transactions (passenger_id, type, amount, payment_method, status, description) VALUES (?, 'reload', ?, ?, 'completed', ?)");
            // $transactionStmt->execute([$userId, $amount, $paymentMethod, "Card reload via " . ucfirst($paymentMethod)]);

            // Commit transaction
            $pdo->commit();

            // Determine redirect path
            if (isset($_POST['dashboard_view']) || isset($_GET['dashboard_view'])) {
                header("Location: index.php?page=buypoints&success=1");
            } else {
                header("Location: ../../view/passenger/buypoints.php?success=1");
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
            header("Location: index.php?page=buypoints&error=" . $errorMessage);
        } else {
            header("Location: ../../view/passenger/buypoints.php?error=" . $errorMessage);
        }
        exit();
    }
} else {
    // If not POST request, redirect to buy points page
    if (isset($_GET['dashboard_view'])) {
        header("Location: index.php?page=buypoints");
    } else {
        header("Location: ../../view/passenger/buypoints.php");
    }
    exit();
}
?>

