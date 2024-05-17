<?php
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_management";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper function to send emails
function send_email($to, $subject, $message)
{
    // Use mail() function or an external library like PHPMailer for real implementation
    // For simplicity, we're just going to simulate this
    echo "<p style='color: green;'>Email sent to $to with subject: $subject and message: $message</p><br>";
}

// Helper function to generate a random OTP
function generate_otp()
{
    return rand(1000, 9999); // Generate a 4-digit OTP
}

// User Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'register') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>Registration successful! Welcome, $username.</p>";
    } else {
        echo "<p style='color: red;'>Error: Could not register.</p>";
    }
    $stmt->close();
}

// User Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $hashed_password);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            echo "<script> alert('Login successful! Welcome back, $username.')</script>";
            // echo "<p style='color: green;'>Login successful! Welcome back, $username.</p>";
        } else {
            echo "<script> alert('Invalid password.')</script>";
            // echo "<p style='color: red;'>Invalid password.</p>";
        }
    } else {    
        echo "<script> alert('No user found with that email.')</script>";
        
        // echo "<p style='color: red;'>No user found with that email.</p>";
    }
    $stmt->close();
}

// Forgot Password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'forgot_password') {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id);
        $stmt->fetch();
        $reset_token = generate_otp(); // Generate a unique OTP
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
        $stmt->bind_param("si", $reset_token, $id);
        $stmt->execute();
        $stmt->close();

        send_email($email, "Password Reset", "Your OTP is: $reset_token");
        echo "<script> alert('Password reset OTP sent to your email!')</script>";
        // echo "<p style='color: green;'>Password reset OTP sent to your email!</p>";
    } else {
        echo "<script> alert('No user found with that email.')</script>";
        // echo "<p style='color: red;'>No user found with that email.</p>";
    }
}

// Reset Password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'reset_password') {
    $email = $_POST['email'];
    $otp = $_POST['otp'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ?");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE email = ?");
        $stmt->bind_param("ss", $new_password, $email);
        if ($stmt->execute()) {
            echo "<script> alert('Password reset successful! You can now log in with your new password.')</script>";
            // echo "<p style='color: green;'>Password reset successful! You can now log in with your new password.</p>";
        } else {
            echo "<script> alert('Error: Could not reset password.')</script>";
            // echo "<p style='color: red;'>Error: Could not reset password.</p>";
        }
        $stmt->close();
    } else {
        echo "<script> alert('Invalid OTP or email.')</script>";
        // echo "<p style='color: red;'>Invalid OTP or email.</p>";
    }
}

// Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    echo "<script> alert('Logged out successfully!')</script>";
    header("Refresh:0; url=index.php");
}

// Access Control
function is_authenticated()
{
    return isset($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TASK 2</title>
    <link rel="stylesheet" href="includes/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <h1 style="text-align: center;">User Authentication System</h1>
    <div class="row">
        <button class="logInbtn">Login</button>
        <button class="forgotBtn">Forgot</button>
        <button class="resetBtn">Reset</button>
        <button class="regBtn">Register</button>
    </div>

    <?php if (!is_authenticated()) : ?>
        <div class="section register-section">
            <h2>Register</h2>
            <form method="post">
                <input type="hidden" name="action" value="register">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Register</button>
            </form>
        </div>
        <div class="section login-section">
            <h2>Login</h2>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>

        <div class="section forgot-section">
            <h2>Forgot Password</h2>
            <form method="post">
                <input type="hidden" name="action" value="forgot_password">
                <input type="email" name="email" placeholder="Email" required>
                <button type="submit">Send OTP</button>
            </form>
        </div>

        <div class="section reset-section">
            <h2>Reset Password</h2>
            <form method="post">
                <input type="hidden" name="action" value="reset_password">
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="otp" placeholder="OTP" required>
                <input type="password" name="new_password" placeholder="New Password" required>
                <button type="submit">Reset Password</button>
            </form>
        </div>
    <?php else : ?>
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>You have access to this section because you are logged in.</p>
        <a href="?action=logout">Logout</a>
    <?php endif; ?>
    <script>
        $(document).ready(function() {
            $('.login-section').hide();
            $('.forgot-section').hide();
            $('.reset-section').hide();
            $('.regBtn').hide();
            $('.logInbtn').click(function() {
                $('.logInBtn').hide();
                $('.forgotBtn').show();
                $('.resetBtn').show();
                $('.regBtn').show();
                $('.login-section').show();
                $('.register-section').hide();
                $('.forgot-section').hide();
                $('.reset-section').hide();
            });
            $('.forgotBtn').click(function() {
                $('.logInBtn').show();
                $('.forgotBtn').hide();
                $('.resetBtn').show();
                $('.regBtn').show();
                $('.register-section').hide();
                $('.forgot-section').show();
                $('.login-section').hide();
                $('.reset-section').hide();
            });
            $('.resetBtn').click(function() {
                $('.logInBtn').show();
                $('.forgotBtn').show();
                $('.resetBtn').hide();
                $('.regBtn').show();
                $('.reset-section').show();
                $('.login-section').hide();
                $('.register-section').hide();
                $('.forgot-section').hide();
            });
            $('.regBtn').click(function() {
                $('.regBtn').hide();
                $('.logInBtn').show();
                $('.forgotBtn').show();
                $('.resetBtn').show();
                $('.register-section').show();
                $('.forgot-section').hide();
                $('.login-section').hide();
                $('.reset-section').hide();
            });
        });
    </script>
</body>

</html>

<?php
// Close the connection
$conn->close();
?>