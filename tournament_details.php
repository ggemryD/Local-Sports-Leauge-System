<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Check if tournament ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: tournaments.php");
    exit;
}

$tournament_id = $_GET["id"];

// Fetch tournament details
$sql = "SELECT t.*, s.sport_name, s.score_type 
        FROM tournaments t 
        JOIN sports s ON t.sport_id = s.id 
        WHERE t.id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($tournament = mysqli_fetch_assoc($result)){
            $tournament_name = $tournament['tournament_name'];
            $elimination_type = $tournament['elimination_type'];
            $status = $tournament['status'];
            $sport_name = $tournament['sport_name'];
            $score_type = $tournament['score_type'];
        } else {
            header("location: tournaments.php");
            exit;
        }
    }
}

// Add team to tournament
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_team"])){
    $team_id = trim($_POST["team_id"]);
    
    // Check if team is already in tournament
    $check_sql = "SELECT * FROM tournament_teams WHERE tournament_id = ? AND team_id = ?";
    if($check_stmt = mysqli_prepare($conn, $check_sql)){
        mysqli_stmt_bind_param($check_stmt, "ii", $tournament_id, $team_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if(mysqli_num_rows($check_result) == 0){
            $insert_sql = "INSERT INTO tournament_teams (tournament_id, team_id) VALUES (?, ?)";
            if($insert_stmt = mysqli_prepare($conn, $insert_sql)){
                mysqli_stmt_bind_param($insert_stmt, "ii", $tournament_id, $team_id);
                mysqli_stmt_execute($insert_stmt);
            }
        }
    }
}

