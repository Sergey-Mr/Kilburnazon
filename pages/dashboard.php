<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in, if not, redirect to the login page
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Include the database connection
include '../database/db_connect.php';

// Fetch employee data for display
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
    $filters[] = "o.Name LIKE CONCAT('%', ?, '%')"; // Correctly using `o.Name`
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

    <div class="container mt-5">
        <h2>Welcome To Dashboard</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>


        <!-- Search Filters Section -->
        <div class="mt-5">
            <h3>Search Employees</h3>
            <form method="get" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Enter name" value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" name="department" id="department" class="form-control" placeholder="Enter department" value="<?php echo htmlspecialchars($_GET['department'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="job_title" class="form-label">Job Title</label>
                        <input type="text" name="job_title" id="job_title" class="form-control" placeholder="Enter job title" value="<?php echo htmlspecialchars($_GET['job_title'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="office" class="form-label">Office</label>
                        <input type="text" name="office" id="office" class="form-control" placeholder="Enter office" value="<?php echo htmlspecialchars($_GET['office'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="submit" class="btn btn-success w-100">Search</button>
                    </div>
                </div>
            </form>

            <!-- Employee Cards -->
            <div class="row">
                <?php if (count($employees) > 0): ?>
                    <?php foreach ($employees as $employee): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card shadow">
                                <div class="card-body">
                                    <!-- Placeholder Image -->
                                    <img src="photo_placeholder.png" alt="Employee Photo" class="card-img-top mb-3" style="height: 150px; object-fit: cover;">

                                    <h5 class="card-title"><?php echo htmlspecialchars($employee['Name'] ?? ''); ?></h5>
                                    <p class="card-text">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($employee['Email']); ?><br>
                                        <strong>Position:</strong> <?php echo htmlspecialchars($employee['Position'] ?? 'N/A'); ?><br>
                                        <strong>Department:</strong> <?php echo htmlspecialchars($employee['Department'] ?? 'N/A'); ?><br>
                                        <strong>Office:</strong> <?php echo htmlspecialchars($employee['Office'] ?? 'N/A'); ?><br>
                                        <strong>Hired Date:</strong> <?php echo htmlspecialchars($employee['Hired_Date']); ?>
                                    </p>
                                    <!-- Modal Trigger Button -->
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal<?php echo $employee['Employee_ID']; ?>">
                                        View Details
                                    </button>
                                </div>
                            </div>

                            <!-- Employee Modal -->
                            <div class="modal fade" id="employeeModal<?php echo $employee['Employee_ID']; ?>" tabindex="-1" aria-labelledby="employeeModalLabel<?php echo $employee['Employee_ID']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="employeeModalLabel<?php echo $employee['Employee_ID']; ?>">
                                                <?php echo htmlspecialchars($employee['Name']); ?> - Details
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <!-- Employee Modal -->
                                        <div class="modal-body">
                                            <img src="photo_placeholder.png" alt="Employee Photo" class="card-img-top mb-3" style="height: 120px; width: 120px; object-fit: cover;">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['Name']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['Email']); ?></p>
                                            <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($employee['DOB'] ?? 'N/A'); ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($employee['Address'] ?? 'N/A'); ?></p>
                                            <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['Position'] ?? 'N/A'); ?></p>
                                            <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['Department'] ?? 'N/A'); ?></p>
                                            <p><strong>Office:</strong> <?php echo htmlspecialchars($employee['Office'] ?? 'N/A'); ?></p>
                                            <p><strong>Salary:</strong> <?php echo htmlspecialchars($employee['Salary'] ?? 'N/A'); ?></p>
                                            <p><strong>Hired Date:</strong> <?php echo htmlspecialchars($employee['Hired_Date']); ?></p>
                                            <p><strong>Contract Type:</strong> <?php echo htmlspecialchars($employee['Contract_Type'] ?? 'N/A'); ?></p>

                                            <!-- Emergency Contact Information -->
                                            <p><strong>Emergency Contact Name:</strong> <?php echo htmlspecialchars($employee['Contact_Name'] ?? 'N/A'); ?></p>
                                            <p><strong>Relationship:</strong> <?php echo htmlspecialchars($employee['Relationship'] ?? 'N/A'); ?></p>
                                            <p><strong>Emergency Contact Phone:</strong> <?php echo htmlspecialchars($employee['Phone'] ?? 'N/A'); ?></p>

                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <!-- Fire Employee Button -->
                                            <form method="post" action="fire_employee.php" onsubmit="return confirm('Are you sure you want to fire this employee?');">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['Employee_ID']; ?>">
                                                <button type="submit" class="btn btn-danger">Fire Employee</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">No employees found matching your search criteria.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

