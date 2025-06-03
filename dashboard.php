<?php
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Fetch some quick statistics
$team_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM teams")->fetch_assoc()['count'];
$tournament_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM tournaments")->fetch_assoc()['count'];
$upcoming_matches = mysqli_query($conn, "SELECT COUNT(*) as count FROM matches WHERE status='scheduled'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sports League Management System</title>
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
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 10px;
        }
        .stat-card p {
            color: #666;
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-card p i {
            font-size: 1.4rem;
            margin-right: 8px;
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
        .page-header {
            margin-bottom: 30px;
        }
        .page-header h2 {
            color: #1e3c72;
            font-weight: 600;
            margin: 0;
        }
        .recent-announcement {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .recent-announcement:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .recent-announcement h6 {
            color: #1e3c72;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .recent-announcement small {
            color: #666;
        }
        .quick-actions .btn {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quick-actions .btn i {
            margin-right: 8px;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-white text-center mb-4">Sports League</h4>
        <a href="dashboard.php" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a>
        <a href="teams.php"><i class='bx bxs-group'></i> Teams</a>
        <a href="tournaments.php"><i class='bx bxs-trophy'></i> Tournaments</a>
        <a href="schedule.php"><i class='bx bx-calendar'></i> Schedule</a>
        <a href="announcements.php"><i class='bx bx-news'></i> Announcements</a>
        <a href="logout.php" class="mt-5"><i class='bx bx-log-out'></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class='bx bxs-dashboard me-2'></i>Dashboard</h2>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <h3><?php echo $team_count; ?></h3>
                    <p><i class='bx bxs-group'></i>Total Teams</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h3><?php echo $tournament_count; ?></h3>
                    <p><i class='bx bxs-trophy'></i>Active Tournaments</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h3><?php echo $upcoming_matches; ?></h3>
                    <p><i class='bx bx-calendar'></i>Upcoming Matches</p>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class='bx bx-rocket me-2'></i>Quick Actions</h5>
                    </div>
                    <div class="card-body quick-actions">
                        <a href="teams.php?action=add" class="btn btn-primary mb-2 w-100">
                            <i class='bx bx-plus-circle'></i>Add New Team
                        </a>
                        <a href="tournaments.php?action=add" class="btn btn-success mb-2 w-100">
                            <i class='bx bx-trophy'></i>Create Tournament
                        </a>
                        <a href="announcements.php?action=add" class="btn btn-info text-white w-100">
                            <i class='bx bx-news'></i>Post Announcement
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class='bx bx-news me-2'></i>Recent Announcements</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
                        while($announcement = mysqli_fetch_assoc($recent_announcements)) {
                            echo "<div class='recent-announcement'>";
                            echo "<h6>" . htmlspecialchars($announcement['title']) . "</h6>";
                            echo "<small><i class='bx bx-time-five me-1'></i>" . date('M d, Y', strtotime($announcement['created_at'])) . "</small>";
                            echo "</div>";
                        }
                        if(mysqli_num_rows($recent_announcements) == 0) {
                            echo '<div class="alert alert-info mb-0">';
                            echo '<i class="bx bx-info-circle me-2"></i>No announcements yet';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 