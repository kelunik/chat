<?php

require __DIR__ . "/config/config.php";

const UI_CSS_FILES = "vendor/highlight/docco.css:font-awesome.min.css:common.css:settings.css:layout.css:room.css:chat_message.css:starred_messages.css:user.css:trello.css:message_card.css:mobile.css";
const UI_JS_FILES = "autocomplete.js:longpress.js:Util.js:DataHandler.js:TimeUpdater.js:Rooms.js:Messages.js:ActivityObserver.js:TemplateManager.js:Formatter.js:NotificationCenter.js:Message.js:Room.js:Input.js:chat.js:main.js";
const UI_JS_FILES_EXTERNAL = "vendor/favico.min.js:vendor/handlebars.min.js:vendor/highlight.min.js:vendor/keymaster.min.js:vendor/moment.min.js:vendor/remarkable.min.js:vendor/issue-linker.min.js:helpers.js";

define("DEPLOY_AUTHORITY", DEPLOY_DOMAIN . (DEPLOY_PORT === 80 || DEPLOY_PORT === 443 ? "" : ":" . DEPLOY_PORT));
define("DEPLOY_URL", (DEPLOY_HTTPS ? "https" : "http") . "://" . DEPLOY_AUTHORITY);

define("TEMPLATE_DIR", __DIR__ . DIRECTORY_SEPARATOR . "html" . DIRECTORY_SEPARATOR);
define("PROJECT_ROOT", __DIR__ . DIRECTORY_SEPARATOR);
