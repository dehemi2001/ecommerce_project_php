<?php

include('layouts/header.php');

include('server/connection.php');

if(isset($_SESSION['logged_in'])){
  header('location: account.php');
  exit;
}

if(isset($_POST['login_btn'])){

  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT user_id, user_name, user_email, user_password, is_verified FROM users WHERE user_email=? LIMIT 1");

  $stmt->bind_param('s', $email);

  if($stmt->execute()){
    $stmt->bind_result($user_id, $user_name, $user_email, $user_password, $is_verified);
    $stmt->store_result();

    if($stmt->num_rows() == 1){
      $stmt->fetch();

      // Verify password and check if account is verified
      if (password_verify($password, $user_password)) {
        if ($is_verified == 1) {
          $_SESSION['user_id'] = $user_id;
          $_SESSION['user_name'] = $user_name;
          $_SESSION['user_email'] = $user_email;
          $_SESSION['logged_in'] = true;
          header('Location: account.php?login_success=logged in successfully');
        } else {
          header('Location: verify_email.php?error=Please verify your email first.&email=' . urlencode($email));
        }
      } else {
        header('Location: login.php?error=Incorrect email or password');
      }
    }else{
      header('Location: login.php?error=could not verify your account');
    }

  }else{
    //error
    header('Location: login.php?error=something went wrong');
  }

}

?>

<!--Login-->
<section class="my-5 py-5">
    <div class="container text-center mt-3 pt-5">
        <h2 class="form-weight-bold">Login</h2>
        <hr class="mx-auto">
    </div>
    <div class="mx-auto container">
        <form id="login-form" method="POST" action="login.php">
          <p style="color:red" class="text-center"><?php if(isset($_GET['error'])){ echo $_GET['error']; } ?></p>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" id="login-email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" id="login-password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" id="login-btn" name="login_btn" value="Login">
            </div>
            <div class="form-group">
                <a id="register-url" href="forgot_password.php" class="btn">Forgot Password?</a><br>
                <a id="register-url" href="register.php" class="btn">Don't have an account? Register</a><br>
                <a id="register-url" href="admin/login.php" class="btn">Admin Login</a>
            </div>
        </form>
    </div>
</section>

<?php include('layouts/footer.php'); ?>