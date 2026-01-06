<?php
require_once 'config.php';

$message = '';
$error = '';

// Handle Delete Request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Check if question has scores associated
    $check_scores = mysqli_query($conn, "SELECT id FROM scores WHERE question_id = $delete_id LIMIT 1");
    if (mysqli_num_rows($check_scores) > 0) {
        $error = "Cannot delete question. It has already been scored.";
    } else {
        if (mysqli_query($conn, "DELETE FROM questions WHERE id = $delete_id")) {
            $message = "Question deleted successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $round_id = intval($_POST['round_id']);
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
    
    if ($round_id > 0 && !empty($question_text)) {
        if ($question_id > 0) {
            $sql = "UPDATE questions SET round_id = $round_id, question_text = '$question_text' WHERE id = $question_id";
            $success_msg = "Question updated successfully!";
        } else {
            $sql = "INSERT INTO questions (round_id, question_text) VALUES ($round_id, '$question_text')";
            $success_msg = "Question added successfully!";
        }
        
        if (mysqli_query($conn, $sql)) {
            $message = $success_msg;
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Please select a round and enter a question.";
    }
}

// Get all rounds
$rounds_query = mysqli_query($conn, "SELECT * FROM rounds ORDER BY created_at DESC");

// Get all questions with round names
$questions_query = mysqli_query($conn, "
    SELECT q.*, r.round_name 
    FROM questions q 
    JOIN rounds r ON q.round_id = r.id 
    ORDER BY r.id, q.id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Questions</title>
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
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            color: #2c3e50;
            font-family: inherit;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
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
        
        .question-badge {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 8px;
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
            <h1>❓ Create Questions</h1>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="action-bar">
            <h2>All Questions</h2>
            <button class="btn-primary" onclick="openModal()">+ Add New Question</button>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error && $_SERVER['REQUEST_METHOD'] != 'POST'): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

<div class="rounds-list">
    <?php 
    $current_round = '';
    $question_number = 0;
    while ($question = mysqli_fetch_assoc($questions_query)): 
        if ($current_round != $question['round_name']) {
            $current_round = $question['round_name'];
            $question_number = 1; // Reset counter for each new round
            echo '<h3 style="margin: 20px 0 15px; color: #2c3e50;">' . htmlspecialchars($current_round) . '</h3>';
        } else {
            $question_number++; // Increment for same round
        }
    ?>
        <div class="round-card">
            <div class="round-content">
                <span class="question-badge">Q<?php echo $question_number; ?></span>
                <div style="color: #2c3e50; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                </div>
            </div>
            <div class="round-actions">
                <button class="btn-edit" onclick='openEditModal(<?php echo $question['id']; ?>, <?php echo $question['round_id']; ?>, <?php echo htmlspecialchars(json_encode($question['question_text']), ENT_QUOTES); ?>)'>
                    ✏️ Edit
                </button>
                <a href="?delete=<?php echo $question['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this question?');">
                    🗑️ Delete
                </a>
            </div>
        </div>
    <?php endwhile; ?>
</div>

    <!-- Modal -->
    <div id="questionModal" class="modal <?php echo ($error && $_SERVER['REQUEST_METHOD'] == 'POST') ? 'show' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Question</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>

            <?php if ($error && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" id="question_id" name="question_id" value="">
                
                <div class="form-group">
                    <label for="round_id">Select Round *</label>
                    <select id="round_id" name="round_id" required>
                        <option value="">-- Choose a Round --</option>
                        <?php 
                        mysqli_data_seek($rounds_query, 0);
                        while ($round = mysqli_fetch_assoc($rounds_query)): 
                        ?>
                            <option value="<?php echo $round['id']; ?>">
                                <?php echo htmlspecialchars($round['round_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="question_text">Question Text *</label>
                    <textarea id="question_text" name="question_text" 
                              placeholder="Enter your question here..." required></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Add Question</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('questionModal').classList.add('show');
            document.getElementById('question_id').value = '';
            document.getElementById('round_id').value = '';
            document.getElementById('question_text').value = '';
            document.querySelector('#questionModal h2').innerText = 'Add New Question';
            document.querySelector('#questionModal .btn-primary').innerText = 'Add Question';
        }

        function openEditModal(id, roundId, text) {
            document.getElementById('questionModal').classList.add('show');
            document.getElementById('question_id').value = id;
            document.getElementById('round_id').value = roundId;
            document.getElementById('question_text').value = text;
            document.querySelector('#questionModal h2').innerText = 'Edit Question';
            document.querySelector('#questionModal .btn-primary').innerText = 'Update Question';
        }

        function closeModal() {
            document.getElementById('questionModal').classList.remove('show');
            <?php if ($message): ?>
                window.location.href = 'create_question.php';
            <?php endif; ?>
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('questionModal');
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