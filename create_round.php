<?php
require_once 'config.php';

$message = '';
$error = '';
$edit_mode = false;
$edit_round = null;

// Handle Edit Request
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_query = mysqli_query($conn, "SELECT * FROM rounds WHERE id = $edit_id");
    if ($edit_query && mysqli_num_rows($edit_query) > 0) {
        $edit_round = mysqli_fetch_assoc($edit_query);
        $edit_mode = true;
    }
}

// Handle Delete Request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Check if round has questions before deleting
    $check_query = mysqli_query($conn, "SELECT id FROM questions WHERE round_id = $delete_id LIMIT 1");
    if (mysqli_num_rows($check_query) > 0) {
        $error = "Cannot delete round. It contains questions. Please delete questions first.";
    } else {
        if (mysqli_query($conn, "DELETE FROM rounds WHERE id = $delete_id")) {
            $message = "Round deleted successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $round_name = mysqli_real_escape_string($conn, $_POST['round_name']);
    $round_id = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0;
    
    if (!empty($round_name)) {
        if ($round_id > 0) {
            // Update existing round
            $sql = "UPDATE rounds SET round_name = '$round_name' WHERE id = $round_id";
            if (mysqli_query($conn, $sql)) {
                $message = "Round updated successfully!";
                $edit_mode = false;
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        } else {
            // Create new round
            $sql = "INSERT INTO rounds (round_name) VALUES ('$round_name')";
            if (mysqli_query($conn, $sql)) {
                $message = "Round created successfully!";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    } else {
        $error = "Please enter a round name.";
    }
}

// Get all rounds with question count
$rounds_query = mysqli_query($conn, "
    SELECT r.*, COUNT(q.id) as question_count 
    FROM rounds r 
    LEFT JOIN questions q ON r.id = q.round_id 
    GROUP BY r.id 
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Round</title>
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
            max-width: 1000px;
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
        
        .rounds-list {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .round-card {
            background: #f8f9fa;
            padding: 24px;
            border-radius: 10px;
            margin-bottom: 16px;
            border-left: 4px solid #667eea;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .round-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .round-content {
            flex: 1;
        }
        
        .round-card h3 {
            color: #2c3e50;
            margin-bottom: 12px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .round-info {
            color: #7f8c8d;
            font-size: 15px;
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .round-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .round-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit, .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-edit {
            background: #667eea;
            color: white;
        }
        
        .btn-edit:hover {
            background: #5568d3;
        }
        
        .btn-delete {
            background: #e8e8e8;
            color: #7f8c8d;
        }
        
        .btn-delete:hover {
            background: #dc3545;
            color: white;
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
            
            .round-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .round-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .round-info {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Create Round</h1>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="action-bar">
            <h2>All Rounds</h2>
            <button class="btn-primary" onclick="openModal()">+ Add New Round</button>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error && $_SERVER['REQUEST_METHOD'] != 'POST'): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="rounds-list">
            <?php if (mysqli_num_rows($rounds_query) > 0): ?>
                <?php while ($round = mysqli_fetch_assoc($rounds_query)): ?>
                    <div class="round-card">
                        <div class="round-content">
                            <h3><?php echo htmlspecialchars($round['round_name']); ?></h3>
                            <div class="round-info">
                                <span>📝 <?php echo $round['question_count']; ?> Questions</span>
                                <span>📅 Created: <?php echo date('M d, Y', strtotime($round['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="round-actions">
                            <a href="?edit=<?php echo $round['id']; ?>" class="btn-edit" onclick="return openEditModal(<?php echo $round['id']; ?>, '<?php echo htmlspecialchars($round['round_name'], ENT_QUOTES); ?>')">
                                ✏️ Edit
                            </a>
                            <a href="?delete=<?php echo $round['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this round?');">
                                🗑️ Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No rounds created yet. Click "Add New Round" to get started.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="roundModal" class="modal <?php echo ($edit_mode || ($error && $_SERVER['REQUEST_METHOD'] == 'POST')) ? 'show' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $edit_mode ? 'Edit Round' : 'Add New Round'; ?></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>

            <?php if ($error && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" id="edit_round_id" name="round_id" value="<?php echo $edit_mode ? $edit_round['id'] : ''; ?>">
                
                <div class="form-group">
                    <label for="round_name">Round Name *</label>
                    <input type="text" id="round_name" name="round_name" 
                           placeholder="e.g., Round 1, Rapid Fire, Finals" 
                           value="<?php echo $edit_mode ? htmlspecialchars($edit_round['round_name']) : ''; ?>" 
                           required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <?php echo $edit_mode ? 'Update Round' : 'Create Round'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('roundModal').classList.add('show');
            document.getElementById('round_name').value = '';
            document.getElementById('edit_round_id').value = '';
            document.querySelector('#roundModal h2').innerText = 'Add New Round';
            document.querySelector('#roundModal .btn-primary').innerText = 'Create Round';
        }

        function openEditModal(id, name) {
            event.preventDefault();
            document.getElementById('roundModal').classList.add('show');
            document.getElementById('round_name').value = name;
            document.getElementById('edit_round_id').value = id;
            document.querySelector('#roundModal h2').innerText = 'Edit Round';
            document.querySelector('#roundModal .btn-primary').innerText = 'Update Round';
            return false;
        }

        function closeModal() {
            document.getElementById('roundModal').classList.remove('show');
            <?php if ($message || $edit_mode): ?>
                window.location.href = 'create_round.php';
            <?php endif; ?>
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('roundModal');
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
    </script>
</body>
</html>