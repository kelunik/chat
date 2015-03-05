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
        <div id="content-fw">
            <form id="room-search-full">
                <input type="text" id="room-search-input-full" name="q" placeholder="Search for rooms…"
                       value="<?= htmlspecialchars($query) ?>">
                <button type="submit"></button>
            </form>

            <div id="room-overview">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-card-title">
                            <a href="/rooms/<?= $room->id ?>">
                                <?= htmlspecialchars($room->name) ?>
                            </a>
                        </div>

                        <div class="room-card-desc">
                            <?php if (!empty($room->description)): ?>
                                <?= htmlspecialchars(preg_replace("~(\\.\\s*)+~", ". ", str_replace("\n", ".", $room->description))) ?>
                            <?php else: ?>
                                <i style="color: #999;">no description</i>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- placeholders to cards in last line don't grow larger than other cards -->
                <div class="room-card"></div>
                <div class="room-card"></div>
                <div class="room-card"></div>
            </div>

            <?php if (sizeof($rooms) === 0): ?>
                <div style="padding: 10px; color: #777;">
                    <i>No results...</i>
                </div>
            <?php endif; ?>

            <div class="info">
                Couldn't find a room that suits you?&nbsp;&nbsp;
                <a href="/rooms/new">Create a new one.</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
