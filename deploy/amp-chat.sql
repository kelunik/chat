SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `messages` (
  `id`      INT(11) NOT NULL,
  `roomId`  INT(11) NOT NULL,
  `userId`  INT(11) NOT NULL,
  `text`    TEXT    NOT NULL,
  `replyTo` INT(11) DEFAULT NULL,
  `edited`  INT(11) NOT NULL,
  `time`    INT(11) NOT NULL
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =476;

CREATE TABLE IF NOT EXISTS `message_stars` (
  `messageId` INT(11) NOT NULL,
  `userId`    INT(11) NOT NULL,
  `time`      INT(11) NOT NULL
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;

CREATE TABLE IF NOT EXISTS `pings` (
  `userId`    INT(11)    NOT NULL,
  `messageId` INT(11)    NOT NULL,
  `seen`      TINYINT(1) NOT NULL,
  `mailed`    TINYINT(1) NOT NULL
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;

CREATE TABLE IF NOT EXISTS `rooms` (
  `id`           INT(11)     NOT NULL,
  `name`         VARCHAR(30) NOT NULL,
  `description`  TEXT        NOT NULL,
  `creationTime` INT(11)     NOT NULL
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =5;

CREATE TABLE IF NOT EXISTS `room_users` (
  `roomId`     INT(11)                               NOT NULL,
  `userId`     INT(11)                               NOT NULL,
  `role`       ENUM('READER', 'WRITER', 'ADMIN', '') NOT NULL,
  `joinedTime` INT(11)                               NOT NULL
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;

CREATE TABLE IF NOT EXISTS `settings` (
  `key`     VARCHAR(30)  NOT NULL,
  `default` VARCHAR(100) NOT NULL
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;

CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT(11)      NOT NULL,
  `name`         VARCHAR(50)  NOT NULL,
  `mail`         VARCHAR(255) NOT NULL,
  `github_token` VARCHAR(40)  NOT NULL,
  `avatar_url`   TINYTEXT     NOT NULL,
  `lastActivity` INT(11)      NOT NULL
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =3;

CREATE TABLE IF NOT EXISTS `user_settings` (
  `userId` INT(11)      NOT NULL,
  `key`    VARCHAR(30)  NOT NULL,
  `value`  VARCHAR(100) NOT NULL
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;


ALTER TABLE `messages`
ADD PRIMARY KEY (`id`);

ALTER TABLE `message_stars`
ADD PRIMARY KEY (`messageId`, `userId`);

ALTER TABLE `pings`
ADD PRIMARY KEY (`userId`, `messageId`);

ALTER TABLE `rooms`
ADD PRIMARY KEY (`id`);

ALTER TABLE `room_users`
ADD PRIMARY KEY (`roomId`, `userId`);

ALTER TABLE `settings`
ADD PRIMARY KEY (`key`);

ALTER TABLE `users`
ADD PRIMARY KEY (`id`), ADD KEY `github_token` (`github_token`);

ALTER TABLE `user_settings`
ADD PRIMARY KEY (`userId`, `key`);


ALTER TABLE `messages`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT =1;
ALTER TABLE `rooms`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT =1;
ALTER TABLE `users`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT =1;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
