<?php
require_once "config/database.php";

$tournament_id = isset($_GET["id"]) ? $_GET["id"] : null;
$tournament_name = "";

if ($tournament_id) {
    // Fetch tournament details
    $sql = "SELECT t.*, s.sport_name, s.score_type 
            FROM tournaments t 
            JOIN sports s ON t.sport_id = s.id 
            WHERE t.id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($tournament = mysqli_fetch_assoc($result)) {
                $tournament_name = $tournament['tournament_name'];
                $elimination_type = $tournament['elimination_type'];
                $status = $tournament['status'];
                $sport_name = $tournament['sport_name'];
                $score_type = $tournament['score_type'];
            } else {
                $tournament_id = null; // Tournament not found
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tournament_id ? htmlspecialchars($tournament_name) . " - " : ""; ?>Public Standings - Sports League</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 1.25rem;
            border-radius: 15px 15px 0 0 !important;
        }
        .card-header h5 {
            margin: 0;
            color: #1e3c72;
            font-weight: 600;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-upcoming { background: #e3f2fd; color: #1976d2; }
        .status-ongoing { background: #e8f5e9; color: #2e7d32; }
        .status-completed { background: #fafafa; color: #616161; }
        .match-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
        }
        .team-name { font-weight: 600; color: #1e3c72; }
        .match-score { font-size: 1.5rem; font-weight: 700; color: #2a5298; }
        .match-date { color: #666; font-size: 0.85rem; }
        .btn-view {
            background: #1e3c72;
            color: white;
            border-radius: 10px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        .btn-view:hover {
            background: #2a5298;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="public_view.php">
                <i class='bx bxs-trophy me-2'></i>Sports League Standings
            </a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class='bx bx-log-in me-1'></i>Admin Login
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (!$tournament_id): ?>
            <!-- Tournament List -->
            <div class="row">
                <div class="col-12 mb-4">
                    <h2 class="text-primary-emphasis fw-bold">Active & Upcoming Tournaments</h2>
                </div>
                <?php
                $sql = "SELECT t.*, s.sport_name FROM tournaments t 
                        JOIN sports s ON t.sport_id = s.id 
                        ORDER BY t.created_at DESC";
                $result = mysqli_query($conn, $sql);
                while ($row = mysqli_fetch_assoc($result)):
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                    <span class="text-muted small"><i class='bx bx-basketball me-1'></i><?php echo $row['sport_name']; ?></span>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3"><?php echo htmlspecialchars($row['tournament_name']); ?></h5>
                                <p class="text-muted small mb-4">
                                    <i class='bx bx-calendar me-1'></i>
                                    <?php echo date('M d', strtotime($row['start_date'])); ?> - <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                </p>
                                <a href="public_view.php?id=<?php echo $row['id']; ?>" class="btn btn-view w-100">
                                    View Brackets & Standings
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <!-- Detailed Tournament View -->
            <div class="row">
                <div class="col-12 mb-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="public_view.php">Tournaments</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($tournament_name); ?></li>
                        </ol>
                    </nav>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="text-primary-emphasis fw-bold mb-1"><?php echo htmlspecialchars($tournament_name); ?></h2>
                            <p class="text-muted mb-0">
                                <i class='bx bx-basketball me-1'></i><?php echo $sport_name; ?> | 
                                <i class='bx bx-trophy me-1'></i><?php echo ucwords(str_replace('_', ' ', $elimination_type)); ?>
                            </p>
                        </div>
                        <span class="status-badge status-<?php echo $status; ?> fs-6">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Rankings -->
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
                    ?>
                        <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #fff 0%, #fff9e6 100%);">
                            <div class="card-body">
                                <h5 class="text-warning-emphasis fw-bold mb-3"><i class='bx bxs-award me-2'></i>Tournament Results</h5>
                                <?php
                                while($rank = mysqli_fetch_assoc($rank_res)){
                                    $medal_class = "";
                                    $label = "";
                                    if($rank['rank'] == 1) { $medal_class = "text-warning"; $label = "Champion"; }
                                    if($rank['rank'] == 2) { $medal_class = "text-secondary"; $label = "2nd Place"; }
                                    if($rank['rank'] == 3) { $medal_class = "text-danger"; $label = "3rd Place"; }
                                ?>
                                    <div class="d-flex align-items-center mb-2 p-2 rounded bg-white shadow-sm">
                                        <i class='bx bxs-medal fs-3 <?php echo $medal_class; ?> me-3'></i>
                                        <div>
                                            <div class="small text-muted"><?php echo $label; ?></div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($rank['team_name']); ?></div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php
                        }
                    }
                    ?>
                    <!-- Standings -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class='bx bx-list-ol me-2'></i>Current Standings</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Team</th>
                                            <th class="text-center">W</th>
                                            <th class="text-center pe-3">L</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $standings_sql = "SELECT t.team_name, tt.wins, tt.losses 
                                                        FROM tournament_teams tt 
                                                        JOIN teams t ON tt.team_id = t.id 
                                                        WHERE tt.tournament_id = ? 
                                                        ORDER BY tt.wins DESC, tt.losses ASC";
                                        if ($stmt = mysqli_prepare($conn, $standings_sql)) {
                                            mysqli_stmt_bind_param($stmt, "i", $tournament_id);
                                            mysqli_stmt_execute($stmt);
                                            $res = mysqli_stmt_get_result($stmt);
                                            while ($row = mysqli_fetch_assoc($res)) {
                                                echo "<tr>";
                                                echo "<td class='ps-3 fw-bold'>" . htmlspecialchars($row['team_name']) . "</td>";
                                                echo "<td class='text-center'>" . $row['wins'] . "</td>";
                                                echo "<td class='text-center pe-3'>" . $row['losses'] . "</td>";
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

                <div class="col-lg-8">
                    <!-- Matches -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class='bx bx-calendar-event me-2'></i>Match Results & Schedule</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $matches_sql = "SELECT m.*, t1.team_name as team1_name, t2.team_name as team2_name 
                                          FROM matches m 
                                          JOIN teams t1 ON m.team1_id = t1.id 
                                          JOIN teams t2 ON m.team2_id = t2.id 
                                          WHERE m.tournament_id = ? 
                                          ORDER BY m.match_date ASC";
                            if ($stmt = mysqli_prepare($conn, $matches_sql)) {
                                mysqli_stmt_bind_param($stmt, "i", $tournament_id);
                                mysqli_stmt_execute($stmt);
                                $res = mysqli_stmt_get_result($stmt);
                                if (mysqli_num_rows($res) == 0) {
                                    echo '<div class="text-center py-5 text-muted">
                                            <i class="bx bx-info-circle fs-1 mb-3"></i>
                                            <p>No matches scheduled yet.</p>
                                          </div>';
                                }
                                while ($match = mysqli_fetch_assoc($res)) {
                                    $is_completed = ($match['status'] == 'completed');
                            ?>
                                    <div class="match-card">
                                        <div class="row align-items-center text-center">
                                            <div class="col-4">
                                                <span class="team-name d-block"><?php echo htmlspecialchars($match['team1_name']); ?></span>
                                            </div>
                                            <div class="col-4">
                                                <?php if ($is_completed): ?>
                                                    <div class="match-score"><?php echo $match['team1_score']; ?> - <?php echo $match['team2_score']; ?></div>
                                                    <span class="badge bg-secondary">Final</span>
                                                <?php else: ?>
                                                    <div class="text-muted fw-bold">VS</div>
                                                    <span class="badge bg-primary">Upcoming</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-4">
                                                <span class="team-name d-block"><?php echo htmlspecialchars($match['team2_name']); ?></span>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <div class="match-date">
                                                    <i class='bx bx-time-five me-1'></i>
                                                    <?php echo date('M d, Y | h:i A', strtotime($match['match_date'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="text-center py-4 mt-5 text-muted border-top">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Sports League Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>