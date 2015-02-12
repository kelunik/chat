# amp-chat [![](https://img.shields.io/badge/amp--chat-join%20Two%20Crowns-blue.svg)](https://dev.kelunik.com)

## Installation

### Basic Repository

If you're not familiar with composer, please read their [introduction](https://getcomposer.org/doc/00-intro.md).

```bash
git clone git@github.com/rdlowrey/amp-chat
cd amp-chat
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
We install `hbsfy`, `handlebars` and `mocha` globally, because we need them in `/html` and `/root/js` or for testing.

> Note: Package name may differ depending on distribution.
> Make sure, you create a symlink from nodejs to node
> if your distro is using nodejs as binary name.

```bash
sudo apt-get install node
cd root/js
sudo npm install
sudo npm install -g hbsfy handlebars mocha
```

#### Redis

amp-chat relies heavily on [redis](http://redis.io). I'll need to install it and start it with the following command:

```bash
redis-server config/redis.conf
```

## Starting the server

Current development recommendation is to start the server using the following command:

```bash
php7 vendor/bin/aerys -c app.php
```

> Note: We're using `php7` here, because you may have another version of PHP as `php`,
> because PHP 7 is far away from being released.
