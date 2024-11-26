<?php 
include '../database/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in, if not, redirect to the login page
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}


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

// Count the number of pending leave requests
$query = "SELECT COUNT(*) as pending_count FROM Vacation WHERE Status = 'pending'";
$stmt = $connection->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$pending_requests = 0;

if ($result && $row = $result->fetch_assoc()) {
    $pending_requests = $row['pending_count'];
}

?>

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
            <?php if ($access): ?>
                <!-- Options for when $access is true -->
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
                <li class="nav-item">
                    <a class="nav-link text-white" href="leave_management.php">
                        Leave Requests 
                        <?php if ($pending_requests > 0): ?>
                            <span class="badge bg-danger"><?php echo $pending_requests; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="absenteeism_report.php">Abstenteeims Report</a>
                </li>
            <?php else: ?>
                <!-- Options for when $access is false -->
                <li class="nav-item">
                    <a class="nav-link text-white" href="request_leave.php">Request a leave</a>
                </li>
            <?php endif; ?>
        </ul>
            <form class="d-flex my-2 my-lg-0">
                <a href="./logout.php" class="btn btn-light my-2 my-sm-0" style="font-weight:bolder;color:green;">
                    Logout</a>
            </form>
        </div>
    </div>
</nav>