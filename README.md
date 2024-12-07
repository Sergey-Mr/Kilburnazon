# Kilburnazon

## Overview
Kilburnazon is an open-source web application developed using PHP and MySQL. It serves as an efficient solution for managing employee-related activities within an organization. This software provides a robust and user-friendly interface for handling employee directories, data management, leave requests, payroll reporting, and more.

## Features

### 1. Employee Directory with Search, Filter, and Profile Details
- Displays a profile card for each employee, including:
  - Photo
  - Job Title
  - Department
  - Contact Information
- Implements dynamic search and filtering options based on attributes like department, job title, location, and start date.
- Allows users to view detailed employee profiles, which include:
  - Contact Details
  - Employment History
  - Relevant Notes
  - Team Associations

### 2. Employee Data Management and Promotion Updates
- Enables adding new employees with all relevant details.
- Supports updating existing employee information, including promotions with automatic pay increases (e.g., 5%).
- Reflects updates accurately in records and associated summaries.

### 3. Employee Leave Management and Absenteeism Reporting
- Features a leave request system allowing employees to:
  - Specify leave type (e.g., sick, vacation, personal).
  - Provide start and end dates and comments.
- Notifies managers of new leave requests with options to approve or deny.
- Updates leave balances automatically upon approval.
- Provides absenteeism reports displaying:
  - Total days absent
  - Reasons for absence
  - Average absence rates by department.

### 4. Payroll Report Generation and Export
- Allows authorized personnel to generate payroll summaries for specific periods (e.g., monthly, quarterly, annually).
- Includes detailed breakdowns of:
  - Base salary
  - Bonuses and incentives
  - Deductions (e.g., taxes, insurance, retirement contributions).
- Features:
  - Filtering and sorting options by department, role, or salary range.
  - Export options for PDF or CSV formats.
  - Summary totals for at-a-glance analysis.
  - Secure access for authorized users.

### 5. Birthday Cards
- Web page to see all employees who have birthday this month.
- Includes an SQL query to retrieve employees with birthdays in the current month.
- Makes it easy to identify and celebrate employees with upcoming birthdays.

### 6. Contract Terminations
- Maintains a log of all contract terminations, capturing:
  - Employee details
  - Date and time of termination
  - ID of the user who deleted the record.
- Ensures compliance with GDPR by retaining data for a maximum of 3 years post-termination.

## Installation

### Database Schema
![Database Schema](./DB%20schema.png)

1. Clone this repository:
   ```bash
   git clone https://github.com/your-repository/employee-management.git
   ```
2. Navigate to the project directory:
   ```bash
   cd employee-management
   ```
3. Set up the database:
   - Import the provided SQL schema into your MySQL database.
   - Update the database configuration in the `config.php` file.
4. Deploy the application on a PHP server (e.g., Apache or Nginx).
5. Access the application through your browser.

## Dependencies
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Server (e.g., Apache, Nginx)

## Usage
- Log in to the system using your credentials.
- Navigate through the dashboard to manage employees, process leave requests, generate reports, and more.

## License
This project is licensed under the [MIT License](LICENSE).

For any questions or support, please contact [Serhii Tupikin](sergey.st265@gmail.com).
