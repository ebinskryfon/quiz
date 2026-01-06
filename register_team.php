<?php
require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_name = mysqli_real_escape_string($conn, $_POST['team_name']);
    $college_name = mysqli_real_escape_string($conn, $_POST['college_name']);
    $district = mysqli_real_escape_string($conn, $_POST['district']);
    $num_members = intval($_POST['num_members']);
    
    if (!empty($team_name) && !empty($college_name) && !empty($district) && $num_members > 0) {
        $sql = "INSERT INTO teams (team_name, college_name, district, num_members) 
                VALUES ('$team_name', '$college_name', '$district', $num_members)";
        
        if (mysqli_query($conn, $sql)) {
            $message = "Team registered successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill all fields correctly.";
    }
}

// Get all teams
$teams_query = mysqli_query($conn, "SELECT * FROM teams ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Team</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 24px 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 4px solid #667eea;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
        }
        
        .back-btn {
            padding: 10px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            border: 2px solid #667eea;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .back-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .action-bar {
            background: white;
            padding: 20px 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .action-bar h2 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 600;
        }
        
        .btn-primary {
            padding: 12px 28px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .teams-list {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 16px 12px;
            text-align: left;
            border-bottom: 1px solid #e8e8e8;
        }
        
        table th {
            background: #f8f9fa;
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        table td {
            color: #2c3e50;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-header h2 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
        }
        
        .close-btn {
            font-size: 32px;
            color: #7f8c8d;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
        }
        
        .close-btn:hover {
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 15px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            color: #2c3e50;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 32px;
        }
        
        .btn-secondary {
            padding: 12px 28px;
            background: white;
            color: #7f8c8d;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #f8f9fa;
            color: #2c3e50;
        }
        
        .message {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 15px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .header, .action-bar {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .modal-content {
                padding: 28px 20px;
            }
            
            table {
                font-size: 14px;
            }
            
            table th, table td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Register Team</h1>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="action-bar">
            <h2>Registered Teams</h2>
            <button class="btn-primary" onclick="openModal()">+ Add New Team</button>
        </div>

        <div class="teams-list">
            <?php if (mysqli_num_rows($teams_query) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Team Name</th>
                            <th>College</th>
                            <th>District</th>
                            <th>Members</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($team = mysqli_fetch_assoc($teams_query)): ?>
                            <tr>
                                <td><?php echo $team['id']; ?></td>
                                <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                <td><?php echo htmlspecialchars($team['college_name']); ?></td>
                                <td><?php echo htmlspecialchars($team['district']); ?></td>
                                <td><?php echo $team['num_members']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($team['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No teams registered yet. Click "Add New Team" to get started.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="teamModal" class="modal <?php echo ($message || $error) ? 'show' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Team</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="team_name">Team Name *</label>
                    <input type="text" id="team_name" name="team_name" required>
                </div>

                <div class="form-group">
                    <label for="college_name">College Name *</label>
                    <input type="text" id="college_name" name="college_name" required>
                </div>

                <div class="form-group">
                    <label for="district">District *</label>
                    <input type="text" id="district" name="district" required>
                </div>

                <div class="form-group">
                    <label for="num_members">Number of Members *</label>
                    <input type="number" id="num_members" name="num_members" min="1" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Register Team</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('teamModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('teamModal').classList.remove('show');
            <?php if ($message): ?>
                window.location.href = 'register_team.php';
            <?php endif; ?>
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('teamModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Auto-close success message after 3 seconds
        <?php if ($message): ?>
            setTimeout(function() {
                closeModal();
            }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>