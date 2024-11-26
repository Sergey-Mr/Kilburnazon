<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include '../database/db_connect.php';

// Fetch available positions, departments, and offices for the dropdowns
$positions_result = $connection->query("SELECT Position_ID, Role_Name FROM Employee_Position");
$departments_result = $connection->query("SELECT Department_ID, Name FROM Department");
$offices_result = $connection->query("SELECT Office_ID, Name FROM Office");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $dob = $_POST['dob'];
    $position_id = $_POST['position'];
    $salary = $_POST['salary'];
    $department_id = $_POST['department'];
    $office_id = $_POST['office'];
    $contract_type = $_POST['contract_type'];
    $nin = $_POST['nin'];
    $emergency_contact_name = $_POST['emergency_contact_name'];
    $emergency_contact_phone = $_POST['emergency_contact_phone'];
    $emergency_contact_relationship = $_POST['emergency_contact_relationship'];

    // Default Date Hired to today
    $date_hired = date('Y-m-d');

    // Check if email already exists
    $check_email_query = "SELECT * FROM Employee WHERE Email = ?";
    $stmt_check_email = $connection->prepare($check_email_query);
    $stmt_check_email->bind_param('s', $email);
    $stmt_check_email->execute();
    $result = $stmt_check_email->get_result();

    if ($result->num_rows > 0) {
        // Email already exists
        $_SESSION['error'] = "Error: The email address is already in use.";
    } else {
        // Prepare SQL to insert the new employee
        $sql = "INSERT INTO Employee (Name, Email, DOB, Position_ID, Salary, Department_ID, Office_ID, Contract_Type, NIN, Hired_Date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param('ssssdsdsss', $name, $email, $dob, $position_id, $salary, $department_id, $office_id, $contract_type, $nin, $date_hired);

        try {
            if ($stmt->execute()) {
                // Now insert the emergency contact data
                $employee_id = $stmt->insert_id; // Get the last inserted employee ID

                // Insert the emergency contact
                $sql_emergency_contact = "INSERT INTO Emergency_Contact (Employee_ID, Contact_Name, Phone, Relationship) 
                                          VALUES (?, ?, ?, ?)";
                $stmt_emergency = $connection->prepare($sql_emergency_contact);
                $stmt_emergency->bind_param('isss', $employee_id, $emergency_contact_name, $emergency_contact_phone, $emergency_contact_relationship);
                $stmt_emergency->execute();

                // Get the newly created contact ID
                $new_contact_id = $connection->insert_id;

                // Update the Employee table with the new Emergency_Contact_ID
                $update_employee_contact_id_query = "
                    UPDATE Employee
                    SET Emergency_Contact_ID = ?
                    WHERE Employee_ID = ?";
                $stmt_update_contact_id = $connection->prepare($update_employee_contact_id_query);
                $stmt_update_contact_id->bind_param("ii", $new_contact_id, $employee_id);
                $stmt_update_contact_id->execute();

                // Success message
                $_SESSION['message'] = "New employee added successfully with emergency contact!";
            }
        } catch (mysqli_sql_exception $e) {
            // Catch any other SQL exceptions and show an error message
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <!-- Display messages -->
        <?php
            if (!empty($_SESSION['message'])) {
                echo "<div class='alert alert-success'>" . $_SESSION['message'] . "</div>";
                unset($_SESSION['message']);
            }
            if (!empty($_SESSION['error'])) {
                echo "<div class='alert alert-danger'>" . $_SESSION['error'] . "</div>";
                unset($_SESSION['error']);
            }
        ?>

        <h2>Add New Employee</h2>

        <form action="add_employee.php" method="POST">
            <!-- Employee Details -->
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" name="dob" id="dob" class="form-control" required>
            </div>

            <!-- Dropdown for Position -->
            <div class="form-group">
                <label for="position">Position</label>
                <select name="position" id="position" class="form-control" required>
                    <option value="">Select Position</option>
                    <?php while ($row = $positions_result->fetch_assoc()) { ?>
                        <option value="<?= $row['Position_ID']; ?>"><?= $row['Role_Name']; ?></option>
                    <?php } ?>
                </select>
            </div>

            <!-- Dropdown for Department -->
            <div class="form-group">
                <label for="department">Department</label>
                <select name="department" id="department" class="form-control" required>
                    <option value="">Select Department</option>
                    <?php while ($row = $departments_result->fetch_assoc()) { ?>
                        <option value="<?= $row['Department_ID']; ?>"><?= $row['Name']; ?></option>
                    <?php } ?>
                </select>
            </div>

            <!-- Dropdown for Office -->
            <div class="form-group">
                <label for="office">Office</label>
                <select name="office" id="office" class="form-control" required>
                    <option value="">Select Office</option>
                    <?php while ($row = $offices_result->fetch_assoc()) { ?>
                        <option value="<?= $row['Office_ID']; ?>"><?= $row['Name']; ?></option>
                    <?php } ?>
                </select>
            </div>

            <!-- Other Fields -->
            <div class="form-group">
                <label for="salary">Salary</label>
                <input type="number" name="salary" id="salary" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="contract_type">Contract Type</label>
                <input type="text" name="contract_type" id="contract_type" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="nin">NIN</label>
                <input type="text" name="nin" id="nin" class="form-control" required>
            </div>

            <!-- Emergency Contact Details -->
            <div class="form-group">
                <label for="emergency_contact_name">Emergency Contact Name</label>
                <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="emergency_contact_phone">Emergency Contact Phone</label>
                <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="emergency_contact_relationship">Relationship to Employee</label>
                <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Add Employee</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
</body>
</html>
