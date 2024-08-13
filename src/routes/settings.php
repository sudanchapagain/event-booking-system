<?php
ob_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login");
    exit();
}

include __DIR__ . '/../components/header.php';
$db = getDbConnection();

$query = "SELECT username, user_phone, email FROM users WHERE user_id = $1";
$result = pg_query_params($db, $query, [$user_id]);

if (!$result || pg_num_rows($result) === 0) {
    die("User not found.");
}

$user = pg_fetch_assoc($result);

function escape($value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['user_phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $errors = [];

    if (!$username) {
        $errors[] = "Username is required.";
    }
    if ($phone && !preg_match('/^\+?\d{10,15}$/', $phone)) {
        $errors[] = "Invalid phone number format.";
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if (empty($errors)) {
        $updateQuery = "UPDATE users SET username = $1, user_phone = $2, email = $3";
        $params = [$username, $phone, $email];

        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $updateQuery .= ", password_hash = $" . (count($params) + 1);
            $params[] = $password_hash;
        }

        $updateQuery .= " WHERE user_id = $" . (count($params) + 1);
        $params[] = $user_id;

        $updateResult = pg_query_params($db, $updateQuery, $params);

        if ($updateResult) {
            header("Location: /settings?update=success");
            exit();
        } else {
            $errors[] = "Failed to update profile.";
        }
    }
}
?>

<head>
    <title>Settings - Chautari</title>
    <link rel="stylesheet" href="../assets/css/default.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/sign.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>
    <main style="max-width: 550px; margin: 0 auto;">
        <h1 style="font-size: 2.3rem; margin-top: 50px;">Settings</h1>

        <?php if (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
            <p class="signupSuccessMessage">Profile updated successfully!</p>
        <?php elseif (!empty($errors)): ?>
            <div class="signupFailMessage">
                <?php foreach ($errors as $error): ?>
                    <p><?= escape($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/settings" style="border: 1px solid lightgrey; border-radius: 8px; padding: 30px; background-color: #fafafa;">
            <label for="username" class="subtitle-profile-detail">Username</label>
            <input type="text" id="username" name="username" class="data-item-profile-detail"
                value="<?= escape($user['username']) ?>" required style="width: 100%;" />

            <label for="user_phone" class="subtitle-profile-detail">Phone Number</label>
            <input type="text" id="user_phone" name="user_phone" class="data-item-profile-detail"
                value="<?= escape($user['user_phone']) ?>" style="width: 100%;" />

            <label for="email" class="subtitle-profile-detail">Email</label>
            <input type="email" id="email" name="email" class="data-item-profile-detail"
                value="<?= escape($user['email']) ?>" required style="width: 100%;" />

            <label for="password" class="subtitle-profile-detail">Password</label>
            <input type="password" id="password" name="password" class="data-item-profile-detail"
                style="width: 100%;" placeholder="Leave blank to keep current password" />

            <button type="submit" class="edit-button-profile">Save Changes</button>
        </form>
    </main>
</body>

<?php include __DIR__ . '/../components/footer.php'; ?>
