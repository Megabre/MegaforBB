-- Rekor çevrimiçi kullanıcı sayısı (phpBB tarzı)
-- Hosting ortamında migration çalıştırılamıyorsa bu dosyayı phpMyAdmin veya mysql CLI ile çalıştırın.
-- Tablo: forum_stats (tek satır, id=1)
--
-- Sütunlar zaten varsa "Duplicate column name" hatası alırsınız; o durumda bu ALTER'ı çalıştırmayın.
-- Geri almak için (isteğe bağlı):
--   ALTER TABLE forum_stats DROP COLUMN record_online_users, DROP COLUMN record_online_date;

ALTER TABLE `forum_stats`
  ADD COLUMN `record_online_users` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `last_member_username`,
  ADD COLUMN `record_online_date` DATETIME NULL DEFAULT NULL AFTER `record_online_users`;
