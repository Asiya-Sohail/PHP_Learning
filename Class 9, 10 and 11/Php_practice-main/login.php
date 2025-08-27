<?php 
include "config.php";
session_start();

if(isset($_POST['login'])){
  
   $email=$_POST['email'];
   $password=$_POST['password'];
   
   
if(!empty($email) && !empty($password) ){
    $sql_email = "SELECT * From users Where email = '$email' ";
    $email_query_run = mysqli_query($conn,$sql_email);
    $email_no = mysqli_num_rows($email_query_run);

    if($email_no>0){
        $row=mysqli_fetch_assoc( $email_query_run);
       if(password_verify($password,$row['password'])){
if($row['status'] ==1){
  $_SESSION['user_id'] = $row['user_id'];
    $_SESSION['name']=$row['name'];
    $_SESSION['role']=$row['role'];
    if($row['role'] == 0){
header('Location:Dashboard.php');
exit;
    }else if($row['role'] == 1){
        header('Location:admin.php');
    }

}else{
 $_SESSION['statusd']='Account Not Approve!';
}

 
       }else{
         $_SESSION['statusd']='Password Incorrect!';
}
       





        
    }else {
$_SESSION['statusd']= "Incorrect Email!";
    }



}else{
   $_SESSION['statusd']= "All Fields Are Required!";  
}



}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; justify-content:center;">
      <div id="login-page" class="page active">
          <div><?php    
        if(isset($_SESSION['statusd']) ){
            echo $_SESSION['statusd'];
        }else if(isset($_SESSION['statusp'])){
            echo $_SESSION['statusp'];
        }
        
        ?></div>
            <h1>Login</h1>
            <div id="login-message" class="message"></div>
            <form id="login-form" method="POST" action="login.php">
                <div class="form-group">
                    <label for="login-email">Email:</label>
                    <input type="email" id="login-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password:</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn">Login</button>
            </form>
            <div class="nav-links">
                <a href="Registration.php">Don't have an account? Register</a>
            </div>
        </div>
</body>
</html>