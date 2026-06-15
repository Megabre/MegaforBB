<?php

declare(strict_types=1);

/**
 * Documentation: tree structure (parent_id), sibling-unique slug.
 */
return [
    'up' => function (\PDO $pdo) {
        // Add parent_id if not exists
        $cols = $pdo->query("SHOW COLUMNS FROM doc_sections LIKE 'parent_id'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE doc_sections ADD COLUMN parent_id INT UNSIGNED NULL DEFAULT NULL AFTER id");
            $pdo->exec("ALTER TABLE doc_sections ADD INDEX idx_doc_sections_parent (parent_id)");
            $pdo->exec("ALTER TABLE doc_sections ADD CONSTRAINT fk_doc_sections_parent FOREIGN KEY (parent_id) REFERENCES doc_sections(id) ON DELETE CASCADE");
        }
        // Drop old unique on slug (name may vary)
        $idx = $pdo->query("SHOW INDEX FROM doc_sections WHERE Column_name = 'slug' AND Non_unique = 0")->fetch(\PDO::FETCH_ASSOC);
        if ($idx) {
            $name = $idx['Key_name'];
            $pdo->exec("ALTER TABLE doc_sections DROP INDEX `" . $name . "`");
        }
        // Add unique (parent_id, slug) if not exists
        $uq = $pdo->query("SHOW INDEX FROM doc_sections WHERE Key_name = 'uq_doc_sections_parent_slug'")->fetch();
        if (!$uq) {
            $pdo->exec("ALTER TABLE doc_sections ADD UNIQUE KEY uq_doc_sections_parent_slug (parent_id, slug)");
        }
    },
    'down' => "
        ALTER TABLE doc_sections DROP FOREIGN KEY fk_doc_sections_parent;
        ALTER TABLE doc_sections DROP INDEX idx_doc_sections_parent;
        ALTER TABLE doc_sections DROP INDEX uq_doc_sections_parent_slug;
        ALTER TABLE doc_sections DROP COLUMN parent_id;
        ALTER TABLE doc_sections ADD UNIQUE KEY uq_doc_sections_slug (slug);
    "
];
