<!doctype html>
<html>
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
        <picture>
            <source srcset="/img/logo_40x40.png, img/logo_40x40x2.png 2x">
            <img src="/img/logo_40x40.png" alt="logo">
        </picture>

        <h1><?= htmlspecialchars(APP_NAME) ?></h1>
    </div>

    <?php
    $md = new Parsedown;
    $md->setMarkupEscaped(true);
    ?>

    <div id="content">
        <div id="content-fw">
            <div id="room-overview">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-card-title">
                            <span style="float: right; color: #777; font-size: 12px;">
                                <?php if ($room->users > 1 || $room->users == 0): ?>
                                    <?= $room->users ?>&nbsp;&nbsp;<i class="fa fa-users fa-fw"></i>
                                <?php else: ?>
                                    <?= $room->users ?>&nbsp;&nbsp;<i class="fa fa-user fa-fw"></i>
                                <?php endif; ?>
                            </span>

                            <a href="/rooms/<?= $room->id ?>">
                                <?= htmlspecialchars($room->name) ?>
                            </a>
                        </div>

                        <div class="room-card-desc">
                            <?= htmlspecialchars($room->description) /* $md->parse($room->description) */ ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="info">
                Couldn't find a room that suits you?&nbsp;&nbsp;
                <a href="/rooms/new">Create a new one.</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
