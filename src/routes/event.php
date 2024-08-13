<?php
ob_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/credentials.php';

$db = getDbConnection();
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (isset($_POST['event_id']) ? (int)$_POST['event_id'] : -1);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($event_id < 1) {
    header('Location: /explore');
    exit();
}
error_log("event_id: $event_id, action: $action");

$imageQuery = "SELECT image_url FROM event_images WHERE event_id = $1 LIMIT 1";
$imageResult = pg_query_params($db, $imageQuery, [$event_id]);
$image = pg_fetch_assoc($imageResult);

$query = "
    SELECT e.*, u.username AS organizer_name, u.user_id AS organizer_id, COALESCE((
        SELECT COUNT(*) FROM user_event_attendance WHERE event_id = e.event_id
    ), 0) AS current_participants
    FROM events e
    LEFT JOIN users u ON e.organizer_id = u.user_id
    WHERE e.event_id = $1 AND e.is_approved = TRUE
";
$result = pg_query_params($db, $query, [$event_id]);

if (!$result) {
    error_log("Database Error: " . pg_last_error($db));
    $error = "An error occurred while fetching the event.";
} else {
    $event = pg_fetch_assoc($result);
    if (!$event) {
        header('Location: /explore');
        exit();
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $query = "SELECT is_admin, is_organizer FROM users WHERE user_id = $1 LIMIT 1";
    $result = pg_query_params($db, $query, [$user_id]);

    if ($result) {
        $user = pg_fetch_assoc($result);
        if ($user) {
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['is_organizer'] = $user['is_organizer'];
        } else {
            $_SESSION['is_admin'] = false;
            $_SESSION['is_organizer'] = false;
        }
    } else {
        error_log("Database Error: " . pg_last_error($db));
        $_SESSION['is_admin'] = false;
        $_SESSION['is_organizer'] = false;
    }
} else {
    $_SESSION['is_admin'] = false;
    $_SESSION['is_organizer'] = false;
    $user_id = null;
}

$is_admin = $_SESSION['is_admin'];
$is_organizer = $_SESSION['is_organizer'];
$is_event_organizer = ($is_organizer && $_SESSION['user_id'] == $event['organizer_id']);

$bookingQuery = "SELECT 1 FROM user_event_attendance WHERE user_id = $1 AND event_id = $2 LIMIT 1";
$bookingResult = pg_query_params($db, $bookingQuery, [$user_id, $event_id]);
$is_booked = pg_num_rows($bookingResult) > 0;

if ($action === 'register' && !$is_booked && $event['ticket_price'] == 0) {
    $addQuery = "
        INSERT INTO user_event_attendance (user_id, event_id)
        VALUES ($1, $2)
        ON CONFLICT DO NOTHING
    ";
    $result = pg_query_params($db, $addQuery, [$user_id, $event_id]);

    if ($result) {
        $_SESSION['transaction_msg'] = "You have successfully registered for the event.";
    } else {
        $_SESSION['transaction_msg'] = "Error registering for the event.";
    }

    header("Location: /event?event_id=$event_id");
    exit();
}

if ($action === 'cancel' && $is_booked) {
    $cancelQuery = "DELETE FROM user_event_attendance WHERE user_id = $1 AND event_id = $2";
    $cancelResult = pg_query_params($db, $cancelQuery, [$user_id, $event_id]);

    if ($cancelResult) {
        $_SESSION['transaction_msg'] = "Your booking has been canceled.";
    } else {
        $_SESSION['transaction_msg'] = "Error canceling your booking.";
    }

    header("Location: /event?event_id=$event_id");
    exit();
}

if ($action === 'delete' && ($is_event_organizer || $is_admin)) {
    $deleteAttendanceQuery = "DELETE FROM user_event_attendance WHERE event_id = $1";
    $deleteImagesQuery = "DELETE FROM event_images WHERE event_id = $1";
    $deleteDatesQuery = "DELETE FROM event_dates WHERE event_id = $1";
    $deleteEventQuery = "DELETE FROM events WHERE event_id = $1";

    $deleteAttendanceResult = pg_query_params($db, $deleteAttendanceQuery, [$event_id]);
    $deleteImagesResult = pg_query_params($db, $deleteImagesQuery, [$event_id]);
    $deleteDatesResult = pg_query_params($db, $deleteDatesQuery, [$event_id]);
    $deleteEventResult = pg_query_params($db, $deleteEventQuery, [$event_id]);

    if ($deleteEventResult) {
        $_SESSION['transaction_msg'] = "Event successfully deleted.";
    } else {
        $_SESSION['transaction_msg'] = "Error deleting event." . pg_last_error($db);
    }

    header('Location: /explore');
    exit();
}

if ($action === 'edit' && $is_event_organizer) {
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $capacity = (int)$_POST['capacity'];
    $ticket_price = (float)$_POST['ticket_price'];

    if (empty($title) || empty($location) || empty($description) || $capacity <= 0 || $ticket_price <= 0) {
        $error = "Please fill in all fields correctly.";
    }

    $checkEventQuery = "SELECT 1 FROM events WHERE event_id = $1 AND organizer_id = $2";
    $eventCheckResult = pg_query_params($db, $checkEventQuery, [$event_id, $_SESSION['user_id']]);
    if (!$eventCheckResult || pg_num_rows($eventCheckResult) === 0) {
        throw new Exception("Event not found or you do not have permission to edit it.");
    }

    $updateQuery = "UPDATE events SET title = $1, location = $2, description = $3, capacity = $4, ticket_price = $5 WHERE event_id = $6 AND organizer_id = $7";

    $result = pg_query_params($db, $updateQuery, [ $title, $location, $description, $capacity, $ticket_price, $event_id, $_SESSION['user_id'] ]);

    if (!$result) {
        throw new Exception("Failed to update event: " . pg_last_error($db));
    }

    $_SESSION['transaction_msg'] = "Event updated successfully.";

    header("Location: /event?event_id=$event_id");
    exit();
}

if ($action == 'pay' && $event['ticket_price'] >= 0) {
    include __DIR__ . '../../handlers/payment_handler.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title'] ?? 'Event Details') ?> - Chautari</title>
    <link rel="stylesheet" href="../assets/css/default.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/event.css">
