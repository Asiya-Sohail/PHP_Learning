<?php 
include "config.php";
session_start();
if(isset($_POST['register'])){
   $name=  $_POST['name'];
   $email=$_POST['email'];
   $password=$_POST['password'];
   
   $encrypted_pass= password_hash($password,PASSWORD_DEFAULT);
$contact=$_POST['phone'];
if(!empty($name) && !empty($email) && !empty($password) && !empty($contact)){
    $sql_email = "SELECT email From users Where email = '$email' ";
    $email_query_run = mysqli_query($conn,$sql_email);
    $email_no = mysqli_num_rows($email_query_run);
    if($email_no>0){
        $_SESSION['statusd']= "Email Already Found!";
    }else {
        $sql_qurey ="INSERT into users(name,email,password,phone) VALUES('$name','$email','$encrypted_pass','$contact')";
// $sql_qurey ="INSERT into users(name,email,password,contact) VALUES('$name','$email','$encrypted_pass','$contact')";
if($result =mysqli_query($conn,$sql_qurey)){
    $_SESSION['statusp']='Account has been Created!';
}else{
      $_SESSION['statusd']='Query Failed!';
}
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

      <div  class="page " style="margin: 100px 0;" >
        <div><?php    
        if(isset($_SESSION['statusd']) ){
            echo $_SESSION['statusd'];
        }else if(isset($_SESSION['statusp'])){
            echo $_SESSION['statusp'];
        }
        
        ?></div>
            <h1>Register</h1>
            <div id="register-message" class="message"></div>
            <form id="register-form"  method="post" action="Registration.php">
                <div class="form-group">
                    <label for="register-name">Full Name:</label>
                    <input type="text" id="register-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email:</label>
                    <input type="email" id="register-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password:</label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="register-phone">Phone:</label>
                    <input type="tel" id="register-phone" name="phone" required>
                </div>
                <button type="submit" name="register" class="btn">Register</button>
            </form>
            <div class="nav-links">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </div>
</body>
</html>