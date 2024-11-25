<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

include '../database/db_connect.php';

$type = $_GET['type'] ?? 'csv';

// Fetch payroll data
$query = "
    SELECT 
        e.Name, 
        d.Name AS Department, 
        p.Role_Name AS Job_Title, 
        e.Salary AS Base_Salary
    FROM Employee e
    LEFT JOIN Department d ON e.Department_ID = d.Department_ID
    LEFT JOIN Employee_Position p ON e.Position_ID = p.Position_ID
";
$result = $connection->query($query);

if (!$result) {
    die("Error fetching data: " . $connection->error);
}

$payroll_data = [];
$total_net_pay = 0;

while ($row = $result->fetch_assoc()) {
    $base_salary = $row['Base_Salary'];

    // Calculate percentages
    $bonuses = round($base_salary * 0.10, 2);
    $incentives = round($base_salary * 0.05, 2);
    $allowances = round($base_salary * 0.08, 2);
    $taxes = round($base_salary * 0.12, 2);
    $insurance = round($base_salary * 0.07, 2);
    $retirement = round($base_salary * 0.05, 2);

    // Calculate Net Pay
    $net_pay = $base_salary + $bonuses + $incentives + $allowances - $taxes - $insurance - $retirement;
    $total_net_pay += $net_pay;

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

// Export as CSV
if ($type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payroll_report.csv"');
    $output = fopen('php://output', 'w');

    // Add CSV header
    fputcsv($output, [
        'Name', 'Department', 'Job Title', 'Base Salary', 'Bonuses', 'Incentives', 
        'Allowances', 'Taxes', 'Insurance', 'Retirement Contributions', 'Net Pay'
    ]);

    // Add data
    foreach ($payroll_data as $row) {
        fputcsv($output, $row);
    }

    // Add total net pay row
    fputcsv($output, array_fill(0, 10, '') + ['Total Net Pay' => number_format($total_net_pay, 2)]);
    fclose($output);
    exit();
}

// Export as PDF
if ($type === 'pdf') {
    require_once('../libs/tcpdf/tcpdf.php'); // Ensure TCPDF is installed

    $pdf = new TCPDF();
    $pdf->AddPage();

    // Set PDF title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Payroll Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);

    // Table header
    $pdf->Cell(30, 10, 'Name', 1);
    $pdf->Cell(30, 10, 'Department', 1);
    $pdf->Cell(30, 10, 'Job Title', 1);
    $pdf->Cell(25, 10, 'Base Salary', 1);
    $pdf->Cell(20, 10, 'Bonuses', 1);
    $pdf->Cell(20, 10, 'Incentives', 1);
    $pdf->Cell(20, 10, 'Net Pay', 1);
    $pdf->Ln();

    // Table rows
    foreach ($payroll_data as $row) {
        $pdf->Cell(30, 10, $row['Name'], 1);
        $pdf->Cell(30, 10, $row['Department'], 1);
        $pdf->Cell(30, 10, $row['Job_Title'], 1);
        $pdf->Cell(25, 10, $row['Base_Salary'], 1);
        $pdf->Cell(20, 10, $row['Bonuses'], 1);
        $pdf->Cell(20, 10, $row['Incentives'], 1);
        $pdf->Cell(20, 10, $row['Net_Pay'], 1);
        $pdf->Ln();
    }

    // Total Net Pay row
    $pdf->Cell(185, 10, 'Total Net Pay: ' . number_format($total_net_pay, 2), 1, 1, 'R');

    // Output PDF
    $pdf->Output('payroll_report.pdf', 'D');
    exit();
}
?>
