# 🚀 Installation & Setup Guide - New Features

## Overview
This document outlines all the new features added to the Meeting & Task Planner system and their installation steps.

### New Features Added:
1. ✅ **Forgot Password & Password Reset** - Users can reset their password via email
2. ✅ **Email Notification System** - Automated email notifications for important actions
3. ✅ **Smart Alert Component** - User-friendly notification system on all pages
4. ✅ **MOM (Minutes of Meeting) Module** - Complete MOM management system

---

## 📋 Database Setup

### Step 1: Create New Tables
Run the following SQL migration file:
```bash
File: database/migration_password_reset.sql
```

Execute the SQL commands in your database management tool (PHPMyAdmin, MySQL Workbench, etc.):

```sql
-- Execute all commands from migration_password_reset.sql
```

**Tables created:**
- `password_reset_tokens` - Stores password reset tokens
- `email_notifications` - Logs all email sending
- `system_notifications` - In-app notification system

### Step 2: Verify Database
After running the migration, verify the tables exist:
```sql
SHOW TABLES LIKE '%password_reset%';
SHOW TABLES LIKE '%notification%';
SHOW TABLES LIKE 'meeting_notes';
```

---

## 🔧 New Files & Their Purposes

### Controllers
| File | Purpose |
|------|---------|
| `controllers/PasswordResetController.php` | Handles password reset logic and system notifications |
| `controllers/MOMController.php` | Manages Minutes of Meeting operations |

### Utilities/Services
| File | Purpose |
|------|---------|
| `utils/EmailService.php` | Email sending and notification service |

### Frontend Pages (Modules)
| File | Purpose |
|------|---------|
| `modules/users/forgot-password.php` | User requests password reset |
| `modules/users/reset-password.php` | User sets new password via token |
| `modules/meetings/mom.php` | MOM management interface |

### Components
| File | Purpose |
|------|---------|
| `includes/smart-alert.php` | Reusable alert component for all pages |

---

## 📧 Email Configuration

### Email Service Setup
The system uses PHP's native `mail()` function. Ensure your server has mail capabilities.

**Configuration Details:**
- **From Address:** noreply@laturadmin.gov.in
- **Reply-To:** support@laturadmin.gov.in
- **Email Types:**
  - Password Reset
  - Meeting Notifications
  - Task Assignments
  - System Alerts

### Update Email Settings (Optional)
Edit `utils/EmailService.php` to customize:
```php
// Line 180-182
$headers .= "From: Your Email <your-email@domain.com>\r\n";
$headers .= "Reply-To: support@yourdomain.com\r\n";
```

---

## 🔐 Password Policy

