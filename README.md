# Latur District Administration Meeting System

## Project Overview
This system is designed for the Latur district administration to manage official meetings, task assignments, attendance, and progress updates for Collectors, organizers, and employees.

## SRS Summary
### Functional Requirements
- Users can log in using their official email and password.
- Collectors can view overall meeting and task summaries.
- Organizers can create meetings, assign responsibilities, and manage attendance.
- Employees can view assigned tasks, update progress, and confirm attendance.
- The system stores meeting details, participant records, task responsibilities, and employee updates.

### Non-Functional Requirements
- Secure session-based authentication.
- Easy navigation for government/admin users.
- Reliable data storage and clear reporting.
- Simple and maintainable code structure.

## Key Features
- Schedule and manage official meetings.
- Assign tasks to employees.
- Track task status and completion.
- Maintain meeting attendance records.
- Monitor updates from employees.

## Folder Structure
- `config/` - database configuration
- `controllers/` - login, meetings, tasks, and attendance actions
- `includes/` - shared header and footer
- `modules/` - login, dashboard, meeting, task, and attendance pages
- `database/` - SQL schema and seed data

## Setup Instructions
1. Start Apache and MySQL in XAMPP.
2. Import the SQL from `database/schema.sql`.
3. Make sure the database credentials in `config/db.php` are correct.
4. Run the project from the browser.

