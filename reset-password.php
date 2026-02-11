<?php
/**
 * Password Reset Script - Run once to reset admin password
 * IMPORTANT: Delete this file after running!
 */

require_once 'includes/db.php';

$newPassword = 'password123';
$passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
    $result = $stmt->execute([$passwordHash, 'admin']);
    
    if ($result) {
        echo '<div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;">';
        echo '<h3>✓ Password Reset Successful!</h3>';
        echo '<p><strong>Username:</strong> admin</p>';
        echo '<p><strong>Password:</strong> password123</p>';
        echo '<p><strong>New Hash:</strong> ' . htmlspecialchars($passwordHash) . '</p>';
        echo '<hr>';
        echo '<p style="color: red;"><strong>⚠️ IMPORTANT: Delete this file (reset-password.php) now for security!</strong></p>';
        echo '<p>You can now login at: <a href="login.php">login.php</a></p>';
        echo '</div>';
    } else {
        echo '<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px;">';
        echo '<h3>✗ Password Reset Failed!</h3>';
        echo '<p>Update did not execute. Check database permissions.</p>';
        echo '</div>';
    }
} catch (PDOException $e) {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px;">';
    echo '<h3>✗ Database Error</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>
