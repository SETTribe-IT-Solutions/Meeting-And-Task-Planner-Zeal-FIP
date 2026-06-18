# Login System Documentation

## Overview
The login system includes a beautiful UI with username/password fields, session management, and secure authentication.

## Files Created

### 1. **login.php** (Root directory)
   - Main login page with attractive UI
   - Handles form submission via AJAX
   - Shows success/error messages
   - Redirects to dashboard on successful login

### 2. **models/users/login.php**
   - Backend login handler
   - Validates credentials against database
   - Creates session variables
   - Returns JSON response

### 3. **models/users/logout.php**
   - Destroys session
   - Clears cookies
   - Redirects to login page

### 4. **helpers/SessionHelper.php**
   - `isLoggedIn()` - Check if user is authenticated
   - `requireLogin()` - Redirect if not logged in
   - `getCurrentUser()` - Get current user data
   - `logout()` - Destroy session
   - `hasRole($role)` - Check user role

### 5. **assets/css/login.css**
   - Modern, responsive login page styling
   - Animations and transitions
   - Mobile-friendly design

### 6. **database/create-users-table.sql**
   - SQL script to create users table
   - Includes test users

## Setup Instructions

### Step 1: Create Database Table
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your `meeting_planner` database
3. Go to "SQL" tab
4. Copy contents of `database/create-users-table.sql`
5. Paste and execute

### Step 2: Test Login
1. Navigate to: `http://localhost/Meeting_Task/Meeting-And-Task-Planner-Zeal-FIP/login.php`
2. Use test credentials:
   - **Username:** admin
   - **Password:** password123

### Step 3: Protect Pages
Add this to any page that requires authentication:

```php
<?php
session_start();
require_once 'helpers/SessionHelper.php';
requireLogin(); // Redirects to login if not authenticated

// Your protected page content here
?>
```

## Features

✅ Beautiful, modern UI with animations
✅ Show/Hide password toggle
✅ Remember me functionality
✅ AJAX form submission
✅ Error/Success messages
✅ Session management
✅ Secure password hashing (bcrypt)
✅ Mobile responsive
✅ Database integration

## Database Schema

```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT
  username VARCHAR(50) UNIQUE NOT NULL
  email VARCHAR(100) UNIQUE NOT NULL
  password VARCHAR(255) NOT NULL (bcrypt hash)
  role VARCHAR(20) DEFAULT 'user'
  created_at TIMESTAMP
  updated_at TIMESTAMP
  is_active BOOLEAN DEFAULT TRUE
)
```

## Security Features

- Passwords hashed with bcrypt
- SQL injection prevention (prepared statements)
- Session management
- CSRF-ready form structure
- Password verification with `password_verify()`

## Creating New Users

Use PHP to hash passwords:

```php
$password = 'user_password_here';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
```

Insert into database:
```sql
INSERT INTO users (username, email, password, role) 
VALUES ('username', 'email@example.com', '$hashedPassword', 'user');
```

## Customization

### Change Dashboard Redirect
Edit `models/users/login.php` line where it sets `redirect`:
```php
$response['redirect'] = 'your/custom/dashboard/path';
```

### Modify Session Variables
Add more to `$_SESSION` in `models/users/login.php` as needed

### Style Changes
Edit `assets/css/login.css` to customize colors, fonts, and animations

## Troubleshooting

**Issue:** "Database connection error"
- Check `config/db.php` credentials
- Ensure MySQL is running
- Verify database exists

**Issue:** "Invalid username or password"
- Check test user exists in database
- Verify bcrypt hashing is correct

**Issue:** "Cannot find database table"
- Run `create-users-table.sql` script
- Check table name matches query in `models/users/login.php`

## API Response Format

Login returns JSON:
```json
{
  "success": true/false,
  "message": "Success or error message",
  "redirect": "path/to/dashboard"
}
```
