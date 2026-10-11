# Firebase Password Reset Setup Guide

## Overview
This guide will help you integrate Firebase Authentication for password reset functionality in your application.

## Step 1: Create a Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Create a new project" or select an existing project
3. Enter your project name (e.g., "DORCFSRF-Auth")
4. Follow the setup wizard and click "Create project"

## Step 2: Enable Email/Password Authentication

1. In Firebase Console, navigate to **Authentication** (left sidebar)
2. Click on the **Sign-in method** tab
3. Enable **Email/Password** provider
4. Click **Save**

## Step 3: Get Your Firebase Configuration

1. In Firebase Console, go to **Project Settings** (gear icon in top-left)
2. Under **Your apps**, click the **Web** option (or add if not present)
3. Copy the Firebase configuration object containing:
   - `apiKey`
   - `authDomain`
   - `projectId`
   - `storageBucket`
   - `messagingSenderId`
   - `appId`

## Step 4: Update Configuration Files

### A. Update forgot.php
Edit `c:/xampp/htdocs/DORCFSRF/pages/click_button/forgot.php`

Replace the `firebaseConfig` object (around line 73) with your actual Firebase config:

```javascript
const firebaseConfig = {
    apiKey: "YOUR_FIREBASE_API_KEY",
    authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_PROJECT_ID.appspot.com",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};
```

### B. Update reset-password.php
Edit `c:/xampp/htdocs/DORCFSRF/pages/click_button/reset-password.php`

Replace the `firebaseConfig` object (around line 230) with the same Firebase config above.

## Step 5: Configure Email Templates (Optional but Recommended)

1. In Firebase Console, go to **Authentication > Templates**
2. Click on **Password reset**
3. Customize the email subject and template
4. Make sure the reset link redirects to:
   ```
   https://yourdomain.com/DORCFSRF/pages/click_button/reset-password.php?mode=resetPassword&oobCode=
   ```

## Step 6: Set Up Email Domain (Optional for Production)

For production, you can set up a custom email domain:

1. Go to **Authentication > Templates**
2. Click **Custom domain** and follow the setup steps

## Step 7: Update Login Forms (Optional)

The reset link in all login forms will now point to `forgot.php`:
- [pages/login_form/admin.php](../../pages/login_form/admin.php)
- [pages/login_form/staff.php](../../pages/login_form/staff.php)
- [pages/login_form/student.php](../../pages/login_form/student.php)

## How the Password Reset Flow Works

### User initiates password reset:
1. User clicks "Forgot your password?" on login page
2. User is taken to `forgot.php`
3. User enters their email address
4. Firebase sends a password reset email

### User resets password:
1. User receives email with password reset link
2. Clicking the link takes user to `reset-password.php?oobCode=...&mode=resetPassword`
3. User enters new password (with validation)
4. Password is reset via Firebase
5. User is redirected to login page

## Testing the Integration

### Test Email Reset (Development):
1. Go to Firebase Console > Authentication > Users
2. Create a test user manually (if needed)
3. Click "Send password reset email" next to the user
4. Check your email for the reset link
5. Follow the link and reset the password

### Test from Your App:
1. Go to `http://localhost/DORCFSRF/pages/click_button/forgot.php`
2. Enter your email address
3. Check your email for the reset link
4. Click the link to reset your password

## Firebase Security Rules

Firebase automatically secures the reset flow:
- Reset codes expire after 1 hour
- Reset codes are single-use
- Reset emails are rate-limited to prevent abuse

## Troubleshooting

### Issue: "sendPasswordResetEmail is not a function"
**Solution:** Make sure you've imported Firebase Auth SDK:
```html
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js"></script>
```

### Issue: Emails not being received
**Solution:** 
- Check your spam/junk folder
- Make sure email/password auth is enabled in Firebase Console
- Check Firebase usage tab to confirm emails are being sent

### Issue: "Invalid or missing reset link"
**Solution:**
- The link may have expired (1 hour limit)
- The code may be incorrect
- Request a new password reset email

### Issue: Firebase config errors
**Solution:**
- Double-check your Firebase configuration values in the code
- Make sure your project ID matches exactly
- Verify all required fields are present

## Database Integration Notes

Your existing database structure remains unchanged. The authentication is handled entirely by Firebase. When a user resets their password through Firebase:

1. Firebase stores the new password hash
2. The next time they log in, the email/password is verified against Firebase
3. Your existing login flow in `student.php`, `staff.php`, `admin.php` continues to work

## Integration with Your Login System

Your current login forms authenticate against the `user_database` table. To fully integrate Firebase:

### Option A: Dual Authentication (Current Implementation)
- Keep existing database authentication
- Add Firebase for password reset only

### Option B: Complete Firebase Migration (Recommended for new projects)
- Replace database authentication with Firebase
- Use Firebase's user management

For now, the setup handles **Option A (Password Reset via Firebase)**.

## Security Best Practices

1. ✓ Always use HTTPS in production
2. ✓ Firebase automatically hashes all passwords
3. ✓ Reset codes expire after 1 hour
4. ✓ Rate limiting prevents brute force attacks
5. ✓ Never expose API keys in public repositories

## Support

- Firebase Documentation: https://firebase.google.com/docs/auth
- Firebase Support: https://firebase.google.com/support

---

**Last Updated:** April 10, 2026
