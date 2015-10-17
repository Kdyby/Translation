CREATE TABLE `translations` (
  `key` varchar(50) NOT NULL,
  `locale` varchar(50) NOT NULL,
  `message` longtext,
  `updated_at` datetime NOT NULL
);

INSERT INTO translations (`key`, locale, message, updated_at) VALUES ('front.header', 'cs_CZ', 'záhlaví', '2015-09-06 00:16:49');
INSERT INTO translations (`key`, locale, message, updated_at) VALUES ('front.header', 'en', 'header', '2015-08-30 11:28:17');
INSERT INTO translations (`key`, locale, message, updated_at) VALUES ('messages.hello', 'cs_CZ', 'ahoj', '2015-09-06 00:16:49');
INSERT INTO translations (`key`, locale, message, updated_at) VALUES ('messages.world', 'cs_CZ', 'svete', '2015-09-06 00:16:49');
INSERT INTO translations (`key`, locale, message, updated_at) VALUES ('messages.hello', 'en', 'hello', '2015-09-06 00:16:49');
INSERT INTO translations (`key`, locale, message, updated_at) VALUES ('messages.world', 'en', 'world', '2015-09-06 00:16:49');