### Requirements
- **Minimum Length:** 8 characters
- **Maximum Length:** 64 characters
- **Must Include:**
  - ✓ Uppercase letters (A-Z)
  - ✓ Lowercase letters (a-z)
  - ✓ Numbers (0-9)
  - ✓ Special characters (!@#$%^&*)

### Token Expiration
- **Reset Link Validity:** 1 hour
- **Automatic Cleanup:** Expired tokens are not automatically deleted but are invalidated

---

## 🔔 Smart Alert System

### How to Use Smart Alerts

#### 1. Include the Component
```php
<?php include __DIR__ . '/includes/smart-alert.php'; ?>
```

#### 2. Set Alert via Session (Server-Side)
```php
$_SESSION['alert'] = [
    'type' => 'success',  // success, error, warning, info
    'title' => 'Success',
    'message' => 'Operation completed successfully'
];
```

#### 3. Show Alert Programmatically (JavaScript)
```javascript
// Show alert dynamically
showSmartAlert('Your message here', 'success', 'Success Title');

// Supported types: success, error, warning, info
```

### Alert Types
- **Success** - Green - Operation completed
- **Error** - Red - Operation failed
- **Warning** - Yellow - Important warning
- **Info** - Blue - Information message

---

## 📝 MOM (Minutes of Meeting) Module

### Accessing MOM
**URL:** `modules/meetings/mom.php?meeting_id=<MEETING_ID>`

### Features
- ✅ Create meeting notes
- ✅ Add note titles and descriptions
- ✅ Link notes to tasks
- ✅ Assign departments
- ✅ Edit existing notes
- ✅ Delete notes
- ✅ View all MOM for a meeting

### Usage Flow
1. Navigate to a specific meeting
2. Click "View Meeting Notes" or access via `mom.php?meeting_id=<ID>`
3. Add notes with title, description, and optional task link
4. Notes are stored and displayed with creator information
5. Only organizers and collectors can create/edit notes

### Database Tables
- **meeting_notes** - Stores MOM data
- Fields: note_title, note_description, department, linked_task_id, created_by, timestamps

---

## 🔄 Integration with Existing Features

### Meetings Module Integration
The MOM module integrates seamlessly with meetings. When a new MOM is created:
- ✅ Notification sent to meeting attendees
- ✅ System notification logged
- ✅ Can link to related tasks
- ✅ Maintains audit trail

### Task Integration
MOMs can be linked to tasks:
- Specify which task is discussed in a note
- Track task-related discussions
- Maintain meeting-to-task relationship

---

## 👤 User Workflows

### Forgot Password Flow
1. User clicks "Forgot Password?" on login page
2. User enters registered email
3. System sends password reset link
4. Link is valid for 1 hour
5. User clicks link and enters new password
6. Password must meet security requirements
7. After reset, user can login with new password

### Creating Meeting Notes
1. Meeting organizer accesses the MOM page
2. Enters note title and description
3. Optionally specifies department and links task
4. Clicks "Save Note"
5. All attendees receive notification
6. Notes can be edited or deleted by creator

---

## 🚨 Error Handling & Validation

### Password Reset
- **Invalid Email:** "Email not found" (without revealing if user exists)
- **Expired Token:** "Invalid or expired reset token"
- **Weak Password:** Specific requirement not met
- **Mismatched Passwords:** "Passwords do not match"

### MOM
- **Unauthorized Access:** User cannot modify others' notes
- **Invalid Meeting:** Meeting not found
- **Invalid Input:** Title or description out of bounds

### Email Sending
- **Failure:** Logged in database, manual retry required
- **Success:** Notification status updated, user receives email

---

## 🔒 Security Features

### Password Security
- ✅ Bcrypt hashing (cost: 12)
- ✅ Secure random token generation
- ✅ Token hash storage (not plain tokens)
- ✅ CSRF protection on all forms
- ✅ Rate limiting on login attempts

### Data Protection
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (HTML escaping)
- ✅ Session-based authentication
- ✅ Role-based access control

### Email Security
- ✅ Sender verification
- ✅ User authentication check
- ✅ Audit trail logging
- ✅ Retry mechanism for failed sends

---

## 📊 Database Queries Reference

### Get Unread Notifications
```php
$controller = new PasswordResetController();
$notifications = $controller->getUnreadNotifications($user_id);
```

### Get MOMs for Meeting
```php
$moms = MOMController::getMOMByMeeting($meeting_id);
```

### Mark Notification as Read
```php
PasswordResetController::markNotificationAsRead($notification_id);
```

---

## 🧪 Testing Checklist

- [ ] **Password Reset Flow**
  - [ ] Request password reset
  - [ ] Receive email with reset link
  - [ ] Link expires after 1 hour
  - [ ] Reset with valid password
  - [ ] Login with new password

- [ ] **Smart Alerts**
  - [ ] Success alerts display correctly
  - [ ] Error alerts show proper messages
  - [ ] Alerts auto-dismiss after 5 seconds
  - [ ] Manual close works

- [ ] **MOM Module**
  - [ ] Create new MOM
  - [ ] Edit existing MOM
  - [ ] Delete MOM
  - [ ] Link task to MOM
  - [ ] View all MOMs for meeting

- [ ] **Email Notifications**
  - [ ] Password reset email received
  - [ ] Email contains reset link
  - [ ] Meeting notification email sent
  - [ ] Email format is readable

---

## 📞 Support & Troubleshooting

### Common Issues

#### "Email not sending"
- Check PHP mail() is configured on server
- Verify SMTP settings in server configuration
- Check error logs in `logs/` directory

#### "Password reset link expired"
- Link is valid for 1 hour only
- User must request new reset link
- Old tokens cannot be reused

#### "MOM not saving"
- Verify user is organizer or collector role
- Check database connection
- Ensure meeting_id is valid

#### "Smart alert not showing"
- Verify smart-alert.php is included
- Check browser console for JavaScript errors
- Ensure Font Awesome icons are loaded

---

## 📌 Important Notes

1. **Database Backups:** Always backup database before migration
2. **Email Testing:** Test email configuration with a test email first
3. **Session:** Ensure session.save_path is writable
4. **File Permissions:** Ensure upload directories have correct permissions
5. **SSL/TLS:** Recommended for password reset (HTTPS)

---

## 🎯 Next Steps

1. Run database migration
2. Configure email settings (if needed)
3. Test forgot password flow
4. Test MOM module
5. Test smart alerts on all pages
6. Deploy to production

---

**Document Version:** 1.0  
**Last Updated:** 2026-07-01  
**Compatible With:** PHP 7.4+, MySQL 5.7+
