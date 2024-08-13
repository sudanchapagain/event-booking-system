<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Struggling to discover fun and exciting events near you? Chautari brings the best local events tailored to you so you can join
            local happenings with ease.">
    <meta property="og:title" content="Chautari — Find out about events around you.">

    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico" sizes="any">
    <link rel="stylesheet" href="../assets/css/header.css">
    <script src="../assets/js/profile-icon-loader.js"></script>
    <script src="../assets/js/hamburger.js" defer></script>
</head>

<body>
    <div class="header-wrapper">
        <header>
            <div class="logo-header">
                <a href="/">
                    <div class="logo-header-inner">
                        <img src="../assets/images/logo-32.svg" alt="logo">
                        <p>Chautari</p>
                    </div>
                </a>
            </div>

            <div>
                <nav>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="/about">About</a></li>
                        <li><a href="/contact">Contact</a></li>
                    </ul>
                </nav>
            </div>

            <div>
                <?php
                if (!isset($_SESSION['user_id'])) {
                    echo '<a class="nav-button-secondary" href="/login">Log in</a>
                          <a class="nav-button-primary" href="/signup">Sign up today</a>';
                } else {
                    $user_id = $_SESSION['user_id'];
                    $db = getDbConnection();
                    $query = "SELECT username, email, is_organizer, is_admin FROM users WHERE user_id = $1";
                    $result = pg_query_params($db, $query, [$user_id]);

                    if (!$result) {
                        die('Error fetching user data');
                    }

                    $user = pg_fetch_assoc($result);
                    $is_organizer = $user['is_organizer'] ?? 'f';
                    $is_admin = $user['is_admin'] ?? 'f';
                    $username = $user['username'];
                    if ($is_admin === 't') {
                        $userType = 'Admin';
                    } else if ($is_organizer === 't') {
                        $userType = 'Organizer';
                    } else {
                        $userType = 'User';
                    }

                    $profileImage = $user['profile_picture'] ?? null;
                    if (!$profileImage) {
                        $profileImage = '/assets/images/fallback.svg';
                    }

                    echo '<div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="position: relative; width: 2.5rem; height: 2.5rem; overflow: hidden; background-color: #f3f4f6; border-radius: 9999px;">
                            <img src="' . htmlspecialchars($profileImage) . '" alt="Profile Picture" id="profilePic" style="margin: 5px -3px 0 0">
                        </div>
                        <div class="profile-info" style="margin-top: 5px">
                            <p><b>' . htmlspecialchars($username) . '</b></p>
                            <p style="color: grey">' . htmlspecialchars($userType) . '</p>
                            </div>
                        </div>

                        <div id="profileDropdown" class="ic-profile-dropdown hidden">
                            <ul class="ic-dropdown-list" style="display: block">
                                <li><a href="/new" class="ic-dropdown-item">New event</a></li>
                                <li><a href="/profile" class="ic-dropdown-item">Profile</a></li>
                                <li><a href="/settings" class="ic-dropdown-item">Settings</a></li>
                                <li><a href="/logout" class="ic-dropdown-item">Log out</a></li>';
                    if ($is_organizer === 't' || $is_admin === 't') {
                        echo '<li><a href="/dashboard" class="ic-dropdown-item">Dashboard</a></li>';
                    };
                    echo '</ul></div></div>';
                }
                ?>
            </div>

            <div class="hamburger" onclick="toggleOverlay()">
                <svg class="nav-toggle" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M4 6H20M4 12H20M4 18H20"></path>
                </svg>
            </div>
            <div id="overlay" class="overlay">
                <div class="overlay-content">
                    <h2>Menu</h2>
                    <hr>
                    <ul>
                        <li><a href="/about">About</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/privacy">Privacy</a></li>
                        <hr>
                        <li><a href="/login">Log in</a></li>
                        <li><a href="/signup">Sign up</a></li>
                        <hr>
                    </ul>
                    <button onclick="toggleOverlay()">Close</button>
                </div>
            </div>
        </header>
    </div>
</body>
