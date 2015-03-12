<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(APP_NAME) ?></title>

    <?php require TEMPLATE_DIR . "head_meta.php"; ?>

    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Pacifico|Lato">
    <link rel="stylesheet" href="/css/all.min.css">

    <style>
        * {
            -webkit-text-size-adjust: none;
            -ms-text-size-adjust: none;
            text-size-adjust: none;
        }

        html, body {
            font-size: 13px;
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

        #content {
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
            margin: -35px 0 50px 0;
            padding: 20px;
            display: inline-block;
            background: rgba(0, 100, 200, 1);
            border-radius: 3px;
            line-height: 20px;
            border: 1px solid #0052a3;
            border-bottom: 3px solid #0052a3;
            position: relative;
        }

        #login:hover {
            border-color: #004080;
        }

        #login:active,
        .pending {
            /* yeah, we need !important unfortunately */
            -webkit-transform: translateY(2px) !important;
            -ms-transform: translateY(2px) !important;
            transform: translateY(2px) !important;
            border-bottom: 1px solid #004080 !important;
            margin-bottom: 52px !important;
        }

        .pending {
            background: rgb(55, 155, 255) !important;
        }

        #logout-container {
            text-align: center;
            line-height: 0;
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
            color: #444;
            font-weight: bold;
            text-align: center;
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

        h1 {
            font-family: 'Pacifico', sans-serif;
            font-weight: normal;
            line-height: 50px;
            padding: 0;
            vertical-align: middle;
            display: inline-block;
            /* TODO: Why do we need this? */
            margin: -3px 80px 0 40px;
            color: #fafafa;
            font-size: 50px;
            text-shadow: 0 0 15px rgba(0, 0, 0, .2);
        }

        .ac-header {
            display: block;
            background-color: rgba(0, 0, 0, .05);
            border-bottom: 1px solid rgba(0, 0, 0, .05);
            z-index: 2;
        }

        .ac-header-inner {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
        }

        .cover {
            background: url(/img/login-cover.jpg) center no-repeat;
            background-size: cover;
            height: 200px;
            margin-top: -50px;
            display: flex;
            padding-top: 50px;
        }

        .cover-content {
            margin: auto;
        }

        .logo {
            vertical-align: middle;
        }

        @media only screen and (max-width: 600px) {
            .logo {
                width: 100px;
                height: 100px;
                padding-bottom: 30px;
            }

            h1 {
                display: none;
            }

            #login {
                margin-bottom: 15px;
            }

            #login:active,
            .pending {
                margin-bottom: 17px !important;
            }

            #features {
                margin-bottom: 25px;
            }
        }
    </style>

    <?php require TEMPLATE_DIR . "google_analytics.php"; ?>
</head>
<body>
<div id="page">
    <div class="ac-header" id="header">
        <div class="ac-header-inner">
            <span class="ac-header-sep"></span>

            <span class="ac-header-label">
                version <?= GIT_COMMIT_ID ?>
            </span>
        </div>
    </div>

    <div id="logout-container">
        <div id="logout-notice">Your session has been terminated.</div>
    </div>

    <div class="cover">
        <div class="cover-content">
            <h1><?= htmlspecialchars(APP_NAME) ?></h1>
            <img class="logo" src="/img/logo.png" width="110" height="110">
        </div>
    </div>

    <div id="content">
        <div id="content-inner">
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
