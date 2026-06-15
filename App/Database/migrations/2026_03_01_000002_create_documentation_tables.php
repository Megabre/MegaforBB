<?php

declare(strict_types=1);

/**
 * Core documentation (Docusaurus-style): sections, pages, and toggle setting.
 * When documentation_enabled is '0', no doc data is loaded — zero system load.
 */
return [
    'up' => "
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

        INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES ('documentation_enabled', '0', 'portal');
        INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES ('documentation_title', 'Documentation', 'portal');
    ",
    'down' => "
        DROP TABLE IF EXISTS doc_pages;
        DROP TABLE IF EXISTS doc_sections;
        DELETE FROM settings WHERE `key` IN ('documentation_enabled', 'documentation_title') AND `group` = 'portal';
    "
];
