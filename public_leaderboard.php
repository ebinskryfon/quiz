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
    <title>Live Leaderboard - Quiz Competition</title>
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
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1600px;
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
            animation: fadeInDown 0.5s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 42px;
            margin-bottom: 8px;
            font-weight: 600;
            text-shadow: none;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 18px;
            font-weight: 500;
        }
        
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: #e8f5e9;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            color: #2e7d32;
            margin-top: 16px;
            box-shadow: none;
        }
        
        .live-dot {
            width: 12px;
            height: 12px;
            background: #ff4444;
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 10px #ff4444;
        }
        
        @keyframes pulse {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.5;
                transform: scale(0.9);
            }
        }
        
        .last-update {
            color: #95a5a6;
            font-size: 14px;
            margin-top: 12px;
        }
        
        /* Podium Section */
        .podium-section {
            display: none;
            margin-bottom: 50px;
            animation: fadeIn 1s ease-out 0.2s both;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .podium-title {
            text-align: center;
            color: #2c3e50;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 40px;
            text-shadow: none;
        }
        
        .podium {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .podium-card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            position: relative;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            cursor: pointer;
        }
        
        .podium-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .podium-card.first {
            order: 2;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: white;
        }
        
        .podium-card.second {
            order: 1;
            background: linear-gradient(135deg, #C0C0C0 0%, #999999 100%);
            color: white;
        }
        
        .podium-card.third {
            order: 3;
            background: linear-gradient(135deg, #CD7F32 0%, #B8733E 100%);
            color: white;
        }
        
        .podium-card .medal {
            font-size: 72px;
            margin-bottom: 16px;
            animation: bounce 2.5s infinite;
            display: inline-block;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        .podium-card .position {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
            opacity: 0.95;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .podium-card .team-name {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        
        .podium-card .college {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .podium-card .score {
            font-size: 48px;
            font-weight: 900;
            background: rgba(255,255,255,0.2);
            padding: 15px 25px;
            border-radius: 15px;
            display: inline-block;
        }
        
        /* Leaderboard Table */
        .leaderboard-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            animation: fadeIn 1s ease-out 0.4s both;
        }
        
        .leaderboard-title {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .rankings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .rankings-table thead {
            background: #f8f9fa;
            color: #667eea;
        }
        
        .rankings-table th {
            padding: 24px;
            text-align: left;
            font-weight: 700;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #e8e8e8;
        }
        
        .rankings-table th:first-child {
            border-radius: 10px 0 0 0;
        }
        
        .rankings-table th:last-child {
            border-radius: 0 10px 0 0;
        }
        
        .rankings-table tbody tr {
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .rankings-table td {
            padding: 32px 24px;
            font-size: 20px;
            color: #2c3e50;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .rankings-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: scale(1.01);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .rankings-table tr.moving-up {
            animation: slideUpBig 0.6s ease-out;
            background: linear-gradient(90deg, rgba(40, 167, 69, 0.2) 0%, rgba(212, 237, 218, 0.4) 100%) !important;
        }
        
        .rankings-table tr.moving-down {
            animation: slideDownBig 0.6s ease-out;
            background: linear-gradient(90deg, rgba(220, 53, 69, 0.2) 0%, rgba(248, 215, 218, 0.4) 100%) !important;
        }
        
        @keyframes slideUpBig {
            0% { 
                transform: translateY(80px) scale(1.05);
                opacity: 0.3;
            }
            50% {
                transform: translateY(10px) scale(1.02);
            }
            100% { 
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        @keyframes slideDownBig {
            0% { 
                transform: translateY(-80px) scale(1.05);
                opacity: 0.3;
            }
            50% {
                transform: translateY(-10px) scale(1.02);
            }
            100% { 
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        .position-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            font-weight: 800;
            font-size: 24px;
            box-shadow: none;
            transition: all 0.3s;
        }
        
        .position-badge.top3 {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.5);
            animation: glow 2s infinite;
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 4px 15px rgba(255, 215, 0, 0.5); }
            50% { box-shadow: 0 4px 25px rgba(255, 215, 0, 0.8); }
        }
        
        .team-name-cell {
            font-weight: 700;
            color: #2c3e50;
            font-size: 24px;
        }
        
        .score-badge {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 25px;
            font-weight: 800;
            font-size: 28px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            gap: 24px;
            font-size: 18px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        .stat-item.plus {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .stat-item.minus {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .stat-item .icon {
            font-size: 18px;
            font-weight: 700;
        }
        
        /* Fullscreen toggle button */
        .fullscreen-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .fullscreen-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }

        .game-over-btn {
            position: fixed;
            bottom: 30px;
            right: 100px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #f1c40f;
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.3s;
            z-index: 1000;
        }

        .game-over-btn:hover {
            transform: scale(1.1);
        }
        
        /* Animation for new entries */
        @keyframes newEntry {
            0% {
                background: rgba(102, 126, 234, 0.3);
                transform: scale(1.05);
            }
            100% {
                background: transparent;
                transform: scale(1);
            }
        }
        
        .new-entry {
            animation: newEntry 1s ease-out;
        }
        
        @media (max-width: 1024px) {
            .podium {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .podium-card.first,
            .podium-card.second,
            .podium-card.third {
                order: initial;
            }
            
            .header h1 {
                font-size: 36px;
            }
            
            .stats {
                flex-direction: column;
                gap: 12px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .podium-title,
            .leaderboard-title {
                font-size: 24px;
            }
            
            .rankings-table {
                font-size: 14px;
            }
            
            .rankings-table th,
            .rankings-table td {
                padding: 12px 8px;
            }
            
            .fullscreen-btn {
                bottom: 15px;
                right: 15px;
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏆 Live Leaderboard</h1>
            <p class="subtitle">Quiz Competition 2026</p>
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span>LIVE</span>
            </div>
            <div class="last-update">Last updated: <span id="last-update-time">Just now</span></div>
        </div>

        <?php if (count($teams) >= 3): ?>
            <div class="podium-section">
                <h2 class="podium-title">🌟 Top 3 Champions 🌟</h2>
                <div class="podium" id="podium">
                    <?php 
                    $top3 = array_slice($teams, 0, 3);
                    $classes = ['first', 'second', 'third'];
                    $medals = ['🥇', '🥈', '🥉'];
                    $positions = ['Champion', '2nd Place', '3rd Place'];
                    for ($i = 0; $i < 3; $i++):
                        if (isset($top3[$i])):
                    ?>
                        <div class="podium-card <?php echo $classes[$i]; ?>" data-team-id="<?php echo $top3[$i]['id']; ?>">
                            <div class="medal"><?php echo $medals[$i]; ?></div>
                            <div class="position"><?php echo $positions[$i]; ?></div>
                            <div class="team-name"><?php echo htmlspecialchars($top3[$i]['team_name']); ?></div>
                            <div class="college"><?php echo htmlspecialchars($top3[$i]['college_name']); ?></div>
                            <div class="score"><?php echo $top3[$i]['total_score']; ?></div>
                        </div>
                    <?php 
                        endif;
                    endfor; 
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="leaderboard-section">
            <h2 class="leaderboard-title">📊 Complete Rankings</h2>
            <table class="rankings-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Team Name</th>
                        <th>College</th>
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
                            <td>
                                <div class="stats">
                                    <div class="stat-item plus">
                                        <span class="icon">✓</span>
                                        <span class="plus-count"><?php echo $team['plus_count']; ?></span>
                                    </div>
                                    <div class="stat-item minus">
                                        <span class="icon">✗</span>
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

    <button class="fullscreen-btn" onclick="toggleFullscreen()" title="Toggle Fullscreen">
        ⛶
    </button>

    <button class="game-over-btn" onclick="toggleGameOver()" title="Toggle Game End View">
        🏆
    </button>

    <script>
        let currentData = {};
        let updateCount = 0;
        
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
                    updateLastUpdateTime();
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
                        row.classList.remove('moving-up', 'moving-down');
                        
                        if (newPosition < oldPosition) {
                            setTimeout(() => {
                                row.classList.add('moving-up');
                                playSound('up');
                            }, 10);
                            setTimeout(() => row.classList.remove('moving-up'), 600);
                        } else if (newPosition > oldPosition) {
                            setTimeout(() => {
                                row.classList.add('moving-down');
                                playSound('down');
                            }, 10);
                            setTimeout(() => row.classList.remove('moving-down'), 600);
                        }
                    }
                }
            });
            
            // Reorder rows with animation
            setTimeout(() => {
                teams.forEach((team, index) => {
                    const row = tbody.querySelector(`tr[data-team-id="${team.id}"]`);
                    if (row) {
                        tbody.appendChild(row);
                    }
                });
            }, 50);
            
            // Update podium
            updatePodium(teams.slice(0, 3));
        }

        function updatePodium(top3) {
            const podium = document.getElementById('podium');
            if (!podium) return;
            
            const positions = ['Champion', '2nd Place', '3rd Place'];
            
            top3.forEach((team, index) => {
                const cards = podium.querySelectorAll('.podium-card');
                if (cards[index]) {
                    cards[index].dataset.teamId = team.id;
                    cards[index].querySelector('.team-name').textContent = team.team_name;
                    cards[index].querySelector('.college').textContent = team.college_name;
                    cards[index].querySelector('.score').textContent = team.total_score;
                }
            });
        }

        function updateLastUpdateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
            document.getElementById('last-update-time').textContent = timeString;
        }

        function playSound(type) {
            // Optional: Add sound effects for position changes
            // You can add audio files and play them here
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }

        function toggleGameOver() {
            const podiumSection = document.querySelector('.podium-section');
            if (podiumSection.style.display === 'none' || podiumSection.style.display === '') {
                podiumSection.style.display = 'block';
                document.querySelector('.header h1').innerText = '🎉 Game Over - Final Results 🎉';
            } else {
                podiumSection.style.display = 'none';
                document.querySelector('.header h1').innerText = '🏆 Live Leaderboard';
            }
        }

        // Poll every 2 seconds for live updates
        setInterval(fetchLeaderboardData, 2000);
        
        // Initial update of timestamp
        updateLastUpdateTime();
        setInterval(updateLastUpdateTime, 1000);
    </script>
</body>
</html>