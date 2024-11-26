<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

include '../database/db_connect.php';

// Retrieve the logged-in user's role
$email = $_SESSION['email'];
$query = "
    SELECT d.Name AS Department_Name
    FROM Employee e
    LEFT JOIN Department d ON e.Department_ID = d.Department_ID
    WHERE e.Email = ?
";
$access = False; 
$stmt = $connection->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $department_name = $row['Department_Name'];
    if ($department_name == 'Executive') {
        // User is not in the Executive department
        $access = True;
        echo `Executive`;
    }
} else {
    // Department not found or query failed
    $access = False;
}

// If access is False, redirect to request_leave.php
if (!$access) {
    header("Location: request_leave.php");
    exit();
}

$message = "";
$toastClass = '';
$birthday_employees = [];

// Fetch employees with birthdays in the current month
$query = "
    SELECT 
        Employee_ID, 
        Name, 
        DOB, 
        Email 
    FROM 
        Employee 
    WHERE 
        MONTH(DOB) = MONTH(CURDATE())
";

$result = $connection->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $birthday_employees[] = $row;
    }
} else {
    $message = "Error fetching data: " . $connection->error;
    $toastClass = 'bg-danger';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birthday Cards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php
    include '../pages/components/navbar.php';
    ?>
    

    <!-- Content -->
    <div class="container mt-5">
        <h2>Birthday Cards</h2>
        <?php if ($message): ?>
            <div class="alert <?php echo $toastClass; ?> text-white">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Birthday List -->
        <?php if (!empty($birthday_employees)): ?>
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Date of Birth</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($birthday_employees as $employee): ?>
                            <tr>
                                <td><?php echo $employee['Employee_ID']; ?></td>
                                <td><?php echo $employee['Name']; ?></td>
                                <td><?php echo date("d-m-Y", strtotime($employee['DOB'])); ?></td>
                                <td><?php echo $employee['Email']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>No birthdays found this month!</strong>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
