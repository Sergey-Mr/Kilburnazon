<!-- Search Filters Section -->
<div class="mt-5">
    <h3>Search Employees</h3>
    <form method="get" class="mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter name" value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="department" class="form-label">Department</label>
                <input type="text" name="department" id="department" class="form-control" placeholder="Enter department" value="<?php echo htmlspecialchars($_GET['department'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="job_title" class="form-label">Job Title</label>
                <input type="text" name="job_title" id="job_title" class="form-control" placeholder="Enter job title" value="<?php echo htmlspecialchars($_GET['job_title'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="office" class="form-label">Office</label>
                <input type="text" name="office" id="office" class="form-control" placeholder="Enter office" value="<?php echo htmlspecialchars($_GET['office'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-success w-100">Search</button>
            </div>
        </div>
    </form>
    <!-- Employee Cards -->
    <div class="row">
        <?php if (count($employees) > 0): ?>
            <?php foreach ($employees as $employee): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <!-- Placeholder Image -->
                            <img src="photo_placeholder.png" alt="Employee Photo" class="card-img-top mb-3" style="height: 150px; object-fit: cover;">
                            <h5 class="card-title"><?php echo htmlspecialchars($employee['Name'] ?? ''); ?></h5>
                            <p class="card-text">
                                <strong>Email:</strong> <?php echo htmlspecialchars($employee['Email']); ?><br>
                                <strong>Position:</strong> <?php echo htmlspecialchars($employee['Position'] ?? 'N/A'); ?><br>
                                <strong>Department:</strong> <?php echo htmlspecialchars($employee['Department'] ?? 'N/A'); ?><br>
                                <strong>Office:</strong> <?php echo htmlspecialchars($employee['Office'] ?? 'N/A'); ?><br>
                                <strong>Hired Date:</strong> <?php echo htmlspecialchars($employee['Hired_Date']); ?>
                            </p>
                            <!-- Modal Trigger Button -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal<?php echo $employee['Employee_ID']; ?>">
                                View Details
                            </button>
                            <!-- Add the Edit button -->
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#editEmployeeModal<?php echo $employee['Employee_ID']; ?>">
                                Edit Employee
                            </button>
                        </div>
                    </div>
                    <!-- Employee Modal -->
                    <div class="modal fade" id="employeeModal<?php echo $employee['Employee_ID']; ?>" tabindex="-1" aria-labelledby="employeeModalLabel<?php echo $employee['Employee_ID']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="employeeModalLabel<?php echo $employee['Employee_ID']; ?>">
                                        <?php echo htmlspecialchars($employee['Name']); ?> - Details
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <!-- Employee Modal -->
                                <div class="modal-body">
                                    <img src="photo_placeholder.png" alt="Employee Photo" class="card-img-top mb-3" style="height: 120px; width: 120px; object-fit: cover;">
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['Name'] ?? ''); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['Email'] ?? ''); ?></p>
                                        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($employee['DOB'] ?? 'N/A'); ?></p>
                                        <p><strong>Address:</strong> <?php echo htmlspecialchars($employee['Address'] ?? 'N/A'); ?></p>
                                        <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['Position'] ?? 'N/A'); ?></p>
                                        <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['Department'] ?? 'N/A'); ?></p>
                                        <p><strong>Office:</strong> <?php echo htmlspecialchars($employee['Office'] ?? 'N/A'); ?></p>
                                        <p><strong>Salary:</strong> <?php echo htmlspecialchars($employee['Salary'] ?? 'N/A'); ?></p>
                                        <p><strong>Hired Date:</strong> <?php echo htmlspecialchars($employee['Hired_Date'] ?? 'N/A'); ?></p>
                                        <p><strong>Contract Type:</strong> <?php echo htmlspecialchars($employee['Contract_Type'] ?? 'N/A'); ?></p>
                                        <!-- Emergency Contact Information -->
                                        <p><strong>Emergency Contact Name:</strong> <?php echo htmlspecialchars($employee['Contact_Name'] ?? 'N/A'); ?></p>
                                        <p><strong>Relationship:</strong> <?php echo htmlspecialchars($employee['Relationship'] ?? 'N/A'); ?></p>
                                        <p><strong>Emergency Contact Phone:</strong> <?php echo htmlspecialchars($employee['Phone'] ?? 'N/A'); ?></p>
                                    </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <!-- Fire Employee Button -->
                                    <form method="post" action="../../controllers/fire_employee.php" onsubmit="return confirm('Are you sure you want to fire this employee?');">
                                        <input type="hidden" name="employee_id" value="<?php echo $employee['Employee_ID']; ?>">
                                        <button type="submit" class="btn btn-danger">Fire Employee</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Edit Employee Modal -->
                    <div class="modal fade" id="editEmployeeModal<?php echo $employee['Employee_ID']; ?>" tabindex="-1" aria-labelledby="editEmployeeModalLabel<?php echo $employee['Employee_ID']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editEmployeeModalLabel<?php echo $employee['Employee_ID']; ?>">Edit Employee Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="../../controllers/edit_employee.php">
                                        <input type="hidden" name="employee_id" value="<?php echo $employee['Employee_ID']; ?>">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" id="name" value="<?php echo htmlspecialchars($employee['Name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($employee['Email'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="dob" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" name="dob" id="dob" value="<?php echo htmlspecialchars($employee['DOB'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <input type="text" class="form-control" name="address" id="address" value="<?php echo htmlspecialchars($employee['Address'] ?? ''); ?>">
                                        </div>
                                        <!-- Department Dropdown -->
                                        <div class="mb-3">
                                            <label for="department" class="form-label">Department</label>
                                            <select class="form-select" name="department" id="department">
                                                <?php
                                                // Fetch departments from the database
                                                $departmentQuery = "SELECT Department_ID, Name FROM Department";
                                                $departmentResult = $connection->query($departmentQuery);
                                                while ($department = $departmentResult->fetch_assoc()):
                                                ?>
                                                    <option value="<?php echo $department['Department_ID']; ?>" <?php echo ($department['Name'] == $employee['Department']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($department['Name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                                
                                        <!-- Position Dropdown -->
                                        <div class="mb-3">
                                            <label for="position" class="form-label">Position</label>
                                            <select class="form-select" name="position" id="position">
                                                <?php
                                                // Fetch positions from the database
                                                $positionQuery = "SELECT Position_ID, Role_Name FROM Employee_Position";
                                                $positionResult = $connection->query($positionQuery);
                                                while ($position = $positionResult->fetch_assoc()):
                                                ?>
                                                    <option value="<?php echo $position['Position_ID']; ?>" <?php echo ($position['Role_Name'] == $employee['Position']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($position['Role_Name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                                
                                        <!-- Office Dropdown -->
                                        <div class="mb-3">
                                            <label for="office" class="form-label">Office</label>
                                            <select class="form-select" name="office" id="office">
                                                <?php
                                                // Fetch offices from the database
                                                $officeQuery = "SELECT Office_ID, Name FROM Office";
                                                $officeResult = $connection->query($officeQuery);
                                                while ($office = $officeResult->fetch_assoc()):
                                                ?>
                                                    <option value="<?php echo $office['Office_ID']; ?>" <?php echo ($office['Name'] == $employee['Office']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($office['Name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
      
                                        <div class="mb-3">
                                            <label for="salary" class="form-label">Salary</label>
                                            <input type="number" class="form-control" name="salary" id="salary" value="<?php echo htmlspecialchars($employee['Salary'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="contract_type" class="form-label">Contract Type</label>
                                            <input type="text" class="form-control" name="contract_type" id="contract_type" value="<?php echo htmlspecialchars($employee['Contract_Type'] ?? ''); ?>">
                                        </div>
                                                
                                        <div class="mb-3">
                                            <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                            <input type="text" class="form-control" name="emergency_contact_name" id="emergency_contact_name" value="<?php echo htmlspecialchars($employee['Contact_Name'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                            <input type="text" class="form-control" name="emergency_contact_phone" id="emergency_contact_phone" value="<?php echo htmlspecialchars($employee['Phone'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="emergency_contact_phone" class="form-label">Emergency Contact Relationship</label>
                                            <input type="text" class="form-control" name="emergency_contact_relationship" id="emergency_contact_relationship" value="<?php echo htmlspecialchars($employee['Relationship'] ?? ''); ?>">
                                        </div>
                                                
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">No employees found matching your search criteria.</p>
        <?php endif; ?>
    </div>
</div>