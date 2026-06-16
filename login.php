<?php
/**
 * StayPUP: Unified Authentication Matrix
 * CPET 103 Web Project
 */
session_start();

// If user is already logged in, skip login page and send straight to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if running on Railway cloud environment, otherwise fallback to local XAMPP
$db_host = isset($_ENV['MYSQLHOST'])     ? $_ENV['MYSQLHOST']     : "localhost";
$db_user = isset($_ENV['MYSQLUSER'])     ? $_ENV['MYSQLUSER']     : "root";
$db_pass = isset($_ENV['MYSQLPASSWORD']) ? $_ENV['MYSQLPASSWORD'] : "";
$db_name = isset($_ENV['MYSQLDATABASE']) ? $_ENV['MYSQLDATABASE'] : "staypup_db";
$db_port = isset($_ENV['MYSQLPORT'])     ? $_ENV['MYSQLPORT']     : "3306";

// Initialize connection with the port included
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("<div style='color:red;'>Database Connection Broken: " . $conn->connect_error . "</div>");
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Prepare statement to prevent SQL injection vulnerabilities
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            // FIX 1: Fetch the user data array EXACTLY once!
            $user = $result->fetch_assoc();

            // Note: If you are hashing your passwords during registration with password_hash(), 
            // you should use: if (password_verify($password, $user['password']))
            // If storing as clear plain-text for testing, a standard string comparison works:
            if ($password === $user['password']) {
                
                // Store database column values into session keys
                $_SESSION['user_id'] = $user['id'];
                // FIX 2: Swapped $user['name'] out for the correct SQL column $user['full_name']
                $_SESSION['user_name'] = $user['full_name']; 
                $_SESSION['user_role'] = $user['role']; // e.g., 'Student' or 'Faculty'

                // Close statements before shifting window contexts
                $stmt->close();
                $conn->close();

                // Redirect cleanly to dashboard landing panel
                header("Location: index.php");
                exit();
            } else {
                $error_msg = "Invalid password credential validation matching parameters.";
            }
        } else {
            $error_msg = "Account username profile destination not recognized within system catalogs.";
        }
        $stmt->close();
    } else {
        $error_msg = "Please fill out all operational authentication text fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Access Portal - StayPUP</title>
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
        }
        .login-card {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            width: 70px;
            height: auto;
            margin-bottom: 0.5rem;
        }
        .login-header h1 {
            font-size: 1.8rem;
            color: #800000; /* Matching PUP Maroon */
            margin: 0;
            font-weight: 700;
        }
        .login-header p {
            color: #666;
            font-size: 0.9rem;
            margin: 5px 0 0 0;
        }
        .error-banner {
            background-color: #ffeef0;
            border-left: 4px solid #f85149;
            color: #b31412;
            padding: 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #800000;
        }
        .btn-login {
            background-color: #800000;
            color: white;
            border: none;
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            background-color: #600000;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
              <img src="image/pup_logo.jpg" alt="PUP Logo" style="height: 50px; width: auto; object-fit: contain; vertical-align: middle;">
            <h1>StayPUP</h1>
            <p>Room Reservation Portal Framework</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="error-banner">
                ⚠️ <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Portal Username ID</label>
                <input type="text" name="username" id="username" placeholder="Enter your institutional account login" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="password">Security Protection Key</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">Authorize & Authenticate</button>
        </form>

         <div style="text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: #555;">
            Need an institutional system profile? <a href="register.php" style="color: #800000; text-decoration: none; font-weight: 600;">Create Account Here</a>
         </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>