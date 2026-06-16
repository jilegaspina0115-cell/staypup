<?php
session_start();

// Import the smart database config matrix instantly 
require_once 'db_connect.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Your queries and prepared statements continue below safely...
$db_host = "localhost";
$db_user = "root";       
$db_pass = "";           
$db_name = "staypup_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<div style='color:red;'>Database Connection Broken: " . $conn->connect_error . "</div>");
}

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $role      = $_POST['role'];
    $password  = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Operational Validation Parameters
    if (!empty($username) && !empty($full_name) && !empty($role) && !empty($password)) {
        
        if ($password !== $confirm_password) {
            $error_msg = "Security Protection Keys do not match.";
        } else {
            // Check if the institutional username profile identifier is already taken
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $error_msg = "Username ID is already registered within system catalogs.";
            } else {
                // Keep plain text matching your current login file configuration.
                // For live deployments later, use password_hash($password, PASSWORD_BCRYPT)
                $insert_stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("ssss", $username, $password, $full_name, $role);

                if ($insert_stmt->execute()) {
                    $success_msg = "Account created successfully! Rerouting to validation gateway...";
                    // Clean redirection header to login page after 2 seconds
                    header("refresh:2;url=login.php");
                } else {
                    $error_msg = "Execution interruption caught during database catalog insertion.";
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    } else {
        $error_msg = "Please populate all necessary validation credential form blocks.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account Portal - StayPUP</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f4f6f9;
            margin: 0;
            font-family: 'Inter', sans-serif;
            padding: 20px 0;
        }
        .register-card {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 460px;
            box-sizing: border-box;
        }
        .register-header {
            text-align: center;
            margin-bottom: 1.8rem;
        }
        .register-header img {
            width: 65px;
            height: auto;
            margin-bottom: 0.5rem;
        }
        .register-header h1 {
            font-size: 1.7rem;
            color: #800000; /* Matching PUP Maroon */
            margin: 0;
            font-weight: 700;
        }
        .register-header p {
            color: #666;
            font-size: 0.85rem;
            margin: 5px 0 0 0;
        }
        .banner {
            padding: 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            border-left: 4px solid;
        }
        .error-banner {
            background-color: #ffeef0;
            border-left-color: #f85149;
            color: #b31412;
        }
        .success-banner {
            background-color: #dcfee5;
            border-left-color: #2da44e;
            color: #1a7f37;
        }
        .form-group {
            margin-bottom: 1.1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.9rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #800000;
        }
        .btn-register {
            background-color: #800000;
            color: white;
            border: none;
            width: 100%;
            padding: 0.8rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }
        .btn-register:hover {
            background-color: #600000;
        }
        .login-link {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.85rem;
            color: #555;
        }
        .login-link a {
            color: #800000;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="register-header">
             <img src="image/pup_logo.jpg" alt="PUP Logo" style="height: 50px; width: auto; object-fit: contain; vertical-align: middle;">
            <h1>StayPUP</h1>
            <p>Institutional Account Registration Matrix</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="banner error-banner">
                ⚠️ <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="banner success-banner">
                ✓ <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Institutional Username ID</label>
                <input type="text" name="username" id="username" placeholder="e.g., joy_legaspina" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="full_name">Complete Legal Name</label>
                <input type="text" name="full_name" id="full_name" placeholder="e.g., Joy O. Legaspina" required>
            </div>

            <div class="form-group">
                <label for="role">System Account Role Badge</label>
                <select name="role" id="role" required>
                    <option value="" disabled selected>-- Select Your Designation Class --</option>
                    <option value="Student">Student Cohort</option>
                    <option value="Faculty">Faculty Staff Member</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Create Security Protection Key</label>
                <input type="password" name="password" id="password" placeholder="Minimum secure characters" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Verify Security Protection Key</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Retype password key structural bounds" required>
            </div>

            <button type="submit" class="btn-register">Compile & Register Profile</button>
        </form>

        <div class="login-link">
            Already possess an active credential ledger? <a href="login.php">Access Gate Here</a>
        </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>