SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `messages` (
`id` int(11) NOT NULL,
  `roomId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `text` text NOT NULL,
  `replyTo` int(11) DEFAULT NULL,
  `edited` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=476 ;

CREATE TABLE IF NOT EXISTS `message_stars` (
  `messageId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pings` (
  `userId` int(11) NOT NULL,
  `messageId` int(11) NOT NULL,
  `seen` tinyint(1) NOT NULL,
  `mailed` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `rooms` (
`id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `creationTime` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

CREATE TABLE IF NOT EXISTS `room_users` (
  `roomId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `role` enum('READER','WRITER','ADMIN','') NOT NULL,
  `joinedTime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(30) NOT NULL,
  `default` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
`id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `github_token` varchar(40) NOT NULL,
  `avatar_url` tinytext NOT NULL,
  `lastActivity` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

CREATE TABLE IF NOT EXISTS `user_settings` (
  `userId` int(11) NOT NULL,
  `key` varchar(30) NOT NULL,
  `value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `messages`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `message_stars`
 ADD PRIMARY KEY (`messageId`,`userId`);

ALTER TABLE `pings`
 ADD PRIMARY KEY (`userId`,`messageId`);

ALTER TABLE `rooms`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `room_users`
 ADD PRIMARY KEY (`roomId`,`userId`);

ALTER TABLE `settings`
 ADD PRIMARY KEY (`key`);

ALTER TABLE `users`
 ADD PRIMARY KEY (`id`), ADD KEY `github_token` (`github_token`);

ALTER TABLE `user_settings`
 ADD PRIMARY KEY (`userId`,`key`);


ALTER TABLE `messages`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
ALTER TABLE `rooms`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
ALTER TABLE `users`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