// Update match result
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_match"])){
    $match_id = $_POST["match_id"];
    
    if($_POST["score_type"] == "binary") {
        // For binary scoring (Mobile Legends, etc.)
        $winner = $_POST["winner"];
        $team1_score = ($winner == "team1") ? 1 : 0;
        $team2_score = ($winner == "team2") ? 1 : 0;
    } else {
        // For points scoring (Basketball, etc.)
        $team1_score = $_POST["team1_score"];
        $team2_score = $_POST["team2_score"];
    }
    
    $update_sql = "UPDATE matches SET team1_score = ?, team2_score = ?, status = 'completed' WHERE id = ?";
    if($update_stmt = mysqli_prepare($conn, $update_sql)){
        mysqli_stmt_bind_param($update_stmt, "iii", $team1_score, $team2_score, $match_id);
        if(mysqli_stmt_execute($update_stmt)){
            // Update standings
            $match_sql = "SELECT team1_id, team2_id FROM matches WHERE id = ?";
            if($match_stmt = mysqli_prepare($conn, $match_sql)){
                mysqli_stmt_bind_param($match_stmt, "i", $match_id);
                mysqli_stmt_execute($match_stmt);
                $match_result = mysqli_stmt_get_result($match_stmt);
                $match = mysqli_fetch_assoc($match_result);
                
                // Update winner's record
                $winner_id = ($team1_score > $team2_score) ? $match['team1_id'] : $match['team2_id'];
                $loser_id = ($team1_score > $team2_score) ? $match['team2_id'] : $match['team1_id'];
                
                mysqli_query($conn, "UPDATE tournament_teams SET wins = wins + 1, points = points + 2 
                                   WHERE tournament_id = $tournament_id AND team_id = $winner_id");
                mysqli_query($conn, "UPDATE tournament_teams SET losses = losses + 1 
                                   WHERE tournament_id = $tournament_id AND team_id = $loser_id");

                // Automatic Progression for Single Elimination
                if($elimination_type == 'single'){
                    // Get current match info
                    $cur_match_sql = "SELECT round, match_number FROM matches WHERE id = ?";
                    if($cur_stmt = mysqli_prepare($conn, $cur_match_sql)){
                        mysqli_stmt_bind_param($cur_stmt, "i", $match_id);
                        mysqli_stmt_execute($cur_stmt);
                        $cur_match = mysqli_fetch_assoc(mysqli_stmt_get_result($cur_stmt));
                        $cur_round = $cur_match['round'];
                        $cur_match_num = $cur_match['match_number'];

                        // Calculate next match position
                        $next_round = $cur_round + 1;
                        $next_match_num = ceil($cur_match_num / 2);
                        $is_team1 = ($cur_match_num % 2 != 0); // Odd match numbers are team1 in next round

                        // Check if the next match already exists
                        $check_next_sql = "SELECT id FROM matches WHERE tournament_id = ? AND round = ? AND match_number = ?";
                        if($check_stmt = mysqli_prepare($conn, $check_next_sql)){
                            mysqli_stmt_bind_param($check_stmt, "iii", $tournament_id, $next_round, $next_match_num);
                            mysqli_stmt_execute($check_stmt);
                            $next_match_res = mysqli_stmt_get_result($check_stmt);
                            
                            if(mysqli_num_rows($next_match_res) > 0){
                                // Update existing next round match
                                $next_match = mysqli_fetch_assoc($next_match_res);
                                $next_match_id = $next_match['id'];
                                $update_next_sql = $is_team1 ? 
                                    "UPDATE matches SET team1_id = ? WHERE id = ?" : 
                                    "UPDATE matches SET team2_id = ? WHERE id = ?";
                                if($upd_stmt = mysqli_prepare($conn, $update_next_sql)){
                                    mysqli_stmt_bind_param($upd_stmt, "ii", $winner_id, $next_match_id);
                                    mysqli_stmt_execute($upd_stmt);
                                }
                            } else {
                                // Create new next round match
                                $insert_next_sql = $is_team1 ? 
                                    "INSERT INTO matches (tournament_id, team1_id, round, match_number, match_date) VALUES (?, ?, ?, ?, ?)" : 
                                    "INSERT INTO matches (tournament_id, team2_id, round, match_number, match_date) VALUES (?, ?, ?, ?, ?)";
                                if($ins_stmt = mysqli_prepare($conn, $insert_next_sql)){
                                    $next_match_date = date('Y-m-d H:i:s', strtotime('+1 day'));
                                    mysqli_stmt_bind_param($ins_stmt, "iiiis", $tournament_id, $winner_id, $next_round, $next_match_num, $next_match_date);
                                    mysqli_stmt_execute($ins_stmt);
                                }
                            }
                        }

                        // Check if it was the final (determine 1st and 2nd)
                        // For simplicity, we can check if there are any more matches in the next round
                        // or if this was the last match possible.
                        // Better: If no next round is possible (e.g., only 1 match in current round), it's the final.
                        $matches_in_round_sql = "SELECT COUNT(*) as count FROM tournament_teams WHERE tournament_id = ?";
                        $tt_res = mysqli_query($conn, $matches_in_round_sql);
                        $total_teams = mysqli_fetch_assoc($tt_res)['count'];
                        $max_rounds = ceil(log($total_teams, 2));
                        
                        if($cur_round == $max_rounds){
                            // This was the final
                            mysqli_query($conn, "UPDATE tournament_teams SET rank = 1 WHERE tournament_id = $tournament_id AND team_id = $winner_id");
                            mysqli_query($conn, "UPDATE tournament_teams SET rank = 2 WHERE tournament_id = $tournament_id AND team_id = $loser_id");
                            mysqli_query($conn, "UPDATE tournaments SET status = 'completed' WHERE id = $tournament_id");
                        }
                        
                        // Handle 3rd Place Match (if it was a semifinal)
                        if($cur_round == $max_rounds - 1){
                            // This was a semifinal. Create/Update 3rd place match.
                            $third_place_match_num = 1;
                            $round_3rd = $max_rounds + 1; // Special round for 3rd place
                            
                            $check_3rd_sql = "SELECT id FROM matches WHERE tournament_id = ? AND round = ? AND match_number = ?";
                            if($check_3rd_stmt = mysqli_prepare($conn, $check_3rd_sql)){
                                mysqli_stmt_bind_param($check_3rd_stmt, "iii", $tournament_id, $round_3rd, $third_place_match_num);
                                mysqli_stmt_execute($check_3rd_stmt);
                                $res_3rd = mysqli_stmt_get_result($check_3rd_stmt);
                                
                                if(mysqli_num_rows($res_3rd) > 0){
                                    $match_3rd = mysqli_fetch_assoc($res_3rd);
                                    $match_3rd_id = $match_3rd['id'];
                                    $upd_3rd_sql = $is_team1 ? 
                                        "UPDATE matches SET team1_id = ? WHERE id = ?" : 
                                        "UPDATE matches SET team2_id = ? WHERE id = ?";
                                    if($upd_3rd_stmt = mysqli_prepare($conn, $upd_3rd_sql)){
                                        mysqli_stmt_bind_param($upd_3rd_stmt, "ii", $loser_id, $match_3rd_id);
                                        mysqli_stmt_execute($upd_3rd_stmt);
                                    }
                                } else {
                                    $ins_3rd_sql = $is_team1 ? 
                                        "INSERT INTO matches (tournament_id, team1_id, round, match_number, match_date) VALUES (?, ?, ?, ?, ?)" : 
                                        "INSERT INTO matches (tournament_id, team2_id, round, match_number, match_date) VALUES (?, ?, ?, ?, ?)";
                                    if($ins_3rd_stmt = mysqli_prepare($conn, $ins_3rd_sql)){
                                        $match_date_3rd = date('Y-m-d H:i:s', strtotime('+1 day'));
                                        mysqli_stmt_bind_param($ins_3rd_stmt, "iiiis", $tournament_id, $loser_id, $round_3rd, $third_place_match_num, $match_date_3rd);
                                        mysqli_stmt_execute($ins_3rd_stmt);
                                    }
                                }
                            }
                        }

                        // Special case: If this was the 3rd place match
                        if($cur_round == $max_rounds + 1){
                            mysqli_query($conn, "UPDATE tournament_teams SET rank = 3 WHERE tournament_id = $tournament_id AND team_id = $winner_id");
                        }
                    }
                }
            }
        }
    }
}

// Generate Bracket
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate_bracket"])){
    // Get all teams in tournament
    $teams = [];
    $teams_sql = "SELECT team_id FROM tournament_teams WHERE tournament_id = ?";
    if($teams_stmt = mysqli_prepare($conn, $teams_sql)){
        mysqli_stmt_bind_param($teams_stmt, "i", $tournament_id);
        mysqli_stmt_execute($teams_stmt);
        $teams_result = mysqli_stmt_get_result($teams_stmt);
        while($row = mysqli_fetch_assoc($teams_result)){
            $teams[] = $row['team_id'];
        }
    }

    if(count($teams) < 2){
        $_SESSION["error"] = "At least 2 teams are required to generate a bracket.";
    } else {
        // Clear existing matches first? Or just add new ones? 
        // Let's just add new ones for now, but usually users want a fresh start.
        
        $match_date = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        if($elimination_type == 'round_robin'){
            // Round Robin: Everyone plays everyone
            for($i = 0; $i < count($teams); $i++){
                for($j = $i + 1; $j < count($teams); $j++){
                    $insert_match = "INSERT INTO matches (tournament_id, team1_id, team2_id, match_date, round, match_number) VALUES (?, ?, ?, ?, 1, ?)";
                    if($stmt = mysqli_prepare($conn, $insert_match)){
                        $match_num = $i * count($teams) + $j;
                        mysqli_stmt_bind_param($stmt, "iiisi", $tournament_id, $teams[$i], $teams[$j], $match_date, $match_num);
                        mysqli_stmt_execute($stmt);
                        $match_date = date('Y-m-d H:i:s', strtotime($match_date . ' + 2 hours'));
                    }
                }
            }
            $_SESSION["success"] = "Round Robin bracket generated successfully!";
        } else {
            // Single Elimination (First Round)
            shuffle($teams);
            $match_num = 1;
            for($i = 0; $i < count($teams) - 1; $i += 2){
                $insert_match = "INSERT INTO matches (tournament_id, team1_id, team2_id, match_date, round, match_number) VALUES (?, ?, ?, ?, 1, ?)";
                if($stmt = mysqli_prepare($conn, $insert_match)){
                    mysqli_stmt_bind_param($stmt, "iiisi", $tournament_id, $teams[$i], $teams[$i+1], $match_date, $match_num);
                    mysqli_stmt_execute($stmt);
                    $match_date = date('Y-m-d H:i:s', strtotime($match_date . ' + 2 hours'));
                    $match_num++;
                }
            }
            if(count($teams) % 2 != 0){
                // Handling bye: last team moves to next round
                $last_team_id = end($teams);
                $next_match_num = ceil($match_num / 2);
                $is_team1 = ($match_num % 2 != 0);
                
                $insert_bye = $is_team1 ? 
                    "INSERT INTO matches (tournament_id, team1_id, round, match_number, match_date) VALUES (?, ?, 2, ?, ?)" : 
                    "INSERT INTO matches (tournament_id, team2_id, round, match_number, match_date) VALUES (?, ?, 2, ?, ?)";
                
                if($stmt = mysqli_prepare($conn, $insert_bye)){
                    $next_date = date('Y-m-d H:i:s', strtotime($match_date . ' + 1 day'));
                    mysqli_stmt_bind_param($stmt, "iiis", $tournament_id, $last_team_id, $next_match_num, $next_date);
                    mysqli_stmt_execute($stmt);
                }
                $_SESSION["info"] = "Bracket generated. " . htmlspecialchars($last_team_id) . " received a bye and moved to Round 2.";
            } else {
                $_SESSION["success"] = "First round bracket generated successfully!";
            }
        }
        
        // Update tournament status to ongoing
        mysqli_query($conn, "UPDATE tournaments SET status = 'ongoing' WHERE id = $tournament_id AND status = 'upcoming'");
        
        header("location: tournament_details.php?id=" . $tournament_id);
        exit;
    }
}