</head>

<body>
    <?php include __DIR__ . '/../components/header.php'; ?>

    <main>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="event-details boxed-text">
                <?php if ($image && isset($image['image_url'])): ?>
                    <img src="<?= htmlspecialchars($image['image_url']); ?>" width="100%" style="height: 400px; border-radius: 12px;border: 1px solid black; margin-top: 5rem;">
                <?php else: ?>
                    <div style="display: block; height: 100px"></div>
                <?php endif; ?>

                <h2 style="text-align: left;font-size: 3rem;"><?= htmlspecialchars($event['title']) ?></h2>
                <br>
                <div class="event-meta" style="display: grid;grid-template-columns: 1fr 1fr 1fr;">
                    <p><b>Organizer:</b> <?= htmlspecialchars($event['organizer_name'] ?? 'Unknown') ?></p>
                    <p><b>Address:</b> <?= htmlspecialchars($event['location']) ?></p>
                    <p><b>Available Capacity: </b><?= $event['capacity'] - $event['current_participants'] ?></p>
                </div>

                <br><br>
                <p style="font-size: 1.3rem; text-align: center;"><?= htmlspecialchars($event['ticket_price']) ?> <b><small>NPR</small></b></p>

                <div class="event-actions" style="margin: 50px auto; max-width: 400px; display: flex; gap: 10px;">
                    <?php if ($is_booked): ?>
                        <form action="/event?event_id=<?= $event_id ?>" method="GET" style="margin: 0;">
                            <input type="hidden" name="event_id" value="<?= $event_id ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="secondary-button" style="background-color: #E81C30; border:none; color: #fff;">Cancel Booking</button>
                        </form>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <p><a href="/login" class="secondary-button">Login to register for this event</a></p>
                    <?php else: ?>
                        <?php if ($event['ticket_price'] == 0): ?>
                            <form action="/event?event_id=<?= $event_id ?>" method="GET" style="margin: 0;">
                                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                <input type="hidden" name="action" value="register">
                                <button type="submit" class="primary-button register-btn" style="border: none;">Book Event</button>
                            </form>
                        <?php else: ?>
                            <form action="/event?event_id=<?= $event_id ?>" method="GET" style="margin: 0;">
                                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                <input type="hidden" name="action" value="pay">
                                <button type="submit" class="primary-button register-btn" style="border: none;">Book at <?= $event['ticket_price'] ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($is_event_organizer): ?>
                        <a href="javascript:void(0);" class="primary-button" style="background-color: #007BFF;" onclick="openModal()">Update</a>
                        <div id="editEventModal" class="modal">
                            <div class="modal-content">
                                <span class="close" onclick="closeModal()">&times;</span>
                                <h2>Edit Event Details</h2>
                                <form id="editEventForm" action="/event?event_id=<?= $event_id ?>" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="edit" value="<?= $event_id ?>">
                                    <input type="hidden" name="action" value="edit">

                                    <label for="title">Event Title:</label>
                                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($event['title']) ?>" required><br><br>

                                    <label for="location">Location:</label>
                                    <input type="text" id="location" name="location" value="<?= htmlspecialchars($event['location']) ?>" required><br><br>

                                    <label for="description">Description:</label>
                                    <textarea id="description" name="description"><?= htmlspecialchars($event['description']) ?></textarea><br><br>

                                    <label for="capacity">Capacity:</label>
                                    <input type="number" id="capacity" name="capacity" value="<?= htmlspecialchars($event['capacity']) ?>" required><br><br>

                                    <label for="ticket_price">Ticket Price:</label>
                                    <input type="number" id="ticket_price" name="ticket_price" value="<?= htmlspecialchars($event['ticket_price']) ?>" step="0.01" required><br><br>

                                    <button type="submit" style="border: none;" class="primary-button">Save Changes</button>
                                </form>
                            </div>
                        </div>

                        <form action="/event?event_id=<?= $event_id ?>" method="GET" style="margin: 0;">
                            <input type="hidden" name="event_id" value="<?= $event_id ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="secondary-button" style="background-color: #E81C30; border:none; color: #fff;">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="event-description">
                    <h2>Description</h2>
                    <p><?= nl2br(htmlspecialchars($event['description'] ?? 'No description available.')) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="../assets/js/event-modal.js"></script>
    </body>
</html>
