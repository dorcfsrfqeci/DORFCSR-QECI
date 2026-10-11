# Firebase Password Reset - Quick Reference Card

## 🚀 Quick Start (5 Minutes)

### Step 1: Get Firebase Config (2 min)
1. Go to https://console.firebase.google.com/
2. Project Settings → Web
3. Copy all values from the config object

### Step 2: Update forgot.php (1 min)
Replace `firebaseConfig` at line ~73:
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

### Step 3: Update reset-password.php (1 min)
Replace `firebaseConfig` at line ~230 with same config above

### Step 4: Test It! (1 min)
1. Go to http://localhost/DORCFSRF/pages/click_button/forgot.php
2. Enter your email
3. Check email for reset link
4. Click link to reset password

---

## 📍 File Locations

| File | Purpose |
|------|---------|
| `pages/click_button/forgot.php` | Send reset email |
| `pages/click_button/reset-password.php` | Reset password form |
| `FIREBASE_SETUP_GUIDE.md` | Full setup guide |
| `FIREBASE_IMPLEMENTATION_SUMMARY.md` | Implementation details |

---

## 🔑 Firebase Config Values You Need

Get these from Firebase Console → Project Settings → Web:

```
apiKey              → "AIza..."
authDomain          → "project-id.firebaseapp.com"
projectId           → "project-id-123456"
storageBucket       → "project-id.appspot.com"
messagingSenderId   → "123456789"
appId               → "1:123456789:web:abc123def456"
```

---

## ✅ Enable Email/Password Auth

1. Firebase Console → Authentication
2. Click "Sign-in method" tab
3. Enable "Email/Password"
4. Save

---

## 🧪 Test URLs

```
Request Reset:  http://localhost/DORCFSRF/pages/click_button/forgot.php
Reset Password: http://localhost/DORCFSRF/pages/click_button/reset-password.php?oobCode=...
Admin Login:    http://localhost/DORCFSRF/pages/login_form/admin.php
Staff Login:    http://localhost/DORCFSRF/pages/login_form/staff.php
Student Login:  http://localhost/DORCFSRF/pages/login_form/student.php
```

---

## 🎯 Flow

```
forgot.php (Request Reset)
        ↓
  Firebase sends email
        ↓
  User clicks email link
        ↓
reset-password.php (Reset Password)
        ↓
  Password updated in Firebase
        ↓
  Redirect to login
```

---

## ⚠️ Common Issues

| Issue | Solution |
|-------|----------|
| Emails not received | Check spam folder, verify auth is enabled |
| Invalid link error | Codes expire in 1 hour, request new one |
| Firebase config error | Verify all 6 values are correct |
| Password requirements fail | Need 8+ chars, 1 uppercase, 1 lowercase, 1 number |

---

## 📞 Links

- Firebase Config: https://console.firebase.google.com/
- Full Docs: https://firebase.google.com/docs/auth/web/manage-users
- Password Reset: https://firebase.google.com/docs/auth/web/manage-users#send_a_password_reset_email

---

**Last Updated:** April 10, 2026 | **Status:** Ready to Deploy ✅
