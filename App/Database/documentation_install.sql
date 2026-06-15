-- ============================================================
-- Dokümantasyon modülü kurulum SQL'i
-- Sunucuda php migrate çalıştıramıyorsanız bu dosyayı phpMyAdmin
-- veya mysql client ile veritabanında çalıştırın.
--
-- İlk kurulum: Tüm dosyayı baştan sona çalıştırın.
-- "Duplicate column 'parent_id'" hatası alırsanız 3. bölümü zaten
-- uygulamışsınızdır; sadece 1 ve 2'yi atlayıp 3'ü tekrar çalıştırmayın.
-- ============================================================

-- 1) Tablolar
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doc_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_doc_sections_slug (slug),
    INDEX idx_doc_sections_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doc_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_doc_pages_section (section_id),
    UNIQUE KEY uq_doc_pages_section_slug (section_id, slug),
    CONSTRAINT fk_doc_pages_section FOREIGN KEY (section_id) REFERENCES doc_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Ayar kayıtları (Genel ayarlarda kullanılıyor; group = 'system')
-- Eski portal kayıtları varsa kaldırıp tek kaynak system olsun diye siliyoruz.
-- ------------------------------------------------------------
DELETE FROM settings WHERE `key` IN ('documentation_enabled', 'documentation_title') AND `group` = 'portal';
INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES ('documentation_enabled', '0', 'system');
INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES ('documentation_title', 'Documentation', 'system');

-- 3) Ağaç yapısı: doc_sections'e parent_id ekleme
-- ------------------------------------------------------------
ALTER TABLE doc_sections ADD COLUMN parent_id INT UNSIGNED NULL DEFAULT NULL AFTER id;
ALTER TABLE doc_sections ADD INDEX idx_doc_sections_parent (parent_id);
ALTER TABLE doc_sections ADD CONSTRAINT fk_doc_sections_parent FOREIGN KEY (parent_id) REFERENCES doc_sections(id) ON DELETE CASCADE;

-- Eski slug unique kaldır (tek slug yerine aynı parent altında benzersiz olacak)
ALTER TABLE doc_sections DROP INDEX uq_doc_sections_slug;

-- Yeni unique: (parent_id, slug) — aynı üst bölümde slug tekrarsız
ALTER TABLE doc_sections ADD UNIQUE KEY uq_doc_sections_parent_slug (parent_id, slug);
