-- ============================================================
-- SEF URL (tüm sistem) - Hosting'de migration yerine çalıştırın
-- Sırayla çalıştırın. Sütun zaten varsa ilgili ALTER hata verir;
-- o tabloyu atlayıp devam edin.
-- ============================================================

-- 1) topics tablosu (konu/makale slug + rastgele url_key)
-- Sadece url_key yoksa çalıştırın:
ALTER TABLE `topics` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL AFTER `slug`;
CREATE UNIQUE INDEX idx_topics_url_key ON topics(url_key);

-- 2) posts
ALTER TABLE `posts` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL AFTER `edit_count`;
CREATE UNIQUE INDEX idx_posts_url_key ON posts(url_key);

-- 3) conversations
ALTER TABLE `conversations` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL;
CREATE UNIQUE INDEX idx_conversations_url_key ON conversations(url_key);

-- 4) notifications
ALTER TABLE `notifications` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL;
CREATE UNIQUE INDEX idx_notifications_url_key ON notifications(url_key);

-- 5) attachments
ALTER TABLE `attachments` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL;
CREATE UNIQUE INDEX idx_attachments_url_key ON attachments(url_key);

-- 6) users
ALTER TABLE `users` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL;
CREATE UNIQUE INDEX idx_users_url_key ON users(url_key);

-- ============================================================
-- Not: Bir tabloda url_key zaten varsa "Duplicate column" alırsınız,
-- o satırı atlayın. İndeks zaten varsa "Duplicate key" alırsınız,
-- o CREATE INDEX satırını atlayın.
-- ============================================================
