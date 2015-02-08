<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
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

        document.addEventListener("submit", function (e) {
            var form = e.target;
            var input = document.createElement('input');
            input.type = "hidden";
            input.name = "csrf-token";
            input.value = window.csrfToken;
            form.appendChild(input);
        }, true);
    </script>

    <?php require TEMPLATE_DIR . "google_analytics.php"; ?>
</head>
<body>
<div id="page">
    <div id="nav">
        <picture>
            <source srcset="/img/logo_40x40.png, img/logo_40x40x2.png 2x">
            <img src="/img/logo_40x40.png" alt="logo">
        </picture>

        <h1>t@lkZone</h1>
    </div>

    <div id="content">
        <div id="content-fw">
            <?php if ($isAdmin): ?>
                <div>
                    You can't leave this room, because you're an owner of this room.<br>
                    Please change that first.
                </div>
            <?php else: ?>
                <form action="/rooms/<?= $room->id ?>/leave" method="post">
                    Do you really want to leave <b><?= htmlspecialchars($room->name) ?></b>?

                    <button type="submit">Leave</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
