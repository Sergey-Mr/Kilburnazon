<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../database/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Extract POST variables
    $employee_id = $_POST['employee_id'];
    $name = $_POST['name'] ?? null;
    $email = $_POST['email'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $address = $_POST['address'] ?? null;
    $salary = $_POST['salary'] ?? null;
    $contract_type = $_POST['contract_type'] ?? null;
    $department_id = $_POST['department'] ?? null;
    $position_id = $_POST['position'] ?? null;
    $office_id = $_POST['office'] ?? null;
    $emergency_contact_name = $_POST['emergency_contact_name'] ?? null;
    $emergency_contact_relationship = $_POST['emergency_contact_relationship'] ?? null;
    $emergency_contact_phone = $_POST['emergency_contact_phone'] ?? null;

    // Start a transaction for safe updates
    $connection->begin_transaction();

    try {
        // Update the Employee table
        $update_employee_query = "
            UPDATE Employee 
            SET Name = ?, Email = ?, DOB = ?, Address = ?, Salary = ?, Contract_Type = ?, 
                Department_ID = ?, Position_ID = ?, Office_ID = ?
            WHERE Employee_ID = ?";
                
        $stmt = $connection->prepare($update_employee_query);
        $stmt->bind_param(
            "ssssdsiiis",
            $name, $email, $dob, $address, $salary, $contract_type,
            $department_id, $position_id, $office_id, $employee_id
        );
        $stmt->execute();
    
        // Fetch the Emergency Contact ID for the employee
        $emergency_contact_id_query = "SELECT Emergency_Contact_ID FROM Employee WHERE Employee_ID = ?";
        $stmt = $connection->prepare($emergency_contact_id_query);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $emergency_contact_id = $result->fetch_assoc()['Emergency_Contact_ID'];
    
        if ($emergency_contact_id) {
            // Update the existing emergency contact
            $update_emergency_contact_query = "
                UPDATE Emergency_Contact
                SET Contact_Name = ?, Relationship = ?, Phone = ?
                WHERE Contact_ID = ?";
            $stmt = $connection->prepare($update_emergency_contact_query);
            $stmt->bind_param(
                "sssi",
                $emergency_contact_name, $emergency_contact_relationship, 
                $emergency_contact_phone, $emergency_contact_id
            );
            $stmt->execute();
        } else {
            // Insert a new emergency contact
            $insert_emergency_contact_query = "
                INSERT INTO Emergency_Contact (Contact_Name, Relationship, Phone, Employee_ID)
                VALUES (?, ?, ?, ?)";
            $stmt = $connection->prepare($insert_emergency_contact_query);
            $stmt->bind_param(
                "sssi",
                $emergency_contact_name, $emergency_contact_relationship, 
                $emergency_contact_phone, $employee_id
            );
            $stmt->execute();
    
            // Get the newly created contact ID
            $new_contact_id = $connection->insert_id;
    
            // Update the Employee table with the new Emergency_Contact_ID
            $update_employee_contact_id_query = "
                UPDATE Employee
                SET Emergency_Contact_ID = ?
                WHERE Employee_ID = ?";
            $stmt = $connection->prepare($update_employee_contact_id_query);
            $stmt->bind_param("ii", $new_contact_id, $employee_id);
            $stmt->execute();
        }
    
        // Commit the transaction
        $connection->commit();
    
        // Set a success message
        $_SESSION['message'] = "Employee details updated successfully.";
    } catch (Exception $e) {
        // Rollback in case of an error
        $connection->rollback();
        $_SESSION['error'] = "Error updating employee details: " . $e->getMessage();
    }
    
    

    // Redirect back to the dashboard or the edit page
    header("Location: dashboard.php");
    exit();
}
