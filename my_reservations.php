<?php
session_start(); // 1. Always start the session first!

// 2. Check if running on Railway cloud environment, otherwise fallback to local XAMPP
$db_host = isset($_ENV['MYSQLHOST'])     ? $_ENV['MYSQLHOST']     : "localhost";
$db_user = isset($_ENV['MYSQLUSER'])     ? $_ENV['MYSQLUSER']     : "root";
$db_pass = isset($_ENV['MYSQLPASSWORD']) ? $_ENV['MYSQLPASSWORD'] : "";
$db_name = isset($_ENV['MYSQLDATABASE']) ? $_ENV['MYSQLDATABASE'] : "staypup_db";
$db_port = isset($_ENV['MYSQLPORT'])     ? $_ENV['MYSQLPORT']     : "3306";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("<div style='color:red;'>Database Connection Broken: " . $conn->connect_error . "</div>");
}

// 3. Make sure a user is actually logged in before showing their records
if (!isset($_SESSION['user_id'])) {
    // If they aren't logged in, redirect them back to the login page
    header("Location: login.php");
    exit();
}

// Get clean session details for the logged-in user
$user_id   = $_SESSION['user_id'];
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Guest';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest User';

// Get the first initial dynamically for the avatar circle profile icon
$user_avatar_initial = !empty($user_name) ? strtoupper($user_name[0]) : 'U';

// Fetch only reservations issued by the current logged-in user's name
$my_ledger_query = "SELECT r.id, rm.room_number, rm.room_type, r.booking_date, r.time_slot, r.purpose, r.status 
                    FROM reservations r 
                    INNER JOIN rooms rm ON r.room_id = rm.id 
                    WHERE r.reserved_by = ? 
                    ORDER BY r.id DESC";

$stmt = $conn->prepare($my_ledger_query);
$stmt->bind_param("s", $user_name); // "s" means string, matching the text user_name
$stmt->execute();
$my_ledger_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - StayPUP</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="navbar">
        <div class="nav-container">
            <div class="brand">
                <span class="logo-icon">
                    <img src="image/pup_logo.jpg" alt="PUP Logo" style="height: 50px; width: auto; object-fit: contain; vertical-align: middle;">
                </span>
                <div class="brand-text">
                    <h1>StayPUP</h1>
                    <small>PUP Biñan Room Reservation System</small>
                </div>
            </div>
            <nav class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="rooms.php">Rooms & Facilities</a>
                <a href="my_reservations.php" class="active">My Reservations</a>
                <a href="logout.php" style="color: #ffcccc; margin-left: 15px;">Logout</a>
            </nav>
            <div class="user-profile">
                <div class="user-avatar"><?php echo htmlspecialchars($user_avatar_initial); ?></div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($user_role); ?> Account</span>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-wrapper">
        <section class="main-content-panel" style="grid-column: span 12;">
            <div class="panel-header">
                <h2>Your Personal Reservation Ledger</h2>
                <p>Tracking history and authorization states for allocations under your profile.</p>
            </div>

            <div class="table-responsive">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Allocation ID</th>
                            <th>Target Facility</th>
                            <th>Classification</th>
                            <th>Execution Date</th>
                            <th>Time Block Window</th>
                            <th>Stated Activity Purpose</th>
                            <th>Status Badge</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($my_ledger_result && $my_ledger_result->num_rows > 0): ?>
                            <?php while ($b = $my_ledger_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $b['id']; ?></strong></td>
                                    <td><span class="table-room"><?php echo htmlspecialchars($b['room_number']); ?></span></td>
                                    <td><?php echo htmlspecialchars($b['room_type']); ?></td>
                                    <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                                    <td><?php echo htmlspecialchars($b['time_slot']); ?></td>
                                    <td><em><?php echo htmlspecialchars($b['purpose']); ?></em></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($b['status']); ?>">
                                            <?php echo htmlspecialchars($b['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">You have not requested any facility reservation records yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="system-footer">
        <div class="footer-container">
            <p>&copy; 2026 Polytechnic University of the Philippines &ndash; Biñan Campus.</p>
        </div>
    </footer>

</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>