// Clear Matches
        if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["clear_matches"])){
            $clear_sql = "DELETE FROM matches WHERE tournament_id = ?";
            if($clear_stmt = mysqli_prepare($conn, $clear_sql)){
                mysqli_stmt_bind_param($clear_stmt, "i", $tournament_id);
                mysqli_stmt_execute($clear_stmt);
                
                // Reset team records
                mysqli_query($conn, "UPDATE tournament_teams SET wins = 0, losses = 0, points = 0, rank = 0 WHERE tournament_id = $tournament_id");
                
                $_SESSION["success"] = "All matches cleared and standings reset.";
                header("location: tournament_details.php?id=" . $tournament_id);
                exit;
            }
        }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tournament_name; ?> - Tournament Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
            padding-top: 20px;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .sidebar a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border-radius: 0 50px 50px 0;
            margin: 4px 0;
            margin-right: 16px;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateX(5px);
        }
        .sidebar a.active {
            background: #fff;
            color: #1e3c72;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .sidebar a i {
            margin-right: 10px;
            font-size: 1.4rem;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px;
        }
        .card-header h5 {
            margin: 0;
            color: #1e3c72;
            font-weight: 600;
        }
        .match-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .match-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .team-name {
            font-weight: 600;
            color: #1e3c72;
            font-size: 1.1rem;
        }
        .match-score {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2a5298;
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 10px;
        }
        .match-date {
            color: #666;
            font-size: 0.9rem;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border: none;
        }
        .tournament-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .tournament-header h2 {
            color: #1e3c72;
            margin: 0;
            font-weight: 600;
        }
        .tournament-info {
            color: #666;
            margin-top: 10px;
        }
        .tournament-info i {
            color: #1e3c72;
            margin-right: 5px;
        }
        .standings-table th {
            background: #f8f9fa;
            color: #1e3c72;
            font-weight: 600;
            border: none;
        }
        .standings-table td {
            vertical-align: middle;
            border-color: #eee;
        }
        .team-record {
            font-weight: 500;
            color: #1e3c72;
        }
        .form-select, .form-control {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #e0e0e0;
        }
        .form-select:focus, .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            border-color: #1e3c72;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-upcoming {
            background: #e3f2fd;
            color: #1976d2;
        }
        .status-ongoing {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-completed {
            background: #fafafa;
            color: #616161;
        }
        .btn-outline-primary {
            color: #1e3c72;
            border-color: #1e3c72;
            background: transparent;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h4 class="text-white text-center mb-4">Sports League</h4>
                <a href="dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
                <a href="teams.php"><i class='bx bxs-group'></i> Teams</a>
                <a href="tournaments.php" class="active"><i class='bx bxs-trophy'></i> Tournaments</a>
                <a href="schedule.php"><i class='bx bx-calendar'></i> Schedule</a>
                <a href="announcements.php"><i class='bx bx-news'></i> Announcements</a>
                <a href="logout.php" class="mt-5"><i class='bx bx-log-out'></i> Logout</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <?php if(isset($_SESSION["success"])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION["error"])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION["info"])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION["info"]; unset($_SESSION["info"]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tournament Header -->
                <div class="tournament-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="tournaments.php" class="btn btn-outline-primary mb-3">
                                <i class='bx bx-arrow-back me-2'></i>Back to Tournaments
                            </a>
                            <h2><?php echo $tournament_name; ?></h2>
                            <div class="tournament-info mt-2">
                                <span class="me-4"><i class='bx bx-basketball'></i><?php echo $sport_name; ?></span>
                                <span class="me-4"><i class='bx bx-trophy'></i><?php echo ucwords(str_replace('_', ' ', $elimination_type)); ?> Elimination</span>
                                <span class="status-badge status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Tournament Teams -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class='bx bxs-group me-2'></i>Teams</h5>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                                    <i class='bx bx-plus-circle'></i> Add Team
                                </button>
                            </div>
                            <div class="card-body">
                                <?php
                                $rankings_sql = "SELECT t.team_name, tt.rank 
                                               FROM tournament_teams tt 
                                               JOIN teams t ON tt.team_id = t.id 
                                               WHERE tt.tournament_id = ? AND tt.rank > 0 
                                               ORDER BY tt.rank ASC";
                                if($rank_stmt = mysqli_prepare($conn, $rankings_sql)){
                                    mysqli_stmt_bind_param($rank_stmt, "i", $tournament_id);
                                    mysqli_stmt_execute($rank_stmt);
                                    $rank_res = mysqli_stmt_get_result($rank_stmt);
                                    if(mysqli_num_rows($rank_res) > 0){
                                        echo '<div class="alert alert-warning border-0 shadow-sm mb-4">';
                                        echo '<h6 class="fw-bold mb-2"><i class="bx bxs-award me-2"></i>Tournament Rankings</h6>';
                                        while($rank = mysqli_fetch_assoc($rank_res)){
                                            $medal = "";
                                            if($rank['rank'] == 1) $medal = "<i class='bx bxs-medal text-warning'></i> Champion: ";
                                            if($rank['rank'] == 2) $medal = "<i class='bx bxs-medal text-secondary'></i> 2nd Place: ";
                                            if($rank['rank'] == 3) $medal = "<i class='bx bxs-medal text-danger'></i> 3rd Place: ";
                                            echo "<div>$medal <strong>" . htmlspecialchars($rank['team_name']) . "</strong></div>";
                                        }
                                        echo '</div>';
                                    }
                                }
                                ?>
                                <div class="table-responsive">
                                    <table class="table standings-table">
                                        <thead>
                                            <tr>
                                                <th>Team</th>
                                                <th class="text-center">W</th>
                                                <th class="text-center">L</th>
                                                <th class="text-center">Pts</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $standings_sql = "SELECT t.team_name, tt.wins, tt.losses, tt.points 
                                                            FROM tournament_teams tt 
                                                            JOIN teams t ON tt.team_id = t.id 
                                                            WHERE tt.tournament_id = ? 
                                                            ORDER BY tt.points DESC";
                                            if($standings_stmt = mysqli_prepare($conn, $standings_sql)){
                                                mysqli_stmt_bind_param($standings_stmt, "i", $tournament_id);
                                                mysqli_stmt_execute($standings_stmt);
                                                $standings_result = mysqli_stmt_get_result($standings_stmt);
                                                while($row = mysqli_fetch_assoc($standings_result)){
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row['team_name']) . "</td>";
                                                    echo "<td class='text-center team-record'>" . $row['wins'] . "</td>";
                                                    echo "<td class='text-center team-record'>" . $row['losses'] . "</td>";
                                                    echo "<td class='text-center team-record'>" . $row['points'] . "</td>";
                                                    echo "</tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Matches -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class='bx bx-game me-2'></i>Matches</h5>
                                <div class="d-flex gap-2">
                                    <form method="post" onsubmit="return confirm('This will generate matches based on the current teams. Continue?');">
                                        <button type="submit" name="generate_bracket" class="btn btn-info btn-sm text-white">
                                            <i class='bx bx-git-merge'></i> Auto-Generate
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to clear all matches and reset standings?');">
                                        <button type="submit" name="clear_matches" class="btn btn-danger btn-sm">
                                            <i class='bx bx-trash'></i> Clear All
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleMatchModal">
                                        <i class='bx bx-calendar-plus'></i> Schedule Match
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php
                                $matches_sql = "SELECT m.*, t1.team_name as team1_name, t2.team_name as team2_name 
                                              FROM matches m 
                                              JOIN teams t1 ON m.team1_id = t1.id 
                                              JOIN teams t2 ON m.team2_id = t2.id 
                                              WHERE m.tournament_id = ? 
                                              ORDER BY m.match_date DESC";
                                if($matches_stmt = mysqli_prepare($conn, $matches_sql)){
                                    mysqli_stmt_bind_param($matches_stmt, "i", $tournament_id);
                                    mysqli_stmt_execute($matches_stmt);
                                    $matches_result = mysqli_stmt_get_result($matches_stmt);
                                    while($match = mysqli_fetch_assoc($matches_result)){
                                        echo '<div class="match-card">';
                                        echo '<div class="row align-items-center">';
                                        echo '<div class="col-md-4 text-end"><span class="team-name">' . htmlspecialchars($match['team1_name']) . '</span></div>';
                                        echo '<div class="col-md-4 text-center">';
                                        if($match['status'] == 'completed'){
                                            echo '<div class="match-score">' . $match['team1_score'] . ' - ' . $match['team2_score'] . '</div>';
                                        } else {
                                            echo '<form method="post" class="d-flex align-items-center justify-content-center gap-2">';
                                            echo '<input type="hidden" name="match_id" value="' . $match['id'] . '">';
                                            
                                            if($score_type == 'binary') {
                                                echo '<select name="winner" class="form-select form-select-sm" style="width: 200px" required>';
                                                echo '<option value="">Select Winner</option>';
                                                echo '<option value="team1">' . htmlspecialchars($match['team1_name']) . '</option>';
                                                echo '<option value="team2">' . htmlspecialchars($match['team2_name']) . '</option>';
                                                echo '</select>';
                                                echo '<input type="hidden" name="score_type" value="binary">';
                                            } else {
                                                echo '<input type="number" name="team1_score" class="form-control form-control-sm" style="width: 60px" required min="0" placeholder="T1">';
                                                echo '<span class="mx-2">-</span>';
                                                echo '<input type="number" name="team2_score" class="form-control form-control-sm" style="width: 60px" required min="0" placeholder="T2">';
                                                echo '<input type="hidden" name="score_type" value="points">';
                                            }
                                            
                                            echo '<button type="submit" name="update_match" class="btn btn-primary btn-sm"><i class="bx bx-check"></i></button>';
                                            echo '</form>';
                                        }
                                        echo '<div class="match-date mt-2">' . date('M d, Y H:i', strtotime($match['match_date'])) . '</div>';
                                        echo '</div>';
                                        echo '<div class="col-md-4 text-start"><span class="team-name">' . htmlspecialchars($match['team2_name']) . '</span></div>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Team Modal -->
    <div class="modal fade" id="addTeamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Team to Tournament</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Select Team</label>
                            <select name="team_id" class="form-select" required>
                                <?php
                                $teams_sql = "SELECT * FROM teams WHERE id NOT IN (SELECT team_id FROM tournament_teams WHERE tournament_id = ?)";
                                if($teams_stmt = mysqli_prepare($conn, $teams_sql)){
                                    mysqli_stmt_bind_param($teams_stmt, "i", $tournament_id);
                                    mysqli_stmt_execute($teams_stmt);
                                    $teams_result = mysqli_stmt_get_result($teams_stmt);
                                    while($team = mysqli_fetch_assoc($teams_result)){
                                        echo '<option value="' . $team['id'] . '">' . htmlspecialchars($team['team_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add_team" class="btn btn-primary">Add Team</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Match Modal -->
    <div class="modal fade" id="scheduleMatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule New Match</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="schedule_match.php" method="post">
                        <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">Team 1</label>
                            <select name="team1_id" class="form-select" required>
                                <?php
                                $teams_sql = "SELECT t.* FROM teams t 
                                            JOIN tournament_teams tt ON t.id = tt.team_id 
                                            WHERE tt.tournament_id = ?";
                                if($teams_stmt = mysqli_prepare($conn, $teams_sql)){
                                    mysqli_stmt_bind_param($teams_stmt, "i", $tournament_id);
                                    mysqli_stmt_execute($teams_stmt);
                                    $teams_result = mysqli_stmt_get_result($teams_stmt);
                                    while($team = mysqli_fetch_assoc($teams_result)){
                                        echo '<option value="' . $team['id'] . '">' . htmlspecialchars($team['team_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Team 2</label>
                            <select name="team2_id" class="form-select" required>
                                <?php
                                if($teams_stmt = mysqli_prepare($conn, $teams_sql)){
                                    mysqli_stmt_bind_param($teams_stmt, "i", $tournament_id);
                                    mysqli_stmt_execute($teams_stmt);
                                    $teams_result = mysqli_stmt_get_result($teams_stmt);
                                    while($team = mysqli_fetch_assoc($teams_result)){
                                        echo '<option value="' . $team['id'] . '">' . htmlspecialchars($team['team_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Match Date & Time</label>
                            <input type="datetime-local" name="match_date" class="form-control" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Schedule Match</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 