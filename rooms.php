<?php
session_start();

/**
 * StayPUP: Rooms & Facilities Catalogue
 * CPET 103 Web Project
 */

// Check if running on Railway cloud environment, otherwise fallback to local XAMPP
$db_host = isset($_ENV['MYSQLHOST'])     ? $_ENV['MYSQLHOST']     : "localhost";
$db_user = isset($_ENV['MYSQLUSER'])     ? $_ENV['MYSQLUSER']     : "root";
$db_pass = isset($_ENV['MYSQLPASSWORD']) ? $_ENV['MYSQLPASSWORD'] : "";
$db_name = isset($_ENV['MYSQLDATABASE']) ? $_ENV['MYSQLDATABASE'] : "staypup_db";
$db_port = isset($_ENV['MYSQLPORT'])     ? $_ENV['MYSQLPORT']     : "3306";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("<div style='color:red;'>Database Connection Broken: " . $conn->connect_error . "</div>");
}

$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Guest';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest User';

$rooms_query = "SELECT * FROM rooms ORDER BY room_number ASC";
$rooms_result = $conn->query($rooms_query);
$rooms = [];
if ($rooms_result && $rooms_result->num_rows > 0) {
    while($row = $rooms_result->fetch_assoc()) {
        $row['amenities_arr'] = array_map('trim', explode(',', $row['amenities']));
        $rooms[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms & Facilities - StayPUP</title>
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
                <a href="rooms.php" class="active">Rooms & Facilities</a>
                <a href="my_reservations.php">My Reservations</a>
                <a href="logout.php" style="color: #ffcccc; margin-left: 15px;">Logout</a>
            </nav>
            <div class="user-profile">
                <div class="user-avatar"><?php echo substr(htmlspecialchars($user_name), 0, 1); ?></div>
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
                <h2>Campus Facilities Directory</h2>
                <p>Detailed breakdown of specialized physical spaces and equipment groups setup within PUP Biñan campus.</p>
            </div>

            <div class="facility-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
                <?php if (empty($rooms)): ?>
                    <p>No rooms currently provisioned within the infrastructure database catalog.</p>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="facility-card">
                            <div class="facility-image-wrapper">
                                <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="<?php echo htmlspecialchars($room['room_number']); ?>" class="facility-img">
                                <span class="status-indicator status-<?php echo strtolower($room['status']); ?>">
                                    <?php echo htmlspecialchars($room['status']); ?>
                                </span>
                            </div>
                            <div class="facility-body">
                                <div class="facility-meta">
                                    <h3><?php echo htmlspecialchars($room['room_number']); ?></h3>
                                    <span class="facility-type"><?php echo htmlspecialchars($room['room_type']); ?></span>
                                </div>
                                <p class="facility-capacity">👤 Maximum Capacity: <strong><?php echo intval($room['capacity']); ?> seats</strong></p>
                                
                                <div class="amenities-container">
                                    <?php foreach ($room['amenities_arr'] as $amenity): ?>
                                        <span class="amenity-tag"><?php echo htmlspecialchars($amenity); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="card-actions">
                                    <a href="index.php" class="btn btn-primary" style="text-align: center; text-decoration: none; width: 100%;">Book This Space</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
<?php $conn->close(); ?>