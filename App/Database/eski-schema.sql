-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 01 Mar 2026, 22:39:53
-- Sunucu sürümü: 11.4.9-MariaDB-ubu2404
-- PHP Sürümü: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `megaforbb`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ads`
--

CREATE TABLE `ads` (
  `id` int(10) UNSIGNED NOT NULL,
  `position_key` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `html_content` longtext DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ads`
--

INSERT INTO `ads` (`id`, `position_key`, `name`, `html_content`, `enabled`, `sort_order`) VALUES
(1, 'header_below_menu', 'Ana sayfa - Menü altı', '<div class=\"ad-placeholder text-center py-3 text-sm text-gray-500 bg-gray-100 rounded\">Reklam alanı: Menü altı</div>', 0, 0),
(2, 'footer_above_home', 'Ana sayfa - Footer üstü', '<div class=\"ad-placeholder text-center py-3 text-sm text-gray-500 bg-gray-100 rounded\">Reklam alanı: Footer üstü</div>', 0, 0),
(3, 'category_between', 'Kategori arası 1', '<div class=\"ad-placeholder text-center py-2 text-xs text-gray-500 bg-gray-50 rounded my-2\">Reklam: Kategori arası</div>', 0, 1),
(4, 'sidebar_top', 'Sidebar üst', '<div class=\"ad-placeholder text-center py-3 text-sm text-gray-500 bg-gray-100 rounded\">Reklam: Sidebar</div>', 0, 0),
(5, 'topic_below_breadcrumb', 'Konu - Breadcrumb altı', '<div class=\"ad-placeholder text-center py-2 text-sm text-gray-500 bg-gray-50 rounded\">Reklam: Breadcrumb altı</div>', 0, 0),
(6, 'topic_between_posts', 'Konu - Mesajlar arası', '<div class=\"ad-placeholder text-center py-2 text-xs text-gray-500 bg-gray-50 rounded\">Reklam: Mesajlar arası</div>', 0, 0),
(7, 'topic_above_footer', 'Konu - Footer üstü', '<div class=\"ad-placeholder text-center py-2 text-sm text-gray-500 bg-gray-50 rounded\">Reklam: Konu footer üstü</div>', 0, 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `badge_type` varchar(32) NOT NULL DEFAULT 'info',
  `display_location` varchar(32) NOT NULL DEFAULT 'both',
  `send_as_notification` tinyint(1) NOT NULL DEFAULT 0,
  `is_dismissible` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `show_from` datetime DEFAULT NULL,
  `show_until` datetime DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `badge_type`, `display_location`, `send_as_notification`, `is_dismissible`, `is_active`, `show_from`, `show_until`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'MegaforBB İlk sürümü BETA', '<p>MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.</p><p>Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.</p>', 'success', 'forum_section', 0, 1, 1, NULL, NULL, 0, '2026-02-22 23:50:49', '2026-02-27 16:09:27');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `announcement_dismissals`
--

CREATE TABLE `announcement_dismissals` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `announcement_id` int(10) UNSIGNED NOT NULL,
  `dismissed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `announcement_dismissals`
--

INSERT INTO `announcement_dismissals` (`user_id`, `announcement_id`, `dismissed_at`) VALUES
(1, 1, '2026-02-23 00:03:35'),
(121, 1, '2026-02-23 15:29:30');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `attachments`
--

CREATE TABLE `attachments` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `storage_driver` varchar(16) NOT NULL DEFAULT 'local',
  `mime_type` varchar(100) NOT NULL DEFAULT 'application/octet-stream',
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `download_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `url_key` varchar(24) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `attachments`
--

