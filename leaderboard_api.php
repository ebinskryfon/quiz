<?php
require_once 'config.php';

header('Content-Type: application/json');

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

echo json_encode($teams);
?>