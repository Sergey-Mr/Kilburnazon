<?php
include '../database/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Path to your CSV file
$file = 'employees.csv';

// Open the CSV file
if (($handle = fopen($file, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ","); // Skip header row

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Map CSV data to variables (excluding the id column)
        $name = $data[1];
        $position = $data[2];
        $department = $data[3];
        $salary = $data[4];
        $email = $data[5];
        $dob = $data[6];
        $office = $data[7];
        $home_address = $data[8];
        $hired_date = $data[9];
        $contract = $data[10];
        $nin = $data[11];
        $emergency_name = $data[12];
        $emergency_relationship = $data[13];
        $emergency_phone = $data[14];

        // Convert DOB from DD/MM/YYYY to YYYY-MM-DD format
        $dobDate = DateTime::createFromFormat('d/m/Y', $dob);
        if ($dobDate !== false) {
            $dob = $dobDate->format('Y-m-d');
        } else {
            $dob = null; // Handle invalid date format if needed
        }

        $hiredDate = DateTime::createFromFormat('d/m/Y', $hired_date);
        if ($hiredDate !== false) {
            $hired_date = $hiredDate->format('Y-m-d');
        } else {
            $hired_date = null;
        }

        // Handle Salary value: If 'n/d', set it to NULL or default value (e.g., 0)
        if ($salary === 'n/d' || !is_numeric($salary)) {
            $salary = null;  // Or use $salary = 0.00; for a default value
        }

        // Insert into Office table
        $officeStmt = $connection->prepare("INSERT IGNORE INTO Office (Name) VALUES (?)");
        $officeStmt->bind_param("s", $office);
        $officeStmt->execute();

        // Get Office_ID
        $officeIdStmt = $connection->prepare("SELECT Office_ID FROM Office WHERE Name = ?");
        $officeIdStmt->bind_param("s", $office);
        $officeIdStmt->execute();
        $officeIdStmt->bind_result($office_id);
        $officeIdStmt->fetch();
        $officeIdStmt->close();

        // Insert into Departments table (if the department doesn't exist)
        $departmentId = null;
        $departmentStmt = $connection->prepare("SELECT Department_ID FROM Department WHERE Name = ?");
        $departmentStmt->bind_param("s", $department);
        $departmentStmt->execute();
        $departmentStmt->bind_result($departmentId);
        $departmentStmt->fetch();
        $departmentStmt->close();

        if ($departmentId === null) {
            // If department doesn't exist, insert it
            $departmentStmtInsert = $connection->prepare("INSERT INTO Department (Name) VALUES (?)");
            $departmentStmtInsert->bind_param("s", $department);
            $departmentStmtInsert->execute();

            // Get the newly inserted Department_ID
            $departmentId = $connection->insert_id;
        }

        // Insert into Employee_Position table
        $positionStmt = $connection->prepare("INSERT IGNORE INTO Employee_Position (Role_Name, Department_ID, Vacation_Policy_ID) VALUES (?, ?, NULL)");
        $positionStmt->bind_param("ss", $position, $departmentId);
        $positionStmt->execute();

        // Get Position_ID
        $positionIdStmt = $connection->prepare("SELECT Position_ID FROM Employee_Position WHERE Role_Name = ? AND Department_ID = ?");
        $positionIdStmt->bind_param("ss", $position, $departmentId);
        $positionIdStmt->execute();
        $positionIdStmt->bind_result($position_id);
        $positionIdStmt->fetch();
        $positionIdStmt->close();

        // Insert into Employee table with Department_ID
        $employeeStmt = $connection->prepare("INSERT INTO Employee (name, Position_ID, Salary, Email, DOB, Office_ID, Address, Hired_Date, Contract_Type, NIN, Department_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $employeeStmt->bind_param("sissssissss", $name, $position_id, $salary, $email, $dob, $office_id, $home_address, $hired_date, $contract, $nin, $departmentId);
        $employeeStmt->execute();

        // Get the auto-generated Employee_ID
        $employeeId = $connection->insert_id;

        // Insert into Emergency_Contact table (if data is available)
        // Insert into Emergency_Contact table (if data is available)
        if (!empty($emergency_name) && !empty($emergency_relationship) && !empty($emergency_phone)) {
            $emergencyStmt = $connection->prepare("INSERT INTO Emergency_Contact (Employee_ID, Contact_Name, Relationship, Phone) VALUES (?, ?, ?, ?)");
            $emergencyStmt->bind_param("isss", $employeeId, $emergency_name, $emergency_relationship, $emergency_phone);
        
            if (!$emergencyStmt->execute()) {
                // Log the error if insert fails
                echo "Error inserting emergency contact: " . $emergencyStmt->error;
            } else {
                // Log the successful insertion
                echo "Emergency contact inserted successfully for Employee ID: " . $employeeId . "<br>";
            }
}

    }

    fclose($handle);
    echo "Data imported successfully!";
} else {
    echo "Failed to open the CSV file.";
}
?>
