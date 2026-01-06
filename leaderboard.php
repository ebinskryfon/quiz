<?php
require_once 'config.php';

// Get leaderboard data
$leaderboard_query = mysqli_query($conn, "
    SELECT 
        t.id,
        t.team_name,
        t.college_name,
        t.district,
        t.total_score,
        COUNT(DISTINCT CASE WHEN s.marks > 0 THEN s.id END) as plus_count,
        COUNT(DISTINCT CASE WHEN s.marks < 0 THEN s.id END) as minus_count
    FROM teams t
    LEFT JOIN scores s ON t.id = s.team_id
    GROUP BY t.id
    ORDER BY t.total_score DESC, t.team_name ASC
");

$teams = [];
while ($team = mysqli_fetch_assoc($leaderboard_query)) {
    $teams[] = $team;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
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
            max-width: 1400px;
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
        
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #d4edda;
            color: #155724;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .live-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .leaderboard {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .podium-section {
            margin-bottom: 48px;
        }
        
        .podium-section h2 {
            text-align: center;
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 32px;
        }
        
        .podium {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .podium-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 32px 24px;
            border-radius: 12px;
            text-align: center;
            color: white;
            position: relative;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .podium-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        
        .podium-card.first {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            order: 2;
        }
        
        .podium-card.second {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            order: 1;
        }
        
        .podium-card.third {
            background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%);
            order: 3;
        }
        
        .podium-card .medal {
            font-size: 56px;
            margin-bottom: 12px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .podium-card .position {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            opacity: 0.95;
        }
        
        .podium-card .team-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .podium-card .college {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 16px;
        }
        
        .podium-card .score {
            font-size: 36px;
            font-weight: 700;
        }
        
        .rankings-section h2 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        
        .rankings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .rankings-table thead {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .rankings-table th {
            padding: 16px 20px;
            text-align: left;
            color: #667eea;
            font-weight: 600;
            border-bottom: 2px solid #e8e8e8;
            font-size: 15px;
        }
        
        .rankings-table tbody {
            position: relative;
        }
        
        .rankings-table tr {
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
        }
        
        .rankings-table td {
            padding: 20px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 15px;
            color: #2c3e50;
        }
        
        .rankings-table tr:hover {
            background: #f8f9fa;
        }
        
        .rankings-table tr.moving-up {
            animation: slideUp 0.5s ease-out;
            background: #d4edda !important;
        }
        
        .rankings-table tr.moving-down {
            animation: slideDown 0.5s ease-out;
            background: #f8d7da !important;
        }
        
        @keyframes slideUp {
            0% { transform: translateY(60px); opacity: 0.5; }
            100% { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes slideDown {
            0% { transform: translateY(-60px); opacity: 0.5; }
            100% { transform: translateY(0); opacity: 1; }
        }
        
        .position-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .position-badge.top3 {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            box-shadow: 0 2px 8px rgba(253, 160, 133, 0.4);
        }
        
        .team-name-cell {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .score-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .score-badge.positive {
            background: #d4edda;
            color: #155724;
        }
        
        .score-badge.negative {
            background: #f8d7da;
            color: #721c24;
        }
        
        .score-badge.zero {
            background: #e8e8e8;
            color: #7f8c8d;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            font-size: 14px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        
        .stat-item.plus {
            color: #28a745;
        }
        
        .stat-item.minus {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .podium {
                grid-template-columns: 1fr;
            }
            
            .podium-card.first,
            .podium-card.second,
            .podium-card.third {
                order: initial;
            }
            
            .rankings-table {
                font-size: 14px;
            }
            
            .rankings-table th,
            .rankings-table td {
                padding: 12px 8px;
            }
            
            .stats {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🏅 Live Leaderboard</h1>
                <div class="live-indicator" style="margin-top: 8px;">
                    <div class="live-dot"></div>
                    <span>Live Updates</span>
                </div>
            </div>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="leaderboard">
            <?php if (count($teams) >= 3): ?>
                <div class="podium-section">
                    <h2>🏆 Top 3 Teams</h2>
                    <div class="podium" id="podium">
                        <?php 
                        $top3 = array_slice($teams, 0, 3);
                        $classes = ['first', 'second', 'third'];
                        $medals = ['🥇', '🥈', '🥉'];
                        for ($i = 0; $i < 3; $i++):
                            if (isset($top3[$i])):
                        ?>
                            <div class="podium-card <?php echo $classes[$i]; ?>" data-team-id="<?php echo $top3[$i]['id']; ?>">
                                <div class="medal"><?php echo $medals[$i]; ?></div>
                                <div class="position"><?php echo ($i + 1); ?><?php echo ($i == 0) ? 'st' : (($i == 1) ? 'nd' : 'rd'); ?> Place</div>
                                <div class="team-name"><?php echo htmlspecialchars($top3[$i]['team_name']); ?></div>
                                <div class="college"><?php echo htmlspecialchars($top3[$i]['college_name']); ?></div>
                                <div class="score"><?php echo $top3[$i]['total_score']; ?> pts</div>
                            </div>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="rankings-section">
                <h2>Complete Rankings</h2>
                <table class="rankings-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Team Name</th>
                            <th>College</th>
                            <th>District</th>
                            <th>Statistics</th>
                            <th>Total Score</th>
                        </tr>
                    </thead>
                    <tbody id="leaderboard-body">
                        <?php 
                        $position = 1;
                        foreach ($teams as $team): 
                        ?>
                            <tr data-team-id="<?php echo $team['id']; ?>" data-position="<?php echo $position; ?>">
                                <td>
                                    <span class="position-badge <?php echo ($position <= 3) ? 'top3' : ''; ?>">
                                        <?php echo $position; ?>
                                    </span>
                                </td>
                                <td class="team-name-cell"><?php echo htmlspecialchars($team['team_name']); ?></td>
                                <td><?php echo htmlspecialchars($team['college_name']); ?></td>
                                <td><?php echo htmlspecialchars($team['district']); ?></td>
                                <td>
                                    <div class="stats">
                                        <div class="stat-item plus">
                                            <span>✓</span>
                                            <span class="plus-count"><?php echo $team['plus_count']; ?></span>
                                        </div>
                                        <div class="stat-item minus">
                                            <span>✗</span>
                                            <span class="minus-count"><?php echo $team['minus_count']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="score-badge <?php echo ($team['total_score'] > 0) ? 'positive' : (($team['total_score'] < 0) ? 'negative' : 'zero'); ?>">
                                        <?php echo ($team['total_score'] > 0) ? '+' : ''; ?><?php echo $team['total_score']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                        $position++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Polling for updates (Alternative to WebSocket if you don't have WebSocket server)
        let currentData = {};
        
        // Initialize current data
        document.querySelectorAll('#leaderboard-body tr').forEach(row => {
            const teamId = row.dataset.teamId;
            currentData[teamId] = {
                position: parseInt(row.dataset.position),
                element: row
            };
        });

        function fetchLeaderboardData() {
            fetch('leaderboard_api.php')
                .then(response => response.json())
                .then(data => {
                    updateLeaderboard(data);
                })
                .catch(error => console.error('Error fetching leaderboard:', error));
        }

        function updateLeaderboard(teams) {
            const tbody = document.getElementById('leaderboard-body');
            const newData = {};
            
            teams.forEach((team, index) => {
                const position = index + 1;
                newData[team.id] = { position, team };
                
                let row = tbody.querySelector(`tr[data-team-id="${team.id}"]`);
                
                if (row) {
                    const oldPosition = parseInt(row.dataset.position);
                    const newPosition = position;
                    
                    // Update row data
                    row.dataset.position = newPosition;
                    
                    // Update position badge
                    const badge = row.querySelector('.position-badge');
                    badge.textContent = newPosition;
                    badge.className = 'position-badge' + (newPosition <= 3 ? ' top3' : '');
                    
                    // Update score
                    const scoreEl = row.querySelector('.score-badge');
                    scoreEl.textContent = (team.total_score > 0 ? '+' : '') + team.total_score;
                    scoreEl.className = 'score-badge ' + (team.total_score > 0 ? 'positive' : (team.total_score < 0 ? 'negative' : 'zero'));
                    
                    // Update stats
                    row.querySelector('.plus-count').textContent = team.plus_count;
                    row.querySelector('.minus-count').textContent = team.minus_count;
                    
                    // Animate position change
                    if (oldPosition !== newPosition) {
                        if (newPosition < oldPosition) {
                            row.classList.add('moving-up');
                            setTimeout(() => row.classList.remove('moving-up'), 500);
                        } else if (newPosition > oldPosition) {
                            row.classList.add('moving-down');
                            setTimeout(() => row.classList.remove('moving-down'), 500);
                        }
                    }
                }
            });
            
            // Reorder rows
            teams.forEach((team, index) => {
                const row = tbody.querySelector(`tr[data-team-id="${team.id}"]`);
                if (row) {
                    tbody.appendChild(row);
                }
            });
            
            // Update podium
            updatePodium(teams.slice(0, 3));
        }

        function updatePodium(top3) {
            const podium = document.getElementById('podium');
            if (!podium) return;
            
            top3.forEach((team, index) => {
                const card = podium.querySelector(`.podium-card[data-team-id="${team.id}"]`);
                if (card) {
                    card.querySelector('.team-name').textContent = team.team_name;
                    card.querySelector('.college').textContent = team.college_name;
                    card.querySelector('.score').textContent = team.total_score + ' pts';
                }
            });
        }

        // Poll every 3 seconds
        setInterval(fetchLeaderboardData, 3000);
    </script>
</body>
</html>