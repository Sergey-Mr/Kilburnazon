<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and if they are from the Executive department
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

$email = $_SESSION['email'];
$employee_id = null;
$department = null; // Variable to store the user's department

// Retrieve the employee's department based on email
$query = "
    SELECT Employee.Employee_ID, Department.Name AS Department
    FROM Employee
    JOIN Department ON Employee.Department_ID = Department.Department_ID
    WHERE Employee.Email = ?
";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $employee_id = $row['Employee_ID'];
    $department = $row['Department']; // Department name from the Department table
} else {
    die("Employee not found.");
}

// Check if the user is from the Executive department
if ($department !== 'Executive') {
    die("You do not have access to this page.");
}

// Handle form submission to update the leave request status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['status_update'])) {
        // Loop through each request and update the status
        foreach ($_POST['status'] as $request_id => $status) {
            $query = "UPDATE Vacation SET Status = ? WHERE Vacation_ID = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("si", $status, $request_id);
            $stmt->execute();
        }
        $message = "Leave requests updated successfully.";
    }
}

// Fetch all pending leave requests
$query = "SELECT Vacation_ID, Employee_ID, Leave_Type, Start_Date, End_Date, Status, Days_Allocated, Message FROM Vacation WHERE Status = 'pending'";
$stmt = $connection->prepare($query);
$stmt->execute();
$leave_requests_result = $stmt->get_result();

// Check if there are no pending leave requests
$no_pending_requests = ($leave_requests_result->num_rows == 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../pages/components/navbar.php'; ?>

    <div class="container mt-5">
        <h2>Leave Management</h2>

        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Display message if no pending leave requests -->
        <?php if ($no_pending_requests): ?>
            <div class="alert alert-warning">No pending leave requests.</div>
        <?php else: ?>
            <!-- Leave Request Management Form -->
            <form action="leave_management.php" method="POST">
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days Allocated</th>
                            <th>Comments</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $leave_requests_result->fetch_assoc()): ?>
                            <tr id="row-<?php echo $row['Vacation_ID']; ?>" class="<?php echo ($row['Status'] == 'approved') ? 'table-success' : (($row['Status'] == 'declined') ? 'table-danger' : ''); ?>">
                                <td><?php echo htmlspecialchars($row['Leave_Type']); ?></td>
                                <td><?php echo htmlspecialchars($row['Start_Date']); ?></td>
                                <td><?php echo htmlspecialchars($row['End_Date']); ?></td>
                                <td><?php echo htmlspecialchars($row['Days_Allocated']); ?></td>
                                <td><?php echo htmlspecialchars($row['Message']); ?></td>
                                <td>
                                    <select name="status[<?php echo $row['Vacation_ID']; ?>]" class="form-control" onchange="updateRowStatus(<?php echo $row['Vacation_ID']; ?>, this)">
                                        <option value="pending" <?php echo ($row['Status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo ($row['Status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                        <option value="declined" <?php echo ($row['Status'] == 'declined') ? 'selected' : ''; ?>>Declined</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <button type="submit" name="status_update" class="btn btn-primary">Update Status</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function updateRowStatus(vacationId, selectElement) {
            var row = document.getElementById('row-' + vacationId);
            var status = selectElement.value;
            
            // Remove any previous status classes
            row.classList.remove('table-success', 'table-danger');

            // Add the appropriate class based on the selected status
            if (status === 'approved') {
                row.classList.add('table-success');
            } else if (status === 'declined') {
                row.classList.add('table-danger');
            } else {
                row.classList.remove('table-success', 'table-danger'); // Reset if status is pending
            }
        }
    </script>
</body>
</html>
