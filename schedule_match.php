<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $tournament_id = $_POST["tournament_id"];
    $team1_id = $_POST["team1_id"];
    $team2_id = $_POST["team2_id"];
    $match_date = $_POST["match_date"];
    
    // Validate teams are different
    if($team1_id == $team2_id){
        $_SESSION["error"] = "Cannot schedule a match between the same team";
        header("location: tournament_details.php?id=" . $tournament_id);
        exit;
    }
    
    // Check if teams are already scheduled for this time
    $check_sql = "SELECT COUNT(*) as count FROM matches 
                  WHERE tournament_id = ? 
                  AND match_date = ? 
                  AND (team1_id = ? OR team1_id = ? OR team2_id = ? OR team2_id = ?)";
                  
    if($check_stmt = mysqli_prepare($conn, $check_sql)){
        mysqli_stmt_bind_param($check_stmt, "isssss", $tournament_id, $match_date, $team1_id, $team2_id, $team1_id, $team2_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $row = mysqli_fetch_assoc($result);
        
        if($row['count'] > 0){
            $_SESSION["error"] = "One or both teams already have a match scheduled at this time";
            header("location: tournament_details.php?id=" . $tournament_id);
            exit;
        }
    }
    
    // Insert new match
    $sql = "INSERT INTO matches (tournament_id, team1_id, team2_id, match_date) VALUES (?, ?, ?, ?)";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "iiis", $tournament_id, $team1_id, $team2_id, $match_date);
        
        if(mysqli_stmt_execute($stmt)){
            // Update tournament status to ongoing if it was upcoming
            $update_sql = "UPDATE tournaments SET status = 'ongoing' WHERE id = ? AND status = 'upcoming'";
            if($update_stmt = mysqli_prepare($conn, $update_sql)){
                mysqli_stmt_bind_param($update_stmt, "i", $tournament_id);
                mysqli_stmt_execute($update_stmt);
            }
            
            header("location: tournament_details.php?id=" . $tournament_id);
            exit();
        } else{
            $_SESSION["error"] = "Something went wrong. Please try again later.";
            header("location: tournament_details.php?id=" . $tournament_id);
            exit;
        }
    }
    
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?> 