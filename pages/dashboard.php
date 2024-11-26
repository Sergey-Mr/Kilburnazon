<?php
include '../database/db_connect.php';
session_start();

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
    // Check if the department is Executive
    if ($department_name == 'Executive') {
        // User is in the Executive department, set access to True
        $access = True;
    }
} else {
    // Department not found or query failed
    $access = False;
}

// Fetch employee data for display with filters
$filters = [];
$params = [];
$types = '';

// Apply filters based on user input
if (!empty($_GET['name'])) {
    $filters[] = "e.Name LIKE CONCAT('%', ?, '%')";
    $params[] = $_GET['name'];
    $types .= 's';
}
if (!empty($_GET['department'])) {
    $filters[] = "d.Name LIKE CONCAT('%', ?, '%')";
    $params[] = $_GET['department'];
    $types .= 's';
}
if (!empty($_GET['job_title'])) {
    $filters[] = "p.Role_Name LIKE CONCAT('%', ?, '%')";
    $params[] = $_GET['job_title'];
    $types .= 's';
}
if (!empty($_GET['office'])) {
    $filters[] = "o.Name LIKE CONCAT('%', ?, '%')";
    $params[] = $_GET['office'];
    $types .= 's';
}
if (!empty($_GET['start_date'])) {
    $filters[] = "e.Hired_Date >= ?";
    $params[] = $_GET['start_date'];
    $types .= 's';
}

// Combine filters into a WHERE clause
$whereClause = count($filters) > 0 ? 'WHERE ' . implode(' AND ', $filters) : '';

$sql = "SELECT e.Employee_ID, e.Name, e.Email, e.DOB, e.Salary, e.Hired_Date, e.Contract_Type, e.NIN, e.Address, 
               p.Role_Name AS Position, d.Name AS Department, o.Name AS Office,
               ec.Contact_Name, ec.Relationship, ec.Phone
        FROM Employee e 
        LEFT JOIN Employee_Position p ON e.Position_ID = p.Position_ID 
        LEFT JOIN Department d ON e.Department_ID = d.Department_ID 
        LEFT JOIN Office o ON e.Office_ID = o.Office_ID
        LEFT JOIN Emergency_Contact ec ON e.Emergency_Contact_ID = ec.Contact_ID
        $whereClause";

$stmt = $connection->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$employees = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <?php include '../pages/components/navbar.php'; ?>

    <div class="container mt-5">
        <h2>Welcome To Dashboard</h2>

        <?php
            // Display success or error message from session if any
            if (!empty($_SESSION['message'])) {
                echo "<div class='alert alert-success'>" . $_SESSION['message'] . "</div>";
                unset($_SESSION['message']);
            }
            if (!empty($_SESSION['error'])) {
                echo "<div class='alert alert-danger'>" . $_SESSION['error'] . "</div>";
                unset($_SESSION['error']);
            }
        ?>

        <?php
        // Set $access based on the user's department
        if ($access) {
            include '../pages/components/employee_search.php';  // Display employee search if access is granted
        } else {
            include 'request_leave.php';  // Show leave request page if access is denied
        }
        ?>
    </div>
</body>
</html>
