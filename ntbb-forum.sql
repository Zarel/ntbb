CREATE TABLE `ntbb_topics` (
  `postid` int NOT NULL,
  `topicid` int NOT NULL,
  `forumid` int NOT NULL,
  `authorid` varchar(255) NOT NULL,
  `authorname` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL,
  `title` varchar(255) NOT NULL,
  `majortitle` tinyint(1) NOT NULL,
  `date` bigint NOT NULL,
  `lastpostid` int NOT NULL,
  `parentpostid` int NOT NULL DEFAULT '0',
  `numposts` int NOT NULL DEFAULT '1',
  `treeindex` varchar(255) NOT NULL,
  `messagehtml` mediumtext NOT NULL,
  PRIMARY KEY (`postid`),
  UNIQUE KEY `treeindex` (`treeindex`),
  UNIQUE KEY `lastpostid` (`lastpostid`),
  KEY `topicid` (`topicid`),
  KEY `forumid` (`forumid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3

CREATE TABLE `ntbb_posts` (
  `postid` int NOT NULL AUTO_INCREMENT,
  `topicid` int NOT NULL,
  `authorid` varchar(255) NOT NULL,
  `authorname` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL,
  `title` varchar(255) NOT NULL,
  `majortitle` tinyint(1) NOT NULL,
  `date` bigint NOT NULL,
  `editdate` int NOT NULL DEFAULT '0',
  `parentpostid` int NOT NULL,
  `treeindex` varchar(255) NOT NULL,
  `messagehtml` mediumtext NOT NULL,
  PRIMARY KEY (`postid`),
  KEY `topicid` (`topicid`),
  KEY `treeindex` (`treeindex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3