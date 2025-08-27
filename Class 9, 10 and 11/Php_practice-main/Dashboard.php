<?php
include 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$fetch_user_data = "SELECT * FROM users WHERE user_id = '$user_id'";
$fetch_user_data_run = mysqli_query($conn,$fetch_user_data);
if(mysqli_num_rows($fetch_user_data_run)>0){
$user_data = mysqli_fetch_assoc($fetch_user_data_run);
$name = $user_data['name'];
$email = $user_data['email'];
$phone = $user_data['phone'];
$address = $user_data['address'];




}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard System</title>
  <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <div class="container">
        <!-- Login Page -->
      

        <!-- Registration Page -->
      

        <!-- Dashboard Page -->
        <div id="dashboard-page" class="page">
            <div class="dashboard-nav">
                <h1>Dashboard</h1>
                <a href="logout.php" style="color: #ecf0f1; cursor: pointer;">Logout</a>
            </div>
            
            <div id="dashboard-message" class="message"></div>
            
            <!-- Profile Display -->
            <div class="profile-display">
                <h3>Your Profile</h3>
                <div class="profile-info" id="profile-info">
                    <div class="info-item">
                        <strong>Name:</strong> <span id="display-name"><?php echo $name ?? ''; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong> <span id="display-email"><?php echo $email ?? ''; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Phone:</strong> <span id="display-phone"><?php echo $phone ?? ''; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Address:</strong> <span id="display-address"><?php echo $address ?? ''; ?></span>
                    </div>
                </div>
                <button class="btn" ><a href="editprofile.php" style="text-decoration: none; color:white;">  Edit Profile </a></button>
                <button class="btn btn-danger" ><a href="deleteprofile.php" style="text-decoration: none; color:white;">Delete Profile</a></button>
            </div>

            <!-- Edit Profile Form -->
        
        </div>
    </div>

  
</body>
</html>