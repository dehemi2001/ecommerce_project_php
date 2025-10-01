<?php
include('layouts/header.php');
include('server/connection.php');

// if user is logged in, redirect to account page
if (isset($_SESSION['logged_in'])) {
    header('location: account.php');
    exit;
}

if (isset($_POST['verify_btn'])) {
    $email = $_POST['email'];
    $verification_code = $_POST['verification_code'];

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT verification_code, is_verified FROM users WHERE user_email = ? LIMIT 1");
    $stmt->bind_param('s', $email);

    if ($stmt->execute()) {
        $stmt->bind_result($db_verification_code, $is_verified);
        $stmt->store_result();

        if ($stmt->num_rows() == 1) {
            $stmt->fetch();

            if ($is_verified == 1) {
                header('location: login.php?error=Account is already verified. Please login.');
            } else {
                // Check if codes match
                if ($db_verification_code == $verification_code) {
                    // Update user to verified
                    $stmt_update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE user_email = ?");
                    $stmt_update->bind_param('s', $email);
                    if ($stmt_update->execute()) {
                        header('location: login.php?message=Verification successful. You can now login.');
                    } else {
                        header('location: verify_email.php?error=Could not verify your account. Please try again.');
                    }
                } else {
                    header('location: verify_email.php?error=Incorrect verification code.');
                }
            }
        } else {
            header('location: verify_email.php?error=This email is not registered.');
        }
    } else {
        header('location: verify_email.php?error=Something went wrong. Please try again.');
    }
}
?>

<section class="my-5 py-5">
    <div class="container text-center mt-3 pt-5">
        <h2 class="form-weight-bold">Verify Your Email</h2>
        <hr class="mx-auto">
    </div>
    <div class="mx-auto container">
        <form id="verify-form" method="POST" action="verify_email.php">
            <p style="color:red" class="text-center"><?php if (isset($_GET['error'])) { echo $_GET['error']; } ?></p>
            <p style="color:green" class="text-center"><?php if (isset($_GET['message'])) { echo $_GET['message']; } ?></p>
            <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" placeholder="Email" required value="<?php if(isset($_GET['email'])) echo htmlspecialchars($_GET['email']); ?>"></div><br>
            <div class="form-group"><label>Verification Code</label><input type="text" class="form-control" name="verification_code" placeholder="Verification Code" required></div><br>
            <div class="form-group"><input type="submit" class="btn" id="verify-btn" name="verify_btn" value="Verify"></div>
        </form>
    </div>
</section>

<?php include('layouts/footer.php'); ?>