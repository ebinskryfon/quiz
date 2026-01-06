<?php
require_once 'config.php';

$selected_team = 0;
if (isset($_GET['team_id'])) {
    $selected_team = intval($_GET['team_id']);
}

// Get all teams
$teams_query = mysqli_query($conn, "SELECT * FROM teams ORDER BY team_name");

// Get detailed scores if team is selected
$scores = [];
$team_info = null;
if ($selected_team > 0) {
    $team_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teams WHERE id = $selected_team"));
    
    $scores_query = mysqli_query($conn, "
        SELECT 
            s.*,
            r.round_name,
            q.question_text,
            (SELECT COUNT(*) FROM questions q2 WHERE q2.round_id = s.round_id AND q2.id <= q.id) as question_number
        FROM scores s
        JOIN rounds r ON s.round_id = r.id
        JOIN questions q ON s.question_id = q.id
        WHERE s.team_id = $selected_team
        ORDER BY r.id, q.id
    ");
    
    while ($score = mysqli_fetch_assoc($scores_query)) {
        $scores[] = $score;
    }
}

// Get round-wise summary
$round_summary = [];
if ($selected_team > 0) {
    $summary_query = mysqli_query($conn, "
        SELECT 
            r.id,
            r.round_name,
            SUM(s.marks) as round_total,
            COUNT(s.id) as questions_attempted,
            SUM(CASE WHEN s.marks > 0 THEN 1 ELSE 0 END) as correct,
            SUM(CASE WHEN s.marks < 0 THEN 1 ELSE 0 END) as incorrect
        FROM rounds r
        LEFT JOIN scores s ON r.id = s.round_id AND s.team_id = $selected_team
        GROUP BY r.id
        ORDER BY r.id
    ");
    
    while ($summary = mysqli_fetch_assoc($summary_query)) {
        $round_summary[] = $summary;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Scores</title>
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
            margin-bottom: 20px;
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
        
        .team-overview {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 32px;
        }
        
        .team-overview h2 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .team-overview .team-subtitle {
            color: #7f8c8d;
            font-size: 15px;
            margin-bottom: 24px;
        }
        
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 24px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e8e8e8;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }
        
        .round-summary {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 32px;
        }
        
        .round-summary h2 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .round-card {
            background: #f8f9fa;
            padding: 24px;
            border-radius: 10px;
            margin-bottom: 16px;
            border-left: 4px solid #667eea;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .round-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .round-card h3 {
            color: #2c3e50;
            margin-bottom: 12px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .round-stats {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .round-stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 15px;
            color: #2c3e50;
        }
        
        .round-stat strong {
            font-weight: 600;
        }
        
        .detailed-scores {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .detailed-scores h2 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .detailed-scores h3 {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 16px;
        }
        
        .score-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border: 2px solid #e8e8e8;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .score-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .score-item .question-info {
            flex: 1;
        }
        
        .score-item .round-badge {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            display: inline-block;
        }
        
        .score-item .question-text {
            color: #2c3e50;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .score-item .mark {
            font-size: 28px;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 8px;
            min-width: 80px;
            text-align: center;
        }
        
        .score-item .mark.positive {
            background: #d4edda;
            color: #155724;
        }
        
        .score-item .mark.negative {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .overview-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .round-stats {
                flex-direction: column;
                gap: 12px;
            }
            
            .score-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .score-item .mark {
                align-self: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Track Scores</h1>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="selection-panel">
            <h2>Select Team</h2>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="team_id">Choose a team to view detailed scores</label>
                    <select id="team_id" name="team_id" onchange="this.form.submit()">
                        <option value="">-- Select Team --</option>
                        <?php 
                        mysqli_data_seek($teams_query, 0);
                        while ($team = mysqli_fetch_assoc($teams_query)): 
                        ?>
                            <option value="<?php echo $team['id']; ?>"
                                    <?php echo ($selected_team == $team['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['team_name']); ?> - <?php echo htmlspecialchars($team['college_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($team_info): ?>
            <div class="team-overview">
                <h2><?php echo htmlspecialchars($team_info['team_name']); ?></h2>
                <div class="team-subtitle">
                    <?php echo htmlspecialchars($team_info['college_name']); ?> • 
                    <?php echo htmlspecialchars($team_info['district']); ?>
                </div>

                <div class="overview-grid">
                    <div class="stat-card">
                        <div class="value"><?php echo $team_info['total_score']; ?></div>
                        <div class="label">Total Score</div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo count($scores); ?></div>
                        <div class="label">Questions Attempted</div>
                    </div>
                    <div class="stat-card">
                        <div class="value" style="color: #28a745;">
                            <?php echo count(array_filter($scores, function($s) { return $s['marks'] > 0; })); ?>
                        </div>
                        <div class="label">Correct Answers</div>
                    </div>
                    <div class="stat-card">
                        <div class="value" style="color: #dc3545;">
                            <?php echo count(array_filter($scores, function($s) { return $s['marks'] < 0; })); ?>
                        </div>
                        <div class="label">Incorrect Answers</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($round_summary)): ?>
                <div class="round-summary">
                    <h2>Round-wise Performance</h2>
                    <?php foreach ($round_summary as $summary): ?>
                        <?php if ($summary['questions_attempted'] > 0): ?>
                            <div class="round-card">
                                <h3><?php echo htmlspecialchars($summary['round_name']); ?></h3>
                                <div class="round-stats">
                                    <div class="round-stat">
                                        <strong>Score:</strong> 
                                        <span style="color: <?php echo ($summary['round_total'] >= 0) ? '#28a745' : '#dc3545'; ?>; font-weight: 600;">
                                            <?php echo ($summary['round_total'] > 0) ? '+' : ''; ?><?php echo $summary['round_total']; ?>
                                        </span>
                                    </div>
                                    <div class="round-stat">
                                        <strong>Attempted:</strong> <?php echo $summary['questions_attempted']; ?>
                                    </div>
                                    <div class="round-stat" style="color: #28a745; font-weight: 600;">
                                        <strong>✓</strong> <?php echo $summary['correct']; ?>
                                    </div>
                                    <div class="round-stat" style="color: #dc3545; font-weight: 600;">
                                        <strong>✗</strong> <?php echo $summary['incorrect']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($scores)): ?>
                <div class="detailed-scores">
                    <h2>Detailed Question-wise Scores</h2>
                    <?php 
                    $current_round = '';
                    foreach ($scores as $score): 
                        if ($current_round != $score['round_name']) {
                            $current_round = $score['round_name'];
                            echo '<h3>' . htmlspecialchars($current_round) . '</h3>';
                        }
                    ?>
                        <div class="score-item">
                            <div class="question-info">
                                <span class="round-badge">Q<?php echo $score['question_number']; ?></span>
                                <div class="question-text">
                                    <?php echo nl2br(htmlspecialchars($score['question_text'])); ?>
                                </div>
                            </div>
                            <div class="mark <?php echo ($score['marks'] > 0) ? 'positive' : 'negative'; ?>">
                                <?php echo ($score['marks'] > 0) ? '+' : ''; ?><?php echo $score['marks']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="detailed-scores">
                    <div class="no-data">
                        No scores recorded yet for this team.
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>