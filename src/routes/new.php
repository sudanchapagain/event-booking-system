<?php
ob_start();
include __DIR__ . "/../components/header.php";

$user_id = $_SESSION["user_id"] ?? null;
$db = getDbConnection();
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $start_date = trim($_POST["start_date"] ?? "");
    $end_date = trim($_POST["end_date"] ?? "");
    $capacity = intval($_POST["capacity"] ?? 0);
    $description = trim($_POST["description"] ?? "");
    $category = intval($_POST["category"] ?? null);
    $price = isset($_POST["ticket_price"]) && $_POST["ticket_price"] !== "" ? floatval($_POST["ticket_price"]) : 0.00;
    $ticket_types = $_POST["ticket_types"] ?? [];
    $event_image_url = null;

    $titlePattern = '/^[a-zA-Z0-9\s]+$/';
    $locationPattern = '/^[a-zA-Z0-9\s,.-]+$/';

    if (empty($title) || !preg_match($titlePattern, $title)) {
        $errors[] = "Title is required and can only contain letters, numbers, and spaces.";
    }

    if (empty($location) || !preg_match($locationPattern, $location)) {
        $errors[] = "Location is required and can only contain letters, numbers, spaces, commas, periods, and dashes.";
    }

    if (empty($start_date)) {
        $errors[] = "Start date is required.";
    }

    if (empty($end_date)) {
        $errors[] = "End date is required.";
    }

    if (!empty($start_date) && !empty($end_date) && strtotime($start_date) >= strtotime($end_date)) {
        $errors[] = "Start date must be earlier than the end date.";
    }

    if ($capacity <= 0) {
        $errors[] = "Capacity must be a positive number.";
    }

    if (!empty($_FILES['event_image']['name'])) {
        $uploadDir = __DIR__ . '/../../public/uploads/events/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $file = $_FILES['event_image'];
        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF images are allowed.";
        } elseif ($file['size'] > 5000000) {
            $errors[] = "File is too large. Maximum size is 5MB.";
        } elseif (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = "Failed to upload image.";
        } else {
            $event_image_url = '/uploads/events/' . $fileName;
        }
    }

    if (empty($errors)) {
        try {
            pg_query($db, "BEGIN");

            $eventQuery = "INSERT INTO events (title, location, organizer_id, description, capacity, ticket_price, is_approved)
                           VALUES ($1, $2, $3, $4, $5, $6, FALSE) RETURNING event_id";
            $eventResult = pg_query_params($db, $eventQuery, [$title, $location, $user_id, $description, $capacity, $price]);
            $event_id = pg_fetch_result($eventResult, 0, "event_id");

            if ($event_image_url) {
                $imageQuery = "INSERT INTO event_images (event_id, image_url, image_type) VALUES ($1, $2, 'main')";
                pg_query_params($db, $imageQuery, [$event_id, $event_image_url]);
            }

            $dateQuery = "INSERT INTO event_dates (event_id, start_date, end_date) VALUES ($1, $2, $3)";
            pg_query_params($db, $dateQuery, [$event_id, $start_date, $end_date]);

            $ticketQuery = "INSERT INTO tickets (event_id, ticket_type, ticket_price) VALUES ($1, $2, $3)";
            foreach ($ticket_types as $ticket) {
                if (!empty($ticket['type']) && is_numeric($ticket['price']) && $ticket['price'] >= 0) {
                    pg_query_params($db, $ticketQuery, [$event_id, $ticket['type'], $ticket['price']]);
                }
            }

            if ($category) {
                $categoryQuery = "INSERT INTO event_category_mapping (event_id, category_id) VALUES ($1, $2)";
                pg_query_params($db, $categoryQuery, [$event_id, $category]);
            }

            pg_query($db, "COMMIT");
            header("Location: /dashboard");
            exit();
        } catch (Exception $e) {
            pg_query($db, "ROLLBACK");
            $errors[] = "Error creating event: " . $e->getMessage();
        }
    }
}

$categoriesQuery = 'SELECT category_id, name FROM event_categories';
$categoriesResult = pg_query($db, $categoriesQuery);
$categories = pg_fetch_all($categoriesResult) ?: [];
?>

<head>
    <title>Create Event - Chautari</title>
    <link rel="stylesheet" href="../assets/css/default.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/new.css">
</head>

<body>
    <main>
        <h1 style="font-size: 2.5rem; color: #ff4a22">Create a new event!</h1>

        <?php if (!empty($errors)): ?>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li class="error-message"><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="new" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            <label for="title">Event Title <small>*</small></label>
            <input type="text" name="title" id="title" placeholder="Internastional art exhibition by nepal art council" required>
            <div id="titleError" class="error-message"></div>
            <br>

            <label for="location">Location <small>*</small></label>
            <input type="text" name="location" id="location" placeholder="Pradarshani marg, Kathmandu" required>
            <div id="locationError" class="error-message"></div>
            <br>

            <div id="startDateError" class="error-message"></div>
            <div id="endDateError" class="error-message"></div>
            <div class="new-event-date-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="start_date">Start Date and Time <small>*</small></label>
                    <input type="datetime-local" name="start_date" id="start_date" required>
                    <script>
                        document.getElementById("start_date").min = new Date().toISOString().split("T")[0] + "T00:00";
                    </script>
                </div>
                <div>
                    <label for="end_date">End Date and Time <small>*</small></label>
                    <input type="datetime-local" name="end_date" id="end_date" required>
                    <script>
                        document.getElementById("end_date").min = new Date().toISOString().split("T")[0] + "T00:00";
                    </script>
                </div>
            </div>
            <br>

            <label for="event_image">Event Image</label>
            <input type="file" name="event_image" id="event_image" accept="image/*">
            <span class="tooltip">Upload an image to represent the event.</span>
            <br><br>

            <label for="description">Event Description <small>*</small></label>
            <textarea name="description" id="description" style="height: 200px;" required></textarea>
            <br><br>

            <label for="capacity">Event Capacity <small>*</small></label>
            <input type="number" name="capacity" id="capacity" placeholder="Specify the maximum number of attendees." required min="1">
            <div id="capacityError" class="error-message"></div>
            <br><br>

            <h3>Ticket Price</h3>
            <span class="tooltip">Leave the price empty if it's free.</span>
            <div id="ticket_price">
                <input type="number" name="ticket_price" step="0.01" min="0" placeholder="Ticket Price">
            </div>
            <br>

            <label for="category">Event Category</label>
            <select name="category" id="category">
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <button class="button-new" type="submit">Create Event</button>
        </form>

        <script src="../assets/js/new-event-validation.js"></script>
    </main>
</body>

<?php include __DIR__ . "/../components/footer.php"; ?>