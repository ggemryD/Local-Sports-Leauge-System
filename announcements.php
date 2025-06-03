<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

$title = $content = "";
$title_err = $content_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter announcement title.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate content
    if(empty(trim($_POST["content"]))){
        $content_err = "Please enter announcement content.";
    } else{
        $content = trim($_POST["content"]);
    }
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($content_err)){
        $sql = "INSERT INTO announcements (title, content) VALUES (?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ss", $param_title, $param_content);
            
            $param_title = $title;
            $param_content = $content;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: announcements.php");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Delete announcement
if(isset($_GET['delete']) && !empty($_GET['delete'])){
    $sql = "DELETE FROM announcements WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = trim($_GET["delete"]);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: announcements.php");
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
    <title>Announcements - Sports League Management System</title>
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
        .announcement-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: none;
        }
        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .announcement-title {
            color: #1e3c72;
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 10px;
        }
        .announcement-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        .announcement-content {
            color: #444;
            line-height: 1.6;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            border-color: #1e3c72;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-white text-center mb-4">Sports League</h4>
        <a href="dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
        <a href="teams.php"><i class='bx bxs-group'></i> Teams</a>
        <a href="tournaments.php"><i class='bx bxs-trophy'></i> Tournaments</a>
        <a href="schedule.php"><i class='bx bx-calendar'></i> Schedule</a>
        <a href="announcements.php" class="active"><i class='bx bx-news'></i> Announcements</a>
        <a href="logout.php" class="mt-5"><i class='bx bx-log-out'></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class='bx bx-news me-2'></i>Announcements</h2>
            <a href="announcements.php?action=add" class="btn btn-primary">
                <i class='bx bx-plus-circle me-2'></i>Post Announcement
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

        <div class="announcements-container">
            <?php
            $result = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
            if(mysqli_num_rows($result) > 0) {
                while($announcement = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="announcement-card">
                        <h3 class="announcement-title">
                            <?php echo htmlspecialchars($announcement['title']); ?>
                        </h3>
                        <div class="announcement-meta">
                            <i class='bx bx-calendar me-1'></i>
                            <?php echo date('F d, Y', strtotime($announcement['created_at'])); ?>
                            <i class='bx bx-time-five ms-3 me-1'></i>
                            <?php echo date('h:i A', strtotime($announcement['created_at'])); ?>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <div class="mt-3 text-end">
                                <a href="announcements.php?action=edit&id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class='bx bx-edit-alt'></i> Edit
                                </a>
                                <a href="announcements.php?action=delete&id=<?php echo $announcement['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this announcement?')">
                                    <i class='bx bx-trash'></i> Delete
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="alert alert-info">';
                echo '<i class="bx bx-info-circle me-2"></i>No announcements found.';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Post Announcement Modal -->
    <div class="modal fade" id="postAnnouncementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Post New Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="announcements.php">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>" required>
                            <div class="invalid-feedback"><?php echo $title_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea name="content" class="form-control <?php echo (!empty($content_err)) ? 'is-invalid' : ''; ?>" rows="4" required><?php echo $content; ?></textarea>
                            <div class="invalid-feedback"><?php echo $content_err; ?></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Post Announcement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show modal when Add Announcement button is clicked
        document.addEventListener('DOMContentLoaded', function() {
            if(window.location.href.includes('action=add')) {
                var modal = new bootstrap.Modal(document.getElementById('postAnnouncementModal'));
                modal.show();
            }
        });
    </script>
</body>
</html> 