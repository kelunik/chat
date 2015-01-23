<?php

/**
 * This file contains all deployment specific configuration.
 * Please rename it to config.php and choose appropriate options.
 */

# Debug
# -----

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


# Deployment
# ----------

# currently this has to be false, because there isn't any way
# to specify a certification file in this config
const DEPLOY_HTTPS = false;
const DEPLOY_DOMAIN = "localhost";
const DEPLOY_PORT = 8080;


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
