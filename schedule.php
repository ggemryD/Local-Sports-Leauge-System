<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - Sports League Management System</title>
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
            margin-bottom: 30px;
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
        .btn {
            padding: 10px 20px;
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
        .match-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            background: white;
        }
        .match-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .match-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        .match-header h6 {
            margin: 0;
            color: #1e3c72;
            font-weight: 600;
        }
        .match-body {
            padding: 20px;
        }
        .team-name {
            font-weight: 600;
            color: #2a5298;
            font-size: 1.1rem;
        }
        .match-vs {
            color: #666;
            font-weight: 500;
            margin: 0 15px;
        }
        .match-date {
            color: #666;
            font-size: 0.9rem;
        }
        .match-status {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-scheduled {
            background: #e3f2fd;
            color: #1976d2;
        }
        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .form-select, .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }
        .form-select:focus, .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            border-color: #1e3c72;
        }
        .page-header {
            margin-bottom: 30px;
        }
        .page-header h2 {
            margin: 0;
            color: #1e3c72;
            font-weight: 600;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-white text-center mb-4">Sports League</h4>
        <a href="dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
        <a href="teams.php"><i class='bx bxs-group'></i> Teams</a>
        <a href="tournaments.php"><i class='bx bxs-trophy'></i> Tournaments</a>
        <a href="schedule.php" class="active"><i class='bx bx-calendar'></i> Schedule</a>
        <a href="announcements.php"><i class='bx bx-news'></i> Announcements</a>
        <a href="logout.php" class="mt-5"><i class='bx bx-log-out'></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class='bx bx-calendar me-2'></i>Match Schedule</h2>
        </div>

        <div class="filter-section">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tournament</label>
                    <select name="tournament" class="form-select">
                        <option value="">All Tournaments</option>
                        <?php
                        $tournament_sql = "SELECT id, tournament_name FROM tournaments";
                        $tournament_result = mysqli_query($conn, $tournament_sql);
                        while($tournament = mysqli_fetch_assoc($tournament_result)) {
                            $selected = (isset($_GET['tournament']) && $_GET['tournament'] == $tournament['id']) ? 'selected' : '';
                            echo "<option value='" . $tournament['id'] . "' $selected>" . htmlspecialchars($tournament['tournament_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="scheduled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class='bx bx-filter-alt me-2'></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <div class="matches-container">
            <div class="row">
            <?php
            // Build the query based on filters
            $where_clauses = array();
            $params = array();
            $types = "";

            if(isset($_GET['tournament']) && !empty($_GET['tournament'])) {
                $where_clauses[] = "m.tournament_id = ?";
                $params[] = $_GET['tournament'];
                $types .= "i";
            }

            if(isset($_GET['status']) && !empty($_GET['status'])) {
                $where_clauses[] = "m.status = ?";
                $params[] = $_GET['status'];
                $types .= "s";
            }

            $where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

            $sql = "SELECT m.*, 
                   t1.team_name as team1_name, 
                   t2.team_name as team2_name,
                   tr.tournament_name
                   FROM matches m
                   JOIN teams t1 ON m.team1_id = t1.id
                   JOIN teams t2 ON m.team2_id = t2.id
                   JOIN tournaments tr ON m.tournament_id = tr.id
                   $where_sql
                   ORDER BY m.match_date ASC";

            if($stmt = mysqli_prepare($conn, $sql)) {
                if(!empty($params)) {
                    mysqli_stmt_bind_param($stmt, $types, ...$params);
                }
                
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                while($match = mysqli_fetch_assoc($result)) {
                    $status_class = $match['status'] == 'completed' ? 'status-completed' : 'status-scheduled';
                    ?>
                    <div class="col-md-6">
                        <div class="match-card">
                            <div class="match-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6><?php echo htmlspecialchars($match['tournament_name']); ?></h6>
                                    <span class="match-status <?php echo $status_class; ?>">
                                        <?php echo ucfirst($match['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="match-body">
                                <div class="d-flex justify-content-center align-items-center mb-3">
                                    <span class="team-name"><?php echo htmlspecialchars($match['team1_name']); ?></span>
                                    <span class="match-vs">vs</span>
                                    <span class="team-name"><?php echo htmlspecialchars($match['team2_name']); ?></span>
                                </div>
                                <div class="text-center">
                                    <?php if($match['status'] == 'completed'): ?>
                                        <div class="mb-2">
                                            <span class="h4"><?php echo $match['team1_score']; ?> - <?php echo $match['team2_score']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="match-date">
                                        <i class='bx bx-calendar-event me-1'></i>
                                        <?php echo date('F d, Y', strtotime($match['match_date'])); ?>
                                        <i class='bx bx-time-five ms-2 me-1'></i>
                                        <?php echo date('h:i A', strtotime($match['match_date'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }

                if(mysqli_num_rows($result) == 0) {
                    echo '<div class="col-12">';
                    echo '<div class="alert alert-info">';
                    echo '<i class="bx bx-info-circle me-2"></i>No matches found.';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 