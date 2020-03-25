SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `log` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `setting` (
  `chatid` bigint(20) NOT NULL,
  `chattitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `chatname` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `mode` enum('start','stop','optin','optout') COLLATE utf8_bin NOT NULL DEFAULT 'start',
  `regex` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `404` tinyint(1) NOT NULL DEFAULT '0',
  `cmdadminonly` tinyint(1) NOT NULL DEFAULT '0',
  `articlepath` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT 'https://zh.wikipedia.org/wiki/',
  `lastuse` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stoptime` timestamp NULL DEFAULT NULL,
  `pagepreview` tinyint(1) NOT NULL DEFAULT '1',
  `leave` tinyint(1) NOT NULL DEFAULT '0',
  `noautoleave` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


ALTER TABLE `log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `setting`
  ADD UNIQUE KEY `chatid` (`chatid`);


ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
