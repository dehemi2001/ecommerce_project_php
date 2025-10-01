<?php

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

include('layouts/header.php');

include('server/connection.php');

  //if user has already registered, then take user to account page
if(isset($_SESSION['logged_in'])){
  header('location: account.php');
  exit;
}

if (isset($_POST['register'])) {

  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $confirmPassword = $_POST['confirmPassword'];

  //if passwords dont match
  if ($password !== $confirmPassword) {
    header('location: register.php?error=passwords dont match');


    //if password is less than 6 char
  } else if (strlen($password) < 6) {
    header('location: register.php?error=password must be at least 6 characters');


    //if there is no error
  } else {
    //check whether there is a user with this email or not
    $stmt1 = $conn->prepare("SELECT count(*) FROM users WHERE user_email=?");
    $stmt1->bind_param('s', $email);
    $stmt1->execute();
    $stmt1->bind_result($num_rows);
    $stmt1->store_result();
    $stmt1->fetch();

    //if there is a user already registered with this email
    if ($num_rows != 0) {
      header('location: register.php?error=user with this email already exists');

      //if no user registered with this email before
    } else{

      //create a new user
      $stmt = $conn->prepare("INSERT INTO users (user_name, user_email, user_password)
                VALUES (?,?,?)");
    
      // Using password_hash for secure password storage
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $verification_code = substr(md5(rand()), 0, 8); // Generate a random verification code

      $stmt = $conn->prepare("INSERT INTO users (user_name, user_email, user_password, verification_code) VALUES (?, ?, ?, ?)");
      $stmt->bind_param('ssss', $name, $email, $hashed_password, $verification_code);

      //if account was created successfully
      if ($stmt->execute()) {
        // Send verification email
        $stmt_admin = $conn->prepare("SELECT * FROM admins LIMIT 1");
        $stmt_admin->execute();
        $admin = $stmt_admin->get_result()->fetch_assoc();
        $admin_email = $admin['admin_email'];
        $app_password = $admin['app_password'];

        require 'vendor/autoload.php';
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 465;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->SMTPAuth = true;
        $mail->Username = $admin_email;
        $mail->Password = $app_password;
        $mail->setFrom($admin_email, 'Solid Computers');
        $mail->addAddress($email);
        $mail->Subject = 'Email Verification';
        $mail->msgHTML("
          <html><body>
          <h2>Verify Your Email Address</h2>
          <p>Thank you for registering. Please use the code below to verify your email address.</p>
          <p><strong>Your verification code is:</strong> $verification_code</p>
          <p>Best regards,<br>Solid Computers</p>
          </body></html>
        ");

        if ($mail->send()) {
          header('location: verify_email.php?message=Registration successful! Please check your email for the verification code.&email=' . urlencode($email));
        } else {
          // This part is tricky. The user is created, but email failed.
          // For simplicity, we'll show a generic error. In a real-world app, you might want to log this.
          header('location: register.php?error=Could not send verification email. Please contact support.');
        }
      } else {
        header('location: register.php?error=Could not create an account at the moment');
      }
    }
  }
}
?>

  <!--Register-->
  <section class="my-5 py-5">
    <div class="container text-center mt-3 pt-5">
      <h2 class="form-weight-bold">Register</h2>
      <hr class="mx-auto">
    </div>
    <div class="mx-auto container">
      <form id="register-form" method="POST" action="register.php">
        <p style="color: red;" class="text-center"><?php if (isset($_GET['error'])) {
                                  echo $_GET['error'];
                                } ?></p>
        <p style="color: green;" class="text-center"><?php if (isset($_GET['message'])) {
                                  echo $_GET['message'];
                                } ?></p>
        <div class="form-group">
          <label>Name</label>
          <input type="text" class="form-control" id="register-name" name="name" placeholder="Name" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" class="form-control" id="register-email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" class="form-control" id="register-password" name="password" placeholder="Password" required>
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" class="form-control" id="register-confirm-password" name="confirmPassword" placeholder="Confirm Password" required>
        </div>
        <div class="form-group">
          <input type="submit" class="btn" id="register-btn" name="register" value="Register">
        </div>
        <div class="form-group">
          <a id="login-url" href="login.php" class="btn">Do you have an account? Login</a>
        </div>
      </form>
    </div>
  </section>

  <?php include('layouts/footer.php'); ?>