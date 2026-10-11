<?php
/**
 * Firebase Configuration File
 * 
 * Store your Firebase project configuration here.
 * Get these values from your Firebase console at https://console.firebase.google.com/
 * 
 * Steps:
 * 1. Go to Project Settings (gear icon)
 * 2. Copy the Web API Key and other config values
 * 3. Paste them below
 */

// Firebase configuration - IMPORTANT: Replace these with your actual values from Firebase Console
define('FIREBASE_API_KEY', 'YOUR_FIREBASE_API_KEY');
define('FIREBASE_AUTH_DOMAIN', 'YOUR_PROJECT_ID.firebaseapp.com');
define('FIREBASE_PROJECT_ID', 'YOUR_PROJECT_ID');
define('FIREBASE_STORAGE_BUCKET', 'YOUR_PROJECT_ID.appspot.com');
define('FIREBASE_MESSAGING_SENDER_ID', 'YOUR_MESSAGING_SENDER_ID');
define('FIREBASE_APP_ID', 'YOUR_APP_ID');

// Optional: For Admin SDK (if using server-side authentication)
define('FIREBASE_SERVICE_ACCOUNT_KEY', '/config/serviceAccountKey.json');

?>

