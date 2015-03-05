<!doctype html>
<html lang="en">
<head>
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <meta charset="utf-8">

    <?php require TEMPLATE_DIR . "head_meta.php"; ?>

    <link rel="stylesheet" href="/css/all.min.css?v=<?= CSS_VERSION ?>">

    <?php require TEMPLATE_DIR . "google_analytics.php"; ?>

    <script>
        var trelloKey = "<?=TRELLO_KEY?>";

        var user = {
            id: <?= (int) ($session->id ?? 0) ?>,
            name: "<?= htmlspecialchars($session->name ?? "") ?>",
            avatar: "<?= htmlspecialchars($session->avatar ?? 0) ?>"
        };

        var config = {
            name: "<?= htmlspecialchars(APP_NAME) ?>",
            host: "<?= htmlspecialchars(DEPLOY_URL) ?>",
            websocketUrl: "<?=DEPLOY_HTTPS ? "wss" : "ws"?>://<?=DEPLOY_AUTHORITY?>/chat"
        };

        window.csrfToken = "<?=htmlspecialchars($session->csrfToken ?? "")?>";
    </script>
</head>
<body>
<div id="transcript"></div>
</body>
<script>var data = <?= json_encode($messages) ?>;</script>
<script src="/js/transcript_bundle.js"></script>
</html>
