<!doctype html>
<html lang="en">
<head>
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <meta charset="utf-8">

    <?php require TEMPLATE_DIR . "head_meta.php"; ?>

    <script>
        var trelloKey = "<?=TRELLO_KEY?>";

        var user = {
            id: <?= (int) $session->id ?>,
            name: "<?= htmlspecialchars($session->name) ?>",
            avatar: "<?= htmlspecialchars($session->avatar) ?>"
        };

        var config = {
            name: "<?= htmlspecialchars(APP_NAME) ?>",
            host: "<?= htmlspecialchars(DEPLOY_URL) ?>",
            websocketUrl: "<?=DEPLOY_HTTPS ? "wss" : "ws"?>://<?=DEPLOY_AUTHORITY?>/chat"
        };

        window.csrfToken = "<?=htmlspecialchars($session->csrfToken)?>";
    </script>

    <?php require TEMPLATE_DIR . "google_analytics.php"; ?>

    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Lato:400,700|Source+Code+Pro">
    <link rel="stylesheet" href="/css/all.min.css?v=<?= DEVELOPMENT ? time() : CSS_VERSION ?>">
</head>
<body>
<div id="page">
    <div class="ac-header" id="header">
        <label class="ac-header-button mobile-only" id="mobile-menu-switch" for="mobile-menu-switch-checkbox">
            <i class="fa fa-fw fa-navicon"></i>
        </label>

        <a class="ac-header-button ac-header-app" href="/">
            <img class="ac-header-app-logo" src="/img/logo_40x40x2.png" width="40" height="40">
        </a>

        <span class="ac-header-button ac-header-title" id="header-title"></span>

        <span class="ac-header-label ac-header-shrinkable" id="header-users"></span>

        <span class="ac-header-sep"></span>

        <span class="ac-header-label" id="current-user"><?= htmlspecialchars($session->name) ?></span>

        <span class="ac-header-button" id="ping-clear-all" title="clear all pings">
            <i class="fa fa-magic"></i>
        </span>

        <form class="ac-header-button" id="logout" action="/logout" method="POST">
            <button type="submit" class="button-no-style" title="logout">
                <i class="fa fa-power-off"></i>
            </button>
        </form>

        <form class="ac-header-label" action="/search" method="GET" id="search">
            <div class="ac-header-search">
                <input class="ac-header-search-text" type="search" role="search" name="q"
                       placeholder="search messages…" autocomplete="off">
                <button class="ac-header-search-button" type="submit"><i class="fa fa-search fa-fw"></i></button>
            </div>
        </form>
    </div>

    <div id="chat">
        <input class="no-display" id="mobile-menu-switch-checkbox" type="checkbox">

        <div id="left-col">
            <div class="sidebar-title">
                <div id="room-search">
                    <i class="fa fa-search"></i>

                    <input type="text" id="room-search-input">
                </div>

                Rooms
            </div>

            <div id="room-tabs"></div>
        </div>

        <div id="main-col">
            <div id="load-error">
                <div class="load-error-hide">
                    <div class="loader"></div>
                </div>

                <i class="fa fa-exclamation-triangle" style="font-size: 100px;"></i><br><br>
                Sorry, our scripts don't seem to load.<br>
                Please try refreshing the page.
            </div>

            <div id="rooms">

            </div>

            <div id="above_input">
                <div id="autocomplete">

                </div>

                <div id="new-messages-indicator">
                    <div id="new-messages">
                        <i class="fa fa-inbox"></i> scroll down to see new messages
                    </div>
                </div>
            </div>

            <div id="input-holder">
                <textarea id="input" placeholder="Type here, use markdown to format your text&hellip;"></textarea>
            </div>
        </div>

        <div id="right-col">
            <div id="stars">

            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="/js/all.min.js?v=<?= DEVELOPMENT ? time() : JS_VERSION ?>"></script>
</body>
</html>
