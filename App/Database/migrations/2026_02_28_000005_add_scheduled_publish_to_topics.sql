-- Planla özelliği: topics tablosuna scheduled_publish_at ve status sütunları
-- Sadece bir kez çalıştırın. Sütunlar zaten varsa hata alırsınız (güvenle yok sayabilirsiniz).

-- 1. Planlanan yayın tarihi (NULL = normal konu)
ALTER TABLE `topics`
  ADD COLUMN `scheduled_publish_at` DATETIME DEFAULT NULL AFTER `last_post_user_id`;

-- 2. Konu durumu: 'published' | 'scheduled' | 'cancelled'
ALTER TABLE `topics`
  ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'published' AFTER `scheduled_publish_at`;

-- 3. Planlanan konuları hızlı bulmak için indeks
CREATE INDEX `idx_topics_status_scheduled` ON `topics` (`status`, `scheduled_publish_at`);
