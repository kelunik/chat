<!doctype html>
<html lang="en">
<head>
    <title>Settings – t@lk</title>
    <meta charset="utf-8">

    <?php require TEMPLATE_DIR . "head_meta.php"; ?>

    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Lato:400,700|Source+Code+Pro">
    <link rel="stylesheet" href="/css/all.min.css?v=<?= CSS_VERSION ?>">

    <script>
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

    <script>
        (function (i, s, o, g, r, a, m) {
            i['GoogleAnalyticsObject'] = r;
            i[r] = i[r] || function () {
                (i[r].q = i[r].q || []).push(arguments)
            }, i[r].l = 1 * new Date();
            a = s.createElement(o),
                m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

        ga('create', '<?= GA_CODE ?>', 'auto');
        ga('send', 'pageview');
    </script>
</head>
<body>
<div id="settings">
    <form id="notifications" method="post" action="/settings">
        <h2>Notification Settings</h2>

        <label>
            notification frequency
            <select name="mail_notifications">
                <?php

                $opts = [
                    'never' => 'never',
                    'default' => '24h'
                ];

                ?>

                <?php foreach ($opts as $opt => $val): ?>
                    <option
                        value="<?= htmlspecialchars($opt) ?>" <?= $opt === $settings["MAIL_NOTIFICATIONS"] ? "selected" : "" ?>><?= htmlspecialchars($val) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div>
            <button type="submit">save</button>
        </div>
    </form>
</div>
</body>
</html>
