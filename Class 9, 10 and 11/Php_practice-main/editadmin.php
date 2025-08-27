<?php
include 'config.php';
session_start();
if(!isset($_GET['user_id'])){
    header('Location: login.php');
    exit();
}
$user_id = $_GET['user_id'];
$fetch_user_data = "SELECT * FROM users WHERE user_id = '$user_id'";
$fetch_user_data_run = mysqli_query($conn,$fetch_user_data);
if(mysqli_num_rows($fetch_user_data_run)>0){
$user_data = mysqli_fetch_assoc($fetch_user_data_run);
$name = $user_data['name'];
$email = $user_data['email'];
$phone = $user_data['contact'];
$address = $user_data['address'];




}
if(isset($_POST['update_profile'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $update_query = "UPDATE users SET name = '$name', email = '$email', contact = '$phone', address = '$address' WHERE user_id = '$user_id'";
    $update_query_run = mysqli_query($conn,$update_query);
    if($update_query_run){
        $_SESSION['statusp'] = "Profile Updated Successfully";
        header('Location: admin.php');
        exit();
    }else{
        $_SESSION['statusd'] = "Profile Update Failed";
        header('Location: editprofile.php');
        exit();
    }
    
}

?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="editprofile">
                
             
        <div id="edit-form" >
                <h3>Edit Profile</h3>
                <?php if(isset($_SESSION['statusp'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['statusp']; ?>
                        <?php unset($_SESSION['statusp']); ?>
                    </div>
                <?php endif; ?>
                <?php if(isset($_SESSION['statusd'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['statusd']; ?>
                        <?php unset($_SESSION['statusd']); ?>
                    </div>
                <?php endif; ?>

                <form id="profile-form" method="post" action="editadmin.php?user_id=<?php echo $user_id; ?>">
                    <div class="form-group">
                        <label for="edit-name">Full Name:</label>
                        <input type="text" id="edit-name" name="name" value="<?php echo $name ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-email">Email:</label>
                        <input type="email" id="edit-email" name="email" value="<?php echo $email ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-phone">Phone:</label>
                        <input type="text" id="edit-phone" name="phone" value="<?php echo $phone ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-address">Address:</label>
                        
                        <textarea name="address" placeholder="Enter your address" ><?php echo $address ?? ''; ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" name="update_profile">Update Profile</button>
                    <button type="button" class="btn" onclick="history.back()">Back</button>
                </form>
            </div>

            </div>
            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
</body>
</html>