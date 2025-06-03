<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

$team_name = $coach_name = $contact_number = "";
$team_name_err = $coach_name_err = $contact_number_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["update_team"])) {
        // Update existing team
        $id = trim($_POST['id']);
        
        // Validate team name
        if(empty(trim($_POST["team_name"]))){
            $team_name_err = "Please enter team name.";
        } else{
            $team_name = trim($_POST["team_name"]);
        }
        
        // Validate coach name
        if(empty(trim($_POST["coach_name"]))){
            $coach_name_err = "Please enter coach name.";
        } else{
            $coach_name = trim($_POST["coach_name"]);
        }
        
        // Validate contact number
        if(empty(trim($_POST["contact_number"]))){
            $contact_number_err = "Please enter contact number.";
        } else{
            $contact_number = trim($_POST["contact_number"]);
        }
        
        // Check input errors before updating in database
        if(empty($team_name_err) && empty($coach_name_err) && empty($contact_number_err)){
            $sql = "UPDATE teams SET team_name = ?, coach_name = ?, contact_number = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sssi", $param_team_name, $param_coach_name, $param_contact_number, $param_id);
                
                $param_team_name = $team_name;
                $param_coach_name = $coach_name;
                $param_contact_number = $contact_number;
                $param_id = $id;
                
                if(mysqli_stmt_execute($stmt)){
                    header("location: teams.php");
                    exit();
                } else{
                    $error_msg = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    } else {
        // Add new team
        // Validate team name
        if(empty(trim($_POST["team_name"]))){
            $team_name_err = "Please enter team name.";
        } else{
            $team_name = trim($_POST["team_name"]);
        }
        
        // Validate coach name
        if(empty(trim($_POST["coach_name"]))){
            $coach_name_err = "Please enter coach name.";
        } else{
            $coach_name = trim($_POST["coach_name"]);
        }
        
        // Validate contact number
        if(empty(trim($_POST["contact_number"]))){
            $contact_number_err = "Please enter contact number.";
        } else{
            $contact_number = trim($_POST["contact_number"]);
        }
        
        // Check input errors before inserting in database
        if(empty($team_name_err) && empty($coach_name_err) && empty($contact_number_err)){
            $sql = "INSERT INTO teams (team_name, coach_name, contact_number) VALUES (?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sss", $param_team_name, $param_coach_name, $param_contact_number);
                
                $param_team_name = $team_name;
                $param_coach_name = $coach_name;
                $param_contact_number = $contact_number;
                
                if(mysqli_stmt_execute($stmt)){
                    header("location: teams.php");
                    exit();
                } else{
                    $error_msg = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Delete team
if(isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])){
    $sql = "DELETE FROM teams WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = trim($_GET["id"]);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: teams.php");
            exit();
        } else{
            $error_msg = "Oops! Something went wrong. Please try again later.";
        }
    }
}

// Edit team
if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])){
    $id = trim($_GET['id']);
    $sql = "SELECT * FROM teams WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if($team = mysqli_fetch_assoc($result)){
                $team_name = $team['team_name'];
                $coach_name = $team['coach_name'];
                $contact_number = $team['contact_number'];
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
    <title>Teams - Sports League Management System</title>
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
        .team-actions .btn {
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
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-white text-center mb-4">Sports League</h4>
        <a href="dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
        <a href="teams.php" class="active"><i class='bx bxs-group'></i> Teams</a>
        <a href="tournaments.php"><i class='bx bxs-trophy'></i> Tournaments</a>
        <a href="schedule.php"><i class='bx bx-calendar'></i> Schedule</a>
        <a href="announcements.php"><i class='bx bx-news'></i> Announcements</a>
        <a href="logout.php" class="mt-5"><i class='bx bx-log-out'></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class='bx bxs-group me-2'></i>Teams</h2>
            <a href="teams.php?action=add" class="btn btn-primary">
                <i class='bx bx-plus-circle me-2'></i>Add New Team
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

        <?php if(isset($_GET['action']) && ($_GET['action'] == 'add' || $_GET['action'] == 'edit')): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $_GET['action'] == 'add' ? 'Add New Team' : 'Edit Team'; ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <?php if(isset($_GET['id'])): ?>
                            <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Team Name</label>
                            <input type="text" name="team_name" class="form-control <?php echo (!empty($team_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $team_name; ?>">
                            <div class="invalid-feedback"><?php echo $team_name_err; ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Coach Name</label>
                            <input type="text" name="coach_name" class="form-control <?php echo (!empty($coach_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $coach_name; ?>">
                            <div class="invalid-feedback"><?php echo $coach_name_err; ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control <?php echo (!empty($contact_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $contact_number; ?>">
                            <div class="invalid-feedback"><?php echo $contact_number_err; ?></div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="<?php echo isset($_GET['id']) ? 'update_team' : 'add_team'; ?>" class="btn btn-primary">
                                <?php echo $_GET['action'] == 'add' ? 'Add Team' : 'Update Team'; ?>
                            </button>
                            <a href="teams.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class='bx bx-list-ul me-2'></i>Team List</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Team Name</th>
                                <th>Coach</th>
                                <th>Contact</th>
                                <th>Created At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = mysqli_query($conn, "SELECT * FROM teams ORDER BY created_at DESC");
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['team_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['coach_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['contact_number']) . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                echo "<td class='text-end team-actions'>";
                                echo "<a href='teams.php?action=edit&id=" . $row['id'] . "' class='btn btn-sm btn-primary'><i class='bx bx-edit-alt'></i></a> ";
                                echo "<a href='teams.php?action=delete&id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\")'><i class='bx bx-trash'></i></a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 