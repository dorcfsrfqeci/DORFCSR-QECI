<?php
/**
 * Firebase Database Bridge
 * Handles authentication with Firebase while keeping user data in database
 */

class FirebaseAuth {
    private $db;
    
    public function __construct($mysqli_connection) {
        $this->db = $mysqli_connection;
    }

    /**
     * Register user in both Firebase and Database
     * Note: Firebase registration must be done from client-side (JavaScript)
     * This function stores the user in the database
     */
    public function registerUserInDatabase($user_id, $full_name, $email, $role, $username, $password = null) {
        try {
            // Check if user already exists
            $check = $this->db->prepare('SELECT account_id FROM user_database WHERE user_id = ? OR email = ?');
            $check->bind_param('ss', $user_id, $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                return [
                    'success' => false,
                    'message' => 'User ID or email already exists'
                ];
            }

            // Insert into database (also store hashed password for local auth fallback)
            $stmt = $this->db->prepare('INSERT INTO user_database (user_id, full_name, role, email, username, password) VALUES (?, ?, ?, ?, ?, ?)');
            
            // Hash the real password if provided, else use placeholder
            $hashed_password = $password ? password_hash($password, PASSWORD_DEFAULT) : password_hash('firebase_managed', PASSWORD_DEFAULT);
            
            $stmt->bind_param('ssssss', $user_id, $full_name, $role, $email, $username, $hashed_password);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'User registered successfully',
                    'user_id' => $user_id
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Database error: ' . $stmt->error
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate user with email
     * Firebase handles password verification on client-side
     * This function fetches user data from database
     */
    public function authenticateUser($email) {
        try {
            $stmt = $this->db->prepare('SELECT user_id, full_name, role, email FROM user_database WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                return [
                    'success' => true,
                    'user' => $user
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'User not found in database'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update user password in database
     * Called after Firebase password reset
     */
    public function updatePasswordInDatabase($email, $new_password_hash) {
        try {
            $stmt = $this->db->prepare('UPDATE user_database SET password = ? WHERE email = ?');
            $stmt->bind_param('ss', $new_password_hash, $email);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Password updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update password'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        try {
            $stmt = $this->db->prepare('SELECT user_id, full_name, role, email, username FROM user_database WHERE user_id = ?');
            $stmt->bind_param('s', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                return $result->fetch_assoc();
            }
            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->db->prepare('SELECT user_id, full_name, role, email, username FROM user_database WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                return $result->fetch_assoc();
            }
            return null;

        } catch (Exception $e) {
            return null;
        }
    }
}
?>
