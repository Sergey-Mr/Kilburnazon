<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include '../database/db_connect.php';

// Initialize employee details variables
$employee_id = $salary_increase_percent = "";
$employee_name = $current_position = $current_salary = "";

// Search employee functionality
if (isset($_POST['search_employee'])) {
    $search_name = $_POST['employee_name'];

    // Query to search employee by name and fetch position name from the Position table
    $sql = "SELECT e.Employee_ID, e.Name, e.Position_ID, e.Salary, p.Role_Name 
            FROM Employee e
            JOIN Employee_Position p ON e.Position_ID = p.Position_ID
            WHERE e.Name LIKE ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('s', $search_name);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch matching employees
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Process promotion (updating salary)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_employee'])) {
    // Collect form data
    $employee_id = $_POST['employee_id'];
    $salary_increase_percent = $_POST['salary_increase'];

    // Fetch current salary of the employee
    $sql = "SELECT Salary FROM Employee WHERE Employee_ID = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();

    if ($employee) {
        // Calculate the new salary with the increase
        $current_salary = $employee['Salary'];
        $new_salary = $current_salary * (1 + $salary_increase_percent / 100);

        // Update the employee's salary in the database
        $sql = "UPDATE Employee SET Salary = ? WHERE Employee_ID = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param('di', $new_salary, $employee_id);

        if ($stmt->execute()) {
            $message = "Employee promoted successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
    } else {
        $message = "Employee not found!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promote Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Function to calculate the new salary dynamically
        function calculateNewSalary() {
            const salary = parseFloat(document.getElementById('employee_salary').value);
            const increasePercent = parseFloat(document.getElementById('salary_increase').value);
            const newSalary = salary * (1 + increasePercent / 100);

            document.getElementById('new_salary').value = newSalary.toFixed(2);
        }
    </script>
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
        <h2>Promote Employee</h2>

        <?php if (isset($message)): ?>
            <div class="alert alert-info">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Search Form -->
        <form action="promote_employee.php" method="POST">
            <div class="form-group">
                <label for="employee_name">Search Employee by Name</label>
                <input type="text" name="employee_name" id="employee_name" class="form-control" required>
                <button type="submit" name="search_employee" class="btn btn-primary mt-2">Search</button>
            </div>
        </form>

        <!-- Display search results -->
        <?php if (isset($employees) && count($employees) > 0): ?>
            <h4 class="mt-4">Select Employee</h4>
            <ul class="list-group">
                <?php foreach ($employees as $employee): ?>
                    <li class="list-group-item">
                        <a href="#" class="employee-select" data-id="<?= $employee['Employee_ID']; ?>"
                           data-name="<?= $employee['Name']; ?>"
                           data-position="<?= $employee['Role_Name']; ?>"
                           data-salary="<?= $employee['Salary']; ?>">
                            <?= $employee['Name']; ?> (Position: <?= $employee['Role_Name']; ?>, Salary: $<?= number_format($employee['Salary'], 2); ?>)
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Promotion Form -->
        <form action="promote_employee.php" method="POST" id="promotion_form" style="display: none;">
            <input type="hidden" name="employee_id" id="employee_id">
            <div class="form-group">
                <label for="employee_position">Current Position</label>
                <input type="text" name="employee_position" id="employee_position" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label for="employee_salary">Current Salary</label>
                <input type="number" name="employee_salary" id="employee_salary" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label for="salary_increase">Salary Increase (%)</label>
                <input type="number" name="salary_increase" id="salary_increase" class="form-control" required
                       onchange="calculateNewSalary()" />
            </div>
            <div class="form-group">
                <label for="new_salary">New Salary</label>
                <input type="text" name="new_salary" id="new_salary" class="form-control" readonly>
            </div>
            <button type="submit" name="promote_employee" class="btn btn-success mt-3">Promote Employee</button>
        </form>
    </div>

    <script>
        // Handle employee selection
        const employeeLinks = document.querySelectorAll('.employee-select');
        employeeLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const employee_id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const position = this.getAttribute('data-position');
                const salary = this.getAttribute('data-salary');

                // Set the employee details in the form
                document.getElementById('employee_id').value = employee_id;
                document.getElementById('employee_position').value = position;
                document.getElementById('employee_salary').value = salary; // Set current salary
                document.getElementById('salary_increase').value = ''; // Reset salary increase field
                document.getElementById('new_salary').value = ''; // Reset new salary
                document.getElementById('promotion_form').style.display = 'block'; // Show promotion form
            });
        });
    </script>
</body>
</html>
