# amp-chat

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

#### Java & Closure Compiler

Currently, we use Google's [Closure Compiler](https://developers.google.com/closure/compiler/) which requires Java.
Please ensure that `java` is in your path.

> Note: Currently, Javascript compression is always disabled,
> so there's no need to have Java installed, this may change at any time.

#### NodeJS & Handlebars

Currently, we need node to precompile handlebars' templates, so they can be included into `all.min.js`.

> Note: Package name may differ depending on distribution.

```bash
sudo apt-get install node
npm install handlebars -g
```

[Here's more information](http://handlebarsjs.com/precompilation.html) regarding handlebars precompilation.

#### Redis

amp-chat relies heavily on [redis](http://redis.io). I'll need to install it and start it with the following command:

```bash
redis-server config/redis.conf
```

## Starting the server

Current development recommendation is to start the server using the following command:

```bash
php7 vendor/amphp/aerys/bin/aerys -c app.php
```

> Note: We're using `php7` here, because you may have another version of PHP as `php`,
> because PHP 7 is far away from being released.
