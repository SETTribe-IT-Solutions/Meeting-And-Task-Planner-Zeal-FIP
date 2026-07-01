# 🚀 Quick Reference - New Features

## Features Overview

### 1️⃣ Forgot Password Flow
```
User Login Page
    ↓ (Click "Forgot Password?")
Forgot Password Page (forgot-password.php)
    ↓ (Enter email)
Email Sent with Reset Link
    ↓ (Click link in email)
Reset Password Page (reset-password.php?token=xxx)
    ↓ (Set new password)
Password Updated ✅
    ↓
Login with new password
```

**Files Involved:**
- `controllers/PasswordResetController.php`
- `utils/EmailService.php`
- `modules/users/forgot-password.php`
- `modules/users/reset-password.php`
- Database: `password_reset_tokens` table

---

### 2️⃣ MOM (Minutes of Meeting) Module
```
Meeting View Page
    ↓ (Click "View MOM" button)
MOM Page (mom.php?meeting_id=X)
    ↓ (Add/Edit/Delete notes)
Notes saved to database
    ↓
Attendees notified ✅
```

**Files Involved:**
- `controllers/MOMController.php`
- `modules/meetings/mom.php`
- `modules/meetings/view.php` (button added)
- Database: `meeting_notes` table

---

### 3️⃣ Smart Alert System
```
Server-Side Action
    ↓
Set Session Alert
    ↓
Page Loads
    ↓
Smart Alert Renders
    ↓
Auto-dismiss after 5 seconds
```

**Usage Examples:**

#### Set Alert (Server-Side)
```php
$_SESSION['alert'] = [
    'type' => 'success',
    'title' => 'Success',
    'message' => 'Operation completed'
];
```

#### Show Alert (JavaScript)
```javascript
showSmartAlert('Message here', 'success', 'Title');
```

**Supported Types:** success, error, warning, info

---

### 4️⃣ Email Notifications
```
System Event
    ↓ (Password reset, Meeting created, etc.)
EmailService::send*Email()
    ↓
Email queued
    ↓
Sent via PHP mail()
    ↓
Logged to email_notifications table
```

**Email Types:**
- Password reset emails
- Meeting notification emails
- Task assignment emails
- System alert emails

---

## 📁 File Structure

```
Meeting-And-Task-Planner-Zeal-FIP/
├── controllers/
│   ├── PasswordResetController.php (NEW)
│   └── MOMController.php (NEW)
├── modules/
│   ├── users/
│   │   ├── forgot-password.php (NEW)
│   │   ├── reset-password.php (NEW)
│   │   └── login.php (UPDATED)
│   └── meetings/
│       ├── mom.php (NEW)
│       └── view.php (UPDATED)
├── includes/
│   └── smart-alert.php (NEW)
├── utils/
│   └── EmailService.php (NEW)
├── database/
│   └── migration_password_reset.sql (NEW)
├── SETUP_GUIDE.md (NEW)
├── CHANGELOG.md (NEW)
└── index.php (UPDATED)
```

---

## 🔧 Configuration

### Email Settings
Edit `utils/EmailService.php` lines 180-182:
```php
$headers .= "From: Your Email <your-email@domain.com>\r\n";
$headers .= "Reply-To: support@domain.com\r\n";
```

### Password Requirements
Edit `controllers/PasswordResetController.php` for custom rules:
- Min length: 8 characters
- Max length: 64 characters
- Must include: UPPERCASE, lowercase, numbers, special chars

### Token Expiration
Edit `controllers/PasswordResetController.php`:
```php
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Change duration here
```

---

## 🧪 Quick Test Checklist

### Password Reset
- [ ] Navigate to login page
- [ ] Click "Forgot Password?"
- [ ] Enter valid email
- [ ] Check email for reset link
- [ ] Click link and set new password
- [ ] Login with new password

### MOM Module
- [ ] Open any meeting
- [ ] Click "View MOM" button
- [ ] Create new note
- [ ] Edit note
- [ ] Delete note
- [ ] Verify attendees get notified

### Smart Alerts
- [ ] Create a meeting (should show success alert)
- [ ] Update a task (should show success alert)
- [ ] Try invalid action (should show error alert)
- [ ] Verify alerts auto-dismiss
- [ ] Manually close alert

---

## 💡 Usage Examples

### Create Password Reset Token
```php
require_once 'controllers/PasswordResetController.php';

$result = PasswordResetController::generateResetToken($user_id, $email);
if ($result['success']) {
    $token = $result['token'];
    $resetLink = APP_URL . "/modules/users/reset-password.php?token=" . $token;
    // Send email with link
}
```

### Send Email
```php
require_once 'utils/EmailService.php';

$result = EmailService::sendPasswordResetEmail(
    $email,
    $resetLink,
    $userName
);
```

### Create System Notification
```php
PasswordResetController::createSystemNotification(
    $user_id,
    'Title',
    'Message',
    'success',      // alert type
    'password'      // category
);
```

### Get MOM for Meeting
```php
require_once 'controllers/MOMController.php';

$moms = MOMController::getMOMByMeeting($meeting_id);
foreach ($moms as $mom) {
    echo $mom['note_title'];
}
```

### Show Alert Programmatically
```javascript
// From any JavaScript file
showSmartAlert('Operation completed!', 'success', 'Success');
```

---

## 🔒 Security Notes

### CSRF Protection
```php
// Always include CSRF token in forms
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Validate in controller
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // Reject request
}
```

### SQL Injection Prevention
```php
// Always use prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
```

### XSS Prevention
```php
// Always escape output
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

---

## 🐛 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Email not sending | Check PHP mail config, verify SMTP settings |
| Password reset link not working | Verify token validity, check database connection |
| MOM page not loading | Ensure meeting_id parameter exists, check permissions |
| Smart alert not showing | Verify smart-alert.php included, check Font Awesome CDN |
| Database errors | Run migration, verify table creation |

---

## 📊 Database Tables

### password_reset_tokens
```sql
id, user_id, email, token, token_hash, expires_at, used_at, created_at
```

### email_notifications
```sql
id, user_id, recipient_email, subject, notification_type, 
sent_status, retry_count, error_message, sent_at, created_at
```

### system_notifications
```sql
id, user_id, title, message, alert_type, notification_category,
related_entity_type, related_entity_id, is_read, created_at, read_at
```

### meeting_notes
```sql
id, meeting_id, note_title, note_description, department, 
linked_task_id, created_by, created_at, updated_at
```

---

## 🎯 Integration Checklist

Before deploying to production:

- [ ] Run database migration
- [ ] Test forgot password flow end-to-end
- [ ] Test MOM creation and editing
- [ ] Verify smart alerts on all pages
- [ ] Check email delivery
- [ ] Test CSRF protection
- [ ] Verify user authorization
- [ ] Test on mobile devices
- [ ] Check browser console for errors
- [ ] Load test with multiple users
- [ ] Backup database before migration

---

## 📞 Support Resources

- **Setup Guide:** See `SETUP_GUIDE.md`
- **Changes Log:** See `CHANGELOG.md`
- **Code Comments:** Check inline PHP comments
- **Database:** Check table schemas in `database/schema.sql`

---

**Last Updated:** July 1, 2026  
**Version:** 1.0  
**For:** Meeting & Task Planner v2.1.0
