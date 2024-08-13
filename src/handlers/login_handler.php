<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email format.');
    }

    $password = $_POST['password'];
    if (strlen($password) < 8) {
        die('Password must be at least 8 characters long.');
    }

    $conn = getDbConnection();

    if (!$conn) {
        header('Location: ../login?login=fail_generic');
        exit();
    }

    $query = 'SELECT user_id, password_hash FROM users WHERE email = $1';
    $result = pg_query_params($conn, $query, [$email]);

    if ($result) {
        if (pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $user_id = $row['user_id'];
            $password_hash = $row['password_hash'];

            if (password_verify($password, $password_hash)) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $email;

                header('Location: ../explore');
                exit();
            } else {
                header('Location: ../login?login=fail');
                exit();
            }
        } else {
            header('Location: ../login?login=fail');
            exit();
        }
    } else {
        header('Location: ../login?login=fail_generic');
        exit();
    }

    pg_close($conn);
} else {
    die('Invalid request method.');
}
