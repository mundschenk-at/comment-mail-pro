CREATE TABLE IF NOT EXISTS `%%prefix%%sub_event_log` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key.',
  `sub_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Subscription ID from the subs table; at the time of the event. The subscription may or may not still exist.',
  `key` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Subscription key from the subs table; at the time of the event. The subscription may or may not still exist.',
  `oby_sub_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Overwritten by subscription ID from the subs table? The subscription may or may not still exist.',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'User ID from the wp_users table; at the time of the event, if applicable. The user may or may not still exist.',
  `post_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Post ID from the wp_posts table; at the time of the event. The post may or may not still exist.',
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Comment ID from the wp_comments table; at the time of the event, if applicable. The comment may or may not still exist.',
  `deliver` enum('asap','hourly','daily','weekly') COLLATE utf8_unicode_ci NOT NULL COMMENT 'Delivery option; at the time of the event.',
  `fname` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'First name; at the time of the event.',
  `lname` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Last name; at the time of the event.',
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Email address; at the time of the event.',
  `ip` varchar(39) COLLATE utf8_unicode_ci NOT NULL COMMENT 'IP address; at the time of the event.',
  `region` varchar(2) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Geographic region; at the time of the event.',
  `country` varchar(2) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Geographic country; at the time of the event.',
  `status` enum('unconfirmed','subscribed','suspended','trashed','deleted') COLLATE utf8_unicode_ci NOT NULL COMMENT 'Subscription status; at the time of the event.',
  `key_before` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Subscription key from the subs table; before the event, if applicable. The subscription may or may not still exist.',
  `user_id_before` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'User ID from the wp_users table; before the event, if applicable. The user may or may not still exist.',
  `post_id_before` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Post ID from the wp_posts table; before the event, if applicable. The post may or may not still exist.',
  `comment_id_before` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Comment ID from the wp_comments table; before the event, if applicable. The comment may or may not still exist.',
  `deliver_before` enum('','asap','hourly','daily','weekly') COLLATE utf8_unicode_ci NOT NULL COMMENT 'Delivery option; before the event, if applicable.',
  `fname_before` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'First name; before the event, if applicable.',
  `lname_before` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Last name; before the event, if applicable.',
  `email_before` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Email address; before the event, if applicable.',
  `ip_before` varchar(39) COLLATE utf8_unicode_ci NOT NULL COMMENT 'IP address; before the event, if applicable.',
  `region_before` varchar(2) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Geographic region; before the event, if applicable.',
  `country_before` varchar(2) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Geographic country; before the event, if applicable.',
  `status_before` enum('','unconfirmed','subscribed','suspended','trashed','deleted') COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Subscription status; before the event, if applicable.',
  `event` enum('inserted','updated','overwritten','purged','cleaned','deleted') COLLATE utf8_unicode_ci NOT NULL COMMENT 'Event type.',
  `user_initiated` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'This event was user initiated, or no?',
  `time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Event time. Unix timestamp.',
  PRIMARY KEY (`ID`),
  FULLTEXT KEY `fulltext_search` (`key`,`fname`,`lname`,`email`,`ip`,`key_before`,`fname_before`,`lname_before`,`email_before`,`ip_before`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;