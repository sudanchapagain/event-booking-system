<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log('Signup handler invoked');
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: /signup?signup_error=wrong_email_format');
        exit();
    }

    $username = htmlspecialchars(trim($_POST['username']));
    if (strlen($username) < 3) {
        header('Location: /signup?signup_error=wrong_username_format');
        exit();
    }

    $password = $_POST['password'];
    if (strlen($password) < 8) {
        header('Location: /signup?signup_error=wrong_password_length');
        exit();
    }

    $phone_number = trim($_POST['phone_number']);
    if (!preg_match('/^(98|97)\d{8}$/', $phone_number)) {
        header('Location: /signup?signup_error=wrong_phone_format');
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $conn = getDbConnection();

    if (!$conn) {
        header('Location: /signup?signup_error=generic');
        exit();
    }

    $email_check_query = 'SELECT 1 FROM users WHERE email = $1';
    $email_check_result = pg_query_params($conn, $email_check_query, [$email]);

    if (pg_num_rows($email_check_result) > 0) {
        header('Location: /signup?signup_error=email_fail');
        exit();
    }

    $username_check_query = 'SELECT 1 FROM users WHERE username = $1';
    $username_check_result = pg_query_params($conn, $username_check_query, [$username]);

    if (pg_num_rows($username_check_result) > 0) {
        header('Location: /signup?signup_error=username_taken');
        exit();
    }

    $phone_check_query = 'SELECT 1 FROM users WHERE user_phone = $1';
    $phone_check_result = pg_query_params($conn, $phone_check_query, [$phone_number]);

    if (pg_num_rows($phone_check_result) > 0) {
        header('Location: /signup?signup_error=duplicate_phone');
        exit();
    }

    $query = 'INSERT INTO users (username, email, password_hash, user_phone) VALUES ($1, $2, $3, $4)';
    $result = pg_query_params($conn, $query, [$username, $email, $password_hash, $phone_number]);

    if ($result) {
        header('Location: /login?signup=success');
        exit();
    } else {
        header('Location: /signup?signup_error=generic');
        exit();
    }

    pg_close($conn);
} else {
    die('Invalid request method.');
}
