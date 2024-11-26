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

// Handle date range filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default: start of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Default: end of current month

$query = "
    SELECT 
        d.Name AS Department,
        e.Name AS Name,
        SUM(DATEDIFF(v.End_Date, v.Start_Date) + 1) AS Total_Days_Absent,
        v.Leave_Type,
        COUNT(v.Vacation_ID) AS Total_Leave_Instances
    FROM Vacation v
    LEFT JOIN Employee e ON v.Employee_ID = e.Employee_ID
    LEFT JOIN Department d ON e.Department_ID = d.Department_ID
    WHERE v.Status = 'approved'
      AND v.Start_Date BETWEEN ? AND ?
    GROUP BY d.Department_ID, e.Employee_ID, v.Leave_Type
";

$stmt = $connection->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// Average absence rates by department
$query_average = "
    SELECT 
    d.Name AS Department,
    COUNT(DISTINCT e.Employee_ID) AS Total_Employees,
    COALESCE(SUM(DATEDIFF(v.End_Date, v.Start_Date) + 1), 0) AS Total_Days_Absent,
    COALESCE(ROUND(SUM(DATEDIFF(v.End_Date, v.Start_Date) + 1) / COUNT(DISTINCT e.Employee_ID), 2), 0) AS Average_Absence_Rate
FROM Department d
LEFT JOIN Employee e ON d.Department_ID = e.Department_ID
LEFT JOIN Vacation v ON e.Employee_ID = v.Employee_ID AND v.Status = 'approved' AND v.Start_Date BETWEEN ? AND ?
GROUP BY d.Department_ID;
";

$stmt_average = $connection->prepare($query_average);
$stmt_average->bind_param("ss", $start_date, $end_date);
$stmt_average->execute();
$result_average = $stmt_average->get_result();
$average_data = $result_average->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absenteeism Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../pages/components/navbar.php'; ?>
    <div class="container mt-5">
        <h2>Absenteeism Report</h2>
        
        <!-- Date Filters -->
        <form class="mb-4" method="GET" action="absenteeism_report.php">
            <div class="row">
                <div class="col-md-5">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-2 mt-4">
                    <button type="submit" class="btn btn-primary w-100 mt-2">Submit</button>
                </div>
            </div>
            <div class="mb-4">
                <button type="button" class="btn btn-secondary" onclick="setMonth()">This Month</button>
                <button type="button" class="btn btn-secondary" onclick="setQuarter()">This Quarter</button>
            </div>
        </form>
        <!-- Department Table -->
        <h4 class="mt-5">Average Absence Rates by Department</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Total Employees</th>
                    <th>Total Days Absent</th>
                    <th>Average Absence Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($average_data)): ?>
                    <?php foreach ($average_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Department'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($row['Total_Employees'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($row['Total_Days_Absent'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($row['Average_Absence_Rate'] ?? 0); ?> days</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No data available for the selected period.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Report Table -->
        <h4 class="mt-5">Absence Details</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Employee Name</th>
                    <th>Total Days Absent</th>
                    <th>Leave Type</th>
                    <th>Total Leave Instances</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Department'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['Name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($row['Total_Days_Absent'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($row['Leave_Type'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['Total_Leave_Instances'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No absenteeism data found for the selected period.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>


<script>
    function setMonth() {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
        document.getElementById('end_date').value = lastDay.toISOString().split('T')[0];
    }

    function setQuarter() {
        const now = new Date();
        const quarter = Math.floor((now.getMonth() + 3) / 3); // 1-based quarter
        const firstMonth = (quarter - 1) * 3; // Start of quarter
        const firstDay = new Date(now.getFullYear(), firstMonth, 1);
        const lastDay = new Date(now.getFullYear(), firstMonth + 3, 0);

        document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
        document.getElementById('end_date').value = lastDay.toISOString().split('T')[0];
    }
</script>