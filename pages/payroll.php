<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}


// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);


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


// Retrieve the logged-in user's role
$email = $_SESSION['email'];
$query = "
    SELECT d.Name AS Department_Name
    FROM Employee e
    LEFT JOIN Department d ON e.Department_ID = d.Department_ID
    WHERE e.Email = ?
";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $department_name = $row['Department_Name'];
    if ($department_name !== 'Executive') {
        // User is not in the Executive department
        header("HTTP/1.1 403 Forbidden");
        die("<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>");
    }
} else {
    // Department not found or query failed
    header("HTTP/1.1 403 Forbidden");
    die("<h1>403 Forbidden</h1><p>Unable to verify your department.</p>");
}

// Fetch departments and roles
$departments_result = $connection->query("SELECT Department_ID, Name FROM Department");
$roles_result = $connection->query("SELECT Position_ID, Role_Name FROM Employee_Position");

$message = "";
$toastClass = '';
$payroll_data = [];
$total_net_pay = 0;
$total_base_salary = 0; // Total base salary
$count = 0; // Number of employees


$selected_time_period = isset($_POST['time_period']) ? $_POST['time_period'] : '';
$selected_department_id = isset($_POST['department']) ? $_POST['department'] : '';
$selected_role_id = isset($_POST['role']) ? $_POST['role'] : '';
$selected_salary_range = isset($_POST['salary_range']) ? $_POST['salary_range'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_period = $_POST['time_period'];
    $department_id = $_POST['department'];
    $role_id = $_POST['role'];
    $salary_range = $_POST['salary_range'];

    // Base query for payroll report
    $query = "
        SELECT 
            e.Name, 
            d.Name AS Department, 
            p.Role_Name AS Job_Title, 
            e.Salary AS Base_Salary
        FROM Employee e
        LEFT JOIN Department d ON e.Department_ID = d.Department_ID
        LEFT JOIN Employee_Position p ON e.Position_ID = p.Position_ID
        WHERE 1=1
    ";

    // Apply filters
    if (!empty($department_id)) {
        $query .= " AND e.Department_ID = '" . $connection->real_escape_string($department_id) . "'";
    }
    if (!empty($role_id)) {
        $query .= " AND e.Position_ID = '" . $connection->real_escape_string($role_id) . "'";
    }
    if (!empty($salary_range)) {
        $range = explode('-', $salary_range);
        if (count($range) == 2) {
            $query .= " AND e.Salary BETWEEN " . intval($range[0]) . " AND " . intval($range[1]);
        }
    }

    $result = $connection->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $count++; // Increment employee count
            $base_salary = $row['Base_Salary'];
            $total_base_salary += $base_salary; // Add to total base salary
    
            // Calculate other salary components and net pay (existing logic)...
            $bonuses = round($base_salary * 0.10, 2);
            $incentives = round($base_salary * 0.05, 2);
            $allowances = round($base_salary * 0.08, 2);
            $taxes = round($base_salary * 0.12, 2);
            $insurance = round($base_salary * 0.07, 2);
            $retirement = round($base_salary * 0.05, 2);
            $net_pay = $base_salary + $bonuses + $incentives + $allowances - $taxes - $insurance - $retirement;
    
            $total_net_pay += $net_pay; // Add to total net pay
    
            // Add the row data to the payroll array (existing logic)...
            $payroll_data[] = [
                'Name' => $row['Name'],
                'Department' => $row['Department'],
                'Job_Title' => $row['Job_Title'],
                'Base_Salary' => number_format($base_salary ?? 0, 2),
                'Bonuses' => number_format($bonuses ?? 0, 2),
                'Incentives' => number_format($incentives ?? 0, 2),
                'Allowances' => number_format($allowances ?? 0, 2),
                'Taxes' => number_format($taxes ?? 0, 2),
                'Insurance' => number_format($insurance ?? 0, 2),
                'Retirement_Contributions' => number_format($retirement ?? 0, 2),
                'Net_Pay' => number_format($net_pay ?? 0, 2),
            ];
        }
    } else {
        $message = "Error fetching data: " . $connection->error;
        $toastClass = 'bg-danger';
    }
}
if (isset($_POST['export_csv'])) {
    // Clear output buffering to prevent conflicts
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=payroll_report.csv');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        die('Failed to open output stream.');
    }

    // Write CSV headers
    fputcsv($output, ['Name', 'Department', 'Job Title', 'Base Salary', 'Bonuses', 'Incentives', 'Allowances', 'Taxes', 'Insurance', 'Retirement Contributions', 'Net Pay']);

    // Write CSV data
    foreach ($payroll_data as $row) {
        fputcsv($output, [
            $row['Name'],
            $row['Department'],
            $row['Job_Title'],
            $row['Base_Salary'],
            $row['Bonuses'],
            $row['Incentives'],
            $row['Allowances'],
            $row['Taxes'],
            $row['Insurance'],
            $row['Retirement_Contributions'],
            $row['Net_Pay']
        ]);
    }

    // Add total row
    fputcsv($output, [
        'Total', '', '', '', '', '', '', '', '', '', // Empty cells for non-total columns
        number_format($total_net_pay, 2) // Total net pay
    ]);

    fclose($output);
    exit();
}
if (isset($_POST['export_pdf'])) {
    require_once('tcpdf/tcpdf.php'); // Adjust path as needed
    
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);
    
    // Title
    $pdf->Write(0, 'Payroll Report', '', 0, 'L', true, 0, false, false, 0);
    $pdf->Ln(10);
    
    // Table header
    $html = '<table border="1" cellpadding="4">';
    $html .= '<thead><tr>
                <th>Name</th>
                <th>Department</th>
                <th>Job Title</th>
                <th>Base Salary</th>
                <th>Bonuses</th>
                <th>Incentives</th>
                <th>Allowances</th>
                <th>Taxes</th>
                <th>Insurance</th>
                <th>Retirement Contributions</th>
                <th>Net Pay</th>
              </tr></thead><tbody>';
    
    // Add data rows
    foreach ($payroll_data as $row) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($row['Name']) . '</td>
                    <td>' . htmlspecialchars($row['Department']) . '</td>
                    <td>' . htmlspecialchars($row['Job_Title']) . '</td>
                    <td>' . number_format($row['Base_Salary'], 2) . '</td>
                    <td>' . number_format($row['Bonuses'], 2) . '</td>
                    <td>' . number_format($row['Incentives'], 2) . '</td>
                    <td>' . number_format($row['Allowances'], 2) . '</td>
                    <td>' . number_format($row['Taxes'], 2) . '</td>
                    <td>' . number_format($row['Insurance'], 2) . '</td>
                    <td>' . number_format($row['Retirement_Contributions'], 2) . '</td>
                    <td>' . number_format($row['Net_Pay'], 2) . '</td>
                  </tr>';
    }
    $html .= '</tbody></table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('payroll_report.pdf', 'D');
    exit();
}

