<?php

/**
 * This file contains all deployment specific configuration.
 * Please rename it to config.php and choose appropriate options.
 */

# Debug
# -----

const DEVELOPMENT = true;
# const DEBUG = false;
# const MYSQL_DEBUG = true;

# Mysql
# -----

const DB_HOST = "localhost";
const DB_USER = "";
const DB_PASS = "";
const DB_DB = "";


# Redis
# -----

# set this to null if you don't want to use a password
const REDIS_PASSWORD = "secret";

# see https://github.com/settings/applications for those options
const GITHUB_CLIENT_ID = "";
const GITHUB_CLIENT_SECRET = "";

# see https://trello.com/app-key
const TRELLO_KEY = "";


# Deployment
# ----------

# Be sure to set DEPLOY_HTTPS_CERT to your .pem file
# if you want to enable TLS
const DEPLOY_HTTPS = false;
const DEPLOY_HTTPS_CERT = null;
const DEPLOY_HTTPS_SUITES = "ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!3DES:!MD5:!PSK";
const DEPLOY_DOMAIN = "localhost";
const DEPLOY_PORT = 80;


# Mails
# -----

# specify SMTP settings for email notifications
# currently, ssl is always used and there's no option to disable it
# feel free to add some PR that introduces such an option
const MAIL_SERVER = "";
const MAIL_PORT = 465;
const MAIL_USER = "";
const MAIL_PASS = "";

# Tracking
# --------
const GA_CODE = "";
