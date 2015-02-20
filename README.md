# amp-chat [![](https://img.shields.io/badge/amp--chat-join%20Two%20Crowns-blue.svg)](https://dev.kelunik.com)

## Installation

### Basic Repository

If you're not familiar with composer, please read [their introduction](https://getcomposer.org/doc/00-intro.md).

```bash
git clone git@github.com/kelunik/amp-chat && cd amp-chat
composer install
```

### Deployment Requirements

#### Configuration

First, have a look into the `config` directory.
There are two sample files that have to be duplicated, just remove the `.sample` from their filenames.

#### MySQL

You'll need an empty MySQL database. There's a SQL dump in `deploy/amp-chat.sql` which can be imported.
Later changes will have to be integrated manually, at least currently.

Be sure to add at least one room to the `rooms` table, otherwise you won't have much fun.

#### Node

You'll need a few node dependencies to create our frontend bundle.

> Note: Package name may differ depending on distribution.
> Make sure, you create a symlink from nodejs to node
> if your distro is using nodejs as binary name.

```bash
sudo apt-get install npm
npm install
```

#### Redis

amp-chat relies heavily on [redis](http://redis.io). You'll need to install it and start it with the following command:

```bash
redis-server config/redis.conf
```

#### Github OAuth

Register a new [GitHub application](https://github.com/settings/applications) and put the created "Client ID" and "Client Secret" into your `config/config.php` (`GITHUB_CLIENT_ID` and `GITHUB_CLIENT_SECRET`).

Configure the "Authorization callback URL" in your registered GitHub Application to your application's root URL and the suffix `/oauth/github`, e.g. `http://localhost:8080/oauth/github`.

## Starting the server

Make sure to configure the IP and PORT the chat is supposed to run on in config/config.php (`DEPLOY_DOMAIN`, `DEPLOY_PORT`).

Current development recommendation is to start the server using the following command:

```bash
php7 vendor/bin/aerys -c app.php
```

> Note: We're using `php7` here, because you may have another version of PHP as `php`,
> because PHP 7 is far away from being released.
