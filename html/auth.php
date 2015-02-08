<!DOCTYPE html>
<html manifest="/manifest.appcache">
<head lang="en">
    <meta charset="UTF-8">
    <title>t@lkZone</title>

    <?php require TEMPLATE_DIR . "head_meta.php"; ?>

    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Pacifico|Lato">
    <link rel="stylesheet" href="/css/font-awesome.min.css">

    <style>
        * {
            -webkit-text-size-adjust: none;
            -ms-text-size-adjust: none;
            text-size-adjust: none;
        }

        html, body {
            font-size: 15px;
            font-family: Lato, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: #fff;
        }

        #page {
            min-height: 100vh;
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-flex-flow: column;
            -ms-flex-flow: column;
            flex-flow: column;
        }

        a {
            font-weight: bold;
            text-decoration: none;
            color: rgba(0, 100, 200, .85);
        }

        a:hover {
            text-decoration: underline;
            color: rgb(0, 100, 200);
        }

        #nav {
            padding: 15px 0;
            background: #eee;
            text-align: center;
            line-height: 60px;
            border-bottom: 1px solid rgba(0, 0, 0, .05);
            -webkit-box-flex: 0;
            -webkit-flex: 0 0 auto;
            -ms-flex: 0 0 auto;
            flex: 0 0 auto;
        }

        #nav img {
            vertical-align: middle;
            display: inline-block;
        }

        h1 {
            font-family: 'Pacifico', sans-serif;
            font-weight: normal;
            font-size: 26px;
            line-height: 60px;
            margin: 0 0 0 20px;
            padding: 0;
            vertical-align: middle;
            display: inline-block;
            color: #333;
        }

        #content {
            padding: 20px;
            -webkit-box-flex: 2;
            -webkit-flex: 2;
            -ms-flex: 2;
            flex: 2;
        }

        #content-inner {
            max-width: 1000px;
            margin: 0 auto;
        }

        h2 {
            line-height: 40px;
            font-size: 22px;
            margin: 0 0 10px 0;
        }

        #login {
            text-decoration: none;
            font-weight: bold;
            color: #fff;
            margin: 25px 0 5px 0;
            padding: 20px;
            display: inline-block;
            background: rgba(0, 100, 200, .85);
            border-radius: 3px;
            line-height: 20px;
            border: 1px solid #005DBA;
            border-bottom: 3px solid #005DBA;
            position: relative;
        }

        #login:active,
        .pending {
            /* yeah, we need !important unfortunately */
            -webkit-transform: translateY(2px) !important;
            -ms-transform: translateY(2px) !important;
            transform: translateY(2px) !important;
            border-bottom: 1px solid #005DBA !important;
        }

        .pending {
            opacity: .6;
        }

        #logout-container {
            text-align: center;
            line-height: 0;
            margin-top: 20px;
        }

        #logout-notice {
            padding: 20px 20px;
            margin: 0 auto;
            background-color: #FFF3AE;
            border: 1px solid rgba(0, 0, 0, .1);
            border-radius: 3px 3px;
            display: none;
            color: #333;
            box-shadow: 0 0 8px rgba(0, 0, 0, .05);
        }

        .fa-github {
            font-size: 20px;
            line-height: 14px;
        }

        #footer {
            background: rgba(0, 0, 0, .05);
            border-top: 1px solid rgba(0, 0, 0, .1);
            padding: 10px;
            font-size: 13px;
            color: #666;
            font-weight: bold;
            text-align: center;
        }

        #top {
            background: rgba(0, 100, 200, .85);
            border-bottom: 1px solid rgba(0, 0, 0, .2);
            line-height: 35px;
            color: #eee;
            font-size: 13px;
            font-weight: bold;
            padding: 0 20px;
        }

        #top-inner {
            max-width: 1000px;
            margin: 0 auto;
        }

        .flex {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-pack: justify;
            -webkit-justify-content: space-between;
            -ms-flex-pack: justify;
            justify-content: space-between;
        }

        .flex-left: {
            -webkit-align-self: flex-start;
            -ms-flex-item-align: start;
            align-self: flex-start;
        }

        .flex-right {
            -webkit-align-self: flex-end;
            -ms-flex-item-align: end;
            align-self: flex-end;
        }

        #features {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-flex-wrap: wrap;
            -ms-flex-wrap: wrap;
            flex-wrap: wrap;
        }

        .feature {
            border: 0px solid rgba(0, 0, 0, .01);
            -webkit-box-flex: 2;
            -webkit-flex-grow: 2;
            -ms-flex-positive: 2;
            flex-grow: 2;
            width: 0;
            margin: 10px;
            padding: 10px 10px 10px 70px;
            position: relative;
            min-height: 70px;
            color: #444;
            min-width: 220px;
        }

        .feature .fa {
            position: absolute;
            left: 5px;
            top: 20px;
            color: #fff;
            background: #D12115;
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            border-radius: 50%;
        }

        .feature h2 {
            color: #333;
            margin: 0 0 4px 0;
            padding: 0;
            font-size: 20px;
        }

        .text-center {
            text-align: center;
        }
    </style>

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
</head>
<body>
<div id="page">
    <div id="top">
        <div id="top-inner" class="flex">
            <div class="flex-left"></div>
            <div class="flex-right">version <?= GIT_COMMIT_ID ?></div>
        </div>
    </div>

    <div id="nav">
        <picture>
            <source srcset="/img/logo_60x60.png, img/logo_60x60x2.png 2x">
            <img src="/img/logo_60x60.png" alt="logo">
        </picture>

        <h1>t@lkZone</h1>
    </div>

    <div id="logout-container">
        <div id="logout-notice">Your session has been terminated.</div>
    </div>

    <div id="content">
        <div id="content-inner">
            <div id="features">
                <div class="feature">
                    <i class="fa fa-font fa-2x"></i>

                    <h2>Markdown</h2>
                    Write your messages using
                    <a href="http://daringfireball.net/projects/markdown/" target="_blank">markdown</a>.
                </div>

                <div class="feature">
                    <i class="fa fa-reply fa-2x"></i>

                    <h2>Direct Reply</h2>
                    Reply directly to messages so others can follow your conversation.
                </div>

                <div class="feature">
                    <i class="fa fa-star fa-2x"></i>

                    <h2>Stars</h2>
                    Star messages so they get more attention.
                </div>
            </div>

            <div class="text-center">
                <a href="/login/github" id="login">
                    <i class="fa fa-github"></i>&nbsp;&nbsp;&nbsp;<span class="text">Sign in with GitHub</span>
                </a>
            </div>

            <script>
                document.getElementById("login").addEventListener("click", function (e) {
                    this.querySelector("span").innerHTML = "Signing in&hellip;";
                    this.classList.add("pending");
                });
            </script>
        </div>
    </div>

    <div id="footer">
        © <?= date('Y') ?> <a href="https://github.com/amphp">amphp</a> and <a
            href="https://github.com/rdlowrey/amp-chat/graphs/contributors">contributors</a>
    </div>
</div>

<script>
    if (window.sessionStorage) {
        var logout = window.sessionStorage.getItem("autologout");

        if (logout) {
            window.sessionStorage.setItem("autologout", "1");
            document.getElementById("logout-notice").style.display = "inline-block";
        }
    }
</script>

</body>
</html>
