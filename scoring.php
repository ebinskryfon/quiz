<?php
require_once 'config.php';

$message = '';
$error = '';
$selected_round = 0;
$selected_question = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['award_marks'])) {
    $question_id = intval($_POST['question_id']);
    $round_id = intval($_POST['round_id']);
    
    if (isset($_POST['marks']) && is_array($_POST['marks'])) {
        foreach ($_POST['marks'] as $team_id => $mark) {
            $team_id = intval($team_id);
            
            if ($mark === 'delete') {
                mysqli_query($conn, "DELETE FROM scores WHERE team_id = $team_id AND question_id = $question_id");
            } else {
                $marks = intval($mark);
                // Check if score already exists
                $check = mysqli_query($conn, "SELECT id FROM scores WHERE team_id = $team_id AND question_id = $question_id");
                
                if (mysqli_num_rows($check) > 0) {
                    // Update existing score
                    mysqli_query($conn, "UPDATE scores SET marks = $marks WHERE team_id = $team_id AND question_id = $question_id");
                } else {
                    // Insert new score
                    mysqli_query($conn, "INSERT INTO scores (team_id, question_id, round_id, marks) VALUES ($team_id, $question_id, $round_id, $marks)");
                }
            }
            
            // Update team total score
            $total_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(marks) as total FROM scores WHERE team_id = $team_id"));
            $total = $total_row['total'] !== null ? $total_row['total'] : 0;
            mysqli_query($conn, "UPDATE teams SET total_score = $total WHERE id = $team_id");
        }
    }
    
    $message = "Marks awarded successfully!";
    $selected_round = $round_id;
    $selected_question = $question_id;
}

// Get selected round and question from GET or POST
if (isset($_GET['round_id'])) {
    $selected_round = intval($_GET['round_id']);
}
if (isset($_GET['question_id'])) {
    $selected_question = intval($_GET['question_id']);
}
if (isset($_POST['round_id']) && !isset($_POST['award_marks'])) {
    $selected_round = intval($_POST['round_id']);
    $selected_question = 0;
}
if (isset($_POST['question_id']) && !isset($_POST['award_marks'])) {
    $selected_round = intval($_POST['round_id']);
    $selected_question = intval($_POST['question_id']);
}

// Get all rounds
$rounds_query = mysqli_query($conn, "SELECT * FROM rounds ORDER BY id");

