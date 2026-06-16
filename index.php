<?php
session_start();

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Global user profile state strings
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest User';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Guest';

/**
 * StayPUP: A PUP Biñan Room Reservation System
 * CPET 103 Web Project - Database Driven Model (MySQL Cloud & XAMPP)
 */

// 1. Establish Database Connection Matrix (Handles Railway Cloud Variables + Local XAMPP fallback)
$db_host = isset($_ENV['MYSQLHOST'])     ? $_ENV['MYSQLHOST']     : "localhost";
$db_user = isset($_ENV['MYSQLUSER'])     ? $_ENV['MYSQLUSER']     : "root";
$db_pass = isset($_ENV['MYSQLPASSWORD']) ? $_ENV['MYSQLPASSWORD'] : "";
$db_name = isset($_ENV['MYSQLDATABASE']) ? $_ENV['MYSQLDATABASE'] : "staypup_db";
$db_port = isset($_ENV['MYSQLPORT'])     ? $_ENV['MYSQLPORT']     : "3306";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("<div style='color:red; font-family:sans-serif; padding:20px;'><strong>Database Connection Broken:</strong> " . $conn->connect_error . "</div>");
}

$conflict_alert = false;
$success_alert = isset($_GET['success']) && $_GET['success'] == 1;

// =========================================================================
// POST CONTROLLER ENGINE: Handles Both Creation and Modification Updates
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- MODE A: HANDLE SUBMISSION OF AN EDIT / UPDATE REQUEST ---
    if (isset($_POST['update_reservation_id'])) {
        $res_id        = intval($_POST['update_reservation_id']);
        $selected_room = $_POST['room_id'];
        $booking_date  = $_POST['booking_date'];
        $time_slot     = $_POST['time_slot'];
        $purpose       = trim($_POST['purpose']);
        $status        = $_POST['status']; 

        // Evaluate if they picked a DB asset or typed a manual room number
        if ($selected_room === 'custom_specify') {
            $final_room_identity = isset($_POST['custom_room_number']) ? trim($_POST['custom_room_number']) : '';
        } else {
            $final_room_identity = $selected_room;
        }

        // Update statement execution using $final_room_identity text string variable mapping
        $update_stmt = $conn->prepare("UPDATE reservations SET room_id = ?, booking_date = ?, time_slot = ?, purpose = ?, status = ? WHERE id = ?");
        $update_stmt->bind_param("sssssi", $final_room_identity, $booking_date, $time_slot, $purpose, $status, $res_id);
        
        if ($update_stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            echo "<div style='color:red;'>Execution Update Error: " . $update_stmt->error . "</div>";
        }
        $update_stmt->close();
    }
    
    // --- MODE B: HANDLE NEW RESERVATION ENTRY CREATION ---
    else {
        // Capture the structural form fields safely
        $booking_date  = isset($_POST['booking_date']) ? trim($_POST['booking_date']) : '2026-06-15';
        $time_slot     = isset($_POST['time_slot']) ? trim($_POST['time_slot']) : '';
        $purpose       = isset($_POST['purpose']) ? trim($_POST['purpose']) : ''; 
        $selected_room = isset($_POST['room_id']) ? $_POST['room_id'] : '';

        // Evaluate if they picked a DB asset or typed a manual room number
        if ($selected_room === 'custom_specify') {
            $final_room_identity = isset($_POST['custom_room_number']) ? trim($_POST['custom_room_number']) : '';
        } else {
            $final_room_identity = $selected_room;
        }

        // Only run insertion if fields aren't completely blank
        if (!empty($final_room_identity) && !empty($booking_date) && !empty($time_slot)) {
            
            // Prepared insertion target logic binding reserved_by data dynamically
            $stmt = $conn->prepare("INSERT INTO reservations (room_id, booking_date, time_slot, purpose, reserved_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $final_room_identity, $booking_date, $time_slot, $purpose, $user_name);
            
            if ($stmt->execute()) {
                // Refresh cleanly to show the new data entry in your dashboard logs
                header("Location: index.php?success=1");
                exit();
            } else {
                echo "<div style='color:red;'>Execution Insertion Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
}

// =========================================================================
// DATA MANIPULATION ENGINE: Handle Edit & Delete/Cancel Requests via GET
// =========================================================================

// --- HANDLE DELETE / CANCEL ACTION ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    // Updates status to 'Cancelled' to maintain structural database history log integrity
    $delete_stmt = $conn->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        header("Location: index.php");
        exit();
    }
    $delete_stmt->close();
}

// --- HANDLE UPDATE LOG ACCESSION ---
$edit_mode = false;
$edit_data = [];

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['id']);
    
    // Fetch current details of the targeted log to pre-populate our booking form
    $edit_query = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
    $edit_query->bind_param("i", $edit_id);
    $edit_query->execute();
    $edit_data = $edit_query->get_result()->fetch_assoc();
    $edit_query->close();
}

