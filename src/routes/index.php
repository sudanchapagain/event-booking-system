<?php include __DIR__ . '/../components/header.php'; ?>

<head>
    <title>Home - Chautari</title>

    <link rel="stylesheet" href="../assets/css/default.css">
    <link rel="stylesheet" href="../assets/css/index.css">

    <style>
        nav ul li:nth-child(1) a {
            background-color: #f0f0f0;
            padding: 8px 15px;
            border-radius: 20px;
        }
    </style>
</head>

<body>
    <main>
        <h1>
            Struggling to discover fun and exciting events near you?
        </h1>

        <p class="description">
            Chautari brings the best local events tailored to you so you can join local happenings with ease.
        </p>

        <div class="cta">
            <a class="primary-button" href="/explore">
                Discover events near you

                <span class="button-arrow">
                    <svg
                        width="20"
                        height="20"
                        fill="none"
                        viewBox="0 0 24 24"
                        class="ml-1">
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M13.75 6.75L19.25 12L13.75 17.25">
                        </path>
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M19 12H4.75">
                        </path>
                    </svg>
                </span>
            </a>

            <a class="secondary-button" href="/about">
                Learn more
            </a>
        </div>

        <div class="advert-video-wrapper">
            <img
                class="advert-video"
                src="../assets/images/placeholder-video.png"
                alt="video showcasing the product">
        </div>

        <hr>
    </main>
</body>

<?php include __DIR__ . '/../components/footer.php'; ?>