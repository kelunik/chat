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
    <div class="ac-header" id="header">
        <a class="ac-header-button ac-header-app" href="/">
            <img class="ac-header-app-logo" src="/img/logo_40x40x2.png" width="40" height="40">
        </a>

        <span class="ac-header-sep"></span>

        <span class="ac-header-label" id="current-user"><?= htmlspecialchars($session->name) ?></span>

        <form class="ac-header-button" id="logout" action="/logout" method="POST">
            <button type="submit" class="button-no-style" title="logout">
                <i class="fa fa-power-off"></i>
            </button>
        </form>
    </div>

    <div id="content">
        <div id="content-fw"></div>
    </div>
</div>
<script>var data = <?= json_encode($rooms) ?>;</script>
<script src="/js/room_overview_bundle.js"></script>
</body>
</html>
