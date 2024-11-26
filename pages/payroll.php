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

require_once '../vendor/autoload.php';
use TCPDF;

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

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_period = $_POST['time_period'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $department_id = $_POST['department'] ?? '';
    $role_id = $_POST['role'] ?? '';
    $salary_range = $_POST['salary_range'] ?? '';

    // Base query for payroll report
    $query = "
        SELECT 
            e.Name, 
            d.Name AS Department, 
            p.Role_Name AS Job_Title, 
            e.Salary AS Base_Salary, 
            pr.Bonuses, 
            pr.Incentives, 
            pr.Allowances, 
            pr.Taxes, 
            pr.Insurance, 
            pr.Retirement_Contributions, 
            pr.Payroll_Date
        FROM Payroll pr
        LEFT JOIN Employee e ON pr.Employee_ID = e.Employee_ID
        LEFT JOIN Department d ON e.Department_ID = d.Department_ID
        LEFT JOIN Employee_Position p ON e.Position_ID = p.Position_ID
        WHERE 1=1
    ";

    // Apply time period filter
    if ($time_period === 'custom') {
        if (!empty($start_date) && !empty($end_date)) {
            $query .= " AND pr.Payroll_Date BETWEEN '" . $connection->real_escape_string($start_date) . "' 
                        AND '" . $connection->real_escape_string($end_date) . "'";
        } else {
            $message = "Please select both start and end dates for the custom range.";
            $toastClass = 'bg-danger';
        }
    }

    // Apply other filters
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
            $bonuses = $row['Bonuses'];
            $incentives = $row['Incentives'];
            $allowances = $row['Allowances'];
            $taxes = $row['Taxes'];
            $insurance = $row['Insurance'];
            $retirement = $row['Retirement_Contributions'];
            $net_pay = $base_salary + $bonuses + $incentives + $allowances - $taxes - $insurance - $retirement;

            $total_net_pay += $net_pay;
            $total_base_salary += $base_salary;
            $count++;

            $payroll_data[] = [
                'Name' => $row['Name'],
                'Department' => $row['Department'],
                'Job_Title' => $row['Job_Title'],
                'Base_Salary' => number_format($row['Base_Salary'] ?? 0, 2),
                'Bonuses' => number_format($row['Bonuses'] ?? 0, 2),
                'Incentives' => number_format($row['Incentives'] ?? 0, 2),
                'Allowances' => number_format($row['Allowances'] ?? 0, 2),
                'Taxes' => number_format($row['Taxes'] ?? 0, 2),
                'Insurance' => number_format($row['Insurance'] ?? 0, 2),
                'Retirement_Contributions' => number_format($row['Retirement_Contributions'] ?? 0, 2),
                'Net_Pay' => number_format($net_pay ?? 0, 2),
                'Payroll_Date' => $row['Payroll_Date'],
            ];
        }
    } else {
        $message = "Error fetching data: " . $connection->error;
        $toastClass = 'bg-danger';
    }
}
if ($result->num_rows == 0) {
    $message = "No payroll data found for the selected time period.";
    $toastClass = 'bg-warning';
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

    // Start output buffering to prevent errors being sent to the browser
    ob_start();

    // Initialize PDF object
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
    
    $total_net_pay = 0;
    $total_base_salary = 0;
    $total_entries = count($payroll_data);

    // Initialize averages to avoid undefined variables
    $average_net_pay = 0;
    $average_base_salary = 0;
    
    // Loop through each entry in payroll_data
    foreach ($payroll_data as $row) {
        // Check if the necessary keys exist and are valid
        $base_salary = isset($row['Base_Salary']) ? (float)$row['Base_Salary'] : 0;
        $bonuses = isset($row['Bonuses']) ? (float)$row['Bonuses'] : 0;
        $incentives = isset($row['Incentives']) ? (float)$row['Incentives'] : 0;
        $allowances = isset($row['Allowances']) ? (float)$row['Allowances'] : 0;
        $taxes = isset($row['Taxes']) ? (float)$row['Taxes'] : 0;
        $insurance = isset($row['Insurance']) ? (float)$row['Insurance'] : 0;
        $retirement_contrib = isset($row['Retirement_Contributions']) ? (float)$row['Retirement_Contributions'] : 0;
        $net_pay = isset($row['Net_Pay']) ? (float)$row['Net_Pay'] : 0;

        $formatted_base_salary = number_format((float)$row['Base_Salary'], 2, '.', ',');
        $formatted_bonuses = number_format((float)$row['Bonuses'], 2, '.', ',');
        $formatted_incentives = number_format((float)$row['Incentives'], 2, '.', ',');
        $formatted_allowances = number_format((float)$row['Allowances'], 2, '.', ',');
        $formatted_taxes = number_format((float)$row['Taxes'], 2, '.', ',');
        $formatted_insurance = number_format((float)$row['Insurance'], 2, '.', ',');
        $formatted_retirement_contributions = number_format((float)$row['Retirement_Contributions'], 2, '.', ',');
        $formatted_net_pay = number_format((float)$row['Net_Pay'], 2, '.', ',');


        // Add data rows to the HTML
        $html .= '<tr>
    <td>' . htmlspecialchars($row['Name'] ?? '') . '</td>
    <td>' . htmlspecialchars($row['Department'] ?? '') . '</td>
    <td>' . htmlspecialchars($row['Job_Title'] ?? '') . '</td>
    <td>' . $formatted_base_salary . '</td>
    <td>' . $formatted_bonuses . '</td>
    <td>' . $formatted_incentives . '</td>
    <td>' . $formatted_allowances . '</td>
    <td>' . $formatted_taxes . '</td>
    <td>' . $formatted_insurance . '</td>
    <td>' . $formatted_retirement_contributions . '</td>
    <td>' . $formatted_net_pay . '</td>
</tr>';

        // Sum the values for total calculations
        $total_net_pay += $net_pay;
        $total_base_salary += $base_salary;
    }

    // Calculate averages (ensure division by zero doesn't happen)
    if ($total_entries > 0) {
        $average_net_pay = $total_net_pay / $total_entries;
        $average_base_salary = $total_base_salary / $total_entries;
    }

    // Add total and average rows to the table
    $html .= '<tr>
                <td colspan="10" class="text-end"><strong>Total Net Pay:</strong></td>
                <td><strong>' . number_format($total_net_pay, 2) . '</strong></td>
              </tr>';
    $html .= '<tr>
                <td colspan="10" class="text-end"><strong>Average Base Salary:</strong></td>
                <td><strong>' . number_format($average_base_salary, 2) . '</strong></td>
              </tr>';
    $html .= '<tr>
                <td colspan="10" class="text-end"><strong>Average Net Pay:</strong></td>
                <td><strong>' . number_format($average_net_pay, 2) . '</strong></td>
              </tr>';
    
    $html .= '</tbody></table>';
    
    // Write the HTML to PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Output the PDF
    $pdf->Output('payroll_report.pdf', 'D');
    
    // End the script execution to prevent further output
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
                    <option value="custom" <?php echo $selected_time_period === 'custom' ? 'selected' : ''; ?>>Custom</option>
                </select>
            </div>
            <div id="custom-date-range" class="mb-3" style="display: none;">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
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

<script>
    document.getElementById('time_period').addEventListener('change', function () {
        const customRange = document.getElementById('custom-date-range');
        if (this.value === 'custom') {
            customRange.style.display = 'block';
        } else {
            customRange.style.display = 'none';
        }
    });

    // Trigger change event on page load to ensure correct visibility
    document.getElementById('time_period').dispatchEvent(new Event('change'));
</script>