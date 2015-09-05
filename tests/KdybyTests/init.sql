CREATE TABLE `translation` (
  `key` varchar(50) NOT NULL,
  `locale` varchar(50) NOT NULL,
  `message` longtext,
  `updated_at` datetime NOT NULL
);

INSERT INTO translation (`key`, locale, message, updated_at) VALUES ('front.header', 'cs', 'záhlaví', '2015-09-06 00:16:49');
INSERT INTO translation (`key`, locale, message, updated_at) VALUES ('front.header', 'en', 'header', '2015-08-30 11:28:17');
INSERT INTO translation (`key`, locale, message, updated_at) VALUES ('messages.hello', 'cs', 'nazdar', '2015-09-06 00:16:49');
INSERT INTO translation (`key`, locale, message, updated_at) VALUES ('messages.world', 'cs', 'svete', '2015-09-06 00:16:49');
INSERT INTO translation (`key`, locale, message, updated_at) VALUES ('messages.hello', 'cs', 'hello', '2015-09-06 00:16:49');
INSERT INTO translation (`key`, locale, message, updated_at) VALUES ('messages.world', 'cs', 'world', '2015-09-06 00:16:49');