// Get questions for selected round with attendance status
$questions = [];
if ($selected_round > 0) {
    $questions_query = mysqli_query($conn, "
        SELECT q.*, 
               CASE WHEN EXISTS (
                   SELECT 1 FROM scores s WHERE s.question_id = q.id
               ) THEN 1 ELSE 0 END as is_attended
        FROM questions q 
        WHERE q.round_id = $selected_round 
        ORDER BY q.id
    ");
    $question_number = 1;
    while ($q = mysqli_fetch_assoc($questions_query)) {
        $q['question_number'] = $question_number++;
        $questions[] = $q;
    }
}

// Get all teams with their current scores for the selected question
$teams = [];
if ($selected_question > 0) {
    $teams_query = mysqli_query($conn, "
        SELECT t.*, s.marks as current_marks
        FROM teams t
        LEFT JOIN scores s ON t.id = s.team_id AND s.question_id = $selected_question
        ORDER BY t.team_name
    ");
    while ($team = mysqli_fetch_assoc($teams_query)) {
        $teams[] = $team;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoring System</title>
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
        
        .selection-panel {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 32px;
        }
        
        .selection-panel h2 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        
        .selection-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 15px;
        }
        
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            color: #2c3e50;
            font-family: inherit;
        }
        
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group select:disabled {
            background: #f8f9fa;
            cursor: not-allowed;
        }
        
        .questions-grid {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 32px;
        }
        
        .questions-grid h3 {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .questions-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .question-btn {
            padding: 16px;
            border-radius: 8px;
            border: 2px solid #e8e8e8;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .question-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .question-btn.attended {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .question-btn.unattended {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .question-btn.active {
            background: #667eea;
            border-color: #667eea;
            color: white;
            font-weight: 600;
        }
        
        .scoring-panel {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .question-display {
            background: #f8f9fa;
            padding: 24px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .question-display h3 {
            color: #667eea;
            margin-bottom: 12px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .question-display p {
            color: #2c3e50;
            line-height: 1.6;
            font-size: 16px;
        }
        
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .team-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e8e8e8;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .team-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .team-card h4 {
            color: #2c3e50;
            margin-bottom: 6px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .team-card .college {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 16px;
        }
        
        .mark-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .mark-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e8e8e8;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .mark-btn.plus {
            color: #28a745;
            border-color: #28a745;
        }
        
        .mark-btn.plus:hover, .mark-btn.plus.active {
            background: #28a745;
            color: white;
        }
        
        .mark-btn.minus {
            color: #dc3545;
            border-color: #dc3545;
        }
        
        .mark-btn.minus:hover, .mark-btn.minus.active {
            background: #dc3545;
            color: white;
        }
        
        .mark-btn:hover {
            transform: translateY(-2px);
        }

        .mark-btn.delete {
            color: #7f8c8d;
            border-color: #e8e8e8;
        }
        
        .mark-btn.delete:hover, .mark-btn.delete.active {
            background: #e8e8e8;
            color: #dc3545;
            border-color: #dc3545;
        }
        
        .current-score {
            text-align: center;
            margin-top: 12px;
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }
        
        .submit-section {
            margin-top: 32px;
            text-align: center;
        }
        
        .btn-success {
            padding: 14px 32px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }
        
        input[type="radio"] {
            display: none;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .selection-row {
                grid-template-columns: 1fr;
            }
            
            .questions-list {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .teams-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Scoring System</h1>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="selection-panel">
            <h2>Select Round</h2>
            <form method="POST" action="" id="roundForm">
                <div class="selection-row">
                    <div class="form-group">
                        <label for="round_id">Choose Round</label>
                        <select id="round_id" name="round_id" onchange="this.form.submit()" required>
                            <option value="">-- Select a Round --</option>
                            <?php 
                            mysqli_data_seek($rounds_query, 0);
                            while ($round = mysqli_fetch_assoc($rounds_query)): 
                            ?>
                                <option value="<?php echo $round['id']; ?>" 
                                        <?php echo ($selected_round == $round['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($round['round_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($selected_round > 0 && !empty($questions)): ?>
            <div class="questions-grid">
                <h3>Select Question</h3>
                <div class="questions-list">
                    <?php foreach ($questions as $q): ?>
                        <a href="?round_id=<?php echo $selected_round; ?>&question_id=<?php echo $q['id']; ?>" 
                           class="question-btn <?php echo ($q['is_attended'] == 1) ? 'attended' : 'unattended'; ?> <?php echo ($selected_question == $q['id']) ? 'active' : ''; ?>">
                            Q<?php echo $q['question_number']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($selected_question > 0 && !empty($teams)): ?>
            <?php 
            $question_detail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM questions WHERE id = $selected_question"));
            // Get question number
            $q_num_result = mysqli_query($conn, "
                SELECT COUNT(*) + 1 as q_num 
                FROM questions 
                WHERE round_id = $selected_round AND id < $selected_question
            ");
            $q_num = mysqli_fetch_assoc($q_num_result)['q_num'];
            ?>
            <div class="scoring-panel">
                <div class="question-display">
                    <h3>Question <?php echo $q_num; ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($question_detail['question_text'])); ?></p>
                </div>

                <form method="POST" action="" id="scoringForm">
                    <input type="hidden" name="question_id" value="<?php echo $selected_question; ?>">
                    <input type="hidden" name="round_id" value="<?php echo $selected_round; ?>">
                    
                    <div class="teams-grid">
                        <?php foreach ($teams as $team): ?>
                            <div class="team-card">
                                <h4><?php echo htmlspecialchars($team['team_name']); ?></h4>
                                <div class="college"><?php echo htmlspecialchars($team['college_name']); ?></div>
                                
                                <div class="mark-buttons">
                                    <input type="radio" name="marks[<?php echo $team['id']; ?>]" value="5" 
                                           id="plus_<?php echo $team['id']; ?>" 
                                           <?php echo ($team['current_marks'] !== null && $team['current_marks'] == 5) ? 'checked' : ''; ?>>
                                    <label for="plus_<?php echo $team['id']; ?>" class="mark-btn plus <?php echo ($team['current_marks'] !== null && $team['current_marks'] == 5) ? 'active' : ''; ?>">
                                        +5
                                    </label>
                                    
                                    <input type="radio" name="marks[<?php echo $team['id']; ?>]" value="0" 
                                           id="zero_<?php echo $team['id']; ?>" 
                                           <?php echo ($team['current_marks'] !== null && $team['current_marks'] == 0) ? 'checked' : ''; ?>>
                                    <label for="zero_<?php echo $team['id']; ?>" class="mark-btn <?php echo ($team['current_marks'] !== null && $team['current_marks'] == 0) ? 'active' : ''; ?>">
                                        0
                                    </label>
                                    
                                    <input type="radio" name="marks[<?php echo $team['id']; ?>]" value="-3" 
                                           id="minus_<?php echo $team['id']; ?>" 
                                           <?php echo ($team['current_marks'] !== null && $team['current_marks'] == -3) ? 'checked' : ''; ?>>
                                    <label for="minus_<?php echo $team['id']; ?>" class="mark-btn minus <?php echo ($team['current_marks'] !== null && $team['current_marks'] == -3) ? 'active' : ''; ?>">
                                        -3
                                    </label>

                                    <input type="radio" name="marks[<?php echo $team['id']; ?>]" value="delete" 
                                           id="delete_<?php echo $team['id']; ?>" 
                                           <?php echo ($team['current_marks'] === null) ? 'checked' : ''; ?>>
                                    <label for="delete_<?php echo $team['id']; ?>" class="mark-btn delete <?php echo ($team['current_marks'] === null) ? 'active' : ''; ?>" title="Clear Score">
                                        🗑️
                                    </label>
                                </div>
                                
                                <div class="current-score">
                                    <?php if ($team['current_marks'] !== null): ?>
                                        Current: <?php echo $team['current_marks'] > 0 ? '+' : ''; ?><?php echo $team['current_marks']; ?>
                                    <?php else: ?>
                                        Not Attempted
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="submit-section">
                        <button type="submit" name="award_marks" class="btn-success">
                            💾 Save All Marks
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add click handlers to labels for active state
        document.querySelectorAll('.mark-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active from siblings
                this.parentElement.querySelectorAll('.mark-btn').forEach(b => {
                    b.classList.remove('active');
                });
                // Add active to clicked
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>