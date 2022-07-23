<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">

    <title>Whoops!</title>

    <style type="text/css">
        :root {
            --main-bg-color: #fff;
            --main-text-color: #555;
            --dark-text-color: #222;
            --light-text-color: #c7c7c7;
            --brand-primary-color: #E06E3F;
            --light-bg-color: #ededee;
            --dark-bg-color: #404040;
        }

        body {
            height: 100%;
            background: var(--main-bg-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            color: var(--main-text-color);
            font-weight: 300;
            margin: 0;
            padding: 0;
        }
        h1 {
            font-weight: lighter;
            letter-spacing: 0.8;
            font-size: 3rem;
            color: var(--dark-text-color);
            margin: 0;
        }
        h1.headline {
            margin-top: 20%;
            font-size: 5rem;
        }
        .text-center {
            text-align: center;
        }
        p.lead {
            font-size: 1.6rem;
        }
        .container {
            max-width: 75rem;
            margin: 0 auto;
            padding: 1rem;
        }
    </style>
</head>
<body>

    <div class="container text-center">

        <h1 class="headline"><?= setting('Site.siteName') ?></h1>

        <p class="lead">The site is temporarily offline. Please check back a little bit later.</p>

    </div>

</body>

</html>
