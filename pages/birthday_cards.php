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
    <nav class="navbar navbar-expand-sm navbar-light bg-success">
        <div class="container">
            <a class="navbar-brand" href="#" style="font-weight:bold; color:white;">Dashboard</a>
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapsibleNavId" aria-controls="collapsibleNavId" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="collapsibleNavId">
                <ul class="navbar-nav m-auto mt-2 mt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="dashboard.php">Employee Directory</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="payroll.php">Payroll</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="add_employee.php">Add New Employee</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="promote_employee.php">Promote Employee</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="birthday_cards.php">Birthday Cards</a>
                    </li>
                </ul>
                <form class="d-flex my-2 my-lg-0">
                    <a href="./logout.php" class="btn btn-light my-2 my-sm-0" style="font-weight:bolder;color:green;">
                        Logout</a>
                </form>
            </div>
        </div>
    </nav>

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