$average_base_salary = $count > 0 ? $total_base_salary / $count : 0;
$average_net_pay = $count > 0 ? $total_net_pay / $count : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php
    include '../pages/components/navbar.php';
    ?>
    
    <!-- Content -->
    <div class="container mt-5">
        <h2>Payroll Report</h2>
        <?php if ($message): ?>
            <div class="alert <?php echo $toastClass; ?> text-white">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Filter Form -->
        <form action="payroll.php" method="POST">
            <div class="mb-3">
                <label for="time_period" class="form-label">Time Period</label>
                <select name="time_period" id="time_period" class="form-control" required>
                    <option value="monthly" <?php echo $selected_time_period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="quarterly" <?php echo $selected_time_period === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                    <option value="annually" <?php echo $selected_time_period === 'annually' ? 'selected' : ''; ?>>Annually</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <select name="department" id="department" class="form-control">
                    <option value="">Select Department</option>
                    <?php while ($dept = $departments_result->fetch_assoc()): ?>
                        <option value="<?php echo $dept['Department_ID']; ?>" 
                            <?php echo $dept['Department_ID'] == $selected_department_id ? 'selected' : ''; ?>>
                            <?php echo $dept['Name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-control">
                    <option value="">Select Role</option>
                    <?php while ($role = $roles_result->fetch_assoc()): ?>
                        <option value="<?php echo $role['Position_ID']; ?>" 
                            <?php echo $role['Position_ID'] == $selected_role_id ? 'selected' : ''; ?>>
                            <?php echo $role['Role_Name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="salary_range" class="form-label">Salary Range</label>
                <input type="text" name="salary_range" id="salary_range" class="form-control" 
                    placeholder="e.g., 20000-50000" value="<?php echo htmlspecialchars($selected_salary_range); ?>">

            </div>
            <button type="submit" class="btn btn-primary">Generate Report</button>
            
            <?php if (!empty($payroll_data)): ?>
                <button type="submit" name="export_csv" class="btn btn-secondary mt-3">Export CSV</button>
                <button type="submit" name="export_pdf" class="btn btn-secondary mt-3">Export PDF</button>
            <?php endif; ?>
        </form>

        <!-- Payroll Report Table -->
        <?php if (!empty($payroll_data)): ?>
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Job Title</th>
                            <th>Base Salary</th>
                            <th>Bonuses</th>
                            <th>Incentives</th>
                            <th>Allowances</th>
                            <th>Taxes</th>
                            <th>Insurance</th>
                            <th>Retirement Contributions</th>
                            <th>Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payroll_data as $row): ?>
                            <tr>
                                <td><?php echo $row['Name']; ?></td>
                                <td><?php echo $row['Department']; ?></td>
                                <td><?php echo $row['Job_Title']; ?></td>
                                <td><?php echo $row['Base_Salary']; ?></td>
                                <td><?php echo $row['Bonuses']; ?></td>
                                <td><?php echo $row['Incentives']; ?></td>
                                <td><?php echo $row['Allowances']; ?></td>
                                <td><?php echo $row['Taxes']; ?></td>
                                <td><?php echo $row['Insurance']; ?></td>
                                <td><?php echo $row['Retirement_Contributions']; ?></td>
                                <td><?php echo $row['Net_Pay']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="10" class="text-end"><strong>Total Net Pay:</strong></td>
                            <td><strong><?php echo number_format($total_net_pay, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="10" class="text-end"><strong>Average Base Salary:</strong></td>
                            <td><strong><?php echo number_format($average_base_salary, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="10" class="text-end"><strong>Average Net Pay:</strong></td>
                            <td><strong><?php echo number_format($average_net_pay, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function exportCSV() {
            alert('CSV export functionality not implemented yet.');
        }
        function exportPDF() {
            alert('PDF export functionality not implemented yet.');
        }
    </script>
</body>
</html>