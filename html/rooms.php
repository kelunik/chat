<!doctype html>
<html lang="en">
<head>
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <meta charset="utf-8">

    <?php require TEMPLATE_DIR . "head_meta.php"; ?>

    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Lato:400,700|Source+Code+Pro|Pacifico">
    <link rel="stylesheet" href="/css/all.min.css?v=<?= CSS_VERSION ?>">

    <script>
        var user = {
            id: <?= (int) $session->id ?>,
            name: "<?= htmlspecialchars($session->name) ?>",
            avatar: "<?= htmlspecialchars($session->avatar) ?>"
        };

        window.csrfToken = "<?=htmlspecialchars($session->csrfToken)?>";
    </script>

    <?php require TEMPLATE_DIR . "google_analytics.php"; ?>
</head>
<body>
<div id="page">
    <div id="nav">
        <div id="nav-inner">
            <div class="flex-left">
                <picture>
                    <source srcset="/img/logo_40x40.png, img/logo_40x40x2.png 2x">
                    <img src="/img/logo_40x40.png" alt="logo" width="40" height="40">
                </picture>

                <h1><?= htmlspecialchars(APP_NAME) ?></h1>
            </div>

            <div class="flex-right">
                <?= htmlspecialchars($session->name) ?>
            </div>
        </div>
    </div>

    <div id="content">
        <div id="content-fw"></div>
    </div>
</div>
<script>var data = <?= json_encode($rooms) ?>;</script>
<script src="/js/room_overview_bundle.js"></script>
</body>
</html>
