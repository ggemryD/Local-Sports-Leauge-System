<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

$tournament_name = $start_date = $end_date = $elimination_type = $sport_id = "";
$tournament_name_err = $start_date_err = $end_date_err = $elimination_type_err = $sport_id_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate tournament name
    if(empty(trim($_POST["tournament_name"]))){
        $tournament_name_err = "Please enter tournament name.";
    } else{
        $tournament_name = trim($_POST["tournament_name"]);
    }
    
    // Validate sport
    if(empty(trim($_POST["sport_id"]))){
        $sport_id_err = "Please select a sport.";
    } else{
        $sport_id = trim($_POST["sport_id"]);
    }
    
    // Validate start date
    if(empty(trim($_POST["start_date"]))){
        $start_date_err = "Please enter start date.";
    } else{
        $start_date = trim($_POST["start_date"]);
    }
    
    // Validate end date
    if(empty(trim($_POST["end_date"]))){
        $end_date_err = "Please enter end date.";
    } else{
        $end_date = trim($_POST["end_date"]);
        if($end_date < $start_date){
            $end_date_err = "End date must be after start date.";
        }
    }
    
    // Validate elimination type
    if(empty(trim($_POST["elimination_type"]))){
        $elimination_type_err = "Please select elimination type.";
    } else{
        $elimination_type = trim($_POST["elimination_type"]);
    }
    
    // Check input errors before inserting in database
    if(empty($tournament_name_err) && empty($start_date_err) && empty($end_date_err) && empty($elimination_type_err) && empty($sport_id_err)){
        $sql = "INSERT INTO tournaments (tournament_name, sport_id, start_date, end_date, elimination_type) VALUES (?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sisss", $param_tournament_name, $param_sport_id, $param_start_date, $param_end_date, $param_elimination_type);
            
            $param_tournament_name = $tournament_name;
            $param_sport_id = $sport_id;
            $param_start_date = $start_date;
            $param_end_date = $end_date;
            $param_elimination_type = $elimination_type;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: tournaments.php");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Delete tournament
if(isset($_GET['delete']) && !empty($_GET['delete'])){
    $sql = "DELETE FROM tournaments WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = trim($_GET["delete"]);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: tournaments.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournaments - Sports League Management System</title>
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
        .table {
            margin: 0;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #1e3c72;
            background: #f8f9fa;
        }
        .table td {
            vertical-align: middle;
        }
        .tournament-actions .btn {
            padding: 6px 12px;
            margin: 0 3px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            border-color: #1e3c72;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-header h2 {
            margin: 0;
            color: #1e3c72;
            font-weight: 600;
        }
        .tournament-card {
            height: 100%;
        }
        .tournament-card .card-body {
            padding: 20px;
        }
        .tournament-status {
            padding: 5px 10px;
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
        .tournament-date {
            font-size: 0.9rem;
            color: #666;
        }
        .tournament-type {
            font-size: 0.9rem;
            color: #1e3c72;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-white text-center mb-4">Sports League</h4>
        <a href="dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
        <a href="teams.php"><i class='bx bxs-group'></i> Teams</a>
        <a href="tournaments.php" class="active"><i class='bx bxs-trophy'></i> Tournaments</a>
        <a href="schedule.php"><i class='bx bx-calendar'></i> Schedule</a>
        <a href="announcements.php"><i class='bx bx-news'></i> Announcements</a>
        <a href="logout.php" class="mt-5"><i class='bx bx-log-out'></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class='bx bxs-trophy me-2'></i>Tournaments</h2>
            <a href="tournaments.php?action=add" class="btn btn-primary">
                <i class='bx bx-plus-circle me-2'></i>Create Tournament
            </a>
        </div>

        <?php if(isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class='bx bx-check-circle me-2'></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class='bx bx-error-circle me-2'></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php
            $result = mysqli_query($conn, "SELECT t.*, s.sport_name FROM tournaments t JOIN sports s ON t.sport_id = s.id ORDER BY created_at DESC");
            while($row = mysqli_fetch_assoc($result)) {
                $status_class = '';
                switch($row['status']) {
                    case 'upcoming':
                        $status_class = 'status-upcoming';
                        break;
                    case 'ongoing':
                        $status_class = 'status-ongoing';
                        break;
                    case 'completed':
                        $status_class = 'status-completed';
                        break;
                }
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card tournament-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['tournament_name']); ?></h5>
                                <span class="tournament-status <?php echo $status_class; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </div>
                            <p class="tournament-type mb-2">
                                <i class='bx bx-basketball me-2'></i><?php echo htmlspecialchars($row['sport_name']); ?>
                            </p>
                            <p class="tournament-date mb-2">
                                <i class='bx bx-calendar me-2'></i>
                                <?php 
                                echo date('M d, Y', strtotime($row['start_date']));
                                if($row['end_date']) {
                                    echo ' - ' . date('M d, Y', strtotime($row['end_date']));
                                }
                                ?>
                            </p>
                            <p class="mb-3">
                                <i class='bx bx-trophy me-2'></i>
                                <?php echo ucwords(str_replace('_', ' ', $row['elimination_type'])); ?> Elimination
                            </p>
                            <div class="d-flex justify-content-end tournament-actions">
                                <a href="tournament_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                    <i class='bx bx-show'></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Create Tournament Modal -->
    <div class="modal fade" id="createTournamentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Tournament</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Tournament Name</label>
                            <input type="text" name="tournament_name" class="form-control <?php echo (!empty($tournament_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $tournament_name; ?>" required>
                            <div class="invalid-feedback"><?php echo $tournament_name_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sport</label>
                            <select name="sport_id" class="form-control <?php echo (!empty($sport_id_err)) ? 'is-invalid' : ''; ?>" required>
                                <option value="">Select Sport</option>
                                <?php
                                $sports_sql = "SELECT * FROM sports";
                                $sports_result = mysqli_query($conn, $sports_sql);
                                while($sport = mysqli_fetch_assoc($sports_result)) {
                                    $selected = ($sport_id == $sport['id']) ? 'selected' : '';
                                    echo '<option value="' . $sport['id'] . '" ' . $selected . '>' . htmlspecialchars($sport['sport_name']) . '</option>';
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback"><?php echo $sport_id_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control <?php echo (!empty($start_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $start_date; ?>" required>
                            <div class="invalid-feedback"><?php echo $start_date_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control <?php echo (!empty($end_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $end_date; ?>" required>
                            <div class="invalid-feedback"><?php echo $end_date_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Elimination Type</label>
                            <select name="elimination_type" class="form-control <?php echo (!empty($elimination_type_err)) ? 'is-invalid' : ''; ?>" required>
                                <option value="">Select Type</option>
                                <option value="single" <?php echo ($elimination_type == "single") ? 'selected' : ''; ?>>Single Elimination</option>
                                <option value="double" <?php echo ($elimination_type == "double") ? 'selected' : ''; ?>>Double Elimination</option>
                                <option value="round_robin" <?php echo ($elimination_type == "round_robin") ? 'selected' : ''; ?>>Round Robin</option>
                            </select>
                            <div class="invalid-feedback"><?php echo $elimination_type_err; ?></div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Create Tournament</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show create tournament modal when action=add is in URL
        if (window.location.href.includes('action=add')) {
            var createTournamentModal = new bootstrap.Modal(document.getElementById('createTournamentModal'));
            createTournamentModal.show();
        }
    </script>
</body>
</html> 