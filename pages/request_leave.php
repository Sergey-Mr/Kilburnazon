<?php
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

include '../database/db_connect.php';

// Get user details from the session
$email = $_SESSION['email'];
$employee_id = null;
$office_id = null;

// Retrieve employee data from the database
$query = "SELECT Employee_ID, Office_ID FROM Employee WHERE Email = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $employee_id = $row['Employee_ID'];
    $office_id = $row['Office_ID'];
} else {
    die("Employee not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form data
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $comments = $_POST['comments'];

    if (empty($leave_type) || empty($start_date) || empty($end_date)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: dashboard.php");
        exit;
    } elseif ($start_date > $end_date) {
        $_SESSION['error'] = "Start date cannot be after end date.";
        header("Location: dashboard.php");
        exit;
    } else {
        // Insert leave request into the database
        if (!empty($start_date) && !empty($end_date)) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            $days_allocated = $interval->days + 1; // Include the start date
        } else {
            $days_allocated = 0; // Default value if dates are missing
        }        
        $status = 'pending';
        $query = "INSERT INTO Vacation (Employee_ID, Leave_Type, Start_Date, End_Date, Status, Days_Allocated, Message, Office_ID) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("issssdsd", $employee_id, $leave_type, $start_date, $end_date, $status, $days_allocated, $comments, $office_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Leave request submitted successfully. Awaiting approval.";
            header("Location: dashboard.php");  // Redirect to avoid resubmission
            exit;
        } else {
            $_SESSION['error'] = "Failed to submit leave request. Error: " . $stmt->error;
            header("Location: dashboard.php");
            exit;
        }
    }
}


// Fetch all leave requests for the logged-in employee
$query = "SELECT Leave_Type, Start_Date, End_Date, Status, Days_Allocated, Message FROM Vacation WHERE Employee_ID = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$leave_requests_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Request Leave</h2>

        <!-- Display success/error message -->
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Leave Request Form -->
        <form action="dashboard.php" method="POST">
            <div class="mb-3">
                <label for="leave_type" class="form-label">Leave Type</label>
                <select name="leave_type" id="leave_type" class="form-control" required>
                    <option value="">Select Leave Type</option>
                    <option value="sick">Sick</option>
                    <option value="vacation">Vacation</option>
                    <option value="personal">Personal</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="comments" class="form-label">Comments</label>
                <textarea name="comments" id="comments" class="form-control" rows="4"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>

        <!-- Display Leave Requests -->
        <h3 class="mt-5">Your Leave Requests</h3>
        <?php if ($leave_requests_result->num_rows > 0): ?>
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
                        <tr class="<?php echo ($row['Status'] == 'approved') ? 'table-success' : (($row['Status'] == 'declined') ? 'table-danger' : ''); ?>">
                            <td><?php echo htmlspecialchars($row['Leave_Type']); ?></td>
                            <td><?php echo htmlspecialchars($row['Start_Date']); ?></td>
                            <td><?php echo htmlspecialchars($row['End_Date']); ?></td>
                            <td><?php echo htmlspecialchars($row['Days_Allocated']); ?></td>
                            <td><?php echo htmlspecialchars($row['Message']); ?></td>
                            <td><?php echo ucfirst($row['Status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no leave requests.</p>
        <?php endif; ?>
    </div>
</body>
</html>
