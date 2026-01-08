<?php
require_once 'config.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $action = $_POST['action'];
    $admin_id = $_SESSION['admin_id'];

    // Verify password
    $query = "SELECT * FROM admins WHERE id = $admin_id AND password = '" . mysqli_real_escape_string($conn, $password) . "'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        // Password correct, proceed with clearing data
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
        
        $success = true;
        $tables_cleared = [];

        try {
            // Helper logic to clear dependent tables based on selection
            if ($action == 'clear_scores' || $action == 'clear_questions' || $action == 'clear_rounds' || $action == 'clear_teams' || $action == 'clear_all') {
                mysqli_query($conn, "TRUNCATE TABLE scores");
                mysqli_query($conn, "UPDATE teams SET total_score = 0");
                if ($action == 'clear_scores') $tables_cleared[] = "Scores";
            }

            if ($action == 'clear_questions' || $action == 'clear_rounds' || $action == 'clear_all') {
                mysqli_query($conn, "TRUNCATE TABLE questions");
                if ($action == 'clear_questions') $tables_cleared[] = "Questions (and Scores)";
            }

            if ($action == 'clear_rounds' || $action == 'clear_all') {
                mysqli_query($conn, "TRUNCATE TABLE rounds");
                if ($action == 'clear_rounds') $tables_cleared[] = "Rounds (and Questions & Scores)";
            }

            if ($action == 'clear_teams' || $action == 'clear_all') {
                mysqli_query($conn, "TRUNCATE TABLE teams");
                if ($action == 'clear_teams') $tables_cleared[] = "Teams (and Scores)";
            }

            if ($action == 'clear_all') {
                $tables_cleared[] = "All Data (Teams, Rounds, Questions, Scores)";
            }

        } catch (Exception $e) {
            $success = false;
            $error = "Database error: " . $e->getMessage();
        }

        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

        if ($success && empty($error)) {
            $message = "Successfully cleared: " . implode(", ", $tables_cleared);
        }
    } else {
        $error = "Incorrect password provided. Action cancelled.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Quiz System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: white; padding: 24px 32px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; border-top: 4px solid #667eea; }
        .header h1 { color: #2c3e50; font-size: 28px; font-weight: 600; }
        .back-btn { padding: 10px 24px; background: white; color: #667eea; text-decoration: none; border-radius: 8px; border: 2px solid #667eea; transition: all 0.3s; font-weight: 500; }
        .back-btn:hover { background: #667eea; color: white; }
        .settings-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 32px; }
        .danger-zone { border: 2px solid #f8d7da; }
        .danger-zone h2 { color: #dc3545; margin-bottom: 16px; font-size: 24px; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e8e8e8; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; }
        .form-control:focus { outline: none; border-color: #667eea; }
        .btn-danger { padding: 14px 28px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.3s; width: 100%; }
        .btn-danger:hover { background: #c82333; }
        .message { padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; font-size: 15px; }
        .message.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .message.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Settings</h1>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="settings-card danger-zone">
            <h2>⚠️ Data Management</h2>
            <p style="margin-bottom: 24px; color: #721c24;">
                <strong>Warning:</strong> These actions are irreversible. Clearing data will permanently remove it from the database.
            </p>

            <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure you want to clear this data? This action cannot be undone.');">
                <div class="form-group">
                    <label for="action">Select Action</label>
                    <select name="action" id="action" class="form-control" required>
                        <option value="">-- Select Data to Clear --</option>
                        <option value="clear_scores">Clear Scores Only (Reset Game Progress)</option>
                        <option value="clear_questions">Clear Questions (and Scores)</option>
                        <option value="clear_rounds">Clear Rounds (and Questions & Scores)</option>
                        <option value="clear_teams">Clear Teams (and Scores)</option>
                        <option value="clear_all">🗑️ Clear ALL Data (Factory Reset)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Admin Password Confirmation</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your admin password to confirm" required>
                </div>

                <button type="submit" class="btn-danger">Execute Clear Command</button>
            </form>
        </div>
    </div>
</body>
</html>