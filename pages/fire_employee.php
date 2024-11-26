<?php
include '../database/db_connect.php';
session_start();
echo $_SESSION['user_id'];

if (!isset($_SESSION['user_id'])) {
    die("Admin ID (user_id) is not set in session.");
}

$employee_id = intval($_POST['employee_id'] ?? 0);
$deleted_by = $_SESSION['user_id'];

if (!$employee_id || !$deleted_by) {
    die("Missing required data. Debug:<br>Employee ID: $employee_id<br>Deleted By: $deleted_by");
}

// Begin transaction
$connection->begin_transaction();

try {
    // Debugging step
    echo "Attempting to fire employee with ID $employee_id by admin $deleted_by.<br>";

    // Set @deleted_by session variable in MySQL
    $set_deleted_by_query = "SET @deleted_by = $deleted_by";
    if (!$connection->query($set_deleted_by_query)) {
        throw new Exception("Error setting @deleted_by: " . $connection->error);
    }

    // Delete the employee
    $delete_query = "DELETE FROM Employee WHERE Employee_ID = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param("i", $employee_id);
    if (!$stmt->execute()) {
        throw new Exception("Error deleting employee: " . $stmt->error);
    }

    // Check if the employee was successfully deleted
    if ($stmt->affected_rows > 0) {
        $connection->commit();
        $_SESSION['message'] = "Employee fired successfully.";
        header("Location: dashboard.php");
        exit();
    } else {
        throw new Exception("No rows affected. Employee ID might not exist.");
    }
} catch (Exception $e) {
    // Roll back the transaction if an error occurs
    $connection->rollback();
    die("Error: " . $e->getMessage());
}
?>
