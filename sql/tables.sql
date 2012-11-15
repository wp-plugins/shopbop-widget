CREATE TABLE IF NOT EXISTS `%PREFIX%shopbop_cache` (
  `id_wp_shopbop_cache` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL DEFAULT '',
  `data` text NOT NULL,
  `date` varchar(32),
  `type` enum('marketing','pane1','pane2','promotion') DEFAULT NULL,
  `last_update` datetime DEFAULT '1970-01-01 00:00:00',
  `post_id` INT NOT NULL,
  `update_requested` datetime DEFAULT NULL,
  PRIMARY KEY (`id_wp_shopbop_cache`),
  UNIQUE KEY (`path`, `type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
|
CREATE TABLE `%PREFIX%shopbop_category_assignments` (
  `post_id` INT NOT NULL,
  `category_path` VARCHAR(255) NULL,
  `use_default` INT(1) DEFAULT 1,
  `is_random` INT(1) DEFAULT 1,
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
|
INSERT INTO `%PREFIX%shopbop_category_assignments` (post_id) VALUES (-1);
