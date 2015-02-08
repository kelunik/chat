<?php if (defined("GA_CODE") && !empty(GA_CODE)): ?>
    <script>
        (function (i, s, o, g, r, a, m) {
            i['GoogleAnalyticsObject'] = r;
            i[r] = i[r] || function () {
                (i[r].q = i[r].q || []).push(arguments)
            };
            i[r].l = 1 * new Date();
            a = s.createElement(o);
            m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

        ga('create', '<?= GA_CODE ?>', 'auto');
        ga('send', 'pageview');
    </script>
<?php else: ?>
    <script>
        // prevent undefined function errors and log for debugging
        var ga = function () {
            console.log("Google Analytics", arguments);
        };
    </script>
<?php endif; ?>
