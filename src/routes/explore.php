<?php
include __DIR__ . '/../components/header.php';

$db = getDbConnection();

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 10000;
$category = isset($_GET['category']) ? intval($_GET['category']) : null;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$whereConditions = [];
$params = [];
$paramIndex = 1;

if ($searchTerm) {
    $whereConditions[] = "e.title ILIKE $" . $paramIndex;
    $params[] = '%' . $searchTerm . '%';
    $paramIndex++;
}

if ($location) {
    $whereConditions[] = "e.location ILIKE $" . $paramIndex;
    $params[] = '%' . $location . '%';
    $paramIndex++;
}

if ($category) {
    $whereConditions[] = "ec.category_id = $" . $paramIndex;
    $params[] = $category;
    $paramIndex++;
}

if ($startDate) {
    $whereConditions[] = "ed.start_date >= $" . $paramIndex;
    $params[] = $startDate . ' 00:00:00';
    $paramIndex++;
}

if ($endDate) {
    $whereConditions[] = "ed.end_date <= $" . $paramIndex;
    $params[] = $endDate . ' 23:59:59';
    $paramIndex++;
}

if ($minPrice !== 0 || $maxPrice !== 10000) {
    $whereConditions[] = "e.ticket_price BETWEEN $" . $paramIndex . " AND $" . ($paramIndex + 1);
    $params[] = $minPrice;
    $params[] = $maxPrice;
    $paramIndex += 2;
}

$whereConditions[] = "e.is_approved = TRUE";

$whereClause = $whereConditions ? "WHERE " . implode(' AND ', $whereConditions) : '';

$query = "
    SELECT e.event_id, e.title, e.location, e.capacity, e.ticket_price,
           ed.start_date, ed.end_date, c.name AS category
    FROM events e
    LEFT JOIN event_category_mapping ec ON e.event_id = ec.event_id
    LEFT JOIN event_categories c ON ec.category_id = c.category_id
    LEFT JOIN event_dates ed ON e.event_id = ed.event_id
    $whereClause
    GROUP BY e.event_id, ed.start_date, ed.end_date, c.name
    ORDER BY ed.start_date ASC
";

$result = pg_query_params($db, $query, $params);

if (!$result) {
    die("Error fetching events.");
}

$events = pg_fetch_all($result);

$categoriesQuery = 'SELECT category_id, name FROM event_categories';
$categoriesResult = pg_query($db, $categoriesQuery);

if (!$categoriesResult) {
    die("Error fetching categories.");
}

$categories = pg_fetch_all($categoriesResult);

function safe_htmlspecialchars($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<head>
    <title>Explore Events</title>
    <script defer src="../assets/js/explore.js"></script>
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/default.css">
    <link rel="stylesheet" href="../assets/css/explore.css">
</head>

<body>
    <main>
        <h1 style="font-size: 2.5rem; margin-top: 1rem">Explore Events</h1>

        <form method="GET" action="explore">
            <input type="text" name="search" id="search" value="<?= safe_htmlspecialchars($searchTerm) ?>" placeholder="Search here"><button class="search-connect" type="submit">Search</button>
            <button type="button" class="filter-button" onclick="showFilterDialog()" style="background-color: #fff; color: grey; border: 1px solid grey; padding: 10px 15px; border-radius: 8px">More Filters</button>

            <dialog id="filter-dialog" style="border: 0.1px solid #6c1501; border-radius: 8px">
                <form method="dialog">
                    <fieldset>
                        <legend>Filter Options</legend>

                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" value="<?= safe_htmlspecialchars($location) ?>" placeholder="City, venue, etc.">

                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $category == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= safe_htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="min_price">Price Range</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" name="min_price" id="min_price" value="<?= $minPrice ?>" placeholder="Min" min="0">
                            <input type="number" name="max_price" id="max_price" value="<?= $maxPrice ?>" placeholder="Max" min="0">
                        </div>

                        <label for="start_date">Event Dates</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="date" name="start_date" id="start_date" value="<?= isset($_GET['start_date']) ? safe_htmlspecialchars($_GET['start_date']) : '' ?>" placeholder="Start Date">
                            <input type="date" name="end_date" id="end_date" value="<?= isset($_GET['end_date']) ? safe_htmlspecialchars($_GET['end_date']) : '' ?>" placeholder="End Date">
                        </div>
                    </fieldset>

                    <div class="form-actions" style="margin-top: 1rem">
                        <button type="button" onclick="closeFilterDialog()" style="background-color: grey">Close</button>
                        <button type="submit">Apply Filters</button>
                    </div>
                </form>
            </dialog>

        </form>

        <div class="event-wrapper">
            <?php if ($events): ?>
                <div class="event-container">
                    <?php foreach ($events as $event): ?>
                        <div class="event-item">
                            <?php
                            $imageQuery = "SELECT image_url FROM event_images WHERE event_id = $1 LIMIT 1";
                            $imageResult = pg_query_params($db, $imageQuery, [$event['event_id']]);
                            $image = pg_fetch_assoc($imageResult);
                            ?>
                            <?php if ($image && isset($image['image_url'])): ?>
                                <img src="<?= htmlspecialchars($image['image_url']); ?>" width="100%" style="height: 200px; border-radius: 12px; border: 1px solid lightgrey;">
                            <?php else: ?>
                                <div style="display: block; height: 100px"></div>
                            <?php endif; ?>
                            <br>
                            <br>
                            <h2 class="event-title"><?= safe_htmlspecialchars($event['title']) ?></h2>
                            <hr style="margin: 10px 0">
                            <p class="event-location">Location: <?= safe_htmlspecialchars($event['location']) ?></p>
                            <p class="event-category">Category: <?= safe_htmlspecialchars($event['category']) ?></p>
                            <p class="event-date">Start Date: <?= safe_htmlspecialchars($event['start_date']) ?> <br> End Date: <?= safe_htmlspecialchars($event['end_date']) ?></p>
                            <p class="event-price">Price: NPR-<?= safe_htmlspecialchars($event['ticket_price']) ?: '0' ?></p>
                            <p><a href="/event?event_id=<?= $event['event_id'] ?>" class="cta-button-event-list">View More</a></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No events found.</p>
            <?php endif; ?>
        </div>
    </main>
</body>

<?php include __DIR__ . '/../components/footer.php'; ?>