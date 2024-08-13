<?php
$khalti_public_key = "46b35070613c4b79853d998afc3feafa";
$conn = getDbConnection();

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_NUMBER_INT);

$eventQuery = "SELECT title, ticket_price FROM events WHERE event_id = $event_id";
$eventResult = pg_query($conn, $eventQuery);
if (!$eventResult) {
    die("Error fetching event details: " . pg_last_error($conn));
}
if (pg_num_rows($eventResult) == 0) {
    die("Event not found.");
}

$eventData = pg_fetch_assoc($eventResult);
$eventName   = $eventData['title'];
$ticketPrice = $eventData['ticket_price'];

$amount = (float)$ticketPrice * 100;
$purchase_order_id   = "event/id-" . $event_id;
$purchase_order_name = $eventName;

$return_url = "http://localhost:8080/validate";
$website_url = "http://localhost:8080";

$payload = [
    "return_url"          => $return_url,
    "website_url"         => $website_url,
    "amount"              => $amount,
    "purchase_order_id"   => $purchase_order_id,
    "purchase_order_name" => $purchase_order_name
];

$initiate_url = "https://dev.khalti.com/api/v2/epayment/initiate/";

$live_secret_key_formatted = KHALTI_SECRET_KEY;

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $initiate_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => "POST",
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        "Authorization: key $live_secret_key_formatted",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);
if (curl_errno($curl)) {
    $error_message = 'cURL Error: ' . curl_error($curl);
    curl_close($curl);
    pg_close($conn);
    die($error_message);
}
curl_close($curl);

$parsed = json_decode($response, true);

if (isset($parsed["payment_url"])) {
    header("Location: " . $parsed["payment_url"]);
    pg_close($conn);
    exit();
} else {
    $error_detail = isset($parsed['detail']) ? $parsed['detail'] : 'Unknown error';
    pg_close($conn);
    die("Payment initiation failed. Error: " . $error_detail);
}