INSERT INTO `attachments` (`id`, `post_id`, `user_id`, `original_name`, `stored_name`, `storage_driver`, `mime_type`, `file_size`, `download_count`, `created_at`, `url_key`) VALUES
(1, 66, 1, 'lost.gif', 'att_69a198ca917b70.13742958_lost.gif', 'local', 'image/gif', 355474, 3, '2026-02-27 16:14:50', NULL),
(2, 81, 129, 'pngtree-user-profile-avatar-png-image_10211467.png', 'att_69a4a0851620c9.53050130_pngtree-user-profile-avatar-png-image_10211467.png', 'local', 'image/png', 11582, 0, '2026-03-01 23:24:37', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blocked_email_domains`
--

CREATE TABLE `blocked_email_domains` (
  `id` int(10) UNSIGNED NOT NULL,
  `domain` varchar(255) NOT NULL COMMENT 'e.g. tempmail.com',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `blocked_email_domains`
--

INSERT INTO `blocked_email_domains` (`id`, `domain`, `created_at`) VALUES
(1, 'tempmail.com', '2026-02-26 15:51:35'),
(2, 'guerrillamail.com', '2026-02-26 15:51:35'),
(3, '10minutemail.com', '2026-02-26 15:51:35'),
(4, 'mailinator.com', '2026-02-26 15:51:35'),
(5, 'throwaway.email', '2026-02-26 15:51:35'),
(6, 'temp-mail.org', '2026-02-26 15:51:35'),
(7, 'fakeinbox.com', '2026-02-26 15:51:35'),
(8, 'trashmail.com', '2026-02-26 15:51:35'),
(9, 'yopmail.com', '2026-02-26 15:51:35'),
(10, 'getnada.com', '2026-02-26 15:51:35'),
(11, 'mailnesia.com', '2026-02-26 15:51:35'),
(12, 'sharklasers.com', '2026-02-26 15:51:35'),
(13, 'guerrillamail.info', '2026-02-26 15:51:35'),
(14, 'dispostable.com', '2026-02-26 15:51:35'),
(15, 'tempinbox.com', '2026-02-26 15:51:35'),
(16, 'mohmal.com', '2026-02-26 15:51:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blocked_usernames`
--

CREATE TABLE `blocked_usernames` (
  `id` int(10) UNSIGNED NOT NULL,
  `pattern` varchar(255) NOT NULL COMMENT 'Exact username or regex pattern',
  `is_regex` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blocked_words`
--

CREATE TABLE `blocked_words` (
  `id` int(10) UNSIGNED NOT NULL,
  `word` varchar(255) NOT NULL,
  `replacement` varchar(255) DEFAULT NULL COMMENT 'Replace with this if action=replace',
  `is_regex` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `slug` varchar(128) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(64) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#cccccc',
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_article_category` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `sort_order`, `is_article_category`, `created_at`, `updated_at`) VALUES
(1, 'MegaforBB Community', 'megaforbb-community', 'MegaforBB Forum sistemi için Genel tartışma forumu.', 'comments', '#017596', 0, 0, '2026-02-23 04:05:22', '2026-02-26 20:33:17'),
(2, 'Help and Technical Support', 'help-and-technical-support', 'MegaforBB Help and Technical Support forum', 'cloud', '#014781', 1, 0, '2026-02-23 04:34:57', '2026-02-26 20:33:17'),
(4, 'Genel Dökümanlar', 'genel-d-k-manlar', '', 'book', '#a5f3f1', 2, 1, '2026-03-01 03:58:51', '2026-03-01 03:58:54');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `ip_address`, `is_read`, `created_at`) VALUES
(1, 'Ali', 'slaweally@hotmail.com', 'MegaforBB', 'MegaforBB\r\nBizimle iletişime geçmek için formu kullanın. En kısa sürede size dönüş yapacağız.', '127.0.0.1', 1, '2026-02-23 15:38:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `contact_message_replies`
--

CREATE TABLE `contact_message_replies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_message_id` bigint(20) UNSIGNED NOT NULL,
  `reply_body` text NOT NULL,
  `replied_by_user_id` int(10) UNSIGNED NOT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `contact_message_replies`
--

INSERT INTO `contact_message_replies` (`id`, `contact_message_id`, `reply_body`, `replied_by_user_id`, `email_sent`, `created_at`) VALUES
(1, 1, 'En kısa sürede size dönüş yapacağız.', 1, 1, '2026-02-25 17:26:28');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `content_permissions`
--

CREATE TABLE `content_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` smallint(5) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `content_type` varchar(128) NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `value` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `conversations`
--

CREATE TABLE `conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `url_key` varchar(24) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `conversations`
--

INSERT INTO `conversations` (`id`, `created_at`, `url_key`) VALUES
(16, '2026-02-27 17:51:10', NULL),
(17, '2026-02-27 17:59:20', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `conversation_user`
--

CREATE TABLE `conversation_user` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `conversation_user`
--

INSERT INTO `conversation_user` (`id`, `conversation_id`, `user_id`, `last_read_at`, `created_at`) VALUES
(3, 16, 129, '2026-02-27 18:07:43', '2026-02-27 15:51:10'),
(4, 16, 1, '2026-02-27 18:07:33', '2026-02-27 15:51:10'),
(5, 17, 129, '2026-02-27 18:04:37', '2026-02-27 15:59:20'),
(6, 17, 131, NULL, '2026-02-27 15:59:20');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `doc_pages`
--

CREATE TABLE `doc_pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `doc_pages`
--

INSERT INTO `doc_pages` (`id`, `section_id`, `title`, `slug`, `content`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sistem gereksinimleri', 'sistem-gereksinimleri', '<h2 id=\"operating-systems\">MegaforBB Sistem gereksinimleir nedir ?</h2><p>MegaforBB herhangi bir hosting üzerinde çalışabilmesi ve sorun yaşatmamamsı için öngürülerek geliştirilmiştir ancak; <b>Cloudpanel</b> control paneli için özel optimize edilmiştir.</p><p>- Herhangi bir kontrol panelinde de sorunsuz kullanabilirsiniz, Tavziyemiz ise aşağıdaki gibi minimum özelliklere sahip Cloud Server üzerinde Cloudpanel koşturarak kullanılabilit.</p><p>Cloudpanel için minimum sistem egreksinimleri ;</p><h2 id=\"operating-systems\">Operating Systems</h2><ul><li>Ubuntu 24.04 (Noble Numbat) </li><li>Ubuntu 22.04 (Jammy Jellyfish)</li><li>Debian 13 (Trixie)</li><li>Debian 12 (Bookworm)</li><li>Debian 11 (Bullseye)</li></ul><h2 id=\"architectures\">Architectures</h2><ul><li><strong>X86</strong></li><li><strong>ARM64</strong></li></ul><h2 id=\"cores\">Cores</h2><ul><li><strong>&gt;= 1 Core</strong></li></ul><h2 id=\"memory\">Memory</h2><ul><li><strong>&gt;= 2 GB</strong> of <strong>RAM</strong></li></ul><h2 id=\"disk\">Disk</h2><ul><li><strong>&gt;= 10 GB</strong></li></ul><p><b><font color=\"#ff0000\">Cloudpanel control panelinde sorunsuz çalışacak şekilde optimize edilmiştir.</font></b></p>', 10, '2026-03-01 15:45:02', '2026-03-01 16:23:59'),
(2, 1, 'Kurulum ve Güncelleme', 'kurulum-detaylar', '<p>MegaforBB kurulum ve güncelleme işlemi şu anda henüz canlı sürümde olmadığı için detaylı bilgi daha sonrasında eklenecektir.</p>', 20, '2026-03-01 15:45:16', '2026-03-01 17:51:11'),
(3, 1, 'Altyapı ve teknolojiler', 'altyapi-ve-teknolojiler', '<h1 class=\"\">MegaforBB altyapı</h1><p>MegaforBB CMS Forum sistemini tamamen özel olarak Symfony&nbsp; + Laravel FW\'lerini MegaforBB Forecor çekirdeği üzerine özel olarak optimize edip sistemi geliştirdik.&nbsp;</p><p>Sistemimizde Symfony ve Laravel optimize edilerek ayrıştırılmış paket sistemi ile Forecor çekirdeğinde kullanılmaktadır.&nbsp;</p><p>Ön yüz ise: Twig tema motoru ile birlikte Alpine, ve Tailwind, Tabler.io yapıları kullanılmıştır.&nbsp;</p>', 30, '2026-03-01 15:45:30', '2026-03-01 17:49:54'),
(4, 1, 'Tavsiyeler', 'tavsiyeler', '<p>MegaforBB takip etmeye devam edin, Güzel projelerin geleceğinden emin olabilirsiniz.</p>', 40, '2026-03-01 15:45:46', '2026-03-01 23:22:44'),
(5, 2, 'Forum ve Kategori', 'forum-ve-kategori', '<h2 class=\"\">MegaforBB Forum</h2><p>Forum ve kategri sistemi genel olarak bildiğimiz Bullein Board sisteminin gelişmiş halidir.&nbsp;</p><p>Kategori ve Forum mantığı burada Frontend\'te aynı olsa da admin panelde Kullanım ve yönetim kolaylığı için tüm sisetm sürükle bırak ile yönetilebilir olması için basitleştirilmiştir.</p><p><br></p>', 0, '2026-03-01 15:46:19', '2026-03-01 17:38:25'),
(7, 4, 'Döküman sistemi', 'documan-sistemi', '', 10, '2026-03-01 15:47:11', '2026-03-01 16:02:15'),
(8, 4, 'Makale sistemi', 'makale-sistemi', '', 20, '2026-03-01 15:48:11', '2026-03-01 16:02:17'),
(9, 4, 'Sayfa sistemi', 'sayfa-sistemi', '', 0, '2026-03-01 15:54:13', '2026-03-01 15:54:22'),
(10, 4, 'Etiket Sistemi', 'etiket-sistemi', '', 30, '2026-03-01 15:57:24', '2026-03-01 16:02:17');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `doc_sections`
--

CREATE TABLE `doc_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `doc_sections`
--

INSERT INTO `doc_sections` (`id`, `parent_id`, `title`, `slug`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Başlarken', 'ba-larken', 0, '2026-03-01 15:44:43', '2026-03-01 15:52:40'),
(2, NULL, 'Kullanım ve Detaylar', 'kullan-m-ve-detaylar', 20, '2026-03-01 15:46:04', '2026-03-01 15:53:38'),
(4, 2, 'CMS sistemi', 'cms-sistemi', 10, '2026-03-01 15:53:14', '2026-03-01 15:53:43');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `forums`
--

CREATE TABLE `forums` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` smallint(5) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(128) NOT NULL,
  `slug` varchar(128) NOT NULL,
  `forum_type` varchar(32) NOT NULL DEFAULT 'discussion',
  `description` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `icon` varchar(64) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `topic_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `post_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_post_id` int(10) UNSIGNED DEFAULT NULL,
  `last_post_at` datetime DEFAULT NULL,
  `last_post_user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `allow_new_posts` tinyint(1) NOT NULL DEFAULT 1,
  `moderate_new_topics` tinyint(1) NOT NULL DEFAULT 0,
  `moderate_new_posts` tinyint(1) NOT NULL DEFAULT 0,
  `count_user_posts` tinyint(1) NOT NULL DEFAULT 1,
  `include_in_new_posts` tinyint(1) NOT NULL DEFAULT 1,
  `indexing_mode` tinyint(1) NOT NULL DEFAULT 1,
  `min_tags` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `default_sort_order` varchar(32) NOT NULL DEFAULT 'last_post_desc',
  `topic_date_limit` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `topic_prompts` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `forums`
--

INSERT INTO `forums` (`id`, `category_id`, `parent_id`, `name`, `slug`, `forum_type`, `description`, `image_url`, `icon`, `sort_order`, `topic_count`, `post_count`, `last_post_id`, `last_post_at`, `last_post_user_id`, `created_at`, `updated_at`, `allow_new_posts`, `moderate_new_topics`, `moderate_new_posts`, `count_user_posts`, `include_in_new_posts`, `indexing_mode`, `min_tags`, `default_sort_order`, `topic_date_limit`, `topic_prompts`) VALUES
(1, 1, NULL, 'Duyuru ve Güncelleme', 'duyuru-ve-g-ncelleme', 'discussion', 'MegaforBB yazılımıyla ilgili genel haberleri burada bulabilirsiniz.\r\n', 'https://www.megaforbb.com.tr/uploads/images/2026/02/news-updates.png', 'fa-regular fa-newspaper', 0, 7, 11, 5, '2026-03-02 00:39:01', 1, '2026-02-23 04:07:42', '2026-03-01 22:39:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(3, 1, NULL, 'Bug Reports', 'bug-reports', 'discussion', 'MegaforBB Bug reports forum (Hata raporlama forumu)', 'https://www.megaforbb.com.tr/uploads/images/2026/02/bugs-report.png', 'bug', 1, 5, 11, 31, '2026-03-02 00:39:01', 1, '2026-02-23 04:34:23', '2026-03-01 22:39:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(4, 2, NULL, 'Genel Sorular ', 'genel-sorular-', 'discussion', 'Megaforbb\'a yeni mi katıldınız veya platformu kullanma konusunda genel yardıma mı ihtiyacınız var? Genel Yapılandırma kılavuzu ile ilgili sorularınızı ve taleplerinizi buraya yazın. ', 'https://www.megaforbb.com.tr/uploads/images/2026/02/general-quest.png', 'users', 2, 6, 11, 22, '2026-03-02 00:39:01', 1, '2026-02-23 04:35:54', '2026-03-01 22:39:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(6, 2, NULL, 'Test ve Demo', 'test-ve-demo', 'discussion', 'Bu kategoride test ve demo içeriklerini paylaşacağız. Yeni özellikleri burada paylaşıp kullanıcıların beğenisine sunacağız.', 'https://www.megaforbb.com.tr/uploads/images/2026/02/test-demo.png', 'wand-magic-sparkles', 3, 6, 13, 41, '2026-03-02 00:39:01', 1, '2026-02-23 20:09:29', '2026-03-01 22:39:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(8, 4, NULL, 'Yazılım ve detaylı dökümanlar', 'yaz-l-m-ve-detayl-d-k-manlar', 'article', '', '', 'code', 0, 2, 2, 37, '2026-03-02 00:39:01', 1, '2026-03-01 03:59:48', '2026-03-01 22:39:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(9, 4, NULL, 'Hosting - Server Dökümanları', 'hosting---server-d-k-manlar-', 'article', 'Hosting ve server Dökümanları', '', 'server', 0, 0, 0, NULL, '2026-03-02 00:39:01', NULL, '2026-03-01 04:00:19', '2026-03-01 22:39:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `forum_stats`
--

CREATE TABLE `forum_stats` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `total_topics` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_posts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_members` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_member_id` int(10) UNSIGNED DEFAULT NULL,
  `last_member_username` varchar(64) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `forum_stats`
--

INSERT INTO `forum_stats` (`id`, `total_topics`, `total_posts`, `total_members`, `last_member_id`, `last_member_username`, `updated_at`) VALUES
(1, 26, 48, 4, 131, 'slaweally', '2026-03-01 22:17:59');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `group_permissions`
--

CREATE TABLE `group_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` smallint(5) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `value` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `import_errors`
--

CREATE TABLE `import_errors` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'xenforo',
  `step` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_id` int(10) UNSIGNED DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `import_id_map`
--

CREATE TABLE `import_id_map` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'xenforo',
  `entity_type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_id` int(10) UNSIGNED NOT NULL,
  `new_id` int(10) UNSIGNED NOT NULL,
  `extra` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `import_id_map`
--

INSERT INTO `import_id_map` (`id`, `source`, `entity_type`, `old_id`, `new_id`, `extra`) VALUES
(1, 'megaforbb', 'role', 1, 1, NULL),
(2, 'megaforbb', 'role', 2, 2, NULL),
(3, 'megaforbb', 'role', 3, 3, NULL),
(4, 'megaforbb', 'role', 4, 4, NULL),
(5, 'megaforbb', 'user', 1, 1, NULL),
(6, 'megaforbb', 'user', 121, 129, NULL),
(7, 'megaforbb', 'user', 122, 130, NULL),
(8, 'megaforbb', 'category', 1, 1, NULL),
(9, 'megaforbb', 'category', 2, 2, NULL),
(10, 'megaforbb', 'forum', 1, 1, NULL),
(11, 'megaforbb', 'forum', 2, 2, NULL),
(12, 'megaforbb', 'forum', 3, 3, NULL),
(13, 'megaforbb', 'forum', 4, 4, NULL),
(14, 'megaforbb', 'forum', 5, 5, NULL),
(15, 'megaforbb', 'forum', 6, 6, NULL),
(16, 'megaforbb', 'forum', 7, 7, NULL),
(17, 'megaforbb', 'prefix', 1, 1, NULL),
(18, 'megaforbb', 'prefix', 3, 2, NULL),
(19, 'megaforbb', 'prefix', 4, 3, NULL),
(20, 'megaforbb', 'topic', 1, 1, NULL),
(21, 'megaforbb', 'topic_first_post', 1, 1, NULL),
(22, 'megaforbb', 'topic_last_post', 1, 3, NULL),
(23, 'megaforbb', 'topic', 2, 2, NULL),
(24, 'megaforbb', 'topic_first_post', 2, 2, NULL),
(25, 'megaforbb', 'topic_last_post', 2, 2, NULL),
(26, 'megaforbb', 'topic', 3, 3, NULL),
(27, 'megaforbb', 'topic_first_post', 3, 4, NULL),
(28, 'megaforbb', 'topic_last_post', 3, 6, NULL),
(29, 'megaforbb', 'topic', 4, 4, NULL),
(30, 'megaforbb', 'topic_first_post', 4, 7, NULL),
(31, 'megaforbb', 'topic_last_post', 4, 9, NULL),
(32, 'megaforbb', 'topic', 5, 5, NULL),
(33, 'megaforbb', 'topic_first_post', 5, 10, NULL),
(34, 'megaforbb', 'topic_last_post', 5, 10, NULL),
(35, 'megaforbb', 'topic', 6, 6, NULL),
(36, 'megaforbb', 'topic_first_post', 6, 13, NULL),
(37, 'megaforbb', 'topic_last_post', 6, 15, NULL),
(38, 'megaforbb', 'topic', 7, 7, NULL),
(39, 'megaforbb', 'topic_first_post', 7, 16, NULL),
(40, 'megaforbb', 'topic_last_post', 7, 18, NULL),
(41, 'megaforbb', 'topic', 8, 8, NULL),
(42, 'megaforbb', 'topic_first_post', 8, 19, NULL),
(43, 'megaforbb', 'topic_last_post', 8, 21, NULL),
(44, 'megaforbb', 'topic', 9, 9, NULL),
(45, 'megaforbb', 'topic_first_post', 9, 22, NULL),
(46, 'megaforbb', 'topic_last_post', 9, 24, NULL),
(47, 'megaforbb', 'topic', 10, 10, NULL),
(48, 'megaforbb', 'topic_first_post', 10, 25, NULL),
(49, 'megaforbb', 'topic_last_post', 10, 27, NULL),
(50, 'megaforbb', 'topic', 11, 11, NULL),
(51, 'megaforbb', 'topic_first_post', 11, 28, NULL),
(52, 'megaforbb', 'topic_last_post', 11, 30, NULL),
(53, 'megaforbb', 'topic', 12, 12, NULL),
(54, 'megaforbb', 'topic_first_post', 12, 31, NULL),
(55, 'megaforbb', 'topic_last_post', 12, 33, NULL),
(56, 'megaforbb', 'topic', 13, 13, NULL),
(57, 'megaforbb', 'topic_first_post', 13, 34, NULL),
(58, 'megaforbb', 'topic_last_post', 13, 36, NULL),
(59, 'megaforbb', 'topic', 14, 14, NULL),
(60, 'megaforbb', 'topic_first_post', 14, 37, NULL),
(61, 'megaforbb', 'topic_last_post', 14, 37, NULL),
(62, 'megaforbb', 'topic', 15, 15, NULL),
(63, 'megaforbb', 'topic_first_post', 15, 40, NULL),
(64, 'megaforbb', 'topic_last_post', 15, 40, NULL),
(65, 'megaforbb', 'topic', 16, 16, NULL),
(66, 'megaforbb', 'topic_first_post', 16, 43, NULL),
(67, 'megaforbb', 'topic_last_post', 16, 45, NULL),
(68, 'megaforbb', 'topic', 17, 17, NULL),
(69, 'megaforbb', 'topic_first_post', 17, 46, NULL),
(70, 'megaforbb', 'topic_last_post', 17, 49, NULL),
(71, 'megaforbb', 'topic_accepted_post', 18, 51, NULL),
(72, 'megaforbb', 'topic', 18, 18, NULL),
(73, 'megaforbb', 'topic_first_post', 18, 50, NULL),
(74, 'megaforbb', 'topic_last_post', 18, 52, NULL),
(75, 'megaforbb', 'topic', 19, 19, NULL),
(76, 'megaforbb', 'topic_first_post', 19, 53, NULL),
(77, 'megaforbb', 'topic_last_post', 19, 56, NULL),
(78, 'megaforbb', 'topic', 20, 20, NULL),
(79, 'megaforbb', 'topic_first_post', 20, 55, NULL),
(80, 'megaforbb', 'topic_last_post', 20, 55, NULL),
(81, 'megaforbb', 'topic_accepted_post', 21, 58, NULL),
(82, 'megaforbb', 'topic', 21, 21, NULL),
(83, 'megaforbb', 'topic_first_post', 21, 57, NULL),
(84, 'megaforbb', 'topic_last_post', 21, 58, NULL),
(85, 'megaforbb', 'topic', 22, 22, NULL),
(86, 'megaforbb', 'topic_first_post', 22, 59, NULL),
(87, 'megaforbb', 'topic_last_post', 22, 59, NULL),
(88, 'megaforbb', 'topic', 23, 23, NULL),
(89, 'megaforbb', 'topic_first_post', 23, 60, NULL),
(90, 'megaforbb', 'topic_last_post', 23, 61, NULL),
(91, 'megaforbb', 'topic', 24, 24, NULL),
(92, 'megaforbb', 'topic_first_post', 24, 62, NULL),
(93, 'megaforbb', 'topic_last_post', 24, 62, NULL),
(94, 'megaforbb', 'topic', 25, 25, NULL),
(95, 'megaforbb', 'topic_first_post', 25, 63, NULL),
(96, 'megaforbb', 'topic_last_post', 25, 63, NULL),
(97, 'megaforbb', 'post', 1, 1, NULL),
(98, 'megaforbb', 'post', 2, 2, NULL),
(99, 'megaforbb', 'post', 3, 3, NULL),
(100, 'megaforbb', 'post', 4, 4, NULL),
(101, 'megaforbb', 'post', 5, 5, NULL),
(102, 'megaforbb', 'post', 6, 6, NULL),
(103, 'megaforbb', 'post', 7, 7, NULL),
(104, 'megaforbb', 'post', 8, 8, NULL),
(105, 'megaforbb', 'post', 9, 9, NULL),
(106, 'megaforbb', 'post', 10, 10, NULL),
(107, 'megaforbb', 'post', 13, 11, NULL),
(108, 'megaforbb', 'post', 14, 12, NULL),
(109, 'megaforbb', 'post', 15, 13, NULL),
(110, 'megaforbb', 'post', 16, 14, NULL),
(111, 'megaforbb', 'post', 17, 15, NULL),
(112, 'megaforbb', 'post', 18, 16, NULL),
(113, 'megaforbb', 'post', 19, 17, NULL),
(114, 'megaforbb', 'post', 20, 18, NULL),
(115, 'megaforbb', 'post', 21, 19, NULL),
(116, 'megaforbb', 'post', 22, 20, NULL),
(117, 'megaforbb', 'post', 23, 21, NULL),
(118, 'megaforbb', 'post', 24, 22, NULL),
(119, 'megaforbb', 'post', 25, 23, NULL),
(120, 'megaforbb', 'post', 26, 24, NULL),
(121, 'megaforbb', 'post', 27, 25, NULL),
(122, 'megaforbb', 'post', 28, 26, NULL),
(123, 'megaforbb', 'post', 29, 27, NULL),
(124, 'megaforbb', 'post', 30, 28, NULL),
(125, 'megaforbb', 'post', 31, 29, NULL),
(126, 'megaforbb', 'post', 32, 30, NULL),
(127, 'megaforbb', 'post', 33, 31, NULL),
(128, 'megaforbb', 'post', 34, 32, NULL),
(129, 'megaforbb', 'post', 35, 33, NULL),
(130, 'megaforbb', 'post', 36, 34, NULL),
(131, 'megaforbb', 'post', 37, 35, NULL),
(132, 'megaforbb', 'post', 40, 36, NULL),
(133, 'megaforbb', 'post', 43, 37, NULL),
(134, 'megaforbb', 'post', 46, 38, NULL),
(135, 'megaforbb', 'post', 47, 39, NULL),
(136, 'megaforbb', 'post', 48, 40, NULL),
(137, 'megaforbb', 'post', 49, 41, NULL),
(138, 'megaforbb', 'post', 50, 42, NULL),
(139, 'megaforbb', 'post', 51, 43, NULL),
(140, 'megaforbb', 'post', 52, 44, NULL),
(141, 'megaforbb', 'post', 53, 45, NULL),
(142, 'megaforbb', 'post', 54, 46, NULL),
(143, 'megaforbb', 'post', 55, 47, NULL),
(144, 'megaforbb', 'post', 56, 48, NULL),
(145, 'megaforbb', 'post', 57, 49, NULL),
(146, 'megaforbb', 'post', 58, 50, NULL),
(147, 'megaforbb', 'post', 59, 51, NULL),
(148, 'megaforbb', 'post', 60, 52, NULL),
(149, 'megaforbb', 'post', 61, 53, NULL),
(150, 'megaforbb', 'post', 62, 54, NULL),
(151, 'megaforbb', 'post', 63, 55, NULL),
(152, 'megaforbb', 'poll', 1, 1, NULL),
(153, 'megaforbb', 'poll_option', 1, 1, NULL),
(154, 'megaforbb', 'poll_option', 2, 2, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `import_progress`
--

CREATE TABLE `import_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` varchar(32) NOT NULL DEFAULT 'xenforo',
  `step` varchar(64) NOT NULL,
  `status` enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
  `total_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `processed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `error_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `import_progress`
--

INSERT INTO `import_progress` (`id`, `source`, `step`, `status`, `total_rows`, `processed_rows`, `error_count`, `started_at`, `completed_at`) VALUES
(1, 'megaforbb', 'roles', 'completed', 4, 0, 0, '2026-02-26 18:45:10', '2026-02-26 18:45:11'),
(2, 'megaforbb', 'users', 'completed', 3, 2, 0, '2026-02-26 18:45:11', '2026-02-26 18:45:11'),
(3, 'megaforbb', 'forums', 'completed', 9, 9, 0, '2026-02-26 18:45:11', '2026-02-26 18:45:11'),
(4, 'megaforbb', 'prefixes', 'completed', 3, 3, 0, '2026-02-26 18:45:11', '2026-02-26 18:45:11'),
(5, 'megaforbb', 'topics', 'completed', 25, 25, 0, '2026-02-26 18:45:11', '2026-02-26 18:45:11'),
(6, 'megaforbb', 'posts', 'completed', 55, 55, 0, '2026-02-26 18:45:11', '2026-02-26 18:45:12'),
(7, 'megaforbb', 'polls', 'completed', 5, 5, 0, '2026-02-26 18:45:12', '2026-02-26 18:45:12'),
(8, 'megaforbb', 'social', 'completed', 60, 59, 0, '2026-02-26 18:45:12', '2026-02-26 18:45:12'),
(9, 'megaforbb', 'sync_counters', 'completed', 8, 8, 0, '2026-02-26 18:45:12', '2026-02-26 18:45:12');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `invitations`
--

CREATE TABLE `invitations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(16) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `used_by` int(10) UNSIGNED DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `invitations`
--

INSERT INTO `invitations` (`id`, `user_id`, `code`, `email`, `used_at`, `used_by`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 1, '8DZKMRCUKY', NULL, '2026-02-26 14:17:25', NULL, '2026-03-05 14:16:08', '2026-02-26 14:16:08', '2026-02-26 14:17:25'),
(2, 1, 'P3L7JYFVXY', NULL, '2026-02-26 21:38:25', 131, '2026-03-05 21:37:20', '2026-02-26 21:37:20', '2026-02-26 21:38:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `locales`
--

CREATE TABLE `locales` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(5) NOT NULL,
  `name` varchar(64) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(6) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `locales`
--

INSERT INTO `locales` (`id`, `code`, `name`, `is_default`, `sort_order`) VALUES
(1, 'tr', 'Türkçe', 1, 0),
(2, 'en', 'English', 0, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `ran_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`, `ran_at`) VALUES
(1, '2025_01_01_000001_create_search_and_features_tables', 1, '2026-02-22 03:08:51'),
(2, '2025_01_01_000002_add_soft_delete_and_edit_columns', 2, '2026-02-22 03:09:13'),
(3, '2025_01_01_000003_add_private_topic_and_edit_timeout', 3, '2026-02-22 03:31:29'),
(4, '2025_01_01_000004_create_import_tables', 4, '2026-02-22 04:42:21'),
(5, '2025_01_01_000005_create_announcements_tables', 5, '2026-02-22 23:48:35'),
(6, '2025_01_01_000006_create_topic_tags', 6, '2026-02-23 02:04:16'),
(7, '2025_01_01_000007_tags_system', 7, '2026-02-23 02:13:24'),
(8, '2025_01_01_000008_add_approved_at_to_users', 8, '2026-02-23 05:10:44'),
(9, '2026_02_23_000001_create_user_activities_table', 9, '2026-02-23 14:34:26'),
(10, '2026_02_23_000002_create_contact_messages_table', 9, '2026-02-23 14:34:26'),
(11, '2026_02_23_000003_add_image_url_to_forums', 10, '2026-02-23 16:55:31'),
(12, '2026_02_23_000004_question_answer_system', 11, '2026-02-23 17:09:10'),
(13, '2026_02_23_000005_contact_message_replies', 12, '2026-02-24 07:06:15'),
(14, '2026_02_25_000001_storage_s3_and_attachments_driver', 13, '2026-02-26 03:44:39'),
(15, '2026_02_26_000001_add_admin_twofa_to_users', 13, '2026-02-26 03:44:39'),
(16, '2026_02_26_000002_invitations_system', 14, '2026-02-26 11:12:20'),
(17, '2026_02_26_000003_spam_zombie_role_quotas', 15, '2026-02-26 11:35:01'),
(18, '2026_02_26_000004_user_account_close', 16, '2026-02-26 11:52:15'),
(19, '2026_02_26_000005_censorship_protection', 17, '2026-02-26 12:51:35'),
(20, '2026_02_26_000006_smileys', 18, '2026-02-26 13:21:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `url_key` varchar(24) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `data`, `read_at`, `created_at`, `url_key`) VALUES
(1, 1, 'reaction', '{\"url\":\"\\/topic\\/5\",\"from_user_id\":129,\"from_username\":\"kaan\",\"post_id\":57,\"topic_id\":5}', '2026-02-26 20:44:06', '2026-02-26 20:43:31', NULL),
(2, 129, 'reply', '{\"url\":\"\\/topic\\/20\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"topic_id\":20,\"topic_title\":\"Kullanıcı kayıt - Captcha sorunu\"}', '2026-02-28 03:28:38', '2026-02-26 21:20:12', NULL),
(3, 129, 'reply', '{\"url\":\"\\/topic\\/25\",\"from_user_id\":131,\"from_username\":\"slaweally\",\"topic_id\":25,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-28 03:28:38', '2026-02-26 21:43:00', NULL),
(4, 129, 'reaction', '{\"url\":\"\\/topic\\/25\",\"from_user_id\":131,\"from_username\":\"slaweally\",\"post_id\":55,\"topic_id\":25}', '2026-02-28 03:28:38', '2026-02-26 21:43:08', NULL),
(5, 1, 'reaction', '{\"url\":\"\\/topic\\/27\",\"from_user_id\":131,\"from_username\":\"slaweally\",\"post_id\":59,\"topic_id\":27}', '2026-02-27 15:35:01', '2026-02-27 15:32:40', NULL),
(6, 1, 'reaction', '{\"url\":\"\\/topic\\/28\",\"from_user_id\":131,\"from_username\":\"slaweally\",\"post_id\":60,\"topic_id\":28}', '2026-02-27 15:51:31', '2026-02-27 15:35:57', NULL),
(7, 1, 'reputation', '{\"url\":\"\\/member\\/Sinek10\",\"from_user_id\":131,\"from_username\":\"slaweally\",\"value\":1}', '2026-02-27 15:54:46', '2026-02-27 15:36:02', NULL),
(8, 129, 'reply', '{\"url\":\"\\/topic\\/25\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"topic_id\":25,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-28 03:28:38', '2026-02-27 15:55:56', NULL),
(9, 131, 'reaction', '{\"url\":\"\\/topic\\/25\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"post_id\":63,\"topic_id\":25,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-27 15:56:18', '2026-02-27 15:56:01', NULL),
(10, 129, 'reaction', '{\"url\":\"\\/topic\\/25\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"post_id\":55,\"topic_id\":25,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-28 03:28:38', '2026-02-27 15:56:09', NULL),
(11, 1, 'reaction', '{\"url\":\"\\/topic\\/25\",\"from_user_id\":131,\"from_username\":\"slaweally\",\"post_id\":64,\"topic_id\":25,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-27 16:03:57', '2026-02-27 15:56:39', NULL),
(12, 1, 'reply', '{\"url\":\"\\/topic\\/22\",\"from_user_id\":131,\"from_username\":\"slaweally\",\"topic_id\":22,\"topic_title\":\"Kurulum ve güncelleme sistemi\"}', '2026-02-27 15:58:21', '2026-02-27 15:58:13', NULL),
(13, 129, 'reply', '{\"url\":\"\\/topic\\/31\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"topic_id\":31,\"topic_title\":\"SEF Url desteği eklenmeli\"}', '2026-02-28 03:28:38', '2026-02-28 02:07:24', NULL),
(14, 131, 'reaction', '{\"url\":\"\\/topic\\/kurulum-ve-guncelleme-sistemi-22\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"post_id\":65,\"topic_id\":22,\"topic_title\":\"Kurulum ve güncelleme sistemi\"}', NULL, '2026-02-28 02:37:12', NULL),
(15, 1, 'reaction', '{\"url\":\"\\/topic\\/profil-yorumlari-32\",\"from_user_id\":129,\"from_username\":\"kaan\",\"post_id\":72,\"topic_id\":32,\"topic_title\":\"Kullanıcı Profil Yorumları\"}', '2026-02-28 03:44:44', '2026-02-28 03:44:39', NULL),
(16, 1, 'reply', '{\"url\":\"\\/topic\\/konu-dosya-test-ve-etiket-test-29\",\"from_user_id\":129,\"from_username\":\"kaan\",\"topic_id\":29,\"topic_title\":\"Konu dosya test ve Etiket Test\"}', '2026-03-02 00:27:30', '2026-03-01 23:25:10', NULL),
(17, 1, 'reaction', '{\"url\":\"\\/topic\\/konu-dosya-test-ve-etiket-test-29\",\"from_user_id\":129,\"from_username\":\"kaan\",\"post_id\":66,\"topic_id\":29,\"topic_title\":\"Konu dosya test ve Etiket Test\"}', '2026-03-02 00:27:30', '2026-03-01 23:25:16', NULL),
(18, 129, 'reply', '{\"url\":\"\\/topic\\/anti-bump-mesaj-yorum-artirma-sistemi-40\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"topic_id\":40,\"topic_title\":\"Anti Bump Mesaj - yorum artırma sistemi\"}', '2026-03-02 00:11:23', '2026-03-02 00:00:05', NULL),
(19, 129, 'reaction', '{\"url\":\"\\/topic\\/anti-bump-mesaj-yorum-artirma-sistemi-40\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"post_id\":84,\"topic_id\":40,\"topic_title\":\"Anti Bump Mesaj - yorum artırma sistemi\"}', '2026-03-02 00:11:28', '2026-03-02 00:07:01', NULL),
(20, 1, 'reaction', '{\"url\":\"\\/topic\\/anti-bump-mesaj-yorum-artirma-sistemi-40\",\"from_user_id\":129,\"from_username\":\"kaan\",\"post_id\":85,\"topic_id\":40,\"topic_title\":\"Anti Bump Mesaj - yorum artırma sistemi\"}', '2026-03-02 00:27:30', '2026-03-02 00:11:33', NULL),
(21, 1, 'reply', '{\"url\":\"\\/topic\\/soru-cevap-test-konusu-41\",\"from_user_id\":129,\"from_username\":\"kaan\",\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\"}', '2026-03-02 00:27:30', '2026-03-02 00:14:58', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pages`
--

CREATE TABLE `pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(128) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `pages`
--

INSERT INTO `pages` (`id`, `slug`, `title`, `body`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'kurallar', 'Kurallar', '<p>MegaforBB Forum kuralları;</p><ol><li>Forumumuz tamamen yeni olduğu için henüz beta test aşamasındadır dolayısıyla haalar ve eksikleri çıkarınıza fayda salayacak şekilde kullanmanız yasaktır.</li><li>Forum içinde illegal veya argo konuşmak mesaj yazmak insanları kırıcı davranışlar kesinlikle yasaktır.&nbsp;</li><li>Sitede İnsanlara saygılı olmanız ve tüm etik kuralları benimsemeniz beklenmektedir.</li><li>Forum kuarllarında esneklik yapılmaz, Süresiz engellenirsiniz....</li></ol>', 1, '2026-02-20 07:35:59', '2026-02-24 01:34:53');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `permission_definitions`
--

CREATE TABLE `permission_definitions` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(64) NOT NULL,
  `group` varchar(64) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `default_value` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `permission_definitions`
--

INSERT INTO `permission_definitions` (`id`, `key`, `group`, `description`, `default_value`, `created_at`, `updated_at`) VALUES
(1, 'forum.view', 'Genel Forum', 'Forumu ve konuları görüntüleyebilir.', 1, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(2, 'forum.create_thread', 'Genel Forum', 'Yeni konu açabilir.', 1, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(3, 'forum.create_post', 'Genel Forum', 'Konulara yanıt yazabilir.', 1, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(4, 'forum.edit_own_post', 'Genel Forum', 'Kendi mesajlarını düzenleyebilir.', 1, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(5, 'forum.delete_own_post', 'Genel Forum', 'Kendi mesajlarını silebilir.', 1, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(6, 'mod.edit_post', 'Forum Moderasyon', 'Başkalarının mesajlarını düzenleyebilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(7, 'mod.delete_post', 'Forum Moderasyon', 'Başkalarının mesajlarını silebilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(8, 'mod.delete_thread', 'Forum Moderasyon', 'Konuları silebilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(9, 'mod.lock_thread', 'Forum Moderasyon', 'Konuları kilitleyebilir / açabilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(10, 'mod.move_thread', 'Forum Moderasyon', 'Konuları başka foruma taşıyabilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(11, 'mod.stick_thread', 'Forum Moderasyon', 'Konuları sabitleyebilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(12, 'admin.manage_users', 'Yönetim (Admin)', 'Kullanıcıları yönetebilir, yasaklayabilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(13, 'admin.manage_roles', 'Yönetim (Admin)', 'Grup ve rolleri yönetebilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(14, 'admin.manage_forums', 'Yönetim (Admin)', 'Kategori ve forumları yönetebilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41'),
(15, 'admin.manage_settings', 'Yönetim (Admin)', 'Genel sistem ayarlarını değiştirebilir.', 0, '2026-02-20 11:49:41', '2026-02-20 11:49:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `polls`
--

CREATE TABLE `polls` (
  `id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `question` varchar(500) NOT NULL,
  `max_votes` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `allow_change_vote` tinyint(1) NOT NULL DEFAULT 0,
  `closes_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `polls`
--

INSERT INTO `polls` (`id`, `topic_id`, `question`, `max_votes`, `allow_change_vote`, `closes_at`, `created_at`) VALUES
(1, 24, 'Megaforbb için Tema motoru seçimi ?', 1, 0, NULL, '2026-02-24 15:08:04');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `poll_options`
--

CREATE TABLE `poll_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `poll_id` int(10) UNSIGNED NOT NULL,
  `option_text` varchar(500) NOT NULL,
  `vote_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `poll_options`
--

INSERT INTO `poll_options` (`id`, `poll_id`, `option_text`, `vote_count`, `sort_order`) VALUES
(1, 1, 'Blade - Laravel', 1, 0),
(2, 1, 'Twig - Symfony', 1, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `poll_votes`
--

CREATE TABLE `poll_votes` (
  `id` int(10) UNSIGNED NOT NULL,
  `poll_id` int(10) UNSIGNED NOT NULL,
  `option_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `poll_votes`
--

INSERT INTO `poll_votes` (`id`, `poll_id`, `option_id`, `user_id`, `created_at`) VALUES
(1, 1, 2, 1, '2026-02-24 15:08:07'),
(2, 1, 1, 129, '2026-02-24 15:08:58');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `posts`
--

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `body` longtext NOT NULL,
  `body_html` longtext DEFAULT NULL,
  `like_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `net_votes` int(11) NOT NULL DEFAULT 0,
  `is_first_post` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `edited_at` datetime DEFAULT NULL,
  `edited_by` int(10) UNSIGNED DEFAULT NULL,
  `edit_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `url_key` varchar(24) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `posts`
--

INSERT INTO `posts` (`id`, `topic_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(1, 1, 1, '<h2>MegaforBB - Yeni Nesil Forum Scripti</h2><p>MegaforBB, \"<b>Güvenli, Güçlü, maksimum performans</b>\" felsefesiyle tasarlanmıştır. Hiçbir ağır framework kullanılmamış (Zend, Laravel Symfony vs. yoktur). Sistemin kalbini oluşturan Forecor (Özel Çekirdek, symfony ve Laravel\'in belirli kütüphanelerini kullanmıştır) sayesinde milisaniyelik tepki süreleriyle çalışır. Yüz binlerce üyesi ve konusu olan büyük toplulukları, sunucu kaynaklarını sömürmeden kolaylıkla kaldırabilmesi için özel olarak optimize edilmiştir. Tasarımda günümüz modern standartlarını baz alan TailwindCSS (v3) ve dinamik kullanıcı etkileşimleri için Alpine.js (gerekli yerlerde native vanilla JS) kullanılmıştır. Mobil uyumluluk sonradan eklenmiş bir yama değil, sistemin temel yapıtaşlarından biridir (%100 Responsive).&nbsp; %100 Özel Mimari, PHP 8.2+, modern TailwindCSS kullanılarak sıfırdan kodlanan, ultra hızlı, güvenlikli ve SEO uyumlu forum sistemi.</p><div contenteditable=\"false\"><hr></div><p>Diğer forum sistemlerinden ayıran bir çok özellik mevcuttur, Diğerlerinde Modül - Eklenti ile gelen gerekli ve önemli sistem parçacıkları bizde direkt olarak forumun bir parçası olarak geliyor. Sisteme herhangi bir yük bindirimemek için optimize edildi.</p><p><br></p><p>Diğer forum sistemlerinden import işlemi ile içeri aktarma veya taşınma yapılabilr (Şu anda sadece xenforo ve Mybb) destekliyoruz.&nbsp;</p><p>Forum sistemimiz ana giriş rotası Forum, Portal ve Makale olmak üzere 3 farklı seçim yapılabilir bunu farklı kullanıcı türleri için özel olarak yapılandırdık. kullanıp kullnmamak tamamen kullanıcının isteğine bağlı.</p><p>Kategori bazlı veya genel atanabilen \"Konu Ön Ekleri\". Kırmızı bir \"Satıldı\" veya yeşil bir \"Soru\" tagı oluşturup CSS atamalarını doğrudan Admin panelinden yapabilirsiniz. Seçilen prefixler hem konu listelerinde hem konu başlıklarında renkli badge (rozet) olarak sergilenir.</p><p>Klasik Beğeni (+1 Like) butonuna ek olarak kullanıcılara +Rep / -Rep verebilme (Gerekçeli, yorumlu şekilde). Belirli Rep ve Post sayısına ulaşan kullanıcılara otomatik olarak Admin\'in belirlediği rütbe ve Nişanların (Rozetlerin) atanması sistemi.</p><p>XSS açıklarına sıfır tolerans! Geliştirilmiş core_sanitize_html filtresi ile zararlı kod enjeksiyonları HTML formatlama sırasında engellenir. CSRF tokenleri her form için unique yaratılır. PDO Prepared Statements sayesinde SQL Injection imkansızdır. Sadece klasik \"konu aç cevapla\" değil. Konular \"Soru\" tipinde açılabilir. Soru tipli başlıklarda kullanıcılar yararlı buldukları mesajlara StackOverflow\'daki gibi Yukarı/Aşağı (Upvote/Downvote) verebilir ve konu sahibi (Veya yetkili) doğru cevabı \"Çözüm olarak işaretle\" diyebilir.</p><p><br></p>', '<h2>MegaforBB - Yeni Nesil Forum Scripti</h2><p>MegaforBB, \"<b>Güvenli, Güçlü, maksimum performans</b>\" felsefesiyle tasarlanmıştır. Hiçbir ağır framework kullanılmamış (Zend, Laravel Symfony vs. yoktur). Sistemin kalbini oluşturan Forecor (Özel Çekirdek, symfony ve Laravel\'in belirli kütüphanelerini kullanmıştır) sayesinde milisaniyelik tepki süreleriyle çalışır. Yüz binlerce üyesi ve konusu olan büyük toplulukları, sunucu kaynaklarını sömürmeden kolaylıkla kaldırabilmesi için özel olarak optimize edilmiştir. Tasarımda günümüz modern standartlarını baz alan TailwindCSS (v3) ve dinamik kullanıcı etkileşimleri için Alpine.js (gerekli yerlerde native vanilla JS) kullanılmıştır. Mobil uyumluluk sonradan eklenmiş bir yama değil, sistemin temel yapıtaşlarından biridir (%100 Responsive).&nbsp; %100 Özel Mimari, PHP 8.2+, modern TailwindCSS kullanılarak sıfırdan kodlanan, ultra hızlı, güvenlikli ve SEO uyumlu forum sistemi.</p><div contenteditable=\"false\"><hr></div><p>Diğer forum sistemlerinden ayıran bir çok özellik mevcuttur, Diğerlerinde Modül - Eklenti ile gelen gerekli ve önemli sistem parçacıkları bizde direkt olarak forumun bir parçası olarak geliyor. Sisteme herhangi bir yük bindirimemek için optimize edildi.</p><p><br></p><p>Diğer forum sistemlerinden import işlemi ile içeri aktarma veya taşınma yapılabilr (Şu anda sadece xenforo ve Mybb) destekliyoruz.&nbsp;</p><p>Forum sistemimiz ana giriş rotası Forum, Portal ve Makale olmak üzere 3 farklı seçim yapılabilir bunu farklı kullanıcı türleri için özel olarak yapılandırdık. kullanıp kullnmamak tamamen kullanıcının isteğine bağlı.</p><p>Kategori bazlı veya genel atanabilen \"Konu Ön Ekleri\". Kırmızı bir \"Satıldı\" veya yeşil bir \"Soru\" tagı oluşturup CSS atamalarını doğrudan Admin panelinden yapabilirsiniz. Seçilen prefixler hem konu listelerinde hem konu başlıklarında renkli badge (rozet) olarak sergilenir.</p><p>Klasik Beğeni (+1 Like) butonuna ek olarak kullanıcılara +Rep / -Rep verebilme (Gerekçeli, yorumlu şekilde). Belirli Rep ve Post sayısına ulaşan kullanıcılara otomatik olarak Admin\'in belirlediği rütbe ve Nişanların (Rozetlerin) atanması sistemi.</p><p>XSS açıklarına sıfır tolerans! Geliştirilmiş core_sanitize_html filtresi ile zararlı kod enjeksiyonları HTML formatlama sırasında engellenir. CSRF tokenleri her form için unique yaratılır. PDO Prepared Statements sayesinde SQL Injection imkansızdır. Sadece klasik \"konu aç cevapla\" değil. Konular \"Soru\" tipinde açılabilir. Soru tipli başlıklarda kullanıcılar yararlı buldukları mesajlara StackOverflow\'daki gibi Yukarı/Aşağı (Upvote/Downvote) verebilir ve konu sahibi (Veya yetkili) doğru cevabı \"Çözüm olarak işaretle\" diyebilir.</p><p><br></p>', 0, 0, 1, '2026-02-23 04:11:06', '2026-02-28 03:47:25', '2026-02-28 03:47:25', 1, 11, NULL, NULL, NULL),
(2, 2, 1, '<p>MegaforBB kurulumu için, Kullanım türüne bağlı olacak şekilde ilgili sunucunuzun Apache, nginx vb özel yapılandırma yapmanız gerekmektedir. Örnek olarak nginx için aşağıda ekstra kural seti verilmiştir.&nbsp; Diğer kısımlarda sadece veritabanını ve sistem dosyalarını \r\nindirip FTP\'ye yükleyip .env dosyasından birbirine bağlamanız yeterli \r\nolacaktır.</p><p><br></p><p>Nginx için ekstra kural seti:&nbsp;</p><p><br></p><p><br></p>\r\n\r\n<pre>location ^~ /theme-assets/ {\r\n  {{varnish_proxy_pass}}\r\n  proxy_set_header Host $host;\r\n  proxy_set_header X-Forwarded-Host $host;\r\n  proxy_set_header X-Real-IP $remote_addr;\r\n  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\r\n  proxy_set_header X-Forwarded-Proto $scheme;\r\n  proxy_hide_header X-Varnish;\r\n  proxy_redirect off;\r\n  proxy_max_temp_file_size 0;\r\n  proxy_connect_timeout 720;\r\n  proxy_send_timeout 720;\r\n  proxy_read_timeout 720;\r\n  proxy_buffer_size 128k;\r\n  proxy_buffers 4 256k;\r\n  proxy_busy_buffers_size 256k;\r\n  proxy_temp_file_write_size 256k;\r\n}\r\n</pre>\r\n\r\nBu Nginx kurallarını uygulamanız gerekmektedir, css ve js dosyalarının sorunsuz çalışması için.', '<p>MegaforBB kurulumu için, Kullanım türüne bağlı olacak şekilde ilgili sunucunuzun Apache, nginx vb özel yapılandırma yapmanız gerekmektedir. Örnek olarak nginx için aşağıda ekstra kural seti verilmiştir.&nbsp; Diğer kısımlarda sadece veritabanını ve sistem dosyalarını \r\nindirip FTP\'ye yükleyip .env dosyasından birbirine bağlamanız yeterli \r\nolacaktır.</p><p><br></p><p>Nginx için ekstra kural seti:&nbsp;</p><p><br></p><p><br></p>\r\n\r\n<pre>location ^~ /theme-assets/ {\r\n  {{varnish_proxy_pass}}\r\n  proxy_set_header Host $host;\r\n  proxy_set_header X-Forwarded-Host $host;\r\n  proxy_set_header X-Real-IP $remote_addr;\r\n  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\r\n  proxy_set_header X-Forwarded-Proto $scheme;\r\n  proxy_hide_header X-Varnish;\r\n  proxy_redirect off;\r\n  proxy_max_temp_file_size 0;\r\n  proxy_connect_timeout 720;\r\n  proxy_send_timeout 720;\r\n  proxy_read_timeout 720;\r\n  proxy_buffer_size 128k;\r\n  proxy_buffers 4 256k;\r\n  proxy_busy_buffers_size 256k;\r\n  proxy_temp_file_write_size 256k;\r\n}\r\n</pre>\r\n\r\nBu Nginx kurallarını uygulamanız gerekmektedir, css ve js dosyalarının sorunsuz çalışması için.', 0, 0, 1, '2026-02-23 04:15:08', '2026-02-24 01:14:03', '2026-02-24 01:14:03', 1, 3, NULL, NULL, NULL),
(3, 1, 129, '<p>Hayırlı olsun, Nice güzel yeniliklerle yeni sürümlere inşallah.</p>', '<p>Hayırlı olsun, Nice güzel yeniliklerle yeni sürümlere inşallah.</p>', 1, 0, 0, '2026-02-23 06:31:13', '2026-02-23 06:50:51', NULL, NULL, 0, NULL, NULL, NULL),
(10, 5, 1, 'MegaforBB Haftalık ufak minr güncellemelerini burada paylaşacağız.', 'MegaforBB Haftalık ufak minr güncellemelerini burada paylaşacağız.', 0, 0, 1, '2026-02-21 07:04:46', '2026-02-23 18:11:52', NULL, NULL, 0, NULL, NULL, NULL),
(35, 14, 1, '<p>MegaforBB Seo için özel geliştirilmiş Schema ve url seti kuralları vardır.&nbsp;</p><p><br></p><p>Şu anda Sistemde halihazırsa seo uyumlu link yapısı mevcuttur ve aktiftir, schema yapısı ise json yapısında ilerleyen dönemde paylaşılacaktır.&nbsp;</p><p><br></p><p>Tüm sistemin schema kurallarını ilerleyen dönemde paylaşacağız.</p>', '<p>MegaforBB Seo için özel geliştirilmiş Schema ve url seti kuralları vardır.&nbsp;</p><p><br></p><p>Şu anda Sistemde halihazırsa seo uyumlu link yapısı mevcuttur ve aktiftir, schema yapısı ise json yapısında ilerleyen dönemde paylaşılacaktır.&nbsp;</p><p><br></p><p>Tüm sistemin schema kurallarını ilerleyen dönemde paylaşacağız.</p>', 0, 0, 1, '2026-02-09 07:04:46', '2026-02-24 00:33:46', '2026-02-23 18:16:44', 1, 1, NULL, NULL, NULL),
(36, 15, 1, '<p>Merhabalar.</p><p>MegaforBB Forum sisteminin Tasarım ve tema üzerindeki hataları, değişiklik istekleri güncellemeleri ve sorunlarını bu post altında bildirimlerde buşunabilirsiniz.</p><p><br></p><p><font color=\"#ff0000\">Not: Absürt ve kişisel istekler değerlendirilmeyecektir.</font></p>', '<p>Merhabalar.</p><p>MegaforBB Forum sisteminin Tasarım ve tema üzerindeki hataları, değişiklik istekleri güncellemeleri ve sorunlarını bu post altında bildirimlerde buşunabilirsiniz.</p><p><br></p><p>Not: Absürt ve kişisel istekler değerlendirilmeyecektir.</p>', 0, 0, 1, '2026-02-10 07:04:46', '2026-02-24 00:34:07', '2026-02-23 18:07:46', 1, 1, NULL, NULL, NULL),
(45, 19, 1, '<p>Merhabalar, Bu konuda @kaan ile birlikte kullanıcı ve etiket sistemini test ediyoruz.</p><p>Post etiket: yüzlerce cevaba ulaşan konuların içinden hangi mesajdan bahsettiğinizi alıntı yapmadan etiket ile belirtme sistemidir. </p><p><br></p><p>Bu etiket sistemi Temel etiket sisteminden farklıdır bu sadece konu içindeki mesajların ID değerleri ile çalışmaktadır, ve sadece içinde bulunduğu konuya ait link verir.</p>', '<p>Merhabalar, Bu konuda <a href=\"/member/kaan\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"kaan\">@kaan</a> ile birlikte kullanıcı ve etiket sistemini test ediyoruz.</p><p>Post etiket: yüzlerce cevaba ulaşan konuların içinden hangi mesajdan bahsettiğinizi alıntı yapmadan etiket ile belirtme sistemidir. </p><p><br></p><p>Bu etiket sistemi Temel etiket sisteminden farklıdır bu sadece konu içindeki mesajların ID değerleri ile çalışmaktadır, ve sadece içinde bulunduğu konuya ait link verir.</p>', 0, 0, 1, '2026-02-24 00:54:25', '2026-02-24 10:40:14', '2026-02-24 00:55:52', 1, 1, NULL, NULL, NULL),
(46, 19, 129, '<p>Örnek bir cevap vererek Etiket sisteminin anlatılması ve @slaweally ile deniyoruz.&nbsp;</p><p>Bu mesajın #2 yaparak etiketlemiş olabiliriz.</p><p><br></p><p>/*-------------------------------------------*/</p><p><br></p><p>Sorun çözüldü, Etiket ve Ment. sistemi mükemmel.</p><p>#2 yaparak ve @Sinek10 yaparak görebiliriz</p>', '<p>Örnek bir cevap vererek Etiket sisteminin anlatılması ve @slaweally ile deniyoruz.&nbsp;</p><p>Bu mesajın <a href=\"/topic/19/post-by-pos/2\" class=\"post-ref inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors\" data-topic-id=\"19\" data-post-pos=\"2\">#2</a> yaparak etiketlemiş olabiliriz.</p><p><br></p><p>/*-------------------------------------------*/</p><p><br></p><p>Sorun çözüldü, Etiket ve Ment. sistemi mükemmel.</p><p><a href=\"/topic/19/post-by-pos/2\" class=\"post-ref inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors\" data-topic-id=\"19\" data-post-pos=\"2\">#2</a> yaparak ve <a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a> yaparak görebiliriz</p>', 1, 0, 0, '2026-02-24 00:57:31', '2026-02-24 10:40:14', '2026-02-24 04:32:41', 1, 1, NULL, NULL, NULL),
(47, 20, 129, '<p>Kullanıcı kayıt sırasında Captcha doğrulaması gerçekleştiği halde hata veriyor Captcha doğrulaması başarısız hatası basıyor.</p><p>Sorunun temeli Cloudflare\'mı yoksa sistemdeen mi kaynaklanıyor bilmiyorum, incelenmesi gerekiyor. @Sinek10</p>', '<p>Kullanıcı kayıt sırasında Captcha doğrulaması gerçekleştiği halde hata veriyor Captcha doğrulaması başarısız hatası basıyor.</p><p>Sorunun temeli Cloudflare\'mı yoksa sistemdeen mi kaynaklanıyor bilmiyorum, incelenmesi gerekiyor. <a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a></p>', 0, 0, 1, '2026-02-24 03:54:41', '2026-02-24 04:24:04', '2026-02-24 04:24:04', 1, 4, NULL, NULL, NULL),
(48, 19, 1, '<p>Test bir #2 ve @Sinek10</p>', '<p>Test bir <a href=\"/topic/19/post-by-pos/2\" class=\"post-ref inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors\" data-topic-id=\"19\" data-post-pos=\"2\">#2</a> ve <a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a></p>', 0, 0, 0, '2026-02-24 04:31:31', '2026-02-24 10:40:14', NULL, NULL, 0, NULL, NULL, NULL),
(49, 21, 129, '<p>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor.&nbsp;</p><p>@Sinek10</p>', '<p>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor.&nbsp;</p><p><a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a></p>', 0, 0, 1, '2026-02-24 04:41:16', '2026-02-24 04:41:16', NULL, NULL, 0, NULL, NULL, NULL),
(50, 21, 1, '<p><img style=\"width: 1480px;\" src=\"/uploads/images/2026/02/fd347efa7e0da350.png\"></p><p><br></p><p></p><blockquote class=\"border-l-4 border-blue-500 pl-3 py-2 my-2 bg-blue-50 rounded-r text-blue-900\" data-author=\"kaan\"><p><strong>kaan yazdı:</strong><br>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor. @Sinek10</p></blockquote><p><br></p><p>Sorun çözülmüştür, Aslında çalışıyor ancak tema\'da göstermemişiz, hallettik.</p><p></p>', '<p><img style=\"width: 1480px;\" src=\"/uploads/images/2026/02/fd347efa7e0da350.png\"></p><p><br></p><p></p><blockquote class=\"border-l-4 border-blue-500 pl-3 py-2 my-2 bg-blue-50 rounded-r text-blue-900\" data-author=\"kaan\"><p><strong>kaan yazdı:</strong><br>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor. <a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a></p></blockquote><p><br></p><p>Sorun çözülmüştür, Aslında çalışıyor ancak tema\'da göstermemişiz, hallettik.</p><p></p>', 1, 1, 0, '2026-02-24 04:44:44', '2026-02-24 10:52:11', '2026-02-24 10:14:11', 1, 1, NULL, NULL, NULL),
(51, 22, 1, '<p>MegaforBB Uzaktan kurulum ve güncelleme sistemi gerekli olur mu ve ne derece gereklidir ?</p><p>Örneğin basit bir Wordpress kurulumu gibi olmalı mı yoksa sadece Profesyonellere hitap ettiği için şu anki yeterli olur mu ?</p>', '<p>MegaforBB Uzaktan kurulum ve güncelleme sistemi gerekli olur mu ve ne derece gereklidir ?</p><p>Örneğin basit bir Wordpress kurulumu gibi olmalı mı yoksa sadece Profesyonellere hitap ettiği için şu anki yeterli olur mu ?</p>', 0, 1, 1, '2026-02-24 05:11:28', '2026-02-26 18:45:20', NULL, NULL, 0, NULL, NULL, NULL),
(52, 23, 1, '<p>Konu düzenleme geçmişinde sınırsız geçmiş verisi tutuluyor, buna bir çözüm bulmak gerekiyor.<br>Acaba son 3 düzenlemeyi mi saklamak mantıklı yoksa sürüm olarak ilk mesaj son mesaj saklamak mı mantıklı ? Düşünüp uygulamaya koyacağız...</p>', '<p>Konu düzenleme geçmişinde sınırsız geçmiş verisi tutuluyor, buna bir çözüm bulmak gerekiyor.<br>Acaba son 3 düzenlemeyi mi saklamak mantıklı yoksa sürüm olarak ilk mesaj son mesaj saklamak mı mantıklı ? Düşünüp uygulamaya koyacağız...</p>', 0, 0, 1, '2026-02-24 10:50:52', '2026-02-24 10:50:52', NULL, NULL, 0, NULL, NULL, NULL),
(53, 23, 1, '<p>Son 3 değişiklik mantıklı olarak uygulandı.\r\n\r\n</p><p><br></p><p>Konu Düzenleme Loglarının (Geçmişinin) Limitlenmesi: Artık bir yorum ne kadar çok düzenlenirse düzenlensin, veritabanının şişmesini önlemek için yalnızca son 3 değişikliği sunucu üzerinde (veritabanı post_edits tablosunda) tutulacaktır. Mesaj her düzenlendiğinde arkada bu limit kontrol edilecek ve eğer 3\'ten fazla geçmiş kalıntı varsa en eskisi silinerek yer açılacaktır. (Bkz: TopicController::updatePost)</p>', '<p>Son 3 değişiklik mantıklı olarak uygulandı.\r\n\r\n</p><p><br></p><p>Konu Düzenleme Loglarının (Geçmişinin) Limitlenmesi: Artık bir yorum ne kadar çok düzenlenirse düzenlensin, veritabanının şişmesini önlemek için yalnızca son 3 değişikliği sunucu üzerinde (veritabanı post_edits tablosunda) tutulacaktır. Mesaj her düzenlendiğinde arkada bu limit kontrol edilecek ve eğer 3\'ten fazla geçmiş kalıntı varsa en eskisi silinerek yer açılacaktır. (Bkz: TopicController::updatePost)</p>', 0, 0, 0, '2026-02-24 11:03:52', '2026-02-24 11:04:44', '2026-02-24 11:04:44', 1, 1, NULL, NULL, NULL),
(54, 24, 1, '<p>Sistemimizde şu anda spagetti düz php ile tema sistemi var ancak bu sistemin ileri dönük güncelleme ve yeni tema geliştirilmesi konusunda çekincelerim var o nedenle de twig veya blade sistemi düşünüyorum acaba hangisi daha mantıklı olur blade mi twig mi ?</p><p><br></p><p>Twig symfony kullanıcılarının sevdiği ve aşina olduğu sistem, Blade\'de tam tersi laravel tarafının sevdiği sistem.&nbsp;</p><p>Tarafını seç :)</p>', '<p>Sistemimizde şu anda spagetti düz php ile tema sistemi var ancak bu sistemin ileri dönük güncelleme ve yeni tema geliştirilmesi konusunda çekincelerim var o nedenle de twig veya blade sistemi düşünüyorum acaba hangisi daha mantıklı olur blade mi twig mi ?</p><p><br></p><p>Twig symfony kullanıcılarının sevdiği ve aşina olduğu sistem, Blade\'de tam tersi laravel tarafının sevdiği sistem.&nbsp;</p><p>Tarafını seç :)</p>', 0, 0, 1, '2026-02-24 15:08:04', '2026-02-24 15:08:04', NULL, NULL, 0, NULL, NULL, NULL),
(55, 25, 129, '<p>Bildirim sisteminde ufak bir hata var, Bildirimleri okundu işaretle - Tümünü okundu işaretle yapamıyoruz dolayısıyla belirli aralıklarla bildirimler gösteriliyor sağ üstte.</p><p>Sorun teşkil edecek birşey değil ama düzeltilmesi iyi olur.</p>', '<p>Bildirim sisteminde ufak bir hata var, Bildirimleri okundu işaretle - Tümünü okundu işaretle yapamıyoruz dolayısıyla belirli aralıklarla bildirimler gösteriliyor sağ üstte.</p><p>Sorun teşkil edecek birşey değil ama düzeltilmesi iyi olur.</p>', 2, 0, 1, '2026-02-24 21:05:34', '2026-02-27 15:56:09', NULL, NULL, 0, NULL, NULL, NULL),
(56, 26, 1, '<h2>Sansür Koruma Sistemi</h2><p>Sansür koruma sistemi yeni pakette yerleşik olarak sisteme entegre edilmiştir. </p><p><br></p><p><strong>yasak kelimeler</strong>, <strong>yasak kullanıcı adları</strong> ve <strong>geçici e-posta (temp mail) koruması</strong> anlatılmaktadır.</p><p><br></p><ul><li><p><code data-backticks=\"1\">blocked_words</code> — Engellenecek kelimeler (opsiyonel replacement, regex destekli)</p></li><li><p><code data-backticks=\"1\">blocked_usernames</code> — Engellenecek kullanıcı adları (tam eşleşme veya regex)</p></li><li><p><code data-backticks=\"1\">blocked_email_domains</code> — Temp mail domain listesi (kayıtta e-posta domaini kontrolü)</p></li><li><p>İlgili <code data-backticks=\"1\">settings</code> anahtarları ve varsayılan temp mail domain listesi</p></li></ul><h2>Admin Paneli</h2><p><strong>Güvenlik → Sansür koruma</strong> menüsünden:</p><ol><li><p><strong>Yasak kelimeler</strong> — Kelime veya ifade ekleyin. \"Regex\" işaretlenirse PCRE kalıbı kullanılır. \"Değişim\" alanı sadece <strong>Değiştir</strong> modunda kullanılır.</p></li><li><p><strong>Yasak kullanıcı adları</strong> — Kayıtta kabul edilmeyecek kullanıcı adları (tam veya regex).</p></li><li><p><strong>Temp mail domainleri</strong> — Sadece domain adı girin (örn: <code data-backticks=\"1\">tempmail.com</code>). Bu domainlere sahip e-postalarla kayıt engellenir.</p></li><li><p><strong>Ayarlar</strong> — Sansürü aç/kapa, kelime eşleşmesinde \"Engelle\" veya \"Değiştir\", mesaj/başlık/imza alanlarına uygulama seçenekleri, yasak kullanıcı adı ve temp mail kontrollerinin açık olup olmaması.</p></li></ol><h2>Davranış</h2><ul><li><p><strong>Kayıt:</strong> Kullanıcı adı yasak listede veya e-posta domaini temp mail listesindeyse kayıt reddedilir.</p></li><li><p><strong>Konu açma / cevap / düzenleme:</strong> Sansür açıksa ve ilgili alanlar seçiliyse, mesaj/başlık kontrol edilir. <strong>Engelle</strong> modunda yasak kelime varsa içerik kabul edilmez. <strong>Değiştir</strong> modunda eşleşen kelimeler replacement ile değiştirilir ve kaydedilir.</p></li><li><p><strong>İmza:</strong> Profil düzenlemede imza da (ayar açıksa) aynı kelime filtresine tabidir.</p></li><li><p><strong>Makale:</strong> Makale başlığı ve içeriği de (ayar açıksa) kelime sansürüne tabidir.</p></li></ul><p>Listeler ve ayarlar önbelleklenir; değişiklik sonrası önbellek otomatik temizlenir.</p><h2>Dil Anahtarları</h2><ul><li><p><code data-backticks=\"1\">censorship.content_blocked</code> — İçerik reddedildiğinde</p></li><li><p><code data-backticks=\"1\">censorship.username_blocked</code> — Yasak kullanıcı adı</p></li><li><p><code data-backticks=\"1\">censorship.temp_mail_blocked</code> — Temp mail engeli</p></li><li><p><code data-backticks=\"1\">admin.censorship.*</code> — Admin paneli metinleri (TR/EN)</p></li></ul>', '<h2>Sansür Koruma Sistemi</h2><p>Sansür koruma sistemi yeni pakette yerleşik olarak sisteme entegre edilmiştir. </p><p><br></p><p><strong>yasak kelimeler</strong>, <strong>yasak kullanıcı adları</strong> ve <strong>geçici e-posta (temp mail) koruması</strong> anlatılmaktadır.</p><p><br></p><ul><li><p><code data-backticks=\"1\">blocked_words</code> — Engellenecek kelimeler (opsiyonel replacement, regex destekli)</p></li><li><p><code data-backticks=\"1\">blocked_usernames</code> — Engellenecek kullanıcı adları (tam eşleşme veya regex)</p></li><li><p><code data-backticks=\"1\">blocked_email_domains</code> — Temp mail domain listesi (kayıtta e-posta domaini kontrolü)</p></li><li><p>İlgili <code data-backticks=\"1\">settings</code> anahtarları ve varsayılan temp mail domain listesi</p></li></ul><h2>Admin Paneli</h2><p><strong>Güvenlik → Sansür koruma</strong> menüsünden:</p><ol><li><p><strong>Yasak kelimeler</strong> — Kelime veya ifade ekleyin. \"Regex\" işaretlenirse PCRE kalıbı kullanılır. \"Değişim\" alanı sadece <strong>Değiştir</strong> modunda kullanılır.</p></li><li><p><strong>Yasak kullanıcı adları</strong> — Kayıtta kabul edilmeyecek kullanıcı adları (tam veya regex).</p></li><li><p><strong>Temp mail domainleri</strong> — Sadece domain adı girin (örn: <code data-backticks=\"1\">tempmail.com</code>). Bu domainlere sahip e-postalarla kayıt engellenir.</p></li><li><p><strong>Ayarlar</strong> — Sansürü aç/kapa, kelime eşleşmesinde \"Engelle\" veya \"Değiştir\", mesaj/başlık/imza alanlarına uygulama seçenekleri, yasak kullanıcı adı ve temp mail kontrollerinin açık olup olmaması.</p></li></ol><h2>Davranış</h2><ul><li><p><strong>Kayıt:</strong> Kullanıcı adı yasak listede veya e-posta domaini temp mail listesindeyse kayıt reddedilir.</p></li><li><p><strong>Konu açma / cevap / düzenleme:</strong> Sansür açıksa ve ilgili alanlar seçiliyse, mesaj/başlık kontrol edilir. <strong>Engelle</strong> modunda yasak kelime varsa içerik kabul edilmez. <strong>Değiştir</strong> modunda eşleşen kelimeler replacement ile değiştirilir ve kaydedilir.</p></li><li><p><strong>İmza:</strong> Profil düzenlemede imza da (ayar açıksa) aynı kelime filtresine tabidir.</p></li><li><p><strong>Makale:</strong> Makale başlığı ve içeriği de (ayar açıksa) kelime sansürüne tabidir.</p></li></ul><p>Listeler ve ayarlar önbelleklenir; değişiklik sonrası önbellek otomatik temizlenir.</p><h2>Dil Anahtarları</h2><ul><li><p><code data-backticks=\"1\">censorship.content_blocked</code> — İçerik reddedildiğinde</p></li><li><p><code data-backticks=\"1\">censorship.username_blocked</code> — Yasak kullanıcı adı</p></li><li><p><code data-backticks=\"1\">censorship.temp_mail_blocked</code> — Temp mail engeli</p></li><li><p><code data-backticks=\"1\">admin.censorship.*</code> — Admin paneli metinleri (TR/EN)</p></li></ul>', 0, 0, 1, '2026-02-26 20:39:11', '2026-02-26 20:39:11', NULL, NULL, 0, NULL, NULL, NULL),
(57, 5, 1, '<h2>Haftalık güncelleme işlemleri</h2><p><br></p><ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Sansür sistemi geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>MegaforBB - MegaforBB veritabanı import sistemi geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>XenForo v2.2.13  to MegaforBB import aracı geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>MyBB 1.8.39 İmport aracı geliştirildi.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Composer install - update sistemi geliştirildi.</p></li></ul>', '<h2>Haftalık güncelleme işlemleri</h2><p><br></p><ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Sansür sistemi geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>MegaforBB - MegaforBB veritabanı import sistemi geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>XenForo v2.2.13  to MegaforBB import aracı geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>MyBB 1.8.39 İmport aracı geliştirildi.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Composer install - update sistemi geliştirildi.</p></li></ul>', 1, 0, 0, '2026-02-26 20:42:29', '2026-02-26 20:43:31', NULL, NULL, 0, NULL, NULL, NULL),
(58, 15, 1, '<p><br></p><p><strong>Sinek10 yazdı:</strong></p><blockquote><p><br></p><p>Merhabalar.MegaforBB Forum sisteminin Tasarım ve tema üzerindeki hataları, değişiklik istekleri güncellemeleri ve sorunlarını bu post altında bildirimlerde buşunabilirsiniz.Not: Absürt ve kişisel istekler değerlendirilmeyecektir.</p></blockquote><p>Ufak güncellemeler yayınlandı, Forum profil sayfası Footer, ve portal sayfasında güncellemeler yapıldı.</p>', '<p><br></p><p><strong>Sinek10 yazdı:</strong></p><blockquote><p><br></p><p>Merhabalar.MegaforBB Forum sisteminin Tasarım ve tema üzerindeki hataları, değişiklik istekleri güncellemeleri ve sorunlarını bu post altında bildirimlerde buşunabilirsiniz.Not: Absürt ve kişisel istekler değerlendirilmeyecektir.</p></blockquote><p>Ufak güncellemeler yayınlandı, Forum profil sayfası Footer, ve portal sayfasında güncellemeler yapıldı.</p>', 0, 0, 0, '2026-02-26 20:45:19', '2026-02-26 20:45:19', NULL, NULL, 0, NULL, NULL, NULL),
(59, 27, 1, '<h1>Dil Sistemi ve Twig Entegrasyonu</h1><h2>Genel Bakış</h2><p>MegaforBB dil sistemi, <strong>dosya tabanlı çevirileri </strong> <code>lang/{locale}.php</code> üzerinden okur ve <strong>veritabanı override</strong>\'ları ile birleştirir. Uygulama genelinde çeviri çağrıları <code>lang(\'key\')</code> helper\'ı üzerinden yapılır. Twig tarafında aynı helper, <code>TemplateEngine</code> tarafından global fonksiyon olarak kayıtlıdır.</p><ul><li><p>Çeviri helper\'ı: helpers.php</p></li><li><p>Translator servisi: Translator.php</p></li><li><p>Twig fonksiyon kayıtları: TemplateEngine.php</p></li></ul><h2>Locale Çözümleme Sırası</h2><p>Uygulama dili, merkezi olarak <code>Application::resolveLocale()</code> içinde belirlenir. Öncelik sırası aşağıdaki gibidir:</p><ol><li><p><strong>Session</strong>: <code>locale</code> key\'i</p></li><li><p><strong>Cookie</strong>: <code>locale</code> key\'i</p></li><li><p><strong>Accept-Language</strong>: tarayıcı dili, dosya varsa</p></li><li><p><strong>Config</strong>: <code>app.locale</code> (varsayılan <code>tr</code>)</p></li></ol><p>Kaynak: Application.php</p><h2>Çeviri Yükleme ve Override Mantığı</h2><p><code>Translator::load()</code> her locale için önce dosya çevirilerini okur, sonra DB çevirilerini ekler. <code>array_merge</code> kullanıldığı için <strong>DB kayıtları aynı anahtarda dosya değerini override eder</strong>.</p><ul><li><p>Dosya: <code>lang/{locale}.php</code></p></li><li><p>DB tablo: <code>language_lines</code></p></li></ul><p>Kaynaklar:</p><ul><li><p>Translator::load</p></li><li><p>LanguageLine::getTranslationsForLocale</p></li></ul><h2>Fallback Locale</h2><p>Translator, fallback dili <code>Language::getDefault()</code> üzerinden belirler; DB hazır değilse <code>tr</code> kullanır. Böylece anahtar bulunamazsa fallback locale denenir; yine yoksa anahtarın kendisi döner.</p><p><br></p><p>Kaynak: Application::translator, Translator::get</p><h2>Anahtar Formatı (Flat Key)</h2><p>Dil dosyaları <strong>flat key</strong> formatındadır; nested array yerine <code>group.key</code> şeklinde string anahtarlar kullanılır.</p><p><br></p><p>Örnekler:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">\'admin.languages.title\' =&gt; \'Dil Yönetimi\',\r\n\'common.login\' =&gt; \'Giriş Yap\',</code></pre></div><p>Kaynak: lang/tr.php</p><h2>Placeholder Desteği</h2><p><code>lang(\'key\', [\'name\' =&gt; \'Ali\'])</code> çağrısında <code>:name</code> placeholder\'ları string içinde replace edilir.</p><p><br></p><p>Kaynak: Translator::get</p><h2>Twig Dosyalarında Kullanım</h2><p>Twig içinde <code>lang()</code> fonksiyonu doğrudan kullanılabilir. <code>TemplateEngine</code> içinde TwigFunction olarak register edilir ve tüm template\'lerde erişilebilir.</p><p><br></p><p>Örnek:</p><div data-language=\"twig\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"twig\">&lt;h2 class=\"page-title\"&gt;{{ lang(\'admin.languages.title\')|e }}&lt;/h2&gt;</code></pre></div><p>Kaynak:</p><ul><li><p>TemplateEngine::registerFunctions</p></li><li><p>languages/index.html.twig</p></li></ul><h2>PHP Tarafında Kullanım</h2><p>Controller veya servislerde doğrudan <code>lang()</code> helper\'ı çağrılır. Helper, mevcut <code>Application</code> instance üzerinden <code>translator()-&gt;get()</code> çağırır.</p><p><br></p><p>Örnek:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">return $this-&gt;view(\'languages/index\', [\r\n    \'pageTitle\' =&gt; lang(\'admin.languages.title\'),\r\n]);</code></pre></div><p>Kaynak:</p><ul><li><p>AdminLanguageController::index</p></li><li><p>helpers.php</p></li></ul><h2>Admin Panel Dil Yönetimi</h2><p>Dil yönetimi ekranı, DB ve dosya tabanlı dilleri birleştirir; çeviri sayısı <code>Translator::all()</code> ile hesaplanır. Dil ekleme/düzenleme işlemleri DB tarafında <code>language_lines</code> tablosuna yazılır ve ardından <code>lang/{code}.php</code> dosyasına export edilir.</p><p><br></p><p>Öne çıkan akışlar:</p><ul><li><p><strong>Listeleme</strong>: DB + dosya taraması birleştirilir</p></li><li><p><strong>Yeni dil</strong>: DB kaydı + opsiyonel çeviri kopyalama</p></li><li><p><strong>Düzenleme</strong>: tüm anahtarlar (varsayılan + mevcut) bir arada gösterilir</p></li><li><p><strong>Kaydetme</strong>: DB update + dosyaya export</p></li><li><p><strong>Varsayılan dil</strong>: <code>languages.is_default</code> güncellenir</p></li></ul><p>Kaynak: AdminLanguageController</p><h2>Pratik Notlar</h2><ul><li><p>Dil dosyası yoksa locale, <code>resolveLocale</code> içinde dosya kontrolü ile elenir.</p></li><li><p>Twig cache çıktıları <code>storage/views/</code> altında saklanır; dil değişiminde Twig çıktısı tekrar üretilebilir.</p></li><li><p><code>lang()</code> çağrısı her yerde güvenle kullanılabilir; anahtar bulunamazsa anahtarın kendisi döner.</p></li></ul><p>Kaynaklar:</p><ul><li><p>Application::resolveLocale</p></li><li><p>Translator::get</p></li></ul>', '<h1>Dil Sistemi ve Twig Entegrasyonu</h1><h2>Genel Bakış</h2><p>MegaforBB dil sistemi, <strong>dosya tabanlı çevirileri </strong> <code>lang/{locale}.php</code> üzerinden okur ve <strong>veritabanı override</strong>\'ları ile birleştirir. Uygulama genelinde çeviri çağrıları <code>lang(\'key\')</code> helper\'ı üzerinden yapılır. Twig tarafında aynı helper, <code>TemplateEngine</code> tarafından global fonksiyon olarak kayıtlıdır.</p><ul><li><p>Çeviri helper\'ı: helpers.php</p></li><li><p>Translator servisi: Translator.php</p></li><li><p>Twig fonksiyon kayıtları: TemplateEngine.php</p></li></ul><h2>Locale Çözümleme Sırası</h2><p>Uygulama dili, merkezi olarak <code>Application::resolveLocale()</code> içinde belirlenir. Öncelik sırası aşağıdaki gibidir:</p><ol><li><p><strong>Session</strong>: <code>locale</code> key\'i</p></li><li><p><strong>Cookie</strong>: <code>locale</code> key\'i</p></li><li><p><strong>Accept-Language</strong>: tarayıcı dili, dosya varsa</p></li><li><p><strong>Config</strong>: <code>app.locale</code> (varsayılan <code>tr</code>)</p></li></ol><p>Kaynak: Application.php</p><h2>Çeviri Yükleme ve Override Mantığı</h2><p><code>Translator::load()</code> her locale için önce dosya çevirilerini okur, sonra DB çevirilerini ekler. <code>array_merge</code> kullanıldığı için <strong>DB kayıtları aynı anahtarda dosya değerini override eder</strong>.</p><ul><li><p>Dosya: <code>lang/{locale}.php</code></p></li><li><p>DB tablo: <code>language_lines</code></p></li></ul><p>Kaynaklar:</p><ul><li><p>Translator::load</p></li><li><p>LanguageLine::getTranslationsForLocale</p></li></ul><h2>Fallback Locale</h2><p>Translator, fallback dili <code>Language::getDefault()</code> üzerinden belirler; DB hazır değilse <code>tr</code> kullanır. Böylece anahtar bulunamazsa fallback locale denenir; yine yoksa anahtarın kendisi döner.</p><p><br></p><p>Kaynak: Application::translator, Translator::get</p><h2>Anahtar Formatı (Flat Key)</h2><p>Dil dosyaları <strong>flat key</strong> formatındadır; nested array yerine <code>group.key</code> şeklinde string anahtarlar kullanılır.</p><p><br></p><p>Örnekler:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">\'admin.languages.title\' =&gt; \'Dil Yönetimi\',\r\n\'common.login\' =&gt; \'Giriş Yap\',</code></pre></div><p>Kaynak: lang/tr.php</p><h2>Placeholder Desteği</h2><p><code>lang(\'key\', [\'name\' =&gt; \'Ali\'])</code> çağrısında <code>:name</code> placeholder\'ları string içinde replace edilir.</p><p><br></p><p>Kaynak: Translator::get</p><h2>Twig Dosyalarında Kullanım</h2><p>Twig içinde <code>lang()</code> fonksiyonu doğrudan kullanılabilir. <code>TemplateEngine</code> içinde TwigFunction olarak register edilir ve tüm template\'lerde erişilebilir.</p><p><br></p><p>Örnek:</p><div data-language=\"twig\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"twig\">&lt;h2 class=\"page-title\"&gt;{{ lang(\'admin.languages.title\')|e }}&lt;/h2&gt;</code></pre></div><p>Kaynak:</p><ul><li><p>TemplateEngine::registerFunctions</p></li><li><p>languages/index.html.twig</p></li></ul><h2>PHP Tarafında Kullanım</h2><p>Controller veya servislerde doğrudan <code>lang()</code> helper\'ı çağrılır. Helper, mevcut <code>Application</code> instance üzerinden <code>translator()-&gt;get()</code> çağırır.</p><p><br></p><p>Örnek:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">return $this-&gt;view(\'languages/index\', [\r\n    \'pageTitle\' =&gt; lang(\'admin.languages.title\'),\r\n]);</code></pre></div><p>Kaynak:</p><ul><li><p>AdminLanguageController::index</p></li><li><p>helpers.php</p></li></ul><h2>Admin Panel Dil Yönetimi</h2><p>Dil yönetimi ekranı, DB ve dosya tabanlı dilleri birleştirir; çeviri sayısı <code>Translator::all()</code> ile hesaplanır. Dil ekleme/düzenleme işlemleri DB tarafında <code>language_lines</code> tablosuna yazılır ve ardından <code>lang/{code}.php</code> dosyasına export edilir.</p><p><br></p><p>Öne çıkan akışlar:</p><ul><li><p><strong>Listeleme</strong>: DB + dosya taraması birleştirilir</p></li><li><p><strong>Yeni dil</strong>: DB kaydı + opsiyonel çeviri kopyalama</p></li><li><p><strong>Düzenleme</strong>: tüm anahtarlar (varsayılan + mevcut) bir arada gösterilir</p></li><li><p><strong>Kaydetme</strong>: DB update + dosyaya export</p></li><li><p><strong>Varsayılan dil</strong>: <code>languages.is_default</code> güncellenir</p></li></ul><p>Kaynak: AdminLanguageController</p><h2>Pratik Notlar</h2><ul><li><p>Dil dosyası yoksa locale, <code>resolveLocale</code> içinde dosya kontrolü ile elenir.</p></li><li><p>Twig cache çıktıları <code>storage/views/</code> altında saklanır; dil değişiminde Twig çıktısı tekrar üretilebilir.</p></li><li><p><code>lang()</code> çağrısı her yerde güvenle kullanılabilir; anahtar bulunamazsa anahtarın kendisi döner.</p></li></ul><p>Kaynaklar:</p><ul><li><p>Application::resolveLocale</p></li><li><p>Translator::get</p></li></ul>', 1, 0, 1, '2026-02-26 20:48:33', '2026-02-27 15:32:40', '2026-02-26 20:49:00', 1, 1, NULL, NULL, NULL);
INSERT INTO `posts` (`id`, `topic_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(60, 28, 1, '<p>Bu doküman, kullanıcının <strong>kendi hesabını askıya alması</strong> (geçici) ve <strong>kalıcı kapatması</strong> özelliklerini açıklar. Askıya alınan hesap daha sonra tekrar açılabilir; kapatılan hesap kesinlikle açılamaz.</p><h3>Hesabı askıya al (geçici)</h3><ul><li><p>Kullanıcı profil ayarlarından hesabını <strong>geçici olarak askıya alabilir</strong>.</p></li><li><p>İşlem: Mevcut şifre ile onay → <code data-backticks=\"1\">is_suspended = 1</code>, <code data-backticks=\"1\">suspended_at = now()</code> → oturum kapatılır, giriş sayfasına yönlendirilir.</p></li><li><p><strong>Tekrar açma:</strong> Giriş sayfasında “Hesabımı tekrar aç” bağlantısı veya doğrudan <code data-backticks=\"1\">/reactivate-account</code> sayfası; kullanıcı adı/e-posta + şifre ile hesap tekrar açılır ve giriş yapılır.</p></li></ul><h3> Hesabı kalıcı kapat</h3><ul><li><p>Kullanıcı profil ayarlarından hesabını <strong>kalıcı olarak kapatabilir</strong>.</p></li><li><p>İşlem: Mevcut şifre + onay metni (TR: <strong>KAPAT</strong>, EN: <strong>CLOSE</strong>) ile iki aşamalı onay → <code data-backticks=\"1\">closed_at = now()</code> → oturum kapatılır.</p></li><li><p>Kalıcı kapatılan hesap <strong>tekrar açılamaz</strong>; giriş denemelerinde “Bu hesap kalıcı olarak kapatılmıştır” mesajı gösterilir.</p></li></ul><h2>Davranış (Auth)</h2><h3>Oturum (AuthService::user)</h3><ul><li><p>Giriş yapmış kullanıcı yüklenirken <code data-backticks=\"1\">closed_at !== null</code> ise kullanıcı “giriş yapmamış” kabul edilir (null döner). Böylece kapatılmış hesaplar oturum kullanamaz.</p></li></ul><h3>Giriş (AuthService::login)</h3><p>Sıra ile kontrol:</p><ol><li><p>Kullanıcı yok → <code data-backticks=\"1\">false</code></p></li><li><p><code data-backticks=\"1\">is_banned</code> → <code data-backticks=\"1\">false</code></p></li><li><p><strong><code data-backticks=\"1\">closed_at</code> dolu</strong> → <code data-backticks=\"1\">\'closed\'</code> (kalıcı kapatılmış)</p></li><li><p><strong><code data-backticks=\"1\">is_suspended</code> dolu</strong> → <code data-backticks=\"1\">\'suspended\'</code> (askıda)</p></li><li><p>Şifre hatalı → <code data-backticks=\"1\">false</code></p></li><li><p>E-posta doğrulanmamış / onay bekliyor → ilgili kod</p></li><li><p>Başarılı → oturum açılır, <code data-backticks=\"1\">true</code> döner</p></li></ol><h3>AuthController</h3><ul><li><p><strong>login:</strong> <code data-backticks=\"1\">\'closed\'</code> → <code data-backticks=\"1\">auth.account_closed</code> mesajı, login sayfasına yönlendirme. <code data-backticks=\"1\">\'suspended\'</code> → <code data-backticks=\"1\">auth.account_suspended</code> mesajı + flash ile <code data-backticks=\"1\">auth_show_reactivate</code>; login sayfasında “Hesabımı tekrar aç” linki gösterilir.</p></li><li><p><strong>reactivateForm:</strong> Askıya alınmış hesabı tekrar açma formu (kullanıcı adı/e-posta + şifre).</p></li><li><p><strong>reactivate:</strong> Form POST; <code data-backticks=\"1\">AuthService::reactivateAccount()</code> ile şifre doğrulanır, hesap askıdan çıkarılır (<code data-backticks=\"1\">is_suspended = 0</code>, <code data-backticks=\"1\">suspended_at = null</code>), oturum açılır ve ana sayfaya yönlendirilir.</p></li></ul><h3>Reactivate (AuthService::reactivateAccount)</h3><ul><li><p>Giriş (kullanıcı adı veya e-posta) + şifre ile kullanıcı bulunur.</p></li><li><p><code data-backticks=\"1\">closed_at</code> dolu ise → <code data-backticks=\"1\">\'closed\'</code> (tekrar açılamaz).</p></li><li><p>Askıda değilse → <code data-backticks=\"1\">\'not_suspended\'</code>.</p></li><li><p>Şifre yanlışsa → <code data-backticks=\"1\">false</code>.</p></li><li><p>Uygunsa: <code data-backticks=\"1\">is_suspended = 0</code>, <code data-backticks=\"1\">suspended_at = null</code> güncellenir, oturum açılır, <code data-backticks=\"1\">true</code> döner.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>Profil: Askıya al / Kapat sayfası</h2><h3>Sayfa ve formlar</h3><ul><li><p><strong>GET /profile/account</strong> — “Hesap askıya alma ve kapatma” sayfası (ProfileController::accountForm).</p></li><li><p>İki bölüm:</p><ol><li><p><strong>Hesabı askıya al:</strong> Mevcut şifre, “Hesabı askıya al” butonu. Gönderim öncesi JS ile onay sorulur.</p></li><li><p><strong>Hesabı kalıcı kapat:</strong> Mevcut şifre + onay metni alanı (dil dosyasındaki <code data-backticks=\"1\">profile.close_confirm_phrase</code>: TR “KAPAT”, EN “CLOSE”). Gönderim öncesi JS ile onay sorulur.</p></li></ol></li></ul><h3>İşlemler</h3><ul><li><p><strong>POST /profile/suspend-account</strong> (ProfileController::suspendAccount):</p><ul><li><p>CSRF ve mevcut şifre kontrolü.</p></li><li><p><code data-backticks=\"1\">is_suspended = 1</code>, <code data-backticks=\"1\">suspended_at = now()</code> güncellenir.</p></li><li><p>Çıkış yapılır, login sayfasına <code data-backticks=\"1\">auth_success</code> ile yönlendirilir (suspend_success mesajı).</p></li></ul></li><li><p><strong>POST /profile/close-account</strong> (ProfileController::closeAccount):</p><ul><li><p>CSRF, mevcut şifre ve onay metni (tam eşleşme: <code data-backticks=\"1\">profile.close_confirm_phrase</code>) kontrolü.</p></li><li><p><code data-backticks=\"1\">closed_at = now()</code> güncellenir.</p></li><li><p>Çıkış yapılır, login sayfasına <code data-backticks=\"1\">auth_error</code> ile yönlendirilir (close_success mesajı).</p></li></ul></li></ul><div contenteditable=\"false\"><hr></div><h2>Şablonlar</h2><ul><li><p><strong>profile/account</strong> — <code data-backticks=\"1\">templates/frontend/default/views/profile/account.html.twig</code>Askıya al ve kalıcı kapat kartları, şifre ve onay metni alanları, JS confirm.</p></li><li><p><strong>reactivate-account</strong> — <code data-backticks=\"1\">templates/frontend/default/views/reactivate-account.html.twig</code>Kullanıcı adı/e-posta + şifre formu.</p></li><li><p><strong>profile/edit</strong> — “Hesabı askıya al / Kapat” linki eklendi (<code data-backticks=\"1\">profile/account</code>).</p></li><li><p><strong>login</strong> — Askıda hesap için hata kutusunda <code data-backticks=\"1\">auth_show_reactivate</code> flash’ı varsa “Hesabımı tekrar aç” linki (<code data-backticks=\"1\">reactivate-account</code>) gösterilir.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>Admin panel</h2><ul><li><p><strong>Güvenlik → Spam &amp; Zombie → Askıya alınan kullanıcılar</strong> listesinde:</p><ul><li><p><code data-backticks=\"1\">closed_at</code> dolu kullanıcılar için “Askıyı kaldır” butonu yerine <strong>“Kalıcı kapatıldı”</strong> rozeti gösterilir.</p></li><li><p>Unsuspend işlemi yalnızca <code data-backticks=\"1\">closed_at</code> boş kullanıcılar için yapılır; kalıcı kapatılmış hesap askıdan çıkarılamaz.</p></li></ul></li></ul>', '<p>Bu doküman, kullanıcının <strong>kendi hesabını askıya alması</strong> (geçici) ve <strong>kalıcı kapatması</strong> özelliklerini açıklar. Askıya alınan hesap daha sonra tekrar açılabilir; kapatılan hesap kesinlikle açılamaz.</p><h3>Hesabı askıya al (geçici)</h3><ul><li><p>Kullanıcı profil ayarlarından hesabını <strong>geçici olarak askıya alabilir</strong>.</p></li><li><p>İşlem: Mevcut şifre ile onay → <code data-backticks=\"1\">is_suspended = 1</code>, <code data-backticks=\"1\">suspended_at = now()</code> → oturum kapatılır, giriş sayfasına yönlendirilir.</p></li><li><p><strong>Tekrar açma:</strong> Giriş sayfasında “Hesabımı tekrar aç” bağlantısı veya doğrudan <code data-backticks=\"1\">/reactivate-account</code> sayfası; kullanıcı adı/e-posta + şifre ile hesap tekrar açılır ve giriş yapılır.</p></li></ul><h3> Hesabı kalıcı kapat</h3><ul><li><p>Kullanıcı profil ayarlarından hesabını <strong>kalıcı olarak kapatabilir</strong>.</p></li><li><p>İşlem: Mevcut şifre + onay metni (TR: <strong>KAPAT</strong>, EN: <strong>CLOSE</strong>) ile iki aşamalı onay → <code data-backticks=\"1\">closed_at = now()</code> → oturum kapatılır.</p></li><li><p>Kalıcı kapatılan hesap <strong>tekrar açılamaz</strong>; giriş denemelerinde “Bu hesap kalıcı olarak kapatılmıştır” mesajı gösterilir.</p></li></ul><h2>Davranış (Auth)</h2><h3>Oturum (AuthService::user)</h3><ul><li><p>Giriş yapmış kullanıcı yüklenirken <code data-backticks=\"1\">closed_at !== null</code> ise kullanıcı “giriş yapmamış” kabul edilir (null döner). Böylece kapatılmış hesaplar oturum kullanamaz.</p></li></ul><h3>Giriş (AuthService::login)</h3><p>Sıra ile kontrol:</p><ol><li><p>Kullanıcı yok → <code data-backticks=\"1\">false</code></p></li><li><p><code data-backticks=\"1\">is_banned</code> → <code data-backticks=\"1\">false</code></p></li><li><p><strong><code data-backticks=\"1\">closed_at</code> dolu</strong> → <code data-backticks=\"1\">\'closed\'</code> (kalıcı kapatılmış)</p></li><li><p><strong><code data-backticks=\"1\">is_suspended</code> dolu</strong> → <code data-backticks=\"1\">\'suspended\'</code> (askıda)</p></li><li><p>Şifre hatalı → <code data-backticks=\"1\">false</code></p></li><li><p>E-posta doğrulanmamış / onay bekliyor → ilgili kod</p></li><li><p>Başarılı → oturum açılır, <code data-backticks=\"1\">true</code> döner</p></li></ol><h3>AuthController</h3><ul><li><p><strong>login:</strong> <code data-backticks=\"1\">\'closed\'</code> → <code data-backticks=\"1\">auth.account_closed</code> mesajı, login sayfasına yönlendirme. <code data-backticks=\"1\">\'suspended\'</code> → <code data-backticks=\"1\">auth.account_suspended</code> mesajı + flash ile <code data-backticks=\"1\">auth_show_reactivate</code>; login sayfasında “Hesabımı tekrar aç” linki gösterilir.</p></li><li><p><strong>reactivateForm:</strong> Askıya alınmış hesabı tekrar açma formu (kullanıcı adı/e-posta + şifre).</p></li><li><p><strong>reactivate:</strong> Form POST; <code data-backticks=\"1\">AuthService::reactivateAccount()</code> ile şifre doğrulanır, hesap askıdan çıkarılır (<code data-backticks=\"1\">is_suspended = 0</code>, <code data-backticks=\"1\">suspended_at = null</code>), oturum açılır ve ana sayfaya yönlendirilir.</p></li></ul><h3>Reactivate (AuthService::reactivateAccount)</h3><ul><li><p>Giriş (kullanıcı adı veya e-posta) + şifre ile kullanıcı bulunur.</p></li><li><p><code data-backticks=\"1\">closed_at</code> dolu ise → <code data-backticks=\"1\">\'closed\'</code> (tekrar açılamaz).</p></li><li><p>Askıda değilse → <code data-backticks=\"1\">\'not_suspended\'</code>.</p></li><li><p>Şifre yanlışsa → <code data-backticks=\"1\">false</code>.</p></li><li><p>Uygunsa: <code data-backticks=\"1\">is_suspended = 0</code>, <code data-backticks=\"1\">suspended_at = null</code> güncellenir, oturum açılır, <code data-backticks=\"1\">true</code> döner.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>Profil: Askıya al / Kapat sayfası</h2><h3>Sayfa ve formlar</h3><ul><li><p><strong>GET /profile/account</strong> — “Hesap askıya alma ve kapatma” sayfası (ProfileController::accountForm).</p></li><li><p>İki bölüm:</p><ol><li><p><strong>Hesabı askıya al:</strong> Mevcut şifre, “Hesabı askıya al” butonu. Gönderim öncesi JS ile onay sorulur.</p></li><li><p><strong>Hesabı kalıcı kapat:</strong> Mevcut şifre + onay metni alanı (dil dosyasındaki <code data-backticks=\"1\">profile.close_confirm_phrase</code>: TR “KAPAT”, EN “CLOSE”). Gönderim öncesi JS ile onay sorulur.</p></li></ol></li></ul><h3>İşlemler</h3><ul><li><p><strong>POST /profile/suspend-account</strong> (ProfileController::suspendAccount):</p><ul><li><p>CSRF ve mevcut şifre kontrolü.</p></li><li><p><code data-backticks=\"1\">is_suspended = 1</code>, <code data-backticks=\"1\">suspended_at = now()</code> güncellenir.</p></li><li><p>Çıkış yapılır, login sayfasına <code data-backticks=\"1\">auth_success</code> ile yönlendirilir (suspend_success mesajı).</p></li></ul></li><li><p><strong>POST /profile/close-account</strong> (ProfileController::closeAccount):</p><ul><li><p>CSRF, mevcut şifre ve onay metni (tam eşleşme: <code data-backticks=\"1\">profile.close_confirm_phrase</code>) kontrolü.</p></li><li><p><code data-backticks=\"1\">closed_at = now()</code> güncellenir.</p></li><li><p>Çıkış yapılır, login sayfasına <code data-backticks=\"1\">auth_error</code> ile yönlendirilir (close_success mesajı).</p></li></ul></li></ul><div contenteditable=\"false\"><hr></div><h2>Şablonlar</h2><ul><li><p><strong>profile/account</strong> — <code data-backticks=\"1\">templates/frontend/default/views/profile/account.html.twig</code>Askıya al ve kalıcı kapat kartları, şifre ve onay metni alanları, JS confirm.</p></li><li><p><strong>reactivate-account</strong> — <code data-backticks=\"1\">templates/frontend/default/views/reactivate-account.html.twig</code>Kullanıcı adı/e-posta + şifre formu.</p></li><li><p><strong>profile/edit</strong> — “Hesabı askıya al / Kapat” linki eklendi (<code data-backticks=\"1\">profile/account</code>).</p></li><li><p><strong>login</strong> — Askıda hesap için hata kutusunda <code data-backticks=\"1\">auth_show_reactivate</code> flash’ı varsa “Hesabımı tekrar aç” linki (<code data-backticks=\"1\">reactivate-account</code>) gösterilir.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>Admin panel</h2><ul><li><p><strong>Güvenlik → Spam &amp; Zombie → Askıya alınan kullanıcılar</strong> listesinde:</p><ul><li><p><code data-backticks=\"1\">closed_at</code> dolu kullanıcılar için “Askıyı kaldır” butonu yerine <strong>“Kalıcı kapatıldı”</strong> rozeti gösterilir.</p></li><li><p>Unsuspend işlemi yalnızca <code data-backticks=\"1\">closed_at</code> boş kullanıcılar için yapılır; kalıcı kapatılmış hesap askıdan çıkarılamaz.</p></li></ul></li></ul>', 1, 0, 1, '2026-02-26 21:03:33', '2026-02-27 15:35:57', NULL, NULL, 0, NULL, NULL, NULL),
(61, 24, 1, '<p>Modern sistemler, Gelişmiş yönetimi, ve Katı güvenlik kuralları nedeni ile TWIG tema motoru tercih edilmiş ve Tüm forum tema sistemi twig altyapısına taşınmıştır.</p><p><br></p><p>Alttaki cevabı da Gemini verdi :) Bir bildiği var demekki</p><blockquote><p><strong>Güvenlik (Sandbox) Duvarı</strong> Blade içine <code>@php</code> tagı ile doğrudan raw PHP yazılabilir. Müşterilerin kendi temasını düzenleyeceği bir SaaS platformunda Blade kullanırsan sunucuyu ilk günden patlatırlar. Twig\'in izole \"Sandbox\" modu vardır; dışarıdan müdahale eden biri sadece senin izin verdiğin değişkenleri okuyabilir, sistemi hackleyemez.</p><p><strong>Spagetti Koda Geçit Yok</strong> Blade çok laçkadır, view dosyasının içine Controller mantığı ve veritabanı sorgusu yazmaya bile müsaade eder. Twig katıdır, tasarım ile backend kodunu jilet gibi ayırır.</p><p><strong>Symfony Kanı</strong> MegaforBB altyapısında zaten Symfony mimarisini harmanlıyorsun Twig de doğrudan Symfony\'nin kendi ana motorudur.</p></blockquote><p><br></p><p>oy:</p>', '<p>Modern sistemler, Gelişmiş yönetimi, ve Katı güvenlik kuralları nedeni ile TWIG tema motoru tercih edilmiş ve Tüm forum tema sistemi twig altyapısına taşınmıştır.</p><p><br></p><p>Alttaki cevabı da Gemini verdi :) Bir bildiği var demekki</p><blockquote><p><strong>Güvenlik (Sandbox) Duvarı</strong> Blade içine <code>@php</code> tagı ile doğrudan raw PHP yazılabilir. Müşterilerin kendi temasını düzenleyeceği bir SaaS platformunda Blade kullanırsan sunucuyu ilk günden patlatırlar. Twig\'in izole \"Sandbox\" modu vardır; dışarıdan müdahale eden biri sadece senin izin verdiğin değişkenleri okuyabilir, sistemi hackleyemez.</p><p><strong>Spagetti Koda Geçit Yok</strong> Blade çok laçkadır, view dosyasının içine Controller mantığı ve veritabanı sorgusu yazmaya bile müsaade eder. Twig katıdır, tasarım ile backend kodunu jilet gibi ayırır.</p><p><strong>Symfony Kanı</strong> MegaforBB altyapısında zaten Symfony mimarisini harmanlıyorsun Twig de doğrudan Symfony\'nin kendi ana motorudur.</p></blockquote><p><br></p><p>oy:</p>', 0, 0, 0, '2026-02-26 21:06:49', '2026-02-26 21:12:41', '2026-02-26 21:12:41', 1, 4, NULL, NULL, NULL),
(62, 20, 1, '<p>İlgil sorun çözüldü.</p>', '<p>İlgil sorun çözüldü.</p>', 0, 0, 0, '2026-02-26 21:20:12', '2026-02-26 21:20:12', NULL, NULL, 0, NULL, NULL, NULL),
(63, 25, 131, '<p>Bildirim sistemindeki hata  header\'da bildirim görünüyor ancak bildirimler sayfasında hiç görünmüyor bildirim. onun da incelenmesi lazım.</p>', '<p>Bildirim sistemindeki hata  header\'da bildirim görünüyor ancak bildirimler sayfasında hiç görünmüyor bildirim. onun da incelenmesi lazım.</p>', 1, 0, 0, '2026-02-26 21:43:00', '2026-02-27 15:56:01', NULL, NULL, 0, NULL, NULL, NULL),
(64, 25, 1, '<p><br></p><p><strong>kaan yazdı:</strong></p><blockquote><p><br></p><p>Bildirim sisteminde ufak bir hata var, Bildirimleri okundu işaretle - Tümünü okundu işaretle yapamıyoruz dolayısıyla belirli aralıklarla bildirimler gösteriliyor sağ üstte.Sorun teşkil edecek birşey değil ama düzeltilmesi iyi olur.</p></blockquote><p><del>Bildirim sisteminde hata çözüldü.</del></p>', '<p><br></p><p><strong>kaan yazdı:</strong></p><blockquote><p><br></p><p>Bildirim sisteminde ufak bir hata var, Bildirimleri okundu işaretle - Tümünü okundu işaretle yapamıyoruz dolayısıyla belirli aralıklarla bildirimler gösteriliyor sağ üstte.Sorun teşkil edecek birşey değil ama düzeltilmesi iyi olur.</p></blockquote><p><del>Bildirim sisteminde hata çözüldü.</del></p>', 1, 0, 0, '2026-02-27 15:55:56', '2026-02-27 15:56:39', NULL, NULL, 0, NULL, NULL, NULL),
(65, 22, 131, '<p>Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap edecek bir sistem olmasa da olur. Herkesi memnun edemezseniz 😎</p>', '<p>Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap edecek bir sistem olmasa da olur. Herkesi memnun edemezseniz 😎</p>', 1, 0, 0, '2026-02-27 15:58:13', '2026-02-28 02:37:12', NULL, NULL, 0, NULL, NULL, NULL),
(66, 29, 1, '<p>Nu konu içnde Etiket ve dosya ek testi yapıyoruz, sisteme güncelleme geliştirme olarak ekledik. </p><p>Tüm yenilikleri bu şekilde test ortamında paylaşıyoruz.</p><p><br></p><p><br></p>', '<p>Nu konu içnde Etiket ve dosya ek testi yapıyoruz, sisteme güncelleme geliştirme olarak ekledik. </p><p>Tüm yenilikleri bu şekilde test ortamında paylaşıyoruz.</p><p><br></p><p><br></p>', 1, 0, 1, '2026-02-27 16:14:53', '2026-03-01 23:25:16', NULL, NULL, 0, NULL, NULL, NULL),
(67, 30, 1, '<p>Mesaj gönderilirken kullanıcı yazıp mesajı yazıp gönder dediğimizde: Mesaj gönderilemedi. </p><p><br></p><p>Alpine.js sorunu olduğu  konsoldaki hatalardan anlaşılıyor.</p>', '<p>Mesaj gönderilirken kullanıcı yazıp mesajı yazıp gönder dediğimizde: Mesaj gönderilemedi. </p><p><br></p><p>Alpine.js sorunu olduğu  konsoldaki hatalardan anlaşılıyor.</p>', 0, 0, 1, '2026-02-27 16:31:36', '2026-02-27 16:31:36', NULL, NULL, 0, NULL, NULL, NULL),
(68, 30, 1, '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/e9976083f461a5f7.png\" alt=\"666.png\" contenteditable=\"false\">Sorun çözüldü, Mesaj gönderimi sorunsuz yapılıyor.</p><p><br></p><p>Kendim yazıp kendim çözüyorum :) </p>', '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/e9976083f461a5f7.png\" alt=\"666.png\" contenteditable=\"false\">Sorun çözüldü, Mesaj gönderimi sorunsuz yapılıyor.</p><p><br></p><p>Kendim yazıp kendim çözüyorum :) </p>', 0, 0, 0, '2026-02-27 18:08:37', '2026-02-27 18:15:20', '2026-02-27 18:15:20', 1, 1, NULL, NULL, NULL),
(69, 31, 129, '<p>Forum sisteminde SEF Url desteği olması gerekiyor şu anda .com.tr/topic/9 şeklinde görünüyor konular.</p>', '<p>Forum sisteminde SEF Url desteği olması gerekiyor şu anda .com.tr/topic/9 şeklinde görünüyor konular.</p>', 0, 0, 1, '2026-02-28 01:58:35', '2026-02-28 02:18:33', '2026-02-28 02:18:33', 129, 1, NULL, NULL, NULL),
(70, 31, 1, '<p>Şu anda sistemde bu özellik var zaten 3 kademeli çalışıyor;</p><ol><li><p>1- Sef:rakam</p></li><li><p>2-Sef:başlık</p></li><li><p>3-Random karakter</p></li></ol><p><br></p><ul><li><p>1 şu senin bahsettiğin,</p></li><li><p>2: Standart SEF url tarzı her yerde gördüğümüz alışık olduğumuz sistem konu başlığını temizleyip sefurl yapıyor.</p></li><li><p>3: Bu random sistem ise Google sıralama veya seo umrunda olmayan tamamen kendi amacına hitap eden forumlar için geçerli.</p></li></ul><p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/a481eb839a3609e7.png\" alt=\"sef-url.png\" contenteditable=\"false\"><br></p>', '<p>Şu anda sistemde bu özellik var zaten 3 kademeli çalışıyor;</p><ol><li><p>1- Sef:rakam</p></li><li><p>2-Sef:başlık</p></li><li><p>3-Random karakter</p></li></ol><p><br></p><ul><li><p>1 şu senin bahsettiğin,</p></li><li><p>2: Standart SEF url tarzı her yerde gördüğümüz alışık olduğumuz sistem konu başlığını temizleyip sefurl yapıyor.</p></li><li><p>3: Bu random sistem ise Google sıralama veya seo umrunda olmayan tamamen kendi amacına hitap eden forumlar için geçerli.</p></li></ul><p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/a481eb839a3609e7.png\" alt=\"sef-url.png\" contenteditable=\"false\"><br></p>', 0, 0, 0, '2026-02-28 02:07:24', '2026-02-28 20:11:42', '2026-02-28 20:11:42', 1, 1, NULL, NULL, NULL),
(71, 22, 1, '<p><br></p><p><br></p><blockquote><p><strong>slaweally yazdı:</strong></p><p><br></p><p>Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap edecek bir sistem olmasa da olur. Herkesi memnun edemezseniz 😎</p></blockquote><p>Şu anda kurulum sistemi ile ilgili bir durum yok, sistemin henüz piyasaya sürülme durumu belirsizliğini koruyor çünkü :)</p>', '<p><br></p><p><br></p><blockquote><p><strong>slaweally yazdı:</strong></p><p><br></p><p>Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap edecek bir sistem olmasa da olur. Herkesi memnun edemezseniz 😎</p></blockquote><p>Şu anda kurulum sistemi ile ilgili bir durum yok, sistemin henüz piyasaya sürülme durumu belirsizliğini koruyor çünkü :)</p>', 0, 0, 0, '2026-02-28 02:37:48', '2026-02-28 02:37:48', NULL, NULL, 0, NULL, NULL, NULL),
(72, 32, 1, '<p>Merhabalar, Yeni bir özellik olarak duyurmak istediğimiz <strong><em>Profil yorumları</em></strong> sistemini Kullanıma sunduk.</p><p>Bu özellik ile birlikte istediğiniz kullanıcının profil sayfasına kullanıcı hakkında yorum yazabilirsiniz</p><p><br></p><p>Not: Kullanıcı isteğe bağlı olarak bu özelliği kullanmak istiyorsa tabiki</p><p><br></p><p>Bu özelliği ilk denemek için benim profilime girenlere önceden not: Ben kapattım :D</p>', '<p>Merhabalar, Yeni bir özellik olarak duyurmak istediğimiz <strong><em>Profil yorumları</em></strong> sistemini Kullanıma sunduk.</p><p>Bu özellik ile birlikte istediğiniz kullanıcının profil sayfasına kullanıcı hakkında yorum yazabilirsiniz</p><p><br></p><p>Not: Kullanıcı isteğe bağlı olarak bu özelliği kullanmak istiyorsa tabiki</p><p><br></p><p>Bu özelliği ilk denemek için benim profilime girenlere önceden not: Ben kapattım :D</p>', 1, 0, 1, '2026-02-28 03:24:13', '2026-02-28 19:44:21', '2026-02-28 19:44:21', 1, 2, NULL, NULL, NULL),
(74, 34, 1, '<p>Bu mesajı planlanmış konu testi için yazıyorum: 23:08</p><p><br></p><p>Yayın tarihine ise:23:10 olarak ayarlıyorum.</p>', '<p>Bu mesajı planlanmış konu testi için yazıyorum: 23:08</p><p><br></p><p>Yayın tarihine ise:23:10 olarak ayarlıyorum.</p>', 0, 0, 1, '2026-02-28 23:09:00', '2026-02-28 23:40:35', NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `posts` (`id`, `topic_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(76, 36, 1, '<p><strong>KISA MAKALE ÖRNEKLERİ</strong></p><p><br></p><p><br></p><p>Makale örneği arayanlar için birbirinden güzel uzun ve kısa makale örneklerini bir araya getirdik. 7 farklı makaleyi aşağıda başlıklar halinde sizlere sunuyoruz. Okumanın önemi, sınav stresi, bilgisayarın zararları, televizyonun zararları ve diğer başlıklar altında makale örnekleri hazırladık.</p><p><br></p><p><br></p><p><strong>Okumanın Önemi</strong></p><p><br></p><p><br></p><p>Kitap, kırılgan bir yaratıktır, zamandan etkilenir, kemirgenlerden korkar, yabancı maddelerden ve sakar ellerden de. Bu yüzden kütüphaneciler kitapları sadece insanlara karşı değil aynı zamanda doğaya karşı da korur ve hayatını unutulmanın kuvvetlerine karşı savaşmaya adar.</p><p><br></p><p>O altın üniversite yıllarını hatırlayın, kitaplarla dolu okul çantası, öğrenilecek birçok şey, bir sürü bilgi – tarih, coğrafya, matematik, fen. Küçük omuzlarda bilgi dolu çantaların taşındığı ve her şeye güldüğümüz zamanlar. Evet, bütün o şeyler bizi şimdi de güldürür. Eğer omuzlarımızda o ağır yükleri taşımasaydık eminim ki bugün toplumda durduğumuz yerde duruyor olamayacaktık. Bugünkü bilgimizi sadece kitaplara borçluyuz. İnternet diye bir teknolojinin var olmadığı günlerdi ve o zamanlar kitaplar, bilgiyi yaymanın tek yoluydu. Yazarlar kendi duygularını aktarırlardı; uzmanlar bilgilerini gelecek nesillerle paylaşırlardı ve bunlar sadece kitaplar aracılığıyla yapılırdı. Bu yüzden kitaplar bize atalarımızın hediyesi gibidir, bilmediğimiz birçok şeyi bize öğreten, mesela – Ramayana’nın hikayesi, Tulsidass tarafından yazılmış bir kitap.</p><p><br></p><p>Kitaplar başarıya ulaşmak için gidilen yolda en iyi iz sürücülerdir. İlerlemiş teknoloji yüzünden kitapların şimdiki nesle olan önemi neredeyse sıfırdır. Gelişmiş teknolojinin iyi bir şey olmadığını söylemiyorum, ama yazılmış kelimelere inanmalıyız. Biliriz ki eski iyidir. Yazmayı bilmeyen zavallı bir çocuk, kitapların gerçek önemini bilen gerçek kişidir. Kitaplara hak ettikleri önemi vermek çok önemlidir.</p><p><br></p><p>Okumak, zihnimizi rahatlatan en iyi şeydir. Tatlı hikayeler, komik fıkralar, gerçek hikayeler okuyabilirsiniz; bu size kalmış bir şeydir, ne isterseniz onu okursunuz. Çeşitli insanlar için çeşitli kitaplar mevcuttur. Bugünlerde kitapları kolay ve ucuz yoldan kapınıza ulaştıracak kaynaklar var, mesela, siteleri ziyaret ederek çok kolay bir şekilde kitap siparişi verebilirsiniz. Kitap klüplerinden birini biliyorum mesela India Today Book Club, siz üye olduktan sonra siparişlerinizi alıyor. Ondan sonra kurgu, klasik, sanat ve referans kitapları, çocuk okuma kitapları, yemek, bahçe, spor ve sağlık, din, bilişim teknolojisi, tıp ve daha birçok alandan kendi zevkinize göre sayısız kitap siparişi verebilirsiniz, üstelik büyük indirimler ve tatil paketleri gibi heyecan verici hediyelerle.</p><p><br></p><p>İnanıyorum ki başarı pudingini tatmak için içinizde bir merak varsa kitapların fiyatı sizin için hiçbir şeydir. Ben kitap okurken kendimi daha az yalnız hissederim.</p><p><br></p><p><br></p><p><strong>Okuma Zorlukları</strong></p><p><br></p><p>Bazı insanlar okumakta zorluk çeker. Bu durum yaşa bağlı değildir. Nedenleri arasında sağlık sorunları, işitme, özellikle de görme bozuklukları sayılabilir. Bazen de çocuklar okulda iyi öğretilmediği için okuma öğrenemez. Küçüklüklerinde durmadan evden eve taşınan ailelerin çocukları değişik okullara uyum sağlamakta güçlük çekebilir, bu yüzden iyi okuyamayabilirler. Ayrıca bazı çocuklar okumaktan hoşlanmayabilir, başka şeylerle uğraşmak onları daha mutlu edebilir. Okuma öğrenmekte güçlük çeken çocuklara yardımcı olmak için eğitilmiş özel öğretmenler vardır. Bunlar çocuğun neden yaşıtları gibi öğrenemediğini testler uygulayarak araştırır. Sorunun ne olduğu bir kez saptanınca, çocuğun özel eğitimle okuma öğrenmesi kolaylaşır.</p><p><br></p><p>Basit bir bedensel bozukluktan kaynaklandığı sanılan disleksi okumayı öğrenme güçlüğü olarak tanımlanabilir. Normal yaşta okula başlamış, zeka geriliği ya da davranış bozukluğu olmayan bazı çocuklar akıcı bir biçimde okumayı başaramaz ya da söylenişi ve yazılışı yakın harfleri birbirine karıştırır. Örneğin, disleksililer “ya” yı “ay” ya da “d” yi “b” olarak okur. Disleksinin çeşitli dereceleri vardır.çabuk farkına varılması durumunda bazen özel eğitimle okuma öğretilse de, disleksinin nedenlerine ilişkin kesin bir bulgu yoktur. Disleksililer okuma eksikliklerini görsel ve işitsel gereçlerle bir ölçüde giderebilmektedir.</p><p><br></p><p>Annelerin, babaların, öğretmenlerin ilk amacı, çocuğu sadece okul sıralarında değil, ömrü boyunca okumaktan zevk alacak bir kişi olarak yetiştirmek olmalıdır. Yalnızca güzel okumanın yeterli olmayacağı, okumanın yaşamın vazgeçilmez, verimli bir uğraşı olduğu bilinci çocuklara aşılanmalıdır. Böylesi bir özendirmeyle çocuklara koskoca bir kitap ve bilgi dünyasının kapıları açılmış olur.</p><p><br></p><p><br></p><p><strong>Bilgisayar Zararlı Mı?</strong></p><p><br></p><p>Son günlerde bilim adamları(Bazıları) ilerki yıllarda,insan zekasının geriliyeceğini iddaa ediyor. Gerekceleri ise tek şuçlu olarak bilgisayar`ı gösteriyorlar. Hepimizin bildiği gibi beyin cimnastiki dediğimiz bir olay var. Beynimizi ne kadar zorlarsak, o kadar gelişmesine ve genç kalmasına katkıda bulunuyoruz… Bunlardan en basiti bulmaca çözmek gibi. Şimdi acaba şöyle bir kolaycılığa kaçıyormuyuz ,veya zamanla kaçacakmıyız?Bu kolaycılığın doğal sonucu olarakta gelecek kuşaklarda IQ`muzda bir düşme olacak mı? Bir arkadaşınız sizden bir konu hakkında bilgi almak istiyor,veya çoçuğumuzun takıldığı bir dersten dolayı,size birşey sorma isteği duyduğun da,onlara vereceğimiz cevap: Bana sormana ve düşünmene artık gerek yok . Gir bilgisayara ne sormak veya öğrenmek istiyorsan,yaz ve tıkla bu kadar basit hemen karşına çıkar. Bu örneklerin sonunda bilim adamlarının endişeleri acaba haklı çıkar mı?</p><p><br></p><p><br></p><p><strong>Sınav Stresi</strong></p><p><br></p><p>Sınav stresiyle boğuşan birçok insandan biri misiniz? O gizemli sorularla dolu masanın önünde oturma düşüncesi kalbinizi çok kötü çarptırıyor ve vücudunuzu terletmeye mi başlıyor? Gevşeyin! O korkuları ve sınav stresini basit stres azaltma stratejileri kullanarak alt edebilirsiniz.</p><p><br></p><p>Nefes almanın basit sanatını hatırlayın. Birkaç ağır nefes alın ve zihninizin verdiğiniz nefesle birlikte gevşemesine izin verin. Basit bir meseledir fakat sınav paniği ortaya çıktığında kolaylıkla unutulabilir.</p><p><br></p><p>Olumlu şeyler düşünün. Sınavdan iyi sonuç almanızı engelleyecek en iyi şey, olumsuz konuşarak kendinizi panik etmenizdir. Olumlu şeylere odaklanın. Bunu yapabileceğinize kendinizi ikna edin. Ayrıca sınavdan 100 üzerinden 100 alamazsanız bunun dünyanın sonu olmayacağını kendinize hatırlatın.</p><p><br></p><p>Egzersiz için zaman ayırın. Beyin fonksiyonlarının geliştirmenin çok iyi bir yolu, vücudunuzun o kısmındaki kan akışını geliştirmenizdir. Egzersiz bunun için harika bir yoldur. Egzersiz ayrıca vücudunuzdaki endorfin seviyesini de arttırarak duygusal stresi azaltmanıza da yardımcı olur.</p><p><br></p><p>Aşırı çalışmayın. Sınav stresi yaşarken bir de bulduğunuz her boş dakikada derse gömülmeyin. Bütün temelleri aldığınıza ve konuyu candan öğrendiğinize emin olmak istiyorsunuzdur. Bunla ilgili sorun şu ki, eğer beyninize bir mola verdirmezseniz, o zaman bilgiyi kısa zamanlı hafızadan uzun zamanlı hafızaya taşıma süreci etkilenecektir. Eğer beyniniz yorulursa, fonksiyonları zayıflayacaktır. Çalışma süresini daha kısa seanslara programlayarak kendinize beyin gücü bahşedebilirsiniz. Zamanınız varken plan yapın ki çalışmanız için gerekli süreniz olsun.</p><p><br></p><p>Sınav stresiyle baş edebilmeniz akademik kariyerinizde daha başarılı olmanıza yardımcı olur. Zihin sağlığınızı olumlu bir yerde tutmak için kendinize ihtiyacınız olan molaları vermeyi ihmal etmeyin. Patlarcasına çalışmak size sınavda istediğiniz başarıyı sunmayacaktır.</p><p><br></p><p><br></p><p><strong>Kalbin Emeği</strong></p><p><br></p><p>Dua kalbin emeğidir. Kalpten gelen arzuları ifade eder. Ancak insanın bu arzuların üzerinde bir gücü yoktur. İnsan öyle bir şekilde yaratılmıştır ki insan tam olarak ne aradığını ya da gerçek niyetinin ne olduğunu bilmez. Dolayısıyla dualarının önem teşkil eden doğasını da anlayamamaktadır.</p><p>Buna karşılık, dua kitaplarında yazanlar aslında kişinin istemesi gerekenleri öğrenmesini anlatan şeylerdir. Eğer kişi kendisi üzerinde çalışırsa ve arzu ve düşüncelerini kontrol edip yönlendirme yolunda çalışırsa, dua kitaplarını yazan insanların arzu ve talep seviyelerine yükselir. Dua kitapları maneviyatı edinmiş insanlar tarafından binlerce yıl önce yazılmıştır.</p><p>Kişinin dua kitaplarını yazan kişiler ile arzularını uyumlu hale getirebilmesi için birkaç hazırlık safhasından geçmesi gereklidir. Kişi kötülüğün doğasını ve nelere sebep olduğunu anlaması gerekir, şöyle ki, insanın doğası gereği egoist bir eğilimi olduğudur. Kişi egoizminin (benliğinin) kötülüğün kaynağı olduğunu anlamalıdır. Hatta dahası, bunların hepsinin ruhun en derin noktasında edinilmesi ve fark edilmesi gerekmektedir.</p><p><br></p><p><br></p><p><strong>Televizyon Zararı</strong></p><p><br></p><p>ÖZELLİKLE yaşlı insanlardan şu sözleri çok sık duyarsınız: “Televizyon çıkalı eski muhabbetler kalmadı.” Biz bu haklı sözleri değiştirerek şöyle diyoruz: “Televizyon çıkalı anne babalar çocuklarına eskisi kadar zaman ayıramaz oldu.” Anne gündüz televizyon izlerken eteğine yapışan çocuğu başından savmak için “git oyuncaklarınla oyna, görmüyor musun televizyon izliyorum” der. Baba işten dönüp akşam yemeğini yedikten sonra koltuğuna oturur, eline kumandayı alır, saatlerce şu kanal senin bu kanal benim dolaşır durur. Baba özlemi çeken çocuğuna yarım saatini ayırmaz.</p><p><br></p><p>Geliri yerinde, okumuş ailelerin çoğu çocuk odasına da televizyon almaktadır. Alırken çocukla bir anlaşma yapar ve söz vermesini isterler: “Ancak ödevini yapıp dersini çalıştıktan sonra televizyon izleyeceksin.” Çocuk hiç düşünmeden söz verir. Aslında bu anlaşmada iki taraf da birbirini aldatmaktadır. Anne babanın amacı çocuktan kurtulmak, çocuğun da amacı televizyon sahibi olmaktır. Araştırmalar, odasına televizyon alınan çocukların, beklenenin aksine okul başarısında düşme olduğunu göstermektedir. Çocuk, televizyon izleyebilmek için ödevlerini çala kalem yapmakta, derslerine yeterince çalışmamakta ve sınavlara iyi hazırlanamamaktadır.</p><p><br></p><p>Çocuklarda televizyon seyretme alışkanlığı sadece okul başarısını etkilemekle kalmıyor; fiziksel, sosyal, zihinsel ve duygusal gelişimlerini de yavaşlatıyor. Çocuk, televizyon başında yeterince hareket etmediği ve biriken enerjisini harcayamadığı için devamlı kilo almaktadır. Sokakta arkadaşlarıyla oyun oynayan ve koşan bir çocuk birikmiş vücut enerjisini boşalttığı için rahatlamakta; eve sakinleşmiş olarak dönmektedir. Halbuki televizyonun karşısında saatlerce oturan bir çocuk enerjisini boşaltmak şöyle dursun, aksine bu cihazlardan yayılan elektronlara maruz kalmakta ve vücudundaki statik elektrik yükü artmaktadır. Bu sebeple, televizyon bağımlısı çocuklar daha sinirli ve daha saldırgandır. Yaşlarına uygun olmayan programları izlemeleri halinde kafaları karışır, ruh sağlıkları bozulur.</p><p><br></p><p>Televizyona düşkün çocuklarda sosyal beceriler zayıflamaya ve içe dönük bir kişilik gelişmeye başlar. Ailesiyle, arkadaşlarıyla ve diğer insanlarla sosyal ilişki kurmada isteksiz davranırlar. Televizyon izleyen bir çocuk, kendisi birşey üretmemekte, sadece başkaları tarafından üretilen şeyleri izlemekte veya oynamaktadır. Hazırı kullanmaya alışmış bu çocuklarda el becerileri ve motor hareketler gelişmez, büyüklerin yardımı olmadan kendi başlarına bir iş beceremezler. Zihinsel ve duygusal gelişimleri de normal değildir. Olaylar arasında sebep-sonuç ilişkisi kuramaz, bilgiyi yorumlayamazlar. Kitap okumak ve ders çalışmak gibi zihinsel çaba gerektiren işlerden hoşlanmazlar. Televizyon karşısında daima alıcı durumunda oldukları için konuşmaya ihtiyaç duymamakta, dolayısıyla dil becerileri gelişmemektedir. Dil becerileri zayıf olduğu için başkalarıyla diyalog kuramaz, duygularını ve düşüncelerini doğru ifade edemezler.</p><p><br></p><p>Küçük yaştan itibaren televizyon izlemeye alışan çocuklarda gelişim bozuklukları daha belirgin ve daha ciddidir. Bu çocuklar akranlarına nazaran daha geç yürür ve daha geç konuşurlar. Konuşulanları ve kendilerine verilen direktifleri anlamakta güçlük çekerler. Dil becerileri gelişmediği için isteklerini büyüklerin elinden tutarak veya işaret ederek anlatmaya çalışırlar. Anneye aşırı bağımlıdırlar. Yabancılarla duygusal ilişkiye giremezler. Öpülmekten ve kucaklanmaktan hoşlanmazlar. İsimleriyle çağırıldıkları zaman tepki vermezler. Yaşıtlarıyla oyun oynamayı ve oyun kurmayı beceremezler. Ellerini ve parmaklarını iyi kullanamazlar. Çarşı, pazar, toplu taşıma araçları gibi kalabalık yerlerde bulunmaktan hoşlanmaz, huysuzluk gösterirler. Doğuştan zihin geriliği olan ve fazla televizyon izleyen çocuklarda otizm belirtileri artmakta, bu çocukları eğitmek daha da zorlaşmaktadır.</p><p><br></p><p><br></p><p><strong>Çocuklarınıza Zaman Ayırın</strong></p><p><br></p><p>Çocukları televizyon bağımlılığından kurtarmanın tek çaresi onlara zaman ayırmaktır. Anne baba olarak öncelikli görevimiz çocuklarımıza iyi bir eğitim kazandırmaktır. Hiçbir işimiz çocuk eğitiminden daha önemli değildir. Eğer çocukların yapmaktan zevk alacakları müzik, resim, spor, kitap okumak gibi faydalı bir becerileri yoksa; anne babaların televizyonu yasaklamaları problemi çözmeyecek, daha da ağırlaştıracaktır.</p><p><br></p><p>Çocuğunun inatçılığından, söz dinlememesinden, aşırı televizyon izlemesinden ve okuldaki başarısızlığından yakınan bir babaya “çocuğunuza zaman ayırın” tavsiyesinde bulunduğumuzda, “her akşam en az bir saat beraber ders çalışıyoruz, ödevlerine yardım ediyorum, ama değişen bir şey yok” demişti. Gülerek: “Hayır, dedim, bizim kastettiğimiz beraberlik bu değil. Çocuk bu beraberlikten zevk almaz, aksine bir an önce bitmesini ister. Siz çocuğunuza zaman ayırmıyorsunuz, ona ders çalıştırıyorsunuz.”</p><p><br></p><p>Çocuğunuza ayırdığınız zamanın süresi değil, kalitesi önemlidir. Eğer bu beraberlikten iki taraf da zevk alıyorsa, kaliteli bir beraberlik var demektir. Birlikte yürüyüşe çıkmak, çocuk parkına gitmek, piknik yapmak, akşam yemeğinden sonra ailece çaylı-pastalı sohbet etmek, birlikte televizyonda kaliteli bir film veya program izlemek, uyku saatinde çocuğunuza masal veya kısa bir hikaye okumak ilk anda aklımıza gelebilen kaliteli beraberliklerdir.</p><p><br></p><p>Çocuğunuzla birlikte iken iyi bir dinleyici olmalısınız. Çocuk duygularını, hayallerini, düşüncelerini, endişelerini, korkularını çekinmeden dile getirmeli ve sizinle paylaşmalıdır.</p><p>Çocuklarını dinlemeyen anne babalar onları tanımakta güçlük çekerler. Çocuğunuzu ne kadar çok tanırsanız, yetenekleri konusunda beklentileriniz o kadar gerçekçi olur.</p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><strong>Emek Kavramına Farklı Bir Bakış</strong></p><p><br></p><p><br></p><p>Emek, varlığın kendisiyle bir bütündür. Varolan her birey emeğin birer parçasıdır. Okuluna giden öğretmenin sınıfta ders anlatması bir emek olduğu gibi, onu dinleyen öğrencinin sarfettiği çaba da bir emektir, aynı zamanda öğretmeni dinlemeyip başka birşey ile meşgul olan öğrencinin sarfettiği çaba da bir emektir.</p><p><br></p><p>Emek her alandadır, benim bu yazıyı yazarken, sizin okurken ayırdığınız vakitte emek ile ilintili bir durumdur.</p><p><br></p><p>Yukarıda bahsettiğim konular, emeğin hizmet yönünden anlaşılabilmesi için genel ve basit örneklerdi, birde emeğin üretim alanında yeri vardır ki, üzerinde hassasiyetle durmamız gereken bir durumdur.</p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><strong>Eğitim Köleleştiriyor mu?</strong></p><p><br></p><p>Yıllar önce o zamanlar çok popüler bir haftalık dergi olan Nokta İstanbul’da ilginç bir deney yapmıştı. Bir tiyatro sanatçısı olan Ezel Akay eline bir megafon alarak koyu renk elbiseler ve siyah pardesüler giyen ekibiyle birlikte önce güvercinleriyle ünlü Yenicamii’nin arkasındaki parka giderler. Parkta oturan gezen etrafı seyreden bir sürü insan vardı. Akay elindeki megafonla kalabalığa doğru sert bir emir verir: “Herkes ayağa kalksın!” Emri duyan Akay’ı ve ekibini gören istisnasız herkes derhal ayağa kalkar.</p><p><br></p><p>Sonra Eminönü İskelesi’ne geçerler. Akay yine sert bir emirle: “Herkes yere çöksün!” diye bağırır. Gemiden inenler bilet kuyruğunda bekleyenler simitçiler işportacılar emri duyan herkes yere çöker.</p><p>Sonra Mecidiyeköy’deki stadyumun önüne giderler. Megafondan: “Herkes ellirini kaldırıp duvara yaslansın!” emri duyuldu. Stadyuma girmek için kuyrukta bekleyen futbol seyircileri kokoreççiler bayrakçılar derhal emre uyarlar.</p><p><br></p><p>Daha sonra da ekip bir fabrikanın önüne giderler. Mesai saati başlamak üzeredir. Fabrikanın girişine bir masa koyarlar ve masanın üzerinde düzmece bir evrak yerleştirerek işçilere emiri verirler: “Herkes içeriye girerken bu kâğıtlara parmak basacak!” Giren basar giren basar. Kimsenin aklına “siz kimsiniz hemşehrim? Neden bu kâğıtlara parmak basıyoruz?” diye sormak gelmez.</p><p><br></p><p>Son olarak da Beyoğlu’na gelirler. İstiklal Caddesinde gezinen vitrinleri seyreden kalabalığa yine sert bir emir verilir: “Herkes sıraya girsin arama var!” Emri duyan herkes koyun sürüsü gibi sessizce sıraya girer. Ancak caddede dolaşan bir çift bu emre uymaz.</p><p><br></p><p>Ekiptekilerden biri onlara doğru bağırır: “Hey siz ikiniz! Emri duymadınız mı?” Kendilerine seslenildiğini anlayan ve herkesin sıraya girdiğini gören adam cevap verir: “Who are you? What is happening here?” Sıraya girenler içerisindeki kravatlı takım elbiseli bir bey ekibe yardımcı olmanın verdiği gurur ve heyecanla lafa karışır: “Adam turist İngilizce konuşuyor.” Ekip elemanı gülmemek için kendisini zor tutar:</p><p><br></p><p>“Ne diyor peki?”</p><p>“Siz kimsiniz burada neler oluyor?”</p><p><br></p><p>Ve o iki turistin haricinde hiç kimse neler olup bittiğini kendilerine böyle gün ortasında emirler yağdırıp sıraya sokanların kim olduğunu sormaz ya da soramaz.</p>', '<p><strong>KISA MAKALE ÖRNEKLERİ</strong></p><p><br></p><p><br></p><p>Makale örneği arayanlar için birbirinden güzel uzun ve kısa makale örneklerini bir araya getirdik. 7 farklı makaleyi aşağıda başlıklar halinde sizlere sunuyoruz. Okumanın önemi, sınav stresi, bilgisayarın zararları, televizyonun zararları ve diğer başlıklar altında makale örnekleri hazırladık.</p><p><br></p><p><br></p><p><strong>Okumanın Önemi</strong></p><p><br></p><p><br></p><p>Kitap, kırılgan bir yaratıktır, zamandan etkilenir, kemirgenlerden korkar, yabancı maddelerden ve sakar ellerden de. Bu yüzden kütüphaneciler kitapları sadece insanlara karşı değil aynı zamanda doğaya karşı da korur ve hayatını unutulmanın kuvvetlerine karşı savaşmaya adar.</p><p><br></p><p>O altın üniversite yıllarını hatırlayın, kitaplarla dolu okul çantası, öğrenilecek birçok şey, bir sürü bilgi – tarih, coğrafya, matematik, fen. Küçük omuzlarda bilgi dolu çantaların taşındığı ve her şeye güldüğümüz zamanlar. Evet, bütün o şeyler bizi şimdi de güldürür. Eğer omuzlarımızda o ağır yükleri taşımasaydık eminim ki bugün toplumda durduğumuz yerde duruyor olamayacaktık. Bugünkü bilgimizi sadece kitaplara borçluyuz. İnternet diye bir teknolojinin var olmadığı günlerdi ve o zamanlar kitaplar, bilgiyi yaymanın tek yoluydu. Yazarlar kendi duygularını aktarırlardı; uzmanlar bilgilerini gelecek nesillerle paylaşırlardı ve bunlar sadece kitaplar aracılığıyla yapılırdı. Bu yüzden kitaplar bize atalarımızın hediyesi gibidir, bilmediğimiz birçok şeyi bize öğreten, mesela – Ramayana’nın hikayesi, Tulsidass tarafından yazılmış bir kitap.</p><p><br></p><p>Kitaplar başarıya ulaşmak için gidilen yolda en iyi iz sürücülerdir. İlerlemiş teknoloji yüzünden kitapların şimdiki nesle olan önemi neredeyse sıfırdır. Gelişmiş teknolojinin iyi bir şey olmadığını söylemiyorum, ama yazılmış kelimelere inanmalıyız. Biliriz ki eski iyidir. Yazmayı bilmeyen zavallı bir çocuk, kitapların gerçek önemini bilen gerçek kişidir. Kitaplara hak ettikleri önemi vermek çok önemlidir.</p><p><br></p><p>Okumak, zihnimizi rahatlatan en iyi şeydir. Tatlı hikayeler, komik fıkralar, gerçek hikayeler okuyabilirsiniz; bu size kalmış bir şeydir, ne isterseniz onu okursunuz. Çeşitli insanlar için çeşitli kitaplar mevcuttur. Bugünlerde kitapları kolay ve ucuz yoldan kapınıza ulaştıracak kaynaklar var, mesela, siteleri ziyaret ederek çok kolay bir şekilde kitap siparişi verebilirsiniz. Kitap klüplerinden birini biliyorum mesela India Today Book Club, siz üye olduktan sonra siparişlerinizi alıyor. Ondan sonra kurgu, klasik, sanat ve referans kitapları, çocuk okuma kitapları, yemek, bahçe, spor ve sağlık, din, bilişim teknolojisi, tıp ve daha birçok alandan kendi zevkinize göre sayısız kitap siparişi verebilirsiniz, üstelik büyük indirimler ve tatil paketleri gibi heyecan verici hediyelerle.</p><p><br></p><p>İnanıyorum ki başarı pudingini tatmak için içinizde bir merak varsa kitapların fiyatı sizin için hiçbir şeydir. Ben kitap okurken kendimi daha az yalnız hissederim.</p><p><br></p><p><br></p><p><strong>Okuma Zorlukları</strong></p><p><br></p><p>Bazı insanlar okumakta zorluk çeker. Bu durum yaşa bağlı değildir. Nedenleri arasında sağlık sorunları, işitme, özellikle de görme bozuklukları sayılabilir. Bazen de çocuklar okulda iyi öğretilmediği için okuma öğrenemez. Küçüklüklerinde durmadan evden eve taşınan ailelerin çocukları değişik okullara uyum sağlamakta güçlük çekebilir, bu yüzden iyi okuyamayabilirler. Ayrıca bazı çocuklar okumaktan hoşlanmayabilir, başka şeylerle uğraşmak onları daha mutlu edebilir. Okuma öğrenmekte güçlük çeken çocuklara yardımcı olmak için eğitilmiş özel öğretmenler vardır. Bunlar çocuğun neden yaşıtları gibi öğrenemediğini testler uygulayarak araştırır. Sorunun ne olduğu bir kez saptanınca, çocuğun özel eğitimle okuma öğrenmesi kolaylaşır.</p><p><br></p><p>Basit bir bedensel bozukluktan kaynaklandığı sanılan disleksi okumayı öğrenme güçlüğü olarak tanımlanabilir. Normal yaşta okula başlamış, zeka geriliği ya da davranış bozukluğu olmayan bazı çocuklar akıcı bir biçimde okumayı başaramaz ya da söylenişi ve yazılışı yakın harfleri birbirine karıştırır. Örneğin, disleksililer “ya” yı “ay” ya da “d” yi “b” olarak okur. Disleksinin çeşitli dereceleri vardır.çabuk farkına varılması durumunda bazen özel eğitimle okuma öğretilse de, disleksinin nedenlerine ilişkin kesin bir bulgu yoktur. Disleksililer okuma eksikliklerini görsel ve işitsel gereçlerle bir ölçüde giderebilmektedir.</p><p><br></p><p>Annelerin, babaların, öğretmenlerin ilk amacı, çocuğu sadece okul sıralarında değil, ömrü boyunca okumaktan zevk alacak bir kişi olarak yetiştirmek olmalıdır. Yalnızca güzel okumanın yeterli olmayacağı, okumanın yaşamın vazgeçilmez, verimli bir uğraşı olduğu bilinci çocuklara aşılanmalıdır. Böylesi bir özendirmeyle çocuklara koskoca bir kitap ve bilgi dünyasının kapıları açılmış olur.</p><p><br></p><p><br></p><p><strong>Bilgisayar Zararlı Mı?</strong></p><p><br></p><p>Son günlerde bilim adamları(Bazıları) ilerki yıllarda,insan zekasının geriliyeceğini iddaa ediyor. Gerekceleri ise tek şuçlu olarak bilgisayar`ı gösteriyorlar. Hepimizin bildiği gibi beyin cimnastiki dediğimiz bir olay var. Beynimizi ne kadar zorlarsak, o kadar gelişmesine ve genç kalmasına katkıda bulunuyoruz… Bunlardan en basiti bulmaca çözmek gibi. Şimdi acaba şöyle bir kolaycılığa kaçıyormuyuz ,veya zamanla kaçacakmıyız?Bu kolaycılığın doğal sonucu olarakta gelecek kuşaklarda IQ`muzda bir düşme olacak mı? Bir arkadaşınız sizden bir konu hakkında bilgi almak istiyor,veya çoçuğumuzun takıldığı bir dersten dolayı,size birşey sorma isteği duyduğun da,onlara vereceğimiz cevap: Bana sormana ve düşünmene artık gerek yok . Gir bilgisayara ne sormak veya öğrenmek istiyorsan,yaz ve tıkla bu kadar basit hemen karşına çıkar. Bu örneklerin sonunda bilim adamlarının endişeleri acaba haklı çıkar mı?</p><p><br></p><p><br></p><p><strong>Sınav Stresi</strong></p><p><br></p><p>Sınav stresiyle boğuşan birçok insandan biri misiniz? O gizemli sorularla dolu masanın önünde oturma düşüncesi kalbinizi çok kötü çarptırıyor ve vücudunuzu terletmeye mi başlıyor? Gevşeyin! O korkuları ve sınav stresini basit stres azaltma stratejileri kullanarak alt edebilirsiniz.</p><p><br></p><p>Nefes almanın basit sanatını hatırlayın. Birkaç ağır nefes alın ve zihninizin verdiğiniz nefesle birlikte gevşemesine izin verin. Basit bir meseledir fakat sınav paniği ortaya çıktığında kolaylıkla unutulabilir.</p><p><br></p><p>Olumlu şeyler düşünün. Sınavdan iyi sonuç almanızı engelleyecek en iyi şey, olumsuz konuşarak kendinizi panik etmenizdir. Olumlu şeylere odaklanın. Bunu yapabileceğinize kendinizi ikna edin. Ayrıca sınavdan 100 üzerinden 100 alamazsanız bunun dünyanın sonu olmayacağını kendinize hatırlatın.</p><p><br></p><p>Egzersiz için zaman ayırın. Beyin fonksiyonlarının geliştirmenin çok iyi bir yolu, vücudunuzun o kısmındaki kan akışını geliştirmenizdir. Egzersiz bunun için harika bir yoldur. Egzersiz ayrıca vücudunuzdaki endorfin seviyesini de arttırarak duygusal stresi azaltmanıza da yardımcı olur.</p><p><br></p><p>Aşırı çalışmayın. Sınav stresi yaşarken bir de bulduğunuz her boş dakikada derse gömülmeyin. Bütün temelleri aldığınıza ve konuyu candan öğrendiğinize emin olmak istiyorsunuzdur. Bunla ilgili sorun şu ki, eğer beyninize bir mola verdirmezseniz, o zaman bilgiyi kısa zamanlı hafızadan uzun zamanlı hafızaya taşıma süreci etkilenecektir. Eğer beyniniz yorulursa, fonksiyonları zayıflayacaktır. Çalışma süresini daha kısa seanslara programlayarak kendinize beyin gücü bahşedebilirsiniz. Zamanınız varken plan yapın ki çalışmanız için gerekli süreniz olsun.</p><p><br></p><p>Sınav stresiyle baş edebilmeniz akademik kariyerinizde daha başarılı olmanıza yardımcı olur. Zihin sağlığınızı olumlu bir yerde tutmak için kendinize ihtiyacınız olan molaları vermeyi ihmal etmeyin. Patlarcasına çalışmak size sınavda istediğiniz başarıyı sunmayacaktır.</p><p><br></p><p><br></p><p><strong>Kalbin Emeği</strong></p><p><br></p><p>Dua kalbin emeğidir. Kalpten gelen arzuları ifade eder. Ancak insanın bu arzuların üzerinde bir gücü yoktur. İnsan öyle bir şekilde yaratılmıştır ki insan tam olarak ne aradığını ya da gerçek niyetinin ne olduğunu bilmez. Dolayısıyla dualarının önem teşkil eden doğasını da anlayamamaktadır.</p><p>Buna karşılık, dua kitaplarında yazanlar aslında kişinin istemesi gerekenleri öğrenmesini anlatan şeylerdir. Eğer kişi kendisi üzerinde çalışırsa ve arzu ve düşüncelerini kontrol edip yönlendirme yolunda çalışırsa, dua kitaplarını yazan insanların arzu ve talep seviyelerine yükselir. Dua kitapları maneviyatı edinmiş insanlar tarafından binlerce yıl önce yazılmıştır.</p><p>Kişinin dua kitaplarını yazan kişiler ile arzularını uyumlu hale getirebilmesi için birkaç hazırlık safhasından geçmesi gereklidir. Kişi kötülüğün doğasını ve nelere sebep olduğunu anlaması gerekir, şöyle ki, insanın doğası gereği egoist bir eğilimi olduğudur. Kişi egoizminin (benliğinin) kötülüğün kaynağı olduğunu anlamalıdır. Hatta dahası, bunların hepsinin ruhun en derin noktasında edinilmesi ve fark edilmesi gerekmektedir.</p><p><br></p><p><br></p><p><strong>Televizyon Zararı</strong></p><p><br></p><p>ÖZELLİKLE yaşlı insanlardan şu sözleri çok sık duyarsınız: “Televizyon çıkalı eski muhabbetler kalmadı.” Biz bu haklı sözleri değiştirerek şöyle diyoruz: “Televizyon çıkalı anne babalar çocuklarına eskisi kadar zaman ayıramaz oldu.” Anne gündüz televizyon izlerken eteğine yapışan çocuğu başından savmak için “git oyuncaklarınla oyna, görmüyor musun televizyon izliyorum” der. Baba işten dönüp akşam yemeğini yedikten sonra koltuğuna oturur, eline kumandayı alır, saatlerce şu kanal senin bu kanal benim dolaşır durur. Baba özlemi çeken çocuğuna yarım saatini ayırmaz.</p><p><br></p><p>Geliri yerinde, okumuş ailelerin çoğu çocuk odasına da televizyon almaktadır. Alırken çocukla bir anlaşma yapar ve söz vermesini isterler: “Ancak ödevini yapıp dersini çalıştıktan sonra televizyon izleyeceksin.” Çocuk hiç düşünmeden söz verir. Aslında bu anlaşmada iki taraf da birbirini aldatmaktadır. Anne babanın amacı çocuktan kurtulmak, çocuğun da amacı televizyon sahibi olmaktır. Araştırmalar, odasına televizyon alınan çocukların, beklenenin aksine okul başarısında düşme olduğunu göstermektedir. Çocuk, televizyon izleyebilmek için ödevlerini çala kalem yapmakta, derslerine yeterince çalışmamakta ve sınavlara iyi hazırlanamamaktadır.</p><p><br></p><p>Çocuklarda televizyon seyretme alışkanlığı sadece okul başarısını etkilemekle kalmıyor; fiziksel, sosyal, zihinsel ve duygusal gelişimlerini de yavaşlatıyor. Çocuk, televizyon başında yeterince hareket etmediği ve biriken enerjisini harcayamadığı için devamlı kilo almaktadır. Sokakta arkadaşlarıyla oyun oynayan ve koşan bir çocuk birikmiş vücut enerjisini boşalttığı için rahatlamakta; eve sakinleşmiş olarak dönmektedir. Halbuki televizyonun karşısında saatlerce oturan bir çocuk enerjisini boşaltmak şöyle dursun, aksine bu cihazlardan yayılan elektronlara maruz kalmakta ve vücudundaki statik elektrik yükü artmaktadır. Bu sebeple, televizyon bağımlısı çocuklar daha sinirli ve daha saldırgandır. Yaşlarına uygun olmayan programları izlemeleri halinde kafaları karışır, ruh sağlıkları bozulur.</p><p><br></p><p>Televizyona düşkün çocuklarda sosyal beceriler zayıflamaya ve içe dönük bir kişilik gelişmeye başlar. Ailesiyle, arkadaşlarıyla ve diğer insanlarla sosyal ilişki kurmada isteksiz davranırlar. Televizyon izleyen bir çocuk, kendisi birşey üretmemekte, sadece başkaları tarafından üretilen şeyleri izlemekte veya oynamaktadır. Hazırı kullanmaya alışmış bu çocuklarda el becerileri ve motor hareketler gelişmez, büyüklerin yardımı olmadan kendi başlarına bir iş beceremezler. Zihinsel ve duygusal gelişimleri de normal değildir. Olaylar arasında sebep-sonuç ilişkisi kuramaz, bilgiyi yorumlayamazlar. Kitap okumak ve ders çalışmak gibi zihinsel çaba gerektiren işlerden hoşlanmazlar. Televizyon karşısında daima alıcı durumunda oldukları için konuşmaya ihtiyaç duymamakta, dolayısıyla dil becerileri gelişmemektedir. Dil becerileri zayıf olduğu için başkalarıyla diyalog kuramaz, duygularını ve düşüncelerini doğru ifade edemezler.</p><p><br></p><p>Küçük yaştan itibaren televizyon izlemeye alışan çocuklarda gelişim bozuklukları daha belirgin ve daha ciddidir. Bu çocuklar akranlarına nazaran daha geç yürür ve daha geç konuşurlar. Konuşulanları ve kendilerine verilen direktifleri anlamakta güçlük çekerler. Dil becerileri gelişmediği için isteklerini büyüklerin elinden tutarak veya işaret ederek anlatmaya çalışırlar. Anneye aşırı bağımlıdırlar. Yabancılarla duygusal ilişkiye giremezler. Öpülmekten ve kucaklanmaktan hoşlanmazlar. İsimleriyle çağırıldıkları zaman tepki vermezler. Yaşıtlarıyla oyun oynamayı ve oyun kurmayı beceremezler. Ellerini ve parmaklarını iyi kullanamazlar. Çarşı, pazar, toplu taşıma araçları gibi kalabalık yerlerde bulunmaktan hoşlanmaz, huysuzluk gösterirler. Doğuştan zihin geriliği olan ve fazla televizyon izleyen çocuklarda otizm belirtileri artmakta, bu çocukları eğitmek daha da zorlaşmaktadır.</p><p><br></p><p><br></p><p><strong>Çocuklarınıza Zaman Ayırın</strong></p><p><br></p><p>Çocukları televizyon bağımlılığından kurtarmanın tek çaresi onlara zaman ayırmaktır. Anne baba olarak öncelikli görevimiz çocuklarımıza iyi bir eğitim kazandırmaktır. Hiçbir işimiz çocuk eğitiminden daha önemli değildir. Eğer çocukların yapmaktan zevk alacakları müzik, resim, spor, kitap okumak gibi faydalı bir becerileri yoksa; anne babaların televizyonu yasaklamaları problemi çözmeyecek, daha da ağırlaştıracaktır.</p><p><br></p><p>Çocuğunun inatçılığından, söz dinlememesinden, aşırı televizyon izlemesinden ve okuldaki başarısızlığından yakınan bir babaya “çocuğunuza zaman ayırın” tavsiyesinde bulunduğumuzda, “her akşam en az bir saat beraber ders çalışıyoruz, ödevlerine yardım ediyorum, ama değişen bir şey yok” demişti. Gülerek: “Hayır, dedim, bizim kastettiğimiz beraberlik bu değil. Çocuk bu beraberlikten zevk almaz, aksine bir an önce bitmesini ister. Siz çocuğunuza zaman ayırmıyorsunuz, ona ders çalıştırıyorsunuz.”</p><p><br></p><p>Çocuğunuza ayırdığınız zamanın süresi değil, kalitesi önemlidir. Eğer bu beraberlikten iki taraf da zevk alıyorsa, kaliteli bir beraberlik var demektir. Birlikte yürüyüşe çıkmak, çocuk parkına gitmek, piknik yapmak, akşam yemeğinden sonra ailece çaylı-pastalı sohbet etmek, birlikte televizyonda kaliteli bir film veya program izlemek, uyku saatinde çocuğunuza masal veya kısa bir hikaye okumak ilk anda aklımıza gelebilen kaliteli beraberliklerdir.</p><p><br></p><p>Çocuğunuzla birlikte iken iyi bir dinleyici olmalısınız. Çocuk duygularını, hayallerini, düşüncelerini, endişelerini, korkularını çekinmeden dile getirmeli ve sizinle paylaşmalıdır.</p><p>Çocuklarını dinlemeyen anne babalar onları tanımakta güçlük çekerler. Çocuğunuzu ne kadar çok tanırsanız, yetenekleri konusunda beklentileriniz o kadar gerçekçi olur.</p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><strong>Emek Kavramına Farklı Bir Bakış</strong></p><p><br></p><p><br></p><p>Emek, varlığın kendisiyle bir bütündür. Varolan her birey emeğin birer parçasıdır. Okuluna giden öğretmenin sınıfta ders anlatması bir emek olduğu gibi, onu dinleyen öğrencinin sarfettiği çaba da bir emektir, aynı zamanda öğretmeni dinlemeyip başka birşey ile meşgul olan öğrencinin sarfettiği çaba da bir emektir.</p><p><br></p><p>Emek her alandadır, benim bu yazıyı yazarken, sizin okurken ayırdığınız vakitte emek ile ilintili bir durumdur.</p><p><br></p><p>Yukarıda bahsettiğim konular, emeğin hizmet yönünden anlaşılabilmesi için genel ve basit örneklerdi, birde emeğin üretim alanında yeri vardır ki, üzerinde hassasiyetle durmamız gereken bir durumdur.</p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><strong>Eğitim Köleleştiriyor mu?</strong></p><p><br></p><p>Yıllar önce o zamanlar çok popüler bir haftalık dergi olan Nokta İstanbul’da ilginç bir deney yapmıştı. Bir tiyatro sanatçısı olan Ezel Akay eline bir megafon alarak koyu renk elbiseler ve siyah pardesüler giyen ekibiyle birlikte önce güvercinleriyle ünlü Yenicamii’nin arkasındaki parka giderler. Parkta oturan gezen etrafı seyreden bir sürü insan vardı. Akay elindeki megafonla kalabalığa doğru sert bir emir verir: “Herkes ayağa kalksın!” Emri duyan Akay’ı ve ekibini gören istisnasız herkes derhal ayağa kalkar.</p><p><br></p><p>Sonra Eminönü İskelesi’ne geçerler. Akay yine sert bir emirle: “Herkes yere çöksün!” diye bağırır. Gemiden inenler bilet kuyruğunda bekleyenler simitçiler işportacılar emri duyan herkes yere çöker.</p><p>Sonra Mecidiyeköy’deki stadyumun önüne giderler. Megafondan: “Herkes ellirini kaldırıp duvara yaslansın!” emri duyuldu. Stadyuma girmek için kuyrukta bekleyen futbol seyircileri kokoreççiler bayrakçılar derhal emre uyarlar.</p><p><br></p><p>Daha sonra da ekip bir fabrikanın önüne giderler. Mesai saati başlamak üzeredir. Fabrikanın girişine bir masa koyarlar ve masanın üzerinde düzmece bir evrak yerleştirerek işçilere emiri verirler: “Herkes içeriye girerken bu kâğıtlara parmak basacak!” Giren basar giren basar. Kimsenin aklına “siz kimsiniz hemşehrim? Neden bu kâğıtlara parmak basıyoruz?” diye sormak gelmez.</p><p><br></p><p>Son olarak da Beyoğlu’na gelirler. İstiklal Caddesinde gezinen vitrinleri seyreden kalabalığa yine sert bir emir verilir: “Herkes sıraya girsin arama var!” Emri duyan herkes koyun sürüsü gibi sessizce sıraya girer. Ancak caddede dolaşan bir çift bu emre uymaz.</p><p><br></p><p>Ekiptekilerden biri onlara doğru bağırır: “Hey siz ikiniz! Emri duymadınız mı?” Kendilerine seslenildiğini anlayan ve herkesin sıraya girdiğini gören adam cevap verir: “Who are you? What is happening here?” Sıraya girenler içerisindeki kravatlı takım elbiseli bir bey ekibe yardımcı olmanın verdiği gurur ve heyecanla lafa karışır: “Adam turist İngilizce konuşuyor.” Ekip elemanı gülmemek için kendisini zor tutar:</p><p><br></p><p>“Ne diyor peki?”</p><p>“Siz kimsiniz burada neler oluyor?”</p><p><br></p><p>Ve o iki turistin haricinde hiç kimse neler olup bittiğini kendilerine böyle gün ortasında emirler yağdırıp sıraya sokanların kim olduğunu sormaz ya da soramaz.</p>', 0, 0, 1, '2026-03-01 04:09:11', '2026-03-01 04:09:11', NULL, NULL, 0, NULL, NULL, NULL),
(77, 37, 1, '<p>Mevlâna, en mükemmel varlık olarak yaratılan insana büyük değer vermiştir. Kendi özünün farkına varıp aslını idrâk eden insan, yüce Allah\'ın huzurunda saygıyla eğilir ve başkalarının eksiklerini görüp kusurlarıyla uğraşmaz. Mevlâna, bilgelik, sevgi ve hoşgörü gibi ahlâkî iyilikleri şahsında toplayan insan-ı kâmili ney ile sembolize eder.</p><p>1 Sözlükte \"e-n-s\" kök fiilinden türetilen \"ins\" ve \"insan\" kelim esi, insanlık nevine ait bir şa ¬ hıs, kabile, grup, insan topluluğu, bir şeyin ortaya çıkması, vahşiliğin zıddı olan medenîlik, yakınlık, sevim li olmak, alışm ak, göz bebeği, siyah nokta, parmak ucu, düşünm ek ve işitm ek anlam larına gelir. Bu m analardan çıkan son u ca göre insan, vahşiliği terk eden, m edenî olan, yakınlık duyduğu şeylere alışabilen bir varlıktır. el-Cevherî, es-Sıhah, (I-VI), Kahire 1982, III, 904-906; Rağıb el-Isfahânî, Müfredâtü Elfâzı\'l-Kur\'an, tahkik: Safvân Adnân Dâvûdî, BeyrutDımaşk 1997,94; İbn Fâris, Mucemu Makâyısı\'l-Luğa, I, 145; İbn Manzûr, L isan u lA rab, I, 147-150; M uham m ed Ali et-Tehânevî, Keşşâfu Istılahâtı\'l-Fünûn, (I-II), Editör: Refik el-A cem , Beyrut 1996, I, 277-280.</p><p>2 Nisa suresi, 4/28.</p><p>3 İbrahim Suresi, 14/34; İsrâ suresi, 17/67; Hac suresi, 22/16; Şûrâ suresi, 42/48; Zuhruf suresi, 43/15; Âdiyat suresi, 100/6.</p><p>4 İsrâ suresi, 17/11.</p><p>5 İsrâ suresi, 17/100.</p><p>6 Kehf suresi, 18/54.</p><p>7 Ahzâb suresi, 33/72.</p><p>8 M eâric suresi, 70/19.</p><p>9 İnşikâk suresi, 84/6.</p><p>10 İsrâ suresi 17/70.</p><p>11 Nisa suresi 4/113.</p><p>12 Tîn suresi, 95/4.</p><p>13 O sm an Türer, \"Tasavvufî D üşüncede İnsan\", Tasavvuf, Yıl: 2, Sayı:5, Ocak 2001 Ankara, 9-15.</p><p>14 Naci Okçu, Şeyh Gâlib (Hayatı, Edebî Kişilği, Eserleri, Şiirlerinin Umumi Tahlili ve Divanının Tenkitli Metni), Ankara 1993, c. I, s. 318-320. Tercî-i bend.</p><p>15 Râğıb el-Isfahânî, 561; Seyyid Şerif Cürcânî, Kitâbu\'t-Ta\'rîfât, Kahire 1991, 249.</p><p>16 Râğıb el-Isfahânî, 561.</p><p>17 Mevlâna, Mesnevî, çeviren: Şefik Can, İstanbul 1995, c. IV, 521-524.</p><p>18 Mevlâna, Mesnevî, c. VI, 4206-4360.</p><p>19 Ahmed Eflâkî, Ariflerin Menkıbeleri (Menâkıbu\'l-ârifin), çeviren: Tahsin Yazıcı 3. baskı İstanbul 1995, II, 124-125.</p><p>20 M evlâna, Mesnevî, II, 881-882.</p><p>21 Şefik Can, Mevlâna Hayatı, Şahsiyeti ve Fikirleri, İstanbul 1995, 100.</p><p>22 Hucurât suresi 49/13.</p><p>23 M ehm et Demirci, \"Nûr-ı M uham m edî\", DEÜlFDergisi I, İzmir 1983, 239-245.</p><p>24 Muhyiddin İbnü’I-Arabî, el-Fütûhâtü\'l-Mekkiyye, Kahire 1293 h., I, 153-155.</p><p>25 Muhyiddin İbnü’l-Arabî, Füsûsü\'l-Hikem, M ısır 1946, 31-33; Toshihiko Izutsu, İbnü\'l-Arabî\'nin Fusûs\'undaki Anahtar Kavramlar, çeviren: Ahmed Yüksel Özemre, İstanbul 1998, 317, 313, 332. İbnü’I-Arabî insan-ı kâmili açıklarken zikrettiği \"Allah Âdem ’i kendi sûreti üzere yarattı.\" (Aclûnî, Keşfu\'l-Hafâ, I, 379, 1215 nolu hadis; Süyûtî, el-Câm iu’s-Sağir, I, 532, 3928 nolu hadis) hadisindeki zamirin Allah’a râci olduğunu ifade eder ve bu görüşü \"rahm an suretinde\" hadisinin desteklediğini belirtir. Ona göre bu durum fark makâmı olarak düşünüldüğünde m ana \"Allah’ın suretinde\", cem makâmı olarak düşünülürse \"Hak m akâm ında kulun varlığı, yani kulun sureti\" şeklinde olur. Âdem’den kastedilen, eşyanın zuhuruna sebep olan, bütün m evcudatın kendisinde toplandığı, âlem in ruhu ve zübdesi kılınan insan-ı kâmildir. İsim ve sıfatlar âlem i, nispet ve izafet kabilinden olup âlem lerin Rabbi m ertebesin e delâlet eder. Bu m ertebenin tafsil edilm iş şekli âlem ; özü ise Âdem’dir. Âlem isim lerin aynası; Âdem ise müsem m anın aynasıdır. İşte bu m ana sebebiyle \"Allah Teâlâ Âdem’i kendi sureti üzere yarattı\" buyrulmuştur. Allah Âdem ’i hakikat suretinde yaratm ıştır. Cem âlem inde O ’nun için bir suret tasavvur edilem ez. Suret ancak fark âlem inde olur. İbnü’1-Arabî, el-Fütûhâtül-M ekkiyye, I, 136¬ 137; A. Avni Konuk, Füsûsul-Hikem Tercüme ve Şerhi, (I-IV), Hazırlayanlar: M. Tahralı-S. Eraydın, İstanbul 1987-1990, IV, 137; İsm ail Fennî Ertuğrul, Vahdet-i Vücûd ve İbn Arabi, Hazırlayan: M ustafa Kara, İstanbul 1991, 33-39.</p><p>26 Abdülkerim el-Cîlî, İnsan-ı Kâmil, Çeviren: Abdülaziz M ecdi Tolun, (Hazırlayanlar: Selçuk Eraydın, Ekrem Demirli, Abdullah Kartal), İstanbul 1998, 345-346.</p><p>27 M ustafa Kara, \"M evlâna, M esnevî ve Ney\", Ney\'e Dâir, Konya 2006, s. 15.</p><p>28 Mevlâna, Mesnevî, c.I, 1-4.</p><p>29 M ehm et Demirci, Mevlâna\'dan Düşünceler, Konya 2006, ss. 106-109.</p><p>30 Mevlâna, Divân-ı Kebîr, Hazırlayan: Abdülbâki Gölpınarlı, Ankara 1992, c. II, 3929-3937.</p><p>31 Mevlâna, Mesnevî, c. II, 1395.</p><p>32 İsm ail Yakıt, Batı Düşüncesi ve Mevlâna, İstanbul 1993, s. 36.</p><p>33 M evlâna, Mesnevî, c. V, 3571-3576.</p><p>34 M evlâna, Mesnevî, c. III, 2701-2730.</p><p>35 M evlâna, Mesnevî, c. III, 265-266.</p><p>36 M evlâna, Mesnevî, c. VI, 2450-2452.</p><p>37 Mevlâna, Mesnevî, c. III, 1789, 1798.</p><p>38 Mevlâna, Mesnevî, c. V, 363-364.</p><p>39 Mevlâna, Mesnevî, c. IV, 2484, 2519-2522.</p><p>40 Mevlâna, Mesnevî, c. VI, 2547-2549, 2564-2566.</p><p>41 Mevlâna, Mesnevî, c. VI, 2505.</p><p>42 M evlâna, Mesnevî, c. IV, 2339-2345.</p><p>43 M evlâna, Mesnevî, c. VI, 2080-2094</p><p>44 M evlâna, Mesnevî, c. VI, 2624-2625.</p><p>45 M evlâna, Mesnevî, c. II, 2344-2352.</p><p>46 Mevlâna, Divân-ı Kebîr, c. II, 3116-3124, 880-883.</p><p>47 Mevlâna, Mesnevî, c. II, 3215-3218.</p><p>48 Mevlâna, Mesnevî, c. II, 3317-3319.</p><p>49 Bediuzzaman Firuzanfer, Mevlâna Celâleddin Rûmî, çeviren: Feridun Nâfiz Uzluk, İstanbul 1997, s.37.</p>', '<p>Mevlâna, en mükemmel varlık olarak yaratılan insana büyük değer vermiştir. Kendi özünün farkına varıp aslını idrâk eden insan, yüce Allah\'ın huzurunda saygıyla eğilir ve başkalarının eksiklerini görüp kusurlarıyla uğraşmaz. Mevlâna, bilgelik, sevgi ve hoşgörü gibi ahlâkî iyilikleri şahsında toplayan insan-ı kâmili ney ile sembolize eder.</p><p>1 Sözlükte \"e-n-s\" kök fiilinden türetilen \"ins\" ve \"insan\" kelim esi, insanlık nevine ait bir şa ¬ hıs, kabile, grup, insan topluluğu, bir şeyin ortaya çıkması, vahşiliğin zıddı olan medenîlik, yakınlık, sevim li olmak, alışm ak, göz bebeği, siyah nokta, parmak ucu, düşünm ek ve işitm ek anlam larına gelir. Bu m analardan çıkan son u ca göre insan, vahşiliği terk eden, m edenî olan, yakınlık duyduğu şeylere alışabilen bir varlıktır. el-Cevherî, es-Sıhah, (I-VI), Kahire 1982, III, 904-906; Rağıb el-Isfahânî, Müfredâtü Elfâzı\'l-Kur\'an, tahkik: Safvân Adnân Dâvûdî, BeyrutDımaşk 1997,94; İbn Fâris, Mucemu Makâyısı\'l-Luğa, I, 145; İbn Manzûr, L isan u lA rab, I, 147-150; M uham m ed Ali et-Tehânevî, Keşşâfu Istılahâtı\'l-Fünûn, (I-II), Editör: Refik el-A cem , Beyrut 1996, I, 277-280.</p><p>2 Nisa suresi, 4/28.</p><p>3 İbrahim Suresi, 14/34; İsrâ suresi, 17/67; Hac suresi, 22/16; Şûrâ suresi, 42/48; Zuhruf suresi, 43/15; Âdiyat suresi, 100/6.</p><p>4 İsrâ suresi, 17/11.</p><p>5 İsrâ suresi, 17/100.</p><p>6 Kehf suresi, 18/54.</p><p>7 Ahzâb suresi, 33/72.</p><p>8 M eâric suresi, 70/19.</p><p>9 İnşikâk suresi, 84/6.</p><p>10 İsrâ suresi 17/70.</p><p>11 Nisa suresi 4/113.</p><p>12 Tîn suresi, 95/4.</p><p>13 O sm an Türer, \"Tasavvufî D üşüncede İnsan\", Tasavvuf, Yıl: 2, Sayı:5, Ocak 2001 Ankara, 9-15.</p><p>14 Naci Okçu, Şeyh Gâlib (Hayatı, Edebî Kişilği, Eserleri, Şiirlerinin Umumi Tahlili ve Divanının Tenkitli Metni), Ankara 1993, c. I, s. 318-320. Tercî-i bend.</p><p>15 Râğıb el-Isfahânî, 561; Seyyid Şerif Cürcânî, Kitâbu\'t-Ta\'rîfât, Kahire 1991, 249.</p><p>16 Râğıb el-Isfahânî, 561.</p><p>17 Mevlâna, Mesnevî, çeviren: Şefik Can, İstanbul 1995, c. IV, 521-524.</p><p>18 Mevlâna, Mesnevî, c. VI, 4206-4360.</p><p>19 Ahmed Eflâkî, Ariflerin Menkıbeleri (Menâkıbu\'l-ârifin), çeviren: Tahsin Yazıcı 3. baskı İstanbul 1995, II, 124-125.</p><p>20 M evlâna, Mesnevî, II, 881-882.</p><p>21 Şefik Can, Mevlâna Hayatı, Şahsiyeti ve Fikirleri, İstanbul 1995, 100.</p><p>22 Hucurât suresi 49/13.</p><p>23 M ehm et Demirci, \"Nûr-ı M uham m edî\", DEÜlFDergisi I, İzmir 1983, 239-245.</p><p>24 Muhyiddin İbnü’I-Arabî, el-Fütûhâtü\'l-Mekkiyye, Kahire 1293 h., I, 153-155.</p><p>25 Muhyiddin İbnü’l-Arabî, Füsûsü\'l-Hikem, M ısır 1946, 31-33; Toshihiko Izutsu, İbnü\'l-Arabî\'nin Fusûs\'undaki Anahtar Kavramlar, çeviren: Ahmed Yüksel Özemre, İstanbul 1998, 317, 313, 332. İbnü’I-Arabî insan-ı kâmili açıklarken zikrettiği \"Allah Âdem ’i kendi sûreti üzere yarattı.\" (Aclûnî, Keşfu\'l-Hafâ, I, 379, 1215 nolu hadis; Süyûtî, el-Câm iu’s-Sağir, I, 532, 3928 nolu hadis) hadisindeki zamirin Allah’a râci olduğunu ifade eder ve bu görüşü \"rahm an suretinde\" hadisinin desteklediğini belirtir. Ona göre bu durum fark makâmı olarak düşünüldüğünde m ana \"Allah’ın suretinde\", cem makâmı olarak düşünülürse \"Hak m akâm ında kulun varlığı, yani kulun sureti\" şeklinde olur. Âdem’den kastedilen, eşyanın zuhuruna sebep olan, bütün m evcudatın kendisinde toplandığı, âlem in ruhu ve zübdesi kılınan insan-ı kâmildir. İsim ve sıfatlar âlem i, nispet ve izafet kabilinden olup âlem lerin Rabbi m ertebesin e delâlet eder. Bu m ertebenin tafsil edilm iş şekli âlem ; özü ise Âdem’dir. Âlem isim lerin aynası; Âdem ise müsem m anın aynasıdır. İşte bu m ana sebebiyle \"Allah Teâlâ Âdem’i kendi sureti üzere yarattı\" buyrulmuştur. Allah Âdem ’i hakikat suretinde yaratm ıştır. Cem âlem inde O ’nun için bir suret tasavvur edilem ez. Suret ancak fark âlem inde olur. İbnü’1-Arabî, el-Fütûhâtül-M ekkiyye, I, 136¬ 137; A. Avni Konuk, Füsûsul-Hikem Tercüme ve Şerhi, (I-IV), Hazırlayanlar: M. Tahralı-S. Eraydın, İstanbul 1987-1990, IV, 137; İsm ail Fennî Ertuğrul, Vahdet-i Vücûd ve İbn Arabi, Hazırlayan: M ustafa Kara, İstanbul 1991, 33-39.</p><p>26 Abdülkerim el-Cîlî, İnsan-ı Kâmil, Çeviren: Abdülaziz M ecdi Tolun, (Hazırlayanlar: Selçuk Eraydın, Ekrem Demirli, Abdullah Kartal), İstanbul 1998, 345-346.</p><p>27 M ustafa Kara, \"M evlâna, M esnevî ve Ney\", Ney\'e Dâir, Konya 2006, s. 15.</p><p>28 Mevlâna, Mesnevî, c.I, 1-4.</p><p>29 M ehm et Demirci, Mevlâna\'dan Düşünceler, Konya 2006, ss. 106-109.</p><p>30 Mevlâna, Divân-ı Kebîr, Hazırlayan: Abdülbâki Gölpınarlı, Ankara 1992, c. II, 3929-3937.</p><p>31 Mevlâna, Mesnevî, c. II, 1395.</p><p>32 İsm ail Yakıt, Batı Düşüncesi ve Mevlâna, İstanbul 1993, s. 36.</p><p>33 M evlâna, Mesnevî, c. V, 3571-3576.</p><p>34 M evlâna, Mesnevî, c. III, 2701-2730.</p><p>35 M evlâna, Mesnevî, c. III, 265-266.</p><p>36 M evlâna, Mesnevî, c. VI, 2450-2452.</p><p>37 Mevlâna, Mesnevî, c. III, 1789, 1798.</p><p>38 Mevlâna, Mesnevî, c. V, 363-364.</p><p>39 Mevlâna, Mesnevî, c. IV, 2484, 2519-2522.</p><p>40 Mevlâna, Mesnevî, c. VI, 2547-2549, 2564-2566.</p><p>41 Mevlâna, Mesnevî, c. VI, 2505.</p><p>42 M evlâna, Mesnevî, c. IV, 2339-2345.</p><p>43 M evlâna, Mesnevî, c. VI, 2080-2094</p><p>44 M evlâna, Mesnevî, c. VI, 2624-2625.</p><p>45 M evlâna, Mesnevî, c. II, 2344-2352.</p><p>46 Mevlâna, Divân-ı Kebîr, c. II, 3116-3124, 880-883.</p><p>47 Mevlâna, Mesnevî, c. II, 3215-3218.</p><p>48 Mevlâna, Mesnevî, c. II, 3317-3319.</p><p>49 Bediuzzaman Firuzanfer, Mevlâna Celâleddin Rûmî, çeviren: Feridun Nâfiz Uzluk, İstanbul 1997, s.37.</p>', 0, 0, 1, '2026-03-01 04:09:55', '2026-03-01 04:09:55', NULL, NULL, 0, NULL, NULL, NULL),
(78, 38, 1, '<p><strong>MegaforBB Forum</strong> sisteminde Haftalık geliştirme planı olarak MArt ayının ilk geniş büyük güncellemesini Döküman sistemi olarak yapıyoruz.</p><p><a href=\"https://docusaurus.io/blog/releases/3.9\">Docusaurus</a> Biliyor olmalısınız, biz bu sistemi yazılım ve ürün geliştiriciler için foruma direkt olarak dahil ediyoruz. Tüm sisteme entegra çalışır ve herhangi bir uyumsuzluk sorunu olmaması için çekirdeğe gömülü olarak gelir.</p><p>Aktif veya kapalı durumda kullanılabileceği için herhangi bir ek yük bindirmeyecektir. </p><p><br></p><p>Merhak edip gezip indelemek isteyenler için: <a href=\"https://www.megaforbb.com.tr/documentation/\">MegaforBB Dokümanlar</a></p><p><br></p><p>Gelişmelerimizi takip etmeye devam edin.</p>', '<p><strong>MegaforBB Forum</strong> sisteminde Haftalık geliştirme planı olarak MArt ayının ilk geniş büyük güncellemesini Döküman sistemi olarak yapıyoruz.</p><p><a href=\"https://docusaurus.io/blog/releases/3.9\">Docusaurus</a> Biliyor olmalısınız, biz bu sistemi yazılım ve ürün geliştiriciler için foruma direkt olarak dahil ediyoruz. Tüm sisteme entegra çalışır ve herhangi bir uyumsuzluk sorunu olmaması için çekirdeğe gömülü olarak gelir.</p><p>Aktif veya kapalı durumda kullanılabileceği için herhangi bir ek yük bindirmeyecektir. </p><p><br></p><p>Merhak edip gezip indelemek isteyenler için: <a href=\"https://www.megaforbb.com.tr/documentation/\">MegaforBB Dokümanlar</a></p><p><br></p><p>Gelişmelerimizi takip etmeye devam edin.</p>', 0, 0, 1, '2026-03-01 16:46:33', '2026-03-01 16:46:33', NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `posts` (`id`, `topic_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(79, 5, 1, '<ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Çevrimiçi üyeleri - Botlar sayfası yapıldı.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p></li><li class=\"task-list-item\" data-task=\"true\"><p>Bugün yamayı planladığım farklı bir şeyler daha var bitmeden yazmayayım :)</p></li></ul>', '<ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Çevrimiçi üyeleri - Botlar sayfası yapıldı.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p></li><li class=\"task-list-item\" data-task=\"true\"><p>Bugün yamayı planladığım farklı bir şeyler daha var bitmeden yazmayayım :)</p></li></ul>', 0, 0, 0, '2026-03-01 17:30:52', '2026-03-01 17:31:32', '2026-03-01 17:31:32', 1, 1, NULL, NULL, NULL),
(80, 39, 1, '<p>Bu konunun içeriğini sadece konuyu açan kullanıcılar ve yöneticiler görecektir.</p><p><br></p>', '<p>Bu konunun içeriğini sadece konuyu açan kullanıcılar ve yöneticiler görecektir.</p><p><br></p>', 0, 0, 1, '2026-03-01 22:56:42', '2026-03-01 23:13:46', NULL, NULL, 0, NULL, NULL, NULL),
(81, 29, 129, '<p>Cevap kısmında da dosya yüklenebiliyr olması iyi olmuş</p>', '<p>Cevap kısmında da dosya yüklenebiliyr olması iyi olmuş</p>', 0, 0, 0, '2026-03-01 23:25:10', '2026-03-01 23:25:10', NULL, NULL, 0, NULL, NULL, NULL),
(82, 5, 1, '<p><br></p><p><br></p><blockquote><p><strong>Sinek10 yazdı:</strong></p><p><br></p><p>Çevrimiçi üyeleri - Botlar sayfası yapıldı.</p><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p><p>Bugün yamayı planladığım farklı bir şeyler daha var bitmeden yazmayayım :)</p></blockquote><p>Tamamlandı, Private konu sistemi: https://www.megaforbb.com.tr/topic/ozel-private-konu-test-39</p>', '<p><br></p><p><br></p><blockquote><p><strong>Sinek10 yazdı:</strong></p><p><br></p><p>Çevrimiçi üyeleri - Botlar sayfası yapıldı.</p><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p><p>Bugün yamayı planladığım farklı bir şeyler daha var bitmeden yazmayayım :)</p></blockquote><p>Tamamlandı, Private konu sistemi: https://www.megaforbb.com.tr/topic/ozel-private-konu-test-39</p>', 0, 0, 0, '2026-03-01 23:28:00', '2026-03-01 23:28:00', NULL, NULL, 0, NULL, NULL, NULL),
(83, 40, 129, '<p>Bu konda  Anti Bump sisteminin sisteme dahil edildiğini ve  ardışık olarak peş peşe mesaj yazılamayacağını test edeceğiz.</p>', '<p>Bu konda  Anti Bump sisteminin sisteme dahil edildiğini ve  ardışık olarak peş peşe mesaj yazılamayacağını test edeceğiz.</p>', 0, 0, 1, '2026-03-01 23:43:52', '2026-03-01 23:43:52', NULL, NULL, 0, NULL, NULL, NULL),
(84, 40, 129, '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/03/ad2f8dcf1190106d.png\" alt=\"image.png\" contenteditable=\"false\">Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda 1 dk yeterli.</p>', '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/03/ad2f8dcf1190106d.png\" alt=\"image.png\" contenteditable=\"false\">Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda 1 dk yeterli.</p>', 1, 0, 0, '2026-03-01 23:45:42', '2026-03-02 00:07:01', NULL, NULL, 0, NULL, NULL, NULL),
(85, 40, 1, '<p><br></p><p><br></p><blockquote><p><strong>kaan yazdı:</strong></p><p><br></p><p>Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda 1 dk yeterli.</p></blockquote><p>Eskiler bilir Cotonti\'de yerleşik olarak gelen bir özelliktir bu anti bump sistemi :) Çok beğendiğim birşeydi, neden olmasın ki ?</p>', '<p><br></p><p><br></p><blockquote><p><strong>kaan yazdı:</strong></p><p><br></p><p>Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda 1 dk yeterli.</p></blockquote><p>Eskiler bilir Cotonti\'de yerleşik olarak gelen bir özelliktir bu anti bump sistemi :) Çok beğendiğim birşeydi, neden olmasın ki ?</p>', 1, 0, 0, '2026-03-02 00:00:05', '2026-03-02 00:11:33', NULL, NULL, 0, NULL, NULL, NULL),
(86, 41, 1, '<p>Bu konuda MegaforBB soru - cevap ve çözüm sistemini test ediyoruz. </p>', '<p>Bu konuda MegaforBB soru - cevap ve çözüm sistemini test ediyoruz. </p>', 0, 0, 1, '2026-03-02 00:14:11', '2026-03-02 00:14:11', NULL, NULL, 0, NULL, NULL, NULL),
(87, 41, 129, '<p>Şu anda mantık olarak sistemde çalışıyor  ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)</p>', '<p>Şu anda mantık olarak sistemde çalışıyor  ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)</p>', 0, 0, 0, '2026-03-02 00:14:58', '2026-03-02 00:14:58', NULL, NULL, 0, NULL, NULL, NULL),
(88, 41, 1, '<p><br></p><p><br></p><blockquote><p><strong>kaan yazdı:</strong></p><p><br></p><p>Şu anda mantık olarak sistemde çalışıyor ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)</p></blockquote><p>Haklı olduğun için alıntı yapıp cevap veriyorum :)</p>', '<p><br></p><p><br></p><blockquote><p><strong>kaan yazdı:</strong></p><p><br></p><p>Şu anda mantık olarak sistemde çalışıyor ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)</p></blockquote><p>Haklı olduğun için alıntı yapıp cevap veriyorum :)</p>', 0, 0, 0, '2026-03-02 00:17:59', '2026-03-02 00:17:59', NULL, NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `post_edits`
--

CREATE TABLE `post_edits` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `old_body` longtext NOT NULL,
  `edit_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `post_edits`
--

INSERT INTO `post_edits` (`id`, `post_id`, `user_id`, `old_body`, `edit_reason`, `created_at`) VALUES
(1, 2, 1, '<p>MegaforBB kurulumu için sadece veritabanını ve sistem dosyalarını indirip FTP\'ye yükleyip .env dosyasından birbirine bağlamanız yeterli olacaktır.</p>', NULL, '2026-02-23 04:28:09'),
(2, 43, 129, '<p><span class=\"comment-copy\" itemprop=\"text\">I\'ve yet to nail down exactly\r\n why this works sometimes and not others, but for anyone wanting to \r\nquickly toggle errors in a php script (or enable them via a <code>$_REQUEST</code> parameter) these two lines will work most of the time.</span></p>', NULL, '2026-02-23 17:15:45'),
(3, 36, 1, 'Harika bir çalışma! İlerleyen günlerde eklenecek yeni modülleri heyecanla bekliyoruz.', NULL, '2026-02-23 18:07:46'),
(4, 2, 1, '<p>MegaforBB kurulumu için sadece veritabanını ve sistem dosyalarını \r\nindirip FTP\'ye yükleyip .env dosyasından birbirine bağlamanız yeterli \r\nolacaktır.</p>', NULL, '2026-02-23 18:14:13'),
(5, 35, 1, 'Bence bu özellik çok kullanışlı olmuş. Geliştiren ekibin ellerine sağlık. Siz ne düşünüyorsunuz?', NULL, '2026-02-23 18:16:44'),
(6, 45, 1, '<p>Merhabalar, Bu konuda @kaan ile birlikte kullanıcı ve etiket sistemini test ediyoruz.</p><p>Post etiket: yüzlerce cevaba ulaşan konuların içinden hangi mesajdan bahsettiğinizi alıntı yapmadan etiket ile belirtme sistemidir. örneğin bu konunun #1 yaptığım için ilk mesajını yani bu mesajı etiketleme sistemidir.</p>', NULL, '2026-02-24 00:55:52'),
(7, 2, 1, '<p>MegaforBB kurulumu için sadece veritabanını ve sistem dosyalarını \r\nindirip FTP\'ye yükleyip .env dosyasından birbirine bağlamanız yeterli \r\nolacaktır.</p><p><br></p><p>Nginx için ekstra kural seti:&nbsp;</p><p><br></p><p><br></p>\r\n\r\n<pre>location ^~ /theme-assets/ {\r\n  {{varnish_proxy_pass}}\r\n  proxy_set_header Host $host;\r\n  proxy_set_header X-Forwarded-Host $host;\r\n  proxy_set_header X-Real-IP $remote_addr;\r\n  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\r\n  proxy_set_header X-Forwarded-Proto $scheme;\r\n  proxy_hide_header X-Varnish;\r\n  proxy_redirect off;\r\n  proxy_max_temp_file_size 0;\r\n  proxy_connect_timeout 720;\r\n  proxy_send_timeout 720;\r\n  proxy_read_timeout 720;\r\n  proxy_buffer_size 128k;\r\n  proxy_buffers 4 256k;\r\n  proxy_busy_buffers_size 256k;\r\n  proxy_temp_file_write_size 256k;\r\n}\r\n</pre>\r\n\r\nBu Nginx kurallarını uygulamanız gerekmektedir, css ve js dosyalarının sorunsuz çalışması için.', NULL, '2026-02-24 01:14:03'),
(9, 47, 1, '<p>Kullanıcı kayıt sırasında Captcha doğrulaması gerçekleştiği halde hata veriyor Captcha doğrulaması başarısız hatası basıyor.</p><p>Sorunun temeli Cloudflare\'mı yoksa sistemdeen mi kaynaklanıyor bilmiyorum, incelenmesi gerekiyor.@sinek10</p>', NULL, '2026-02-24 04:14:24'),
(10, 47, 1, '<p>Kullanıcı kayıt sırasında Captcha doğrulaması gerçekleştiği halde hata veriyor Captcha doğrulaması başarısız hatası basıyor.</p><p>Sorunun temeli Cloudflare\'mı yoksa sistemdeen mi kaynaklanıyor bilmiyorum, incelenmesi gerekiyor.@Sinek10</p>', NULL, '2026-02-24 04:23:24'),
(11, 47, 1, '<p>Kullanıcı kayıt sırasında Captcha doğrulaması gerçekleştiği halde hata veriyor Captcha doğrulaması başarısız hatası basıyor.</p><p>Sorunun temeli Cloudflare\'mı yoksa sistemdeen mi kaynaklanıyor bilmiyorum, incelenmesi gerekiyor.</p>', NULL, '2026-02-24 04:24:04'),
(12, 46, 1, '<p>Örnek bir cevap vererek Etiket sisteminin anlatılması ve @slaweally ile deniyoruz.&nbsp;</p><p>Bu mesajın #2 yaparak etiketlemiş olabiliriz.</p>', NULL, '2026-02-24 04:32:41'),
(14, 50, 1, '<p><img style=\"width: 1480px;\" src=\"/uploads/images/2026/02/fd347efa7e0da350.png\"></p><p><br></p><p></p><blockquote class=\"border-l-4 border-blue-500 pl-3 py-2 my-2 bg-blue-50 rounded-r text-blue-900\" data-author=\"kaan\"><p><strong>kaan yazdı:</strong><br>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor.&amp;nbsp;@Sinek10</p></blockquote><p><br></p><p>Sorun çözülmüştür, Aslında çalışıyor ancak tema\'da göstermemişiz, hallettik.</p><p></p>', NULL, '2026-02-24 10:14:11'),
(15, 53, 1, '<p><br></p><p></p><blockquote class=\"border-l-4 border-blue-500 pl-3 py-2 my-2 bg-blue-50 rounded-r text-blue-900\" data-author=\"Sinek10\"><p><strong>Sinek10 yazdı:</strong><br>Konu düzenleme geçmişinde sınırsız geçmiş verisi tutuluyor, buna bir çözüm bulmak gerekiyor.Acaba son 3 düzenlemeyi mi saklamak mantıklı yoksa sürüm olarak ilk mesaj son mesaj saklamak mı mantıklı ? Düşünüp uygulamaya koyacağız...</p></blockquote><p><br></p><ol style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235); list-style: decimal; margin: 0.5rem 0px; padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 1rem !important; color: rgb(209, 213, 218); font-family: &quot;Segoe WPC&quot;, &quot;Segoe UI&quot;, sans-serif; font-size: 13.008px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(37, 37, 38); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><li style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235);\"><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235); margin: 0px;\"><strong style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235); font-weight: bolder;\">Konu Düzenleme Loglarının (Geçmişinin) Limitlenmesi:</strong><span>&nbsp;</span>Artık bir yorum ne kadar çok düzenlenirse düzenlensin, veritabanının şişmesini önlemek için yalnızca<span>&nbsp;</span><strong style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235); font-weight: bolder;\">son 3 değişikliği</strong><span>&nbsp;</span>sunucu üzerinde (veritabanı<span>&nbsp;</span><code class=\"whitespace-pre-wrap\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; white-space: pre-wrap; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, &quot;Liberation Mono&quot;, &quot;Courier New&quot;, monospace; font-feature-settings: normal; font-variation-settings: normal; font-size: 0.9em; border-radius: 0.25rem; background-color: rgba(255, 255, 255, 0.1); padding: 0.125rem 0.25rem; color: rgb(209, 213, 218); word-break: break-word;\">post_edits</code><span>&nbsp;</span>tablosunda) tutulacaktır. Mesaj her düzenlendiğinde arkada bu limit kontrol edilecek ve eğer 3\'ten fazla geçmiş kalıntı varsa en eskisi silinerek yer açılacaktır. (Bkz:<span>&nbsp;</span><code class=\"whitespace-pre-wrap\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; white-space: pre-wrap; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, &quot;Liberation Mono&quot;, &quot;Courier New&quot;, monospace; font-feature-settings: normal; font-variation-settings: normal; font-size: 0.9em; border-radius: 0.25rem; background-color: rgba(255, 255, 255, 0.1); padding: 0.125rem 0.25rem; color: rgb(209, 213, 218); word-break: break-word;\">TopicController::updatePost</code>)</p><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235); margin: 0px;\"><br></p><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(67 128 180 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: rgb(229, 231, 235); margin: 0px;\">Son 3 değişiklik mantıklı olarak uygulandı.</p></li></ol><p><br></p><p></p>', NULL, '2026-02-24 11:04:44'),
(18, 59, 1, '<h1>Dil Sistemi ve Twig Entegrasyonu</h1><h2>Genel Bakış</h2><p>MegaforBB dil sistemi, <strong>dosya tabanlı çevirileri</strong> <code data-backticks=\"1\">lang/{locale}.php</code> üzerinden okur ve <strong>veritabanı override</strong>\'ları ile birleştirir. Uygulama genelinde çeviri çağrıları <code data-backticks=\"1\">lang(\'key\')</code> helper\'ı üzerinden yapılır. Twig tarafında aynı helper, <code data-backticks=\"1\">TemplateEngine</code> tarafından global fonksiyon olarak kayıtlıdır.</p><ul><li><p>Çeviri helper\'ı: <a href=\"file:///c:/laragon/www/MegaforBB/forecor/core/helpers.php#L20-L35\">helpers.php</a></p></li><li><p>Translator servisi: <a href=\"file:///c:/laragon/www/MegaforBB/app/Services/Translator.php#L1-L138\">Translator.php</a></p></li><li><p>Twig fonksiyon kayıtları: <a href=\"file:///c:/laragon/www/MegaforBB/app/Core/TemplateEngine.php#L135-L178\">TemplateEngine.php</a></p></li></ul><h2>Locale Çözümleme Sırası</h2><p>Uygulama dili, merkezi olarak <code data-backticks=\"1\">Application::resolveLocale()</code> içinde belirlenir. Öncelik sırası aşağıdaki gibidir:</p><ol><li><p><strong>Session</strong>: <code data-backticks=\"1\">locale</code> key\'i</p></li><li><p><strong>Cookie</strong>: <code data-backticks=\"1\">locale</code> key\'i</p></li><li><p><strong>Accept-Language</strong>: tarayıcı dili, dosya varsa</p></li><li><p><strong>Config</strong>: <code data-backticks=\"1\">app.locale</code> (varsayılan <code data-backticks=\"1\">tr</code>)</p></li></ol><p>Kaynak: <a href=\"file:///c:/laragon/www/MegaforBB/forecor/core/Application.php#L219-L246\">Application.php</a></p><h2>Çeviri Yükleme ve Override Mantığı</h2><p><code data-backticks=\"1\">Translator::load()</code> her locale için önce dosya çevirilerini okur, sonra DB çevirilerini ekler. <code data-backticks=\"1\">array_merge</code> kullanıldığı için <strong>DB kayıtları aynı anahtarda dosya değerini override eder</strong>.</p><ul><li><p>Dosya: <code data-backticks=\"1\">lang/{locale}.php</code></p></li><li><p>DB tablo: <code data-backticks=\"1\">language_lines</code></p></li></ul><p>Kaynaklar:</p><ul><li><p><a href=\"file:///c:/laragon/www/MegaforBB/app/Services/Translator.php#L48-L68\">Translator::load</a></p></li><li><p><a href=\"file:///c:/laragon/www/MegaforBB/app/Models/LanguageLine.php#L15-L29\">LanguageLine::getTranslationsForLocale</a></p></li></ul><h2>Fallback Locale</h2><p>Translator, fallback dili <code data-backticks=\"1\">Language::getDefault()</code> üzerinden belirler; DB hazır değilse <code data-backticks=\"1\">tr</code> kullanır. Böylece anahtar bulunamazsa fallback locale denenir; yine yoksa anahtarın kendisi döner.</p><p><br></p><p>Kaynak: <a href=\"file:///c:/laragon/www/MegaforBB/forecor/core/Application.php#L195-L217\">Application::translator</a>, <a href=\"file:///c:/laragon/www/MegaforBB/app/Services/Translator.php#L70-L94\">Translator::get</a></p><h2>Anahtar Formatı (Flat Key)</h2><p>Dil dosyaları <strong>flat key</strong> formatındadır; nested array yerine <code data-backticks=\"1\">group.key</code> şeklinde string anahtarlar kullanılır.</p><p><br></p><p>Örnekler:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">\'admin.languages.title\' =&gt; \'Dil Yönetimi\',\r\n\'common.login\' =&gt; \'Giriş Yap\',</code></pre></div><p>Kaynak: <a href=\"file:///c:/laragon/www/MegaforBB/lang/tr.php\">lang/tr.php</a></p><h2>Placeholder Desteği</h2><p><code data-backticks=\"1\">lang(\'key\', [\'name\' =&gt; \'Ali\'])</code> çağrısında <code data-backticks=\"1\">:name</code> placeholder\'ları string içinde replace edilir.</p><p><br></p><p>Kaynak: <a href=\"file:///c:/laragon/www/MegaforBB/app/Services/Translator.php#L89-L92\">Translator::get</a></p><h2>Twig Dosyalarında Kullanım</h2><p>Twig içinde <code data-backticks=\"1\">lang()</code> fonksiyonu doğrudan kullanılabilir. <code data-backticks=\"1\">TemplateEngine</code> içinde TwigFunction olarak register edilir ve tüm template\'lerde erişilebilir.</p><p><br></p><p>Örnek:</p><div data-language=\"twig\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"twig\">&lt;h2 class=\"page-title\"&gt;{{ lang(\'admin.languages.title\')|e }}&lt;/h2&gt;</code></pre></div><p>Kaynak:</p><ul><li><p><a href=\"file:///c:/laragon/www/MegaforBB/app/Core/TemplateEngine.php#L135-L178\">TemplateEngine::registerFunctions</a></p></li><li><p><a href=\"file:///c:/laragon/www/MegaforBB/templates/admin/default/views/languages/index.html.twig#L1-L26\">languages/index.html.twig</a></p></li></ul><h2>PHP Tarafında Kullanım</h2><p>Controller veya servislerde doğrudan <code data-backticks=\"1\">lang()</code> helper\'ı çağrılır. Helper, mevcut <code data-backticks=\"1\">Application</code> instance üzerinden <code data-backticks=\"1\">translator()-&gt;get()</code> çağırır.</p><p><br></p><p>Örnek:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">return $this-&gt;view(\'languages/index\', [\r\n    \'pageTitle\' =&gt; lang(\'admin.languages.title\'),\r\n]);</code></pre></div><p>Kaynak:</p><ul><li><p><a href=\"file:///c:/laragon/www/MegaforBB/app/Controllers/AdminLanguageController.php#L64-L103\">AdminLanguageController::index</a></p></li><li><p><a href=\"file:///c:/laragon/www/MegaforBB/forecor/core/helpers.php#L20-L35\">helpers.php</a></p></li></ul><h2>Admin Panel Dil Yönetimi</h2><p>Dil yönetimi ekranı, DB ve dosya tabanlı dilleri birleştirir; çeviri sayısı <code data-backticks=\"1\">Translator::all()</code> ile hesaplanır. Dil ekleme/düzenleme işlemleri DB tarafında <code data-backticks=\"1\">language_lines</code> tablosuna yazılır ve ardından <code data-backticks=\"1\">lang/{code}.php</code> dosyasına export edilir.</p><p><br></p><p>Öne çıkan akışlar:</p><ul><li><p><strong>Listeleme</strong>: DB + dosya taraması birleştirilir</p></li><li><p><strong>Yeni dil</strong>: DB kaydı + opsiyonel çeviri kopyalama</p></li><li><p><strong>Düzenleme</strong>: tüm anahtarlar (varsayılan + mevcut) bir arada gösterilir</p></li><li><p><strong>Kaydetme</strong>: DB update + dosyaya export</p></li><li><p><strong>Varsayılan dil</strong>: <code data-backticks=\"1\">languages.is_default</code> güncellenir</p></li></ul><p>Kaynak: <a href=\"file:///c:/laragon/www/MegaforBB/app/Controllers/AdminLanguageController.php#L1-L468\">AdminLanguageController</a></p><h2>Pratik Notlar</h2><ul><li><p>Dil dosyası yoksa locale, <code data-backticks=\"1\">resolveLocale</code> içinde dosya kontrolü ile elenir.</p></li><li><p>Twig cache çıktıları <code data-backticks=\"1\">storage/views/</code> altında saklanır; dil değişiminde Twig çıktısı tekrar üretilebilir.</p></li><li><p><code data-backticks=\"1\">lang()</code> çağrısı her yerde güvenle kullanılabilir; anahtar bulunamazsa anahtarın kendisi döner.</p></li></ul><p>Kaynaklar:</p><ul><li><p><a href=\"file:///c:/laragon/www/MegaforBB/forecor/core/Application.php#L219-L246\">Application::resolveLocale</a></p></li><li><p><a href=\"file:///c:/laragon/www/MegaforBB/app/Services/Translator.php#L70-L94\">Translator::get</a></p></li></ul>', NULL, '2026-02-26 20:49:00'),
(20, 61, 1, '<p>Moder sistemler, Gelişmiş yönetimi, ve Katı güvenlik kuralları nedeni ile TWIG tema motoru tercih edilmiş ve Tüm forum tema sistemi twig altyapısına taşınmıştır.</p><p>oy:</p>', NULL, '2026-02-26 21:09:28'),
(21, 61, 1, '<p>Modern sistemler, Gelişmiş yönetimi, ve Katı güvenlik kuralları nedeni ile TWIG tema motoru tercih edilmiş ve Tüm forum tema sistemi twig altyapısına taşınmıştır.</p><p>oy:</p>', NULL, '2026-02-26 21:11:37'),
(22, 61, 1, '<p>Modern sistemler, Gelişmiş yönetimi, ve Katı güvenlik kuralları nedeni ile TWIG tema motoru tercih edilmiş ve Tüm forum tema sistemi twig altyapısına taşınmıştır.</p><p><br></p><p>Alttaki cevabı da Gemini verdi :) Bir bildiği var demekki </p><blockquote><p><strong>Güvenlik (Sandbox) Duvarı</strong> Blade içine <code>@php</code> tagı ile doğrudan raw PHP yazılabilir. Müşterilerin kendi temasını düzenleyeceği bir SaaS platformunda Blade kullanırsan sunucuyu ilk günden patlatırlar. Twig\'in izole \"Sandbox\" modu vardır; dışarıdan müdahale eden biri sadece senin izin verdiğin değişkenleri okuyabilir, sistemi hackleyemez.</p><p><strong>Spagetti Koda Geçit Yok</strong> Blade çok laçkadır, view dosyasının içine Controller mantığı ve veritabanı sorgusu yazmaya bile müsaade eder. Twig katıdır, tasarım ile backend kodunu jilet gibi ayırır.</p><p><strong>Symfony Kanı</strong> MegaforBB altyapısında zaten Symfony mimarisini harmanlıyoruz, Twig de doğrudan Symfony\'nin kendi ana motorudur.</p></blockquote><p><br></p><p>oy:</p>', NULL, '2026-02-26 21:12:41'),
(23, 68, 1, '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/e9976083f461a5f7.png\" alt=\"666.png\" contenteditable=\"false\">Sorun çözüldü, Mesaj gönderimi sorunsuz yapılıyor. </p>', NULL, '2026-02-27 18:15:20'),
(24, 69, 129, '<p>Forum sisteminde SEF Url desteği olması gerekiyor şu anda .com.tr/topic/5 şeklinde görünüyor konular. </p>', NULL, '2026-02-28 02:18:33'),
(25, 72, 1, '<p>Merhabalar, Yeni bir özellik olarak duyurmak istediğimiz Profil yorumları sistemini Kullanıma sunduk.</p><p>Bu özellik ile birlikte istediğiniz kullanıcının profil sayfasına kullanıcı hakkında yorum yazabilirsiniz</p><p><br></p><p>Not: Kullanıcı isteğe bağlı olarak bu özelliği kullanmak istiyorsa tabiki :)</p><p><br></p>', NULL, '2026-02-28 03:25:08'),
(26, 1, 1, '<p></p><h2 class=\"\" align=\"center\">MegaforBB - Yeni Nesil Forum Scripti</h2><p><br><br>MegaforBB, \"<b>Güvenli, Güçlü, maksimum performans</b>\" felsefesiyle tasarlanmıştır. Hiçbir ağır framework kullanılmamış (Zend, Laravel Symfony vs. yoktur). Sistemin kalbini oluşturan Forecor (Özel Çekirdek, symfony ve Laravel\'in belirli kütüphanelerini kullanmıştır) sayesinde milisaniyelik tepki süreleriyle çalışır. Yüz binlerce üyesi ve konusu olan büyük toplulukları, sunucu kaynaklarını sömürmeden kolaylıkla kaldırabilmesi için özel olarak optimize edilmiştir.<br><br>Tasarımda günümüz modern standartlarını baz alan TailwindCSS (v3) ve dinamik kullanıcı etkileşimleri için Alpine.js (gerekli yerlerde native vanilla JS) kullanılmıştır. Mobil uyumluluk sonradan eklenmiş bir yama değil, sistemin temel yapıtaşlarından biridir (%100 Responsive).&nbsp; %100 Özel Mimari, PHP 8.2+, saf PDO ve modern TailwindCSS kullanılarak \r\nsıfırdan kodlanan, ultra hızlı, güvenlikli ve SEO uyumlu forum sistemi.<br></p><p><br></p><hr>Diğer forum sistemlerinden ayıran bir çok özellik mevcuttur, Diğerlerinde Modül - Eklenti ile gelen gerekli ve önemli sistem parçacıkları bizde direkt olarak forumun bir parçası olarak geliyor. Sisteme herhangi bir yük bindirimemek için optimize edildi.<p></p><p></p><p><br></p><p>Diğer forum sistemlerinden import işlemi ile içeri aktarma veya taşınma yapılabilr (Şu anda sadece xenforo ve Mybb) destekliyoruz.&nbsp;</p><p>Forum sistemimiz ana giriş rotası Forum, Portal ve Makale olmak üzere 3 farklı seçim yapılabilir bunu farklı kullanıcı türleri için özel olarak yapılandırdık. kullanıp kullnmamak tamamen kullanıcının isteğine bağlı.</p><p><br></p><p>Kategori bazlı veya genel atanabilen \"Konu Ön Ekleri\". Kırmızı bir \"Satıldı\" veya yeşil bir \"Soru\" tagı oluşturup CSS atamalarını doğrudan Admin panelinden yapabilirsiniz. Seçilen prefixler hem konu listelerinde hem konu başlıklarında renkli badge (rozet) olarak sergilenir.</p><p><br></p><p>Klasik Beğeni (+1 Like) butonuna ek olarak kullanıcılara +Rep / -Rep verebilme (Gerekçeli, yorumlu şekilde). Belirli Rep ve Post sayısına ulaşan kullanıcılara otomatik olarak Admin\'in belirlediği rütbe ve Nişanların (Rozetlerin) atanması sistemi.</p><p><br></p><p>XSS açıklarına sıfır tolerans! Geliştirilmiş core_sanitize_html filtresi ile zararlı kod enjeksiyonları HTML formatlama sırasında engellenir. CSRF tokenleri her form için unique yaratılır. PDO Prepared Statements sayesinde SQL Injection imkansızdır.<br><br>Sadece klasik \"konu aç cevapla\" değil. Konular \"Soru\" tipinde açılabilir. Soru tipli başlıklarda kullanıcılar yararlı buldukları mesajlara StackOverflow\'daki gibi Yukarı/Aşağı (Upvote/Downvote) verebilir ve konu sahibi (Veya yetkili) doğru cevabı \"Çözüm olarak işaretle\" diyebilir.<br><br></p><p></p>', 'MegaforBB', '2026-02-28 03:46:49'),
(27, 1, 1, '<p><br></p><h2>MegaforBB - Yeni Nesil Forum Scripti</h2><p>MegaforBB, \"<b>Güvenli, Güçlü, maksimum performans</b>\" felsefesiyle tasarlanmıştır. Hiçbir ağır framework kullanılmamış (Zend, Laravel Symfony vs. yoktur). Sistemin kalbini oluşturan Forecor (Özel Çekirdek, symfony ve Laravel\'in belirli kütüphanelerini kullanmıştır) sayesinde milisaniyelik tepki süreleriyle çalışır. Yüz binlerce üyesi ve konusu olan büyük toplulukları, sunucu kaynaklarını sömürmeden kolaylıkla kaldırabilmesi için özel olarak optimize edilmiştir. Tasarımda günümüz modern standartlarını baz alan TailwindCSS (v3) ve dinamik kullanıcı etkileşimleri için Alpine.js (gerekli yerlerde native vanilla JS) kullanılmıştır. Mobil uyumluluk sonradan eklenmiş bir yama değil, sistemin temel yapıtaşlarından biridir (%100 Responsive).&nbsp; %100 Özel Mimari, PHP 8.2+, modern TailwindCSS kullanılarak sıfırdan kodlanan, ultra hızlı, güvenlikli ve SEO uyumlu forum sistemi.</p><p><br></p><div contenteditable=\"false\"><hr></div><p>Diğer forum sistemlerinden ayıran bir çok özellik mevcuttur, Diğerlerinde Modül - Eklenti ile gelen gerekli ve önemli sistem parçacıkları bizde direkt olarak forumun bir parçası olarak geliyor. Sisteme herhangi bir yük bindirimemek için optimize edildi.</p><p><br></p><p><br></p><p><br></p><p>Diğer forum sistemlerinden import işlemi ile içeri aktarma veya taşınma yapılabilr (Şu anda sadece xenforo ve Mybb) destekliyoruz.&nbsp;</p><p><br></p><p>Forum sistemimiz ana giriş rotası Forum, Portal ve Makale olmak üzere 3 farklı seçim yapılabilir bunu farklı kullanıcı türleri için özel olarak yapılandırdık. kullanıp kullnmamak tamamen kullanıcının isteğine bağlı.</p><p><br></p><p>Kategori bazlı veya genel atanabilen \"Konu Ön Ekleri\". Kırmızı bir \"Satıldı\" veya yeşil bir \"Soru\" tagı oluşturup CSS atamalarını doğrudan Admin panelinden yapabilirsiniz. Seçilen prefixler hem konu listelerinde hem konu başlıklarında renkli badge (rozet) olarak sergilenir.</p><p><br></p><p>Klasik Beğeni (+1 Like) butonuna ek olarak kullanıcılara +Rep / -Rep verebilme (Gerekçeli, yorumlu şekilde). Belirli Rep ve Post sayısına ulaşan kullanıcılara otomatik olarak Admin\'in belirlediği rütbe ve Nişanların (Rozetlerin) atanması sistemi.</p><p><br></p><p>XSS açıklarına sıfır tolerans! Geliştirilmiş core_sanitize_html filtresi ile zararlı kod enjeksiyonları HTML formatlama sırasında engellenir. CSRF tokenleri her form için unique yaratılır. PDO Prepared Statements sayesinde SQL Injection imkansızdır. Sadece klasik \"konu aç cevapla\" değil. Konular \"Soru\" tipinde açılabilir. Soru tipli başlıklarda kullanıcılar yararlı buldukları mesajlara StackOverflow\'daki gibi Yukarı/Aşağı (Upvote/Downvote) verebilir ve konu sahibi (Veya yetkili) doğru cevabı \"Çözüm olarak işaretle\" diyebilir.</p><p><br></p>', NULL, '2026-02-28 03:47:00'),
(28, 1, 1, '<h2>MegaforBB - Yeni Nesil Forum Scripti</h2><p>MegaforBB, \"<b>Güvenli, Güçlü, maksimum performans</b>\" felsefesiyle tasarlanmıştır. Hiçbir ağır framework kullanılmamış (Zend, Laravel Symfony vs. yoktur). Sistemin kalbini oluşturan Forecor (Özel Çekirdek, symfony ve Laravel\'in belirli kütüphanelerini kullanmıştır) sayesinde milisaniyelik tepki süreleriyle çalışır. Yüz binlerce üyesi ve konusu olan büyük toplulukları, sunucu kaynaklarını sömürmeden kolaylıkla kaldırabilmesi için özel olarak optimize edilmiştir. Tasarımda günümüz modern standartlarını baz alan TailwindCSS (v3) ve dinamik kullanıcı etkileşimleri için Alpine.js (gerekli yerlerde native vanilla JS) kullanılmıştır. Mobil uyumluluk sonradan eklenmiş bir yama değil, sistemin temel yapıtaşlarından biridir (%100 Responsive).&nbsp; %100 Özel Mimari, PHP 8.2+, modern TailwindCSS kullanılarak sıfırdan kodlanan, ultra hızlı, güvenlikli ve SEO uyumlu forum sistemi.</p><p><br></p><div contenteditable=\"false\"><hr></div><p>Diğer forum sistemlerinden ayıran bir çok özellik mevcuttur, Diğerlerinde Modül - Eklenti ile gelen gerekli ve önemli sistem parçacıkları bizde direkt olarak forumun bir parçası olarak geliyor. Sisteme herhangi bir yük bindirimemek için optimize edildi.</p><p><br></p><p><br></p><p><br></p><p>Diğer forum sistemlerinden import işlemi ile içeri aktarma veya taşınma yapılabilr (Şu anda sadece xenforo ve Mybb) destekliyoruz.&nbsp;</p><p><br></p><p>Forum sistemimiz ana giriş rotası Forum, Portal ve Makale olmak üzere 3 farklı seçim yapılabilir bunu farklı kullanıcı türleri için özel olarak yapılandırdık. kullanıp kullnmamak tamamen kullanıcının isteğine bağlı.</p><p><br></p><p>Kategori bazlı veya genel atanabilen \"Konu Ön Ekleri\". Kırmızı bir \"Satıldı\" veya yeşil bir \"Soru\" tagı oluşturup CSS atamalarını doğrudan Admin panelinden yapabilirsiniz. Seçilen prefixler hem konu listelerinde hem konu başlıklarında renkli badge (rozet) olarak sergilenir.</p><p><br></p><p>Klasik Beğeni (+1 Like) butonuna ek olarak kullanıcılara +Rep / -Rep verebilme (Gerekçeli, yorumlu şekilde). Belirli Rep ve Post sayısına ulaşan kullanıcılara otomatik olarak Admin\'in belirlediği rütbe ve Nişanların (Rozetlerin) atanması sistemi.</p><p><br></p><p>XSS açıklarına sıfır tolerans! Geliştirilmiş core_sanitize_html filtresi ile zararlı kod enjeksiyonları HTML formatlama sırasında engellenir. CSRF tokenleri her form için unique yaratılır. PDO Prepared Statements sayesinde SQL Injection imkansızdır. Sadece klasik \"konu aç cevapla\" değil. Konular \"Soru\" tipinde açılabilir. Soru tipli başlıklarda kullanıcılar yararlı buldukları mesajlara StackOverflow\'daki gibi Yukarı/Aşağı (Upvote/Downvote) verebilir ve konu sahibi (Veya yetkili) doğru cevabı \"Çözüm olarak işaretle\" diyebilir.</p><p><br></p>', NULL, '2026-02-28 03:47:25'),
(29, 72, 1, '<p>Merhabalar, Yeni bir özellik olarak duyurmak istediğimiz Profil yorumları sistemini Kullanıma sunduk.</p><p>Bu özellik ile birlikte istediğiniz kullanıcının profil sayfasına kullanıcı hakkında yorum yazabilirsiniz</p><p><br></p><p>Not: Kullanıcı isteğe bağlı olarak bu özelliği kullanmak istiyorsa tabiki</p><p><br></p><p>Bu özelliği ilk denemek için benim profilime girenlere önceden not: Ben kapattım :D</p>', NULL, '2026-02-28 19:44:21'),
(30, 70, 1, '<p>Şu anda sistemde bu özellik var zaten 3 kademeli çalışıyor;</p><p>1- Sef:rakam</p><p>2-Sef:başlık</p><p>3-Random karakter</p><p><br></p><p>1 şu senin bahsettiğin, </p><p>2: Standart SEF url tarzı her yerde gördüğümüz alışık olduğumuz sistem konu başlığını temizleyip sefurl yapıyor.</p><p>3: Bu random sistem ise Google sıralama veya seo umrunda olmayan tamamen kendi amacına hitap eden forumlar için geçerli. </p><p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/a481eb839a3609e7.png\" alt=\"sef-url.png\" contenteditable=\"false\"><br></p>', NULL, '2026-02-28 20:11:42'),
(31, 79, 1, '<ul><li class=\"task-list-item\" data-task=\"true\"><p>Çevrimiçi üyeleri  - Botlar sayfası yapıldı. </p></li><li class=\"task-list-item\" data-task=\"true\"><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p></li></ul>', NULL, '2026-03-01 17:31:32');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
(1, 3, 1, '2026-02-23 06:50:51'),
(5, 46, 1, '2026-02-24 01:05:04'),
(6, 50, 129, '2026-02-24 10:52:11'),
(7, 57, 129, '2026-02-26 20:43:31'),
(8, 55, 131, '2026-02-26 21:43:08'),
(9, 59, 131, '2026-02-27 15:32:40'),
(10, 60, 131, '2026-02-27 15:35:57'),
(11, 63, 1, '2026-02-27 15:56:01'),
(12, 55, 1, '2026-02-27 15:56:09'),
(13, 64, 131, '2026-02-27 15:56:39'),
(14, 65, 1, '2026-02-28 02:37:12'),
(15, 72, 129, '2026-02-28 03:44:39'),
(16, 66, 129, '2026-03-01 23:25:16'),
(17, 84, 1, '2026-03-02 00:07:01'),
(18, 85, 129, '2026-03-02 00:11:33');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `post_reports`
--

CREATE TABLE `post_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `reporter_user_id` int(10) UNSIGNED NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `post_votes`
--

CREATE TABLE `post_votes` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `value` tinyint(4) NOT NULL COMMENT '1 up, -1 down',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `post_votes`
--

INSERT INTO `post_votes` (`id`, `post_id`, `user_id`, `value`, `created_at`) VALUES
(1, 50, 121, 1, '2026-02-23 17:14:36'),
(2, 51, 1, 1, '2026-02-23 17:15:03');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `prefixes`
--

CREATE TABLE `prefixes` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `css_class` varchar(50) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `prefixes`
--

INSERT INTO `prefixes` (`id`, `name`, `css_class`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Soru', 'badge bg-primary', 1, '2026-02-20 12:26:28', '2026-02-20 12:26:28'),
(2, 'Çözüm', 'badge bg-primary', 1, '2026-02-20 12:26:37', '2026-02-20 12:26:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `private_messages`
--

CREATE TABLE `private_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `body_html` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `private_messages`
--

INSERT INTO `private_messages` (`id`, `conversation_id`, `user_id`, `body`, `body_html`, `created_at`) VALUES
(1, 16, 129, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column &#039;updated_at&#039; in &#039;INSERT INTO&#039; (Connection: default, SQL: insert into `private_messages` (`conversation_id`, `user_id`, `body`, `body_html`, `updated_at`, `created_at`) values (15, 129, gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor. Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz., gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor. Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz., 2026-02-27 17:48:59, 2026-02-27 17:48:59)) in /home/m', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column &#039;updated_at&#039; in &#039;INSERT INTO&#039; (Connection: default, SQL: insert into `private_messages` (`conversation_id`, `user_id`, `body`, `body_html`, `updated_at`, `created_at`) values (15, 129, gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor. Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz., gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor. Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz., 2026-02-27 17:48:59, 2026-02-27 17:48:59)) in /home/m', '2026-02-27 17:51:10'),
(2, 16, 129, 'MegaforBB İlk sürümü BETA\r\nMegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', 'MegaforBB İlk sürümü BETA\r\nMegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', '2026-02-27 17:51:52'),
(3, 16, 129, 'MegaforBB İlk sürümü BETA\r\nMegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', 'MegaforBB İlk sürümü BETA\r\nMegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', '2026-02-27 17:54:19'),
(4, 16, 129, 'MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', 'MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', '2026-02-27 17:58:07'),
(5, 17, 129, 'MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', 'MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', '2026-02-27 17:59:20'),
(6, 16, 129, 'ede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', 'ede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor.\r\n\r\nTüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', '2026-02-27 18:04:14'),
(7, 16, 1, 'MegaforBB İlk sürümü BETA MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor. Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', 'MegaforBB İlk sürümü BETA MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor. Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', '2026-02-27 18:07:33');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `profile_comments`
--

CREATE TABLE `profile_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Profil sahibi',
  `author_id` int(10) UNSIGNED NOT NULL COMMENT 'Yorumu yazan',
  `body` text NOT NULL,
  `body_html` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `profile_comments`
--

INSERT INTO `profile_comments` (`id`, `user_id`, `author_id`, `body`, `body_html`, `created_at`) VALUES
(1, 129, 1, 'İlk profil yorumunu test ediyoruz, Hayırlı olsun :)', 'İlk profil yorumunu test ediyoruz, Hayırlı olsun :)', '2026-02-28 18:22:14');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reward_levels`
--

CREATE TABLE `reward_levels` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `min_posts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `min_reputation` int(11) NOT NULL DEFAULT 0 COMMENT 'net rep (pozitif - negatif)',
  `min_likes` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'aldığı toplam beğeni',
  `badge_label` varchar(64) NOT NULL DEFAULT '',
  `badge_icon` varchar(128) DEFAULT NULL COMMENT 'Font Awesome sınıfı veya emoji',
  `badge_css` varchar(128) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `reward_levels`
--

INSERT INTO `reward_levels` (`id`, `name`, `min_posts`, `min_reputation`, `min_likes`, `badge_label`, `badge_icon`, `badge_css`, `sort_order`) VALUES
(1, 'Yeni Üye', 0, 0, 0, 'Yeni Üye', 'fa-solid fa-seedling', 'text-gray-500', 0),
(2, 'Aktif Üye', 10, 0, 0, 'Aktif', 'fa-solid fa-comment', 'text-blue-600', 10),
(3, 'Deneyimli', 50, 5, 10, 'Deneyimli', 'fa-solid fa-star', 'text-amber-500', 20),
(4, 'Uzman', 200, 20, 50, 'Uzman', 'fa-solid fa-award', 'text-purple-600', 30),
(5, 'Efsane', 1000, 100, 500, 'Efsane', 'fa-solid fa-crown', 'text-amber-400', 40);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `roles`
--

CREATE TABLE `roles` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_staff` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `pm_daily_limit` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `daily_topic_limit` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bump_per_day` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `color`, `is_staff`, `sort_order`, `pm_daily_limit`, `daily_topic_limit`, `bump_per_day`) VALUES
(1, 'Yönetici', 'y-netici', '#f9d9d9', 1, 0, 0, 0, 0),
(2, 'Moderatör', 'moderat-r', '#9dbaf7', 1, 1, 0, 0, 0),
(3, 'Üye', '-ye', '#97d9f9', 0, 2, 10, 10, 1),
(4, 'Misafir', 'misafir', '#c0c5c9', 0, 3, 0, 0, 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(128) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(64) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `group`) VALUES
(1, 'site_name', 'MegaforBB', 'general'),
(2, 'site_description', 'Topluluk güncellemeleri, teknik konular ve duyurular.', 'general'),
(3, 'topics_per_page', '20', 'forum'),
(4, 'posts_per_page', '15', 'forum'),
(5, 'default_locale', 'en', 'general'),
(6, 'registration_enabled', '1', 'auth'),
(7, 'maintenance_mode', '0', 'general'),
(8, 'reputation_enabled', '1', 'forum'),
(9, 'antibump_enabled', '1', 'forum'),
(10, 'antibump_seconds', '60', 'forum'),
(11, 'max_post_length', '0', 'topic_post'),
(12, 'max_profile_message_length', '0', 'topic_post'),
(13, 'min_time_between_posts', '0', 'topic_post'),
(14, 'min_time_between_topics', '0', 'topic_post'),
(15, 'unfurl_url_enabled', '1', 'topic_post'),
(16, 'markdown_to_bbcode_enabled', '0', 'topic_post'),
(17, 'lightbox_all_images_enabled', '1', 'topic_post'),
(18, 'show_online_status', '1', 'topic_post'),
(19, 'show_signatures_to_guests', '1', 'topic_post'),
(20, 'multi_quote_enabled', '1', 'topic_post'),
(21, 'select_to_quote_enabled', '1', 'topic_post'),
(22, 'max_last_page_links', '5', 'topic_post'),
(23, 'enable_previews', '1', 'topic_post'),
(24, 'rss_content_length', '500', 'topic_post'),
(25, 'max_poll_options', '10', 'topic_post'),
(26, 'unread_retention_days', '30', 'topic_post'),
(27, 'followed_email_include_content', '1', 'topic_post'),
(28, 'dm_email_include_content', '1', 'topic_post'),
(29, 'max_topic_title_length', '200', 'topic_post'),
(32, 'messages_enabled', '1', 'communication'),
(33, 'notifications_enabled', '1', 'communication'),
(34, 'notification_toast_enabled', '1', 'communication'),
(35, 'forum_logo_url', 'https://www.megaforbb.com.tr/megabb.png', 'system'),
(36, 'top_menu_items', '[{\"label\":\"Ana Sayfa\",\"url\":\"\",\"visible\":\"1\",\"order\":0,\"depth\":0},{\"label\":\"Forum\",\"url\":\"forum\",\"visible\":\"1\",\"order\":1,\"depth\":0},{\"label\":\"Döküman\",\"url\":\"documentation\",\"visible\":\"1\",\"order\":2,\"depth\":0},{\"label\":\"Makaleler\",\"url\":\"articles\",\"visible\":\"1\",\"order\":3,\"depth\":0},{\"label\":\"Kurallar\",\"url\":\"page\\/kurallar\",\"visible\":\"1\",\"order\":4,\"depth\":0},{\"label\":\"Üyeler\",\"url\":\"members\",\"visible\":\"1\",\"order\":5,\"depth\":0},{\"label\":\"İletişim\",\"url\":\"iletisim\",\"visible\":\"1\",\"order\":6,\"depth\":0},{\"label\":\"Zaman Tüneli\",\"url\":\"timeline\",\"visible\":\"0\",\"order\":7,\"depth\":0}]', 'system'),
(37, 'show_site_title_next_to_logo', '0', 'system'),
(38, 'social_facebook', 'https://www.facebook.com/', 'system'),
(39, 'social_twitter', 'https://www.facebook.com/', 'system'),
(40, 'social_instagram', 'https://www.facebook.com/', 'system'),
(41, 'social_youtube', 'https://www.facebook.com/', 'system'),
(42, 'social_linkedin', 'https://www.facebook.com/', 'system'),
(43, 'social_show_header', '0', 'system'),
(44, 'social_show_footer', '1', 'system'),
(45, 'social_show_sidebar', '1', 'system'),
(46, 'minify_html', '1', 'performance'),
(47, 'minify_css', '1', 'performance'),
(48, 'minify_js', '1', 'performance'),
(49, 'cdn_url', '', 'performance'),
(50, 'lazy_load_images', '1', 'performance'),
(51, 'image_optimize', '1', 'performance'),
(52, 'security_cooldown_reply', '60', 'security'),
(53, 'security_cooldown_new_topic', '30', 'security'),
(54, 'security_cooldown_edit_post', '10', 'security'),
(55, 'security_cooldown_edit_topic', '10', 'security'),
(56, 'security_cooldown_login', '30', 'security'),
(57, 'security_cooldown_register', '60', 'security'),
(58, 'security_cooldown_send_pm', '30', 'security'),
(59, 'security_cooldown_report', '60', 'security'),
(60, 'security_cooldown_like', '5', 'security'),
(61, 'security_violations_before_block', '5', 'security'),
(62, 'security_violation_window_minutes', '5', 'security'),
(63, 'security_block_duration_minutes', '15', 'security'),
(64, 'security_enabled', '1', 'security'),
(65, 'security_block_message', 'Çok fazla hızlı işlem denemesi veya kural ihlali. Geçici olarak engellendiniz. Lütfen belirtilen süre sonra tekrar deneyin.', 'security'),
(66, 'captcha_provider', 'turnstile', 'security'),
(67, 'recaptcha_site_key', '', 'security'),
(68, 'recaptcha_secret_key', '', 'security'),
(69, 'recaptcha_version', 'v2', 'security'),
(70, 'recaptcha_score_threshold', '0.5', 'security'),
(71, 'turnstile_site_key', '0x4AAAAAACg5juczZdnHF8Q_', 'security'),
(72, 'turnstile_secret_key', '0x4AAAAAACg5juZZXfF0wxRpLpsXZ4TvWXI', 'security'),
(73, 'captcha_on_login', '1', 'security'),
(74, 'captcha_on_register', '1', 'security'),
(75, 'registration_require_email_verification', '1', 'auth'),
(76, 'security_global_rate_per_minute', '120', 'security'),
(77, 'security_global_rate_block_minutes', '5', 'security'),
(78, 'security_suspicious_blocks_threshold', '3', 'security'),
(79, 'security_suspicious_block_minutes', '1440', 'security'),
(80, 'security_global_rate_enabled', '0', 'security'),
(81, 'security_headers_enabled', '1', 'security'),
(82, 'security_hsts_enabled', '0', 'security'),
(83, 'registration_requires_approval', '0', 'auth'),
(84, 'security_attack_mode', '0', 'security'),
(85, 'security_log_retention_days', '1', 'security'),
(86, 'security_tracking_enabled', '1', 'security'),
(87, 'analytics_visitor_log_enabled', '1', 'security'),
(88, 'analytics_log_retention_minutes', '10', 'security'),
(89, 'error_404_action', 'page', 'system'),
(90, 'error_404_redirect_url', '', 'system'),
(91, 'portal_enabled', '1', 'portal'),
(92, 'portal_forum_ids', '[1,3,4,6]', 'portal'),
(93, 'portal_latest_topics_count', '10', 'portal'),
(94, 'portal_latest_articles_count', '5', 'portal'),
(95, 'article_comments_enabled', '1', 'portal'),
(96, 'article_forum_id', '4', 'portal'),
(103, 'home_page_type', 'portal', 'portal'),
(104, 'home_page_custom_url', '', 'portal'),
(105, 'articles_view_mode', 'grid', 'portal'),
(106, 'portal_latest_comments_count', '8', 'portal'),
(107, 'portal_tab_limit', '15', 'portal'),
(108, 'portal_tab_max', '50', 'portal'),
(109, 'portal_card_1', '{\"type\":\"latest\",\"title\":\"Son içerikler\",\"description\":\"Son paylaşılan içeirkelri burada gösteriyoruz Tüm detayları ile birlikte.\",\"layout\":\"list\",\"per_slide\":4,\"total\":4,\"category_id\":2,\"color\":\"#1c4910\",\"border_color\":\"#aaf4a6\",\"enabled\":true}', 'portal'),
(110, 'portal_card_2', '{\"type\":\"category\",\"title\":\"Kategori\",\"description\":\"Kategori bazlı yapısal içerikleri konuları gösteriyoruz.\",\"layout\":\"list\",\"per_slide\":4,\"total\":4,\"category_id\":2,\"color\":\"#359315\",\"border_color\":\"#8f8f8f\",\"enabled\":true}', 'portal'),
(111, 'portal_card_3', '{\"type\":\"popular\",\"title\":\"En Popüler\",\"description\":\"Bu kategoride en popüler makaleler forum yazıları grünüyor.\",\"layout\":\"list\",\"per_slide\":4,\"total\":4,\"category_id\":0,\"color\":\"#821515\",\"border_color\":\"#000000\",\"enabled\":true}', 'portal'),
(112, 'portal_tab_visibility', '{\"newest_topics\":true,\"most_replied\":true,\"most_viewed\":true,\"popular_users\":true,\"top_replied\":true,\"top_viewed\":true}', 'portal'),
(113, 'members_list_enabled', '1', 'portal'),
(114, 'edit_timeout_minutes', '0', 'topic_post'),
(115, 'hero_title', 'MegaforBB Nedir ?', 'forum'),
(116, 'hero_description', 'Özel yapı, Güçlü, Güvenli, Hızlı Geleneksel Forum yazılımı.', 'forum'),
(117, 'hero_show_social', '0', 'forum'),
(118, 'hero_visible', '0', 'forum'),
(119, 'forum_favicon_url', 'https://www.megaforbb.com.tr/megafav.png', 'system'),
(120, 'son_olaylar_settings', '{\"tab_order\":[\"newest_topics\",\"most_replied\",\"most_viewed\",\"top_replied\",\"top_viewed\",\"popular_users\"],\"column_order\":[\"title\",\"last_reply\",\"replies_views\",\"category\"],\"enabled\":true,\"show_replies_views\":\"1\",\"show_last_reply\":\"1\",\"show_category\":\"1\",\"show_topic_icon\":\"1\",\"tab_label_newest_topics\":\"Yeni Konular\",\"tab_label_most_replied\":\"Son Cevaplanan\",\"tab_label_most_viewed\":\"Son Gezilen\",\"tab_label_top_viewed\":\"En Çok Okunan\",\"tab_label_top_replied\":\"En Çok Yorumlanan\",\"tab_label_popular_users\":\"Popüler Kullanıcılar\",\"comment_snippet_limit\":80,\"topic_title_limit\":75}', 'portal'),
(121, 'enable_timeline', '1', 'system'),
(122, 'mail_driver', 'smtp', 'mail'),
(123, 'smtp_host', 'fusion.mxrouting.net', 'mail'),
(124, 'smtp_port', '465', 'mail'),
(125, 'smtp_username', 'hello@megaforbb.com.tr', 'mail'),
(126, 'smtp_password', 'q1w2e3r4t5A!!', 'mail'),
(127, 'smtp_encryption', 'ssl', 'mail'),
(128, 'mail_from_address', 'hello@megaforbb.com.tr', 'mail'),
(129, 'mail_from_name', 'MegaforBB Community', 'mail'),
(130, 'cache_driver', 'redis', 'performance'),
(131, 'redis_host', '127.0.0.1', 'performance'),
(132, 'redis_port', '6379', 'performance'),
(133, 'redis_password', '', 'performance'),
(134, 'redis_username', '', 'performance'),
(135, 'timeline_title', 'Zaman Tüneli', 'system'),
(136, 'timeline_description', 'Sitede yapılan son işlemler.', 'system'),
(137, 'disabled_plugins', '[\"MegaforIstatistik\"]', 'forum'),
(138, 'hide_content_from_guests', '1', 'topic_post'),
(139, 'active_frontend_theme', 'default', 'forum'),
(140, 'storage_driver', 'local', 'storage'),
(141, 'storage_aws_s3_key', '', 'storage'),
(142, 'storage_aws_s3_region', 'us-east-1', 'storage'),
(143, 'storage_aws_s3_bucket', '', 'storage'),
(144, 'storage_aws_s3_prefix', '', 'storage'),
(145, 'storage_aws_s3_cdn_url', '', 'storage'),
(146, 'storage_r2_key', '', 'storage'),
(147, 'storage_r2_endpoint', '', 'storage'),
(148, 'storage_r2_bucket', '', 'storage'),
(149, 'storage_r2_prefix', '', 'storage'),
(150, 'storage_r2_cdn_url', '', 'storage'),
(153, 'storage_aws_s3_secret', '', 'storage'),
(159, 'storage_r2_secret', '', 'storage'),
(164, 'registration_requires_invite', '0', 'auth'),
(165, 'cron_token', '36pdd5x1md96hmbbrrcvzs', 'system'),
(166, 'gzip_enabled', '1', 'performance'),
(167, 'consolidate_assets', '1', 'performance'),
(168, 'minify_consolidated', '1', 'performance'),
(169, 'jquery_cdn_url', '', 'performance'),
(170, 'jquery_enabled', '1', 'performance'),
(171, 'ajax_enabled', '1', 'performance'),
(172, 'censorship_enabled', '1', 'censorship'),
(173, 'censorship_word_action', 'block', 'censorship'),
(174, 'censorship_apply_posts', '1', 'censorship'),
(175, 'censorship_apply_topic_titles', '1', 'censorship'),
(176, 'censorship_apply_signatures', '1', 'censorship'),
(177, 'temp_mail_block_enabled', '1', 'censorship'),
(178, 'blocked_usernames_enabled', '1', 'censorship'),
(179, 'smiley_enabled', '1', 'smiley'),
(180, 'smiley_use_gif', '1', 'smiley'),
(181, 'smiley_gif_max_size_kb', '500', 'smiley'),
(182, 'seo_site_name', 'MegaforBB', 'seo'),
(183, 'seo_description', 'MegaforBB; Geleneksel forum sistemlerinin Modern hali, Güçlü, Güvenli ve Hızlı Forum yazılımı BETA olarak piyasaya sürülmüştür.', 'seo'),
(184, 'seo_keywords', 'En iyi Forum yazılımı, En iyi forum sistemi, php forum scripti', 'seo'),
(185, 'og_title', 'MegaforBB', 'seo'),
(186, 'og_description', 'MegaforBB; Geleneksel forum sistemlerinin Modern hali, Güçlü, Güvenli ve Hızlı Forum yazılımı BETA olarak piyasaya sürülmüştür.', 'seo'),
(187, 'og_image', '', 'seo'),
(188, 'og_type', 'website', 'seo'),
(189, 'schema_json', '{\"@context\":\"https://schema.org\",\"@type\":\"WebSite\",\"name\":\"MegaforBB\",\"description\":\"MegaforBB; Geleneksel forum sistemlerinin Modern hali, Güçlü, Güvenli ve Hızlı Forum yazılımı BETA olarak piyasaya sürülmüştür.\",\"url\":\"https://www.megaforbb.com.tr\",\"potentialAction\":{\"@type\":\"SearchAction\",\"target\":{\"@type\":\"EntryPoint\",\"urlTemplate\":\"https://www.megaforbb.com.tr/search?q={search_term_string}\"},\"query-input\":\"required name=search_term_string\"},\"publisher\":{\"@type\":\"Organization\",\"name\":\"Megabre\",\"logo\":{\"@type\":\"ImageObject\",\"url\":\"https://www.megaforbb.com.tr/megabb.png\"}}}', 'seo'),
(190, 'sef_url_mode', 'slug', 'seo'),
(191, 'sef_topic_url_mode', 'slug', 'seo'),
(192, 'profile_comments_enabled', '1', 'system'),
(193, 'footer_menu_items', '[{\"label\":\"Kurallar\",\"url\":\"page\\/kurallar\",\"visible\":\"1\",\"order\":0},{\"label\":\"Gizlilik\",\"url\":\"page\\/gizlilik\",\"visible\":\"1\",\"order\":1},{\"label\":\"Site Haritası\",\"url\":\"sitemap.xml\",\"visible\":\"1\",\"order\":2},{\"label\":\"Yukarı\",\"url\":\"#\",\"visible\":\"1\",\"order\":3}]', 'system'),
(194, 'footer_quick_links', '[{\"label\":\"Ana sayfa\",\"url\":\"\",\"icon\":\"fa-solid fa-house\",\"visible\":\"1\",\"order\":0},{\"label\":\"Forum\",\"url\":\"forum\",\"icon\":\"fa-solid fa-comments\",\"visible\":\"1\",\"order\":1},{\"label\":\"Üyeler\",\"url\":\"members\",\"icon\":\"fa-solid fa-users\",\"visible\":\"1\",\"order\":2},{\"label\":\"Profil Ayarları\",\"url\":\"profile\\/edit\",\"icon\":\"fa-solid fa-user-gear\",\"visible\":\"1\",\"order\":3},{\"label\":\"İletişim\",\"url\":\"iletisim\",\"icon\":\"fa-solid fa-envelope\",\"visible\":\"1\",\"order\":4}]', 'system'),
(195, 'documentation_enabled', '1', 'system'),
(196, 'documentation_title', 'MegaforBB Docs', 'system');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sidebar_widgets`
--

CREATE TABLE `sidebar_widgets` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'html',
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` longtext DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `sidebar_widgets`
--

INSERT INTO `sidebar_widgets` (`id`, `type`, `title`, `content`, `sort_order`, `enabled`) VALUES
(1, 'online_users', 'Çevrimiçi Üyeler', NULL, 30, 1),
(2, 'forum_stats', 'Forum İstatistikleri', NULL, 40, 1),
(3, 'recent_topics', 'Son Konular', NULL, 10, 1),
(4, 'popular_topics', 'Popüler Konularımız', NULL, 0, 1),
(5, 'tag_cloud', '', '10', 20, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `smileys`
--

CREATE TABLE `smileys` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL,
  `unicode_char` varchar(16) DEFAULT NULL COMMENT 'Unicode emoji karakteri',
  `image_path` varchar(255) DEFAULT NULL COMMENT 'GIF dosya yolu (public/smileys/...)',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `smileys`
--

INSERT INTO `smileys` (`id`, `code`, `unicode_char`, `image_path`, `sort_order`, `created_at`) VALUES
(1, 'loft:', NULL, 'smileys/lost_1772113766.gif', 0, '2026-02-26 16:49:26'),
(2, 'oy:', NULL, 'smileys/oy_1772129337.gif', 0, '2026-02-26 21:08:57');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `use_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `description`, `use_count`, `created_at`, `updated_at`) VALUES
(7, 'Megaforbb', 'megaforbb', NULL, 5, '2026-02-23 04:10:26', '2026-03-01 16:46:33'),
(8, 'Megaforbb release', 'megaforbb-release', NULL, 1, '2026-02-23 04:10:37', '2026-02-23 04:11:06'),
(11, 'Sef Url', 'sef-url', NULL, 1, '2026-02-28 01:58:18', '2026-02-28 01:58:35'),
(12, 'Megaforbb Sef Url', 'megaforbb-sef-url', NULL, 1, '2026-02-28 01:58:31', '2026-02-28 01:58:35'),
(13, 'Profil', 'profil', NULL, 0, '2026-02-28 03:24:00', '2026-02-28 03:25:43'),
(14, 'Feature', 'feature', NULL, 0, '2026-02-28 03:24:11', '2026-02-28 03:25:43'),
(15, 'Sistem izleyici', 'sistem-izleyici', NULL, 0, '2026-02-28 03:34:10', '2026-02-28 03:34:10'),
(16, 'Log takibi', 'log-takibi', NULL, 0, '2026-02-28 03:34:16', '2026-02-28 03:34:16'),
(17, 'Döküman', 'dokuman', NULL, 1, '2026-03-01 16:46:24', '2026-03-01 16:46:33');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `topics`
--

CREATE TABLE `topics` (
  `id` int(10) UNSIGNED NOT NULL,
  `moved_to_topic_id` int(10) UNSIGNED DEFAULT NULL,
  `forum_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `prefix_id` smallint(5) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `url_key` varchar(24) DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'topic',
  `is_sticky` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `is_solved` tinyint(1) NOT NULL DEFAULT 0,
  `accepted_post_id` int(10) UNSIGNED DEFAULT NULL,
  `reply_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `first_post_id` int(10) UNSIGNED DEFAULT NULL,
  `last_post_id` int(10) UNSIGNED DEFAULT NULL,
  `last_post_at` datetime DEFAULT NULL,
  `last_post_user_id` int(10) UNSIGNED DEFAULT NULL,
  `scheduled_publish_at` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'published',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `topics`
--

INSERT INTO `topics` (`id`, `moved_to_topic_id`, `forum_id`, `user_id`, `prefix_id`, `title`, `slug`, `url_key`, `type`, `is_sticky`, `is_locked`, `is_private`, `is_solved`, `accepted_post_id`, `reply_count`, `view_count`, `first_post_id`, `last_post_id`, `last_post_at`, `last_post_user_id`, `scheduled_publish_at`, `status`, `created_at`, `updated_at`, `deleted_at`, `deleted_by`) VALUES
(1, NULL, 1, 1, 3, 'MegaforBB v0.1.1 Yayınlandı', 'megaforbb-v011-yayinlandi-1', NULL, 'topic', 1, 0, 0, 0, NULL, 1, 88, 1, 3, '2026-02-23 06:31:13', 129, NULL, 'published', '2026-02-23 04:11:06', '2026-03-01 07:27:36', NULL, NULL),
(2, NULL, 4, 1, NULL, 'Megaforbb Kurulum', 'megaforbb-kurulum-2', NULL, 'topic', 0, 1, 0, 0, NULL, 0, 37, 2, 2, '2026-02-23 04:15:08', 1, NULL, 'published', '2026-02-23 04:15:08', '2026-03-01 22:37:29', NULL, NULL),
(5, NULL, 1, 1, NULL, 'Haftalık Güncelleme  - İlerleme konusu', 'haftalik-guncelleme-ilerleme-konusu-5', NULL, 'topic', 0, 0, 0, 0, NULL, 3, 95, 10, 82, '2026-03-01 23:28:00', 1, NULL, 'published', '2026-02-21 07:04:46', '2026-03-01 23:28:00', NULL, NULL),
(14, NULL, 4, 1, NULL, 'SEO Uyumlu Forum Altyapısı', 'seo-uyumlu-forum-altyapisi-14', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 48, 35, 35, '2026-02-09 07:04:46', 1, NULL, 'published', '2026-02-09 07:04:46', '2026-03-01 17:32:45', NULL, NULL),
(15, NULL, 4, 1, NULL, 'Arayüz Tasarımı Geri Bildirimleri', 'arayuz-tasarimi-geri-bildirimleri-15', NULL, 'topic', 0, 0, 0, 0, NULL, 1, 110, 36, 58, '2026-02-26 20:45:19', 1, NULL, 'published', '2026-02-10 07:04:46', '2026-03-01 22:37:31', NULL, NULL),
(19, NULL, 6, 1, NULL, 'Kullanıcı ve Post etiket test', 'kullanici-ve-post-etiket-test-19', NULL, 'topic', 0, 1, 0, 0, NULL, 2, 47, 45, 48, '2026-02-24 04:31:31', 1, NULL, 'published', '2026-02-24 00:54:25', '2026-03-01 22:39:56', NULL, NULL),
(20, NULL, 3, 129, NULL, 'Kullanıcı kayıt - Captcha sorunu', 'kullanici-kayit-captcha-sorunu-20', NULL, 'topic', 0, 1, 0, 0, NULL, 1, 24, 47, 62, '2026-02-26 21:20:12', 1, NULL, 'published', '2026-02-24 03:54:41', '2026-03-01 07:29:31', NULL, NULL),
(21, NULL, 3, 129, NULL, 'Konu başlığı Ön ek çalışmıyor - Görünmüyor', 'konu-basligi-on-ek-calismiyor-gorunmuyor-21', NULL, 'question', 0, 1, 0, 1, 50, 1, 30, 49, 50, '2026-02-24 04:44:44', 1, NULL, 'published', '2026-02-24 04:41:16', '2026-03-01 07:43:47', NULL, NULL),
(22, NULL, 4, 1, NULL, 'Kurulum ve güncelleme sistemi', 'kurulum-ve-guncelleme-sistemi-22', NULL, 'topic', 0, 0, 0, 0, NULL, 2, 23, 51, 71, '2026-02-28 02:37:48', 1, NULL, 'published', '2026-02-24 05:11:28', '2026-03-01 07:28:06', NULL, NULL),
(23, NULL, 4, 1, NULL, 'Konu düzenleme geçmişi ?', 'konu-duzenleme-gecmisi-23', NULL, 'question', 0, 1, 1, 0, NULL, 1, 19, 52, 53, '2026-02-24 11:03:52', 1, NULL, 'published', '2026-02-24 10:50:52', '2026-03-01 22:44:41', NULL, NULL),
(24, NULL, 4, 1, NULL, 'Tema motoru için Blade vs Twig', 'tema-motoru-icin-blade-vs-twig-24', NULL, 'topic', 0, 1, 0, 0, NULL, 1, 26, 54, 61, '2026-02-26 21:06:49', 1, NULL, 'published', '2026-02-24 15:08:04', '2026-03-01 07:29:20', NULL, NULL),
(25, NULL, 3, 129, NULL, 'Bildirim sisteminde hata', 'bildirim-sisteminde-hata-25', NULL, 'topic', 0, 0, 0, 0, NULL, 2, 24, 55, 64, '2026-02-27 15:55:56', 1, NULL, 'published', '2026-02-24 21:05:34', '2026-03-01 07:29:20', NULL, NULL),
(26, NULL, 1, 1, NULL, 'Sansür Koruma Sistemi', 'sansur-koruma-sistemi-26', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 7, 56, 56, '2026-02-26 20:39:11', 1, NULL, 'published', '2026-02-26 20:39:11', '2026-03-01 04:06:13', NULL, NULL),
(27, NULL, 1, 1, NULL, 'Dil Sistemi ve Twig Entegrasyonu', 'dil-sistemi-ve-twig-entegrasyonu-27', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 10, 59, 59, '2026-02-26 20:48:33', 1, NULL, 'published', '2026-02-26 20:48:33', '2026-03-01 07:42:55', NULL, NULL),
(28, NULL, 1, 1, NULL, 'Kullanıcı Hesabı Askıya Alma ve Kalıcı Kapatma', 'kullanici-hesabi-askiya-alma-ve-kalici-kapatma-28', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 17, 60, 60, '2026-02-26 21:03:33', 1, NULL, 'published', '2026-02-26 21:03:33', '2026-03-01 07:50:37', NULL, NULL),
(29, NULL, 6, 1, NULL, 'Konu dosya test ve Etiket Test', 'konu-dosya-test-ve-etiket-test-29', NULL, 'topic', 0, 0, 0, 0, NULL, 1, 24, 66, 81, '2026-03-01 23:25:10', 129, NULL, 'published', '2026-02-27 16:14:53', '2026-03-01 23:41:49', NULL, NULL),
(30, NULL, 3, 1, NULL, 'Mesaj gönderim hatası', 'mesaj-gonderim-hatasi-30', NULL, 'topic', 0, 0, 0, 0, NULL, 1, 19, 67, 68, '2026-02-27 18:08:37', 1, NULL, 'published', '2026-02-27 16:31:36', '2026-03-01 07:27:36', NULL, NULL),
(31, NULL, 3, 129, NULL, 'SEF Url desteği eklenmeli', 'sef-url-destegi-eklenmeli-31', NULL, 'topic', 0, 0, 0, 0, NULL, 1, 20, 69, 70, '2026-02-28 02:07:24', 1, NULL, 'published', '2026-02-28 01:58:35', '2026-03-01 16:48:11', NULL, NULL),
(32, NULL, 1, 1, 5, 'Kullanıcı Profil Yorumları', 'profil-yorumlari-32', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 28, 72, 72, '2026-02-28 03:24:13', 1, NULL, 'published', '2026-02-28 03:24:13', '2026-03-01 22:03:10', NULL, NULL),
(34, NULL, 6, 1, NULL, 'Planlanmış konu TEST', 'planlanmis-konu-test-34', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 14, 74, 74, '2026-02-28 23:09:00', 1, NULL, 'published', '2026-02-28 23:09:00', '2026-03-01 23:57:26', NULL, NULL),
(36, NULL, 8, 1, NULL, 'TEST İÇİN KISA MAKALE ÖRNEKLERİ', 'test-icin-kisa-makale-ornekleri-36', NULL, 'article', 0, 0, 0, 0, NULL, 0, 9, 76, 76, '2026-03-01 04:09:11', 1, NULL, 'published', '2026-03-01 04:09:11', '2026-03-01 16:54:29', NULL, NULL),
(37, NULL, 8, 1, NULL, 'Mevlana’ya Göre İnsanın Mahiyeti ve Kâmil İnsan Olma', 'mevlanaya-gore-insanin-mahiyeti-ve-kamil-insan-olma-37', NULL, 'article', 0, 0, 0, 0, NULL, 0, 12, 77, 77, '2026-03-01 04:09:55', 1, NULL, 'published', '2026-03-01 04:09:55', '2026-03-01 17:45:37', NULL, NULL),
(38, NULL, 1, 1, 5, 'Döküman Sistemi geliştirildi.', 'dokuman-sistemi-gelistirildi-38', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 7, 78, 78, '2026-03-01 16:46:33', 1, NULL, 'published', '2026-03-01 16:46:33', '2026-03-01 22:29:48', NULL, NULL),
(39, NULL, 6, 1, NULL, 'Özel -private konu test', 'ozel-private-konu-test-39', NULL, 'topic', 0, 0, 1, 0, NULL, 0, 9, 80, 80, '2026-03-01 22:56:42', 1, NULL, 'published', '2026-03-01 22:56:42', '2026-03-01 23:27:40', NULL, NULL),
(40, NULL, 6, 129, NULL, 'Anti Bump Mesaj - yorum artırma sistemi', 'anti-bump-mesaj-yorum-artirma-sistemi-40', NULL, 'topic', 0, 0, 0, 0, NULL, 2, 13, 83, 85, '2026-03-02 00:00:05', 1, NULL, 'published', '2026-03-01 23:43:52', '2026-03-02 00:11:28', NULL, NULL),
(41, NULL, 6, 1, NULL, 'Soru - Cevap -Test konusu', 'soru-cevap-test-konusu-41', NULL, 'question', 0, 0, 0, 1, 87, 2, 36, 86, 88, '2026-03-02 00:17:59', 1, NULL, 'published', '2026-03-02 00:14:11', '2026-03-02 00:38:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `topic_bumps`
--

CREATE TABLE `topic_bumps` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `bumped_at` date NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `topic_bumps`
--

INSERT INTO `topic_bumps` (`id`, `user_id`, `topic_id`, `bumped_at`, `created_at`) VALUES
(1, 131, 28, '2026-02-26', '2026-02-26 21:41:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `topic_prefixes`
--

CREATE TABLE `topic_prefixes` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `css_class` varchar(64) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `category_id` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `topic_prefixes`
--

INSERT INTO `topic_prefixes` (`id`, `name`, `slug`, `css_class`, `sort_order`, `category_id`) VALUES
(3, 'Update', 'update-1771904224', 'bg-amber-50 text-amber-700', 1, 1),
(5, 'Feature', 'feature-1772238118', 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20', 0, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `topic_private_viewers`
--

CREATE TABLE `topic_private_viewers` (
  `topic_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `topic_private_viewers`
--

INSERT INTO `topic_private_viewers` (`topic_id`, `user_id`, `created_at`) VALUES
(39, 129, '2026-03-01 23:13:46');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `topic_reads`
--

CREATE TABLE `topic_reads` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `last_read_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `topic_reads`
--

INSERT INTO `topic_reads` (`user_id`, `topic_id`, `last_read_at`) VALUES
(1, 1, '2026-02-28 14:20:09'),
(1, 2, '2026-02-27 16:11:05'),
(1, 3, '2026-02-23 18:10:34'),
(1, 4, '2026-02-23 18:12:17'),
(1, 5, '2026-03-01 23:28:00'),
(1, 6, '2026-02-24 00:32:45'),
(1, 7, '2026-02-23 18:14:23'),
(1, 8, '2026-02-24 00:32:53'),
(1, 9, '2026-02-24 00:33:21'),
(1, 10, '2026-02-24 00:33:05'),
(1, 11, '2026-02-24 00:33:15'),
(1, 12, '2026-02-23 18:17:01'),
(1, 13, '2026-02-23 18:16:50'),
(1, 14, '2026-03-01 17:32:45'),
(1, 15, '2026-02-28 03:02:33'),
(1, 16, '2026-02-23 18:08:02'),
(1, 17, '2026-02-23 18:04:42'),
(1, 18, '2026-02-23 18:10:46'),
(1, 19, '2026-03-01 00:38:23'),
(1, 20, '2026-02-26 21:20:16'),
(1, 21, '2026-02-28 02:20:32'),
(1, 22, '2026-02-28 20:19:59'),
(1, 23, '2026-03-01 22:44:41'),
(1, 24, '2026-02-26 21:13:00'),
(1, 25, '2026-02-28 02:24:03'),
(1, 26, '2026-02-26 20:39:11'),
(1, 27, '2026-02-27 15:35:01'),
(1, 28, '2026-02-27 15:54:08'),
(1, 29, '2026-03-01 23:28:23'),
(1, 30, '2026-02-28 20:21:24'),
(1, 31, '2026-03-01 16:48:11'),
(1, 32, '2026-02-28 20:26:10'),
(1, 33, '2026-02-28 23:08:05'),
(1, 34, '2026-03-01 23:57:26'),
(1, 37, '2026-03-01 04:19:31'),
(1, 38, '2026-03-01 17:45:41'),
(1, 39, '2026-03-01 23:27:40'),
(1, 40, '2026-03-02 00:06:56'),
(1, 41, '2026-03-02 00:38:00'),
(129, 1, '2026-02-24 01:59:51'),
(129, 2, '2026-02-24 00:59:34'),
(129, 5, '2026-02-26 20:43:28'),
(129, 16, '2026-02-23 17:49:06'),
(129, 17, '2026-02-23 15:28:59'),
(129, 18, '2026-02-23 17:15:45'),
(129, 19, '2026-02-24 00:57:31'),
(129, 20, '2026-02-24 03:54:41'),
(129, 21, '2026-02-24 15:14:36'),
(129, 24, '2026-02-24 21:02:10'),
(129, 25, '2026-02-24 21:05:34'),
(129, 29, '2026-03-01 23:25:10'),
(129, 31, '2026-02-28 03:41:02'),
(129, 32, '2026-02-28 03:44:37'),
(129, 39, '2026-03-01 23:16:18'),
(129, 40, '2026-03-02 00:11:28'),
(129, 41, '2026-03-02 00:38:10'),
(131, 20, '2026-02-26 21:41:55'),
(131, 22, '2026-02-27 16:09:32'),
(131, 25, '2026-02-27 16:29:33'),
(131, 27, '2026-02-27 15:32:37'),
(131, 28, '2026-02-27 15:35:55');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `topic_subscriptions`
--

CREATE TABLE `topic_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `topic_subscriptions`
--

INSERT INTO `topic_subscriptions` (`id`, `topic_id`, `user_id`, `created_at`) VALUES
(1, 1, 1, '2026-02-28 14:19:19');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `topic_tags`
--

CREATE TABLE `topic_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `topic_tags`
--

INSERT INTO `topic_tags` (`id`, `topic_id`, `tag_id`, `created_at`) VALUES
(1, 1, 7, '2026-02-23 04:11:06'),
(2, 1, 8, '2026-02-23 04:11:06'),
(3, 2, 7, '2026-02-23 04:15:08'),
(6, 29, 7, '2026-02-27 14:14:53'),
(7, 31, 7, '2026-02-27 23:58:35'),
(8, 31, 11, '2026-02-27 23:58:35'),
(9, 31, 12, '2026-02-27 23:58:35'),
(12, 38, 7, '2026-03-01 14:46:33'),
(13, 38, 17, '2026-03-01 14:46:33');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `custom_title` varchar(128) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` smallint(5) UNSIGNED NOT NULL DEFAULT 3,
  `approved_at` datetime DEFAULT NULL,
  `locale` varchar(5) NOT NULL DEFAULT 'tr',
  `avatar_path` varchar(255) DEFAULT NULL,
  `cover_photo_path` varchar(255) DEFAULT NULL,
  `reputation_positive` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reputation_negative` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `location` varchar(128) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `signature` varchar(500) DEFAULT NULL,
  `first_name` varchar(128) DEFAULT NULL,
  `last_name` varchar(128) DEFAULT NULL,
  `show_name` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Ad soyad profil/postbitte gösterilsin mi',
  `birthday` date DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `warning_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reward_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified_at` datetime DEFAULT NULL,
  `email_verification_token` varchar(64) DEFAULT NULL,
  `admin_twofa_question` varchar(255) DEFAULT NULL,
  `admin_twofa_answer_hash` varchar(255) DEFAULT NULL,
  `available_invites` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `trust_score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `message_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_suspended` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `suspended_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `url_key` varchar(24) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `custom_title`, `email`, `password_hash`, `role_id`, `approved_at`, `locale`, `avatar_path`, `cover_photo_path`, `reputation_positive`, `reputation_negative`, `location`, `website`, `bio`, `signature`, `first_name`, `last_name`, `show_name`, `birthday`, `is_verified`, `is_banned`, `warning_points`, `reward_points`, `remember_token`, `last_activity_at`, `created_at`, `updated_at`, `email_verified_at`, `email_verification_token`, `admin_twofa_question`, `admin_twofa_answer_hash`, `available_invites`, `trust_score`, `message_count`, `is_suspended`, `suspended_at`, `closed_at`, `url_key`) VALUES
(1, 'Sinek10', 'Forum Yöneticisi', 'sys@rootali.net', '$2y$12$LbjGJsrgj/mLYaIEerLzhuA9W2BpVegGHOU2u9pgeakf2X0/T7J0S', 1, NULL, 'tr', 'uploads/avatars/2026/02/u1_9f8a4d1c.png', 'uploads/covers/2026/02/u1_138eabc1.jpg', 1, 0, 'Trabzon', 'https://www.megaforbb.com.tr', 'Hakkımda bilinen şeyler çok az', 'Yazdığımız şeyler bizi temsil eder, Efendilik iyidir.', 'Sinek', 'Onlu', 1, '1993-07-24', 1, 0, 0, 150, NULL, '2026-03-02 00:38:00', '2026-02-20 08:15:02', '2026-03-02 00:38:00', '2026-02-21 15:39:55', NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL),
(129, 'kaan', NULL, 'kaan@kaan.com', '$2y$10$nCdypWVFYxK.C.eGAKtedOAjBRBUNBxTVgeHv0ifR0HBMkW/YnO0S', 3, '2026-02-23 05:11:01', 'tr', 'uploads/avatars/2026/02/u121_b830268c.png', NULL, 0, 0, 'İsveç', 'https://www.megaforbb.com.tr', 'Hakkımda fazla şey bilinmez', 'Burada benim imzam olması gerekiyormuş öyle söylüyorlar.', 'Kaan', 'Demo', 0, '2026-02-23', 0, 0, 0, 0, NULL, '2026-03-02 00:38:31', '2026-02-26 18:45:11', '2026-03-02 00:38:31', NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL),
(130, 'softwarencoder', '', 'softwarencoder@yavuz-selim.com', '$2y$12$02lsCT1glzprK02qr/MWNuoVoI3aEdRGlzXHQlYO7476hAFILi22a', 1, '2026-02-23 20:50:11', 'en', 'https://www.gravatar.com/avatar/c4aa5045243955ac2ef60112e7e427f6?d=mp&s=200', NULL, 0, 0, '', '', '', NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, NULL, '2026-02-24 11:11:49', '2026-02-26 18:45:11', '2026-02-26 18:45:11', '2026-02-23 18:53:24', NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL),
(131, 'slaweally', NULL, 'slaweally@hotmail.com', '$2y$12$mvYQvepqBNT/Q63kcdiwZO4oM2sDKspRkOrZfYlXXkx7hareox7zi', 3, '2026-02-26 21:38:25', 'tr', 'https://www.gravatar.com/avatar/2f282f3bcba16bede1e498e35ced9908?d=mp&s=200', NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, NULL, '2026-02-27 17:04:34', '2026-02-26 21:38:25', '2026-02-27 17:04:34', '2026-02-26 21:39:14', NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_activities`
--

CREATE TABLE `user_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `user_activities`
--

INSERT INTO `user_activities` (`id`, `user_id`, `action_type`, `item_id`, `details`, `created_at`) VALUES
(1, 1, 'post_created', 49, '{\"topic_id\": 17}', '2026-02-23 15:14:48'),
(2, 1, 'like_given', 48, '{\"owner_id\": 121, \"topic_id\": 17}', '2026-02-23 15:14:52'),
(3, 121, 'like_given', 46, '{\"owner_id\": 1, \"topic_id\": 17}', '2026-02-23 15:29:01'),
(4, 121, 'rep_given', 1, '{\"value\": 1, \"target_username\": null}', '2026-02-23 15:29:07'),
(5, 1, 'rep_given', 121, '{\"value\": -1, \"post_id\": 48, \"topic_id\": 17, \"topic_title\": \"Yeni Özellikler ve Eklent…\", \"target_username\": \"kaan\"}', '2026-02-23 15:52:29'),
(6, 1, 'like_given', 42, '{\"owner_id\": 121, \"topic_id\": 15, \"topic_title\": \"Arayüz Tasarımı Geri Bild…\"}', '2026-02-23 15:52:39'),
(7, 1, 'topic_created', 18, '{\"slug\": \"how-do-i-get-php-errors-to-display-1771855706\", \"title\": \"How do I get PHP errors to display?\", \"forum_id\": 1, \"forum_name\": \"News and Updates\"}', '2026-02-23 17:08:26'),
(8, 121, 'post_created', 51, '{\"topic_id\": 18, \"topic_title\": \"\"}', '2026-02-23 17:14:53'),
(9, 1, 'post_created', 52, '{\"topic_id\": 18, \"topic_title\": \"\"}', '2026-02-23 17:16:03'),
(10, 1, 'topic_created', 19, '{\"title\":\"Kullanıcı ve Post etiket test\",\"slug\":\"kullanici-ve-post-etiket-test-1771890865\",\"forum_id\":6,\"forum_name\":\"Test & Demo\"}', '2026-02-24 00:54:25'),
(11, 121, 'post_created', 54, '{\"topic_id\":19,\"topic_title\":\"\"}', '2026-02-24 00:57:31'),
(12, 1, 'like_given', 54, '{\"topic_id\":19,\"owner_id\":121,\"topic_title\":\"Kullanıcı ve Post etiket …\"}', '2026-02-24 01:05:04'),
(14, 1, 'post_created', 67, '{\"topic_id\":1,\"topic_title\":\"MegaforBB v0.1.1 Yayınlandı\",\"body_snippet\":\"\\/** Redirect için güvenli URL: sadece relative veya aynı host. Open re…\"}', '2026-02-24 23:49:21'),
(15, 1, 'rep_given', 121, '{\"value\":1,\"target_username\":\"kaan\",\"post_id\":30,\"topic_id\":11,\"topic_title\":\"Performans İyileştirmeler…\"}', '2026-02-24 23:50:24'),
(16, 1, 'like_given', 25, '{\"topic_id\":10,\"owner_id\":121,\"topic_title\":\"Sistem Hakkında Önemli Bi…\"}', '2026-02-24 23:52:41'),
(17, 1, 'post_created', 68, '{\"topic_id\":6,\"topic_title\":\"Yeni Özellikler ve Eklentiler - 232\",\"body_snippet\":\"&nbsp; &nbsp; public function unblock(string $username): string {&nbsp…\"}', '2026-02-24 23:59:09'),
(18, 1, 'post_created', 69, '{\"topic_id\":1,\"topic_title\":\"MegaforBB v0.1.1 Yayınlandı\",\"body_snippet\":\"## 1. Eksik \\/ Eşleşmemiş Modellerin Tamamlanması- [ ] **1.1** `Convers…\"}', '2026-02-25 00:29:57'),
(19, 1, 'like_given', 3, '{\"topic_id\":1,\"owner_id\":121,\"topic_title\":\"MegaforBB v0.1.1 Yayınlan…\"}', '2026-02-25 00:30:13'),
(20, 1, 'topic_created', 20, '{\"title\":\"Veritabanı bağlı ama tablolar oluşturulmamış. Önce şemayı içe a\",\"slug\":\"veritabani-bagli-ama-tablolar-olusturulmamis-once-semayi-ice-a-1771969439\",\"forum_id\":3,\"forum_name\":\"Bug Reports\"}', '2026-02-25 00:43:59'),
(21, 1, 'post_created', 71, '{\"topic_id\":20,\"topic_title\":\"Veritabanı bağlı ama tablolar oluşturulmamış. Önce şemayı içe a\",\"body_snippet\":\"Veritabanı bağlı ama tablolar oluşturulmamış. Önce şemayı içe aktar.##…\"}', '2026-02-25 00:44:26'),
(22, 1, 'post_created', 72, '{\"topic_id\":20,\"topic_title\":\"Veritabanı bağlı ama tablolar oluşturulmamış. Önce şemayı içe a\",\"body_snippet\":\"oooo kamooonnnn\"}', '2026-02-25 01:15:07'),
(23, 1, 'post_created', 73, '{\"topic_id\":1,\"topic_title\":\"MegaforBB v0.1.1 Yayınlandı\",\"body_snippet\":\"## 5. İsteğe Bağlı İyileştirmeler- **ADMIN_PATH tutarlılığı:** Bazı ad…\"}', '2026-02-25 01:25:39'),
(24, 1, 'post_created', 74, '{\"topic_id\":11,\"topic_title\":\"Performans İyileştirmeleri Üzerine - 431\",\"body_snippet\":\"sdsdadasdsadSinek10 yazdı:Özet:&amp;nbsp;Cevap yazma sırasında kullanı…\"}', '2026-02-25 01:59:15'),
(25, 1, 'post_created', 75, '{\"topic_id\":5,\"topic_title\":\"Haftalık Güncelleme  - İlerleme konusu\",\"body_snippet\":\"@kaan ile #2 mesajını test ediyoruz.\"}', '2026-02-25 02:40:47'),
(26, 1, 'topic_created', 21, '{\"title\":\"MegaforBB – Sunucuya Yükleme\",\"slug\":\"megaforbb-sunucuya-yukleme-1771982228\",\"forum_id\":4,\"forum_name\":\"General Questions\"}', '2026-02-25 04:17:08'),
(27, 1, 'post_created', 77, '{\"topic_id\":5,\"topic_title\":\"Haftalık Güncelleme  - İlerleme konusu\",\"body_snippet\":\"\"}', '2026-02-25 04:18:37'),
(28, 1, 'topic_created', 24, '{\"title\":\"Guest Content Hiding Feature Walkthrough\",\"slug\":\"guest-content-hiding-feature-walkthrough-1771983472\",\"forum_id\":6,\"forum_name\":\"Test & Demo\"}', '2026-02-25 04:37:52'),
(29, 1, 'post_created', 80, '{\"topic_id\":19,\"topic_title\":\"Kullanıcı ve Post etiket test\",\"body_snippet\":\"lost:)\"}', '2026-02-26 16:49:51'),
(30, 1, 'post_created', 81, '{\"topic_id\":19,\"topic_title\":\"Kullanıcı ve Post etiket test\",\"body_snippet\":\"❤️[secret]Secret content[\\/secret]\"}', '2026-02-26 17:13:12'),
(31, 1, 'topic_created', 26, '{\"title\":\"Sansür Koruma Sistemi\",\"slug\":\"sansur-koruma-sistemi-1772127550\",\"forum_id\":1,\"forum_name\":\"News and Updates\"}', '2026-02-26 20:39:11'),
(32, 1, 'post_created', 57, '{\"topic_id\":5,\"topic_title\":\"Haftalık Güncelleme  - İlerleme konusu\",\"body_snippet\":\"Haftalık güncelleme işlemleriSansür sistemi geliştirildiMegaforBB - Me…\"}', '2026-02-26 20:42:29'),
(33, 129, 'like_given', 57, '{\"topic_id\":5,\"owner_id\":1,\"topic_title\":\"Haftalık Güncelleme  - İl…\"}', '2026-02-26 20:43:31'),
(34, 1, 'post_created', 58, '{\"topic_id\":15,\"topic_title\":\"Arayüz Tasarımı Geri Bildirimleri\",\"body_snippet\":\"Sinek10 yazdı:Merhabalar.MegaforBB Forum sisteminin Tasarım ve tema üz…\"}', '2026-02-26 20:45:19'),
(35, 1, 'topic_created', 27, '{\"title\":\"Dil Sistemi ve Twig Entegrasyonu\",\"slug\":\"dil-sistemi-ve-twig-entegrasyonu-1772128113\",\"forum_id\":1,\"forum_name\":\"News and Updates\"}', '2026-02-26 20:48:33'),
(36, 1, 'topic_created', 28, '{\"title\":\"Kullanıcı Hesabı Askıya Alma ve Kalıcı Kapatma\",\"slug\":\"kullanici-hesabi-askiya-alma-ve-kalici-kapatma-1772129013\",\"forum_id\":1,\"forum_name\":\"News and Updates\"}', '2026-02-26 21:03:33'),
(37, 1, 'post_created', 61, '{\"topic_id\":24,\"topic_title\":\"Tema motoru için Blade vs Twig\",\"body_snippet\":\"Moder sistemler, Gelişmiş yönetimi, ve Katı güvenlik kuralları nedeni …\"}', '2026-02-26 21:06:49'),
(38, 1, 'post_created', 62, '{\"topic_id\":20,\"topic_title\":\"Kullanıcı kayıt - Captcha sorunu\",\"body_snippet\":\"İlgil sorun çözüldü.\"}', '2026-02-26 21:20:12'),
(39, 131, 'post_created', 63, '{\"topic_id\":25,\"topic_title\":\"Bildirim sisteminde hata\",\"body_snippet\":\"Bildirim sistemindeki hata header\'da bildirim görünüyor ancak bildirim…\"}', '2026-02-26 21:43:00'),
(40, 131, 'like_given', 55, '{\"topic_id\":25,\"owner_id\":129,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-26 21:43:08'),
(41, 131, 'like_given', 59, '{\"topic_id\":27,\"owner_id\":1,\"topic_title\":\"Dil Sistemi ve Twig Enteg…\"}', '2026-02-27 15:32:40'),
(42, 131, 'like_given', 60, '{\"topic_id\":28,\"owner_id\":1,\"topic_title\":\"Kullanıcı Hesabı Askıya A…\"}', '2026-02-27 15:35:57'),
(43, 131, 'rep_given', 1, '{\"value\":1,\"target_username\":\"Sinek10\",\"post_id\":60,\"topic_id\":28,\"topic_title\":\"Kullanıcı Hesabı Askıya A…\"}', '2026-02-27 15:36:02'),
(44, 1, 'post_created', 64, '{\"topic_id\":25,\"topic_title\":\"Bildirim sisteminde hata\",\"body_snippet\":\"kaan yazdı:Bildirim sisteminde ufak bir hata var, Bildirimleri okundu …\"}', '2026-02-27 15:55:56'),
(45, 1, 'like_given', 63, '{\"topic_id\":25,\"owner_id\":131,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-27 15:56:01'),
(46, 1, 'like_given', 55, '{\"topic_id\":25,\"owner_id\":129,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-27 15:56:09'),
(47, 131, 'like_given', 64, '{\"topic_id\":25,\"owner_id\":1,\"topic_title\":\"Bildirim sisteminde hata\"}', '2026-02-27 15:56:39'),
(48, 131, 'post_created', 65, '{\"topic_id\":22,\"topic_title\":\"Kurulum ve güncelleme sistemi\",\"body_snippet\":\"Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap ed…\"}', '2026-02-27 15:58:13'),
(49, 1, 'topic_created', 29, '{\"title\":\"Konu dosya test ve Etiket Test\",\"slug\":\"konu-dosya-test-ve-etiket-test-1772198093\",\"forum_id\":6,\"forum_name\":\"Test & Demo\"}', '2026-02-27 16:14:53'),
(50, 1, 'topic_created', 30, '{\"title\":\"Mesaj gönderim hatası\",\"slug\":\"mesaj-gonderim-hatasi-1772199096\",\"forum_id\":3,\"forum_name\":\"Bug Reports\"}', '2026-02-27 16:31:36'),
(51, 1, 'post_created', 68, '{\"topic_id\":30,\"topic_title\":\"Mesaj gönderim hatası\",\"body_snippet\":\"Sorun çözüldü, Mesaj gönderimi sorunsuz yapılıyor.\"}', '2026-02-27 18:08:37'),
(52, 129, 'topic_created', 31, '{\"title\":\"SEF Url desteği eklenmeli\",\"slug\":\"sef-url-destegi-eklenmeli-31\",\"forum_id\":3,\"forum_name\":\"Bug Reports\"}', '2026-02-28 01:58:35'),
(53, 1, 'post_created', 70, '{\"topic_id\":31,\"topic_title\":\"SEF Url desteği eklenmeli\",\"body_snippet\":\"Şu anda sistemde bu özellik var zaten 3 kademeli çalışıyor;1- Sef:raka…\"}', '2026-02-28 02:07:24'),
(54, 1, 'like_given', 65, '{\"topic_id\":22,\"owner_id\":131,\"topic_title\":\"Kurulum ve güncelleme sis…\"}', '2026-02-28 02:37:12'),
(55, 1, 'post_created', 71, '{\"topic_id\":22,\"topic_title\":\"Kurulum ve güncelleme sistemi\",\"body_snippet\":\"slaweally yazdı:Kurulum sistemi olmasına gerek yok. Herkese tüm kullan…\"}', '2026-02-28 02:37:48'),
(56, 1, 'topic_created', 32, '{\"title\":\"Profil Yorumları\",\"slug\":\"profil-yorumlari-32\",\"forum_id\":1,\"forum_name\":\"News and Updates\"}', '2026-02-28 03:24:13'),
(57, 129, 'like_given', 72, '{\"topic_id\":32,\"owner_id\":1,\"topic_title\":\"Kullanıcı Profil Yorumlar…\"}', '2026-02-28 03:44:39'),
(58, 1, 'topic_created', 33, '{\"title\":\"Planlanmış konu Test\",\"slug\":\"planlanmis-konu-test-33\",\"forum_id\":6,\"forum_name\":\"Test & Demo\"}', '2026-02-28 22:58:22'),
(59, 1, 'topic_created', 34, '{\"title\":\"Planlanmış konu TEST\",\"slug\":\"planlanmis-konu-test-34\",\"forum_id\":6,\"forum_name\":\"Test & Demo\"}', '2026-02-28 23:27:04'),
(60, 1, 'topic_created', 34, '{\"title\":\"Planlanmış konu TEST\",\"slug\":\"planlanmis-konu-test-34\",\"forum_id\":6,\"forum_name\":\"Test & Demo\"}', '2026-02-28 23:41:01'),
(61, 1, 'topic_created', 38, '{\"title\":\"Döküman Sistemi geliştirildi.\",\"slug\":\"dokuman-sistemi-gelistirildi-38\",\"forum_id\":1,\"forum_name\":\"Duyuru ve Güncelleme\"}', '2026-03-01 16:46:33'),
(62, 1, 'post_created', 79, '{\"topic_id\":5,\"topic_title\":\"Haftalık Güncelleme  - İlerleme konusu\",\"body_snippet\":\"Çevrimiçi üyeleri - Botlar sayfası yapıldı. Site haritası sistemi geli…\"}', '2026-03-01 17:30:52'),
(63, 1, 'topic_created', 39, '{\"title\":\"Özel -private konu test\",\"slug\":\"ozel-private-konu-test-39\",\"forum_id\":6,\"forum_name\":\"Test ve Demo\"}', '2026-03-01 22:56:42'),
(64, 129, 'post_created', 81, '{\"topic_id\":29,\"topic_title\":\"Konu dosya test ve Etiket Test\",\"body_snippet\":\"Cevap kısmında da dosya yüklenebiliyr olması iyi olmuş\"}', '2026-03-01 23:25:10'),
(65, 129, 'like_given', 66, '{\"topic_id\":29,\"owner_id\":1,\"topic_title\":\"Konu dosya test ve Etiket…\"}', '2026-03-01 23:25:16'),
(66, 1, 'post_created', 82, '{\"topic_id\":5,\"topic_title\":\"Haftalık Güncelleme  - İlerleme konusu\",\"body_snippet\":\"Sinek10 yazdı:Çevrimiçi üyeleri - Botlar sayfası yapıldı.Site haritası…\"}', '2026-03-01 23:28:00'),
(67, 129, 'topic_created', 40, '{\"title\":\"Anti Bump Mesaj - yorum artırma sistemi\",\"slug\":\"anti-bump-mesaj-yorum-artirma-sistemi-40\",\"forum_id\":6,\"forum_name\":\"Test ve Demo\"}', '2026-03-01 23:43:52'),
(68, 129, 'post_created', 84, '{\"topic_id\":40,\"topic_title\":\"Anti Bump Mesaj - yorum artırma sistemi\",\"body_snippet\":\"Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda …\"}', '2026-03-01 23:45:42'),
(69, 1, 'post_created', 85, '{\"topic_id\":40,\"topic_title\":\"Anti Bump Mesaj - yorum artırma sistemi\",\"body_snippet\":\"kaan yazdı:Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması iç…\"}', '2026-03-02 00:00:05'),
(70, 1, 'like_given', 84, '{\"topic_id\":40,\"owner_id\":129,\"topic_title\":\"Anti Bump Mesaj - yorum a…\"}', '2026-03-02 00:07:01'),
(71, 129, 'like_given', 85, '{\"topic_id\":40,\"owner_id\":1,\"topic_title\":\"Anti Bump Mesaj - yorum a…\"}', '2026-03-02 00:11:33'),
(72, 1, 'topic_created', 41, '{\"title\":\"Soru - Cevap -Test konusu\",\"slug\":\"soru-cevap-test-konusu-41\",\"forum_id\":6,\"forum_name\":\"Test ve Demo\"}', '2026-03-02 00:14:11'),
(73, 129, 'post_created', 87, '{\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\",\"body_snippet\":\"Şu anda mantık olarak sistemde çalışıyor ancak ne derece iyice incelem…\"}', '2026-03-02 00:14:58'),
(74, 1, 'post_created', 88, '{\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\",\"body_snippet\":\"kaan yazdı:Şu anda mantık olarak sistemde çalışıyor ancak ne derece iy…\"}', '2026-03-02 00:17:59');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_bans`
--

CREATE TABLE `user_bans` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_blocks`
--

CREATE TABLE `user_blocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Engelleyen',
  `blocked_user_id` int(10) UNSIGNED NOT NULL COMMENT 'Engellenen',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_custom_fields`
--

CREATE TABLE `user_custom_fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `field_key` varchar(64) NOT NULL,
  `field_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_custom_fields`
--

INSERT INTO `user_custom_fields` (`id`, `user_id`, `field_key`, `field_value`) VALUES
(1, 1, 'bilgisayar', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_field_definitions`
--

CREATE TABLE `user_field_definitions` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL COMMENT 'Görünen ad',
  `field_key` varchar(64) NOT NULL COMMENT 'Benzersiz anahtar (slug)',
  `field_type` varchar(32) NOT NULL DEFAULT 'text' COMMENT 'text, number, date, textarea, select',
  `field_options` text DEFAULT NULL COMMENT 'Select için JSON: ["A","B"]',
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `show_on_registration` tinyint(1) NOT NULL DEFAULT 0,
  `show_on_profile` tinyint(1) NOT NULL DEFAULT 1,
  `show_in_postbit` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_follows`
--

CREATE TABLE `user_follows` (
  `id` int(10) UNSIGNED NOT NULL,
  `follower_id` int(10) UNSIGNED NOT NULL,
  `following_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_reputations`
--

CREATE TABLE `user_reputations` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_user_id` int(10) UNSIGNED NOT NULL,
  `to_user_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED DEFAULT NULL,
  `value` tinyint(4) NOT NULL COMMENT '1 = +rep, -1 = -rep',
  `comment` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_reputations`
--

INSERT INTO `user_reputations` (`id`, `from_user_id`, `to_user_id`, `post_id`, `value`, `comment`, `created_at`) VALUES
(1, 131, 1, 60, 1, 'Bravo', '2026-02-27 15:36:02');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_warnings`
--

CREATE TABLE `user_warnings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `points` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `position_key` (`position_key`),
  ADD KEY `enabled` (`enabled`);

--
-- Tablo için indeksler `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_dates` (`is_active`,`show_from`,`show_until`),
  ADD KEY `idx_display_location` (`display_location`);

--
-- Tablo için indeksler `announcement_dismissals`
--
ALTER TABLE `announcement_dismissals`
  ADD PRIMARY KEY (`user_id`,`announcement_id`),
  ADD KEY `idx_announcement` (`announcement_id`);

--
-- Tablo için indeksler `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_attachments_url_key` (`url_key`),
  ADD KEY `idx_att_post` (`post_id`),
  ADD KEY `idx_att_user` (`user_id`);

--
-- Tablo için indeksler `blocked_email_domains`
--
ALTER TABLE `blocked_email_domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_domain` (`domain`(191)),
  ADD KEY `idx_bed_domain` (`domain`(64));

--
-- Tablo için indeksler `blocked_usernames`
--
ALTER TABLE `blocked_usernames`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bu_pattern` (`pattern`(64));

--
-- Tablo için indeksler `blocked_words`
--
ALTER TABLE `blocked_words`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bw_word` (`word`(64));

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Tablo için indeksler `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Tablo için indeksler `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_message_id` (`contact_message_id`),
  ADD KEY `idx_replied_at` (`created_at`);

--
-- Tablo için indeksler `content_permissions`
--
ALTER TABLE `content_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_lookup` (`content_type`,`content_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `cp_perm_fk` (`permission_id`);

--
-- Tablo için indeksler `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_conversations_url_key` (`url_key`);

--
-- Tablo için indeksler `conversation_user`
--
ALTER TABLE `conversation_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conv_user` (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `doc_pages`
--
ALTER TABLE `doc_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_doc_pages_section_slug` (`section_id`,`slug`),
  ADD KEY `idx_doc_pages_section` (`section_id`);

--
-- Tablo için indeksler `doc_sections`
--
ALTER TABLE `doc_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_doc_sections_parent_slug` (`parent_id`,`slug`),
  ADD KEY `idx_doc_sections_sort` (`sort_order`),
  ADD KEY `idx_doc_sections_parent` (`parent_id`);

--
-- Tablo için indeksler `forums`
--
ALTER TABLE `forums`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `sort_order` (`sort_order`),
  ADD KEY `last_post_at` (`last_post_at`);

--
-- Tablo için indeksler `forum_stats`
--
ALTER TABLE `forum_stats`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `group_permissions`
--
ALTER TABLE `group_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_perm` (`role_id`,`permission_id`),
  ADD KEY `gp_perm_fk` (`permission_id`);

--
-- Tablo için indeksler `import_errors`
--
ALTER TABLE `import_errors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_source_step` (`source`,`step`);

--
-- Tablo için indeksler `import_id_map`
--
ALTER TABLE `import_id_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_source_entity_old` (`source`,`entity_type`,`old_id`),
  ADD KEY `idx_entity_new` (`entity_type`,`new_id`);

--
-- Tablo için indeksler `import_progress`
--
ALTER TABLE `import_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_source_step` (`source`,`step`);

--
-- Tablo için indeksler `invitations`
--
ALTER TABLE `invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invitations_code_unique` (`code`),
  ADD KEY `invitations_user_id` (`user_id`),
  ADD KEY `invitations_used_by` (`used_by`);

--
-- Tablo için indeksler `locales`
--
ALTER TABLE `locales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Tablo için indeksler `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_migration` (`migration`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_notifications_url_key` (`url_key`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `read_at` (`read_at`);

--
-- Tablo için indeksler `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Tablo için indeksler `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pr_email` (`email`),
  ADD KEY `idx_pr_token` (`token`);

--
-- Tablo için indeksler `permission_definitions`
--
ALTER TABLE `permission_definitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Tablo için indeksler `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_poll_topic` (`topic_id`);

--
-- Tablo için indeksler `poll_options`
--
ALTER TABLE `poll_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_po_poll` (`poll_id`);

--
-- Tablo için indeksler `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pv_user_option` (`poll_id`,`option_id`,`user_id`),
  ADD KEY `idx_pv_poll` (`poll_id`),
  ADD KEY `idx_pv_user` (`user_id`);

--
-- Tablo için indeksler `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_posts_url_key` (`url_key`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`);
ALTER TABLE `posts` ADD FULLTEXT KEY `ft_posts_body` (`body`);

--
-- Tablo için indeksler `post_edits`
--
ALTER TABLE `post_edits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pe_post` (`post_id`);

--
-- Tablo için indeksler `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_user` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `post_reports`
--
ALTER TABLE `post_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_reports_post_id` (`post_id`),
  ADD KEY `post_reports_reporter_user_id` (`reporter_user_id`),
  ADD KEY `post_reports_status` (`status`),
  ADD KEY `post_reports_created_at` (`created_at`),
  ADD KEY `post_reports_reviewed_by_fk` (`reviewed_by`);

--
-- Tablo için indeksler `post_votes`
--
ALTER TABLE `post_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_post_user` (`post_id`,`user_id`),
  ADD KEY `idx_post` (`post_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Tablo için indeksler `prefixes`
--
ALTER TABLE `prefixes`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `profile_comments`
--
ALTER TABLE `profile_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_profile_comments_user_id` (`user_id`),
  ADD KEY `idx_profile_comments_author_id` (`author_id`),
  ADD KEY `idx_profile_comments_created_at` (`created_at`);

--
-- Tablo için indeksler `reward_levels`
--
ALTER TABLE `reward_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Tablo için indeksler `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Tablo için indeksler `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `group` (`group`);

--
-- Tablo için indeksler `sidebar_widgets`
--
ALTER TABLE `sidebar_widgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Tablo için indeksler `smileys`
--
ALTER TABLE `smileys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_smileys_code` (`code`(8)),
  ADD KEY `idx_smileys_sort` (`sort_order`);

--
-- Tablo için indeksler `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tag_slug` (`slug`),
  ADD KEY `idx_tag_name` (`name`);

--
-- Tablo için indeksler `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_topics_url_key` (`url_key`),
  ADD KEY `forum_id` (`forum_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `prefix_id` (`prefix_id`),
  ADD KEY `is_sticky` (`is_sticky`),
  ADD KEY `last_post_at` (`last_post_at`),
  ADD KEY `slug` (`slug`(191)),
  ADD KEY `moved_to_topic_id` (`moved_to_topic_id`),
  ADD KEY `is_private` (`is_private`),
  ADD KEY `type` (`type`),
  ADD KEY `idx_accepted_post` (`accepted_post_id`),
  ADD KEY `idx_topics_status_scheduled` (`status`,`scheduled_publish_at`);
ALTER TABLE `topics` ADD FULLTEXT KEY `ft_topics_title` (`title`);

--
-- Tablo için indeksler `topic_bumps`
--
ALTER TABLE `topic_bumps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_bumps_user_date` (`user_id`,`bumped_at`),
  ADD KEY `topic_bumps_topic` (`topic_id`);

--
-- Tablo için indeksler `topic_prefixes`
--
ALTER TABLE `topic_prefixes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Tablo için indeksler `topic_private_viewers`
--
ALTER TABLE `topic_private_viewers`
  ADD PRIMARY KEY (`topic_id`,`user_id`),
  ADD KEY `idx_tpv_user` (`user_id`);

--
-- Tablo için indeksler `topic_reads`
--
ALTER TABLE `topic_reads`
  ADD PRIMARY KEY (`user_id`,`topic_id`),
  ADD KEY `idx_tr_topic` (`topic_id`);

--
-- Tablo için indeksler `topic_subscriptions`
--
ALTER TABLE `topic_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `topic_user` (`topic_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `topic_tags`
--
ALTER TABLE `topic_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_topic_tag` (`topic_id`,`tag_id`),
  ADD KEY `idx_tt_topic` (`topic_id`),
  ADD KEY `idx_tt_tag` (`tag_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_users_url_key` (`url_key`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `last_activity_at` (`last_activity_at`),
  ADD KEY `created_at` (`created_at`);

--
-- Tablo için indeksler `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Tablo için indeksler `user_bans`
--
ALTER TABLE `user_bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Tablo için indeksler `user_blocks`
--
ALTER TABLE `user_blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_blocked` (`user_id`,`blocked_user_id`),
  ADD KEY `blocked_user_id` (`blocked_user_id`);

--
-- Tablo için indeksler `user_custom_fields`
--
ALTER TABLE `user_custom_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_field` (`user_id`,`field_key`);

--
-- Tablo için indeksler `user_field_definitions`
--
ALTER TABLE `user_field_definitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `field_key` (`field_key`);

--
-- Tablo için indeksler `user_follows`
--
ALTER TABLE `user_follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `follower_following` (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Tablo için indeksler `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_preference` (`user_id`,`preference_key`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Tablo için indeksler `user_reputations`
--
ALTER TABLE `user_reputations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Tablo için indeksler `user_warnings`
--
ALTER TABLE `user_warnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `blocked_email_domains`
--
ALTER TABLE `blocked_email_domains`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Tablo için AUTO_INCREMENT değeri `blocked_usernames`
--
ALTER TABLE `blocked_usernames`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `blocked_words`
--
ALTER TABLE `blocked_words`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `content_permissions`
--
ALTER TABLE `content_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Tablo için AUTO_INCREMENT değeri `conversation_user`
--
ALTER TABLE `conversation_user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `doc_pages`
--
ALTER TABLE `doc_pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `doc_sections`
--
ALTER TABLE `doc_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `forums`
--
ALTER TABLE `forums`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `forum_stats`
--
ALTER TABLE `forum_stats`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `group_permissions`
--
ALTER TABLE `group_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `import_errors`
--
ALTER TABLE `import_errors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `import_id_map`
--
ALTER TABLE `import_id_map`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- Tablo için AUTO_INCREMENT değeri `import_progress`
--
ALTER TABLE `import_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `invitations`
--
ALTER TABLE `invitations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `locales`
--
ALTER TABLE `locales`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Tablo için AUTO_INCREMENT değeri `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `permission_definitions`
--
ALTER TABLE `permission_definitions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `poll_votes`
--
ALTER TABLE `poll_votes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- Tablo için AUTO_INCREMENT değeri `post_edits`
--
ALTER TABLE `post_edits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Tablo için AUTO_INCREMENT değeri `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `post_reports`
--
ALTER TABLE `post_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `post_votes`
--
ALTER TABLE `post_votes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `prefixes`
--
ALTER TABLE `prefixes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `profile_comments`
--
ALTER TABLE `profile_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `reward_levels`
--
ALTER TABLE `reward_levels`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `roles`
--
ALTER TABLE `roles`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- Tablo için AUTO_INCREMENT değeri `sidebar_widgets`
--
ALTER TABLE `sidebar_widgets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `smileys`
--
ALTER TABLE `smileys`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Tablo için AUTO_INCREMENT değeri `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Tablo için AUTO_INCREMENT değeri `topic_bumps`
--
ALTER TABLE `topic_bumps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `topic_prefixes`
--
ALTER TABLE `topic_prefixes`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `topic_subscriptions`
--
ALTER TABLE `topic_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `topic_tags`
--
ALTER TABLE `topic_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- Tablo için AUTO_INCREMENT değeri `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- Tablo için AUTO_INCREMENT değeri `user_bans`
--
ALTER TABLE `user_bans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `user_blocks`
--
ALTER TABLE `user_blocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `user_custom_fields`
--
ALTER TABLE `user_custom_fields`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `user_field_definitions`
--
ALTER TABLE `user_field_definitions`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `user_follows`
--
ALTER TABLE `user_follows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `user_reputations`
--
ALTER TABLE `user_reputations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `user_warnings`
--
ALTER TABLE `user_warnings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  ADD CONSTRAINT `fk_contact_reply_message` FOREIGN KEY (`contact_message_id`) REFERENCES `contact_messages` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `content_permissions`
--
ALTER TABLE `content_permissions`
  ADD CONSTRAINT `cp_perm_fk` FOREIGN KEY (`permission_id`) REFERENCES `permission_definitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cp_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cp_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `conversation_user`
--
ALTER TABLE `conversation_user`
  ADD CONSTRAINT `conv_user_conv_fk` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conv_user_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `doc_pages`
--
ALTER TABLE `doc_pages`
  ADD CONSTRAINT `fk_doc_pages_section` FOREIGN KEY (`section_id`) REFERENCES `doc_sections` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `doc_sections`
--
ALTER TABLE `doc_sections`
  ADD CONSTRAINT `fk_doc_sections_parent` FOREIGN KEY (`parent_id`) REFERENCES `doc_sections` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `forums`
--
ALTER TABLE `forums`
  ADD CONSTRAINT `forums_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forums_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `group_permissions`
--
ALTER TABLE `group_permissions`
  ADD CONSTRAINT `gp_perm_fk` FOREIGN KEY (`permission_id`) REFERENCES `permission_definitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gp_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `invitations`
--
ALTER TABLE `invitations`
  ADD CONSTRAINT `invitations_used_by_foreign` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invitations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_topic_fk` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_post_fk` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `post_reports`
--
ALTER TABLE `post_reports`
  ADD CONSTRAINT `post_reports_post_fk` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_reports_reporter_fk` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_reports_reviewed_by_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `private_messages`
--
ALTER TABLE `private_messages`
  ADD CONSTRAINT `pm_conv_fk` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pm_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `profile_comments`
--
ALTER TABLE `profile_comments`
  ADD CONSTRAINT `fk_profile_comment_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_profile_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_forum_fk` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topics_moved_fk` FOREIGN KEY (`moved_to_topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topics_prefix_fk` FOREIGN KEY (`prefix_id`) REFERENCES `topic_prefixes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `topics_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `topic_prefixes`
--
ALTER TABLE `topic_prefixes`
  ADD CONSTRAINT `topic_prefixes_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `topic_private_viewers`
--
ALTER TABLE `topic_private_viewers`
  ADD CONSTRAINT `tpv_topic_fk` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tpv_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `topic_subscriptions`
--
ALTER TABLE `topic_subscriptions`
  ADD CONSTRAINT `sub_topic_fk` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sub_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Tablo kısıtlamaları `user_bans`
--
ALTER TABLE `user_bans`
  ADD CONSTRAINT `user_bans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_bans_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_blocks`
--
ALTER TABLE `user_blocks`
  ADD CONSTRAINT `user_blocks_blocked_fk` FOREIGN KEY (`blocked_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_blocks_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_custom_fields`
--
ALTER TABLE `user_custom_fields`
  ADD CONSTRAINT `user_custom_fields_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_follows`
--
ALTER TABLE `user_follows`
  ADD CONSTRAINT `follow_follower_fk` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follow_following_fk` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `fk_user_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_reputations`
--
ALTER TABLE `user_reputations`
  ADD CONSTRAINT `ur_from_user_fk` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ur_post_fk` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ur_to_user_fk` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_warnings`
--
ALTER TABLE `user_warnings`
  ADD CONSTRAINT `user_warnings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_warnings_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
