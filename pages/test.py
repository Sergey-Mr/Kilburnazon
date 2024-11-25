import random
import datetime

# Function to generate fictional data for each employee
def generate_payroll_data(employee_id):
    # Generate random data for the required fields (in percentage range 1 to 100)
    bonuses = round(random.uniform(1.00, 100.00), 2)  # Random bonus percentage between 1 and 100
    incentives = round(random.uniform(1.00, 100.00), 2)  # Random incentive percentage between 1 and 100
    allowances = round(random.uniform(1.00, 100.00), 2)  # Random allowance percentage between 1 and 100
    
    # Set taxes to a fixed value of 20.00 for everyone
    taxes = 20.00  # Fixed tax value
    
    # Generate insurance and retirement contributions as a percentage between 1% and 10%
    insurance_percentage = round(random.uniform(1.00, 10.00), 2)  # Insurance as a percentage between 1 and 10
    retirement_contrib_percentage = round(random.uniform(1.00, 10.00), 2)  # Retirement contribution as a percentage between 1 and 10
    
    # Calculate insurance and retirement contributions based on the sum of Bonuses, Incentives, and Allowances
    total_income = bonuses + incentives + allowances
    insurance = round(total_income * insurance_percentage / 100, 2)
    retirement_contrib = round(total_income * retirement_contrib_percentage / 100, 2)
    
    # Get the current date for the payroll date
    payroll_date = datetime.datetime.now().strftime('%Y-%m-%d')
    
    # Create the SQL insert statement
    sql_command = f"""
    INSERT INTO Payroll (Employee_ID, Bonuses, Incentives, Allowances, Taxes, Insurance, Retirement_Contributions, Payroll_Date)
    VALUES ({employee_id}, {bonuses}, {incentives}, {allowances}, {taxes}, {insurance}, {retirement_contrib}, '{payroll_date}');
    """
    
    return sql_command

# Generate SQL commands for all employee IDs from 1 to 219
def generate_insert_commands():
    sql_commands = []
    for employee_id in range(1, 220):
        sql_commands.append(generate_payroll_data(employee_id))
    return sql_commands

# Output the SQL commands
if __name__ == "__main__":
    insert_commands = generate_insert_commands()
    
    # Print all insert commands
    for command in insert_commands:
        print(command)
