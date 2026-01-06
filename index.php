<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Scoring System - Dashboard</title>
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
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            text-align: center;
            border-top: 4px solid #667eea;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 32px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 32px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 42px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .stat-card p {
            color: #7f8c8d;
            font-size: 15px;
            font-weight: 500;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .menu-card {
            background: white;
            padding: 36px 28px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }
        
        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
        }
        
        .menu-card .icon {
            font-size: 56px;
            margin-bottom: 16px;
            line-height: 1;
        }
        
        .menu-card h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .menu-card p {
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏆 Quiz Scoring System</h1>
            <p>Comprehensive Quiz Management Dashboard</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>12</h3>
                <p>Total Teams</p>
            </div>
            <div class="stat-card">
                <h3>5</h3>
                <p>Active Rounds</p>
            </div>
            <div class="stat-card">
                <h3>48</h3>
                <p>Total Questions</p>
            </div>
        </div>

        <div class="menu-grid">
            <a href="register_team.php" class="menu-card">
                <div class="icon">👥</div>
                <h3>Register Team</h3>
                <p>Add new teams to the competition</p>
            </a>

            <a href="create_round.php" class="menu-card">
                <div class="icon">🔄</div>
                <h3>Create Round</h3>
                <p>Add new quiz rounds</p>
            </a>

            <a href="create_question.php" class="menu-card">
                <div class="icon">❓</div>
                <h3>Create Questions</h3>
                <p>Add questions to rounds</p>
            </a>

            <a href="scoring.php" class="menu-card">
                <div class="icon">✅</div>
                <h3>Scoring</h3>
                <p>Award marks to teams</p>
            </a>

            <a href="leaderboard.php" class="menu-card">
                <div class="icon">🏅</div>
                <h3>Leaderboard</h3>
                <p>View rankings and scores</p>
            </a>

            <a href="track_scores.php" class="menu-card">
                <div class="icon">📊</div>
                <h3>Track Scores</h3>
                <p>Detailed score analysis</p>
            </a>
        </div>
    </div>
</body>
</html>