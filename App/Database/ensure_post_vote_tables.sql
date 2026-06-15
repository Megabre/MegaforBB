-- Mesaj oylama (soru/cevap) için gerekli tablo ve alanlar.
-- Vote sistemi çalışmıyorsa bu dosyayı sunucuda bir kez çalıştırın.
-- (Zaten varsa: post_votes oluşturulmaz, net_votes için "Duplicate column" hatası alırsanız yok sayın.)

-- 1) post_votes tablosu (yoksa oluşturulur)
CREATE TABLE IF NOT EXISTS `post_votes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `value` tinyint(4) NOT NULL COMMENT '1 up, -1 down',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post_user` (`post_id`,`user_id`),
  KEY `idx_post` (`post_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) posts tablosunda net_votes sütunu (yoksa ekleyin; zaten varsa "Duplicate column" hatası alırsınız, yok sayın)
-- Aşağıdaki satırı çalıştırın. Hata alırsanız sütun zaten var demektir.
ALTER TABLE `posts` ADD COLUMN `net_votes` int(11) NOT NULL DEFAULT 0 AFTER `like_count`;
