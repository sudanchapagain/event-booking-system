<?php
$conn = getDbConnection();

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $purchase_order_id = isset($_GET['purchase_order_id']) ? $_GET['purchase_order_id'] : '';
    preg_match('/event\/id-(\d+)/', $purchase_order_id, $matches);
    if (isset($matches[1])) {
        $event_id = (int)$matches[1];
    } else {
        $event_id = -1;
    }

    $pidx = isset($_GET['pidx']) ? $_GET['pidx'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : -1;

    if ($event_id === -1 || $user_id === -1 || $status !== "Completed" || !$pidx) {
        die("Invalid request or payment not successful.");
    }

    $insertQuery = "INSERT INTO user_event_attendance (user_id, event_id, status) VALUES ($user_id, $event_id, 'confirmed')";
    $insertResult = pg_query($conn, $insertQuery);

    if ($insertResult) {
        header("Refresh: 2; URL=/event?event_id=" . $event_id);
    } else {
        die("Error registering user: " . pg_last_error($conn));
    }
    pg_close($conn);
} else {
    echo "Invalid request method.";
}
?>
