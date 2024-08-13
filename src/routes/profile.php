<?php
include __DIR__ . '/../components/header.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login");
    exit();
}

$db = getDbConnection();
$query = "SELECT user_id, username, user_phone, email FROM users WHERE user_id = $1";
$result = pg_query_params($db, $query, [$user_id]);

if (!$result) {
    die('Error fetching user data');
}

$user = pg_fetch_assoc($result);

function escape($value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<head>
    <title>Profile - Chautari</title>
    <link rel="stylesheet" href="../assets/css/default.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/sign.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>

<body>
    <main style="max-width: 550px; margin: 0 auto;">
        <h1 style="font-size: 2.3rem; letter-spacing: normal;  margin-top: 50px;">Profile</h1>

        <div style="display:block; border: 1px solid lightgrey; border-radius: 8px; padding: 30px; margin-bottom: 4rem; background-color: #fafafa;">
            <?php if (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
                <p class="signupSuccessMessage" style="text-align: center;">Profile updated successfully!</p>
            <?php endif; ?>

            <div>
                <img src="/assets/images/default-avatar.png" alt="Profile Picture"
                    style="width: 150px; height: 150px; border-radius: 50%; margin: 0 auto; display: block; border: 1px solid lightgrey;"></p>
            </div>

            <p class="subtitle-profile-detail">Username</p>
            <p class="data-item-profile-detail"><?= escape($user['username']) ?></span></p>
            <p class="subtitle-profile-detail">Email</p>
            <p class="data-item-profile-detail"><?= escape($user['email']) ?></span></p>
            <p class="subtitle-profile-detail">Phone</p>
            <p class="data-item-profile-detail"><?= escape($user['user_phone']) ?></span></p>

            <br>
            <a href="/settings?username=<?= escape($user['username']) ?>" class="edit-button-profile">Edit Profile</a>
        </div>
    </main>
</body>

<?php include __DIR__ . '/../components/footer.php'; ?>
