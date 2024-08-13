<?php include __DIR__ . '/../handlers/logout_handler.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Struggling to discover fun and exciting events near you? 
        Chautari brings the best local events tailored to you so you can join local happenings with ease.">

    <meta property="og:title" content="Chautari — Find out about events around you.">

    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico" sizes="any">
    <title>logout - Chautari</title>
    <link rel="stylesheet" href="../assets/css/default.css">
</head>

<body>

    <p id="initial-message">Logging out...</p>

    <h1 id="thank-you-message">Thank you for using Chautari.</h1>
    <h1 id="logged-out-message">You have been logged out.</h1>
    <p id="redirect-message">Redirecting you to the home page...</p>

    <script>
        setTimeout(() => {
            document.getElementById('initial-message').classList.add('show');
        }, 0);

        setTimeout(() => {
            document.getElementById('thank-you-message').classList.add('show');
            document.getElementById('initial-message').classList.remove('show');
        }, 1000);

        setTimeout(() => {
            document.getElementById('logged-out-message').classList.add('show');
            document.getElementById('redirect-message').classList.add('show');
        }, 2500);

        setTimeout(() => {
            window.location.href = '/';
        }, 3500);
    </script>

    <style>
        body {
            text-align: center;
            margin-top: 5rem;
            font-family: Arial, sans-serif;
        }

        #logout-message,
        #thank-you-message,
        #logged-out-message,
        #redirect-message {
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        #thank-you-message {
            font-size: 2rem;
            margin: 1rem 0;
        }

        #logged-out-message {
            font-size: 1.5rem;
            margin: 1rem 0;
        }

        #redirect-message {
            color: #666;
        }

        #initial-message {
            color: #d42f2f;
            font-weight: bold;
        }

        #logout-message.show,
        #thank-you-message.show,
        #logged-out-message.show,
        #redirect-message.show {
            opacity: 1;
        }
    </style>

</body>

</html>