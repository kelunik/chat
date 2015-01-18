<?php

require __DIR__ . "/config/config.php";

const UI_CSS_FILES = "vendor/highlight/railscasts.css:font-awesome.min.css:common.css:settings.css:layout.css:room.css:chat_message.css:starred_messages.css:user.css:lightbox.css";
const UI_JS_FILES = "handlebars.min.js:highlight.min.js:keymaster.min.js:autocomplete.js:moment.min.js:remarkable.min.js:issue-linker.min.js:helpers.js:DataHandler.js:Formatter.js:LightBox.js:MessageHandler.js:NotificationCenter.js:RoomHandler.js:TemplateManager.js:TimeUpdater.js:User.js:chat.js";

define("DEPLOY_AUTHORITY", DEPLOY_DOMAIN . (DEPLOY_PORT == 80 ? "" : ":" . DEPLOY_PORT));
define("DEPLOY_URL", (DEPLOY_HTTPS ? "https" : "http") . "://" . DEPLOY_AUTHORITY);

define("TEMPLATE_DIR", __DIR__ . DIRECTORY_SEPARATOR . "html" . DIRECTORY_SEPARATOR);
define("PROJECT_ROOT", __DIR__ . DIRECTORY_SEPARATOR);
