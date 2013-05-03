-- phpMyAdmin SQL Dump
-- version 3.3.8.1
-- http://www.phpmyadmin.net
--
-- 主机: w.rdc.sae.sina.com.cn:3307
-- 生成日期: 2013 年 05 月 03 日 11:59
-- 服务器版本: 5.5.23
-- PHP 版本: 5.2.9

-- 对于@ggtt101五笔机器人，你需要手动将/ggtt/*.txt的相关字库/词库导入到对应的数据表中。


SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `app_marcher`
--

-- --------------------------------------------------------

--
-- 表的结构 `app_alarma`
--

CREATE TABLE IF NOT EXISTS `app_alarma` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(36) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `body` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=238 ;

-- --------------------------------------------------------

--
-- 表的结构 `app_ffbirthday2`
--

CREATE TABLE IF NOT EXISTS `app_ffbirthday2` (
  `id` varchar(50) NOT NULL,
  `birthday` varchar(10) NOT NULL,
  `oauth_token` varchar(50) NOT NULL,
  `oauth_token_secret` varchar(50) NOT NULL,
  `uid` varchar(50) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  `last_check` int(11) NOT NULL DEFAULT '-1',
  `dm_freq` int(11) NOT NULL DEFAULT '-1',
  `avatar_quota` int(11) NOT NULL DEFAULT '20',
  `avatar_filesize` int(11) NOT NULL DEFAULT '512000',
  PRIMARY KEY (`id`),
  KEY `birthday_idx` (`id`,`birthday`),
  KEY `last_check` (`last_check`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_ffhitlist`
--

CREATE TABLE IF NOT EXISTS `app_ffhitlist` (
  `id` varchar(36) NOT NULL,
  `query_id` varchar(10) NOT NULL,
  `query` varchar(100) NOT NULL,
  `lastid` int(11) NOT NULL,
  `lastmatch` int(11) NOT NULL,
  `lastdm` int(11) NOT NULL,
  `lastquery` int(11) NOT NULL,
  PRIMARY KEY (`query_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_ffontime`
--

CREATE TABLE IF NOT EXISTS `app_ffontime` (
  `id` varchar(15) NOT NULL,
  `user` varchar(36) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `recurring` int(11) NOT NULL DEFAULT '-1' COMMENT '0天，1周，2月',
  `sequence` int(11) NOT NULL DEFAULT '-1',
  `message` varchar(500) NOT NULL,
  `type` varchar(7) NOT NULL,
  `retry` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_ffontime_follower`
--

CREATE TABLE IF NOT EXISTS `app_ffontime_follower` (
  `id` varchar(36) NOT NULL,
  `request` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_ggtt`
--

CREATE TABLE IF NOT EXISTS `app_ggtt` (
  `word` varchar(5) NOT NULL,
  `code` varchar(4) NOT NULL,
  PRIMARY KEY (`word`),
  KEY `word` (`word`,`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_ggtt_gbk`
--

CREATE TABLE IF NOT EXISTS `app_ggtt_gbk` (
  `word` varchar(5) NOT NULL,
  `code` varchar(4) NOT NULL,
  PRIMARY KEY (`word`),
  KEY `word` (`word`,`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_ggtt_log`
--

CREATE TABLE IF NOT EXISTS `app_ggtt_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `user` varchar(40) NOT NULL,
  `msg` varchar(30) NOT NULL,
  `body` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=389 ;

-- --------------------------------------------------------

--
-- 表的结构 `app_ggtt_words`
--

CREATE TABLE IF NOT EXISTS `app_ggtt_words` (
  `code` varchar(4) NOT NULL,
  `words` varchar(60) NOT NULL,
  `times` int(11) NOT NULL,
  `prio` int(11) NOT NULL,
  KEY `code` (`code`,`words`),
  KEY `prio` (`prio`),
  KEY `times` (`times`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ff5_id`
--

CREATE TABLE IF NOT EXISTS `ff5_id` (
  `name` varchar(10) NOT NULL,
  `id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ff5_photo`
--

CREATE TABLE IF NOT EXISTS `ff5_photo` (
  `largeurl` varchar(200) DEFAULT NULL,
  `user_url` varchar(200) DEFAULT NULL,
  `text` varchar(2000) DEFAULT NULL,
  `msg_id` varchar(200) DEFAULT NULL,
  `id` varchar(200) NOT NULL DEFAULT '',
  `user_id` varchar(200) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `user_screen_name` varchar(200) DEFAULT NULL,
  `repost_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ffavatar_birthday`
--

CREATE TABLE IF NOT EXISTS `ffavatar_birthday` (
  `id` varchar(36) NOT NULL,
  `birthday` varchar(5) NOT NULL,
  `aid` int(11) NOT NULL,
  `state` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ffavatar_schedule`
--

CREATE TABLE IF NOT EXISTS `ffavatar_schedule` (
  `aid` int(11) NOT NULL,
  `schedule` int(11) NOT NULL,
  `recurring` varchar(1) NOT NULL DEFAULT 'd',
  `status` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`aid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ffavatar_storage`
--

CREATE TABLE IF NOT EXISTS `ffavatar_storage` (
  `aid` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(36) NOT NULL,
  `avatar` varchar(150) NOT NULL,
  `type` varchar(15) NOT NULL,
  `storage` varchar(20) NOT NULL,
  `tweak` varchar(5) NOT NULL,
  PRIMARY KEY (`aid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3464 ;

-- --------------------------------------------------------

--
-- 表的结构 `index_applist`
--

CREATE TABLE IF NOT EXISTS `index_applist` (
  `app_name` varchar(60) NOT NULL,
  `app_url` varchar(200) NOT NULL,
  `app_desc` varchar(200) NOT NULL,
  `app_rate` varchar(60) NOT NULL,
  PRIMARY KEY (`app_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `test1`
--

CREATE TABLE IF NOT EXISTS `test1` (
  `id` int(11) NOT NULL,
  `name` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `test2`
--

CREATE TABLE IF NOT EXISTS `test2` (
  `id` int(11) NOT NULL,
  `user` varchar(500) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
