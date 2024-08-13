<?php
if (isset($_SESSION['user_id'])) {
    header('Location: /explore');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - Chautari</title>
    <meta name="description" content="Struggling to discover fun and exciting events near you? Chautari brings the best local events tailored to you so you can join
            local happenings with ease.">
    <meta property="og:title" content="Chautari — Find out about events around you.">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico" sizes="any">

    <link rel="stylesheet" href="../assets/css/default.css">
    <link rel="stylesheet" href="../assets/css/sign.css">
</head>

<body class="centerBody">

    <main class="formCenter">

        <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] == 'wrong_email_format'): ?>
            <p class="signupFailMessage">Invalid Email format.</p>
        <?php endif; ?>

        <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] == 'username_taken'): ?>
            <p class="signupFailMessage">Username already taken. Please choose another one.</p>
        <?php endif; ?>

        <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] == 'wrong_username_format'): ?>
            <p class="signupFailMessage">Username must be at least 3 characters long and unique.</p>
        <?php endif; ?>

        <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] == 'wrong_password_length'): ?>
            <p class="signupFailMessage">Password must be at least 8 characters long.</p>
        <?php endif; ?>

        <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] == 'wrong_phone_format'): ?>
            <p class="signupFailMessage">Phone number must start with 98 or 97 and be exactly 10 digits long.</p>
        <?php endif; ?>

        <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] == 'duplicate_phone'): ?>
            <p class="signupFailMessage">Sign up with another phone number.</p>
        <?php endif; ?>

        <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] == 'generic'): ?>
            <p class="signupFailMessage">Sorry, something went wrong. Please try again later.</p>
        <?php endif; ?>

        <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] == 'email_fail'): ?>
            <p class="signupFailMessage">Please use a different email.</p>
        <?php endif; ?>

        <a href="/" target="_self"><img class="smallImageInForm" src="../assets/images/logo-32.svg" alt="" srcset=""></a>

        <h1>Sign up</h1>

        <p class="snippetSignInfo">Already have an account? <a href="./login">Log in</a></p>

        <div class="formbody">
            <form action="/signup" method="post" onsubmit="return validateForm()">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required inputmode="text">
                <span id="usernameError" class="error"></span>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required inputmode="email">
                <span id="emailError" class="error"></span>

                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" required pattern="^(98|97)\d{8}$" maxlength="10" inputmode="numeric">
                <span id="phoneNumberError" class="error"></span>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required inputmode="password">
                <span id="passwordError" class="error"></span>

                <span id="passNoMatch"></span>
                <style>
                    #passNoMatch,
                    .error {
                        color: #d42f2f;
                    }
                </style>

                <p class="snippetSignInfo"><small><b>Note:</b><br>
                        If you forget your login credentials, we cannot recover or reset them. Please store them
                        securely.</small></p>

                <br>
                <button type="submit">Sign up</button>
                <br>

                <p class="snippetSignInfo"><small>By using this service, you agree to our <a href="/privacy">Privacy
                            Policy</a>,
                        <a href="/terms">Terms and Conditions</a>, and <a href="/dpa">Data Protection
                            Agreement</a>, which outline how we handle your data and the rules governing usage.</small>
                </p>

            </form>
        </div>
    </main>

    <script src="../assets/js/signup_validation.js"></script>
</body>

</html>