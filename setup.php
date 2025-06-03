<?php
require_once "config/database.php";

// Default admin credentials
$username = "admin";
$password = "admin123"; // This will be hashed
$email = "admin@example.com";

// Check if admin already exists
$check_sql = "SELECT id FROM users WHERE username = ?";
if($check_stmt = mysqli_prepare($conn, $check_sql)){
    mysqli_stmt_bind_param($check_stmt, "s", $username);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if(mysqli_stmt_num_rows($check_stmt) == 0){
        // Create admin user
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $email);
            
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if(mysqli_stmt_execute($stmt)){
                echo "Admin user created successfully!<br>";
                echo "Username: " . $username . "<br>";
                echo "Password: " . $password . "<br>";
                echo "<a href='index.php'>Go to login page</a>";
            } else{
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    } else {
        echo "Admin user already exists!";
    }
}

mysqli_close($conn);
?> 