// 4. Read Phase: Fetch All Active System Rooms Matrix Profiles
$rooms_query = "SELECT * FROM rooms";
$rooms_result = $conn->query($rooms_query);
$rooms = [];
if ($rooms_result && $rooms_result->num_rows > 0) {
    while($row = $rooms_result->fetch_assoc()) {
        $row['amenities_arr'] = array_map('trim', explode(',', $row['amenities']));
        $rooms[] = $row;
    }
}

// 5. Read Phase: Fetch Combined Reservation Ledger via Table Left Join 
$ledger_query = "SELECT r.id, r.room_id, rm.room_number, r.booking_date, r.time_slot, r.status 
                 FROM reservations r 
                 LEFT JOIN rooms rm ON r.room_id = rm.id 
                 ORDER BY r.id DESC";
$ledger_result = $conn->query($ledger_query);

// Get first initial for dynamic circle avatar icon
$first_letter = !empty($user_name) ? strtoupper($user_name[0]) : 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StayPUP - Facility Reservation System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                 <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
                 <a href="rooms.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>">Rooms & Facilities</a>
                 <a href="my_reservations.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_reservations.php' ? 'active' : ''; ?>">My Reservations</a>
                 <?php if ($user_role === 'Admin'): ?>
                    <a href="#" class="admin-badge">Admin Panel</a>
                 <?php endif; ?>
                 <a href="logout.php" style="color: #ffcccc; margin-left: 15px;">Logout</a>
            </nav>

            <div class="user-profile-container" style="display: flex; align-items: center; gap: 10px;">
                <span class="avatar-circle" style="background-color: #ffcc00; color: #000; font-weight: bold; padding: 5px 11px; border-radius: 50%; display: inline-block;">
                  <?php echo $first_letter; ?>
                </span>
                <span class="account-details" style="color: #fff; font-weight: 500;">
                  <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
                </span>
            </div>
        </div>
    </header>

    <main class="dashboard-wrapper">
        
        <?php if ($conflict_alert): ?>
            <div class="alert alert-danger animate-fade">
                <div class="alert-icon">⚠️</div>
                <div class="alert-content">
                    <h3>Automated Schedule Conflict Detected!</h3>
                    <p>The requested room is already allocated during that specific date and time slot block.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success_alert): ?>
            <div class="alert alert-success animate-fade">
                <div class="alert-icon">✓</div>
                <div class="alert-content">
                    <h3>Reservation Request Submitted Successfully!</h3>
                    <p>Your booking has been compiled into the centralized database ledger successfully.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid-layout">
            
            <section class="main-content-panel">
                <div class="panel-header">
                    <h2>Real-Time Room Profiles & Availability</h2>
                    <p>Live inventory values loaded dynamically from <code>staypup_db.rooms</code> data.</p>
                </div>

                <div class="facility-grid">
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
                                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('room_select').value = '<?php echo $room['id']; ?>'; handleRoomSpecificationChange();">
                                            Select Space
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div> 

                <div class="ledger-section">
                    <div class="panel-header">
                        <h2>Centralized Reservation Logs Ledger</h2>
                        <p>Real-time relational database record join tracking activity logs inside system.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="ledger-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Room/Facility</th>
                                    <th>Scheduled Date</th>
                                    <th>Time Frame Allocation</th>
                                    <th>Status Badge</th>
                                    <th>Actions Management</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($ledger_result && $ledger_result->num_rows > 0): ?>
                                    <?php while ($b = $ledger_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $b['id']; ?></strong></td>
                                            <td>
                                                <span class="table-room">
                                                    <?php echo !empty($b['room_number']) ? htmlspecialchars($b['room_number']) : htmlspecialchars($b['room_id']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                                            <td><?php echo htmlspecialchars($b['time_slot']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower($b['status']); ?>">
                                                    <?php echo htmlspecialchars($b['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="?action=edit&id=<?php echo $b['id']; ?>" class="action-btn edit-btn" title="Update Log" style="text-decoration: none; font-size: 1.1rem;">✏️</a>
                                                    <a href="?action=delete&id=<?php echo $b['id']; ?>" class="action-btn delete-btn" title="Cancel/Delete Booking" onclick="return confirm('Are you sure you want to cancel this reservation record?');" style="text-decoration: none; font-size: 1.1rem;">🗑️</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No reservation instances logged inside relational storage files yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <aside class="sidebar-panel">
                <div class="sticky-panel">
                    <div class="panel-header border-bottom">
                        <h2><?php echo $edit_mode ? "Modify Reservation #".$edit_data['id'] : "Create Room Reservation"; ?></h2>
                        <p>Centralized ledger data mutation workspace.</p>
                    </div>

                    <form action="" method="POST" class="booking-form">
                        
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="update_reservation_id" value="<?php echo $edit_data['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="room_select">Target Room Location</label>
                            <select name="room_id" id="room_select" required onchange="handleRoomSpecificationChange()">
                                <option value="" disabled <?php echo !$edit_mode ? 'selected' : ''; ?>>-- Select an Available Room --</option>
                                
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>" <?php echo ($edit_mode && $edit_data['room_id'] == $room['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($room['room_number']); ?> (Cap: <?php echo $room['capacity']; ?>)
                                    </option>
                                <?php endforeach; ?>
                                
                                <option value="custom_specify" <?php echo ($edit_mode && !numeric_check_custom($edit_data['room_id'], $rooms)) ? 'selected' : ''; ?>>✨ Specify Room Number...</option>
                            </select>
                        </div>

                        <?php 
                        function numeric_check_custom($current_id, $rooms_list) {
                            foreach($rooms_list as $rm) {
                                if($rm['id'] == $current_id) return true;
                            }
                            return false;
                        }
                        ?>

                        <div class="form-group" id="manual_specification_container" style="display: none; margin-top: 0.85rem;">
                            <label for="custom_room_number">Enter Custom Room Designation</label>
                            <input type="text" name="custom_room_number" id="custom_room_number" 
                                   value="<?php echo ($edit_mode && !numeric_check_custom($edit_data['room_id'], $rooms)) ? htmlspecialchars($edit_data['room_id']) : ''; ?>"
                                   placeholder="e.g., 201, 302, or Engineering Lab" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 6px;">
                        </div>
                                 
                        <div class="form-group">
                            <label for="room_type">Room Classification Type</label>
                            <select name="room_type" id="room_type" required>
                                <option value="Normal Room">Standard Lecture Room</option>
                                <option value="Computer Lab">Computer Laboratory Room</option>
                                <option value="CAD Lab">CAD Laboratory Room</option>
                            </select>
                        </div>

                        <div class="form-group"> 
                            <label for="booking_date">Target Date Execution</label>
                            <input type="date" name="booking_date" id="booking_date" required
                                   value="<?php echo $edit_mode ? $edit_data['booking_date'] : '2026-06-15'; ?>">
                        </div>

                        <div class="form-group">
                            <label for="time_slot">Time Slot Allocation Block</label>
                            <select name="time_slot" id="time_slot" required>
                                <option value="" disabled <?php echo !$edit_mode ? 'selected' : ''; ?>>-- Select a Time Window --</option>
                                <?php 
                                $slots = ["07:30 AM - 10:30 AM", "10:30 AM - 01:30 PM", "01:30 PM - 04:30 PM", "04:30 PM - 07:30 PM"];
                                foreach($slots as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($edit_mode && $edit_data['time_slot'] === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($edit_mode): ?>
                            <div class="form-group">
                                <label for="status_select">Authorization Status Badge</label>
                                <select name="status" id="status_select" required>
                                    <option value="Pending" <?php echo $edit_data['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo $edit_data['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Cancelled" <?php echo $edit_data['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="purpose">Academic Purpose / Justification</label>
                            <textarea name="purpose" id="purpose" rows="3" required><?php echo $edit_mode ? htmlspecialchars($edit_data['purpose']) : ''; ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <?php echo $edit_mode ? "Save Structural Modifications" : "Verify & Execute Reservation"; ?>
                        </button>
                        
                        <?php if ($edit_mode): ?>
                            <a href="index.php" class="btn btn-secondary btn-block" style="text-align:center; text-decoration:none; margin-top:0.5rem; display:block;">Cancel Editing</a>
                        <?php endif; ?>
                    </form>
                </div>
            </aside>

        </div>
    </main>

    <footer class="system-footer">
        <div class="footer-container">
            <p>&copy; 2026 Polytechnic University of the Philippines &ndash; Biñan Campus. All Rights Reserved.</p>
            <p><strong>CPET 103 Web Engineering Project Portfolio DB Backend Artifact</strong></p>
        </div>
    </footer>

<script>
function handleRoomSpecificationChange() {
    var selectField = document.getElementById('room_select');
    var inputContainer = document.getElementById('manual_specification_container');
    var customInput = document.getElementById('custom_room_number');

    if (selectField && selectField.value === 'custom_specify') {
        inputContainer.style.display = 'block';
        customInput.setAttribute('required', 'required');
        <?php if (!$edit_mode): ?>
        customInput.focus();
        <?php endif; ?>
    } else if (inputContainer) {
        inputContainer.style.display = 'none';
        customInput.removeAttribute('required');
    }
}

document.addEventListener("DOMContentLoaded", handleRoomSpecificationChange);
</script>

</body>
</html>
<?php $conn->close(); ?>