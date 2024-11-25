<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

include '../database/db_connect.php';

// Fetch departments and roles
$departments_result = $connection->query("SELECT Department_ID, Name FROM Department");
$roles_result = $connection->query("SELECT Position_ID, Role_Name FROM Employee_Position");

$message = "";
$toastClass = '';
$payroll_data = [];
$total_net_pay = 0;

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
            $base_salary = $row['Base_Salary'];

            // Calculate percentages based on base salary
            $bonuses = round($base_salary * 0.10, 2); // 10% Bonuses
            $incentives = round($base_salary * 0.05, 2); // 5% Incentives
            $allowances = round($base_salary * 0.08, 2); // 8% Allowances
            $taxes = round($base_salary * 0.12, 2); // 12% Taxes
            $insurance = round($base_salary * 0.07, 2); // 7% Insurance
            $retirement = round($base_salary * 0.05, 2); // 5% Retirement Contributions

            // Calculate Net Pay
            $net_pay = $base_salary + $bonuses + $incentives + $allowances - $taxes - $insurance - $retirement;
            $total_net_pay += $net_pay;

            // Append to payroll data
            $payroll_data[] = [
                'Name' => $row['Name'],
                'Department' => $row['Department'],
                'Job_Title' => $row['Job_Title'],
                'Base_Salary' => number_format($base_salary, 2),
                'Bonuses' => number_format($bonuses, 2),
                'Incentives' => number_format($incentives, 2),
                'Allowances' => number_format($allowances, 2),
                'Taxes' => number_format($taxes, 2),
                'Insurance' => number_format($insurance, 2),
                'Retirement_Contributions' => number_format($retirement, 2),
                'Net_Pay' => number_format($net_pay, 2),
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
    <nav class="navbar navbar-expand-sm navbar-light bg-success">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="#">Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Employee Directory</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="add_employee.php">Add Employee</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="payroll.php">Payroll Report</a></li>
                </ul>
                <a href="./logout.php" class="btn btn-light">Logout</a>
            </div>
        </div>
    </nav>

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
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annually">Annually</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <select name="department" id="department" class="form-control">
                    <option value="">Select Department</option>
                    <?php while ($dept = $departments_result->fetch_assoc()): ?>
                        <option value="<?php echo $dept['Department_ID']; ?>"><?php echo $dept['Name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-control">
                    <option value="">Select Role</option>
                    <?php while ($role = $roles_result->fetch_assoc()): ?>
                        <option value="<?php echo $role['Position_ID']; ?>"><?php echo $role['Role_Name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="salary_range" class="form-label">Salary Range</label>
                <input type="text" name="salary_range" id="salary_range" class="form-control" placeholder="e.g., 20000-50000">
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
