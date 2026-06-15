-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 07 Mar 2026, 01:22:21
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
(1, 66, 1, 'lost.gif', 'att_69a198ca917b70.13742958_lost.gif', 'local', 'image/gif', 355474, 6, '2026-02-27 16:14:50', NULL),
(2, 81, 129, 'pngtree-user-profile-avatar-png-image_10211467.png', 'att_69a4a0851620c9.53050130_pngtree-user-profile-avatar-png-image_10211467.png', 'local', 'image/png', 11582, 2, '2026-03-01 23:24:37', NULL);

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
(1, 'Ali', 'slaweally@hotmail.com', 'MegaforBB', 'MegaforBB\r\nBizimle iletişime geçmek için formu kullanın. En kısa sürede size dönüş yapacağız.', '127.0.0.1', 1, '2026-02-23 15:38:06'),
(2, 'Ali', 'sys@rootali.net', 'Test iletişim mesajı', 'Test bir iletişim mesajı gönderelim bakalım sonuç ne oluyor.', '78.190.138.42', 1, '2026-03-05 20:42:04');

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
(1, 1, 'En kısa sürede size dönüş yapacağız.', 1, 1, '2026-02-25 17:26:28'),
(2, 2, 'İletişim mesajına dönülen cevap', 1, 1, '2026-03-05 21:13:59');

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
(17, '2026-02-27 17:59:20', NULL),
(18, '2026-03-07 00:09:58', NULL);

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
(6, 17, 131, NULL, '2026-02-27 15:59:20'),
(7, 18, 132, '2026-03-07 00:09:58', '2026-03-06 22:09:58'),
(8, 18, 1, '2026-03-07 00:44:34', '2026-03-06 22:09:58');

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
(2, 1, 'Kurulum ve Güncelleme', 'kurulum-detaylar', '<p><b>MegaforBB</b> kurulum ve güncelleme işlemi şu anda henüz canlı sürümde olmadığı için detaylı bilgi daha sonrasında eklenecektir.</p>', 20, '2026-03-01 15:45:16', '2026-03-02 22:13:44'),
(3, 1, 'Altyapı ve teknolojiler', 'altyapi-ve-teknolojiler', '<h1 class=\"\">MegaforBB altyapı</h1><p>MegaforBB CMS Forum sistemini tamamen özel olarak Symfony&nbsp; + Laravel FW\'lerini MegaforBB Forecor çekirdeği üzerine özel olarak optimize edip sistemi geliştirdik.&nbsp;</p><p>Sistemimizde Symfony ve Laravel optimize edilerek ayrıştırılmış paket sistemi ile Forecor çekirdeğinde kullanılmaktadır.&nbsp;</p><p>Ön yüz ise: Twig tema motoru ile birlikte Alpine, ve Tailwind, Tabler.io yapıları kullanılmıştır.&nbsp;</p>', 30, '2026-03-01 15:45:30', '2026-03-01 17:49:54'),
(4, 1, 'Tavsiyeler', 'tavsiyeler', '<p>MegaforBB takip etmeye devam edin, Güzel projelerin geleceğinden emin olabilirsiniz.</p>', 40, '2026-03-01 15:45:46', '2026-03-01 23:22:44'),
(5, 2, 'Forum ve Kategori', 'forum-ve-kategori', '<h2 class=\"\">MegaforBB Forum</h2><p>Forum ve kategori sistemi genel olarak bildiğimiz Bulletin Board sisteminin gelişmiş halidir.&nbsp;</p><p>Kategori ve Forum mantığı burada Frontend\'te aynı olsa da admin panelde Kullanım ve yönetim kolaylığı için tüm sisetm sürükle bırak ile yönetilebilir olması için basitleştirilmiştir. Ana Kategori oluşturup içinde Forumlar oluştrarak Forum sitenizi hazır hale getirebilirsiniz, Forum detayları için Banner ve İkon kullanımı yerleşik olarak&nbsp; gelmektedir diğer forum yazılımlarında ekstra plugin olarak özel yapılan işlem MegaforBB bünyesinde yerleşik olarak gelmekktedir.</p><p><br></p>', 0, '2026-03-01 15:46:19', '2026-03-04 09:59:44'),
(7, 4, 'Döküman sistemi', 'documan-sistemi', '', 10, '2026-03-01 15:47:11', '2026-03-01 16:02:15'),
(8, 4, 'Makale sistemi', 'makale-sistemi', '', 20, '2026-03-01 15:48:11', '2026-03-01 16:02:17'),
(9, 4, 'Sayfa sistemi', 'sayfa-sistemi', '', 0, '2026-03-01 15:54:13', '2026-03-01 15:54:22'),
(10, 4, 'Etiket Sistemi', 'etiket-sistemi', '', 30, '2026-03-01 15:57:24', '2026-03-01 16:02:17'),
(11, 5, 'MegaforBB Download', 'megaforbb-download', '<p>MegaforBB İndirme pajketi bulunmamaktadır, Sistem henüz BETA aşamasında olduğu için sadece Beta kullanıcılarına özel kurulum sağlanmaktadır.&nbsp;</p><p>Kurulum ve sistem teknik detaylar için iletişime geçebilrsiniz.</p><p>MegaforBB Free bir Forum yazılımı Forum scripti değildir, Ücretsiz kullanım sağlanan projedir. MegaforBB Null, vey MegaforBB Warex olarak kullanmak dağıtmak ve kopyalamak kesinlikle yasaktır.&nbsp;</p><p>Forum yazılımımızı Tamamen ücretsiz bir şekilde test edip incleme ve deneyimlemek için iletişime geçebilirsiniz.</p><p><font color=\"#ff0000\"><b>Not:</b> İlgili websiteler - Forumlar&nbsp; hizmet aktif olduğu sürece anlık takp edilecektir, online olmayan, İçerik eklenmeyen tamamen boş bırakılan forumlar kapatılacaktır.&nbsp;</font></p><p><font color=\"#ff0000\"><br></font></p>', 10, '2026-03-02 22:14:49', '2026-03-04 09:57:21'),
(12, 6, 'MegaforBB Tema geliştirme kılavuzu', 'tema-geli-tirme-k-lavuzu', '<div class=\"megaforbb-doc\"><p>Bu kılavuz, MegaforBB forum yazılımında <strong>Twig</strong> tabanlı tema sistemini kullanarak nasıl tema geliştirebileceğinizi adım adım ve tüm teknik detaylarıyla anlatır. Çekirdek dosyalara dokunmadan yalnızca tema klasörü ve şablonlarla çalışırsınız.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"icindekiler\">İçindekiler</h2>\r\n<div class=\"toc\">\r\n<ol>\r\n	<li><a href=\"#tema-sistemine-giris\">Tema Sistemine Giriş</a></li>\r\n	<li><a href=\"#tema-klasor-yapisi\">Tema Klasör Yapısı</a></li>\r\n	<li><a href=\"#theme-json\">theme.json ve Tema Tanımı</a></li>\r\n	<li><a href=\"#sablon-mantigi\">Şablon (Twig) Çalışma Mantığı</a></li>\r\n	<li><a href=\"#asset-kullanimi\">Asset (CSS, JS, Resim) Kullanımı</a></li>\r\n	<li><a href=\"#sayfa-sablonlari\">Sayfa Şablonları Listesi</a></li>\r\n	<li><a href=\"#layout-degiskenleri\">Layout\'a Gelen Değişkenler</a></li>\r\n	<li><a href=\"#twig-fonksiyonlari\">Twig Fonksiyonları</a></li>\r\n	<li><a href=\"#twig-filtreleri\">Twig Filtreleri</a></li>\r\n	<li><a href=\"#base-sablon\">Base Şablon ve Bloklar</a></li>\r\n	<li><a href=\"#yeni-tema-adimlari\">Yeni Tema Oluşturma Adımları</a></li>\r\n	<li><a href=\"#admin-tema\">Admin Paneli Teması</a></li>\r\n	<li><a href=\"#sss\">Sık Sorulan Sorular</a></li>\r\n</ol>\r\n</div>\r\n\r\n<hr>\r\n\r\n<h2 id=\"tema-sistemine-giris\">1. Tema Sistemine Giriş</h2>\r\n<p>MegaforBB\'de ön yüz ve admin paneli ayrı tema sistemleriyle çalışır:</p>\r\n<ul>\r\n	<li><strong>Ön yüz temaları:</strong> Forumun ziyaretçilere görünen kısmı. <code>templates/frontend/</code> altında her klasör bir temadır.</li>\r\n	<li><strong>Admin temaları:</strong> Yönetim panelinin görünümü. <code>templates/admin/</code> altında tanımlanır.</li>\r\n</ul>\r\n<p>Tema motoru <strong>Twig</strong> şablon dilini kullanır. Aktif tema, yönetim panelinden <strong>İçerik → Tema Yönetimi</strong> bölümünden seçilir. Sistem önce aktif temanın şablonlarına bakar; bulamadığı dosyaları <strong>default</strong> temadan alır. Bu sayede tüm sayfaları yeniden yazmadan sadece değiştirmek istediğiniz şablonları override edebilirsiniz.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"tema-klasor-yapisi\">2. Tema Klasör Yapısı</h2>\r\n<p>Ön yüz teması için standart yapı aşağıdaki gibidir. Klasör adı (slug) tema listesinde ve ayarlarda kullanılır; yalnızca harf, rakam, tire ve alt çizgi kullanın (örn. <code>benim-temam</code>).</p>\r\n<pre><code>templates/frontend/benim-temam/\r\n├── theme.json          # Zorunlu — tema bilgisi (yoksa listede görünmez)\r\n├── screenshot.png      # İsteğe bağlı — tema önizleme resmi (veya screenshot.jpg)\r\n├── views/              # Zorunlu — Twig şablonları (.html.twig)\r\n│   ├── base.html.twig  # Ana iskelet (header, footer, content alanı)\r\n│   ├── index.html.twig # Forum ana sayfa içeriği\r\n│   ├── showthread.html.twig\r\n│   └── ...\r\n└── assets/             # İsteğe bağlı — CSS, JS, resimler\r\n    ├── css/\r\n    │   ├── theme.css\r\n    │   └── ...\r\n    ├── js/\r\n    │   └── theme.js\r\n    └── images/</code></pre>\r\n<ul>\r\n	<li><strong>views/</strong> — Tüm sayfa şablonları burada yer alır. Sadece değiştirmek istediğiniz dosyaları kopyalayıp düzenlemeniz yeterlidir; diğer sayfalar otomatik olarak <strong>default</strong> temadan kullanılır.</li>\r\n	<li><strong>assets/</strong> — Tema özelinde CSS, JavaScript ve resimler. Bu klasör yoksa veya bir dosya yoksa sistem <strong>default</strong> temanın aynı yoldaki dosyasına düşer. Böylece tüm asset\'leri kopyalamak zorunda kalmazsınız.</li>\r\n</ul>\r\n\r\n<hr>\r\n\r\n<h2 id=\"theme-json\">3. theme.json ve Tema Tanımı</h2>\r\n<p>Her tema klasöründe <strong>theme.json</strong> dosyası bulunmalıdır; yoksa tema yönetim sayfasında listelenmez ve etkinleştirilemez.</p>\r\n<p><strong>Örnek theme.json:</strong></p>\r\n<pre><code>{\r\n    \"name\": \"Benim Tema Adım\",\r\n    \"version\": \"1.0.0\",\r\n    \"author\": \"Adınız\",\r\n    \"description\": \"Kısa tema açıklaması; liste görünümünde kullanılır.\"\r\n}</code></pre>\r\n<table>\r\n	<thead>\r\n		<tr><th>Alan</th><th>Zorunlu</th><th>Açıklama</th></tr>\r\n	</thead>\r\n	<tbody>\r\n		<tr><td><strong>name</strong></td><td>Evet</td><td>Tema listesinde görünen ad.</td></tr>\r\n		<tr><td><strong>version</strong></td><td>Hayır</td><td>Sürüm bilgisi (örn. 1.0.0).</td></tr>\r\n		<tr><td><strong>author</strong></td><td>Hayır</td><td>Tema yazarı.</td></tr>\r\n		<tr><td><strong>description</strong></td><td>Hayır</td><td>Bir satırlık açıklama.</td></tr>\r\n	</tbody>\r\n</table>\r\n<p>Dosya <strong>UTF-8</strong> ve geçerli <strong>JSON</strong> olmalıdır.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"sablon-mantigi\">4. Şablon (Twig) Çalışma Mantığı</h2>\r\n<ul>\r\n	<li>Sistem önce <strong>aktif temanın</strong> <code>views/</code> klasörüne bakar.</li>\r\n	<li>İstenen şablon orada yoksa <strong>default</strong> temanın <code>views/</code> klasörüne bakılır.</li>\r\n	<li>Aynı isimli şablon hem sizin temanızda hem default\'ta varsa <strong>sizin temanızdaki</strong> kullanılır.</li>\r\n</ul>\r\n<p>Bu sayede:</p>\r\n<ul>\r\n	<li>Sadece <strong>base.html.twig</strong> ve <strong>index.html.twig</strong> ekleyerek bile yeni bir tema oluşturabilirsiniz; diğer tüm sayfalar default\'tan gelir.</li>\r\n	<li>İstediğiniz sayfayı tek tek kopyalayıp özelleştirebilirsiniz.</li>\r\n</ul>\r\n<p>Şablon dosya isimleri, çekirdeğin kullandığı view isimleriyle aynı olmalıdır (örn. <code>showthread.html.twig</code>, <code>profile.html.twig</code>). Sayfa şablonları listesi Bölüm 6\'da verilmiştir.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"asset-kullanimi\">5. Asset (CSS, JS, Resim) Kullanımı</h2>\r\n<p>Tema içinde CSS, JavaScript veya resim dosyalarınıza Twig\'den şu fonksiyonla link verirsiniz:</p>\r\n<pre><code>{{ theme_asset_url(\'css/theme.css\') }}\r\n{{ theme_asset_url(\'js/theme.js\') }}\r\n{{ theme_asset_url(\'images/logo.png\') }}</code></pre>\r\n<ul>\r\n	<li>URL her zaman <code>/theme-assets/...</code> formatındadır.</li>\r\n	<li>Önce <strong>aktif temanın</strong> <code>assets/</code> klasörüne bakılır.</li>\r\n	<li>Dosya orada yoksa <strong>default</strong> temanın <code>assets/</code> klasöründeki aynı yol kullanılır.</li>\r\n</ul>\r\n<p>Böylece kendi temanızda yalnızca değiştirdiğiniz CSS/JS dosyalarını tutabilir; diğerleri (örn. tailwind.css, theme.js) default\'tan gelir.</p>\r\n<p><strong>Örnek (base.html.twig içinde):</strong></p>\r\n<pre><code>&lt;link rel=\"stylesheet\" href=\"{{ theme_asset_url(\'css/theme.css\') }}?v={{ \'now\'|date(\'U\') }}\"&gt;\r\n&lt;script src=\"{{ theme_asset_url(\'js/theme.js\') }}?v={{ \'now\'|date(\'U\') }}\"&gt;&lt;/script&gt;</code></pre>\r\n\r\n<hr>\r\n\r\n<h2 id=\"sayfa-sablonlari\">6. Sayfa Şablonları Listesi</h2>\r\n<p>Aşağıdaki view isimleri, çekirdeğin kullandığı sayfa şablonlarına karşılık gelir. Şablon dosya adı <code>{view}.html.twig</code> veya alt dizinde <code>alt/view.html.twig</code> şeklindedir. Hepsini override etmek zorunda değilsiniz; sadece değiştirmek istediklerinizi temanıza ekleyin.</p>\r\n<p><strong>Genel ve giriş sayfaları:</strong><br>\r\n<code>404</code>, <code>index</code>, <code>portal</code>, <code>login</code>, <code>register</code>, <code>register_pending</code>, <code>reactivate-account</code>, <code>forgot_password</code>, <code>maintenance</code>, <code>security-check</code></p>\r\n<p><strong>Forum ve konular:</strong><br>\r\n<code>forum_display</code>, <code>topics/create</code>, <code>showthread</code>, <code>topic/private_topic</code>, <code>topic/single_post</code>, <code>topics/edit</code>, <code>topics/move</code>, <code>topics/merge</code>, <code>posts/edit</code>, <code>edit_history</code></p>\r\n<p><strong>Üyeler ve profil:</strong><br>\r\n<code>members/index</code>, <code>profile</code>, <code>member/subscriptions</code>, <code>member/topics</code>, <code>member/posts</code>, <code>member/likes</code>, <code>member/reputation</code>, <code>profile/edit</code>, <code>profile/password</code>, <code>profile/account</code>, <code>profile/preferences</code></p>\r\n<p><strong>Makaleler:</strong><br>\r\n<code>article/index</code>, <code>article/show</code>, <code>article/create</code></p>\r\n<p><strong>Mesajlar ve bildirimler:</strong><br>\r\n<code>conversations/index</code>, <code>conversations/show</code>, <code>conversations/new</code>, <code>notifications/index</code></p>\r\n<p><strong>Diğer:</strong><br>\r\n<code>search</code>, <code>moderation/reports</code>, <code>moderation/approvals</code>, <code>documentation/index</code>, <code>documentation/show</code>, <code>online/index</code>, <code>timeline/index</code>, <code>page</code>, <code>contact/index</code></p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"layout-degiskenleri\">7. Layout\'a Gelen Değişkenler</h2>\r\n<p>Tüm layout sayfalarında (base şablonu kullanan sayfalarda) aşağıdaki değişkenler çekirdek tarafından sağlanır. İçerik şablonuna özel ek değişkenler (ör. konu sayfasında <code>topic</code>, <code>posts</code>) ilgili controller\'dan gelir.</p>\r\n\r\n<h3>Genel</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>pageTitle</code></td><td>Sayfa başlığı.</td></tr>\r\n		<tr><td><code>locale</code></td><td>Aktif dil kodu (örn. tr, en).</td></tr>\r\n		<tr><td><code>user</code></td><td>Giriş yapmış kullanıcı nesnesi veya null.</td></tr>\r\n		<tr><td><code>isStaff</code></td><td>Kullanıcı admin veya moderatör mü (true/false).</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Bildirim ve mesaj</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>unreadNotifications</code></td><td>Okunmamış bildirim sayısı.</td></tr>\r\n		<tr><td><code>unreadMessages</code></td><td>Okunmamış özel mesaj sayısı.</td></tr>\r\n		<tr><td><code>messagesEnabled</code>, <code>notificationsEnabled</code>, <code>notificationToastEnabled</code></td><td>İlgili özelliğin açık olup olmadığı.</td></tr>\r\n		<tr><td><code>staffPendingReports</code>, <code>staffPendingApprovals</code></td><td>Yetkili kullanıcı için bekleyen rapor/onay sayıları.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Site ve SEO</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>site_name</code></td><td>Site adı.</td></tr>\r\n		<tr><td><code>seo_description</code>, <code>seo_keywords</code></td><td>Meta açıklama ve anahtar kelimeler.</td></tr>\r\n		<tr><td><code>canonical_url</code></td><td>Sayfanın canonical URL\'i.</td></tr>\r\n		<tr><td><code>og_title</code>, <code>og_description</code>, <code>og_image</code>, <code>og_type</code></td><td>Open Graph alanları.</td></tr>\r\n		<tr><td><code>schema_json</code></td><td>JSON-LD için ham veri (varsa).</td></tr>\r\n		<tr><td><code>forum_logo_url</code>, <code>forum_favicon_url</code></td><td>Logo ve favicon URL\'leri.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Menüler</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>top_menu_items</code></td><td>Üst menü ağacı: label, href, children (alt menü).</td></tr>\r\n		<tr><td><code>footer_menu_items</code></td><td>Footer menü öğeleri.</td></tr>\r\n		<tr><td><code>footer_quick_links_items</code></td><td>Footer hızlı linkler (icon, label, url, href).</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Özellik bayrakları</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>members_list_enabled</code></td><td>Üye listesi açık mı.</td></tr>\r\n		<tr><td><code>documentation_enabled</code></td><td>Dokümantasyon modülü açık mı.</td></tr>\r\n		<tr><td><code>portal_enabled</code></td><td>Portal/ana sayfa tipi.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>İstatistik ve içerik</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>stats</code></td><td>Genel istatistik nesnesi.</td></tr>\r\n		<tr><td><code>onlineStats</code></td><td>Çevrimiçi istatistik.</td></tr>\r\n		<tr><td><code>online</code></td><td>Çevrimiçi üye listesi.</td></tr>\r\n		<tr><td><code>ads</code></td><td>Reklam alanları (pozisyona göre).</td></tr>\r\n		<tr><td><code>announcements</code></td><td>Aktif duyurular.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Hero alanı</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>hero_visible</code></td><td>Hero bölümü gösterilsin mi.</td></tr>\r\n		<tr><td><code>hero_title</code>, <code>hero_description</code></td><td>Hero başlık ve açıklama.</td></tr>\r\n		<tr><td><code>hero_f1_icon</code>, <code>hero_f1_title</code>, <code>hero_f1_desc</code> (f2, f3, f4 için de aynı)</td><td>Hero özellik kutuları.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Sidebar ve hook\'lar</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>withSidebar</code></td><td>Sayfada sidebar kullanılsın mı.</td></tr>\r\n		<tr><td><code>topTags</code></td><td>Sidebar için popüler etiketler (withSidebar true ise).</td></tr>\r\n		<tr><td><code>sidebar_blocks</code></td><td>Eklenti/hook ile eklenen sidebar HTML\'i.</td></tr>\r\n		<tr><td><code>header_extra</code></td><td>Eklenti/hook ile head sonuna eklenen HTML.</td></tr>\r\n		<tr><td><code>footer_extra</code></td><td>Eklenti/hook ile body sonuna eklenen HTML.</td></tr>\r\n		<tr><td><code>modal_forums</code></td><td>\"Yeni konu\" modal\'ı için forum listesi.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Tema ve script ayarları</h3>\r\n<table>\r\n	<thead><tr><th>Değişken</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>theme_primary_color</code></td><td>Tema ana rengi (ayarlardan).</td></tr>\r\n		<tr><td><code>custom_css</code>, <code>custom_js</code></td><td>Sistem ayarlarından gelen ek CSS/JS.</td></tr>\r\n		<tr><td><code>jquery_enabled</code>, <code>ajax_enabled</code></td><td>jQuery ve AJAX kullanımı açık mı.</td></tr>\r\n	</tbody>\r\n</table>\r\n<p>Şablonlarda bu değişkenlere doğrudan erişirsiniz (örn. <code>{{ pageTitle }}</code>, <code>{{ user.username }}</code>). Tanımsız olabilecekler için <code>|default(...)</code> kullanabilirsiniz: <code>{{ forum_logo_url|default(\'\') }}</code>.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"twig-fonksiyonlari\">8. Twig Fonksiyonları</h2>\r\n<p>Tema şablonları içinde aşağıdaki fonksiyonlar kullanılabilir.</p>\r\n\r\n<h3>URL\'ler</h3>\r\n<table>\r\n	<thead><tr><th>Fonksiyon</th><th>Kullanım</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>core_url(path)</code></td><td><code>core_url(\'forum\')</code></td><td>Site içi URL üretir.</td></tr>\r\n		<tr><td><code>base_url(path)</code></td><td><code>base_url(\'theme-assets/css/theme.css\')</code></td><td>Base path ile URL.</td></tr>\r\n		<tr><td><code>full_site_url(...)</code></td><td>—</td><td>Tam site URL\'i (parametreli).</td></tr>\r\n		<tr><td><code>theme_asset_url(path)</code></td><td><code>theme_asset_url(\'css/theme.css\')</code></td><td>Tema asset URL\'i (aktif tema, yoksa default).</td></tr>\r\n		<tr><td><code>asset_url(path)</code></td><td><code>asset_url(user.avatar_path)</code></td><td>Yüklenen dosya (avatar, kapak vb.) URL\'i.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Konu, mesaj, üye URL\'leri</h3>\r\n<table>\r\n	<thead><tr><th>Fonksiyon</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>topic_url_path(topic)</code></td><td>Konu için URL path.</td></tr>\r\n		<tr><td><code>topic_url_path_by_id(id)</code></td><td>Konu id ile URL path.</td></tr>\r\n		<tr><td><code>topic_url(topic)</code></td><td>Konu tam URL\'i.</td></tr>\r\n		<tr><td><code>post_url_path(...)</code>, <code>post_url_path_by_id(...)</code></td><td>Mesaj URL path.</td></tr>\r\n		<tr><td><code>conversation_url_path(...)</code>, <code>conversation_url_path_by_id(...)</code></td><td>Özel mesaj konuşması.</td></tr>\r\n		<tr><td><code>notification_url_path(...)</code></td><td>Bildirim.</td></tr>\r\n		<tr><td><code>member_url_path(user)</code></td><td>Üye profil URL path.</td></tr>\r\n		<tr><td><code>article_url_path_by_id(id)</code></td><td>Makale URL path.</td></tr>\r\n		<tr><td><code>attachment_url_path(...)</code></td><td>Ek dosya URL path.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Çeviri ve metin</h3>\r\n<table>\r\n	<thead><tr><th>Fonksiyon</th><th>Kullanım</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>core__(key)</code></td><td><code>core__(\'common.home\')</code></td><td>Çekirdek dil anahtarı.</td></tr>\r\n		<tr><td><code>admin__(key)</code></td><td><code>admin__(\'footer.copyright\')</code></td><td>Admin panel çevirisi.</td></tr>\r\n		<tr><td><code>lang(key, params)</code></td><td><code>lang(\'topic.title\')</code></td><td>Uygulama dil dosyasından çeviri.</td></tr>\r\n		<tr><td><code>core_e(s)</code></td><td><code>core_e(pageTitle)</code></td><td>Metni HTML için escape eder.</td></tr>\r\n		<tr><td><code>avatar_display_name(username)</code></td><td>—</td><td>Avatar/gösterim için isim.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Güvenlik ve form</h3>\r\n<table>\r\n	<thead><tr><th>Fonksiyon</th><th>Kullanım</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>core_csrf_token(name)</code></td><td><code>core_csrf_token(\'login\')</code></td><td>CSRF token değeri.</td></tr>\r\n		<tr><td><code>core_csrf_field(name)</code></td><td><code>core_csrf_field(\'login\')</code></td><td>input type=\"hidden\" alanı.</td></tr>\r\n		<tr><td><code>core_redirect_url_safe(...)</code></td><td>—</td><td>Güvenli yönlendirme URL\'i.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>İçerik işleme</h3>\r\n<table>\r\n	<thead><tr><th>Fonksiyon</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>core_sanitize_html(s)</code></td><td>HTML güvenli hale getirir.</td></tr>\r\n		<tr><td><code>core_quote_bb_to_html(s)</code></td><td>BB kodunu HTML\'e çevirir.</td></tr>\r\n		<tr><td><code>core_process_mentions(s)</code></td><td>Mention\'ları işler.</td></tr>\r\n		<tr><td><code>core_process_post_refs(s, topicId)</code></td><td>Mesaj referanslarını işler.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<h3>Diğer</h3>\r\n<table>\r\n	<thead><tr><th>Fonksiyon</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>core_config(key, default)</code></td><td>Config değeri okur.</td></tr>\r\n		<tr><td><code>env(key, default)</code></td><td>Ortam değişkeni.</td></tr>\r\n		<tr><td><code>hook(name, payload)</code></td><td>Şablonlardan event tetikler (eklenti dinleyebilir).</td></tr>\r\n		<tr><td><code>flash(key)</code></td><td>Flash mesaj (örn. başarı/hata).</td></tr>\r\n		<tr><td><code>attachment_icon(mimeType, originalName)</code></td><td>Ek dosya ikon sınıfı (Font Awesome).</td></tr>\r\n		<tr><td><code>attachment_format_size(bytes)</code></td><td>Dosya boyutunu \"1.5 MB\" gibi metne çevirir.</td></tr>\r\n	</tbody>\r\n</table>\r\n<p><strong>Örnek kullanımlar:</strong></p>\r\n<pre><code>&lt;title&gt;{{ core_e(pageTitle) }} - {{ core_e(site_name) }}&lt;/title&gt;\r\n&lt;a href=\"{{ core_url(\'forum\') }}\"&gt;{{ core__(\'common.forum\') }}&lt;/a&gt;\r\n&lt;img src=\"{{ user.avatar_path ? asset_url(user.avatar_path) : \'https://ui-avatars.com/api/?name=\' ~ avatar_display_name(user.username)|url_encode }}\"&gt;\r\n&lt;form&gt; {{ core_csrf_field(\'login\') }} ... &lt;/form&gt;</code></pre>\r\n\r\n<hr>\r\n\r\n<h2 id=\"twig-filtreleri\">9. Twig Filtreleri</h2>\r\n<table>\r\n	<thead><tr><th>Filtre</th><th>Kullanım</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>time_ago</code></td><td><code>{{ post.created_at|time_ago }}</code></td><td>\"Az önce\", \"5 dakika önce\" vb.</td></tr>\r\n		<tr><td><code>schema_ld_json</code></td><td><code>{{ schema_json|schema_ld_json }}</code></td><td>JSON-LD için güvenli çıktı.</td></tr>\r\n		<tr><td><code>clamp</code></td><td><code>{{ value|clamp(0, 100) }}</code></td><td>Sayıyı min–max aralığına sınırlar.</td></tr>\r\n		<tr><td><code>url_encode</code></td><td><code>{{ name|url_encode }}</code></td><td>URL için encode.</td></tr>\r\n		<tr><td><code>json_decode_array</code></td><td><code>{{ jsonString|json_decode_array }}</code></td><td>JSON string\'i diziye çevirir.</td></tr>\r\n		<tr><td><code>filter_visible_columns</code></td><td>—</td><td>Sütun görünürlük filtreleri (tablolarda).</td></tr>\r\n		<tr><td><code>smileys</code></td><td><code>{{ text|smileys }}</code></td><td>Smiley metnini işler.</td></tr>\r\n		<tr><td><code>rtrim</code></td><td><code>{{ s|rtrim(\'/\') }}</code></td><td>Sağdan karakter siler.</td></tr>\r\n	</tbody>\r\n</table>\r\n<p>Twig\'in standart filtreleri (<code>default</code>, <code>length</code>, <code>upper</code>, <code>lower</code>, <code>join</code>, <code>slice</code>, <code>date</code> vb.) de kullanılabilir.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"base-sablon\">10. Base Şablon ve Bloklar</h2>\r\n<p>Tüm sayfa şablonları (index, showthread, profile vb.) <strong>base.html.twig</strong>\'i extend eder ve <code>content</code> blokunu doldurur. Tema geliştirirken base\'i override ederek tüm sayfalarda ortak header, footer ve yapıyı kontrol edersiniz.</p>\r\n<p><strong>Override edebileceğiniz bloklar:</strong></p>\r\n<table>\r\n	<thead><tr><th>Blok</th><th>Yer</th><th>Açıklama</th></tr></thead>\r\n	<tbody>\r\n		<tr><td><code>head_extra</code></td><td>&lt;head&gt; içi</td><td>Ek CSS, meta etiketleri, script.</td></tr>\r\n		<tr><td><code>body_class</code></td><td>&lt;body&gt;</td><td>Body\'ye verilecek ek CSS sınıfları.</td></tr>\r\n		<tr><td><code>content</code></td><td>Ana alan</td><td>İçerik şablonları burayı doldurur.</td></tr>\r\n	</tbody>\r\n</table>\r\n<p><strong>Değişkenler (hook çıktıları):</strong></p>\r\n<ul>\r\n	<li><code>header_extra</code>: Head bölümünün sonuna eklenecek HTML (örn. eklenti script\'leri).</li>\r\n	<li><code>footer_extra</code>: Sayfa sonuna, &lt;/body&gt; öncesi eklenecek HTML.</li>\r\n</ul>\r\n<p>Bunlar base şablonunda zaten kullanılıyorsa, kendi base override\'ınızda aynı yerlere yazmak yeterlidir:</p>\r\n<pre><code>{% if header_extra is defined and header_extra is not empty %}{{ header_extra|raw }}{% endif %}\r\n...\r\n{% if footer_extra is defined and footer_extra is not empty %}{{ footer_extra|raw }}{% endif %}</code></pre>\r\n<p><strong>Örnek içerik şablonu (index.html.twig):</strong></p>\r\n<pre><code>{% extends \'base.html.twig\' %}\r\n{% block content %}\r\n  &lt;div class=\"container\"&gt;\r\n    &lt;h1&gt;{{ core_e(site_name) }}&lt;/h1&gt;\r\n    ...\r\n  &lt;/div&gt;\r\n{% endblock %}</code></pre>\r\n\r\n<hr>\r\n\r\n<h2 id=\"yeni-tema-adimlari\">11. Yeni Tema Oluşturma Adımları</h2>\r\n<ol>\r\n	<li><strong>Klasör oluşturun</strong> — <code>templates/frontend/benim-temam/</code> (slug\'ı kendi adınızla değiştirin).</li>\r\n	<li><strong>theme.json ekleyin</strong> — En az <code>name</code> alanı olacak şekilde tema adı, sürüm, yazar, açıklama yazın.</li>\r\n	<li><strong>views/ klasörü oluşturun</strong> — İçine en azından override etmek istediğiniz şablonları koyun. Yeni başlıyorsanız <code>base.html.twig</code> ve <code>index.html.twig</code> ile başlamak yeterlidir; diğer sayfalar default\'tan gelir.</li>\r\n	<li><strong>base.html.twig</strong> — Default temadaki base.html.twig\'i kopyalayıp kendi tasarımınıza göre düzenleyin. <code>{% block content %}</code>, <code>header_extra</code>, <code>footer_extra</code> ve menü/istatistik değişkenlerini kullanın.</li>\r\n	<li><strong>Asset\'ler (isteğe bağlı)</strong> — Sadece değiştirdiğiniz CSS/JS dosyalarını <code>assets/css/</code>, <code>assets/js/</code> altına koyun. Diğerleri default\'tan yüklenecektir. Şablonlarda <code>theme_asset_url(\'css/theme.css\')</code> gibi kullanın.</li>\r\n	<li><strong>Önizleme (isteğe bağlı)</strong> — <code>screenshot.png</code> veya <code>screenshot.jpg</code> ekleyerek tema listesinde önizleme gösterebilirsiniz.</li>\r\n	<li><strong>Temayı etkinleştirin</strong> — Yönetim paneli → <strong>İçerik → Tema Yönetimi</strong> → Ön yüz temalarından <strong>Aktifleştir</strong> ile seçin.</li>\r\n</ol>\r\n<p>Tema listesinde görünmesi için theme.json\'ın geçerli ve tema klasörünün <code>templates/frontend/</code> altında olması yeterlidir.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"admin-tema\">12. Admin Paneli Teması</h2>\r\n<p>Admin temaları <code>templates/admin/{slug}/</code> altında, aynı mantıkla (views + theme.json) tanımlanır. Şablon arama sırası yine önce aktif admin teması, sonra default\'tır.</p>\r\n<ul>\r\n	<li>Admin paneli görünümü büyük ölçüde <strong>Tabler</strong> tabanlıdır; özel admin temasında sadece şablonları override edebilirsiniz.</li>\r\n	<li>Admin tarafı için ayrı bir <strong>theme asset</strong> route\'u (örn. /admin-theme-assets/...) yoktur. Özel admin CSS/JS eklemek için <strong>Sistem Ayarları</strong> içindeki <strong>Özel CSS</strong> ve <strong>Özel JS</strong> alanlarını kullanabilirsiniz; bu alanlar tüm admin sayfalarına enjekte edilir.</li>\r\n</ul>\r\n\r\n<hr>\r\n\r\n<h2 id=\"sss\">13. Sık Sorulan Sorular</h2>\r\n<p><strong>S: Tüm şablonları kopyalamak zorunda mıyım?</strong><br>\r\nHayır. Sadece değiştirmek istediğiniz şablonları kendi tema klasörünüze kopyalayıp düzenlemeniz yeterli. Diğer sayfalar default temadan kullanılır.</p>\r\n<p><strong>S: CSS/JS dosyalarımın hepsini tema klasörüne koymalı mıyım?</strong><br>\r\nHayır. Sadece eklediğiniz veya değiştirdiğiniz dosyaları <code>assets/</code> altına koyun. <code>theme_asset_url()</code> önce sizin temanıza, bulamazsa default temaya bakar.</p>\r\n<p><strong>S: Tema listesinde görünmüyor.</strong><br>\r\n<code>theme.json</code> dosyasının tema klasörünün içinde, geçerli JSON ve en az <code>\"name\"</code> alanıyla olduğundan emin olun.</p>\r\n<p><strong>S: Bir sayfa boş veya hatalı görünüyor.</strong><br>\r\nO sayfa için kullandığınız şablon dosya adının çekirdeğin beklediği view adıyla aynı olduğunu kontrol edin (Bölüm 6\'daki listeye bakın). Eksik veya yanlış değişken kullanımı için şablonu inceleyin; tanımsız değişkenlerde <code>|default(...)</code> kullanın.</p>\r\n<p><strong>S: Çevirileri nasıl kullanırım?</strong><br>\r\n<code>core__(\'anahtar\')</code> çekirdek dil dosyalarından, <code>lang(\'anahtar\')</code> uygulama dil dosyalarından çeviri döndürür. Parametreli kullanım için <code>lang(\'anahtar\', { \'param\': deger })</code> kullanılabilir.</p>\r\n<p><strong>S: Eklenti view\'larına nasıl referans verilir?</strong><br>\r\nEklenti şablonları <code>@EklentiAdi/view.html.twig</code> şeklinde namespace ile yüklenir. Tema içinden <code>{% include \'@EklentiAdi/partial.html.twig\' %}</code> gibi kullanabilirsiniz (eklenti buna izin veriyorsa).</p>\r\n\r\n<hr>\r\n\r\n<p>Bu kılavuz MegaforBB tema sisteminin güncel sürümüne göre hazırlanmıştır. Güncellemeler için resmi dokümantasyonu ve forum duyurularını takip edebilirsiniz.</p>\r\n\r\n</div>\r\n\r\n\r\n', 10, '2026-03-04 21:19:59', '2026-03-04 21:29:23');
INSERT INTO `doc_pages` (`id`, `section_id`, `title`, `slug`, `content`, `sort_order`, `created_at`, `updated_at`) VALUES
(13, 7, 'MegaforBB Eklenti geliştirme kılavuzu', 'megaforbb-eklenti-geli-tirme-k-lavuzu', '<div class=\"megaforbb-doc\"><p>Bu kılavuz, MegaforBB forum yazılımında <strong>çekirdek dosyalara hiç dokunmadan</strong> eklenti geliştirmek için kullanılan <strong>event</strong>, <strong>action</strong> ve <strong>filter</strong> sistemini, dizin yapısını, tüm kanca noktalarını ve adım adım örnekleri en ince ayrıntısına kadar anlatır.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"icindekiler\">İçindekiler</h2>\r\n<div class=\"toc\">\r\n<ol>\r\n	<li><a href=\"#eklenti-sistemine-giris\">Eklenti Sistemine Giriş</a></li>\r\n	<li><a href=\"#event-ve-hook-kavramlari\">Event ve Hook Kavramları</a></li>\r\n	<li><a href=\"#eklenti-dizin-yapisi\">Eklenti Dizin Yapısı</a></li>\r\n	<li><a href=\"#pluginjson\">plugin.json – Eklenti Tanımı</a></li>\r\n	<li><a href=\"#pluginphp\">plugin.php – Ana Yapılandırma</a></li>\r\n	<li><a href=\"#cekirdek-event-listesi\">Çekirdek Event Listesi</a></li>\r\n	<li><a href=\"#cekirdek-action-hooklari\">Çekirdek Action Hook\'ları</a></li>\r\n	<li><a href=\"#cekirdek-filter-hooklari\">Çekirdek Filter Hook\'ları</a></li>\r\n	<li><a href=\"#adminmenu-yapisi\">admin.menu Yapısı</a></li>\r\n	<li><a href=\"#routesphp\">routes.php – Eklenti Rotaları</a></li>\r\n	<li><a href=\"#views-eklenti-sablonlari\">views/ – Eklenti Şablonları</a></li>\r\n	<li><a href=\"#install-uninstall\">install.php ve uninstall.php</a></li>\r\n	<li><a href=\"#listener-hook-yazimi\">Listener ve Hook Sınıfları Yazımı</a></li>\r\n	<li><a href=\"#boot-sirasi\">Boot Sırası ve Yükleme</a></li>\r\n	<li><a href=\"#psr4-composer\">PSR-4 ve Composer Autoload</a></li>\r\n	<li><a href=\"#yeni-eklenti-adimlari\">Yeni Eklenti Oluşturma Adımları</a></li>\r\n	<li><a href=\"#twig-hook-notu\">Twig hook() Fonksiyonu Hakkında</a></li>\r\n	<li><a href=\"#admin-panel-eklentiler\">Admin Panel – Eklentiler Sayfası</a></li>\r\n	<li><a href=\"#sss\">Sık Sorulan Sorular</a></li>\r\n</ol>\r\n</div>\r\n\r\n<hr>\r\n\r\n<h2 id=\"eklenti-sistemine-giris\">1. Eklenti Sistemine Giriş</h2>\r\n<p>MegaforBB\'de eklentiler, <strong>çekirdek koda dokunmadan</strong> yeni işlevler eklemenizi sağlar. Her eklenti <code>plugins/</code> dizini altında kendi klasöründe yaşar; zorunlu tek dosya <strong>plugin.php</strong>\'dir. Çekirdek, başlangıçta tüm etkin eklentilerin <code>plugin.php</code> dosyalarını tarar ve buradan event listener\'ları ile action/filter kancalarını yükler.</p>\r\n<ul>\r\n	<li><strong>Dizin = eklenti:</strong> <code>plugins/{EklentiAdi}/</code> içinde <code>plugin.php</code> bulunan her klasör bir eklentidir (örn. <code>plugins/Example/</code>, <code>plugins/Commerce/</code>).</li>\r\n	<li><strong>Etkinleştirme:</strong> Admin panel → <strong>İçerik → Eklentiler</strong> sayfasından eklentiyi <strong>Etkinleştir</strong> veya <strong>Devre dışı bırak</strong> ile açıp kapatırsınız. Devre dışı eklentilerin listener ve hook\'ları hiç yüklenmez.</li>\r\n	<li><strong>Yeni eklenti:</strong> <code>plugins/YeniEklentiAdi/</code> klasörü oluşturup içine <code>plugin.php</code> koymanız yeterlidir; admin panelde otomatik listelenir.</li>\r\n</ul>\r\n<p>Çekirdekte <code>config/events.php</code> sadece çekirdek listener\'ları içerir; eklenti listener\'ları bu dosyaya eklenmez. Eklentiler yalnızca kendi dizinlerine dosya ekleyerek sistemi genişletir.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"event-ve-hook-kavramlari\">2. Event ve Hook Kavramları</h2>\r\n<p>Sistemde iki genişleme mekanizması vardır:</p>\r\n<table>\r\n	<thead>\r\n		<tr>\r\n			<th>Mekanizma</th>\r\n			<th>Amaç</th>\r\n			<th>Çekirdekte nasıl tetiklenir</th>\r\n			<th>Eklentide nasıl kullanılır</th>\r\n		</tr>\r\n	</thead>\r\n	<tbody>\r\n		<tr>\r\n			<td><strong>Events</strong> (Symfony EventDispatcher)</td>\r\n			<td>Olay sonrası işlem (log, bildirim, indeksleme)</td>\r\n			<td>Controller\'larda <code>$this-&gt;app-&gt;event()-&gt;dispatch(...)</code></td>\r\n			<td><code>plugin.php</code> → <code>events</code> → event adı → listener sınıfı</td>\r\n		</tr>\r\n		<tr>\r\n			<td><strong>Actions</strong> (Hook)</td>\r\n			<td>HTML enjeksiyonu veya yan etki</td>\r\n			<td><code>$app-&gt;hooks()-&gt;doAction(\'hook_adi\', ...)</code></td>\r\n			<td><code>plugin.php</code> → <code>actions</code> → hook adı → callable listesi</td>\r\n		</tr>\r\n		<tr>\r\n			<td><strong>Filters</strong> (Hook)</td>\r\n			<td>Veri dönüşümü (menü, view verisi)</td>\r\n			<td><code>$app-&gt;hooks()-&gt;applyFilters(\'hook_adi\', $deger, ...)</code></td>\r\n			<td><code>plugin.php</code> → <code>filters</code> → hook adı → callable listesi</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n<ul>\r\n	<li><strong>Event:</strong> \"Konu oluşturuldu\", \"Kullanıcı giriş yaptı\" gibi bir olay gerçekleştiğinde dinleyiciler (listener) çalışır. Çekirdek sadece <code>dispatch()</code> çağırır; eklentiler kendi listener\'larını <code>plugin.php</code> ile ekler.</li>\r\n	<li><strong>Action:</strong> Belirli bir noktada (örn. sidebar, header) tüm kayıtlı callable\'lar sırayla çalışır; dönen <strong>string</strong> değerler birleştirilir (HTML ekleme). Yan etki için de kullanılabilir.</li>\r\n	<li><strong>Filter:</strong> Bir değer (örn. menü dizisi, view verisi) zincirleme callable\'lardan geçer; her biri değeri dönüştürür, son değer kullanılır.</li>\r\n</ul>\r\n<p><strong>Önemli:</strong> Menü veya veri dönüşümü yapıyorsanız <strong>filter</strong> kullanın; HTML ekleme veya \"bir şey yap\" tarzı yan etki için <strong>action</strong> kullanın. Çekirdekte <code>admin.menu</code> bir <strong>filter</strong> ile tetiklendiği için eklentinizde <code>admin.menu</code>\'yu <strong>filters</strong> altında tanımlamalısınız.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"eklenti-dizin-yapisi\">3. Eklenti Dizin Yapısı</h2>\r\n<p>Örnek bir eklenti klasörü:</p>\r\n<pre><code>plugins/BenimEklentim/\r\n├── plugin.php          # Zorunlu — event, action, filter tanımları\r\n├── plugin.json         # İsteğe bağlı — ad, sürüm, açıklama (admin listesinde)\r\n├── routes.php          # İsteğe bağlı — kendi URL\'leriniz\r\n├── install.php         # İsteğe bağlı — ilk etkinleştirmede bir kez çalışır\r\n├── uninstall.php       # İsteğe bağlı — Kaldır tıklandığında çalışır\r\n├── views/              # İsteğe bağlı — Twig şablonları (@BenimEklentim/...)\r\n│   └── ayarlar.html.twig\r\n├── Listeners/          # İsteğe bağlı — event listener sınıfları\r\n│   └── KonuOlusturulduListener.php\r\n├── Hooks/              # İsteğe bağlı — action/filter callable sınıfları\r\n│   └── AdminMenu.php\r\n└── Controllers/        # İsteğe bağlı — routes.php ile kullanılan controller\'lar\r\n    └── AyarlarController.php</code></pre>\r\n<ul>\r\n	<li><code>plugin.php</code> olmadan klasör eklenti sayılmaz.</li>\r\n	<li>Klasör adı (örn. <code>BenimEklentim</code>) PHP namespace\'inde <code>Plugins\\BenimEklentim</code> olarak kullanılır; boşluk veya özel karakter kullanmayın.</li>\r\n</ul>\r\n\r\n<hr>\r\n\r\n<h2 id=\"pluginjson\">4. plugin.json – Eklenti Tanımı</h2>\r\n<p>Admin panelde eklenti listesinde görünen adı, sürümü ve açıklamayı bu dosya ile verirsiniz. Yoksa listede yalnızca <strong>klasör adı</strong> kullanılır.</p>\r\n<p><strong>Konum:</strong> <code>plugins/{EklentiAdi}/plugin.json</code></p>\r\n<p><strong>Örnek:</strong></p>\r\n<pre><code>{\r\n    \"name\": \"Örnek Eklenti\",\r\n    \"version\": \"1.0.0\",\r\n    \"description\": \"Konu oluşturulduğunda loglama yapan demo eklenti.\",\r\n    \"author\": \"Adınız\"\r\n}</code></pre>\r\n<table>\r\n	<thead>\r\n		<tr><th>Alan</th><th>Zorunlu</th><th>Açıklama</th></tr>\r\n	</thead>\r\n	<tbody>\r\n		<tr><td><strong>name</strong></td><td>Hayır</td><td>Görünen ad (admin listesinde). Yoksa klasör adı kullanılır.</td></tr>\r\n		<tr><td><strong>version</strong></td><td>Hayır</td><td>Sürüm metni (örn. 1.0.0).</td></tr>\r\n		<tr><td><strong>description</strong></td><td>Hayır</td><td>Kısa açıklama; liste alt satırında gösterilir.</td></tr>\r\n		<tr><td><strong>author</strong></td><td>Hayır</td><td>Yazar adı.</td></tr>\r\n	</tbody>\r\n</table>\r\n\r\n<hr>\r\n\r\n<h2 id=\"pluginphp\">5. plugin.php – Ana Yapılandırma</h2>\r\n<p>Bu dosya eklentinin davranışını tanımlar: hangi event\'lere listener, hangi action/filter\'lara callable bağlanacağı.</p>\r\n<p><strong>Konum:</strong> <code>plugins/{EklentiAdi}/plugin.php</code></p>\r\n<p><strong>Dönüş:</strong> PHP dizisi. Aşağıdaki anahtarlar desteklenir.</p>\r\n\r\n<h3>5.1 Yeni format (önerilen)</h3>\r\n<pre><code>&lt;?php\r\n\r\nreturn [\r\n    \'events\' =&gt; [\r\n        \'topic.created\' =&gt; [\r\n            [\\Plugins\\Example\\Listeners\\LogTopicCreated::class, \'handle\'],\r\n        ],\r\n        \'user.login\' =&gt; [\r\n            [\\Plugins\\Example\\Listeners\\LogUserLogin::class, \'handle\'],\r\n        ],\r\n    ],\r\n    \'actions\' =&gt; [\r\n        \'layout.sidebar_blocks\' =&gt; [\r\n            [\\Plugins\\Example\\Hooks\\SidebarBlock::class, \'render\'],\r\n        ],\r\n        \'layout.footer_extra\' =&gt; [\r\n            [\\Plugins\\Example\\Hooks\\FooterScript::class, \'inject\'],\r\n        ],\r\n    ],\r\n    \'filters\' =&gt; [\r\n        \'admin.menu\' =&gt; [\r\n            [\\Plugins\\Example\\Hooks\\AdminMenu::class, \'addItem\'],\r\n        ],\r\n        \'layout.view_data\' =&gt; [\r\n            [\\Plugins\\Example\\Hooks\\LayoutData::class, \'addData\'],\r\n        ],\r\n    ],\r\n];</code></pre>\r\n<ul>\r\n	<li><strong>events:</strong> Event adı (string) =&gt; listener listesi. Her listener <code>[SınıfAdı::class, \'metodAdi\']</code> veya <code>[SınıfAdı::class, \'metodAdi\', öncelik]</code> formatında. Öncelik sayısal (varsayılan 10); düşük önce çalışır.</li>\r\n	<li><strong>actions:</strong> Hook adı =&gt; callable listesi. Action tetiklendiğinde her callable çağrılır; <strong>string</strong> döndürenler birleştirilir (HTML için).</li>\r\n	<li><strong>filters:</strong> Hook adı =&gt; callable listesi. Filter\'da değer zincirleme dönüştürülür: <code>$value = callable($value, ...$args)</code>.</li>\r\n</ul>\r\n\r\n<h3>5.2 Öncelik (priority)</h3>\r\n<p>Hem event listener listesinde hem de action/filter listesinde üçüncü eleman olarak öncelik verilebilir (sayı). Varsayılan 10\'dur; düşük sayı önce çalışır.</p>\r\n<pre><code>\'actions\' =&gt; [\r\n    \'layout.sidebar_blocks\' =&gt; [\r\n        [\\Plugins\\Example\\Hooks\\EarlyBlock::class, \'render\', 5],   // Önce\r\n        [\\Plugins\\Example\\Hooks\\LateBlock::class, \'render\', 20],   // Sonra\r\n    ],\r\n],</code></pre>\r\n\r\n<h3>5.3 Eski format (sadece event\'ler)</h3>\r\n<p>Sadece event kullanıyorsanız, tüm dizi event olarak yorumlanabilir (geriye dönük uyumluluk):</p>\r\n<pre><code>&lt;?php\r\n\r\nreturn [\r\n    \\Forecor\\core\\Events::TOPIC_CREATED =&gt; [\r\n        [\\Plugins\\Example\\Listeners\\LogTopicCreated::class, \'handle\'],\r\n    ],\r\n];</code></pre>\r\n<p>Yeni eklentilerde <strong>yeni format</strong> (<code>events</code>, <code>actions</code>, <code>filters</code> anahtarları) kullanmanız önerilir.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"cekirdek-event-listesi\">6. Çekirdek Event Listesi</h2>\r\n<p>Aşağıdaki event\'ler çekirdekte <strong>gerçekten</strong> <code>dispatch()</code> ile tetiklenir. Eklentinizde <code>plugin.php</code> → <code>events</code> ile listener ekleyebilirsiniz.</p>\r\n<table>\r\n	<thead>\r\n		<tr>\r\n			<th>Event adı (sabit)</th>\r\n			<th>Tetiklenen yer</th>\r\n			<th>Payload (event sınıfı)</th>\r\n		</tr>\r\n	</thead>\r\n	<tbody>\r\n		<tr><td><code>topic.created</code> (Events::TOPIC_CREATED)</td><td>ForumController::create(), cron (zamanlanmış konu)</td><td>TopicCreated (topic, data)</td></tr>\r\n		<tr><td><code>topic.deleted</code> (Events::TOPIC_DELETED)</td><td>TopicController::delete()</td><td>TopicDeleted (topic, deletedByUserId)</td></tr>\r\n		<tr><td><code>post.created</code> (Events::POST_CREATED)</td><td>TopicController (cevap gönderimi)</td><td>PostCreated (post)</td></tr>\r\n		<tr><td><code>post.deleted</code> (Events::POST_DELETED)</td><td>TopicController::postsBulk() (action=delete)</td><td>PostDeleted (post, deletedByUserId)</td></tr>\r\n		<tr><td><code>user.registered</code> (Events::USER_REGISTERED)</td><td>AuthController::register()</td><td>UserRegistered (user)</td></tr>\r\n		<tr><td><code>user.login</code> (Events::USER_LOGIN)</td><td>AuthController::login()</td><td>UserLogin (user)</td></tr>\r\n		<tr><td>TopicEdited::NAME</td><td>TopicController (konu düzenleme)</td><td>TopicEdited (topic, user, body, title)</td></tr>\r\n		<tr><td>PostLiked::NAME</td><td>TopicController (beğeni)</td><td>PostLiked (post, user)</td></tr>\r\n		<tr><td>PostReported::NAME</td><td>TopicController (mesaj şikayet)</td><td>PostReported (post, user)</td></tr>\r\n		<tr><td>ReputationGiven::NAME</td><td>MemberController (rep verme)</td><td>ReputationGiven (...)</td></tr>\r\n	</tbody>\r\n</table>\r\n<p>Event sınıfları <code>app/Events/*.php</code> ve <code>Forecor\\core\\Events</code> sabitleri <code>forecor/core/Events.php</code> dosyasında tanımlıdır. Listener sınıfınızda payload tipine göre type-hint kullanın:</p>\r\n<pre><code>use App\\Events\\TopicCreated;\r\n\r\npublic function handle(TopicCreated $event): void\r\n{\r\n    $topic = $event-&gt;topic;\r\n    // ...\r\n}</code></pre>\r\n\r\n<hr>\r\n\r\n<h2 id=\"cekirdek-action-hooklari\">7. Çekirdek Action Hook\'ları</h2>\r\n<p>Action\'lar belirli noktalarda tetiklenir; kayıtlı callable\'lar sırayla çalışır, <strong>string</strong> döndürenler birleştirilir (genelde HTML).</p>\r\n<table>\r\n	<thead>\r\n		<tr><th>Hook adı</th><th>Tetiklenen dosya</th><th>Argümanlar</th><th>Açıklama</th></tr>\r\n	</thead>\r\n	<tbody>\r\n		<tr><td>layout.sidebar_blocks</td><td>BaseController</td><td>$app</td><td>Ön yüz layout sidebar; widget alanı. Dönen string\'ler birleştirilir.</td></tr>\r\n		<tr><td>layout.header_extra</td><td>BaseController</td><td>$app</td><td>&lt;head&gt; içine ek HTML (analytics, meta, script).</td></tr>\r\n		<tr><td>layout.footer_extra</td><td>BaseController</td><td>$app</td><td>&lt;/body&gt; öncesi ek HTML/script.</td></tr>\r\n		<tr><td>before_topic_create</td><td>ForumController</td><td>$forum, $user</td><td>Konu oluşturulmadan hemen önce (transaction içinde).</td></tr>\r\n		<tr><td>after_topic_create</td><td>ForumController, cron</td><td>$topic, $forum</td><td>Konu oluşturulduktan / zamanlanmış konu yayınlandıktan sonra.</td></tr>\r\n		<tr><td>before_post_create</td><td>TopicController</td><td>$topic, $user</td><td>Cevap gönderilmeden hemen önce.</td></tr>\r\n		<tr><td>after_post_create</td><td>TopicController</td><td>$post, $topic, $user</td><td>Cevap kaydedildikten ve post.created event\'i tetiklendikten sonra.</td></tr>\r\n		<tr><td>admin.forum.form_extra</td><td>AdminForumController</td><td>null veya $forum</td><td>Forum ekleme/düzenleme formuna ek HTML.</td></tr>\r\n		<tr><td>admin.forum.saved</td><td>AdminForumController</td><td>$forum</td><td>Forum kaydedildikten (create/update) sonra.</td></tr>\r\n	</tbody>\r\n</table>\r\n<p><strong>Callable imzası (action):</strong> <code>function($app) { return \'&lt;div&gt;...&lt;/div&gt;\'; }</code> veya hook\'a göre ek argümanlarla. Dönüş tipi string (HTML veya boş).</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"cekirdek-filter-hooklari\">8. Çekirdek Filter Hook\'ları</h2>\r\n<p>Filter\'lar bir değeri dönüştürür; çekirdek <code>applyFilters(\'hook_adi\', $deger, ...ekArgumanlar)</code> çağırır.</p>\r\n<table>\r\n	<thead>\r\n		<tr><th>Hook adı</th><th>Tetiklenen dosya</th><th>Argümanlar</th><th>Açıklama</th></tr>\r\n	</thead>\r\n	<tbody>\r\n		<tr><td>admin.menu</td><td>AdminController</td><td>$navGroups</td><td>Admin sol menü dizisi; öğe ekleme/çıkarma/sıralama.</td></tr>\r\n		<tr><td>layout.view_data</td><td>BaseController</td><td>$data, $contentView</td><td>Layout\'a giden view verisi; eklenti yeni anahtar ekleyebilir.</td></tr>\r\n		<tr><td>topic_list_types</td><td>BaseController</td><td>(varsayılan: [\'topic\',\'question\'])</td><td>Konu listesi sayfalarında gösterilecek konu tipleri.</td></tr>\r\n		<tr><td>topic_create_allowed_types</td><td>ForumController</td><td>$allowedTypes, $forum</td><td>Konu oluşturma sayfasında izin verilen tipler.</td></tr>\r\n		<tr><td>topic.view_data</td><td>TopicController</td><td>$viewData, $topic</td><td>Konu sayfası (showthread) view verisi.</td></tr>\r\n		<tr><td>post.display_data</td><td>TopicController</td><td>$postItem, $topic</td><td>Konu sayfasındaki her mesaj için gösterim verisi.</td></tr>\r\n		<tr><td>user.profile_data</td><td>MemberController</td><td>$profileData, $user</td><td>Üye profil sayfası view verisi.</td></tr>\r\n		<tr><td>translator.lines</td><td>Translator</td><td>$lines, $locale</td><td>Çeviri satırları; eklenti kendi dil anahtarlarını ekleyebilir.</td></tr>\r\n	</tbody>\r\n</table>\r\n<p><strong>Callable imzası (filter):</strong> İlk parametre filtrelenecek değer, sonra çekirdeğin gönderdiği ek argümanlar. Örnek: <code>function($navGroups) { ... return $navGroups; }</code>.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"adminmenu-yapisi\">9. admin.menu Yapısı</h2>\r\n<p><code>admin.menu</code> filter\'ında <code>$navGroups</code> bir dizidir. Her öğe bir menü grubudur: <strong>id</strong>, <strong>icon</strong>, <strong>label</strong>, <strong>url</strong> (isteğe bağlı), <strong>children</strong> (alt menü dizisi). Her child öğe: <strong>icon</strong>, <strong>label</strong>, <strong>url</strong>, <strong>match</strong> (URL eşleşmesi), <strong>separator</strong> (true ise ayırıcı).</p>\r\n<p><strong>Örnek – \"İçerik\" grubuna yeni link ekleme:</strong></p>\r\n<pre><code>public static function addItem($navGroups): array\r\n{\r\n    if (!is_array($navGroups)) {\r\n        return $navGroups;\r\n    }\r\n    $adminPath = env(\'ADMIN_PATH\', \'admin\');\r\n    $item = [\r\n        \'icon\'  =&gt; \'ti-chart-bar\',\r\n        \'label\' =&gt; \'Forum İstatistikleri\',\r\n        \'url\'   =&gt; core_url($adminPath . \'/forum-istatistik\'),\r\n        \'match\' =&gt; \'/\' . $adminPath . \'/forum-istatistik\',\r\n    ];\r\n    foreach ($navGroups as $key =&gt; $group) {\r\n        if (isset($group[\'id\']) &amp;&amp; $group[\'id\'] === \'content\' &amp;&amp; isset($group[\'children\']) &amp;&amp; is_array($group[\'children\'])) {\r\n            $navGroups[$key][\'children\'][] = $item;\r\n            break;\r\n        }\r\n    }\r\n    return $navGroups;\r\n}</code></pre>\r\n\r\n<hr>\r\n\r\n<h2 id=\"routesphp\">10. routes.php – Eklenti Rotaları</h2>\r\n<p>Eklentinin kendi sayfalarını (URL\'lerini) tanımlamak için kullanılır. Sadece <strong>etkin</strong> eklentilerin <code>routes.php</code> dosyası yüklenir.</p>\r\n<p><strong>Konum:</strong> <code>plugins/{EklentiAdi}/routes.php</code></p>\r\n<p><strong>Ortam:</strong> Dosya include edilirken <code>$router</code> değişkeni tanımlıdır.</p>\r\n<pre><code>&lt;?php\r\n\r\n$router-&gt;get(\'/eklenti/ayarlar\', [\\Plugins\\Seo\\Controllers\\SettingsController::class, \'index\']);\r\n$router-&gt;post(\'/eklenti/ayarlar\', [\\Plugins\\Seo\\Controllers\\SettingsController::class, \'save\']);\r\n$router-&gt;get(\'/admin/forum-istatistik\', [\\Plugins\\MegaforIstatistik\\Controllers\\AdminForumIstatistikController::class, \'index\']);</code></pre>\r\n<p>Handler olarak <strong>tam sınıf adı</strong> kullanırsanız çekirdek bunu kabul eder; eklenti kendi controller\'ını <code>Plugins\\EklentiAdi\\Controllers\\...</code> altında tutabilir.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"views-eklenti-sablonlari\">11. views/ – Eklenti Şablonları</h2>\r\n<p>Eklenti kendi Twig şablonlarını <code>plugins/{EklentiAdi}/views/</code> altında koyabilir. Etkin eklentilerin <code>views/</code> dizinleri Twig\'e <strong>namespace</strong> olarak eklenir; şablonlar <code>@EklentiAdi/dosya.html.twig</code> ile render edilir.</p>\r\n<p><strong>Örnek:</strong> <code>plugins/Seo/views/ayarlar.html.twig</code> → namespace <code>Seo</code>, kullanım: <code>@Seo/ayarlar.html.twig</code>. Controller\'dan: <code>$this-&gt;app-&gt;twig(\'frontend\')-&gt;render(\'@Seo/ayarlar.html.twig\', $data);</code></p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"install-uninstall\">12. install.php ve uninstall.php</h2>\r\n<p><strong>install.php</strong> — Eklenti <strong>ilk kez etkinleştirildiğinde</strong> bir kez çalıştırılır. Veritabanı tabloları oluşturma, varsayılan ayar ekleme burada yapılır. Konum: <code>plugins/{EklentiAdi}/install.php</code>. Ortam: <code>$application</code> değişkeni mevcuttur; <code>\\Illuminate\\Database\\Capsule\\Manager</code> ile SQL çalıştırılabilir.</p>\r\n<p><strong>uninstall.php</strong> — Admin panel → Eklentiler → <strong>Kaldır</strong> tıklandığında çalışır. Eklenti <strong>tüm kalıntıları temizlemekle yükümlüdür:</strong> eklediği tabloları DROP etmeli, ayar tablosundan kendi anahtarlarını silmeli. <code>uninstall.php</code> olmayan eklentide \"Kaldır\" butonu gösterilmez veya sadece işaret temizlenir; kalıntı bırakmamak için veritabanı/ayar kullanan her eklentinin <code>uninstall.php</code> yazması önerilir.</p>\r\n<pre><code>// install.php örneği\r\n$schema = DB::schema();\r\nif (!$schema-&gt;hasTable(\'plugin_seo_meta\')) {\r\n    $schema-&gt;create(\'plugin_seo_meta\', function ($table) {\r\n        $table-&gt;id();\r\n        $table-&gt;string(\'page_key\')-&gt;unique();\r\n        $table-&gt;string(\'meta_title\')-&gt;nullable();\r\n        $table-&gt;timestamps();\r\n    });\r\n}\r\n\r\n// uninstall.php örneği\r\nif ($schema-&gt;hasTable(\'plugin_seo_meta\')) {\r\n    $schema-&gt;drop(\'plugin_seo_meta\');\r\n}</code></pre>\r\n\r\n<hr>\r\n\r\n<h2 id=\"listener-hook-yazimi\">13. Listener ve Hook Sınıfları Yazımı</h2>\r\n<p><strong>Event Listener</strong> — Payload\'u type-hint ile alın:</p>\r\n<pre><code>use App\\Events\\TopicCreated;\r\n\r\nfinal class LogTopicCreated\r\n{\r\n    public function handle(TopicCreated $event): void\r\n    {\r\n        $topic = $event-&gt;topic;\r\n        error_log(\'[Plugin Example] topic.created: topic_id=\' . ($topic-&gt;id ?? \'\'));\r\n    }\r\n}</code></pre>\r\n<p><strong>Action Callable</strong> — Dönen değer string (HTML veya boş):</p>\r\n<pre><code>public static function render($app): string\r\n{\r\n    if (!$app instanceof \\Forecor\\core\\Application) {\r\n        return \'\';\r\n    }\r\n    return \'&lt;div class=\"sidebar-widget\"&gt;Eklenti içeriği&lt;/div&gt;\';\r\n}</code></pre>\r\n<p><strong>Filter Callable</strong> — İlk parametre filtrelenecek değer, dönüş dönüştürülmüş değer:</p>\r\n<pre><code>public static function addItem($navGroups): array\r\n{\r\n    if (!is_array($navGroups)) {\r\n        return $navGroups;\r\n    }\r\n    // $navGroups\'a yeni öğe ekleyin\r\n    return $navGroups;\r\n}</code></pre>\r\n\r\n<hr>\r\n\r\n<h2 id=\"boot-sirasi\">14. Boot Sırası ve Yükleme</h2>\r\n<ol>\r\n	<li>Uygulama başlarken <code>Application::event()</code> (veya eşdeğer bootstrap) çağrılır.</li>\r\n	<li>Önce <code>config/events.php</code> yüklenir; tüm çekirdek listener\'lar event dispatcher\'a eklenir.</li>\r\n	<li><code>PluginLoader::loadListeners()</code>: <code>disabled_plugins</code> listesinde olmayan eklentilerin <code>plugin.php</code> dosyaları taranır; <code>events</code> dispatcher\'a eklenir.</li>\r\n	<li><code>PluginLoader::loadHooks()</code>: Aynı eklentilerin <code>actions</code> ve <code>filters</code> tanımları <code>HookService</code>\'e kaydedilir.</li>\r\n	<li>Core rotalardan sonra <code>PluginLoader::loadPluginRoutes()</code> ile etkin eklentilerin <code>routes.php</code> dosyaları çalıştırılır.</li>\r\n	<li>Etkin eklentilerin <code>views/</code> dizinleri Twig loader\'a namespace olarak eklenir.</li>\r\n</ol>\r\n<p>Çekirdek dosyalara dokunulmaz.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"psr4-composer\">15. PSR-4 ve Composer Autoload</h2>\r\n<p>Eklenti içinde <code>Plugins\\EklentiAdi\\...</code> namespace\'li PHP sınıfları kullanıyorsanız, <code>composer.json</code> içinde şu eşleme olmalıdır:</p>\r\n<pre><code>\"autoload\": {\r\n    \"psr-4\": {\r\n        \"App\\\\\": \"app/\",\r\n        \"Plugins\\\\\": \"plugins/\"\r\n    }\r\n}</code></pre>\r\n<p>Yeni sınıf ekledikten sonra proje kökünde <strong><code>composer dump-autoload</code></strong> çalıştırın. Böylece <code>Plugins\\</code> namespace\'i <code>plugins/</code> dizinine eşlenir.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"yeni-eklenti-adimlari\">16. Yeni Eklenti Oluşturma Adımları</h2>\r\n<ol>\r\n	<li><strong>Klasör oluşturun:</strong> <code>plugins/BenimEklentim/</code></li>\r\n	<li><strong>plugin.php ekleyin:</strong> En azından boş dizi: <code>return [\'events\' =&gt; [], \'actions\' =&gt; [], \'filters\' =&gt; []];</code></li>\r\n	<li><strong>İsteğe bağlı plugin.json:</strong> name, version, description, author.</li>\r\n	<li><strong>Listener/Hook sınıfları:</strong> Örn. <code>Listeners/KonuOlusturulduListener.php</code>, <code>Hooks/SidebarBlock.php</code>; namespace <code>Plugins\\BenimEklentim\\...</code>.</li>\r\n	<li><strong>plugin.php\'de kayıt:</strong> <code>events</code>, <code>actions</code>, <code>filters</code> anahtarlarına sınıf referanslarını ekleyin.</li>\r\n	<li><strong>composer dump-autoload</strong> çalıştırın.</li>\r\n	<li>Admin panel → İçerik → Eklentiler → eklentinizi <strong>Etkinleştir</strong>.</li>\r\n	<li>Veritabanı veya ayar kullanıyorsanız <strong>install.php</strong> ve <strong>uninstall.php</strong> yazın.</li>\r\n</ol>\r\n\r\n<hr>\r\n\r\n<h2 id=\"twig-hook-notu\">17. Twig hook() Fonksiyonu Hakkında</h2>\r\n<p>Şablonlarda kullanılan <code>hook(\'isim\', payload)</code> Twig fonksiyonu, <strong>HookService</strong>\'in <code>doAction</code> / <code>applyFilters</code> API\'sini <strong>kullanmaz</strong>. Sadece Symfony EventDispatcher\'a event fırlatır. Yani:</p>\r\n<ul>\r\n	<li>Şablonlardan <strong>action</strong> (HTML birleştirme) veya <strong>filter</strong> (veri dönüşümü) tetiklenemez.</li>\r\n	<li>\"Header\'a eklenti HTML\'i ekle\" gibi bir şeyi sadece çekirdekteki PHP hook noktası (örn. <code>doAction(\'layout.header_extra\', $app)</code>) sağlar.</li>\r\n</ul>\r\n<p>Eklenti HTML veya veri dönüşümü için <strong>plugin.php</strong> içinde <strong>actions</strong> ve <strong>filters</strong> kullanın; çekirdek zaten bu hook\'ları uygun yerlerde tetikliyor.</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"admin-panel-eklentiler\">18. Admin Panel – Eklentiler Sayfası</h2>\r\n<ul>\r\n	<li><strong>Menü:</strong> Admin → İçerik → <strong>Eklentiler</strong></li>\r\n	<li><strong>Liste:</strong> <code>plugins/</code> içinde <code>plugin.php</code> bulunan tüm klasörler listelenir. Eklenti adı <code>plugin.json</code> → <code>name</code> varsa o, yoksa klasör adı kullanılır.</li>\r\n	<li><strong>Etkinleştir:</strong> Eklentiyi açar. <code>install.php</code> varsa ve daha önce kurulmadıysa bir kez çalıştırılır.</li>\r\n	<li><strong>Devre dışı bırak:</strong> Listener ve hook\'lar artık yüklenmez.</li>\r\n	<li><strong>Kaldır:</strong> <code>uninstall.php</code> varsa çalıştırılır; kalıntılar temizlenir.</li>\r\n</ul>\r\n<p>Ayar anahtarları: <code>settings.disabled_plugins</code> (JSON dizi), <code>settings.plugin_installed</code> (kurulum durumu).</p>\r\n\r\n<hr>\r\n\r\n<h2 id=\"sss\">19. Sık Sorulan Sorular</h2>\r\n<p><strong>S: Eklentiyi nasıl test ederim?</strong><br>\r\nKlasörü ve <code>plugin.php</code>\'yi ekleyin, <code>composer dump-autoload</code> yapın, admin panelden etkinleştirin. Örneğin <code>topic.created</code> listener\'ı için yeni bir konu oluşturup log veya ek işlemin çalıştığını kontrol edin.</p>\r\n<p><strong>S: admin.menu\'ye link ekledim ama görünmüyor.</strong><br>\r\n<code>admin.menu</code> çekirdekte <strong>filter</strong> ile tetiklenir. Eklentinizde bu hook\'u <strong>filters</strong> altında tanımladığınızdan emin olun; <strong>actions</strong> altında tanımlarsanız çalışmaz.</p>\r\n<p><strong>S: Event listener\'ım çalışmıyor.</strong><br>\r\nÇekirdekte o event\'in gerçekten <code>dispatch()</code> ile tetiklendiğini kontrol edin (bu kılavuzdaki event tablosu). Eklentinin etkin ve <code>plugin.php</code>\'deki event adının doğru olduğundan emin olun.</p>\r\n<p><strong>S: Eklenti rotalarım 404 veriyor.</strong><br>\r\n<code>routes.php</code> sadece <strong>etkin</strong> eklentilerde yüklenir. Eklentiyi etkinleştirin ve URL\'in router\'da tanımlı sırayla eşleştiğini kontrol edin.</p>\r\n<p><strong>S: Kaldırma sonrası tablo/ayar kalıyor.</strong><br>\r\n<code>uninstall.php</code> yazıp tüm eklediğiniz tabloları, ayar anahtarlarını ve önbelleği burada temizlemeniz gerekir.</p>\r\n<p><strong>S: Başka eklentinin hook\'una bağlanabilir miyim?</strong><br>\r\nSadece çekirdekte tetiklenen hook\'lar belgelenmiştir. Eklentilerin birbirinin hook\'unu tetiklemesi standart değildir.</p>\r\n\r\n<hr>\r\n\r\n<p>Bu kılavuz, MegaforBB eklenti sisteminin tüm bileşenlerini tek bir dokümanda toplar. Çekirdeğe dokunmadan yalnızca <code>plugins/{EklentiAdi}/</code> altında dosya ekleyerek event dinleyebilir, action/filter ile HTML ve veriyi genişletebilirsiniz.</p>\r\n\r\n</div>\r\n\r\n\r\n', 10, '2026-03-04 21:23:11', '2026-03-04 21:32:07');

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
(1, NULL, 'Başlarken', 'ba-larken', 10, '2026-03-01 15:44:43', '2026-03-02 22:19:29'),
(2, NULL, 'Kullanım ve Detaylar', 'kullan-m-ve-detaylar', 20, '2026-03-01 15:46:04', '2026-03-01 15:53:38'),
(4, 2, 'CMS sistemi', 'cms-sistemi', 30, '2026-03-01 15:53:14', '2026-03-02 22:19:29'),
(5, NULL, 'MegaforBB', 'megaforbb-download', 0, '2026-03-02 22:14:22', '2026-03-02 22:19:29'),
(6, NULL, 'MegaforBB Tema', 'megaforbb-tema', 30, '2026-03-04 21:19:44', '2026-03-04 21:19:44'),
(7, NULL, 'MegaforBB Eklenti', 'megaforbb-eklenti', 40, '2026-03-04 21:23:04', '2026-03-04 21:23:04');

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
(1, 1, NULL, 'Duyuru ve Güncelleme', 'duyuru-ve-g-ncelleme', 'discussion', 'MegaforBB yazılımıyla ilgili genel haberleri burada bulabilirsiniz.\r\n', 'https://www.megaforbb.com.tr/uploads/images/2026/02/news-updates.png', 'fa-regular fa-newspaper', 0, 14, 19, 52, '2026-03-07 03:22:01', 1, '2026-02-23 04:07:42', '2026-03-07 01:22:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(3, 1, NULL, 'Bug Reports', 'bug-reports', 'discussion', 'MegaforBB Bug reports forum (Hata raporlama forumu)', 'https://www.megaforbb.com.tr/uploads/images/2026/02/bugs-report.png', 'bug', 1, 6, 14, 51, '2026-03-07 03:22:01', 1, '2026-02-23 04:34:23', '2026-03-07 01:22:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(4, 2, NULL, 'Genel Sorular ', 'genel-sorular-', 'discussion', 'Megaforbb\'a yeni mi katıldınız veya platformu kullanma konusunda genel yardıma mı ihtiyacınız var? Genel Yapılandırma kılavuzu ile ilgili sorularınızı ve taleplerinizi buraya yazın. ', 'https://www.megaforbb.com.tr/uploads/images/2026/02/general-quest.png', 'users', 2, 6, 11, 22, '2026-03-07 03:22:01', 1, '2026-02-23 04:35:54', '2026-03-07 01:22:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(6, 2, NULL, 'Test ve Demo', 'test-ve-demo', 'discussion', 'Bu kategoride test ve demo içeriklerini paylaşacağız. Yeni özellikleri burada paylaşıp kullanıcıların beğenisine sunacağız.', 'https://www.megaforbb.com.tr/uploads/images/2026/02/test-demo.png', 'wand-magic-sparkles', 3, 8, 16, 46, '2026-03-07 03:22:01', 1, '2026-02-23 20:09:29', '2026-03-07 01:22:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(8, 4, NULL, 'Yazılım ve detaylı dökümanlar', 'yaz-l-m-ve-detayl-d-k-manlar', 'article', '', '', 'code', 0, 3, 3, 42, '2026-03-07 03:22:01', 1, '2026-03-01 03:59:48', '2026-03-07 01:22:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL),
(9, 4, NULL, 'Hosting - Server Dökümanları', 'hosting---server-d-k-manlar-', 'article', 'Hosting ve server Dökümanları', '', 'server', 0, 0, 0, NULL, '2026-03-07 03:22:01', NULL, '2026-03-01 04:00:19', '2026-03-07 01:22:01', 0, 0, 0, 0, 0, 1, 0, 'last_post_desc', 0, NULL);

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
(1, 37, 63, 5, 132, 'esw0rmer', '2026-03-07 03:22:01');

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
(21, 1, 'reply', '{\"url\":\"\\/topic\\/soru-cevap-test-konusu-41\",\"from_user_id\":129,\"from_username\":\"kaan\",\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\"}', '2026-03-02 00:27:30', '2026-03-02 00:14:58', NULL),
(22, 129, 'private_topic_added', '{\"url\":\"\\/topic\\/soru-cevap-test-konusu-41\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\"}', '2026-03-02 01:10:48', '2026-03-02 01:10:31', NULL),
(23, 129, 'reaction', '{\"url\":\"\\/topic\\/soru-cevap-test-konusu-41\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"post_id\":87,\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\"}', '2026-03-02 22:39:52', '2026-03-02 01:20:48', NULL),
(24, 1, 'reply', '{\"url\":\"\\/topic\\/soru-cevap-test-konusu-41\",\"from_user_id\":129,\"from_username\":\"kaan\",\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\"}', '2026-03-02 22:12:11', '2026-03-02 01:40:15', NULL),
(25, 131, 'report', '{\"url\":\"\\/topic\\/bildirim-sisteminde-hata-25\",\"from_username\":\"Sinek10\",\"post_id\":63,\"topic_id\":25}', NULL, '2026-03-04 15:50:18', NULL),
(26, 1, 'reaction', '{\"url\":\"\\/topic\\/megaforbb-guncelleme-notlari-gelistirmeler-50\",\"from_user_id\":132,\"from_username\":\"esw0rmer\",\"post_id\":99,\"topic_id\":50,\"topic_title\":\"MegaforBB Güncelleme Notları -Geliştirmeler\"}', '2026-03-06 17:12:42', '2026-03-05 23:44:38', NULL),
(27, 1, 'reputation', '{\"url\":\"\\/topic\\/megaforbb-guncelleme-notlari-gelistirmeler-50#post-99\",\"from_user_id\":132,\"from_username\":\"esw0rmer\",\"value\":1,\"topic_id\":50,\"topic_title\":\"MegaforBB Güncelleme Notları -Geliştirmeler\"}', '2026-03-06 17:12:51', '2026-03-05 23:44:44', NULL),
(28, 1, 'reputation', '{\"url\":\"\\/member\\/Sinek10\",\"from_user_id\":132,\"from_username\":\"esw0rmer\",\"value\":1,\"topic_id\":null,\"topic_title\":\"\"}', '2026-03-06 17:12:48', '2026-03-05 23:44:57', NULL),
(29, 132, 'reply', '{\"url\":\"\\/topic\\/sayfa-icerigi-guncellenirken-wysiwyg-calismiyor-51\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"topic_id\":51,\"topic_title\":\"Sayfa İçeriği Güncellenirken WYSIWYG Çalışmıyor\"}', '2026-03-07 00:10:50', '2026-03-06 17:14:27', NULL),
(30, 1, 'reaction', '{\"url\":\"\\/topic\\/sayfa-icerigi-guncellenirken-wysiwyg-calismiyor-51\",\"from_user_id\":132,\"from_username\":\"esw0rmer\",\"post_id\":101,\"topic_id\":51,\"topic_title\":\"Sayfa İçeriği Güncellenirken WYSIWYG Çalışmıyor\"}', '2026-03-07 00:44:39', '2026-03-07 00:09:11', NULL),
(31, 132, 'reply', '{\"url\":\"\\/topic\\/sayfa-icerigi-guncellenirken-wysiwyg-calismiyor-51\",\"from_user_id\":1,\"from_username\":\"Sinek10\",\"topic_id\":51,\"topic_title\":\"Sayfa İçeriği Güncellenirken WYSIWYG Çalışmıyor\"}', NULL, '2026-03-07 00:42:30', NULL);

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
  `reply_to_id` bigint(20) UNSIGNED DEFAULT NULL,
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

INSERT INTO `posts` (`id`, `topic_id`, `reply_to_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(1, 1, NULL, 1, '<h2>MegaforBB - Yeni Nesil Forum Scripti</h2><p>MegaforBB, \"<b>Güvenli, Güçlü, maksimum performans</b>\" felsefesiyle tasarlanmıştır. Hiçbir ağır framework kullanılmamış (Zend, Laravel Symfony vs. yoktur). Sistemin kalbini oluşturan Forecor (Özel Çekirdek, symfony ve Laravel\'in belirli kütüphanelerini kullanmıştır) sayesinde milisaniyelik tepki süreleriyle çalışır. Yüz binlerce üyesi ve konusu olan büyük toplulukları, sunucu kaynaklarını sömürmeden kolaylıkla kaldırabilmesi için özel olarak optimize edilmiştir. Tasarımda günümüz modern standartlarını baz alan TailwindCSS (v3) ve dinamik kullanıcı etkileşimleri için Alpine.js (gerekli yerlerde native vanilla JS) kullanılmıştır. Mobil uyumluluk sonradan eklenmiş bir yama değil, sistemin temel yapıtaşlarından biridir (%100 Responsive).&nbsp; %100 Özel Mimari, PHP 8.2+, modern TailwindCSS kullanılarak sıfırdan kodlanan, ultra hızlı, güvenlikli ve SEO uyumlu forum sistemi.</p><div contenteditable=\"false\"><hr></div><p>Diğer forum sistemlerinden ayıran bir çok özellik mevcuttur, Diğerlerinde Modül - Eklenti ile gelen gerekli ve önemli sistem parçacıkları bizde direkt olarak forumun bir parçası olarak geliyor. Sisteme herhangi bir yük bindirimemek için optimize edildi.</p><p><br></p><p>Diğer forum sistemlerinden import işlemi ile içeri aktarma veya taşınma yapılabilr (Şu anda sadece xenforo ve Mybb) destekliyoruz.&nbsp;</p><p>Forum sistemimiz ana giriş rotası Forum, Portal ve Makale olmak üzere 3 farklı seçim yapılabilir bunu farklı kullanıcı türleri için özel olarak yapılandırdık. kullanıp kullnmamak tamamen kullanıcının isteğine bağlı.</p><p>Kategori bazlı veya genel atanabilen \"Konu Ön Ekleri\". Kırmızı bir \"Satıldı\" veya yeşil bir \"Soru\" tagı oluşturup CSS atamalarını doğrudan Admin panelinden yapabilirsiniz. Seçilen prefixler hem konu listelerinde hem konu başlıklarında renkli badge (rozet) olarak sergilenir.</p><p>Klasik Beğeni (+1 Like) butonuna ek olarak kullanıcılara +Rep / -Rep verebilme (Gerekçeli, yorumlu şekilde). Belirli Rep ve Post sayısına ulaşan kullanıcılara otomatik olarak Admin\'in belirlediği rütbe ve Nişanların (Rozetlerin) atanması sistemi.</p><p>XSS açıklarına sıfır tolerans! Geliştirilmiş core_sanitize_html filtresi ile zararlı kod enjeksiyonları HTML formatlama sırasında engellenir. CSRF tokenleri her form için unique yaratılır. PDO Prepared Statements sayesinde SQL Injection imkansızdır. Sadece klasik \"konu aç cevapla\" değil. Konular \"Soru\" tipinde açılabilir. Soru tipli başlıklarda kullanıcılar yararlı buldukları mesajlara StackOverflow\'daki gibi Yukarı/Aşağı (Upvote/Downvote) verebilir ve konu sahibi (Veya yetkili) doğru cevabı \"Çözüm olarak işaretle\" diyebilir.</p><p><br></p>', '<h2>MegaforBB - Yeni Nesil Forum Scripti</h2><p>MegaforBB, \"<b>Güvenli, Güçlü, maksimum performans</b>\" felsefesiyle tasarlanmıştır. Hiçbir ağır framework kullanılmamış (Zend, Laravel Symfony vs. yoktur). Sistemin kalbini oluşturan Forecor (Özel Çekirdek, symfony ve Laravel\'in belirli kütüphanelerini kullanmıştır) sayesinde milisaniyelik tepki süreleriyle çalışır. Yüz binlerce üyesi ve konusu olan büyük toplulukları, sunucu kaynaklarını sömürmeden kolaylıkla kaldırabilmesi için özel olarak optimize edilmiştir. Tasarımda günümüz modern standartlarını baz alan TailwindCSS (v3) ve dinamik kullanıcı etkileşimleri için Alpine.js (gerekli yerlerde native vanilla JS) kullanılmıştır. Mobil uyumluluk sonradan eklenmiş bir yama değil, sistemin temel yapıtaşlarından biridir (%100 Responsive).&nbsp; %100 Özel Mimari, PHP 8.2+, modern TailwindCSS kullanılarak sıfırdan kodlanan, ultra hızlı, güvenlikli ve SEO uyumlu forum sistemi.</p><div contenteditable=\"false\"><hr></div><p>Diğer forum sistemlerinden ayıran bir çok özellik mevcuttur, Diğerlerinde Modül - Eklenti ile gelen gerekli ve önemli sistem parçacıkları bizde direkt olarak forumun bir parçası olarak geliyor. Sisteme herhangi bir yük bindirimemek için optimize edildi.</p><p><br></p><p>Diğer forum sistemlerinden import işlemi ile içeri aktarma veya taşınma yapılabilr (Şu anda sadece xenforo ve Mybb) destekliyoruz.&nbsp;</p><p>Forum sistemimiz ana giriş rotası Forum, Portal ve Makale olmak üzere 3 farklı seçim yapılabilir bunu farklı kullanıcı türleri için özel olarak yapılandırdık. kullanıp kullnmamak tamamen kullanıcının isteğine bağlı.</p><p>Kategori bazlı veya genel atanabilen \"Konu Ön Ekleri\". Kırmızı bir \"Satıldı\" veya yeşil bir \"Soru\" tagı oluşturup CSS atamalarını doğrudan Admin panelinden yapabilirsiniz. Seçilen prefixler hem konu listelerinde hem konu başlıklarında renkli badge (rozet) olarak sergilenir.</p><p>Klasik Beğeni (+1 Like) butonuna ek olarak kullanıcılara +Rep / -Rep verebilme (Gerekçeli, yorumlu şekilde). Belirli Rep ve Post sayısına ulaşan kullanıcılara otomatik olarak Admin\'in belirlediği rütbe ve Nişanların (Rozetlerin) atanması sistemi.</p><p>XSS açıklarına sıfır tolerans! Geliştirilmiş core_sanitize_html filtresi ile zararlı kod enjeksiyonları HTML formatlama sırasında engellenir. CSRF tokenleri her form için unique yaratılır. PDO Prepared Statements sayesinde SQL Injection imkansızdır. Sadece klasik \"konu aç cevapla\" değil. Konular \"Soru\" tipinde açılabilir. Soru tipli başlıklarda kullanıcılar yararlı buldukları mesajlara StackOverflow\'daki gibi Yukarı/Aşağı (Upvote/Downvote) verebilir ve konu sahibi (Veya yetkili) doğru cevabı \"Çözüm olarak işaretle\" diyebilir.</p><p><br></p>', 0, 0, 1, '2026-02-23 04:11:06', '2026-02-28 03:47:25', '2026-02-28 03:47:25', 1, 11, NULL, NULL, NULL),
(2, 2, NULL, 1, '<p>MegaforBB kurulumu için, Kullanım türüne bağlı olacak şekilde ilgili sunucunuzun Apache, nginx vb özel yapılandırma yapmanız gerekmektedir. Örnek olarak nginx için aşağıda ekstra kural seti verilmiştir.&nbsp; Diğer kısımlarda sadece veritabanını ve sistem dosyalarını \r\nindirip FTP\'ye yükleyip .env dosyasından birbirine bağlamanız yeterli \r\nolacaktır.</p><p><br></p><p>Nginx için ekstra kural seti:&nbsp;</p><p><br></p><p><br></p>\r\n\r\n<pre>location ^~ /theme-assets/ {\r\n  {{varnish_proxy_pass}}\r\n  proxy_set_header Host $host;\r\n  proxy_set_header X-Forwarded-Host $host;\r\n  proxy_set_header X-Real-IP $remote_addr;\r\n  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\r\n  proxy_set_header X-Forwarded-Proto $scheme;\r\n  proxy_hide_header X-Varnish;\r\n  proxy_redirect off;\r\n  proxy_max_temp_file_size 0;\r\n  proxy_connect_timeout 720;\r\n  proxy_send_timeout 720;\r\n  proxy_read_timeout 720;\r\n  proxy_buffer_size 128k;\r\n  proxy_buffers 4 256k;\r\n  proxy_busy_buffers_size 256k;\r\n  proxy_temp_file_write_size 256k;\r\n}\r\n</pre>\r\n\r\nBu Nginx kurallarını uygulamanız gerekmektedir, css ve js dosyalarının sorunsuz çalışması için.', '<p>MegaforBB kurulumu için, Kullanım türüne bağlı olacak şekilde ilgili sunucunuzun Apache, nginx vb özel yapılandırma yapmanız gerekmektedir. Örnek olarak nginx için aşağıda ekstra kural seti verilmiştir.&nbsp; Diğer kısımlarda sadece veritabanını ve sistem dosyalarını \r\nindirip FTP\'ye yükleyip .env dosyasından birbirine bağlamanız yeterli \r\nolacaktır.</p><p><br></p><p>Nginx için ekstra kural seti:&nbsp;</p><p><br></p><p><br></p>\r\n\r\n<pre>location ^~ /theme-assets/ {\r\n  {{varnish_proxy_pass}}\r\n  proxy_set_header Host $host;\r\n  proxy_set_header X-Forwarded-Host $host;\r\n  proxy_set_header X-Real-IP $remote_addr;\r\n  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\r\n  proxy_set_header X-Forwarded-Proto $scheme;\r\n  proxy_hide_header X-Varnish;\r\n  proxy_redirect off;\r\n  proxy_max_temp_file_size 0;\r\n  proxy_connect_timeout 720;\r\n  proxy_send_timeout 720;\r\n  proxy_read_timeout 720;\r\n  proxy_buffer_size 128k;\r\n  proxy_buffers 4 256k;\r\n  proxy_busy_buffers_size 256k;\r\n  proxy_temp_file_write_size 256k;\r\n}\r\n</pre>\r\n\r\nBu Nginx kurallarını uygulamanız gerekmektedir, css ve js dosyalarının sorunsuz çalışması için.', 0, 0, 1, '2026-02-23 04:15:08', '2026-02-24 01:14:03', '2026-02-24 01:14:03', 1, 3, NULL, NULL, NULL),
(3, 1, NULL, 129, '<p>Hayırlı olsun, Nice güzel yeniliklerle yeni sürümlere inşallah.</p>', '<p>Hayırlı olsun, Nice güzel yeniliklerle yeni sürümlere inşallah.</p>', 1, 0, 0, '2026-02-23 06:31:13', '2026-02-23 06:50:51', NULL, NULL, 0, NULL, NULL, NULL),
(10, 5, NULL, 1, 'MegaforBB Haftalık ufak minr güncellemelerini burada paylaşacağız.', 'MegaforBB Haftalık ufak minr güncellemelerini burada paylaşacağız.', 0, 0, 1, '2026-02-21 07:04:46', '2026-02-23 18:11:52', NULL, NULL, 0, NULL, NULL, NULL),
(35, 14, NULL, 1, '<p>MegaforBB Seo için özel geliştirilmiş Schema ve url seti kuralları vardır.&nbsp;</p><p><br></p><p>Şu anda Sistemde halihazırsa seo uyumlu link yapısı mevcuttur ve aktiftir, schema yapısı ise json yapısında ilerleyen dönemde paylaşılacaktır.&nbsp;</p><p><br></p><p>Tüm sistemin schema kurallarını ilerleyen dönemde paylaşacağız.</p>', '<p>MegaforBB Seo için özel geliştirilmiş Schema ve url seti kuralları vardır.&nbsp;</p><p><br></p><p>Şu anda Sistemde halihazırsa seo uyumlu link yapısı mevcuttur ve aktiftir, schema yapısı ise json yapısında ilerleyen dönemde paylaşılacaktır.&nbsp;</p><p><br></p><p>Tüm sistemin schema kurallarını ilerleyen dönemde paylaşacağız.</p>', 0, 0, 1, '2026-02-09 07:04:46', '2026-02-24 00:33:46', '2026-02-23 18:16:44', 1, 1, NULL, NULL, NULL),
(36, 15, NULL, 1, '<p>Merhabalar.</p><p>MegaforBB Forum sisteminin Tasarım ve tema üzerindeki hataları, değişiklik istekleri güncellemeleri ve sorunlarını bu post altında bildirimlerde buşunabilirsiniz.</p><p><br></p><p><font color=\"#ff0000\">Not: Absürt ve kişisel istekler değerlendirilmeyecektir.</font></p>', '<p>Merhabalar.</p><p>MegaforBB Forum sisteminin Tasarım ve tema üzerindeki hataları, değişiklik istekleri güncellemeleri ve sorunlarını bu post altında bildirimlerde buşunabilirsiniz.</p><p><br></p><p>Not: Absürt ve kişisel istekler değerlendirilmeyecektir.</p>', 0, 0, 1, '2026-02-10 07:04:46', '2026-02-24 00:34:07', '2026-02-23 18:07:46', 1, 1, NULL, NULL, NULL),
(45, 19, NULL, 1, '<p>Merhabalar, Bu konuda @kaan ile birlikte kullanıcı ve etiket sistemini test ediyoruz.</p><p>Post etiket: yüzlerce cevaba ulaşan konuların içinden hangi mesajdan bahsettiğinizi alıntı yapmadan etiket ile belirtme sistemidir. </p><p><br></p><p>Bu etiket sistemi Temel etiket sisteminden farklıdır bu sadece konu içindeki mesajların ID değerleri ile çalışmaktadır, ve sadece içinde bulunduğu konuya ait link verir.</p>', '<p>Merhabalar, Bu konuda <a href=\"/member/kaan\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"kaan\">@kaan</a> ile birlikte kullanıcı ve etiket sistemini test ediyoruz.</p><p>Post etiket: yüzlerce cevaba ulaşan konuların içinden hangi mesajdan bahsettiğinizi alıntı yapmadan etiket ile belirtme sistemidir. </p><p><br></p><p>Bu etiket sistemi Temel etiket sisteminden farklıdır bu sadece konu içindeki mesajların ID değerleri ile çalışmaktadır, ve sadece içinde bulunduğu konuya ait link verir.</p>', 0, 0, 1, '2026-02-24 00:54:25', '2026-02-24 10:40:14', '2026-02-24 00:55:52', 1, 1, NULL, NULL, NULL),
(46, 19, NULL, 129, '<p>Örnek bir cevap vererek Etiket sisteminin anlatılması ve @slaweally ile deniyoruz.&nbsp;</p><p>Bu mesajın #2 yaparak etiketlemiş olabiliriz.</p><p><br></p><p>/*-------------------------------------------*/</p><p><br></p><p>Sorun çözüldü, Etiket ve Ment. sistemi mükemmel.</p><p>#2 yaparak ve @Sinek10 yaparak görebiliriz</p>', '<p>Örnek bir cevap vererek Etiket sisteminin anlatılması ve @slaweally ile deniyoruz.&nbsp;</p><p>Bu mesajın <a href=\"/topic/19/post-by-pos/2\" class=\"post-ref inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors\" data-topic-id=\"19\" data-post-pos=\"2\">#2</a> yaparak etiketlemiş olabiliriz.</p><p><br></p><p>/*-------------------------------------------*/</p><p><br></p><p>Sorun çözüldü, Etiket ve Ment. sistemi mükemmel.</p><p><a href=\"/topic/19/post-by-pos/2\" class=\"post-ref inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors\" data-topic-id=\"19\" data-post-pos=\"2\">#2</a> yaparak ve <a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a> yaparak görebiliriz</p>', 1, 0, 0, '2026-02-24 00:57:31', '2026-02-24 10:40:14', '2026-02-24 04:32:41', 1, 1, NULL, NULL, NULL),
(47, 20, NULL, 129, '<p>Kullanıcı kayıt sırasında Captcha doğrulaması gerçekleştiği halde hata veriyor Captcha doğrulaması başarısız hatası basıyor.</p><p>Sorunun temeli Cloudflare\'mı yoksa sistemdeen mi kaynaklanıyor bilmiyorum, incelenmesi gerekiyor. @Sinek10</p>', '<p>Kullanıcı kayıt sırasında Captcha doğrulaması gerçekleştiği halde hata veriyor Captcha doğrulaması başarısız hatası basıyor.</p><p>Sorunun temeli Cloudflare\'mı yoksa sistemdeen mi kaynaklanıyor bilmiyorum, incelenmesi gerekiyor. <a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a></p>', 0, 0, 1, '2026-02-24 03:54:41', '2026-02-24 04:24:04', '2026-02-24 04:24:04', 1, 4, NULL, NULL, NULL),
(48, 19, NULL, 1, '<p>Test bir #2 ve @Sinek10</p>', '<p>Test bir <a href=\"/topic/19/post-by-pos/2\" class=\"post-ref inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors\" data-topic-id=\"19\" data-post-pos=\"2\">#2</a> ve <a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a></p>', 0, 0, 0, '2026-02-24 04:31:31', '2026-02-24 10:40:14', NULL, NULL, 0, NULL, NULL, NULL),
(49, 21, NULL, 129, '<p>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor.&nbsp;</p><p>@Sinek10</p>', '<p>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor.&nbsp;</p><p><a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a></p>', 0, 0, 1, '2026-02-24 04:41:16', '2026-02-24 04:41:16', NULL, NULL, 0, NULL, NULL, NULL),
(50, 21, NULL, 1, '<p><img style=\"width: 1480px;\" src=\"/uploads/images/2026/02/fd347efa7e0da350.png\"></p><p><br></p><p></p><blockquote class=\"border-l-4 border-blue-500 pl-3 py-2 my-2 bg-blue-50 rounded-r text-blue-900\" data-author=\"kaan\"><p><strong>kaan yazdı:</strong><br>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor. @Sinek10</p></blockquote><p><br></p><p>Sorun çözülmüştür, Aslında çalışıyor ancak tema\'da göstermemişiz, hallettik.</p><p></p>', '<p><img style=\"width: 1480px;\" src=\"/uploads/images/2026/02/fd347efa7e0da350.png\"></p><p><br></p><p></p><blockquote class=\"border-l-4 border-blue-500 pl-3 py-2 my-2 bg-blue-50 rounded-r text-blue-900\" data-author=\"kaan\"><p><strong>kaan yazdı:</strong><br>Konu açarken seçilen konu Ön eki forumda ve konu detayında görünmüyor. <a href=\"/member/Sinek10\" class=\"mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors\" data-mention-username=\"Sinek10\">@Sinek10</a></p></blockquote><p><br></p><p>Sorun çözülmüştür, Aslında çalışıyor ancak tema\'da göstermemişiz, hallettik.</p><p></p>', 1, 1, 0, '2026-02-24 04:44:44', '2026-02-24 10:52:11', '2026-02-24 10:14:11', 1, 1, NULL, NULL, NULL),
(51, 22, NULL, 1, '<p>MegaforBB Uzaktan kurulum ve güncelleme sistemi gerekli olur mu ve ne derece gereklidir ?</p><p>Örneğin basit bir Wordpress kurulumu gibi olmalı mı yoksa sadece Profesyonellere hitap ettiği için şu anki yeterli olur mu ?</p>', '<p>MegaforBB Uzaktan kurulum ve güncelleme sistemi gerekli olur mu ve ne derece gereklidir ?</p><p>Örneğin basit bir Wordpress kurulumu gibi olmalı mı yoksa sadece Profesyonellere hitap ettiği için şu anki yeterli olur mu ?</p>', 0, 1, 1, '2026-02-24 05:11:28', '2026-02-26 18:45:20', NULL, NULL, 0, NULL, NULL, NULL),
(52, 23, NULL, 1, '<p>Konu düzenleme geçmişinde sınırsız geçmiş verisi tutuluyor, buna bir çözüm bulmak gerekiyor.<br>Acaba son 3 düzenlemeyi mi saklamak mantıklı yoksa sürüm olarak ilk mesaj son mesaj saklamak mı mantıklı ? Düşünüp uygulamaya koyacağız...</p>', '<p>Konu düzenleme geçmişinde sınırsız geçmiş verisi tutuluyor, buna bir çözüm bulmak gerekiyor.<br>Acaba son 3 düzenlemeyi mi saklamak mantıklı yoksa sürüm olarak ilk mesaj son mesaj saklamak mı mantıklı ? Düşünüp uygulamaya koyacağız...</p>', 0, 0, 1, '2026-02-24 10:50:52', '2026-02-24 10:50:52', NULL, NULL, 0, NULL, NULL, NULL),
(53, 23, NULL, 1, '<p>Son 3 değişiklik mantıklı olarak uygulandı.\r\n\r\n</p><p><br></p><p>Konu Düzenleme Loglarının (Geçmişinin) Limitlenmesi: Artık bir yorum ne kadar çok düzenlenirse düzenlensin, veritabanının şişmesini önlemek için yalnızca son 3 değişikliği sunucu üzerinde (veritabanı post_edits tablosunda) tutulacaktır. Mesaj her düzenlendiğinde arkada bu limit kontrol edilecek ve eğer 3\'ten fazla geçmiş kalıntı varsa en eskisi silinerek yer açılacaktır. (Bkz: TopicController::updatePost)</p>', '<p>Son 3 değişiklik mantıklı olarak uygulandı.\r\n\r\n</p><p><br></p><p>Konu Düzenleme Loglarının (Geçmişinin) Limitlenmesi: Artık bir yorum ne kadar çok düzenlenirse düzenlensin, veritabanının şişmesini önlemek için yalnızca son 3 değişikliği sunucu üzerinde (veritabanı post_edits tablosunda) tutulacaktır. Mesaj her düzenlendiğinde arkada bu limit kontrol edilecek ve eğer 3\'ten fazla geçmiş kalıntı varsa en eskisi silinerek yer açılacaktır. (Bkz: TopicController::updatePost)</p>', 0, 0, 0, '2026-02-24 11:03:52', '2026-02-24 11:04:44', '2026-02-24 11:04:44', 1, 1, NULL, NULL, NULL),
(54, 24, NULL, 1, '<p>Sistemimizde şu anda spagetti düz php ile tema sistemi var ancak bu sistemin ileri dönük güncelleme ve yeni tema geliştirilmesi konusunda çekincelerim var o nedenle de twig veya blade sistemi düşünüyorum acaba hangisi daha mantıklı olur blade mi twig mi ?</p><p><br></p><p>Twig symfony kullanıcılarının sevdiği ve aşina olduğu sistem, Blade\'de tam tersi laravel tarafının sevdiği sistem.&nbsp;</p><p>Tarafını seç :)</p>', '<p>Sistemimizde şu anda spagetti düz php ile tema sistemi var ancak bu sistemin ileri dönük güncelleme ve yeni tema geliştirilmesi konusunda çekincelerim var o nedenle de twig veya blade sistemi düşünüyorum acaba hangisi daha mantıklı olur blade mi twig mi ?</p><p><br></p><p>Twig symfony kullanıcılarının sevdiği ve aşina olduğu sistem, Blade\'de tam tersi laravel tarafının sevdiği sistem.&nbsp;</p><p>Tarafını seç :)</p>', 0, 0, 1, '2026-02-24 15:08:04', '2026-02-24 15:08:04', NULL, NULL, 0, NULL, NULL, NULL),
(55, 25, NULL, 129, '<p>Bildirim sisteminde ufak bir hata var, Bildirimleri okundu işaretle - Tümünü okundu işaretle yapamıyoruz dolayısıyla belirli aralıklarla bildirimler gösteriliyor sağ üstte.</p><p>Sorun teşkil edecek birşey değil ama düzeltilmesi iyi olur.</p>', '<p>Bildirim sisteminde ufak bir hata var, Bildirimleri okundu işaretle - Tümünü okundu işaretle yapamıyoruz dolayısıyla belirli aralıklarla bildirimler gösteriliyor sağ üstte.</p><p>Sorun teşkil edecek birşey değil ama düzeltilmesi iyi olur.</p>', 2, 0, 1, '2026-02-24 21:05:34', '2026-02-27 15:56:09', NULL, NULL, 0, NULL, NULL, NULL),
(56, 26, NULL, 1, '<h2>Sansür Koruma Sistemi</h2><h2>Sansür koruma sistemi yeni pakette yerleşik olarak sisteme entegre edilmiştir.</h2><p><strong>yasak kelimeler</strong>, <strong>yasak kullanıcı adları</strong> ve <strong>geçici e-posta (temp mail) koruması</strong> anlatılmaktadır.</p><ul><li><p><code>blocked_words</code> — Engellenecek kelimeler (opsiyonel replacement, regex destekli)</p></li><li><p><code>blocked_usernames</code> — Engellenecek kullanıcı adları (tam eşleşme veya regex)</p></li><li><p><code>blocked_email_domains</code> — Temp mail domain listesi (kayıtta e-posta domaini kontrolü)</p></li><li><p>İlgili <code>settings</code> anahtarları ve varsayılan temp mail domain listesi</p></li></ul>', '<h2>Sansür Koruma Sistemi</h2><h2>Sansür koruma sistemi yeni pakette yerleşik olarak sisteme entegre edilmiştir.</h2><p><strong>yasak kelimeler</strong>, <strong>yasak kullanıcı adları</strong> ve <strong>geçici e-posta (temp mail) koruması</strong> anlatılmaktadır.</p><ul><li><p><code>blocked_words</code> — Engellenecek kelimeler (opsiyonel replacement, regex destekli)</p></li><li><p><code>blocked_usernames</code> — Engellenecek kullanıcı adları (tam eşleşme veya regex)</p></li><li><p><code>blocked_email_domains</code> — Temp mail domain listesi (kayıtta e-posta domaini kontrolü)</p></li><li><p>İlgili <code>settings</code> anahtarları ve varsayılan temp mail domain listesi</p></li></ul>', 0, 0, 1, '2026-02-26 20:39:11', '2026-03-04 15:53:27', NULL, NULL, 0, NULL, NULL, NULL),
(57, 5, NULL, 1, '<h2>Haftalık güncelleme işlemleri</h2><p><br></p><ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Sansür sistemi geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>MegaforBB - MegaforBB veritabanı import sistemi geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>XenForo v2.2.13  to MegaforBB import aracı geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>MyBB 1.8.39 İmport aracı geliştirildi.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Composer install - update sistemi geliştirildi.</p></li></ul>', '<h2>Haftalık güncelleme işlemleri</h2><p><br></p><ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Sansür sistemi geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>MegaforBB - MegaforBB veritabanı import sistemi geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>XenForo v2.2.13  to MegaforBB import aracı geliştirildi</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>MyBB 1.8.39 İmport aracı geliştirildi.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Composer install - update sistemi geliştirildi.</p></li></ul>', 1, 0, 0, '2026-02-26 20:42:29', '2026-02-26 20:43:31', NULL, NULL, 0, NULL, NULL, NULL),
(58, 15, NULL, 1, '<p><br></p><p><strong>Sinek10 yazdı:</strong></p><blockquote><p><br></p><p>Merhabalar.MegaforBB Forum sisteminin Tasarım ve tema üzerindeki hataları, değişiklik istekleri güncellemeleri ve sorunlarını bu post altında bildirimlerde buşunabilirsiniz.Not: Absürt ve kişisel istekler değerlendirilmeyecektir.</p></blockquote><p>Ufak güncellemeler yayınlandı, Forum profil sayfası Footer, ve portal sayfasında güncellemeler yapıldı.</p>', '<p><br></p><p><strong>Sinek10 yazdı:</strong></p><blockquote><p><br></p><p>Merhabalar.MegaforBB Forum sisteminin Tasarım ve tema üzerindeki hataları, değişiklik istekleri güncellemeleri ve sorunlarını bu post altında bildirimlerde buşunabilirsiniz.Not: Absürt ve kişisel istekler değerlendirilmeyecektir.</p></blockquote><p>Ufak güncellemeler yayınlandı, Forum profil sayfası Footer, ve portal sayfasında güncellemeler yapıldı.</p>', 0, 0, 0, '2026-02-26 20:45:19', '2026-02-26 20:45:19', NULL, NULL, 0, NULL, NULL, NULL),
(59, 27, NULL, 1, '<h1>Dil Sistemi ve Twig Entegrasyonu</h1><h2>Genel Bakış</h2><p>MegaforBB dil sistemi, <strong>dosya tabanlı çevirileri </strong> <code>lang/{locale}.php</code> üzerinden okur ve <strong>veritabanı override</strong>\'ları ile birleştirir. Uygulama genelinde çeviri çağrıları <code>lang(\'key\')</code> helper\'ı üzerinden yapılır. Twig tarafında aynı helper, <code>TemplateEngine</code> tarafından global fonksiyon olarak kayıtlıdır.</p><ul><li><p>Çeviri helper\'ı: helpers.php</p></li><li><p>Translator servisi: Translator.php</p></li><li><p>Twig fonksiyon kayıtları: TemplateEngine.php</p></li></ul><h2>Locale Çözümleme Sırası</h2><p>Uygulama dili, merkezi olarak <code>Application::resolveLocale()</code> içinde belirlenir. Öncelik sırası aşağıdaki gibidir:</p><ol><li><p><strong>Session</strong>: <code>locale</code> key\'i</p></li><li><p><strong>Cookie</strong>: <code>locale</code> key\'i</p></li><li><p><strong>Accept-Language</strong>: tarayıcı dili, dosya varsa</p></li><li><p><strong>Config</strong>: <code>app.locale</code> (varsayılan <code>tr</code>)</p></li></ol><p>Kaynak: Application.php</p><h2>Çeviri Yükleme ve Override Mantığı</h2><p><code>Translator::load()</code> her locale için önce dosya çevirilerini okur, sonra DB çevirilerini ekler. <code>array_merge</code> kullanıldığı için <strong>DB kayıtları aynı anahtarda dosya değerini override eder</strong>.</p><ul><li><p>Dosya: <code>lang/{locale}.php</code></p></li><li><p>DB tablo: <code>language_lines</code></p></li></ul><p>Kaynaklar:</p><ul><li><p>Translator::load</p></li><li><p>LanguageLine::getTranslationsForLocale</p></li></ul><h2>Fallback Locale</h2><p>Translator, fallback dili <code>Language::getDefault()</code> üzerinden belirler; DB hazır değilse <code>tr</code> kullanır. Böylece anahtar bulunamazsa fallback locale denenir; yine yoksa anahtarın kendisi döner.</p><p><br></p><p>Kaynak: Application::translator, Translator::get</p><h2>Anahtar Formatı (Flat Key)</h2><p>Dil dosyaları <strong>flat key</strong> formatındadır; nested array yerine <code>group.key</code> şeklinde string anahtarlar kullanılır.</p><p><br></p><p>Örnekler:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">\'admin.languages.title\' =&gt; \'Dil Yönetimi\',\r\n\'common.login\' =&gt; \'Giriş Yap\',</code></pre></div><p>Kaynak: lang/tr.php</p><h2>Placeholder Desteği</h2><p><code>lang(\'key\', [\'name\' =&gt; \'Ali\'])</code> çağrısında <code>:name</code> placeholder\'ları string içinde replace edilir.</p><p><br></p><p>Kaynak: Translator::get</p><h2>Twig Dosyalarında Kullanım</h2><p>Twig içinde <code>lang()</code> fonksiyonu doğrudan kullanılabilir. <code>TemplateEngine</code> içinde TwigFunction olarak register edilir ve tüm template\'lerde erişilebilir.</p><p><br></p><p>Örnek:</p><div data-language=\"twig\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"twig\">&lt;h2 class=\"page-title\"&gt;{{ lang(\'admin.languages.title\')|e }}&lt;/h2&gt;</code></pre></div><p>Kaynak:</p><ul><li><p>TemplateEngine::registerFunctions</p></li><li><p>languages/index.html.twig</p></li></ul><h2>PHP Tarafında Kullanım</h2><p>Controller veya servislerde doğrudan <code>lang()</code> helper\'ı çağrılır. Helper, mevcut <code>Application</code> instance üzerinden <code>translator()-&gt;get()</code> çağırır.</p><p><br></p><p>Örnek:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">return $this-&gt;view(\'languages/index\', [\r\n    \'pageTitle\' =&gt; lang(\'admin.languages.title\'),\r\n]);</code></pre></div><p>Kaynak:</p><ul><li><p>AdminLanguageController::index</p></li><li><p>helpers.php</p></li></ul><h2>Admin Panel Dil Yönetimi</h2><p>Dil yönetimi ekranı, DB ve dosya tabanlı dilleri birleştirir; çeviri sayısı <code>Translator::all()</code> ile hesaplanır. Dil ekleme/düzenleme işlemleri DB tarafında <code>language_lines</code> tablosuna yazılır ve ardından <code>lang/{code}.php</code> dosyasına export edilir.</p><p><br></p><p>Öne çıkan akışlar:</p><ul><li><p><strong>Listeleme</strong>: DB + dosya taraması birleştirilir</p></li><li><p><strong>Yeni dil</strong>: DB kaydı + opsiyonel çeviri kopyalama</p></li><li><p><strong>Düzenleme</strong>: tüm anahtarlar (varsayılan + mevcut) bir arada gösterilir</p></li><li><p><strong>Kaydetme</strong>: DB update + dosyaya export</p></li><li><p><strong>Varsayılan dil</strong>: <code>languages.is_default</code> güncellenir</p></li></ul><p>Kaynak: AdminLanguageController</p><h2>Pratik Notlar</h2><ul><li><p>Dil dosyası yoksa locale, <code>resolveLocale</code> içinde dosya kontrolü ile elenir.</p></li><li><p>Twig cache çıktıları <code>storage/views/</code> altında saklanır; dil değişiminde Twig çıktısı tekrar üretilebilir.</p></li><li><p><code>lang()</code> çağrısı her yerde güvenle kullanılabilir; anahtar bulunamazsa anahtarın kendisi döner.</p></li></ul><p>Kaynaklar:</p><ul><li><p>Application::resolveLocale</p></li><li><p>Translator::get</p></li></ul>', '<h1>Dil Sistemi ve Twig Entegrasyonu</h1><h2>Genel Bakış</h2><p>MegaforBB dil sistemi, <strong>dosya tabanlı çevirileri </strong> <code>lang/{locale}.php</code> üzerinden okur ve <strong>veritabanı override</strong>\'ları ile birleştirir. Uygulama genelinde çeviri çağrıları <code>lang(\'key\')</code> helper\'ı üzerinden yapılır. Twig tarafında aynı helper, <code>TemplateEngine</code> tarafından global fonksiyon olarak kayıtlıdır.</p><ul><li><p>Çeviri helper\'ı: helpers.php</p></li><li><p>Translator servisi: Translator.php</p></li><li><p>Twig fonksiyon kayıtları: TemplateEngine.php</p></li></ul><h2>Locale Çözümleme Sırası</h2><p>Uygulama dili, merkezi olarak <code>Application::resolveLocale()</code> içinde belirlenir. Öncelik sırası aşağıdaki gibidir:</p><ol><li><p><strong>Session</strong>: <code>locale</code> key\'i</p></li><li><p><strong>Cookie</strong>: <code>locale</code> key\'i</p></li><li><p><strong>Accept-Language</strong>: tarayıcı dili, dosya varsa</p></li><li><p><strong>Config</strong>: <code>app.locale</code> (varsayılan <code>tr</code>)</p></li></ol><p>Kaynak: Application.php</p><h2>Çeviri Yükleme ve Override Mantığı</h2><p><code>Translator::load()</code> her locale için önce dosya çevirilerini okur, sonra DB çevirilerini ekler. <code>array_merge</code> kullanıldığı için <strong>DB kayıtları aynı anahtarda dosya değerini override eder</strong>.</p><ul><li><p>Dosya: <code>lang/{locale}.php</code></p></li><li><p>DB tablo: <code>language_lines</code></p></li></ul><p>Kaynaklar:</p><ul><li><p>Translator::load</p></li><li><p>LanguageLine::getTranslationsForLocale</p></li></ul><h2>Fallback Locale</h2><p>Translator, fallback dili <code>Language::getDefault()</code> üzerinden belirler; DB hazır değilse <code>tr</code> kullanır. Böylece anahtar bulunamazsa fallback locale denenir; yine yoksa anahtarın kendisi döner.</p><p><br></p><p>Kaynak: Application::translator, Translator::get</p><h2>Anahtar Formatı (Flat Key)</h2><p>Dil dosyaları <strong>flat key</strong> formatındadır; nested array yerine <code>group.key</code> şeklinde string anahtarlar kullanılır.</p><p><br></p><p>Örnekler:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">\'admin.languages.title\' =&gt; \'Dil Yönetimi\',\r\n\'common.login\' =&gt; \'Giriş Yap\',</code></pre></div><p>Kaynak: lang/tr.php</p><h2>Placeholder Desteği</h2><p><code>lang(\'key\', [\'name\' =&gt; \'Ali\'])</code> çağrısında <code>:name</code> placeholder\'ları string içinde replace edilir.</p><p><br></p><p>Kaynak: Translator::get</p><h2>Twig Dosyalarında Kullanım</h2><p>Twig içinde <code>lang()</code> fonksiyonu doğrudan kullanılabilir. <code>TemplateEngine</code> içinde TwigFunction olarak register edilir ve tüm template\'lerde erişilebilir.</p><p><br></p><p>Örnek:</p><div data-language=\"twig\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"twig\">&lt;h2 class=\"page-title\"&gt;{{ lang(\'admin.languages.title\')|e }}&lt;/h2&gt;</code></pre></div><p>Kaynak:</p><ul><li><p>TemplateEngine::registerFunctions</p></li><li><p>languages/index.html.twig</p></li></ul><h2>PHP Tarafında Kullanım</h2><p>Controller veya servislerde doğrudan <code>lang()</code> helper\'ı çağrılır. Helper, mevcut <code>Application</code> instance üzerinden <code>translator()-&gt;get()</code> çağırır.</p><p><br></p><p>Örnek:</p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">return $this-&gt;view(\'languages/index\', [\r\n    \'pageTitle\' =&gt; lang(\'admin.languages.title\'),\r\n]);</code></pre></div><p>Kaynak:</p><ul><li><p>AdminLanguageController::index</p></li><li><p>helpers.php</p></li></ul><h2>Admin Panel Dil Yönetimi</h2><p>Dil yönetimi ekranı, DB ve dosya tabanlı dilleri birleştirir; çeviri sayısı <code>Translator::all()</code> ile hesaplanır. Dil ekleme/düzenleme işlemleri DB tarafında <code>language_lines</code> tablosuna yazılır ve ardından <code>lang/{code}.php</code> dosyasına export edilir.</p><p><br></p><p>Öne çıkan akışlar:</p><ul><li><p><strong>Listeleme</strong>: DB + dosya taraması birleştirilir</p></li><li><p><strong>Yeni dil</strong>: DB kaydı + opsiyonel çeviri kopyalama</p></li><li><p><strong>Düzenleme</strong>: tüm anahtarlar (varsayılan + mevcut) bir arada gösterilir</p></li><li><p><strong>Kaydetme</strong>: DB update + dosyaya export</p></li><li><p><strong>Varsayılan dil</strong>: <code>languages.is_default</code> güncellenir</p></li></ul><p>Kaynak: AdminLanguageController</p><h2>Pratik Notlar</h2><ul><li><p>Dil dosyası yoksa locale, <code>resolveLocale</code> içinde dosya kontrolü ile elenir.</p></li><li><p>Twig cache çıktıları <code>storage/views/</code> altında saklanır; dil değişiminde Twig çıktısı tekrar üretilebilir.</p></li><li><p><code>lang()</code> çağrısı her yerde güvenle kullanılabilir; anahtar bulunamazsa anahtarın kendisi döner.</p></li></ul><p>Kaynaklar:</p><ul><li><p>Application::resolveLocale</p></li><li><p>Translator::get</p></li></ul>', 1, 0, 1, '2026-02-26 20:48:33', '2026-02-27 15:32:40', '2026-02-26 20:49:00', 1, 1, NULL, NULL, NULL),
(60, 28, NULL, 1, '<p>Bu doküman, kullanıcının <strong>kendi hesabını askıya alması</strong> (geçici) ve <strong>kalıcı kapatması</strong> özelliklerini açıklar. Askıya alınan hesap daha sonra tekrar açılabilir; kapatılan hesap kesinlikle açılamaz.</p><h3>Hesabı askıya al (geçici)</h3><ul><li><p>Kullanıcı profil ayarlarından hesabını <strong>geçici olarak askıya alabilir</strong>.</p></li><li><p>İşlem: Mevcut şifre ile onay → <code>is_suspended = 1</code>, <code>suspended_at = now()</code> → oturum kapatılır, giriş sayfasına yönlendirilir.</p></li><li><p><strong>Tekrar açma:</strong> Giriş sayfasında “Hesabımı tekrar aç” bağlantısı veya doğrudan <code>/reactivate-account</code> sayfası; kullanıcı adı/e-posta + şifre ile hesap tekrar açılır ve giriş yapılır.</p></li></ul><h3>Hesabı kalıcı kapat</h3><ul><li><p>Kullanıcı profil ayarlarından hesabını <strong>kalıcı olarak kapatabilir</strong>.</p></li><li><p>İşlem: Mevcut şifre + onay metni (TR: <strong>KAPAT</strong>, EN: <strong>CLOSE</strong>) ile iki aşamalı onay → <code>closed_at = now()</code> → oturum kapatılır.</p></li><li><p>Kalıcı kapatılan hesap <strong>tekrar açılamaz</strong>; giriş denemelerinde “Bu hesap kalıcı olarak kapatılmıştır” mesajı gösterilir.</p></li></ul><h2><br></h2>', '<p>Bu doküman, kullanıcının <strong>kendi hesabını askıya alması</strong> (geçici) ve <strong>kalıcı kapatması</strong> özelliklerini açıklar. Askıya alınan hesap daha sonra tekrar açılabilir; kapatılan hesap kesinlikle açılamaz.</p><h3>Hesabı askıya al (geçici)</h3><ul><li><p>Kullanıcı profil ayarlarından hesabını <strong>geçici olarak askıya alabilir</strong>.</p></li><li><p>İşlem: Mevcut şifre ile onay → <code>is_suspended = 1</code>, <code>suspended_at = now()</code> → oturum kapatılır, giriş sayfasına yönlendirilir.</p></li><li><p><strong>Tekrar açma:</strong> Giriş sayfasında “Hesabımı tekrar aç” bağlantısı veya doğrudan <code>/reactivate-account</code> sayfası; kullanıcı adı/e-posta + şifre ile hesap tekrar açılır ve giriş yapılır.</p></li></ul><h3>Hesabı kalıcı kapat</h3><ul><li><p>Kullanıcı profil ayarlarından hesabını <strong>kalıcı olarak kapatabilir</strong>.</p></li><li><p>İşlem: Mevcut şifre + onay metni (TR: <strong>KAPAT</strong>, EN: <strong>CLOSE</strong>) ile iki aşamalı onay → <code>closed_at = now()</code> → oturum kapatılır.</p></li><li><p>Kalıcı kapatılan hesap <strong>tekrar açılamaz</strong>; giriş denemelerinde “Bu hesap kalıcı olarak kapatılmıştır” mesajı gösterilir.</p></li></ul><h2><br></h2>', 1, 0, 1, '2026-02-26 21:03:33', '2026-03-02 22:29:03', NULL, NULL, 0, NULL, NULL, NULL),
(61, 24, NULL, 1, '<p>Modern sistemler, Gelişmiş yönetimi, ve Katı güvenlik kuralları nedeni ile TWIG tema motoru tercih edilmiş ve Tüm forum tema sistemi twig altyapısına taşınmıştır.</p><p><br></p><p>Alttaki cevabı da Gemini verdi :) Bir bildiği var demekki</p><blockquote><p><strong>Güvenlik (Sandbox) Duvarı</strong> Blade içine <code>@php</code> tagı ile doğrudan raw PHP yazılabilir. Müşterilerin kendi temasını düzenleyeceği bir SaaS platformunda Blade kullanırsan sunucuyu ilk günden patlatırlar. Twig\'in izole \"Sandbox\" modu vardır; dışarıdan müdahale eden biri sadece senin izin verdiğin değişkenleri okuyabilir, sistemi hackleyemez.</p><p><strong>Spagetti Koda Geçit Yok</strong> Blade çok laçkadır, view dosyasının içine Controller mantığı ve veritabanı sorgusu yazmaya bile müsaade eder. Twig katıdır, tasarım ile backend kodunu jilet gibi ayırır.</p><p><strong>Symfony Kanı</strong> MegaforBB altyapısında zaten Symfony mimarisini harmanlıyorsun Twig de doğrudan Symfony\'nin kendi ana motorudur.</p></blockquote><p><br></p><p>oy:</p>', '<p>Modern sistemler, Gelişmiş yönetimi, ve Katı güvenlik kuralları nedeni ile TWIG tema motoru tercih edilmiş ve Tüm forum tema sistemi twig altyapısına taşınmıştır.</p><p><br></p><p>Alttaki cevabı da Gemini verdi :) Bir bildiği var demekki</p><blockquote><p><strong>Güvenlik (Sandbox) Duvarı</strong> Blade içine <code>@php</code> tagı ile doğrudan raw PHP yazılabilir. Müşterilerin kendi temasını düzenleyeceği bir SaaS platformunda Blade kullanırsan sunucuyu ilk günden patlatırlar. Twig\'in izole \"Sandbox\" modu vardır; dışarıdan müdahale eden biri sadece senin izin verdiğin değişkenleri okuyabilir, sistemi hackleyemez.</p><p><strong>Spagetti Koda Geçit Yok</strong> Blade çok laçkadır, view dosyasının içine Controller mantığı ve veritabanı sorgusu yazmaya bile müsaade eder. Twig katıdır, tasarım ile backend kodunu jilet gibi ayırır.</p><p><strong>Symfony Kanı</strong> MegaforBB altyapısında zaten Symfony mimarisini harmanlıyorsun Twig de doğrudan Symfony\'nin kendi ana motorudur.</p></blockquote><p><br></p><p>oy:</p>', 0, 0, 0, '2026-02-26 21:06:49', '2026-02-26 21:12:41', '2026-02-26 21:12:41', 1, 4, NULL, NULL, NULL),
(62, 20, NULL, 1, '<p>İlgil sorun çözüldü.</p>', '<p>İlgil sorun çözüldü.</p>', 0, 0, 0, '2026-02-26 21:20:12', '2026-02-26 21:20:12', NULL, NULL, 0, NULL, NULL, NULL),
(63, 25, NULL, 131, '<p>Bildirim sistemindeki hata  header\'da bildirim görünüyor ancak bildirimler sayfasında hiç görünmüyor bildirim. onun da incelenmesi lazım.</p>', '<p>Bildirim sistemindeki hata  header\'da bildirim görünüyor ancak bildirimler sayfasında hiç görünmüyor bildirim. onun da incelenmesi lazım.</p>', 1, 0, 0, '2026-02-26 21:43:00', '2026-02-27 15:56:01', NULL, NULL, 0, NULL, NULL, NULL),
(64, 25, NULL, 1, '<p><br></p><p><strong>kaan yazdı:</strong></p><blockquote><p><br></p><p>Bildirim sisteminde ufak bir hata var, Bildirimleri okundu işaretle - Tümünü okundu işaretle yapamıyoruz dolayısıyla belirli aralıklarla bildirimler gösteriliyor sağ üstte.Sorun teşkil edecek birşey değil ama düzeltilmesi iyi olur.</p></blockquote><p><del>Bildirim sisteminde hata çözüldü.</del></p>', '<p><br></p><p><strong>kaan yazdı:</strong></p><blockquote><p><br></p><p>Bildirim sisteminde ufak bir hata var, Bildirimleri okundu işaretle - Tümünü okundu işaretle yapamıyoruz dolayısıyla belirli aralıklarla bildirimler gösteriliyor sağ üstte.Sorun teşkil edecek birşey değil ama düzeltilmesi iyi olur.</p></blockquote><p><del>Bildirim sisteminde hata çözüldü.</del></p>', 1, 0, 0, '2026-02-27 15:55:56', '2026-02-27 15:56:39', NULL, NULL, 0, NULL, NULL, NULL),
(65, 22, NULL, 131, '<p>Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap edecek bir sistem olmasa da olur. Herkesi memnun edemezseniz 😎</p>', '<p>Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap edecek bir sistem olmasa da olur. Herkesi memnun edemezseniz 😎</p>', 1, 0, 0, '2026-02-27 15:58:13', '2026-02-28 02:37:12', NULL, NULL, 0, NULL, NULL, NULL),
(66, 29, NULL, 1, '<p>Nu konu içnde Etiket ve dosya ek testi yapıyoruz, sisteme güncelleme geliştirme olarak ekledik. </p><p>Tüm yenilikleri bu şekilde test ortamında paylaşıyoruz.</p><p><br></p><p><br></p>', '<p>Nu konu içnde Etiket ve dosya ek testi yapıyoruz, sisteme güncelleme geliştirme olarak ekledik. </p><p>Tüm yenilikleri bu şekilde test ortamında paylaşıyoruz.</p><p><br></p><p><br></p>', 1, 0, 1, '2026-02-27 16:14:53', '2026-03-01 23:25:16', NULL, NULL, 0, NULL, NULL, NULL),
(67, 30, NULL, 1, '<p>Mesaj gönderilirken kullanıcı yazıp mesajı yazıp gönder dediğimizde: Mesaj gönderilemedi. </p><p><br></p><p>Alpine.js sorunu olduğu  konsoldaki hatalardan anlaşılıyor.</p>', '<p>Mesaj gönderilirken kullanıcı yazıp mesajı yazıp gönder dediğimizde: Mesaj gönderilemedi. </p><p><br></p><p>Alpine.js sorunu olduğu  konsoldaki hatalardan anlaşılıyor.</p>', 0, 0, 1, '2026-02-27 16:31:36', '2026-02-27 16:31:36', NULL, NULL, 0, NULL, NULL, NULL),
(68, 30, NULL, 1, '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/e9976083f461a5f7.png\" alt=\"666.png\" contenteditable=\"false\">Sorun çözüldü, Mesaj gönderimi sorunsuz yapılıyor.</p><p><br></p><p>Kendim yazıp kendim çözüyorum :) </p>', '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/e9976083f461a5f7.png\" alt=\"666.png\" contenteditable=\"false\">Sorun çözüldü, Mesaj gönderimi sorunsuz yapılıyor.</p><p><br></p><p>Kendim yazıp kendim çözüyorum :) </p>', 0, 0, 0, '2026-02-27 18:08:37', '2026-02-27 18:15:20', '2026-02-27 18:15:20', 1, 1, NULL, NULL, NULL),
(69, 31, NULL, 129, '<p>Forum sisteminde SEF Url desteği olması gerekiyor şu anda .com.tr/topic/9 şeklinde görünüyor konular.</p>', '<p>Forum sisteminde SEF Url desteği olması gerekiyor şu anda .com.tr/topic/9 şeklinde görünüyor konular.</p>', 0, 0, 1, '2026-02-28 01:58:35', '2026-02-28 02:18:33', '2026-02-28 02:18:33', 129, 1, NULL, NULL, NULL),
(70, 31, NULL, 1, '<p>Şu anda sistemde bu özellik var zaten 3 kademeli çalışıyor;</p><ol><li><p>1- Sef:rakam</p></li><li><p>2-Sef:başlık</p></li><li><p>3-Random karakter</p></li></ol><p><br></p><ul><li><p>1 şu senin bahsettiğin,</p></li><li><p>2: Standart SEF url tarzı her yerde gördüğümüz alışık olduğumuz sistem konu başlığını temizleyip sefurl yapıyor.</p></li><li><p>3: Bu random sistem ise Google sıralama veya seo umrunda olmayan tamamen kendi amacına hitap eden forumlar için geçerli.</p></li></ul><p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/a481eb839a3609e7.png\" alt=\"sef-url.png\" contenteditable=\"false\"><br></p>', '<p>Şu anda sistemde bu özellik var zaten 3 kademeli çalışıyor;</p><ol><li><p>1- Sef:rakam</p></li><li><p>2-Sef:başlık</p></li><li><p>3-Random karakter</p></li></ol><p><br></p><ul><li><p>1 şu senin bahsettiğin,</p></li><li><p>2: Standart SEF url tarzı her yerde gördüğümüz alışık olduğumuz sistem konu başlığını temizleyip sefurl yapıyor.</p></li><li><p>3: Bu random sistem ise Google sıralama veya seo umrunda olmayan tamamen kendi amacına hitap eden forumlar için geçerli.</p></li></ul><p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/02/a481eb839a3609e7.png\" alt=\"sef-url.png\" contenteditable=\"false\"><br></p>', 0, 0, 0, '2026-02-28 02:07:24', '2026-02-28 20:11:42', '2026-02-28 20:11:42', 1, 1, NULL, NULL, NULL),
(71, 22, NULL, 1, '<p><br></p><p><br></p><blockquote><p><strong>slaweally yazdı:</strong></p><p><br></p><p>Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap edecek bir sistem olmasa da olur. Herkesi memnun edemezseniz 😎</p></blockquote><p>Şu anda kurulum sistemi ile ilgili bir durum yok, sistemin henüz piyasaya sürülme durumu belirsizliğini koruyor çünkü :)</p>', '<p><br></p><p><br></p><blockquote><p><strong>slaweally yazdı:</strong></p><p><br></p><p>Kurulum sistemi olmasına gerek yok. Herkese tüm kullanıcılara hitap edecek bir sistem olmasa da olur. Herkesi memnun edemezseniz 😎</p></blockquote><p>Şu anda kurulum sistemi ile ilgili bir durum yok, sistemin henüz piyasaya sürülme durumu belirsizliğini koruyor çünkü :)</p>', 0, 0, 0, '2026-02-28 02:37:48', '2026-02-28 02:37:48', NULL, NULL, 0, NULL, NULL, NULL),
(72, 32, NULL, 1, '<p>Merhabalar, Yeni bir özellik olarak duyurmak istediğimiz <strong><em>Profil yorumları</em></strong> sistemini Kullanıma sunduk.</p><p>Bu özellik ile birlikte istediğiniz kullanıcının profil sayfasına kullanıcı hakkında yorum yazabilirsiniz</p><p><br></p><p>Not: Kullanıcı isteğe bağlı olarak bu özelliği kullanmak istiyorsa tabiki</p><p><br></p><p>Bu özelliği ilk denemek için benim profilime girenlere önceden not: Ben kapattım :D</p>', '<p>Merhabalar, Yeni bir özellik olarak duyurmak istediğimiz <strong><em>Profil yorumları</em></strong> sistemini Kullanıma sunduk.</p><p>Bu özellik ile birlikte istediğiniz kullanıcının profil sayfasına kullanıcı hakkında yorum yazabilirsiniz</p><p><br></p><p>Not: Kullanıcı isteğe bağlı olarak bu özelliği kullanmak istiyorsa tabiki</p><p><br></p><p>Bu özelliği ilk denemek için benim profilime girenlere önceden not: Ben kapattım :D</p>', 1, 0, 1, '2026-02-28 03:24:13', '2026-02-28 19:44:21', '2026-02-28 19:44:21', 1, 2, NULL, NULL, NULL),
(74, 34, NULL, 1, '<p>Bu mesajı planlanmış konu testi için yazıyorum: 23:07</p><p><br></p><p>Yayın tarihine ise:23:09 olarak ayarlıyorum.</p>', '<p>Bu mesajı planlanmış konu testi için yazıyorum: 23:07</p><p><br></p><p>Yayın tarihine ise:23:09 olarak ayarlıyorum.</p>', 0, 0, 1, '2026-02-28 23:09:00', '2026-03-04 15:52:24', NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `posts` (`id`, `topic_id`, `reply_to_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(76, 36, NULL, 1, '<p><strong>cPanel mi Plesk mi Tercih Edilmeli? Detaylı İnceleme ve Karşılaştırma</strong></p><p>Web hosting kontrol panelleri arasında en popüler iki seçenek olan <strong>cPanel</strong> ve <strong>Plesk</strong>, web sitelerini yönetmek isteyenler için güçlü ve etkili araçlar sunar. Ancak hangi kontrol panelini tercih etmeniz gerektiği konusu, ihtiyaçlarınıza ve bütçenize bağlı olarak değişebilir. Bu yazıda, <strong>cPanel</strong> ve <strong>Plesk</strong> kontrol panellerini detaylıca karşılaştırarak, artılarını, eksilerini, fiyat-performans analizini ve hangi kullanıcılar için daha uygun olduklarını inceleyeceğiz.</p><h2><strong>cPanel Nedir?</strong></h2><p><strong>cPanel</strong>, Linux tabanlı web hosting kontrol panelidir ve özellikle <strong>CentOS, CloudLinux</strong> ve <strong>Red Hat Enterprise Linux</strong> işletim sistemleriyle uyumlu olarak çalışır. Kullanıcı dostu arayüzü ve geniş özellik yelpazesiyle web sitesi sahipleri, geliştiriciler ve hosting sağlayıcıları arasında popülerdir.</p><h2><strong>Plesk Nedir?</strong></h2><p><strong>Plesk</strong>, hem <strong>Linux</strong> hem de <strong>Windows</strong> tabanlı sunucularda çalışabilen çok yönlü bir web hosting kontrol panelidir. Bu özelliği sayesinde daha esnek bir kullanım sunar ve özellikle <strong>ASP.NET</strong> ve <strong>MS SQL</strong> tabanlı uygulamalar geliştirenler için ideal bir seçimdir.</p><h2><strong>cPanel ve Plesk Karşılaştırması: Artılar ve Eksiler</strong></h2><h3><strong>1. Kullanıcı Arayüzü ve Kullanım Kolaylığı</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Artıları:</strong> Oldukça kullanıcı dostu ve sezgisel bir arayüze sahiptir. Özellikle başlangıç düzeyindeki kullanıcılar için idealdir. Menüleri açık ve anlaşılırdır.</p></li><li><p><strong>Eksileri:</strong> İlk bakışta karmaşık gelebilir, ancak kısa sürede alışmak mümkündür. Sadece <strong>Linux</strong> tabanlı sunucularda çalıştığı için <strong>Windows</strong> kullanıcıları için uygun değildir.</p></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Artıları:</strong> Daha modern ve temiz bir arayüze sahip olup, hem <strong>Linux</strong> hem de <strong>Windows</strong> sunucularda çalışabilir. Özellikle <strong>WordPress</strong> kullanıcıları için optimize edilmiş araçlar içerir.</p></li><li><p><strong>Eksileri:</strong> cPanel’e göre daha fazla menü ve ayar barındırdığından, yeni başlayan kullanıcılar için kafa karıştırıcı olabilir.</p></li></ul></li></ul><h3><strong>2. Özellikler ve İşlevsellik</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Artıları:</strong></p><ul><li><p><strong>WHM</strong> (Web Host Manager) ile gelişmiş yönetim ve özelleştirme seçenekleri sunar.</p></li><li><p>Güçlü <strong>e-posta yönetimi</strong>, <strong>veritabanı yönetimi (MySQL ve PostgreSQL)</strong> ve <strong>yedekleme</strong> özelliklerine sahiptir.</p></li><li><p><strong>Softaculous</strong> entegrasyonu ile 400’den fazla script ve uygulamayı kolayca kurma imkanı tanır.</p></li></ul></li><li><p><strong>Eksileri:</strong></p><ul><li><p><strong>ASP.NET</strong> ve <strong>MS SQL</strong> desteği olmadığı için <strong>Windows</strong> tabanlı uygulamalarla uyumlu değildir.</p></li><li><p>Yedekleme işlemleri Plesk’e göre daha yavaş olabilir.</p></li></ul></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Artıları:</strong></p><ul><li><p><strong>ASP.NET, MS SQL</strong> ve <strong>IIS</strong> desteği ile <strong>Windows</strong> tabanlı uygulamalar için mükemmeldir.</p></li><li><p><strong>Docker</strong> ve <strong>Git</strong> entegrasyonları ile geliştiriciler için daha esnek bir çalışma ortamı sunar.</p></li><li><p><strong>WordPress Toolkit</strong> ile WordPress sitelerini yönetmek, güncellemeleri takip etmek ve güvenliği sağlamak daha kolaydır.</p></li></ul></li><li><p><strong>Eksileri:</strong> Bazı gelişmiş özellikler için ekstra lisanslama gerekebilir.</p></li></ul></li></ul><h3><strong>3. Güvenlik ve Güncellemeler</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Artıları:</strong> <strong>CSF (ConfigServer Security &amp; Firewall)</strong> ve <strong>cPHulk</strong> ile gelişmiş güvenlik seçenekleri sunar. Ayrıca <strong>AutoSSL</strong> özelliği ile ücretsiz SSL sertifikalarını otomatik olarak yeniler.</p></li><li><p><strong>Eksileri:</strong> Güvenlik özelliklerini etkinleştirmek ve yapılandırmak biraz teknik bilgi gerektirebilir.</p></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Artıları:</strong> <strong>ModSecurity Web Application Firewall</strong>, <strong>Fail2Ban</strong> ve <strong>SSL It!</strong> eklentileriyle güçlü güvenlik önlemleri sunar.</p></li><li><p><strong>Eksileri:</strong> Bazı güvenlik özellikleri varsayılan olarak kapalı gelir ve manuel olarak etkinleştirilmelidir.</p></li></ul></li></ul><h3><strong>4. Performans ve Hız</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Artıları:</strong> <strong>LiteSpeed</strong> ve <strong>CloudLinux</strong> ile entegre çalışarak yüksek performans sağlar. Özellikle <strong>MySQL</strong> veritabanı yönetimi konusunda hızlıdır.</p></li><li><p><strong>Eksileri:</strong> Çok fazla eklenti kullanıldığında performans düşebilir.</p></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Artıları:</strong> <strong>Nginx</strong> ve <strong>Apache</strong> web sunucuları ile uyumlu çalışarak yüksek performans sunar. <strong>Redis</strong> ve <strong>Memcached</strong> desteği ile önbellekleme konusunda daha hızlıdır.</p></li><li><p><strong>Eksileri:</strong> Özellikle Windows sunucularda yoğun yük altında performans kaybı yaşanabilir.</p></li></ul></li></ul><h3><strong>5. Fiyatlandırma ve Lisanslama</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Fiyat:</strong> Lisanslama, hesap başına ücretlendirilir. 5 hesaplı lisans yaklaşık <strong>15-20 dolar/ay</strong> iken, 100 hesaplı lisans <strong>45-50 dolar/ay</strong> civarındadır.</p></li><li><p><strong>Avantaj:</strong> Küçük işletmeler ve bireysel kullanıcılar için daha uygun fiyatlıdır.</p></li><li><p><strong>Dezavantaj:</strong> Çok sayıda hesap yöneten hosting şirketleri için maliyetli olabilir.</p></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Fiyat:</strong> Daha esnek lisans seçenekleri sunar. <strong>Web Admin (10 domain)</strong> lisansı <strong>10 dolar/ay</strong>, <strong>Web Pro (30 domain)</strong> lisansı <strong>15 dolar/ay</strong>, <strong>Web Host (sınırsız domain)</strong> lisansı <strong>35 dolar/ay</strong> civarındadır.</p></li><li><p><strong>Avantaj:</strong> Özellikle çok sayıda domain yöneten ajanslar ve geliştiriciler için daha hesaplıdır.</p></li><li><p><strong>Dezavantaj:</strong> Ek özellikler için ekstra lisanslama gerekebilir.</p></li></ul></li></ul><h2><strong>Hangi Kullanıcılar İçin Daha Uygun?</strong></h2><ul><li><p><strong>cPanel Tercih Etmesi Gerekenler:</strong></p><ul><li><p>Sadece <strong>Linux</strong> tabanlı hosting kullanacak olanlar.</p></li><li><p>Basit ve kullanıcı dostu bir arayüz isteyen başlangıç düzeyindeki kullanıcılar.</p></li><li><p>Küçük işletmeler ve bireysel web sitesi sahipleri.</p></li></ul></li><li><p><strong>Plesk Tercih Etmesi Gerekenler:</strong></p><ul><li><p><strong>Windows</strong> ve <strong>Linux</strong> sunucuları bir arada yönetmek isteyenler.</p></li><li><p><strong>ASP.NET, MS SQL</strong> veya <strong>Docker</strong> gibi gelişmiş özellikleri kullanacak olanlar.</p></li><li><p>WordPress yöneticileri ve ajanslar.</p></li></ul></li></ul><h2><strong>Sonuç: cPanel mi Plesk mi Daha İyi?</strong></h2><ul><li><p><strong>cPanel</strong>: Basitlik ve kullanıcı dostu arayüzüyle <strong>Linux kullanıcıları</strong> için ideal.</p></li><li><p><strong>Plesk</strong>: <strong>Windows</strong> ve <strong>Linux</strong> platformlarını bir arada kullanmak isteyen, <strong>WordPress</strong> yöneten ajanslar için daha uygun.</p></li></ul><p>Özetle, <strong>Linux kullanıcıları ve basit arayüz isteyenler için cPanel</strong>, <strong>daha esnek özellikler ve Windows desteği isteyenler için Plesk</strong> daha iyi bir tercih olacaktır.</p>', '<p><strong>cPanel mi Plesk mi Tercih Edilmeli? Detaylı İnceleme ve Karşılaştırma</strong></p><p>Web hosting kontrol panelleri arasında en popüler iki seçenek olan <strong>cPanel</strong> ve <strong>Plesk</strong>, web sitelerini yönetmek isteyenler için güçlü ve etkili araçlar sunar. Ancak hangi kontrol panelini tercih etmeniz gerektiği konusu, ihtiyaçlarınıza ve bütçenize bağlı olarak değişebilir. Bu yazıda, <strong>cPanel</strong> ve <strong>Plesk</strong> kontrol panellerini detaylıca karşılaştırarak, artılarını, eksilerini, fiyat-performans analizini ve hangi kullanıcılar için daha uygun olduklarını inceleyeceğiz.</p><h2><strong>cPanel Nedir?</strong></h2><p><strong>cPanel</strong>, Linux tabanlı web hosting kontrol panelidir ve özellikle <strong>CentOS, CloudLinux</strong> ve <strong>Red Hat Enterprise Linux</strong> işletim sistemleriyle uyumlu olarak çalışır. Kullanıcı dostu arayüzü ve geniş özellik yelpazesiyle web sitesi sahipleri, geliştiriciler ve hosting sağlayıcıları arasında popülerdir.</p><h2><strong>Plesk Nedir?</strong></h2><p><strong>Plesk</strong>, hem <strong>Linux</strong> hem de <strong>Windows</strong> tabanlı sunucularda çalışabilen çok yönlü bir web hosting kontrol panelidir. Bu özelliği sayesinde daha esnek bir kullanım sunar ve özellikle <strong>ASP.NET</strong> ve <strong>MS SQL</strong> tabanlı uygulamalar geliştirenler için ideal bir seçimdir.</p><h2><strong>cPanel ve Plesk Karşılaştırması: Artılar ve Eksiler</strong></h2><h3><strong>1. Kullanıcı Arayüzü ve Kullanım Kolaylığı</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Artıları:</strong> Oldukça kullanıcı dostu ve sezgisel bir arayüze sahiptir. Özellikle başlangıç düzeyindeki kullanıcılar için idealdir. Menüleri açık ve anlaşılırdır.</p></li><li><p><strong>Eksileri:</strong> İlk bakışta karmaşık gelebilir, ancak kısa sürede alışmak mümkündür. Sadece <strong>Linux</strong> tabanlı sunucularda çalıştığı için <strong>Windows</strong> kullanıcıları için uygun değildir.</p></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Artıları:</strong> Daha modern ve temiz bir arayüze sahip olup, hem <strong>Linux</strong> hem de <strong>Windows</strong> sunucularda çalışabilir. Özellikle <strong>WordPress</strong> kullanıcıları için optimize edilmiş araçlar içerir.</p></li><li><p><strong>Eksileri:</strong> cPanel’e göre daha fazla menü ve ayar barındırdığından, yeni başlayan kullanıcılar için kafa karıştırıcı olabilir.</p></li></ul></li></ul><h3><strong>2. Özellikler ve İşlevsellik</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Artıları:</strong></p><ul><li><p><strong>WHM</strong> (Web Host Manager) ile gelişmiş yönetim ve özelleştirme seçenekleri sunar.</p></li><li><p>Güçlü <strong>e-posta yönetimi</strong>, <strong>veritabanı yönetimi (MySQL ve PostgreSQL)</strong> ve <strong>yedekleme</strong> özelliklerine sahiptir.</p></li><li><p><strong>Softaculous</strong> entegrasyonu ile 400’den fazla script ve uygulamayı kolayca kurma imkanı tanır.</p></li></ul></li><li><p><strong>Eksileri:</strong></p><ul><li><p><strong>ASP.NET</strong> ve <strong>MS SQL</strong> desteği olmadığı için <strong>Windows</strong> tabanlı uygulamalarla uyumlu değildir.</p></li><li><p>Yedekleme işlemleri Plesk’e göre daha yavaş olabilir.</p></li></ul></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Artıları:</strong></p><ul><li><p><strong>ASP.NET, MS SQL</strong> ve <strong>IIS</strong> desteği ile <strong>Windows</strong> tabanlı uygulamalar için mükemmeldir.</p></li><li><p><strong>Docker</strong> ve <strong>Git</strong> entegrasyonları ile geliştiriciler için daha esnek bir çalışma ortamı sunar.</p></li><li><p><strong>WordPress Toolkit</strong> ile WordPress sitelerini yönetmek, güncellemeleri takip etmek ve güvenliği sağlamak daha kolaydır.</p></li></ul></li><li><p><strong>Eksileri:</strong> Bazı gelişmiş özellikler için ekstra lisanslama gerekebilir.</p></li></ul></li></ul><h3><strong>3. Güvenlik ve Güncellemeler</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Artıları:</strong> <strong>CSF (ConfigServer Security &amp; Firewall)</strong> ve <strong>cPHulk</strong> ile gelişmiş güvenlik seçenekleri sunar. Ayrıca <strong>AutoSSL</strong> özelliği ile ücretsiz SSL sertifikalarını otomatik olarak yeniler.</p></li><li><p><strong>Eksileri:</strong> Güvenlik özelliklerini etkinleştirmek ve yapılandırmak biraz teknik bilgi gerektirebilir.</p></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Artıları:</strong> <strong>ModSecurity Web Application Firewall</strong>, <strong>Fail2Ban</strong> ve <strong>SSL It!</strong> eklentileriyle güçlü güvenlik önlemleri sunar.</p></li><li><p><strong>Eksileri:</strong> Bazı güvenlik özellikleri varsayılan olarak kapalı gelir ve manuel olarak etkinleştirilmelidir.</p></li></ul></li></ul><h3><strong>4. Performans ve Hız</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Artıları:</strong> <strong>LiteSpeed</strong> ve <strong>CloudLinux</strong> ile entegre çalışarak yüksek performans sağlar. Özellikle <strong>MySQL</strong> veritabanı yönetimi konusunda hızlıdır.</p></li><li><p><strong>Eksileri:</strong> Çok fazla eklenti kullanıldığında performans düşebilir.</p></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Artıları:</strong> <strong>Nginx</strong> ve <strong>Apache</strong> web sunucuları ile uyumlu çalışarak yüksek performans sunar. <strong>Redis</strong> ve <strong>Memcached</strong> desteği ile önbellekleme konusunda daha hızlıdır.</p></li><li><p><strong>Eksileri:</strong> Özellikle Windows sunucularda yoğun yük altında performans kaybı yaşanabilir.</p></li></ul></li></ul><h3><strong>5. Fiyatlandırma ve Lisanslama</strong></h3><ul><li><p><strong>cPanel</strong>:</p><ul><li><p><strong>Fiyat:</strong> Lisanslama, hesap başına ücretlendirilir. 5 hesaplı lisans yaklaşık <strong>15-20 dolar/ay</strong> iken, 100 hesaplı lisans <strong>45-50 dolar/ay</strong> civarındadır.</p></li><li><p><strong>Avantaj:</strong> Küçük işletmeler ve bireysel kullanıcılar için daha uygun fiyatlıdır.</p></li><li><p><strong>Dezavantaj:</strong> Çok sayıda hesap yöneten hosting şirketleri için maliyetli olabilir.</p></li></ul></li><li><p><strong>Plesk</strong>:</p><ul><li><p><strong>Fiyat:</strong> Daha esnek lisans seçenekleri sunar. <strong>Web Admin (10 domain)</strong> lisansı <strong>10 dolar/ay</strong>, <strong>Web Pro (30 domain)</strong> lisansı <strong>15 dolar/ay</strong>, <strong>Web Host (sınırsız domain)</strong> lisansı <strong>35 dolar/ay</strong> civarındadır.</p></li><li><p><strong>Avantaj:</strong> Özellikle çok sayıda domain yöneten ajanslar ve geliştiriciler için daha hesaplıdır.</p></li><li><p><strong>Dezavantaj:</strong> Ek özellikler için ekstra lisanslama gerekebilir.</p></li></ul></li></ul><h2><strong>Hangi Kullanıcılar İçin Daha Uygun?</strong></h2><ul><li><p><strong>cPanel Tercih Etmesi Gerekenler:</strong></p><ul><li><p>Sadece <strong>Linux</strong> tabanlı hosting kullanacak olanlar.</p></li><li><p>Basit ve kullanıcı dostu bir arayüz isteyen başlangıç düzeyindeki kullanıcılar.</p></li><li><p>Küçük işletmeler ve bireysel web sitesi sahipleri.</p></li></ul></li><li><p><strong>Plesk Tercih Etmesi Gerekenler:</strong></p><ul><li><p><strong>Windows</strong> ve <strong>Linux</strong> sunucuları bir arada yönetmek isteyenler.</p></li><li><p><strong>ASP.NET, MS SQL</strong> veya <strong>Docker</strong> gibi gelişmiş özellikleri kullanacak olanlar.</p></li><li><p>WordPress yöneticileri ve ajanslar.</p></li></ul></li></ul><h2><strong>Sonuç: cPanel mi Plesk mi Daha İyi?</strong></h2><ul><li><p><strong>cPanel</strong>: Basitlik ve kullanıcı dostu arayüzüyle <strong>Linux kullanıcıları</strong> için ideal.</p></li><li><p><strong>Plesk</strong>: <strong>Windows</strong> ve <strong>Linux</strong> platformlarını bir arada kullanmak isteyen, <strong>WordPress</strong> yöneten ajanslar için daha uygun.</p></li></ul><p>Özetle, <strong>Linux kullanıcıları ve basit arayüz isteyenler için cPanel</strong>, <strong>daha esnek özellikler ve Windows desteği isteyenler için Plesk</strong> daha iyi bir tercih olacaktır.</p>', 0, 0, 1, '2026-03-01 04:09:11', '2026-03-02 22:25:31', NULL, NULL, 0, NULL, NULL, NULL),
(77, 37, NULL, 1, '<p>Sunucu durumu (server status), bir sunucunun mevcut çalışma durumunu ve performansını gösteren önemli bir metriktir. Özellikle web siteleri ve çevrimiçi hizmetler için sunucunun sürekli erişilebilir olması, kullanıcı memnuniyeti ve iş sürekliliği açısından kritik öneme sahiptir. Bu bağlamda, sunucunun ne kadar süre kesintisiz çalıştığını ifade eden “uptime” kavramı ve bu süreyi izlemek için kullanılan araçlar büyük bir önem taşır.</p><h2>Uptime Nedir?</h2><p>Uptime, bir sunucunun veya sistemin kesintisiz olarak çalıştığı süreyi ifade eder. Yüksek bir uptime oranı, sunucunun güvenilirliğini ve istikrarını gösterirken, düşük bir oran olası teknik sorunlara veya bakım gereksinimlerine işaret edebilir. Uptime oranı genellikle yüzde (%) olarak ifade edilir ve şu şekilde hesaplanır:</p><div data-language=\"text\" class=\"toastui-editor-ww-code-block\"><pre><code>Uptime Oranı (%) = (Çalışma Süresi / Toplam Süre) x 100\r\n<br></code></pre></div><p>Örneğin, bir yıl içinde (365 gün x 24 saat = 8760 saat) toplamda 4 saatlik bir kesinti yaşandıysa, uptime oranı şu şekilde hesaplanır:</p><div data-language=\"text\" class=\"toastui-editor-ww-code-block\"><pre><code>Uptime Oranı (%) = [(8760 - 4) / 8760] x 100 ≈ %99,95\r\n<br></code></pre></div><p>Bu hesaplama, sunucunun yıl boyunca %99,95 oranında kesintisiz çalıştığını gösterir.</p><h2>Uptime Oranlarının Önemi</h2><p>Yüksek uptime oranları, özellikle e-ticaret siteleri, bankacılık uygulamaları ve diğer kritik hizmetler sunan platformlar için hayati öneme sahiptir. Düşük bir uptime oranı, müşteri memnuniyetsizliğine, gelir kaybına ve marka itibarının zedelenmesine yol açabilir. Bu nedenle, sunucu ve hizmet sağlayıcıları genellikle %99 ve üzeri uptime oranları sunmayı taahhüt ederler.</p><h2>FreeUptime.org ile Uptime İzleme</h2><p>Sunucu ve web sitelerinin uptime durumunu izlemek için çeşitli araçlar bulunmaktadır. Bu araçlar, sunucunun ne kadar süre çevrimiçi olduğunu, olası kesintileri ve performans metriklerini takip etmeye yardımcı olur. Bu bağlamda, <a href=\"https://freeuptime.org/\">FreeUptime.org</a> gibi platformlar, kullanıcıların sunucularını ve web sitelerini etkin bir şekilde izlemelerine olanak tanır.</p><h3>FreeUptime.org Nedir?</h3><p>FreeUptime.org, web siteleri, sunucular ve çeşitli hizmetlerin çalışma sürelerini izlemek ve durum sayfaları oluşturmak için kullanılan ücretsiz bir izleme platformudur. Kullanıcı dostu arayüzü ve kapsamlı özellikleri sayesinde, sunucu yöneticileri ve web sitesi sahipleri için vazgeçilmez bir araçtır.</p><h3>FreeUptime.org’un Özellikleri</h3><ul><li><p><strong>Çoklu Konumdan İzleme:</strong> FreeUptime.org, izlenen hizmetleri dünya genelindeki farklı konumlardan kontrol ederek, global erişilebilirliği sağlar.</p></li><li><p><strong>Özel HTTP İstekleri:</strong> Kullanıcılar, özel istek yöntemleri, istek gövdeleri, temel kimlik doğrulama ve özel istek başlıkları tanımlayarak detaylı izleme yapabilirler.</p></li><li><p><strong>Özel HTTP Yanıtları:</strong> Belirli bir yanıt bekleyerek, hizmetlerin doğru çalışıp çalışmadığını doğrulayabilirsiniz.</p></li><li><p><strong>E-posta Bildirimleri:</strong> Hizmetlerinizde bir kesinti veya sorun olduğunda anında e-posta bildirimleri alarak hızlı müdahale imkanı elde edersiniz.</p></li><li><p><strong>Proje Yönetimi:</strong> Farklı projelerinizi kategorize ederek, her birini ayrı ayrı izleyebilir ve yönetebilirsiniz.</p></li><li><p><strong>Durum Sayfaları:</strong> Ziyaretçilerinize ve müşterilerinize şeffaflık sağlamak için izleme istatistiklerinizi gösteren özel durum sayfaları oluşturabilirsiniz.</p></li><li><p><strong>Olay Yönetimi:</strong> Hizmetlerinizin ne zaman ve ne kadar süreyle erişilemez olduğunu kaydederek, geçmiş olayları analiz edebilirsiniz.</p></li></ul><h3>FreeUptime.org’un Kullanım Alanları</h3><ul><li><p><strong>Web Sitesi Sahipleri:</strong> Web sitelerinin sürekli erişilebilirliğini sağlamak ve olası kesintileri hızlıca tespit etmek için kullanabilirler.</p></li><li><p><strong>Sunucu Yöneticileri:</strong> Sunucularının performansını ve çalışma sürelerini izleyerek, proaktif önlemler alabilirler.</p></li><li><p><strong>Uygulama Geliştiricileri:</strong> API’lerin ve diğer hizmetlerin düzgün çalıştığından emin olmak için izleme yapabilirler.</p></li><li><p><strong>BT Destek Ekipleri:</strong> Müşterilere sunulan hizmetlerin durumunu izleyerek, kesinti durumlarında hızlıca müdahale edebilirler.</p></li></ul><h2><br></h2>', '<p>Sunucu durumu (server status), bir sunucunun mevcut çalışma durumunu ve performansını gösteren önemli bir metriktir. Özellikle web siteleri ve çevrimiçi hizmetler için sunucunun sürekli erişilebilir olması, kullanıcı memnuniyeti ve iş sürekliliği açısından kritik öneme sahiptir. Bu bağlamda, sunucunun ne kadar süre kesintisiz çalıştığını ifade eden “uptime” kavramı ve bu süreyi izlemek için kullanılan araçlar büyük bir önem taşır.</p><h2>Uptime Nedir?</h2><p>Uptime, bir sunucunun veya sistemin kesintisiz olarak çalıştığı süreyi ifade eder. Yüksek bir uptime oranı, sunucunun güvenilirliğini ve istikrarını gösterirken, düşük bir oran olası teknik sorunlara veya bakım gereksinimlerine işaret edebilir. Uptime oranı genellikle yüzde (%) olarak ifade edilir ve şu şekilde hesaplanır:</p><div data-language=\"text\" class=\"toastui-editor-ww-code-block\"><pre><code>Uptime Oranı (%) = (Çalışma Süresi / Toplam Süre) x 100\r\n<br></code></pre></div><p>Örneğin, bir yıl içinde (365 gün x 24 saat = 8760 saat) toplamda 4 saatlik bir kesinti yaşandıysa, uptime oranı şu şekilde hesaplanır:</p><div data-language=\"text\" class=\"toastui-editor-ww-code-block\"><pre><code>Uptime Oranı (%) = [(8760 - 4) / 8760] x 100 ≈ %99,95\r\n<br></code></pre></div><p>Bu hesaplama, sunucunun yıl boyunca %99,95 oranında kesintisiz çalıştığını gösterir.</p><h2>Uptime Oranlarının Önemi</h2><p>Yüksek uptime oranları, özellikle e-ticaret siteleri, bankacılık uygulamaları ve diğer kritik hizmetler sunan platformlar için hayati öneme sahiptir. Düşük bir uptime oranı, müşteri memnuniyetsizliğine, gelir kaybına ve marka itibarının zedelenmesine yol açabilir. Bu nedenle, sunucu ve hizmet sağlayıcıları genellikle %99 ve üzeri uptime oranları sunmayı taahhüt ederler.</p><h2>FreeUptime.org ile Uptime İzleme</h2><p>Sunucu ve web sitelerinin uptime durumunu izlemek için çeşitli araçlar bulunmaktadır. Bu araçlar, sunucunun ne kadar süre çevrimiçi olduğunu, olası kesintileri ve performans metriklerini takip etmeye yardımcı olur. Bu bağlamda, <a href=\"https://freeuptime.org/\">FreeUptime.org</a> gibi platformlar, kullanıcıların sunucularını ve web sitelerini etkin bir şekilde izlemelerine olanak tanır.</p><h3>FreeUptime.org Nedir?</h3><p>FreeUptime.org, web siteleri, sunucular ve çeşitli hizmetlerin çalışma sürelerini izlemek ve durum sayfaları oluşturmak için kullanılan ücretsiz bir izleme platformudur. Kullanıcı dostu arayüzü ve kapsamlı özellikleri sayesinde, sunucu yöneticileri ve web sitesi sahipleri için vazgeçilmez bir araçtır.</p><h3>FreeUptime.org’un Özellikleri</h3><ul><li><p><strong>Çoklu Konumdan İzleme:</strong> FreeUptime.org, izlenen hizmetleri dünya genelindeki farklı konumlardan kontrol ederek, global erişilebilirliği sağlar.</p></li><li><p><strong>Özel HTTP İstekleri:</strong> Kullanıcılar, özel istek yöntemleri, istek gövdeleri, temel kimlik doğrulama ve özel istek başlıkları tanımlayarak detaylı izleme yapabilirler.</p></li><li><p><strong>Özel HTTP Yanıtları:</strong> Belirli bir yanıt bekleyerek, hizmetlerin doğru çalışıp çalışmadığını doğrulayabilirsiniz.</p></li><li><p><strong>E-posta Bildirimleri:</strong> Hizmetlerinizde bir kesinti veya sorun olduğunda anında e-posta bildirimleri alarak hızlı müdahale imkanı elde edersiniz.</p></li><li><p><strong>Proje Yönetimi:</strong> Farklı projelerinizi kategorize ederek, her birini ayrı ayrı izleyebilir ve yönetebilirsiniz.</p></li><li><p><strong>Durum Sayfaları:</strong> Ziyaretçilerinize ve müşterilerinize şeffaflık sağlamak için izleme istatistiklerinizi gösteren özel durum sayfaları oluşturabilirsiniz.</p></li><li><p><strong>Olay Yönetimi:</strong> Hizmetlerinizin ne zaman ve ne kadar süreyle erişilemez olduğunu kaydederek, geçmiş olayları analiz edebilirsiniz.</p></li></ul><h3>FreeUptime.org’un Kullanım Alanları</h3><ul><li><p><strong>Web Sitesi Sahipleri:</strong> Web sitelerinin sürekli erişilebilirliğini sağlamak ve olası kesintileri hızlıca tespit etmek için kullanabilirler.</p></li><li><p><strong>Sunucu Yöneticileri:</strong> Sunucularının performansını ve çalışma sürelerini izleyerek, proaktif önlemler alabilirler.</p></li><li><p><strong>Uygulama Geliştiricileri:</strong> API’lerin ve diğer hizmetlerin düzgün çalıştığından emin olmak için izleme yapabilirler.</p></li><li><p><strong>BT Destek Ekipleri:</strong> Müşterilere sunulan hizmetlerin durumunu izleyerek, kesinti durumlarında hızlıca müdahale edebilirler.</p></li></ul><h2><br></h2>', 0, 0, 1, '2026-03-01 04:09:55', '2026-03-02 22:24:30', NULL, NULL, 0, NULL, NULL, NULL),
(78, 38, NULL, 1, '<p><strong>MegaforBB Forum</strong> sisteminde Haftalık geliştirme planı olarak MArt ayının ilk geniş büyük güncellemesini Döküman sistemi olarak yapıyoruz.</p><p><a href=\"https://docusaurus.io/blog/releases/3.9\">Docusaurus</a> Biliyor olmalısınız, biz bu sistemi yazılım ve ürün geliştiriciler için foruma direkt olarak dahil ediyoruz. Tüm sisteme entegra çalışır ve herhangi bir uyumsuzluk sorunu olmaması için çekirdeğe gömülü olarak gelir.</p><p>Aktif veya kapalı durumda kullanılabileceği için herhangi bir ek yük bindirmeyecektir. </p><p><br></p><p>Merhak edip gezip indelemek isteyenler için: <a href=\"https://www.megaforbb.com.tr/documentation/\">MegaforBB Dokümanlar</a></p><p><br></p><p>Gelişmelerimizi takip etmeye devam edin.</p>', '<p><strong>MegaforBB Forum</strong> sisteminde Haftalık geliştirme planı olarak MArt ayının ilk geniş büyük güncellemesini Döküman sistemi olarak yapıyoruz.</p><p><a href=\"https://docusaurus.io/blog/releases/3.9\">Docusaurus</a> Biliyor olmalısınız, biz bu sistemi yazılım ve ürün geliştiriciler için foruma direkt olarak dahil ediyoruz. Tüm sisteme entegra çalışır ve herhangi bir uyumsuzluk sorunu olmaması için çekirdeğe gömülü olarak gelir.</p><p>Aktif veya kapalı durumda kullanılabileceği için herhangi bir ek yük bindirmeyecektir. </p><p><br></p><p>Merhak edip gezip indelemek isteyenler için: <a href=\"https://www.megaforbb.com.tr/documentation/\">MegaforBB Dokümanlar</a></p><p><br></p><p>Gelişmelerimizi takip etmeye devam edin.</p>', 0, 0, 1, '2026-03-01 16:46:33', '2026-03-01 16:46:33', NULL, NULL, 0, NULL, NULL, NULL),
(79, 5, NULL, 1, '<ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Çevrimiçi üyeleri - Botlar sayfası yapıldı.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p></li><li class=\"task-list-item\" data-task=\"true\"><p>Bugün yamayı planladığım farklı bir şeyler daha var bitmeden yazmayayım :)</p></li></ul>', '<ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Çevrimiçi üyeleri - Botlar sayfası yapıldı.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p></li><li class=\"task-list-item\" data-task=\"true\"><p>Bugün yamayı planladığım farklı bir şeyler daha var bitmeden yazmayayım :)</p></li></ul>', 0, 0, 0, '2026-03-01 17:30:52', '2026-03-01 17:31:32', '2026-03-01 17:31:32', 1, 1, NULL, NULL, NULL),
(80, 39, NULL, 1, '<p>Bu konunun içeriğini sadece konuyu açan kullanıcılar ve yöneticiler görecektir.</p><p><br></p>', '<p>Bu konunun içeriğini sadece konuyu açan kullanıcılar ve yöneticiler görecektir.</p><p><br></p>', 0, 0, 1, '2026-03-01 22:56:42', '2026-03-01 23:13:46', NULL, NULL, 0, NULL, NULL, NULL),
(81, 29, NULL, 129, '<p>Cevap kısmında da dosya yüklenebiliyr olması iyi olmuş</p>', '<p>Cevap kısmında da dosya yüklenebiliyr olması iyi olmuş</p>', 0, 0, 0, '2026-03-01 23:25:10', '2026-03-01 23:25:10', NULL, NULL, 0, NULL, NULL, NULL),
(82, 5, NULL, 1, '<p><br></p><p><br></p><blockquote><p><strong>Sinek10 yazdı:</strong></p><p><br></p><p>Çevrimiçi üyeleri - Botlar sayfası yapıldı.</p><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p><p>Bugün yamayı planladığım farklı bir şeyler daha var bitmeden yazmayayım :)</p></blockquote><p>Tamamlandı, Private konu sistemi: https://www.megaforbb.com.tr/topic/ozel-private-konu-test-39</p>', '<p><br></p><p><br></p><blockquote><p><strong>Sinek10 yazdı:</strong></p><p><br></p><p>Çevrimiçi üyeleri - Botlar sayfası yapıldı.</p><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p><p>Bugün yamayı planladığım farklı bir şeyler daha var bitmeden yazmayayım :)</p></blockquote><p>Tamamlandı, Private konu sistemi: https://www.megaforbb.com.tr/topic/ozel-private-konu-test-39</p>', 0, 0, 0, '2026-03-01 23:28:00', '2026-03-01 23:28:00', NULL, NULL, 0, NULL, NULL, NULL),
(83, 40, NULL, 129, '<p>Bu konda  Anti Bump sisteminin sisteme dahil edildiğini ve  ardışık olarak peş peşe mesaj yazılamayacağını test edeceğiz.</p>', '<p>Bu konda  Anti Bump sisteminin sisteme dahil edildiğini ve  ardışık olarak peş peşe mesaj yazılamayacağını test edeceğiz.</p>', 0, 0, 1, '2026-03-01 23:43:52', '2026-03-01 23:43:52', NULL, NULL, 0, NULL, NULL, NULL),
(84, 40, NULL, 129, '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/03/ad2f8dcf1190106d.png\" alt=\"image.png\" contenteditable=\"false\">Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda 1 dk yeterli.</p>', '<p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/03/ad2f8dcf1190106d.png\" alt=\"image.png\" contenteditable=\"false\">Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda 1 dk yeterli.</p>', 1, 0, 0, '2026-03-01 23:45:42', '2026-03-02 00:07:01', NULL, NULL, 0, NULL, NULL, NULL),
(85, 40, NULL, 1, '<p><br></p><p><br></p><blockquote><p><strong>kaan yazdı:</strong></p><p><br></p><p>Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda 1 dk yeterli.</p></blockquote><p>Eskiler bilir Cotonti\'de yerleşik olarak gelen bir özelliktir bu anti bump sistemi :) Çok beğendiğim birşeydi, neden olmasın ki ?</p>', '<p><br></p><p><br></p><blockquote><p><strong>kaan yazdı:</strong></p><p><br></p><p>Sanırım çalışıyor, Süreyi uzatabiliriz, spam yapılmaması için şu anda 1 dk yeterli.</p></blockquote><p>Eskiler bilir Cotonti\'de yerleşik olarak gelen bir özelliktir bu anti bump sistemi :) Çok beğendiğim birşeydi, neden olmasın ki ?</p>', 1, 0, 0, '2026-03-02 00:00:05', '2026-03-02 00:11:33', NULL, NULL, 0, NULL, NULL, NULL),
(86, 41, NULL, 1, '<p>Bu konuda MegaforBB soru - cevap ve çözüm sistemini test ediyoruz.</p>', '<p>Bu konuda MegaforBB soru - cevap ve çözüm sistemini test ediyoruz.</p>', 0, 0, 1, '2026-03-02 00:14:11', '2026-03-02 01:56:47', NULL, NULL, 0, NULL, NULL, NULL),
(87, 41, NULL, 129, '<p>Şu anda mantık olarak sistemde çalışıyor  ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)</p>', '<p>Şu anda mantık olarak sistemde çalışıyor  ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)</p>', 1, 1, 0, '2026-03-02 00:14:58', '2026-03-02 01:39:13', NULL, NULL, 0, NULL, NULL, NULL),
(88, 41, NULL, 1, '<p><br></p><p><br></p><blockquote><p><strong>kaan yazdı:</strong></p><p><br></p><p>Şu anda mantık olarak sistemde çalışıyor ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)</p></blockquote><p>Haklı olduğun için alıntı yapıp cevap veriyorum :)</p>', '<p><br></p><p><br></p><blockquote><p><strong>kaan yazdı:</strong></p><p><br></p><p>Şu anda mantık olarak sistemde çalışıyor ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)</p></blockquote><p>Haklı olduğun için alıntı yapıp cevap veriyorum :)</p>', 0, 1, 0, '2026-03-02 00:17:59', '2026-03-02 01:31:32', NULL, NULL, 0, NULL, NULL, NULL),
(89, 41, NULL, 129, '<p><br></p><p><br></p><blockquote><p><strong>Sinek10 yazdı:</strong></p><p><br></p><p>kaan yazdı:Şu anda mantık olarak sistemde çalışıyor ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)Haklı olduğun için alıntı yapıp cevap veriyorum :)</p></blockquote><p>Acaba çözümü değiştirebiliyor muyuz :)</p>', '<p><br></p><p><br></p><blockquote><p><strong>Sinek10 yazdı:</strong></p><p><br></p><p>kaan yazdı:Şu anda mantık olarak sistemde çalışıyor ancak ne derece iyice incelemek lazım o nedenle her türlü bolca mesaj yazmalıyız :)Haklı olduğun için alıntı yapıp cevap veriyorum :)</p></blockquote><p>Acaba çözümü değiştirebiliyor muyuz :)</p>', 0, 1, 0, '2026-03-02 01:40:15', '2026-03-02 01:56:51', NULL, NULL, 0, NULL, NULL, NULL),
(90, 42, NULL, 1, '<p>Sucuri ve Cloudflare, web sitesi güvenliği ve performansını artırmak için kullanılan iki popüler hizmettir. Her ikisi de Web Uygulama Güvenlik Duvarı (WAF), İçerik Dağıtım Ağı (CDN) ve DDoS koruması sunar. Ancak, sundukları özellikler ve odaklandıkları alanlar bakımından farklılık gösterirler. Bu karşılaştırmada, hem normal kullanıcılar hem de sistem yöneticileri (sysadmin) perspektifinden Sucuri ve Cloudflare’in artı ve eksilerini inceleyeceğiz.</p><p><strong>Normal Kullanıcı Perspektifi:</strong></p><ul><li><p><strong>Cloudflare:</strong></p><ul><li><p><em>Artıları:</em></p><ul><li><p><strong>Ücretsiz Plan:</strong> Cloudflare, temel seviyede CDN ve DDoS koruması içeren ücretsiz bir plan sunar, bu da küçük web sitesi sahipleri için cazip bir seçenektir.</p></li><li><p><strong>Kolay Kurulum:</strong> DNS ayarlarını değiştirerek hızlı bir şekilde entegre edilebilir, teknik bilgi gereksinimi düşüktür.</p></li><li><p><strong>Performans Artışı:</strong> Küresel CDN ağı sayesinde web sitesi yükleme sürelerini azaltır.</p></li></ul></li><li><p><em>Eksileri:</em></p><ul><li><p><strong>Sınırlı Güvenlik Özellikleri:</strong> Ücretsiz ve düşük maliyetli planlarda gelişmiş güvenlik özellikleri sınırlıdır; tam koruma için daha yüksek ücretli planlara geçmek gerekebilir.</p></li><li><p><strong>Kötü Amaçlı Yazılım Temizleme Yok:</strong> Cloudflare, sunucu taraması veya kötü amaçlı yazılım temizleme hizmeti sunmaz. <a href=\"https://www.wpbeginner.com/tr/opinion/sucuri-vs-cloudflare-pros-and-cons-which-one-is-better/?utm_source=chatgpt.com\">WPBeginner</a></p></li></ul></li></ul></li><li><p><strong>Sucuri:</strong></p><ul><li><p><em>Artıları:</em></p><ul><li><p><strong>Kapsamlı Güvenlik Hizmetleri:</strong> Sucuri, kötü amaçlı yazılım taraması, güvenlik izleme ve sınırsız kötü amaçlı yazılım temizleme gibi kapsamlı güvenlik hizmetleri sunar.</p></li><li><p><strong>Performans Artışı:</strong> Dahili CDN’si sayesinde web sitesi hızını artırır.</p></li><li><p><strong>E-ticaret Güvenliği:</strong> Tüm planlarında e-ticaret siteleri için gelişmiş güvenlik özellikleri mevcuttur. <a href=\"https://sucuri.net/comparison/cloudflare-vs-sucuri/?utm_source=chatgpt.com\">Sucuri</a></p></li></ul></li><li><p><em>Eksileri:</em></p><ul><li><p><strong>Ücretsiz Plan Yok:</strong> Sucuri’nin ücretsiz bir planı bulunmamaktadır; tüm hizmetler ücretlidir.</p></li><li><p><strong>Kurulum Karmaşıklığı:</strong> Kurulum ve yapılandırma, teknik bilgi gerektirebilir ve yeni kullanıcılar için daha zorlayıcı olabilir.</p></li></ul></li></ul></li></ul><p><strong>Sistem Yöneticisi (Sysadmin) Perspektifi:</strong></p><ul><li><p><strong>Cloudflare:</strong></p><ul><li><p><em>Artıları:</em></p><ul><li><p><strong>Gelişmiş Özellikler:</strong> Yüksek ücretli planlarda gelişmiş güvenlik ve performans özellikleri sunar.</p></li><li><p><strong>Kolay Entegrasyon:</strong> Mevcut altyapıya entegrasyonu genellikle sorunsuzdur.</p></li><li><p><strong>API Desteği:</strong> Geliştiriciler için geniş API desteği sağlar, otomasyon ve özelleştirme imkanı verir.</p></li></ul></li><li><p><em>Eksileri:</em></p><ul><li><p><strong>Sınırlı DDoS Koruması:</strong> Ücretsiz ve Pro planlarında DDoS koruması sınırlı olabilir; yüksek hacimli saldırılarda yetersiz kalabilir. <a href=\"https://community.centminmod.com/threads/ddos-protection.14431/?utm_source=chatgpt.com\">CentminMod Community</a></p></li><li><p><strong>Müşteri Desteği:</strong> Daha düşük planlarda müşteri desteği sınırlı olabilir, bu da sorun çözümünü geciktirebilir.</p></li></ul></li></ul></li><li><p><strong>Sucuri:</strong></p><ul><li><p><em>Artıları:</em></p><ul><li><p><strong>Kapsamlı Güvenlik:</strong> Sunucu tarafı tarama, dosya bütünlüğü kontrolü ve güvenlik izleme gibi derinlemesine güvenlik özellikleri sunar.</p></li><li><p><strong>Hızlı Destek:</strong> Güvenlik ihlallerinde hızlı müdahale ve destek sağlar.</p></li><li><p><strong>Uyumluluk:</strong> Mevcut CDN’lerle entegrasyon yeteneği sayesinde esneklik sunar. <a href=\"https://docs.sucuri.net/website-firewall/configuration/support-for-cloudflare/?utm_source=chatgpt.com\">Sucuri Docs</a></p></li></ul></li><li><p><em>Eksileri:</em></p><ul><li><p><strong>Fiyatlandırma:</strong> Geniş kapsamlı güvenlik hizmetleri nedeniyle maliyeti daha yüksek olabilir.</p></li><li><p><strong>Performans Odaklı Değil:</strong> Odak noktası güvenlik olduğundan, performans iyileştirmeleri Cloudflare kadar belirgin olmayabilir.</p></li></ul></li></ul></li></ul><p><strong>Sonuç:</strong></p><p>Normal kullanıcılar için Cloudflare, ücretsiz planı ve kolay kurulumu sayesinde temel düzeyde güvenlik ve performans artışı sağlar. Ancak, daha kapsamlı güvenlik ihtiyaçları olan kullanıcılar için Sucuri’nin sunduğu hizmetler daha uygun olabilir. Sistem yöneticileri açısından, Sucuri’nin derinlemesine güvenlik özellikleri ve hızlı desteği, özellikle güvenlik odaklı projelerde avantaj sağlar. Öte yandan, Cloudflare’ın API desteği ve entegrasyon kolaylığı, performans ve ölçeklenebilirlik gereksinimleri olan projelerde tercih edilebilir.</p><p>Her iki hizmetin de sunduğu özellikler ve fiyatlandırma modelleri dikkate alınarak, ihtiyaçlarınıza en uygun olanı seçmeniz önerilir.</p>', '<p>Sucuri ve Cloudflare, web sitesi güvenliği ve performansını artırmak için kullanılan iki popüler hizmettir. Her ikisi de Web Uygulama Güvenlik Duvarı (WAF), İçerik Dağıtım Ağı (CDN) ve DDoS koruması sunar. Ancak, sundukları özellikler ve odaklandıkları alanlar bakımından farklılık gösterirler. Bu karşılaştırmada, hem normal kullanıcılar hem de sistem yöneticileri (sysadmin) perspektifinden Sucuri ve Cloudflare’in artı ve eksilerini inceleyeceğiz.</p><p><strong>Normal Kullanıcı Perspektifi:</strong></p><ul><li><p><strong>Cloudflare:</strong></p><ul><li><p><em>Artıları:</em></p><ul><li><p><strong>Ücretsiz Plan:</strong> Cloudflare, temel seviyede CDN ve DDoS koruması içeren ücretsiz bir plan sunar, bu da küçük web sitesi sahipleri için cazip bir seçenektir.</p></li><li><p><strong>Kolay Kurulum:</strong> DNS ayarlarını değiştirerek hızlı bir şekilde entegre edilebilir, teknik bilgi gereksinimi düşüktür.</p></li><li><p><strong>Performans Artışı:</strong> Küresel CDN ağı sayesinde web sitesi yükleme sürelerini azaltır.</p></li></ul></li><li><p><em>Eksileri:</em></p><ul><li><p><strong>Sınırlı Güvenlik Özellikleri:</strong> Ücretsiz ve düşük maliyetli planlarda gelişmiş güvenlik özellikleri sınırlıdır; tam koruma için daha yüksek ücretli planlara geçmek gerekebilir.</p></li><li><p><strong>Kötü Amaçlı Yazılım Temizleme Yok:</strong> Cloudflare, sunucu taraması veya kötü amaçlı yazılım temizleme hizmeti sunmaz. <a href=\"https://www.wpbeginner.com/tr/opinion/sucuri-vs-cloudflare-pros-and-cons-which-one-is-better/?utm_source=chatgpt.com\">WPBeginner</a></p></li></ul></li></ul></li><li><p><strong>Sucuri:</strong></p><ul><li><p><em>Artıları:</em></p><ul><li><p><strong>Kapsamlı Güvenlik Hizmetleri:</strong> Sucuri, kötü amaçlı yazılım taraması, güvenlik izleme ve sınırsız kötü amaçlı yazılım temizleme gibi kapsamlı güvenlik hizmetleri sunar.</p></li><li><p><strong>Performans Artışı:</strong> Dahili CDN’si sayesinde web sitesi hızını artırır.</p></li><li><p><strong>E-ticaret Güvenliği:</strong> Tüm planlarında e-ticaret siteleri için gelişmiş güvenlik özellikleri mevcuttur. <a href=\"https://sucuri.net/comparison/cloudflare-vs-sucuri/?utm_source=chatgpt.com\">Sucuri</a></p></li></ul></li><li><p><em>Eksileri:</em></p><ul><li><p><strong>Ücretsiz Plan Yok:</strong> Sucuri’nin ücretsiz bir planı bulunmamaktadır; tüm hizmetler ücretlidir.</p></li><li><p><strong>Kurulum Karmaşıklığı:</strong> Kurulum ve yapılandırma, teknik bilgi gerektirebilir ve yeni kullanıcılar için daha zorlayıcı olabilir.</p></li></ul></li></ul></li></ul><p><strong>Sistem Yöneticisi (Sysadmin) Perspektifi:</strong></p><ul><li><p><strong>Cloudflare:</strong></p><ul><li><p><em>Artıları:</em></p><ul><li><p><strong>Gelişmiş Özellikler:</strong> Yüksek ücretli planlarda gelişmiş güvenlik ve performans özellikleri sunar.</p></li><li><p><strong>Kolay Entegrasyon:</strong> Mevcut altyapıya entegrasyonu genellikle sorunsuzdur.</p></li><li><p><strong>API Desteği:</strong> Geliştiriciler için geniş API desteği sağlar, otomasyon ve özelleştirme imkanı verir.</p></li></ul></li><li><p><em>Eksileri:</em></p><ul><li><p><strong>Sınırlı DDoS Koruması:</strong> Ücretsiz ve Pro planlarında DDoS koruması sınırlı olabilir; yüksek hacimli saldırılarda yetersiz kalabilir. <a href=\"https://community.centminmod.com/threads/ddos-protection.14431/?utm_source=chatgpt.com\">CentminMod Community</a></p></li><li><p><strong>Müşteri Desteği:</strong> Daha düşük planlarda müşteri desteği sınırlı olabilir, bu da sorun çözümünü geciktirebilir.</p></li></ul></li></ul></li><li><p><strong>Sucuri:</strong></p><ul><li><p><em>Artıları:</em></p><ul><li><p><strong>Kapsamlı Güvenlik:</strong> Sunucu tarafı tarama, dosya bütünlüğü kontrolü ve güvenlik izleme gibi derinlemesine güvenlik özellikleri sunar.</p></li><li><p><strong>Hızlı Destek:</strong> Güvenlik ihlallerinde hızlı müdahale ve destek sağlar.</p></li><li><p><strong>Uyumluluk:</strong> Mevcut CDN’lerle entegrasyon yeteneği sayesinde esneklik sunar. <a href=\"https://docs.sucuri.net/website-firewall/configuration/support-for-cloudflare/?utm_source=chatgpt.com\">Sucuri Docs</a></p></li></ul></li><li><p><em>Eksileri:</em></p><ul><li><p><strong>Fiyatlandırma:</strong> Geniş kapsamlı güvenlik hizmetleri nedeniyle maliyeti daha yüksek olabilir.</p></li><li><p><strong>Performans Odaklı Değil:</strong> Odak noktası güvenlik olduğundan, performans iyileştirmeleri Cloudflare kadar belirgin olmayabilir.</p></li></ul></li></ul></li></ul><p><strong>Sonuç:</strong></p><p>Normal kullanıcılar için Cloudflare, ücretsiz planı ve kolay kurulumu sayesinde temel düzeyde güvenlik ve performans artışı sağlar. Ancak, daha kapsamlı güvenlik ihtiyaçları olan kullanıcılar için Sucuri’nin sunduğu hizmetler daha uygun olabilir. Sistem yöneticileri açısından, Sucuri’nin derinlemesine güvenlik özellikleri ve hızlı desteği, özellikle güvenlik odaklı projelerde avantaj sağlar. Öte yandan, Cloudflare’ın API desteği ve entegrasyon kolaylığı, performans ve ölçeklenebilirlik gereksinimleri olan projelerde tercih edilebilir.</p><p>Her iki hizmetin de sunduğu özellikler ve fiyatlandırma modelleri dikkate alınarak, ihtiyaçlarınıza en uygun olanı seçmeniz önerilir.</p>', 0, 0, 1, '2026-03-02 22:26:45', '2026-03-02 22:26:45', NULL, NULL, 0, NULL, NULL, NULL),
(91, 43, NULL, 1, '<p>Sistem iletişim mesajları geliştirildi, Artık gelen mailler sadece mail adresine değil, mesajlaşma sistemi gibi admin panelden de görünüyyor ve yanıt veriliyor, verilen yanıt kullanıcı adı ile kaydediliyor yani web siteye gelen iletişim formu mesajına hangi yönetici ne yazmış görebiliyoruz. bu sayede aynı iletişim mesajına farklı yöneticiler defalarca cevap vermesinin önüne geçebiliyoruz.</p><p><br></p><p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/03/17c03974ff722e63.png\" alt=\"Ekran görüntüsü 2026-03-04 014448.png\" contenteditable=\"false\"><br></p>', '<p>Sistem iletişim mesajları geliştirildi, Artık gelen mailler sadece mail adresine değil, mesajlaşma sistemi gibi admin panelden de görünüyyor ve yanıt veriliyor, verilen yanıt kullanıcı adı ile kaydediliyor yani web siteye gelen iletişim formu mesajına hangi yönetici ne yazmış görebiliyoruz. bu sayede aynı iletişim mesajına farklı yöneticiler defalarca cevap vermesinin önüne geçebiliyoruz.</p><p><br></p><p><img src=\"https://www.megaforbb.com.tr/uploads/images/2026/03/17c03974ff722e63.png\" alt=\"Ekran görüntüsü 2026-03-04 014448.png\" contenteditable=\"false\"><br></p>', 0, 0, 1, '2026-03-04 01:45:37', '2026-03-04 01:45:37', NULL, NULL, 0, NULL, NULL, NULL),
(92, 5, NULL, 1, '<ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Postbit sadeleştirildi sadece kullanıcı adı, özel başlık, mesaj ve beğeni sayısı görünüyor artık.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Postbit güncellemesi - Postbit modal içinde artık daha detaylı ve kapsamlı şekilde gösterliyor.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Bu güncelleme ile birlikte Forumda profil resmi daha büyük ve güzel görünüyor.</p></li></ul>', '<ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Postbit sadeleştirildi sadece kullanıcı adı, özel başlık, mesaj ve beğeni sayısı görünüyor artık.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Postbit güncellemesi - Postbit modal içinde artık daha detaylı ve kapsamlı şekilde gösterliyor.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Bu güncelleme ile birlikte Forumda profil resmi daha büyük ve güzel görünüyor.</p></li></ul>', 0, 0, 0, '2026-03-04 02:26:46', '2026-03-04 02:27:29', '2026-03-04 02:27:29', 1, 2, NULL, NULL, NULL),
(93, 44, NULL, 1, '<p>Merhabalar, MegaforBB altyapısında yapılan bir güvenlik incelemesi sonrasında altyapı ve yönetim paneli tarafında sertleştirmeler uygulandı. Bu güncellemeler özellikle <strong>sunucu ve panel güvenliğini</strong> hedefliyor; günlük kullanımınız (konu açma, mesaj yazma, profil, özel mesajlar vb.) aynı şekilde devam ediyor.</p><p><strong>Ne yapıldı?</strong></p><ul><li><p><strong>Cron ve bakım scriptleri</strong> artık yalnızca yetkili erişimle çalışacak şekilde korunuyor. Bu sayede sistem bakım işlemleri dışarıdan tetiklenemez.</p></li><li><p><strong>Yönetim paneli</strong> (tema düzenleme, dosya yolları vb.) için path ve erişim kontrolleri sıkılaştırıldı; yetkisiz dosya erişimi engellendi.</p></li><li><p><strong>Dosya yükleme</strong> (ekler, avatar, kapak) tarafında dosya adı ve yol güvenliği güçlendirildi; çift uzantı ve benzeri riskler azaltıldı.</p></li></ul><p><strong>Siz bir şey yapmalı mısınız?</strong></p><p>Hayır. Bu değişiklikler sunucu ve panel tarafında; <strong>normal üye kullanımı</strong> (giriş, konu/mesaj, profil, özel mesaj, bildirimler) aynı. Ek bir işlem veya ayar yapmanız gerekmiyor.</p><p><strong>Güvenlik konusunda genel hatırlatma</strong></p><ul><li><p>Şifrenizi kimseyle paylaşmayın; güçlü ve siteye özel bir şifre kullanın.</p></li><li><p>Şüpheli e-postalar veya linklere tıklamayın; resmi duyurular her zaman forum üzerinden yapılır.</p></li><li><p>Bir güvenlik endişeniz olursa lütfen yönetimle iletişime geçin.</p></li></ul><p>Sorularınızı bu konu altında iletebilirsiniz. İyi forumlar.</p><p>— MegaforBB Ekibi</p>', '<p>Merhabalar, MegaforBB altyapısında yapılan bir güvenlik incelemesi sonrasında altyapı ve yönetim paneli tarafında sertleştirmeler uygulandı. Bu güncellemeler özellikle <strong>sunucu ve panel güvenliğini</strong> hedefliyor; günlük kullanımınız (konu açma, mesaj yazma, profil, özel mesajlar vb.) aynı şekilde devam ediyor.</p><p><strong>Ne yapıldı?</strong></p><ul><li><p><strong>Cron ve bakım scriptleri</strong> artık yalnızca yetkili erişimle çalışacak şekilde korunuyor. Bu sayede sistem bakım işlemleri dışarıdan tetiklenemez.</p></li><li><p><strong>Yönetim paneli</strong> (tema düzenleme, dosya yolları vb.) için path ve erişim kontrolleri sıkılaştırıldı; yetkisiz dosya erişimi engellendi.</p></li><li><p><strong>Dosya yükleme</strong> (ekler, avatar, kapak) tarafında dosya adı ve yol güvenliği güçlendirildi; çift uzantı ve benzeri riskler azaltıldı.</p></li></ul><p><strong>Siz bir şey yapmalı mısınız?</strong></p><p>Hayır. Bu değişiklikler sunucu ve panel tarafında; <strong>normal üye kullanımı</strong> (giriş, konu/mesaj, profil, özel mesaj, bildirimler) aynı. Ek bir işlem veya ayar yapmanız gerekmiyor.</p><p><strong>Güvenlik konusunda genel hatırlatma</strong></p><ul><li><p>Şifrenizi kimseyle paylaşmayın; güçlü ve siteye özel bir şifre kullanın.</p></li><li><p>Şüpheli e-postalar veya linklere tıklamayın; resmi duyurular her zaman forum üzerinden yapılır.</p></li><li><p>Bir güvenlik endişeniz olursa lütfen yönetimle iletişime geçin.</p></li></ul><p>Sorularınızı bu konu altında iletebilirsiniz. İyi forumlar.</p><p>— MegaforBB Ekibi</p>', 0, 0, 1, '2026-03-04 02:42:21', '2026-03-04 02:42:44', NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `posts` (`id`, `topic_id`, `reply_to_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(94, 45, NULL, 1, '<h1>MegaforBB – Büyük Veri (4.3M Konu / 29M Mesaj / 217K Üye) Taşıma Analizi</h1><p><strong>Tarih:</strong> 4 Mart 2026<strong>Hedef veri:</strong> 4.317.051 konu, 29.353.083 mesaj, 217.255 üye</p><div contenteditable=\"false\"><hr></div><h2>1. Özet Sonuç</h2><table><thead><tr><th><p>Soru</p></th><th><p>Cevap</p></th></tr></thead><tbody><tr><td><p>Bu veriyle sistem çöker mi?</p></td><td><p><strong>Kritik bir kod hatası düzeltilmezse, çok mesajlı konularda evet (timeout / bellek hatası).</strong></p></td></tr><tr><td><p>Donma / kasma olur mu?</p></td><td><p>Konu listesi ve forum listesi <strong>sayfalanmış ve limitli</strong>; <strong>konu içi mesaj listesi</strong> tüm mesajları belleğe çektiği için büyük konularda <strong>ciddi donma/kasma ve çökme riski</strong> var.</p></td></tr><tr><td><p>Veritabanı yapısı yeterli mi?</p></td><td><p><strong>Evet.</strong> Tablolar InnoDB, indeksler ana sorgulara uygun, ID tipleri bu büyüklük için yeterli.</p></td></tr><tr><td><p>Altyapı destekler mi?</p></td><td><p><strong>Kod tarafında bir kritik düzeltme şart.</strong> Sonrasında uygun sunucu ve DB ayarlarıyla desteklenebilir.</p></td></tr></tbody></table><p><strong>En önemli nokta (düzeltildi):</strong> Konu detay sayfasında (<strong>showthread</strong>) bir konudaki <strong>tüm mesajlar</strong> veritabanından çekilip PHP’de sayfalanıyor. 10.000–100.000+ mesajlı konularda bu, bellek ve süre limitini aşarak <strong>timeout veya PHP Fatal (memory)</strong> ile sonuçlanır. Bu davranış <strong>mutlaka</strong> veritabanı seviyesinde LIMIT/OFFSET (veya keyset) ile sayfalamaya çevrilmeli.</p><div contenteditable=\"false\"><hr></div><h2>2. Veritabanı Tablo Yapısı Özeti</h2><h3>2.1 Ana tablolar</h3><ul><li><p><strong><code data-backticks=\"1\">topics</code></strong></p><ul><li><p><code data-backticks=\"1\">id</code> int(10) UNSIGNED → ~4.29 milyar kapasite, 4.3M konu rahat.</p></li><li><p>İndeksler: <code data-backticks=\"1\">forum_id</code>, <code data-backticks=\"1\">last_post_at</code>, <code data-backticks=\"1\">user_id</code>, <code data-backticks=\"1\">is_sticky</code>, <code data-backticks=\"1\">slug</code>, FULLTEXT <code data-backticks=\"1\">title</code>.</p></li><li><p>Sorgular: forum konu listesi, sıralama, arama için uygun.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">posts</code></strong></p><ul><li><p><code data-backticks=\"1\">id</code> int(10) UNSIGNED → 29M mesaj için yeterli.</p></li><li><p>İndeksler: <code data-backticks=\"1\">topic_id</code>, <code data-backticks=\"1\">user_id</code>, <code data-backticks=\"1\">created_at</code>, FULLTEXT <code data-backticks=\"1\">body</code>.</p></li><li><p>Konu bazlı listeleme <code data-backticks=\"1\">topic_id</code> ile hızlı; sayfalama <strong>sorguda</strong> yapılmadığı için büyük konularda sorun <strong>uygulama tarafında</strong>.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">users</code></strong></p><ul><li><p><code data-backticks=\"1\">id</code> int(10) UNSIGNED, 217K üye için fazlasıyla yeterli.</p></li><li><p><code data-backticks=\"1\">username</code>, <code data-backticks=\"1\">email</code> UNIQUE; <code data-backticks=\"1\">role_id</code>, <code data-backticks=\"1\">last_activity_at</code>, <code data-backticks=\"1\">created_at</code> indeksli.</p></li></ul></li></ul><h3>2.2 İstatistik ve özet tablolar</h3><ul><li><p><strong><code data-backticks=\"1\">forum_stats</code></strong></p><ul><li><p>Tek satır (<code data-backticks=\"1\">id=1</code>), <code data-backticks=\"1\">total_topics</code>, <code data-backticks=\"1\">total_posts</code>, <code data-backticks=\"1\">total_members</code> int(10).</p></li><li><p>4M / 29M değerleri sığar. Güncellemeler tek satır, cache ile kullanılıyor; ek yük düşük.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">forums</code></strong></p><ul><li><p>Her forum için <code data-backticks=\"1\">topic_count</code>, <code data-backticks=\"1\">post_count</code>, <code data-backticks=\"1\">last_post_*</code> — sayılar int(10), büyük veriyle uyumlu.</p></li></ul></li></ul><h3>2.3 İlişkili / büyüyebilir tablolar</h3><ul><li><p><strong><code data-backticks=\"1\">topic_reads</code></strong> (user_id, topic_id)</p><ul><li><p>Kullanıcı başına okunan konu sayısı arttıkça büyür. 217K kullanıcı × ortalama çok yüksek okuma = çok büyük satır sayısı. İleride arşivleme veya “son N konu” sınırı düşünülebilir.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">user_activities</code></strong></p><ul><li><p><code data-backticks=\"1\">id</code> bigint(20), yüksek aktivite için uygun.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">attachments</code></strong>, <strong><code data-backticks=\"1\">post_likes</code></strong>, <strong><code data-backticks=\"1\">post_edits</code></strong></p><ul><li><p>post_id / user_id indeksleri var; 29M mesajla büyüse bile indeksli kullanımda makul.</p></li></ul></li></ul><h3>2.4 FULLTEXT</h3><ul><li><p><code data-backticks=\"1\">topics.title</code> ve <code data-backticks=\"1\">posts.body</code> üzerinde FULLTEXT var.</p></li><li><p>29M satırlı <code data-backticks=\"1\">posts</code> tablosunda FULLTEXT aramaları, MySQL’de tuning (buffer, ngram, vb.) ve/veya dış arama motoru (ör. Meilisearch) ile desteklenmeli; aksi halde ağır aramalarda gecikme olabilir.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>3. Kritik Kod Analizi: Konu İçi Mesajlar</h2><h3>3.1 Sorunlu akış</h3><p><strong>Dosya:</strong> <code data-backticks=\"1\">app/Controllers/TopicController.php</code></p><ul><li><p><strong>Satır 111:</strong> <code data-backticks=\"1\">getPosts($topicId, ...)</code> çağrılıyor.</p></li><li><p><strong>Satır 119:</strong> <code data-backticks=\"1\">$totalPosts = count($allPosts);</code> → Konudaki <strong>tüm</strong> mesajlar zaten dizide.</p></li><li><p><strong>Satır 124:</strong> <code data-backticks=\"1\">$posts = array_slice($allPosts, ($pageNum - 1) * $perPage, $perPage);</code> → Sayfalama <strong>sadece PHP’de</strong>, veritabanı hep tam listeyi getiriyor.</p></li></ul><p><strong>getPosts() içinde (satır 193–207):</strong></p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">$query = Post::query()\r\n    -&gt;with([\'user\' =&gt; ..., \'user.role\', \'attachments\'])\r\n    -&gt;where(\'topic_id\', $topicId)\r\n    -&gt;whereNull(\'posts.deleted_at\')\r\n    -&gt;orderBy(\'id\');\r\n\r\n$collection = $query-&gt;get();  // ← LIMIT YOK: Tüm mesajlar çekiliyor</code></pre></div><ul><li><p>Bir konuda 50.000 mesaj varsa: 50.000 satır + ilişkiler (user, role, attachments) tek seferde belleğe alınıyor.</p></li><li><p>Sonuç: <strong>Zaman aşımı</strong>, <strong>PHP memory_limit aşımı</strong>, sayfa açılamaz veya sunucu yanıt vermez.</p></li></ul><h3>3.2 Neden yapılmış olabilir?</h3><ul><li><p>“Soru–cevap” tipi konularda <strong>kabul edilen cevabı</strong> belirli bir sıraya koymak için tüm mesajlar üzerinde <code data-backticks=\"1\">usort</code> yapılıyor (satır 273–294). Bu mantık, <strong>sadece o sayfadaki mesajlarla</strong> veya veritabanı tarafında çözülebilir; tüm konuyu çekmek zorunlu değil.</p></li></ul><h3>3.3 Ne yapılmalı? (Zorunlu)</h3><ol><li><p><strong>Sayfalamayı veritabanına taşıyın</strong></p><ul><li><p><code data-backticks=\"1\">getPosts()</code> içinde <code data-backticks=\"1\">$query-&gt;get()</code> yerine:</p><ul><li><p><code data-backticks=\"1\">$pageNum</code> ve <code data-backticks=\"1\">$perPage</code> parametrelerini alın,</p></li><li><p><code data-backticks=\"1\">$query-&gt;offset(($pageNum - 1) * $perPage)-&gt;limit($perPage)-&gt;get()</code> kullanın.</p></li></ul></li><li><p>Toplam mesaj sayısı için ayrı bir <code data-backticks=\"1\">Post::where(\'topic_id\', $topicId)-&gt;whereNull(\'deleted_at\')-&gt;count()</code> (veya cache’lenmiş değer) kullanın.</p></li></ul></li><li><p><strong>Soru–cevap sıralaması</strong></p><ul><li><p>Kabul edilen cevabı “sabit sırada” göstermek için:</p><ul><li><p>Ya sadece <strong>o sayfadaki</strong> postları alıp (first post + accepted post + o sayfadaki diğerleri) PHP’de sıralayın,</p></li><li><p>Ya da “ilk mesaj + kabul edilen mesaj”ı ayrı sorgulayıp, kalanı <code data-backticks=\"1\">ORDER BY id LIMIT/OFFSET</code> ile getirin ve birleştirin.</p></li></ul></li><li><p>Hiçbir senaryoda konunun <strong>tüm</strong> mesajlarını çekmeyin.</p></li></ul></li><li><p><strong>Bellek ve süre</strong></p><ul><li><p>Tek istekte sadece <code data-backticks=\"1\">perPage</code> (ör. 15–25) mesaj yükleneceği için bellek ve süre sınırlarına uyum sağlanır.</p></li></ul></li></ol><p>Bu değişiklik yapılmadan <strong>çok mesajlı konularla</strong> (binlerce/on binlerce mesaj) bu ölçekteki bir forum açıldığında <strong>donma, kasma ve çökme kaçınılmazdır.</strong></p><div contenteditable=\"false\"><hr></div><h2>4. Diğer Sorgular (Kısa Özet)</h2><ul><li><p><strong>Forum konu listesi</strong> (<code data-backticks=\"1\">ForumController::getTopics</code>): <code data-backticks=\"1\">limit($perPage)</code> var, sayfa başına 20–100 konu. Sayfa parametresi (offset) yok; eklenirse derin sayfalarda (örn. 4M konuluk forumda 100. sayfa) büyük <code data-backticks=\"1\">OFFSET</code> yavaşlatabilir; ileride keyset/cursor sayfalama düşünülebilir.</p></li><li><p><strong>Arama</strong> (<code data-backticks=\"1\">SearchController</code>): offset/limit ile sayfalanıyor; 15 sonuç/sayfa.</p></li><li><p><strong>forum_stats</strong>: Tek satır güncelleme + cache; 4M/29M sayıları için sorun yok.</p></li><li><p><strong>Sitemap</strong>: 50.000 konu/sitemap limiti var; makul.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>5. Sunucu / Veritabanı Önerileri</h2><ul><li><p><strong>MySQL/MariaDB:</strong></p><ul><li><p><code data-backticks=\"1\">innodb_buffer_pool_size</code> yeterli (örn. sunucu RAM’inin büyük kısmı), bağlantı sayısı, timeout değerleri.</p></li><li><p>29M satırda FULLTEXT kullanacaksanız InnoDB FULLTEXT ayarları veya harici arama (Meilisearch vb.) önerilir.</p></li></ul></li><li><p><strong>PHP:</strong></p><ul><li><p><code data-backticks=\"1\">memory_limit</code> (tek istekte artık sadece bir sayfa mesaj yükleneceği için makul bir değer yeterli).</p></li><li><p><code data-backticks=\"1\">max_execution_time</code>; uzun süren tek işlemler (import, cron) için gerekirse <code data-backticks=\"1\">set_time_limit</code> kullanılıyor.</p></li></ul></li><li><p><strong>Import:</strong></p><ul><li><p>4.3M konu / 29M mesaj için: toplu (batch) insert, mümkünse geçici olarak FK/indeksleri hafifletme, chunk’lar halinde işlem, hata loglama. Mevcut <code data-backticks=\"1\">import_progress</code> / <code data-backticks=\"1\">import_id_map</code> yapısı bu sürece uyarlanabilir.</p></li></ul></li></ul><div contenteditable=\"false\"><hr></div><h2>6. Sonuç ve Aksiyon Listesi</h2><table><thead><tr><th><p>Öncelik</p></th><th><p>Yapılacak</p></th><th><p>Sonuç</p></th></tr></thead><tbody><tr><td><p><strong>Kritik</strong></p></td><td><p>Konu detayında mesajları DB’de LIMIT/OFFSET ile sayfala; <code data-backticks=\"1\">getPosts()</code> içinde tüm konuyu <code data-backticks=\"1\">get()</code> ile çekmeyi kaldır.</p></td><td><p>Büyük konularda donma/çökme riski ortadan kalkar.</p></td></tr><tr><td><p>Yüksek</p></td><td><p>Soru–cevap “kabul edilen cevap” sıralamasını, sadece o sayfadaki veriyle veya ek küçük sorgularla yap.</p></td><td><p>Aynı sayfalama ile uyumlu, performans korunur.</p></td></tr><tr><td><p>Orta</p></td><td><p>Forum konu listesine sayfa numarası (offset) eklenirse, derin sayfalar için keyset pagination planla.</p></td><td><p>4M konuluk forumlarda ileri sayfalar yavaşlamaz.</p></td></tr><tr><td><p>Orta</p></td><td><p>FULLTEXT’i 29M post ile test et; gerekirse Meilisearch/Elasticsearch veya MySQL tuning.</p></td><td><p>Arama performansı ve stabilite.</p></td></tr><tr><td><p>Düşük</p></td><td><p><code data-backticks=\"1\">topic_reads</code> büyümesini izle; gerekirse arşivleme veya sınır.</p></td><td><p>Uzun vadede tablo boyutu kontrol altında.</p></td></tr></tbody></table><p><strong>Nihai cevap:</strong>Veritabanı yapısı ve tablo/indeks tasarımı <strong>4.3M konu, 29M mesaj, 217K üye</strong> ölçeğini destekleyebilir. Ancak <strong>konu içi mesajların tamamını belleğe çeken mevcut kod</strong> bu ölçekte <strong>kesinlikle</strong> donma, kasma ve çökmeye yol açar. Bu tek kritik nokta düzeltildikten sonra, uygun sunucu ve DB ayarlarıyla sistem bu büyüklükteki bir forumu taşıyabilir; düzeltilmeden büyük veriyle canlıya alınmamalı.</p>', '<h1>MegaforBB – Büyük Veri (4.3M Konu / 29M Mesaj / 217K Üye) Taşıma Analizi</h1><p><strong>Tarih:</strong> 4 Mart 2026<strong>Hedef veri:</strong> 4.317.051 konu, 29.353.083 mesaj, 217.255 üye</p><div contenteditable=\"false\"><hr></div><h2>1. Özet Sonuç</h2><table><thead><tr><th><p>Soru</p></th><th><p>Cevap</p></th></tr></thead><tbody><tr><td><p>Bu veriyle sistem çöker mi?</p></td><td><p><strong>Kritik bir kod hatası düzeltilmezse, çok mesajlı konularda evet (timeout / bellek hatası).</strong></p></td></tr><tr><td><p>Donma / kasma olur mu?</p></td><td><p>Konu listesi ve forum listesi <strong>sayfalanmış ve limitli</strong>; <strong>konu içi mesaj listesi</strong> tüm mesajları belleğe çektiği için büyük konularda <strong>ciddi donma/kasma ve çökme riski</strong> var.</p></td></tr><tr><td><p>Veritabanı yapısı yeterli mi?</p></td><td><p><strong>Evet.</strong> Tablolar InnoDB, indeksler ana sorgulara uygun, ID tipleri bu büyüklük için yeterli.</p></td></tr><tr><td><p>Altyapı destekler mi?</p></td><td><p><strong>Kod tarafında bir kritik düzeltme şart.</strong> Sonrasında uygun sunucu ve DB ayarlarıyla desteklenebilir.</p></td></tr></tbody></table><p><strong>En önemli nokta (düzeltildi):</strong> Konu detay sayfasında (<strong>showthread</strong>) bir konudaki <strong>tüm mesajlar</strong> veritabanından çekilip PHP’de sayfalanıyor. 10.000–100.000+ mesajlı konularda bu, bellek ve süre limitini aşarak <strong>timeout veya PHP Fatal (memory)</strong> ile sonuçlanır. Bu davranış <strong>mutlaka</strong> veritabanı seviyesinde LIMIT/OFFSET (veya keyset) ile sayfalamaya çevrilmeli.</p><div contenteditable=\"false\"><hr></div><h2>2. Veritabanı Tablo Yapısı Özeti</h2><h3>2.1 Ana tablolar</h3><ul><li><p><strong><code data-backticks=\"1\">topics</code></strong></p><ul><li><p><code data-backticks=\"1\">id</code> int(10) UNSIGNED → ~4.29 milyar kapasite, 4.3M konu rahat.</p></li><li><p>İndeksler: <code data-backticks=\"1\">forum_id</code>, <code data-backticks=\"1\">last_post_at</code>, <code data-backticks=\"1\">user_id</code>, <code data-backticks=\"1\">is_sticky</code>, <code data-backticks=\"1\">slug</code>, FULLTEXT <code data-backticks=\"1\">title</code>.</p></li><li><p>Sorgular: forum konu listesi, sıralama, arama için uygun.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">posts</code></strong></p><ul><li><p><code data-backticks=\"1\">id</code> int(10) UNSIGNED → 29M mesaj için yeterli.</p></li><li><p>İndeksler: <code data-backticks=\"1\">topic_id</code>, <code data-backticks=\"1\">user_id</code>, <code data-backticks=\"1\">created_at</code>, FULLTEXT <code data-backticks=\"1\">body</code>.</p></li><li><p>Konu bazlı listeleme <code data-backticks=\"1\">topic_id</code> ile hızlı; sayfalama <strong>sorguda</strong> yapılmadığı için büyük konularda sorun <strong>uygulama tarafında</strong>.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">users</code></strong></p><ul><li><p><code data-backticks=\"1\">id</code> int(10) UNSIGNED, 217K üye için fazlasıyla yeterli.</p></li><li><p><code data-backticks=\"1\">username</code>, <code data-backticks=\"1\">email</code> UNIQUE; <code data-backticks=\"1\">role_id</code>, <code data-backticks=\"1\">last_activity_at</code>, <code data-backticks=\"1\">created_at</code> indeksli.</p></li></ul></li></ul><h3>2.2 İstatistik ve özet tablolar</h3><ul><li><p><strong><code data-backticks=\"1\">forum_stats</code></strong></p><ul><li><p>Tek satır (<code data-backticks=\"1\">id=1</code>), <code data-backticks=\"1\">total_topics</code>, <code data-backticks=\"1\">total_posts</code>, <code data-backticks=\"1\">total_members</code> int(10).</p></li><li><p>4M / 29M değerleri sığar. Güncellemeler tek satır, cache ile kullanılıyor; ek yük düşük.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">forums</code></strong></p><ul><li><p>Her forum için <code data-backticks=\"1\">topic_count</code>, <code data-backticks=\"1\">post_count</code>, <code data-backticks=\"1\">last_post_*</code> — sayılar int(10), büyük veriyle uyumlu.</p></li></ul></li></ul><h3>2.3 İlişkili / büyüyebilir tablolar</h3><ul><li><p><strong><code data-backticks=\"1\">topic_reads</code></strong> (user_id, topic_id)</p><ul><li><p>Kullanıcı başına okunan konu sayısı arttıkça büyür. 217K kullanıcı × ortalama çok yüksek okuma = çok büyük satır sayısı. İleride arşivleme veya “son N konu” sınırı düşünülebilir.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">user_activities</code></strong></p><ul><li><p><code data-backticks=\"1\">id</code> bigint(20), yüksek aktivite için uygun.</p></li></ul></li><li><p><strong><code data-backticks=\"1\">attachments</code></strong>, <strong><code data-backticks=\"1\">post_likes</code></strong>, <strong><code data-backticks=\"1\">post_edits</code></strong></p><ul><li><p>post_id / user_id indeksleri var; 29M mesajla büyüse bile indeksli kullanımda makul.</p></li></ul></li></ul><h3>2.4 FULLTEXT</h3><ul><li><p><code data-backticks=\"1\">topics.title</code> ve <code data-backticks=\"1\">posts.body</code> üzerinde FULLTEXT var.</p></li><li><p>29M satırlı <code data-backticks=\"1\">posts</code> tablosunda FULLTEXT aramaları, MySQL’de tuning (buffer, ngram, vb.) ve/veya dış arama motoru (ör. Meilisearch) ile desteklenmeli; aksi halde ağır aramalarda gecikme olabilir.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>3. Kritik Kod Analizi: Konu İçi Mesajlar</h2><h3>3.1 Sorunlu akış</h3><p><strong>Dosya:</strong> <code data-backticks=\"1\">app/Controllers/TopicController.php</code></p><ul><li><p><strong>Satır 111:</strong> <code data-backticks=\"1\">getPosts($topicId, ...)</code> çağrılıyor.</p></li><li><p><strong>Satır 119:</strong> <code data-backticks=\"1\">$totalPosts = count($allPosts);</code> → Konudaki <strong>tüm</strong> mesajlar zaten dizide.</p></li><li><p><strong>Satır 124:</strong> <code data-backticks=\"1\">$posts = array_slice($allPosts, ($pageNum - 1) * $perPage, $perPage);</code> → Sayfalama <strong>sadece PHP’de</strong>, veritabanı hep tam listeyi getiriyor.</p></li></ul><p><strong>getPosts() içinde (satır 193–207):</strong></p><div data-language=\"php\" class=\"toastui-editor-ww-code-block\"><pre><code data-language=\"php\">$query = Post::query()\r\n    -&gt;with([\'user\' =&gt; ..., \'user.role\', \'attachments\'])\r\n    -&gt;where(\'topic_id\', $topicId)\r\n    -&gt;whereNull(\'posts.deleted_at\')\r\n    -&gt;orderBy(\'id\');\r\n\r\n$collection = $query-&gt;get();  // ← LIMIT YOK: Tüm mesajlar çekiliyor</code></pre></div><ul><li><p>Bir konuda 50.000 mesaj varsa: 50.000 satır + ilişkiler (user, role, attachments) tek seferde belleğe alınıyor.</p></li><li><p>Sonuç: <strong>Zaman aşımı</strong>, <strong>PHP memory_limit aşımı</strong>, sayfa açılamaz veya sunucu yanıt vermez.</p></li></ul><h3>3.2 Neden yapılmış olabilir?</h3><ul><li><p>“Soru–cevap” tipi konularda <strong>kabul edilen cevabı</strong> belirli bir sıraya koymak için tüm mesajlar üzerinde <code data-backticks=\"1\">usort</code> yapılıyor (satır 273–294). Bu mantık, <strong>sadece o sayfadaki mesajlarla</strong> veya veritabanı tarafında çözülebilir; tüm konuyu çekmek zorunlu değil.</p></li></ul><h3>3.3 Ne yapılmalı? (Zorunlu)</h3><ol><li><p><strong>Sayfalamayı veritabanına taşıyın</strong></p><ul><li><p><code data-backticks=\"1\">getPosts()</code> içinde <code data-backticks=\"1\">$query-&gt;get()</code> yerine:</p><ul><li><p><code data-backticks=\"1\">$pageNum</code> ve <code data-backticks=\"1\">$perPage</code> parametrelerini alın,</p></li><li><p><code data-backticks=\"1\">$query-&gt;offset(($pageNum - 1) * $perPage)-&gt;limit($perPage)-&gt;get()</code> kullanın.</p></li></ul></li><li><p>Toplam mesaj sayısı için ayrı bir <code data-backticks=\"1\">Post::where(\'topic_id\', $topicId)-&gt;whereNull(\'deleted_at\')-&gt;count()</code> (veya cache’lenmiş değer) kullanın.</p></li></ul></li><li><p><strong>Soru–cevap sıralaması</strong></p><ul><li><p>Kabul edilen cevabı “sabit sırada” göstermek için:</p><ul><li><p>Ya sadece <strong>o sayfadaki</strong> postları alıp (first post + accepted post + o sayfadaki diğerleri) PHP’de sıralayın,</p></li><li><p>Ya da “ilk mesaj + kabul edilen mesaj”ı ayrı sorgulayıp, kalanı <code data-backticks=\"1\">ORDER BY id LIMIT/OFFSET</code> ile getirin ve birleştirin.</p></li></ul></li><li><p>Hiçbir senaryoda konunun <strong>tüm</strong> mesajlarını çekmeyin.</p></li></ul></li><li><p><strong>Bellek ve süre</strong></p><ul><li><p>Tek istekte sadece <code data-backticks=\"1\">perPage</code> (ör. 15–25) mesaj yükleneceği için bellek ve süre sınırlarına uyum sağlanır.</p></li></ul></li></ol><p>Bu değişiklik yapılmadan <strong>çok mesajlı konularla</strong> (binlerce/on binlerce mesaj) bu ölçekteki bir forum açıldığında <strong>donma, kasma ve çökme kaçınılmazdır.</strong></p><div contenteditable=\"false\"><hr></div><h2>4. Diğer Sorgular (Kısa Özet)</h2><ul><li><p><strong>Forum konu listesi</strong> (<code data-backticks=\"1\">ForumController::getTopics</code>): <code data-backticks=\"1\">limit($perPage)</code> var, sayfa başına 20–100 konu. Sayfa parametresi (offset) yok; eklenirse derin sayfalarda (örn. 4M konuluk forumda 100. sayfa) büyük <code data-backticks=\"1\">OFFSET</code> yavaşlatabilir; ileride keyset/cursor sayfalama düşünülebilir.</p></li><li><p><strong>Arama</strong> (<code data-backticks=\"1\">SearchController</code>): offset/limit ile sayfalanıyor; 15 sonuç/sayfa.</p></li><li><p><strong>forum_stats</strong>: Tek satır güncelleme + cache; 4M/29M sayıları için sorun yok.</p></li><li><p><strong>Sitemap</strong>: 50.000 konu/sitemap limiti var; makul.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>5. Sunucu / Veritabanı Önerileri</h2><ul><li><p><strong>MySQL/MariaDB:</strong></p><ul><li><p><code data-backticks=\"1\">innodb_buffer_pool_size</code> yeterli (örn. sunucu RAM’inin büyük kısmı), bağlantı sayısı, timeout değerleri.</p></li><li><p>29M satırda FULLTEXT kullanacaksanız InnoDB FULLTEXT ayarları veya harici arama (Meilisearch vb.) önerilir.</p></li></ul></li><li><p><strong>PHP:</strong></p><ul><li><p><code data-backticks=\"1\">memory_limit</code> (tek istekte artık sadece bir sayfa mesaj yükleneceği için makul bir değer yeterli).</p></li><li><p><code data-backticks=\"1\">max_execution_time</code>; uzun süren tek işlemler (import, cron) için gerekirse <code data-backticks=\"1\">set_time_limit</code> kullanılıyor.</p></li></ul></li><li><p><strong>Import:</strong></p><ul><li><p>4.3M konu / 29M mesaj için: toplu (batch) insert, mümkünse geçici olarak FK/indeksleri hafifletme, chunk’lar halinde işlem, hata loglama. Mevcut <code data-backticks=\"1\">import_progress</code> / <code data-backticks=\"1\">import_id_map</code> yapısı bu sürece uyarlanabilir.</p></li></ul></li></ul><div contenteditable=\"false\"><hr></div><h2>6. Sonuç ve Aksiyon Listesi</h2><table><thead><tr><th><p>Öncelik</p></th><th><p>Yapılacak</p></th><th><p>Sonuç</p></th></tr></thead><tbody><tr><td><p><strong>Kritik</strong></p></td><td><p>Konu detayında mesajları DB’de LIMIT/OFFSET ile sayfala; <code data-backticks=\"1\">getPosts()</code> içinde tüm konuyu <code data-backticks=\"1\">get()</code> ile çekmeyi kaldır.</p></td><td><p>Büyük konularda donma/çökme riski ortadan kalkar.</p></td></tr><tr><td><p>Yüksek</p></td><td><p>Soru–cevap “kabul edilen cevap” sıralamasını, sadece o sayfadaki veriyle veya ek küçük sorgularla yap.</p></td><td><p>Aynı sayfalama ile uyumlu, performans korunur.</p></td></tr><tr><td><p>Orta</p></td><td><p>Forum konu listesine sayfa numarası (offset) eklenirse, derin sayfalar için keyset pagination planla.</p></td><td><p>4M konuluk forumlarda ileri sayfalar yavaşlamaz.</p></td></tr><tr><td><p>Orta</p></td><td><p>FULLTEXT’i 29M post ile test et; gerekirse Meilisearch/Elasticsearch veya MySQL tuning.</p></td><td><p>Arama performansı ve stabilite.</p></td></tr><tr><td><p>Düşük</p></td><td><p><code data-backticks=\"1\">topic_reads</code> büyümesini izle; gerekirse arşivleme veya sınır.</p></td><td><p>Uzun vadede tablo boyutu kontrol altında.</p></td></tr></tbody></table><p><strong>Nihai cevap:</strong>Veritabanı yapısı ve tablo/indeks tasarımı <strong>4.3M konu, 29M mesaj, 217K üye</strong> ölçeğini destekleyebilir. Ancak <strong>konu içi mesajların tamamını belleğe çeken mevcut kod</strong> bu ölçekte <strong>kesinlikle</strong> donma, kasma ve çökmeye yol açar. Bu tek kritik nokta düzeltildikten sonra, uygun sunucu ve DB ayarlarıyla sistem bu büyüklükteki bir forumu taşıyabilir; düzeltilmeden büyük veriyle canlıya alınmamalı.</p>', 0, 0, 1, '2026-03-04 17:47:18', '2026-03-04 17:47:18', NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `posts` (`id`, `topic_id`, `reply_to_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(95, 46, NULL, 1, '<h1>DataLife Engine (DLE) vs MegaforBB Karşılaştırması</h1><p>Bu belge, DataLife Engine CMS özellikleri ile MegaforBB forum yazılımının mevcut özelliklerini karşılaştırır ve <strong>DLE’de olup MegaforBB’de olmayan</strong> maddeleri listeler. Son bölümde forum için önerilen <strong>yapılacaklar listesi</strong> yer alır.</p><div contenteditable=\"false\"><hr></div><h2>1. Genel Karşılaştırma Özeti</h2><table><thead><tr><th><p>Özellik</p></th><th><p>DLE</p></th><th><p>MegaforBB</p></th></tr></thead><tbody><tr><td><p>MySQL ile veri saklama</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Düşük veritabanı yükü</p></td><td><p>✅</p></td><td><p>✅ (Eloquent, cache)</p></td></tr><tr><td><p>AJAX kullanımı</p></td><td><p>✅</p></td><td><p>✅ (API, bildirimler, portal, etiketler vb.)</p></td></tr><tr><td><p>SEO dostu URL (mod_rewrite)</p></td><td><p>✅</p></td><td><p>✅ (SEF, slug / url_key)</p></td></tr><tr><td><p>SEO URL’i kapatma seçeneği</p></td><td><p>✅</p></td><td><p>✅ (ayarlanabilir)</p></td></tr><tr><td><p>Genel site istatistikleri</p></td><td><p>✅</p></td><td><p>✅ (forum_stats, admin dashboard)</p></td></tr><tr><td><p>Ek alanlar (özel alanlar)</p></td><td><p>✅</p></td><td><p>✅ (custom fields)</p></td></tr><tr><td><p>Çok sayfalı makale</p></td><td><p>✅</p></td><td><p>❌ (forumda konu tek sayfa; portal makale tek sayfa)</p></td></tr><tr><td><p>Flood kontrolü</p></td><td><p>✅</p></td><td><p>✅ (cooldown’lar, rate limit)</p></td></tr><tr><td><p>Yorumlarda otomatik kelime filtresi</p></td><td><p>✅</p></td><td><p>✅ (censorship)</p></td></tr><tr><td><p>Kategori desteği</p></td><td><p>✅</p></td><td><p>✅ (forum kategorileri, iç içe)</p></td></tr><tr><td><p>İç içe kategoriler</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Kategori başına ayrı şablon</p></td><td><p>✅</p></td><td><p>⚠️ (tema var, kategoriye özel şablon yok)</p></td></tr><tr><td><p>Yorumda uzun kelime kesme</p></td><td><p>✅</p></td><td><p>✅ (CSS break-word)</p></td></tr><tr><td><p>Makale/konu puanlama</p></td><td><p>✅</p></td><td><p>✅ (likes, votes, reputation)</p></td></tr><tr><td><p>Takvim</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Makalelerde arama (ek alanlar dahil, vurgulama)</p></td><td><p>✅</p></td><td><p>✅ (arama + Meilisearch, vurgulama kontrol edilmeli)</p></td></tr><tr><td><p>Son ziyaretten beri okunmamış</p></td><td><p>✅</p></td><td><p>✅ (konu bazlı is_unread)</p></td></tr><tr><td><p>Okunma sayacı</p></td><td><p>✅</p></td><td><p>✅ (view_count)</p></td></tr><tr><td><p>Favorilere ekleme</p></td><td><p>✅</p></td><td><p>✅ (abonelik / subscriptions)</p></td></tr><tr><td><p>Siteden iletişim formu</p></td><td><p>✅</p></td><td><p>✅ (iletişim formu)</p></td></tr><tr><td><p>Gzip ile sayfa sıkıştırma</p></td><td><p>✅</p></td><td><p>✅ (Performance ayarı)</p></td></tr><tr><td><p>Kişisel mesajlar</p></td><td><p>✅</p></td><td><p>✅ (Conversations)</p></td></tr><tr><td><p>Çoklu dil</p></td><td><p>✅</p></td><td><p>✅ (lang, locale)</p></td></tr><tr><td><p>Popüler içerik bloğu</p></td><td><p>✅</p></td><td><p>✅ (portal, en çok okunan)</p></td></tr><tr><td><p>Yönetimden istatistik sayfaları</p></td><td><p>✅</p></td><td><p>⚠️ (dashboard var, özel istatistik sayfası oluşturma yok)</p></td></tr><tr><td><p>Basit / gelişmiş kayıt (e-posta aktivasyonu)</p></td><td><p>✅</p></td><td><p>✅ (aktivasyon, davet)</p></td></tr><tr><td><p>Dosya ekleme / ek yükleme</p></td><td><p>✅</p></td><td><p>✅ (attachments)</p></td></tr><tr><td><p>Yetkisiz indirmeye karşı koruma (antileech)</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>RSS içe aktarma</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>RSS informer’lar</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Haberlerde çoklu dil</p></td><td><p>✅</p></td><td><p>⚠️ (içerik diline göre ayrım yok)</p></td></tr><tr><td><p>Tag cloud</p></td><td><p>✅</p></td><td><p>✅ (etiketler)</p></td></tr><tr><td><p>Otomatik akıllı telefon desteği</p></td><td><p>✅</p></td><td><p>✅ (responsive)</p></td></tr></tbody></table><div contenteditable=\"false\"><hr></div><h2>2. Kullanıcı Özellikleri</h2><table><thead><tr><th><p>Özellik</p></th><th><p>DLE</p></th><th><p>MegaforBB</p></th></tr></thead><tbody><tr><td><p>Kayıt</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Yorum ekleme</p></td><td><p>✅</p></td><td><p>✅ (cevap/mesaj)</p></td></tr><tr><td><p>Kendi yorumunu düzenleme/silme</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Haber/makale ekleme</p></td><td><p>✅</p></td><td><p>✅ (konu/makale açma)</p></td></tr><tr><td><p>İçerik moderasyonu</p></td><td><p>✅</p></td><td><p>✅ (rapor, onay)</p></td></tr><tr><td><p>Profil resmi yükleme</p></td><td><p>✅</p></td><td><p>✅ (avatar, cover)</p></td></tr><tr><td><p>Şifre kurtarma</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Sitede içerik düzenleme</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Tema değiştirme</p></td><td><p>✅</p></td><td><p>✅ (frontend temaları)</p></td></tr><tr><td><p>Favorilere ekleme ve hızlı erişim</p></td><td><p>✅</p></td><td><p>✅ (abonelikler)</p></td></tr><tr><td><p>Video görüntüleme/ekleme</p></td><td><p>✅</p></td><td><p>⚠️ (embed/ek dosya ile mümkün, özel video modülü yok)</p></td></tr><tr><td><p>Tek tıkla çoklu resim/dosya yükleme</p></td><td><p>✅</p></td><td><p>✅ (attachment)</p></td></tr><tr><td><p>Kullanıcı bazlı istatistik (puan, profil)</p></td><td><p>✅</p></td><td><p>✅ (reputation, profil sekmeleri)</p></td></tr><tr><td><p>Kayıtlı / misafir için farklı içerik</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Grup bazlı kişiselleştirilmiş reklam</p></td><td><p>✅</p></td><td><p>✅ (reklam pozisyonları)</p></td></tr><tr><td><p>Tek tıkla şikayet (hata, haber, yorum, özel mesaj)</p></td><td><p>✅</p></td><td><p>✅ (rapor sistemi)</p></td></tr><tr><td><p>Gruplar için tam erişim özelleştirme</p></td><td><p>✅</p></td><td><p>✅ (roller, izinler)</p></td></tr></tbody></table><div contenteditable=\"false\"><hr></div><h2>3. Yönetici Özellikleri</h2><table><thead><tr><th><p>Özellik</p></th><th><p>DLE</p></th><th><p>MegaforBB</p></th></tr></thead><tbody><tr><td><p>Haber ekleme/düzenleme/silme</p></td><td><p>✅</p></td><td><p>✅ (konu/makale)</p></td></tr><tr><td><p>Gruplara göre özelleştirilebilir yönetim paneli</p></td><td><p>✅</p></td><td><p>✅ (roller)</p></td></tr><tr><td><p>İki editör (BBCode / WYSIWYG)</p></td><td><p>✅</p></td><td><p>✅ (CKEditor, ToastUI)</p></td></tr><tr><td><p>Yükleme dosyalarında virüs taraması (Anti-Virus)</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Kullanıcı düzenleme</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Emoticon ve HTML</p></td><td><p>✅</p></td><td><p>✅ (smileys, HTML ayarı)</p></td></tr><tr><td><p>İnce erişim haklı kullanıcı grupları</p></td><td><p>✅</p></td><td><p>✅ (roller, izinler)</p></td></tr><tr><td><p>Kullanıcı banlama</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Yönetimde şablon oluşturma/düzenleme</p></td><td><p>✅</p></td><td><p>✅ (tema editörü)</p></td></tr><tr><td><p>Zaman dilimi ayarı</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Yayın tarihi ayarı</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Belirli saatte otomatik yayınlama</p></td><td><p>✅</p></td><td><p>✅ (scheduled_publish_at, cron)</p></td></tr><tr><td><p>Takvim ve arşivi kapatma</p></td><td><p>✅</p></td><td><p>N/A (takvim yok)</p></td></tr><tr><td><p>Haberi sabitleme (her zaman üstte)</p></td><td><p>✅</p></td><td><p>✅ (sticky)</p></td></tr><tr><td><p>Ziyaretçi kaydını kapatma</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Görsellere filigran (watermark)</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Haber başına görsel yükleme (silince görseller de silinsin)</p></td><td><p>✅</p></td><td><p>✅ (ekler konuyla ilişkili)</p></td></tr><tr><td><p>Yüklenen görseller yöneticisi</p></td><td><p>✅</p></td><td><p>✅ (ek yönetimi)</p></td></tr><tr><td><p>IP ile kullanıcı arama</p></td><td><p>✅</p></td><td><p>⚠️ (log/analytics’te IP var; “kullanıcıyı IP’den bul” arayüzü sınırlı)</p></td></tr><tr><td><p>Reklam materyalleri yönetimi</p></td><td><p>✅</p></td><td><p>✅ (Ads)</p></td></tr><tr><td><p>Veritabanında hızlı arama ve değiştirme</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Sitede kurallar yayınlama</p></td><td><p>✅</p></td><td><p>✅ (statik sayfa / duyuru ile yapılabilir)</p></td></tr><tr><td><p>Google için sitemap</p></td><td><p>✅</p></td><td><p>✅ (sitemap.xml)</p></td></tr><tr><td><p>Kelime/anlam otomatik değiştirme filtreleri</p></td><td><p>✅</p></td><td><p>✅ (censorship)</p></td></tr><tr><td><p>Maksimum üye sayısında kaydı geçici durdurma</p></td><td><p>✅</p></td><td><p>⚠️ (davet/onay ile sınırlama var; “max üye” ayarı net değil)</p></td></tr><tr><td><p>Yüklenen görselleri oranı koruyarak küçültme</p></td><td><p>✅</p></td><td><p>⚠️ (image_optimize ayarı var; davranış net değil)</p></td></tr><tr><td><p>Belirli süre gelmeyen kullanıcıları otomatik silme</p></td><td><p>✅</p></td><td><p>✅ (Spam/Zombie, pasif kullanıcı askıya alma)</p></td></tr><tr><td><p>Veritabanı optimizasyonu, onarım, yedekleme ve geri yükleme (script içinden)</p></td><td><p>✅</p></td><td><p>❌</p></td></tr></tbody></table><div contenteditable=\"false\"><hr></div><h2>4. DLE’de Olup MegaforBB’de Olmayan veya Zayıf Olanlar (Özet)</h2><ol><li><p><strong>Takvim</strong> – Etkinlik / doğum günü takvimi yok.</p></li><li><p><strong>RSS</strong> – RSS içe aktarma ve RSS informer (feed) yok.</p></li><li><p><strong>Antileech</strong> – Dosya/ek indirmede referrer kontrolü, yetkisiz hotlink koruması yok.</p></li><li><p><strong>Dosya virüs taraması (Anti-Virus)</strong> – Yüklenen dosyalar için entegre virüs taraması yok.</p></li><li><p><strong>Görsel filigran (watermark)</strong> – Yüklenen görsellere otomatik filigran yok.</p></li><li><p><strong>Veritabanı araçları</strong> – Yönetim panelinden DB yedekleme, geri yükleme, optimizasyon ve onarım yok.</p></li><li><p><strong>Veritabanında hızlı arama ve değiştirme</strong> – Admin panelinde “search &amp; replace in DB” aracı yok.</p></li><li><p><strong>Kategori başına ayrı şablon</strong> – Tema var; kategori/forum bazlı farklı şablon seçimi yok.</p></li><li><p><strong>Çok sayfalı makale</strong> – Tek konu/makale için “sayfa 1, 2, 3” bölümleme yok (forum mantığına uzak; isteğe bağlı).</p></li><li><p><strong>IP ile kullanıcı arama</strong> – IP’den kullanıcı listeleme/arama arayüzü sınırlı veya yok.</p></li><li><p><strong>Maksimum üye sayısında kaydı otomatik durdurma</strong> – Açık “max üye” limiti ve buna göre kapanan kayıt ayarı belirsiz.</p></li><li><p><strong>Yüklenen görselleri otomatik küçültme</strong> – “Oranı koruyarak max genişlik/yükseklik” gibi net bir ayar/uygulama belirsiz (image_optimize var).</p></li></ol><div contenteditable=\"false\"><hr></div><h2>5. Forum İçin Önerilen Yapılacaklar Listesi</h2><p>Aşağıdaki liste, DLE karşılaştırması ve tipik forum ihtiyaçları dikkate alınarak hazırlanmıştır. Öncelik sizin kullanım senaryonuza göre değiştirilebilir.</p><h3>Yüksek öncelik (güvenlik / temel işlev)</h3><ul><li class=\"task-list-item\" data-task=\"true\"><p><strong>RSS / Atom feed</strong>Konu listesi ve/veya forum bazlı RSS/Atom feed’leri (son konular, son cevaplar). İsteğe bağlı: kullanıcı bazlı “takip ettiğim forumlar” feed’i.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Antileech (ek indirme koruması)</strong>Ek indirme URL’lerinde referrer ve/veya token kontrolü; doğrudan link paylaşımında indirmeyi sınırlama veya engelleme.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Yüklenen dosyalarda virüs taraması (isteğe bağlı)</strong>Sunucu tarafında ClamAV veya harici bir API ile yüklenen dosyaları tarama; riskli dosyaları reddetme veya karantinaya alma.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Kullanıcılar için 2FA (iki faktörlü doğrulama)</strong>Şu an sadece admin panelinde 2FA var; normal üyeler için TOTP (Authenticator) veya e-posta kodu ile 2FA eklenmesi.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>IP ile kullanıcı arama (admin)</strong>Admin panelinde “bu IP’ye sahip kullanıcılar” listesi; log ve moderasyon için IP bazlı arama.</p></li></ul><h3>Orta öncelik (yönetim / kullanıcı deneyimi)</h3><ul><li class=\"task-list-item\" data-task=\"true\"><p><strong>Veritabanı yedekleme ve geri yükleme (admin)</strong>Yönetim panelinden tek tıkla (veya zamanlanmış) DB yedekleme; isteğe bağlı geri yükleme (dikkatli kullanım uyarısı ile).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Veritabanı optimizasyonu / onarımı (admin)</strong>Tablolar için OPTIMIZE / REPAIR benzeri işlemlerin panelden çalıştırılabilmesi (büyük sitelerde dikkatli).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Takvim (etkinlikler / doğum günleri)</strong>Basit bir takvim sayfası: duyuru tarihleri, etkinlik tarihleri veya üye doğum günleri (profilde doğum günü alanı gerekir).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>“Son ziyaretten beri okunmamış” listesi</strong>Zaten konu bazlı is_unread var; “tüm okunmamış konular” için tek bir liste sayfası veya filtre (örn. forum ana sayfada “Okunmamış” sekmesi).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Arama sonuçlarında vurgulama (highlight)</strong>Arama sonuçlarında veya konu içinde aranan kelimenin vurgulanması (Meilisearch/MySQL full-text ile entegre).</p></li></ul><h3>Düşük öncelik / isteğe bağlı</h3><ul><li class=\"task-list-item\" data-task=\"true\"><p><strong>Görsel filigran (watermark)</strong>Yüklenen görsellere (özellikle galeri/eklerde) metin veya logo filigranı ekleme seçeneği (ayarlanabilir: açık/kapalı, metin, konum).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Yüklenen görselleri otomatik küçültme</strong>Maksimum genişlik/yükseklik ve kalite ayarı; oran korunarak yeniden boyutlandırma ve isteğe bağlı WebP dönüşümü.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Veritabanında arama ve değiştirme (admin)</strong>Sadece yetkili admin’lere açık, tehlikeli işlem uyarılı “belirli tablo/sütunda metin ara ve değiştir” aracı.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Kategori / forum bazlı şablon seçimi</strong>Belirli kategorilerde veya forumlarda farklı tema/şablon kullanma (örn. özel forum görünümü).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Maksimum üye sayısı limiti</strong>Ayarlanabilir “maksimum kayıtlı üye” sayısı; bu sayıya ulaşınca yeni kayıt formunun kapatılması veya sadece davet moduna geçilmesi.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>RSS dış kaynaklardan içe aktarma</strong>Belirli RSS feed’lerinden otomatik konu veya duyuru oluşturma (portal/haber tarafı için; forumda düşük öncelik).</p></li></ul><div contenteditable=\"false\"><hr></div><h2>6. Sonuç</h2><ul><li><p><strong>MegaforBB</strong>, DLE’nin birçok genel ve yönetici özelliğini (kayıt, moderasyon, roller, flood, sitemap, planlı yayın, sıkıştırma, çoklu dil, tema, reklam, istatistik vb.) karşılıyor veya forum mantığına uyarlıyor.</p></li><li><p><strong>Eksik veya zayıf</strong> alanlar: takvim, RSS (feed + import), antileech, dosya virüs taraması, filigran, veritabanı yedekleme/optimizasyon/arama-değiştirme, kullanıcı 2FA ve bazı ince ayarlar (max üye, kategori şablonu, otomatik görsel küçültme).</p></li><li><p><strong>Yapılacaklar listesi</strong> özellikle güvenlik (antileech, virüs taraması, 2FA), içerik takibi (RSS) ve yönetim kolaylığı (DB yedekleme, IP arama) için kullanılabilir; takvim ve filigran gibi maddeler ihtiyaca göre sonraya bırakılabilir.</p></li></ul><p>Bu belge, mevcut kod tabanı ve DLE özellik listesine göre hazırlanmıştır; yeni özellikler eklendikçe güncellenebilir.</p>', '<h1>DataLife Engine (DLE) vs MegaforBB Karşılaştırması</h1><p>Bu belge, DataLife Engine CMS özellikleri ile MegaforBB forum yazılımının mevcut özelliklerini karşılaştırır ve <strong>DLE’de olup MegaforBB’de olmayan</strong> maddeleri listeler. Son bölümde forum için önerilen <strong>yapılacaklar listesi</strong> yer alır.</p><div contenteditable=\"false\"><hr></div><h2>1. Genel Karşılaştırma Özeti</h2><table><thead><tr><th><p>Özellik</p></th><th><p>DLE</p></th><th><p>MegaforBB</p></th></tr></thead><tbody><tr><td><p>MySQL ile veri saklama</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Düşük veritabanı yükü</p></td><td><p>✅</p></td><td><p>✅ (Eloquent, cache)</p></td></tr><tr><td><p>AJAX kullanımı</p></td><td><p>✅</p></td><td><p>✅ (API, bildirimler, portal, etiketler vb.)</p></td></tr><tr><td><p>SEO dostu URL (mod_rewrite)</p></td><td><p>✅</p></td><td><p>✅ (SEF, slug / url_key)</p></td></tr><tr><td><p>SEO URL’i kapatma seçeneği</p></td><td><p>✅</p></td><td><p>✅ (ayarlanabilir)</p></td></tr><tr><td><p>Genel site istatistikleri</p></td><td><p>✅</p></td><td><p>✅ (forum_stats, admin dashboard)</p></td></tr><tr><td><p>Ek alanlar (özel alanlar)</p></td><td><p>✅</p></td><td><p>✅ (custom fields)</p></td></tr><tr><td><p>Çok sayfalı makale</p></td><td><p>✅</p></td><td><p>❌ (forumda konu tek sayfa; portal makale tek sayfa)</p></td></tr><tr><td><p>Flood kontrolü</p></td><td><p>✅</p></td><td><p>✅ (cooldown’lar, rate limit)</p></td></tr><tr><td><p>Yorumlarda otomatik kelime filtresi</p></td><td><p>✅</p></td><td><p>✅ (censorship)</p></td></tr><tr><td><p>Kategori desteği</p></td><td><p>✅</p></td><td><p>✅ (forum kategorileri, iç içe)</p></td></tr><tr><td><p>İç içe kategoriler</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Kategori başına ayrı şablon</p></td><td><p>✅</p></td><td><p>⚠️ (tema var, kategoriye özel şablon yok)</p></td></tr><tr><td><p>Yorumda uzun kelime kesme</p></td><td><p>✅</p></td><td><p>✅ (CSS break-word)</p></td></tr><tr><td><p>Makale/konu puanlama</p></td><td><p>✅</p></td><td><p>✅ (likes, votes, reputation)</p></td></tr><tr><td><p>Takvim</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Makalelerde arama (ek alanlar dahil, vurgulama)</p></td><td><p>✅</p></td><td><p>✅ (arama + Meilisearch, vurgulama kontrol edilmeli)</p></td></tr><tr><td><p>Son ziyaretten beri okunmamış</p></td><td><p>✅</p></td><td><p>✅ (konu bazlı is_unread)</p></td></tr><tr><td><p>Okunma sayacı</p></td><td><p>✅</p></td><td><p>✅ (view_count)</p></td></tr><tr><td><p>Favorilere ekleme</p></td><td><p>✅</p></td><td><p>✅ (abonelik / subscriptions)</p></td></tr><tr><td><p>Siteden iletişim formu</p></td><td><p>✅</p></td><td><p>✅ (iletişim formu)</p></td></tr><tr><td><p>Gzip ile sayfa sıkıştırma</p></td><td><p>✅</p></td><td><p>✅ (Performance ayarı)</p></td></tr><tr><td><p>Kişisel mesajlar</p></td><td><p>✅</p></td><td><p>✅ (Conversations)</p></td></tr><tr><td><p>Çoklu dil</p></td><td><p>✅</p></td><td><p>✅ (lang, locale)</p></td></tr><tr><td><p>Popüler içerik bloğu</p></td><td><p>✅</p></td><td><p>✅ (portal, en çok okunan)</p></td></tr><tr><td><p>Yönetimden istatistik sayfaları</p></td><td><p>✅</p></td><td><p>⚠️ (dashboard var, özel istatistik sayfası oluşturma yok)</p></td></tr><tr><td><p>Basit / gelişmiş kayıt (e-posta aktivasyonu)</p></td><td><p>✅</p></td><td><p>✅ (aktivasyon, davet)</p></td></tr><tr><td><p>Dosya ekleme / ek yükleme</p></td><td><p>✅</p></td><td><p>✅ (attachments)</p></td></tr><tr><td><p>Yetkisiz indirmeye karşı koruma (antileech)</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>RSS içe aktarma</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>RSS informer’lar</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Haberlerde çoklu dil</p></td><td><p>✅</p></td><td><p>⚠️ (içerik diline göre ayrım yok)</p></td></tr><tr><td><p>Tag cloud</p></td><td><p>✅</p></td><td><p>✅ (etiketler)</p></td></tr><tr><td><p>Otomatik akıllı telefon desteği</p></td><td><p>✅</p></td><td><p>✅ (responsive)</p></td></tr></tbody></table><div contenteditable=\"false\"><hr></div><h2>2. Kullanıcı Özellikleri</h2><table><thead><tr><th><p>Özellik</p></th><th><p>DLE</p></th><th><p>MegaforBB</p></th></tr></thead><tbody><tr><td><p>Kayıt</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Yorum ekleme</p></td><td><p>✅</p></td><td><p>✅ (cevap/mesaj)</p></td></tr><tr><td><p>Kendi yorumunu düzenleme/silme</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Haber/makale ekleme</p></td><td><p>✅</p></td><td><p>✅ (konu/makale açma)</p></td></tr><tr><td><p>İçerik moderasyonu</p></td><td><p>✅</p></td><td><p>✅ (rapor, onay)</p></td></tr><tr><td><p>Profil resmi yükleme</p></td><td><p>✅</p></td><td><p>✅ (avatar, cover)</p></td></tr><tr><td><p>Şifre kurtarma</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Sitede içerik düzenleme</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Tema değiştirme</p></td><td><p>✅</p></td><td><p>✅ (frontend temaları)</p></td></tr><tr><td><p>Favorilere ekleme ve hızlı erişim</p></td><td><p>✅</p></td><td><p>✅ (abonelikler)</p></td></tr><tr><td><p>Video görüntüleme/ekleme</p></td><td><p>✅</p></td><td><p>⚠️ (embed/ek dosya ile mümkün, özel video modülü yok)</p></td></tr><tr><td><p>Tek tıkla çoklu resim/dosya yükleme</p></td><td><p>✅</p></td><td><p>✅ (attachment)</p></td></tr><tr><td><p>Kullanıcı bazlı istatistik (puan, profil)</p></td><td><p>✅</p></td><td><p>✅ (reputation, profil sekmeleri)</p></td></tr><tr><td><p>Kayıtlı / misafir için farklı içerik</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Grup bazlı kişiselleştirilmiş reklam</p></td><td><p>✅</p></td><td><p>✅ (reklam pozisyonları)</p></td></tr><tr><td><p>Tek tıkla şikayet (hata, haber, yorum, özel mesaj)</p></td><td><p>✅</p></td><td><p>✅ (rapor sistemi)</p></td></tr><tr><td><p>Gruplar için tam erişim özelleştirme</p></td><td><p>✅</p></td><td><p>✅ (roller, izinler)</p></td></tr></tbody></table><div contenteditable=\"false\"><hr></div><h2>3. Yönetici Özellikleri</h2><table><thead><tr><th><p>Özellik</p></th><th><p>DLE</p></th><th><p>MegaforBB</p></th></tr></thead><tbody><tr><td><p>Haber ekleme/düzenleme/silme</p></td><td><p>✅</p></td><td><p>✅ (konu/makale)</p></td></tr><tr><td><p>Gruplara göre özelleştirilebilir yönetim paneli</p></td><td><p>✅</p></td><td><p>✅ (roller)</p></td></tr><tr><td><p>İki editör (BBCode / WYSIWYG)</p></td><td><p>✅</p></td><td><p>✅ (CKEditor, ToastUI)</p></td></tr><tr><td><p>Yükleme dosyalarında virüs taraması (Anti-Virus)</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Kullanıcı düzenleme</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Emoticon ve HTML</p></td><td><p>✅</p></td><td><p>✅ (smileys, HTML ayarı)</p></td></tr><tr><td><p>İnce erişim haklı kullanıcı grupları</p></td><td><p>✅</p></td><td><p>✅ (roller, izinler)</p></td></tr><tr><td><p>Kullanıcı banlama</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Yönetimde şablon oluşturma/düzenleme</p></td><td><p>✅</p></td><td><p>✅ (tema editörü)</p></td></tr><tr><td><p>Zaman dilimi ayarı</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Yayın tarihi ayarı</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Belirli saatte otomatik yayınlama</p></td><td><p>✅</p></td><td><p>✅ (scheduled_publish_at, cron)</p></td></tr><tr><td><p>Takvim ve arşivi kapatma</p></td><td><p>✅</p></td><td><p>N/A (takvim yok)</p></td></tr><tr><td><p>Haberi sabitleme (her zaman üstte)</p></td><td><p>✅</p></td><td><p>✅ (sticky)</p></td></tr><tr><td><p>Ziyaretçi kaydını kapatma</p></td><td><p>✅</p></td><td><p>✅</p></td></tr><tr><td><p>Görsellere filigran (watermark)</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Haber başına görsel yükleme (silince görseller de silinsin)</p></td><td><p>✅</p></td><td><p>✅ (ekler konuyla ilişkili)</p></td></tr><tr><td><p>Yüklenen görseller yöneticisi</p></td><td><p>✅</p></td><td><p>✅ (ek yönetimi)</p></td></tr><tr><td><p>IP ile kullanıcı arama</p></td><td><p>✅</p></td><td><p>⚠️ (log/analytics’te IP var; “kullanıcıyı IP’den bul” arayüzü sınırlı)</p></td></tr><tr><td><p>Reklam materyalleri yönetimi</p></td><td><p>✅</p></td><td><p>✅ (Ads)</p></td></tr><tr><td><p>Veritabanında hızlı arama ve değiştirme</p></td><td><p>✅</p></td><td><p>❌</p></td></tr><tr><td><p>Sitede kurallar yayınlama</p></td><td><p>✅</p></td><td><p>✅ (statik sayfa / duyuru ile yapılabilir)</p></td></tr><tr><td><p>Google için sitemap</p></td><td><p>✅</p></td><td><p>✅ (sitemap.xml)</p></td></tr><tr><td><p>Kelime/anlam otomatik değiştirme filtreleri</p></td><td><p>✅</p></td><td><p>✅ (censorship)</p></td></tr><tr><td><p>Maksimum üye sayısında kaydı geçici durdurma</p></td><td><p>✅</p></td><td><p>⚠️ (davet/onay ile sınırlama var; “max üye” ayarı net değil)</p></td></tr><tr><td><p>Yüklenen görselleri oranı koruyarak küçültme</p></td><td><p>✅</p></td><td><p>⚠️ (image_optimize ayarı var; davranış net değil)</p></td></tr><tr><td><p>Belirli süre gelmeyen kullanıcıları otomatik silme</p></td><td><p>✅</p></td><td><p>✅ (Spam/Zombie, pasif kullanıcı askıya alma)</p></td></tr><tr><td><p>Veritabanı optimizasyonu, onarım, yedekleme ve geri yükleme (script içinden)</p></td><td><p>✅</p></td><td><p>❌</p></td></tr></tbody></table><div contenteditable=\"false\"><hr></div><h2>4. DLE’de Olup MegaforBB’de Olmayan veya Zayıf Olanlar (Özet)</h2><ol><li><p><strong>Takvim</strong> – Etkinlik / doğum günü takvimi yok.</p></li><li><p><strong>RSS</strong> – RSS içe aktarma ve RSS informer (feed) yok.</p></li><li><p><strong>Antileech</strong> – Dosya/ek indirmede referrer kontrolü, yetkisiz hotlink koruması yok.</p></li><li><p><strong>Dosya virüs taraması (Anti-Virus)</strong> – Yüklenen dosyalar için entegre virüs taraması yok.</p></li><li><p><strong>Görsel filigran (watermark)</strong> – Yüklenen görsellere otomatik filigran yok.</p></li><li><p><strong>Veritabanı araçları</strong> – Yönetim panelinden DB yedekleme, geri yükleme, optimizasyon ve onarım yok.</p></li><li><p><strong>Veritabanında hızlı arama ve değiştirme</strong> – Admin panelinde “search &amp; replace in DB” aracı yok.</p></li><li><p><strong>Kategori başına ayrı şablon</strong> – Tema var; kategori/forum bazlı farklı şablon seçimi yok.</p></li><li><p><strong>Çok sayfalı makale</strong> – Tek konu/makale için “sayfa 1, 2, 3” bölümleme yok (forum mantığına uzak; isteğe bağlı).</p></li><li><p><strong>IP ile kullanıcı arama</strong> – IP’den kullanıcı listeleme/arama arayüzü sınırlı veya yok.</p></li><li><p><strong>Maksimum üye sayısında kaydı otomatik durdurma</strong> – Açık “max üye” limiti ve buna göre kapanan kayıt ayarı belirsiz.</p></li><li><p><strong>Yüklenen görselleri otomatik küçültme</strong> – “Oranı koruyarak max genişlik/yükseklik” gibi net bir ayar/uygulama belirsiz (image_optimize var).</p></li></ol><div contenteditable=\"false\"><hr></div><h2>5. Forum İçin Önerilen Yapılacaklar Listesi</h2><p>Aşağıdaki liste, DLE karşılaştırması ve tipik forum ihtiyaçları dikkate alınarak hazırlanmıştır. Öncelik sizin kullanım senaryonuza göre değiştirilebilir.</p><h3>Yüksek öncelik (güvenlik / temel işlev)</h3><ul><li class=\"task-list-item\" data-task=\"true\"><p><strong>RSS / Atom feed</strong>Konu listesi ve/veya forum bazlı RSS/Atom feed’leri (son konular, son cevaplar). İsteğe bağlı: kullanıcı bazlı “takip ettiğim forumlar” feed’i.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Antileech (ek indirme koruması)</strong>Ek indirme URL’lerinde referrer ve/veya token kontrolü; doğrudan link paylaşımında indirmeyi sınırlama veya engelleme.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Yüklenen dosyalarda virüs taraması (isteğe bağlı)</strong>Sunucu tarafında ClamAV veya harici bir API ile yüklenen dosyaları tarama; riskli dosyaları reddetme veya karantinaya alma.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Kullanıcılar için 2FA (iki faktörlü doğrulama)</strong>Şu an sadece admin panelinde 2FA var; normal üyeler için TOTP (Authenticator) veya e-posta kodu ile 2FA eklenmesi.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>IP ile kullanıcı arama (admin)</strong>Admin panelinde “bu IP’ye sahip kullanıcılar” listesi; log ve moderasyon için IP bazlı arama.</p></li></ul><h3>Orta öncelik (yönetim / kullanıcı deneyimi)</h3><ul><li class=\"task-list-item\" data-task=\"true\"><p><strong>Veritabanı yedekleme ve geri yükleme (admin)</strong>Yönetim panelinden tek tıkla (veya zamanlanmış) DB yedekleme; isteğe bağlı geri yükleme (dikkatli kullanım uyarısı ile).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Veritabanı optimizasyonu / onarımı (admin)</strong>Tablolar için OPTIMIZE / REPAIR benzeri işlemlerin panelden çalıştırılabilmesi (büyük sitelerde dikkatli).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Takvim (etkinlikler / doğum günleri)</strong>Basit bir takvim sayfası: duyuru tarihleri, etkinlik tarihleri veya üye doğum günleri (profilde doğum günü alanı gerekir).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>“Son ziyaretten beri okunmamış” listesi</strong>Zaten konu bazlı is_unread var; “tüm okunmamış konular” için tek bir liste sayfası veya filtre (örn. forum ana sayfada “Okunmamış” sekmesi).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Arama sonuçlarında vurgulama (highlight)</strong>Arama sonuçlarında veya konu içinde aranan kelimenin vurgulanması (Meilisearch/MySQL full-text ile entegre).</p></li></ul><h3>Düşük öncelik / isteğe bağlı</h3><ul><li class=\"task-list-item\" data-task=\"true\"><p><strong>Görsel filigran (watermark)</strong>Yüklenen görsellere (özellikle galeri/eklerde) metin veya logo filigranı ekleme seçeneği (ayarlanabilir: açık/kapalı, metin, konum).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Yüklenen görselleri otomatik küçültme</strong>Maksimum genişlik/yükseklik ve kalite ayarı; oran korunarak yeniden boyutlandırma ve isteğe bağlı WebP dönüşümü.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Veritabanında arama ve değiştirme (admin)</strong>Sadece yetkili admin’lere açık, tehlikeli işlem uyarılı “belirli tablo/sütunda metin ara ve değiştir” aracı.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Kategori / forum bazlı şablon seçimi</strong>Belirli kategorilerde veya forumlarda farklı tema/şablon kullanma (örn. özel forum görünümü).</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>Maksimum üye sayısı limiti</strong>Ayarlanabilir “maksimum kayıtlı üye” sayısı; bu sayıya ulaşınca yeni kayıt formunun kapatılması veya sadece davet moduna geçilmesi.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><strong>RSS dış kaynaklardan içe aktarma</strong>Belirli RSS feed’lerinden otomatik konu veya duyuru oluşturma (portal/haber tarafı için; forumda düşük öncelik).</p></li></ul><div contenteditable=\"false\"><hr></div><h2>6. Sonuç</h2><ul><li><p><strong>MegaforBB</strong>, DLE’nin birçok genel ve yönetici özelliğini (kayıt, moderasyon, roller, flood, sitemap, planlı yayın, sıkıştırma, çoklu dil, tema, reklam, istatistik vb.) karşılıyor veya forum mantığına uyarlıyor.</p></li><li><p><strong>Eksik veya zayıf</strong> alanlar: takvim, RSS (feed + import), antileech, dosya virüs taraması, filigran, veritabanı yedekleme/optimizasyon/arama-değiştirme, kullanıcı 2FA ve bazı ince ayarlar (max üye, kategori şablonu, otomatik görsel küçültme).</p></li><li><p><strong>Yapılacaklar listesi</strong> özellikle güvenlik (antileech, virüs taraması, 2FA), içerik takibi (RSS) ve yönetim kolaylığı (DB yedekleme, IP arama) için kullanılabilir; takvim ve filigran gibi maddeler ihtiyaca göre sonraya bırakılabilir.</p></li></ul><p>Bu belge, mevcut kod tabanı ve DLE özellik listesine göre hazırlanmıştır; yeni özellikler eklendikçe güncellenebilir.</p>', 0, 0, 1, '2026-03-04 18:04:43', '2026-03-04 18:04:43', NULL, NULL, 0, NULL, NULL, NULL),
(96, 47, NULL, 1, '<p>Merhaba,</p><p>MegaforBB’de eklenti ve tema geliştirirken <strong>çekirdek dosyalara hiç dokunmadan</strong> daha fazla noktaya müdahale edebilmeniz için kanca (hook) ve event altyapısını güncelledik. Aşağıda özet ve teknik detaylar var.</p><h3>Bu güncelleme ne sağlıyor?</h3><ul><li><p><strong>Yeni event’ler:</strong> Konu silme, mesaj silme, kullanıcı kayıt ve giriş olayları artık event olarak tetikleniyor. Eklentiniz bu olaylara dinleyici (listener) yazarak (örneğin loglama, bildirim, entegrasyon) çekirdeği değiştirmeden tepki verebilir.</p></li><li><p><strong>Yeni hook’lar:</strong> Sayfa başına/sonuna HTML (ör. analytics, script), konu/cevap oluşturma öncesi-sonrası, konu sayfası verisi, mesaj gösterim verisi ve profil sayfası verisi için yeni action ve filter noktaları eklendi. Eklentiler ve temalar bu noktalara bağlanarak davranışı ve görünümü özelleştirebilir.</p></li><li><p><strong>Düzeltme:</strong> Commerce eklentisinde Admin menüye öğe ekleyen hook, doğru türde (filter) tanımlandı; menü öğesi artık doğru şekilde görünüyor.</p></li></ul><p>Tüm bu noktalar <strong>çekirdeğe dokunmadan</strong>, sadece <code>plugins/</code> altındaki eklentinizin <code>plugin.php</code> dosyasından kullanılıyor.</p><h3>Yeni event’ler (dinleyici yazabileceğiniz olaylar)</h3><table><thead><tr><th><p>Olay</p></th><th><p>Ne zaman tetiklenir</p></th><th><p>Kullanım fikri</p></th></tr></thead><tbody><tr><td><p><strong>Konu silindi</strong> (<code>topic.deleted</code>)</p></td><td><p>Bir konu silindiğinde</p></td><td><p>İstatistik, log, harici sistem senkronu</p></td></tr><tr><td><p><strong>Mesaj silindi</strong> (<code>post.deleted</code>)</p></td><td><p>Toplu mesaj silme işleminde her mesaj için</p></td><td><p>Log, moderasyon kaydı</p></td></tr><tr><td><p><strong>Kullanıcı kayıt oldu</strong> (<code>user.registered</code>)</p></td><td><p>Kayıt başarıyla tamamlandığında</p></td><td><p>Hoş geldin e-postası, harici CRM/entegrasyon</p></td></tr><tr><td><p><strong>Kullanıcı giriş yaptı</strong> (<code>user.login</code>)</p></td><td><p>Giriş başarılı olduğunda</p></td><td><p>Log, 2FA, özel giriş kuralları</p></td></tr></tbody></table><p>Bu event’lere <code>plugin.php</code> içindeki <code>events</code> bölümünden listener ekleyebilirsiniz; çekirdek <code>config/events.php</code> dosyasını değiştirmeniz gerekmez.</p><h3>Yeni action hook’ları (HTML veya yan etki ekleme)</h3><table><thead><tr><th><p>Hook</p></th><th><p>Nerede</p></th><th><p>Ne için kullanılır</p></th></tr></thead><tbody><tr><td><p><strong>layout.header_extra</strong></p></td><td><p>Tüm sayfalarda, <code>&lt;head&gt;</code> sonuna yakın</p></td><td><p>Analytics, ek CSS/script, meta etiketleri</p></td></tr><tr><td><p><strong>layout.footer_extra</strong></p></td><td><p>Tüm sayfalarda, <code>&lt;/body&gt;</code> öncesi</p></td><td><p>Sayaç, chat widget, ek script</p></td></tr><tr><td><p><strong>before_topic_create</strong></p></td><td><p>Konu oluşturulmadan hemen önce</p></td><td><p>Ek validasyon, log, harici API çağrısı</p></td></tr><tr><td><p><strong>after_topic_create</strong></p></td><td><p>Konu oluşturulduktan / zamanlanmış konu yayınlandıktan sonra</p></td><td><p>Bildirim, sosyal paylaşım, indeksleme</p></td></tr><tr><td><p><strong>before_post_create</strong></p></td><td><p>Cevap (mesaj) gönderilmeden hemen önce</p></td><td><p>Ek kontrol, flood koruması</p></td></tr><tr><td><p><strong>after_post_create</strong></p></td><td><p>Cevap kaydedildikten sonra</p></td><td><p>Bildirim, log, entegrasyon</p></td></tr></tbody></table><p>Bunlar <strong>action</strong> olduğu için <code>plugin.php</code> → <code>actions</code> altında tanımlanır; döndürdüğünüz string’ler (HTML) ilgili yerde birleştirilir.</p><h3>Yeni filter hook’ları (veriyi değiştirme / zenginleştirme)</h3><table><thead><tr><th><p>Hook</p></th><th><p>Nerede</p></th><th><p>Ne için kullanılır</p></th></tr></thead><tbody><tr><td><p><strong>topic.view_data</strong></p></td><td><p>Konu (showthread) sayfası</p></td><td><p>Konu sayfasına ek veri, buton, sekme</p></td></tr><tr><td><p><strong>post.display_data</strong></p></td><td><p>Konu sayfasındaki her mesaj</p></td><td><p>Mesaja özel alan, badge, buton</p></td></tr><tr><td><p><strong>user.profile_data</strong></p></td><td><p>Üye profil sayfası</p></td><td><p>Profil alanları, sekmeler, istatistik</p></td></tr></tbody></table><p>Bunlar <strong>filter</strong> olduğu için <code>plugin.php</code> → <code>filters</code> altında tanımlanır; gelen veriyi değiştirip aynı yapıda geri döndürürsünüz.</p><h3>Eklenti / tema geliştiricileri için</h3><ul><li><p><strong>Events:</strong> <code>App\\Events\\TopicDeleted</code>, <code>PostDeleted</code>, <code>UserRegistered</code>, <code>UserLogin</code> sınıfları ve ilgili event isimleri (<code>topic.deleted</code>, <code>post.deleted</code>, <code>user.registered</code>, <code>user.login</code>) kullanıma hazır.</p></li><li><p><strong>Actions / Filters:</strong> Yukarıdaki tablolardaki hook isimlerini <code>plugin.php</code> içinde <code>actions</code> veya <code>filters</code> dizisine eklemeniz yeterli; çekirdek bu noktaları zaten tetikliyor.</p></li><li><p><strong>Detaylı rehber:</strong> Tüm hook ve event listesi, argümanlar ve kullanım örnekleri için proje içinde şu dokümanlara bakabilirsiniz:</p></li></ul><p>Bu güncellemeyle birlikte hem mevcut eklentileriniz (Commerce dahil) daha tutarlı çalışacak hem de yeni eklenti ve temalarda çekirdeğe dokunmadan daha fazla noktaya müdahale edebileceksiniz. Soru veya öneriniz varsa bu konu altından yazabilirsiniz.</p><p><br></p><p>İyi çalışmalar -MegaforBB Ekibi</p>', '<p>Merhaba,</p><p>MegaforBB’de eklenti ve tema geliştirirken <strong>çekirdek dosyalara hiç dokunmadan</strong> daha fazla noktaya müdahale edebilmeniz için kanca (hook) ve event altyapısını güncelledik. Aşağıda özet ve teknik detaylar var.</p><h3>Bu güncelleme ne sağlıyor?</h3><ul><li><p><strong>Yeni event’ler:</strong> Konu silme, mesaj silme, kullanıcı kayıt ve giriş olayları artık event olarak tetikleniyor. Eklentiniz bu olaylara dinleyici (listener) yazarak (örneğin loglama, bildirim, entegrasyon) çekirdeği değiştirmeden tepki verebilir.</p></li><li><p><strong>Yeni hook’lar:</strong> Sayfa başına/sonuna HTML (ör. analytics, script), konu/cevap oluşturma öncesi-sonrası, konu sayfası verisi, mesaj gösterim verisi ve profil sayfası verisi için yeni action ve filter noktaları eklendi. Eklentiler ve temalar bu noktalara bağlanarak davranışı ve görünümü özelleştirebilir.</p></li><li><p><strong>Düzeltme:</strong> Commerce eklentisinde Admin menüye öğe ekleyen hook, doğru türde (filter) tanımlandı; menü öğesi artık doğru şekilde görünüyor.</p></li></ul><p>Tüm bu noktalar <strong>çekirdeğe dokunmadan</strong>, sadece <code>plugins/</code> altındaki eklentinizin <code>plugin.php</code> dosyasından kullanılıyor.</p><h3>Yeni event’ler (dinleyici yazabileceğiniz olaylar)</h3><table><thead><tr><th><p>Olay</p></th><th><p>Ne zaman tetiklenir</p></th><th><p>Kullanım fikri</p></th></tr></thead><tbody><tr><td><p><strong>Konu silindi</strong> (<code>topic.deleted</code>)</p></td><td><p>Bir konu silindiğinde</p></td><td><p>İstatistik, log, harici sistem senkronu</p></td></tr><tr><td><p><strong>Mesaj silindi</strong> (<code>post.deleted</code>)</p></td><td><p>Toplu mesaj silme işleminde her mesaj için</p></td><td><p>Log, moderasyon kaydı</p></td></tr><tr><td><p><strong>Kullanıcı kayıt oldu</strong> (<code>user.registered</code>)</p></td><td><p>Kayıt başarıyla tamamlandığında</p></td><td><p>Hoş geldin e-postası, harici CRM/entegrasyon</p></td></tr><tr><td><p><strong>Kullanıcı giriş yaptı</strong> (<code>user.login</code>)</p></td><td><p>Giriş başarılı olduğunda</p></td><td><p>Log, 2FA, özel giriş kuralları</p></td></tr></tbody></table><p>Bu event’lere <code>plugin.php</code> içindeki <code>events</code> bölümünden listener ekleyebilirsiniz; çekirdek <code>config/events.php</code> dosyasını değiştirmeniz gerekmez.</p><h3>Yeni action hook’ları (HTML veya yan etki ekleme)</h3><table><thead><tr><th><p>Hook</p></th><th><p>Nerede</p></th><th><p>Ne için kullanılır</p></th></tr></thead><tbody><tr><td><p><strong>layout.header_extra</strong></p></td><td><p>Tüm sayfalarda, <code>&lt;head&gt;</code> sonuna yakın</p></td><td><p>Analytics, ek CSS/script, meta etiketleri</p></td></tr><tr><td><p><strong>layout.footer_extra</strong></p></td><td><p>Tüm sayfalarda, <code>&lt;/body&gt;</code> öncesi</p></td><td><p>Sayaç, chat widget, ek script</p></td></tr><tr><td><p><strong>before_topic_create</strong></p></td><td><p>Konu oluşturulmadan hemen önce</p></td><td><p>Ek validasyon, log, harici API çağrısı</p></td></tr><tr><td><p><strong>after_topic_create</strong></p></td><td><p>Konu oluşturulduktan / zamanlanmış konu yayınlandıktan sonra</p></td><td><p>Bildirim, sosyal paylaşım, indeksleme</p></td></tr><tr><td><p><strong>before_post_create</strong></p></td><td><p>Cevap (mesaj) gönderilmeden hemen önce</p></td><td><p>Ek kontrol, flood koruması</p></td></tr><tr><td><p><strong>after_post_create</strong></p></td><td><p>Cevap kaydedildikten sonra</p></td><td><p>Bildirim, log, entegrasyon</p></td></tr></tbody></table><p>Bunlar <strong>action</strong> olduğu için <code>plugin.php</code> → <code>actions</code> altında tanımlanır; döndürdüğünüz string’ler (HTML) ilgili yerde birleştirilir.</p><h3>Yeni filter hook’ları (veriyi değiştirme / zenginleştirme)</h3><table><thead><tr><th><p>Hook</p></th><th><p>Nerede</p></th><th><p>Ne için kullanılır</p></th></tr></thead><tbody><tr><td><p><strong>topic.view_data</strong></p></td><td><p>Konu (showthread) sayfası</p></td><td><p>Konu sayfasına ek veri, buton, sekme</p></td></tr><tr><td><p><strong>post.display_data</strong></p></td><td><p>Konu sayfasındaki her mesaj</p></td><td><p>Mesaja özel alan, badge, buton</p></td></tr><tr><td><p><strong>user.profile_data</strong></p></td><td><p>Üye profil sayfası</p></td><td><p>Profil alanları, sekmeler, istatistik</p></td></tr></tbody></table><p>Bunlar <strong>filter</strong> olduğu için <code>plugin.php</code> → <code>filters</code> altında tanımlanır; gelen veriyi değiştirip aynı yapıda geri döndürürsünüz.</p><h3>Eklenti / tema geliştiricileri için</h3><ul><li><p><strong>Events:</strong> <code>App\\Events\\TopicDeleted</code>, <code>PostDeleted</code>, <code>UserRegistered</code>, <code>UserLogin</code> sınıfları ve ilgili event isimleri (<code>topic.deleted</code>, <code>post.deleted</code>, <code>user.registered</code>, <code>user.login</code>) kullanıma hazır.</p></li><li><p><strong>Actions / Filters:</strong> Yukarıdaki tablolardaki hook isimlerini <code>plugin.php</code> içinde <code>actions</code> veya <code>filters</code> dizisine eklemeniz yeterli; çekirdek bu noktaları zaten tetikliyor.</p></li><li><p><strong>Detaylı rehber:</strong> Tüm hook ve event listesi, argümanlar ve kullanım örnekleri için proje içinde şu dokümanlara bakabilirsiniz:</p></li></ul><p>Bu güncellemeyle birlikte hem mevcut eklentileriniz (Commerce dahil) daha tutarlı çalışacak hem de yeni eklenti ve temalarda çekirdeğe dokunmadan daha fazla noktaya müdahale edebileceksiniz. Soru veya öneriniz varsa bu konu altından yazabilirsiniz.</p><p><br></p><p>İyi çalışmalar -MegaforBB Ekibi</p>', 0, 0, 1, '2026-03-04 20:53:18', '2026-03-05 00:26:05', NULL, NULL, 0, NULL, NULL, NULL),
(97, 48, NULL, 1, '<p>MegaforBB Tema geliştirmek isteyenler için detaylı ve kapsamlı rehber hazırladık.</p><p>Burada <strong><em><del><a href=\"https://www.megaforbb.com.tr/documentation/megaforbb-tema/tema-geli-tirme-k-lavuzu\">MegaforBB Tema geliştirme</a></del></em></strong> dökümanına ulaşabilirsiniz.</p><p><br></p><p>Sistemde kullanılan varsayılan tematasarımı hoşunuza gitmemesini anlayışla karşılıyoruz :)</p>', '<p>MegaforBB Tema geliştirmek isteyenler için detaylı ve kapsamlı rehber hazırladık.</p><p>Burada <strong><em><del><a href=\"https://www.megaforbb.com.tr/documentation/megaforbb-tema/tema-geli-tirme-k-lavuzu\">MegaforBB Tema geliştirme</a></del></em></strong> dökümanına ulaşabilirsiniz.</p><p><br></p><p>Sistemde kullanılan varsayılan tematasarımı hoşunuza gitmemesini anlayışla karşılıyoruz :)</p>', 0, 0, 1, '2026-03-04 21:18:54', '2026-03-04 21:29:07', NULL, NULL, 0, NULL, NULL, NULL),
(98, 49, NULL, 1, '<p>Merhabalar, MegaforBB Forum sistemimiz için her geçen gün daha da gggüncelelmee ve gelilştirme yaparak sistemi ilerletiyoruz. bu süreçte de tüm yeniliklerimizi sizinle paylaşıyoruz.</p><p>Şimdi sistemimize ekstra özellikler getirecek tema ve eklenti geliştirme kılavuzlarını paylaştık. Dökümanlarda bulabilirsiniz</p><p><br></p><p>Kapsamlı rehbere buradan: <strong><em><del><a href=\"https://www.megaforbb.com.tr/documentation/megaforbb-eklenti/megaforbb-eklenti-geli-tirme-k-lavuzu\">Eklenti geliştirme rehberinden</a></del></em></strong> ulaşabilirsiniz.</p>', '<p>Merhabalar, MegaforBB Forum sistemimiz için her geçen gün daha da gggüncelelmee ve gelilştirme yaparak sistemi ilerletiyoruz. bu süreçte de tüm yeniliklerimizi sizinle paylaşıyoruz.</p><p>Şimdi sistemimize ekstra özellikler getirecek tema ve eklenti geliştirme kılavuzlarını paylaştık. Dökümanlarda bulabilirsiniz</p><p><br></p><p>Kapsamlı rehbere buradan: <strong><em><del><a href=\"https://www.megaforbb.com.tr/documentation/megaforbb-eklenti/megaforbb-eklenti-geli-tirme-k-lavuzu\">Eklenti geliştirme rehberinden</a></del></em></strong> ulaşabilirsiniz.</p>', 0, 0, 1, '2026-03-04 21:32:46', '2026-03-04 21:32:46', NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `posts` (`id`, `topic_id`, `reply_to_id`, `user_id`, `body`, `body_html`, `like_count`, `net_votes`, `is_first_post`, `created_at`, `updated_at`, `edited_at`, `edited_by`, `edit_count`, `url_key`, `deleted_at`, `deleted_by`) VALUES
(99, 50, NULL, 1, '<p><strong>Tarih:</strong> 4 Mart 2025</p><p>Bu güncelleme ile forum yazılımımıza yeni özellikler ve iyileştirmeler eklenmiştir. Aşağıda sizin için özetlenmiştir.</p><h2>Yeni Özellikler</h2><h3>Gerçek Zamanlı Bildirimler (SSE)</h3><ul><li><p><strong>Bildirimler artık anında ulaşıyor.</strong> Sayfayı yenilemeden yeni bildirimler (yanıt, etiketleme, beğeni vb.) anında görüntülenir.</p></li><li><p>Sunucu ile sürekli hafif bir bağlantı kurulur; yeni bildirim geldiğinde rozet sayısı ve bildirim listesi otomatik güncellenir.</p></li><li><p>Paylaşımlı hosting ortamlarıyla uyumludur; ek sunucu veya port açmaya gerek yoktur.</p></li></ul><h3>Gelişmiş Görsel Yükleme (WebP &amp; Gizlilik)</h3><ul><li><p><strong>Yüklenen görseller otomatik işlenir:</strong></p><ul><li><p><strong>WebP formatı:</strong> Daha küçük dosya boyutu, daha hızlı sayfa yüklemesi.</p></li><li><p><strong>EXIF temizleme:</strong> Kamera/konum bilgisi gibi kişisel veriler kaldırılır (gizlilik).</p></li><li><p><strong>Boyut sınırı:</strong> Çok büyük görseller otomatik küçültülür; kalite korunur, büyütme yapılmaz.</p></li></ul></li><li><p>Bu işlemler profil fotoğrafı, kapak fotoğrafı, konu ekleri ve editörden yüklenen görseller için geçerlidir.</p></li></ul><h3>Yükleniyor Göstergesi</h3><ul><li><p><strong>Arka planda işlem yapılırken</strong> (form gönderimi, sayfa verisi çekme vb.) sağ üst köşede “Yükleniyor…” göstergesi görünür.</p></li><li><p>Sadece gerçek isteklerde aktif olur; arka plandaki otomatik güncellemeler (bildirim, rozet) bu göstergede yer almaz.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>Yönetim &amp; Teknik İyileştirmeler</h2><p><em>(Bu maddeler daha çok yönetici ve teknik ekip için bilgi amaçlıdır; isteğe bağlı olarak topluluğa kısaca duyurulabilir.)</em></p><h3>API ve Güvenlik</h3><ul><li><p>Tüm API yanıtleri ortak bir JSON formatına kavuşturuldu; dış entegrasyonlar için tutarlı yapı sağlanır.</p></li><li><p><strong>API istek sınırı (rate limiting)</strong> eklendi; bot veya aşırı isteklerle servisin yükünün artması engellenir.</p></li></ul><h3>Webhook &amp; Bildirim Entegrasyonları</h3><ul><li><p><strong>Discord / Telegram</strong> üzerinden otomatik bildirimler (isteğe bağlı, yönetici ayarlarında yapılandırılır):</p><ul><li><p>Yeni konu açıldığında</p></li><li><p>Kullanıcı banlandığında</p></li><li><p>Konu silindiğinde veya taşındığında</p></li></ul></li><li><p><strong>Kritik sistem hataları</strong> (veritabanı vb.) yapılandırılmışsa Telegram’a bildirilebilir; böylece yönetim ekibi hızlı müdahale edebilir.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>Özet</h2><table><thead><tr><th><p>Alan</p></th><th><p>Değişiklik / Fayda</p></th></tr></thead><tbody><tr><td><p>Bildirimler</p></td><td><p>Anında (SSE), sayfa yenilemeden güncelleme</p></td></tr><tr><td><p>Görseller</p></td><td><p>WebP, EXIF temizleme, boyut/kalite optimizasyonu</p></td></tr><tr><td><p>Kullanıcı deneyimi</p></td><td><p>Yükleniyor göstergesi (gerçek isteklere bağlı)</p></td></tr><tr><td><p>API</p></td><td><p>Standart JSON, istek sınırı</p></td></tr><tr><td><p>Yönetim</p></td><td><p>Discord/Telegram webhook’ları, kritik hata bildirimi</p></td></tr></tbody></table><p>Bu güncellemeler mevcut hesaplarınızı ve içeriklerinizi etkilemez; yalnızca daha iyi performans, gizlilik ve yönetim imkânı sunar.</p><p>Sorularınız için yönetim ekibiyle iletişime geçebilirsiniz.</p><p>— <strong>MegaforBB Ekibi</strong></p>', '<p><strong>Tarih:</strong> 4 Mart 2025</p><p>Bu güncelleme ile forum yazılımımıza yeni özellikler ve iyileştirmeler eklenmiştir. Aşağıda sizin için özetlenmiştir.</p><h2>Yeni Özellikler</h2><h3>Gerçek Zamanlı Bildirimler (SSE)</h3><ul><li><p><strong>Bildirimler artık anında ulaşıyor.</strong> Sayfayı yenilemeden yeni bildirimler (yanıt, etiketleme, beğeni vb.) anında görüntülenir.</p></li><li><p>Sunucu ile sürekli hafif bir bağlantı kurulur; yeni bildirim geldiğinde rozet sayısı ve bildirim listesi otomatik güncellenir.</p></li><li><p>Paylaşımlı hosting ortamlarıyla uyumludur; ek sunucu veya port açmaya gerek yoktur.</p></li></ul><h3>Gelişmiş Görsel Yükleme (WebP &amp; Gizlilik)</h3><ul><li><p><strong>Yüklenen görseller otomatik işlenir:</strong></p><ul><li><p><strong>WebP formatı:</strong> Daha küçük dosya boyutu, daha hızlı sayfa yüklemesi.</p></li><li><p><strong>EXIF temizleme:</strong> Kamera/konum bilgisi gibi kişisel veriler kaldırılır (gizlilik).</p></li><li><p><strong>Boyut sınırı:</strong> Çok büyük görseller otomatik küçültülür; kalite korunur, büyütme yapılmaz.</p></li></ul></li><li><p>Bu işlemler profil fotoğrafı, kapak fotoğrafı, konu ekleri ve editörden yüklenen görseller için geçerlidir.</p></li></ul><h3>Yükleniyor Göstergesi</h3><ul><li><p><strong>Arka planda işlem yapılırken</strong> (form gönderimi, sayfa verisi çekme vb.) sağ üst köşede “Yükleniyor…” göstergesi görünür.</p></li><li><p>Sadece gerçek isteklerde aktif olur; arka plandaki otomatik güncellemeler (bildirim, rozet) bu göstergede yer almaz.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>Yönetim &amp; Teknik İyileştirmeler</h2><p><em>(Bu maddeler daha çok yönetici ve teknik ekip için bilgi amaçlıdır; isteğe bağlı olarak topluluğa kısaca duyurulabilir.)</em></p><h3>API ve Güvenlik</h3><ul><li><p>Tüm API yanıtleri ortak bir JSON formatına kavuşturuldu; dış entegrasyonlar için tutarlı yapı sağlanır.</p></li><li><p><strong>API istek sınırı (rate limiting)</strong> eklendi; bot veya aşırı isteklerle servisin yükünün artması engellenir.</p></li></ul><h3>Webhook &amp; Bildirim Entegrasyonları</h3><ul><li><p><strong>Discord / Telegram</strong> üzerinden otomatik bildirimler (isteğe bağlı, yönetici ayarlarında yapılandırılır):</p><ul><li><p>Yeni konu açıldığında</p></li><li><p>Kullanıcı banlandığında</p></li><li><p>Konu silindiğinde veya taşındığında</p></li></ul></li><li><p><strong>Kritik sistem hataları</strong> (veritabanı vb.) yapılandırılmışsa Telegram’a bildirilebilir; böylece yönetim ekibi hızlı müdahale edebilir.</p></li></ul><div contenteditable=\"false\"><hr></div><h2>Özet</h2><table><thead><tr><th><p>Alan</p></th><th><p>Değişiklik / Fayda</p></th></tr></thead><tbody><tr><td><p>Bildirimler</p></td><td><p>Anında (SSE), sayfa yenilemeden güncelleme</p></td></tr><tr><td><p>Görseller</p></td><td><p>WebP, EXIF temizleme, boyut/kalite optimizasyonu</p></td></tr><tr><td><p>Kullanıcı deneyimi</p></td><td><p>Yükleniyor göstergesi (gerçek isteklere bağlı)</p></td></tr><tr><td><p>API</p></td><td><p>Standart JSON, istek sınırı</p></td></tr><tr><td><p>Yönetim</p></td><td><p>Discord/Telegram webhook’ları, kritik hata bildirimi</p></td></tr></tbody></table><p>Bu güncellemeler mevcut hesaplarınızı ve içeriklerinizi etkilemez; yalnızca daha iyi performans, gizlilik ve yönetim imkânı sunar.</p><p>Sorularınız için yönetim ekibiyle iletişime geçebilirsiniz.</p><p>— <strong>MegaforBB Ekibi</strong></p>', 1, 0, 1, '2026-03-04 23:23:20', '2026-03-05 23:44:38', NULL, NULL, 0, NULL, NULL, NULL),
(100, 51, NULL, 132, '<p>Selamlar,</p><p>Mevcutta var olan bir sayfa düzenlenmek istendiği zaman içerik sayfası kaynak kod görünümünde açılıyor. Kaynak kod görüntüle dediğimiz zaman ise HTML ASCII görünümünde görüntüleniyor.</p>', '<p>Selamlar,</p><p>Mevcutta var olan bir sayfa düzenlenmek istendiği zaman içerik sayfası kaynak kod görünümünde açılıyor. Kaynak kod görüntüle dediğimiz zaman ise HTML ASCII görünümünde görüntüleniyor.</p>', 0, 0, 1, '2026-03-05 23:49:41', '2026-03-05 23:49:41', NULL, NULL, 0, NULL, NULL, NULL),
(101, 51, NULL, 1, '<p>Konu hakkında inceleme yapıp güncelleme yayınlarız. Bildirim için teşekkürler.</p><blockquote><p><strong>esw0rmer yazdı:</strong></p><p><br></p><p>Selamlar,Mevcutta var olan bir sayfa düzenlenmek istendiği zaman içerik sayfası kaynak kod görünümünde açılıyor. Kaynak kod görüntüle dediğimiz zaman ise HTML ASCII görünümünde görüntüleniyor.</p></blockquote><p><br></p>', '<p>Konu hakkında inceleme yapıp güncelleme yayınlarız. Bildirim için teşekkürler.</p><blockquote><p><strong>esw0rmer yazdı:</strong></p><p>Selamlar,Mevcutta var olan bir sayfa düzenlenmek istendiği zaman içerik sayfası kaynak kod görünümünde açılıyor. Kaynak kod görüntüle dediğimiz zaman ise HTML ASCII görünümünde görüntüleniyor.</p></blockquote>', 1, 0, 0, '2026-03-06 17:14:27', '2026-03-07 00:09:11', NULL, NULL, 0, NULL, NULL, NULL),
(102, 51, NULL, 1, '<h4>ASCII ve Kaynak Kod çözüldü</h4><p><code>helpers.php</code> içindeki <code>core_sanitize_html</code> fonksiyonu artık içerikte <code>&amp;lt;p</code> veya <code>&amp;lt;span</code> gibi entity tespiti yaptığında otomatik <code>decode</code> işlemi uyguluyor. </p><blockquote><p><strong>esw0rmer yazdı:</strong></p><p>Selamlar,Mevcutta var olan bir sayfa düzenlenmek istendiği zaman içerik sayfası kaynak kod görünümünde açılıyor. Kaynak kod görüntüle dediğimiz zaman ise HTML ASCII görünümünde görüntüleniyor.</p></blockquote>', '<h4>ASCII ve Kaynak Kod çözüldü</h4><p><code>helpers.php</code> içindeki <code>core_sanitize_html</code> fonksiyonu artık içerikte <code>&amp;lt;p</code> veya <code>&amp;lt;span</code> gibi entity tespiti yaptığında otomatik <code>decode</code> işlemi uyguluyor. </p><blockquote><p><strong>esw0rmer yazdı:</strong></p><p>Selamlar,Mevcutta var olan bir sayfa düzenlenmek istendiği zaman içerik sayfası kaynak kod görünümünde açılıyor. Kaynak kod görüntüle dediğimiz zaman ise HTML ASCII görünümünde görüntüleniyor.</p></blockquote>', 0, 0, 0, '2026-03-07 00:42:30', '2026-03-07 01:14:31', '2026-03-07 01:14:31', 1, 1, NULL, NULL, NULL),
(103, 52, NULL, 1, '<p>MegaforBB artık çok daha güvenli. Sistemin kaportasındaki gedikleri kapattık, </p><ul><li><p><strong>Özel Konu Gizliliği (BOLA):</strong> <code>is_private=1</code> olarak işaretlenen konuların sitemap, API veya yan menüler üzerinden yetkisiz kullanıcılara sızması tamamen engellendi.</p></li><li><p><strong>İşlem Güvenliği (CSRF):</strong> Admin panelindeki Import ve Reset gibi veritabanını sıfırlayabilecek kritik işlemlere CSRF koruması eklendi.</p></li><li><p><strong>XSS Savunması:</strong> HTML filtresi, tırnaksız (unquoted) attribute\'lar üzerinden yapılan kod enjeksiyonu denemelerini yakalayacak şekilde güncellendi.</p></li><li><p><strong>API Tahkimatı:</strong> API mutation noktaları ve performans test alanları artık sadece CSRF token ve POST isteği ile çalışıyor.</p></li><li><p><strong>Görsel ve Veri Güvenliği:</strong> Yüklenen resimler otomatik WebP formatına çevriliyor ve içerdikleri gizli meta veriler (EXIF/Konum) sistem tarafından temizleniyor.</p></li><li><p><strong>Oturum Koruması:</strong> HTTPS aktif olan sunucularda çerezlerin (cookie) güvenliğini sağlamak için \'Secure\' flag kullanımı zorunlu hale getirildi.</p></li><li><p><strong>Anlık Takip:</strong> Kritik sistem hataları ve moderasyon hareketleri anlık olarak Telegram üzerinden yönetim ekibine raporlanıyor. (Henüz geliştirme aşamasında ilerde yayınlanacaktır.)</p></li></ul><p><br></p><blockquote><p>Bu güvenlik testlerini Tamamen yapay zeka\'ya sistemi inceletip kapsamlı analizini yaptırıp olası güvenlik açıkalrının tespitinde son derece yakından kullanıyoruz. Bu güvenlik güncellemesinde Dvina.ai Güvenlik tarama ve analiz sisteminden yardım aldık. Bu sayede kullanıcı bazlı belki de yıllarca fark edilmeyecek olan güvenlik açıklarını bizim için aramış ve bulmuş oldu   kendilerine de Teşekkür ediyoruz :)</p></blockquote>', '<p>MegaforBB artık çok daha güvenli. Sistemin kaportasındaki gedikleri kapattık, </p><ul><li><p><strong>Özel Konu Gizliliği (BOLA):</strong> <code>is_private=1</code> olarak işaretlenen konuların sitemap, API veya yan menüler üzerinden yetkisiz kullanıcılara sızması tamamen engellendi.</p></li><li><p><strong>İşlem Güvenliği (CSRF):</strong> Admin panelindeki Import ve Reset gibi veritabanını sıfırlayabilecek kritik işlemlere CSRF koruması eklendi.</p></li><li><p><strong>XSS Savunması:</strong> HTML filtresi, tırnaksız (unquoted) attribute\'lar üzerinden yapılan kod enjeksiyonu denemelerini yakalayacak şekilde güncellendi.</p></li><li><p><strong>API Tahkimatı:</strong> API mutation noktaları ve performans test alanları artık sadece CSRF token ve POST isteği ile çalışıyor.</p></li><li><p><strong>Görsel ve Veri Güvenliği:</strong> Yüklenen resimler otomatik WebP formatına çevriliyor ve içerdikleri gizli meta veriler (EXIF/Konum) sistem tarafından temizleniyor.</p></li><li><p><strong>Oturum Koruması:</strong> HTTPS aktif olan sunucularda çerezlerin (cookie) güvenliğini sağlamak için \'Secure\' flag kullanımı zorunlu hale getirildi.</p></li><li><p><strong>Anlık Takip:</strong> Kritik sistem hataları ve moderasyon hareketleri anlık olarak Telegram üzerinden yönetim ekibine raporlanıyor. (Henüz geliştirme aşamasında ilerde yayınlanacaktır.)</p></li></ul><blockquote><p>Bu güvenlik testlerini Tamamen yapay zeka\'ya sistemi inceletip kapsamlı analizini yaptırıp olası güvenlik açıkalrının tespitinde son derece yakından kullanıyoruz. Bu güvenlik güncellemesinde Dvina.ai Güvenlik tarama ve analiz sisteminden yardım aldık. Bu sayede kullanıcı bazlı belki de yıllarca fark edilmeyecek olan güvenlik açıklarını bizim için aramış ve bulmuş oldu   kendilerine de Teşekkür ediyoruz :)</p></blockquote>', 0, 0, 1, '2026-03-07 01:37:25', '2026-03-07 01:37:25', NULL, NULL, 0, NULL, NULL, NULL);

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
(31, 79, 1, '<ul><li class=\"task-list-item\" data-task=\"true\"><p>Çevrimiçi üyeleri  - Botlar sayfası yapıldı. </p></li><li class=\"task-list-item\" data-task=\"true\"><p>Site haritası sistemi geliştirilmiş halde otomatik günlük yeniliyor.</p></li></ul>', NULL, '2026-03-01 17:31:32'),
(32, 92, 1, '<ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Postbit sadeleştirildi sadece kullanıcı adı, özel başlık, mesaj ve beğeni sayısı görünüyor artık.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Postbit güncellemesi - Postbit modal içinde artık daha detaylı ve kapsamlı şekilde gösterliyor.</p></li><li class=\"task-list-item\" data-task=\"true\"><p><br></p></li></ul>', NULL, '2026-03-04 02:26:56'),
(33, 92, 1, '<ul><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Postbit sadeleştirildi sadece kullanıcı adı, özel başlık, mesaj ve beğeni sayısı görünüyor artık.</p></li><li class=\"task-list-item checked\" data-task=\"true\" data-task-checked=\"true\"><p>Postbit güncellemesi - Postbit modal içinde artık daha detaylı ve kapsamlı şekilde gösterliyor.</p></li></ul>', NULL, '2026-03-04 02:27:29'),
(34, 102, 1, '<h4>ASCII ve Kaynak Kod çözüldü</h4><p><code>helpers.php</code> içindeki <code>core_sanitize_html</code> fonksiyonu artık içerikte <code>&amp;lt;p</code> veya <code>&amp;lt;span</code> gibi entity tespiti yaptığında otomatik <code>decode</code> işlemi uyguluyor. Bu sayede editör içeriği ham kod olarak değil, render edilmiş zengin metin olarak açıyor.</p><blockquote><p><strong>esw0rmer yazdı:</strong></p><p><br></p><p>Selamlar,Mevcutta var olan bir sayfa düzenlenmek istendiği zaman içerik sayfası kaynak kod görünümünde açılıyor. Kaynak kod görüntüle dediğimiz zaman ise HTML ASCII görünümünde görüntüleniyor.</p></blockquote><p><br></p>', NULL, '2026-03-07 01:14:31');

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
(18, 85, 129, '2026-03-02 00:11:33'),
(19, 87, 1, '2026-03-02 01:20:48'),
(20, 99, 132, '2026-03-05 23:44:38'),
(21, 101, 132, '2026-03-07 00:09:11');

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

--
-- Tablo döküm verisi `post_reports`
--

INSERT INTO `post_reports` (`id`, `post_id`, `reporter_user_id`, `reason`, `status`, `created_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 63, 1, 'Rapor test ediyorum', 'reviewed', '2026-03-04 13:50:18', '2026-03-04 15:50:34', 1);

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
(2, 51, 1, 1, '2026-02-23 17:15:03'),
(4, 88, 129, 1, '2026-03-02 01:31:32'),
(17, 87, 1, 1, '2026-03-02 01:39:13'),
(18, 89, 1, 1, '2026-03-02 01:56:51');

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
(7, 16, 1, 'MegaforBB İlk sürümü BETA MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor. Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', 'MegaforBB İlk sürümü BETA MegaforBB ilk beta sürümü 0.1.1 Beta olarak yayına gerçek test ortamına sürüldü. Bu sitede tüm detayları ile birlikte test ediliyor ve gelişimi için notlar alınıyor. Tüm sistemi inceleyip hata - eksik - yapılandırma - geliştirme gibi tüm önerilerinizi bizimle paylaşırsanız çok seviniriz.', '2026-02-27 18:07:33'),
(8, 18, 132, 'Hocam, her sorun için ayrı başlık mı açayım yoksa tek başlıktan mı ilerleyeli?', 'Hocam, her sorun için ayrı başlık mı açayım yoksa tek başlıktan mı ilerleyeli?', '2026-03-07 00:09:58'),
(9, 18, 1, 'Ayrı ayrı olması daha iyi olur kafa karışıklığı olmaz.  kritik güvenlik açığı gibi sorun varsa onları sadece beni etiketleyerek gizli konu tüünde açarsanız iyi olur.', 'Ayrı ayrı olması daha iyi olur kafa karışıklığı olmaz.  kritik güvenlik açığı gibi sorun varsa onları sadece beni etiketleyerek gizli konu tüünde açarsanız iyi olur.', '2026-03-07 00:44:34');

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
(5, 'default_locale', 'tr', 'general'),
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
(92, 'portal_forum_ids', '[1,3,4,6,8,9]', 'portal'),
(93, 'portal_latest_topics_count', '5', 'portal'),
(94, 'portal_latest_articles_count', '4', 'portal'),
(95, 'article_comments_enabled', '1', 'portal'),
(96, 'article_forum_id', '0', 'portal'),
(103, 'home_page_type', 'portal', 'portal'),
(104, 'home_page_custom_url', '', 'portal'),
(105, 'articles_view_mode', 'grid', 'portal'),
(106, 'portal_latest_comments_count', '5', 'portal'),
(107, 'portal_tab_limit', '15', 'portal'),
(108, 'portal_tab_max', '50', 'portal'),
(109, 'portal_card_1', '{\"type\":\"latest\",\"title\":\"Son içerikler\",\"description\":\"Son paylaşılan içeirkelri burada gösteriyoruz Tüm detayları ile birlikte.\",\"layout\":\"grid\",\"per_slide\":4,\"total\":4,\"category_id\":2,\"color\":\"#1c4910\",\"border_color\":\"#aaf4a6\",\"enabled\":true}', 'portal'),
(110, 'portal_card_2', '{\"type\":\"category\",\"title\":\"Kategori\",\"description\":\"Kategori bazlı yapısal içerikleri konuları gösteriyoruz.\",\"layout\":\"grid\",\"per_slide\":4,\"total\":4,\"category_id\":2,\"color\":\"#359315\",\"border_color\":\"#8f8f8f\",\"enabled\":true}', 'portal'),
(111, 'portal_card_3', '{\"type\":\"popular\",\"title\":\"En Popüler\",\"description\":\"Bu kategoride en popüler makaleler forum yazıları grünüyor.\",\"layout\":\"grid\",\"per_slide\":4,\"total\":4,\"category_id\":0,\"color\":\"#821515\",\"border_color\":\"#000000\",\"enabled\":true}', 'portal'),
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
(196, 'documentation_title', 'MegaforBB Docs', 'system'),
(197, 'hero_f1_icon', 'fa-solid fa-gem', 'forum'),
(198, 'hero_f1_title', 'Pırlanta Kalite', 'forum'),
(199, 'hero_f1_desc', 'Modern mimari, güvenli altyapı ve sınırsız özelleştirme ile forum yazılımının zirvesi.', 'forum'),
(200, 'hero_f2_icon', 'fa-solid fa-bolt', 'forum'),
(201, 'hero_f2_title', 'Hızlı ve Akıcı', 'forum'),
(202, 'hero_f2_desc', 'Laravel ve Symfony gücüyle optimize edilmiş, her ölçekte kusursuz performans.', 'forum'),
(203, 'hero_f3_icon', 'fa-solid fa-palette', 'forum'),
(204, 'hero_f3_title', 'Özelleştirilebilir', 'forum'),
(205, 'hero_f3_desc', 'Tema, eklenti ve modül desteği ile hayalinizdeki topluluğu kurun.', 'forum'),
(206, 'hero_f4_icon', 'fa-solid fa-shield-halved', 'forum'),
(207, 'hero_f4_title', 'Güvenli ve Kararlı', 'forum'),
(208, 'hero_f4_desc', 'Güncel güvenlik standartları ve düzenli güncellemelerle güvende kalın.', 'forum'),
(209, 'cache_key_prefix', 'https://www.megaforbb.com.tr', 'performance'),
(210, 'theme_primary_color', '#206bc4', 'forum'),
(211, 'custom_css', '', 'forum'),
(212, 'custom_js', '', 'forum');

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
(7, 'Megaforbb', 'megaforbb', NULL, 7, '2026-02-23 04:10:26', '2026-03-04 21:29:07'),
(8, 'Megaforbb release', 'megaforbb-release', NULL, 1, '2026-02-23 04:10:37', '2026-02-23 04:11:06'),
(11, 'Sef Url', 'sef-url', NULL, 1, '2026-02-28 01:58:18', '2026-02-28 01:58:35'),
(12, 'Megaforbb Sef Url', 'megaforbb-sef-url', NULL, 1, '2026-02-28 01:58:31', '2026-02-28 01:58:35'),
(13, 'Profil', 'profil', NULL, 0, '2026-02-28 03:24:00', '2026-02-28 03:25:43'),
(14, 'Feature', 'feature', NULL, 0, '2026-02-28 03:24:11', '2026-02-28 03:25:43'),
(15, 'Sistem izleyici', 'sistem-izleyici', NULL, 0, '2026-02-28 03:34:10', '2026-02-28 03:34:10'),
(16, 'Log takibi', 'log-takibi', NULL, 0, '2026-02-28 03:34:16', '2026-02-28 03:34:16'),
(17, 'Döküman', 'dokuman', NULL, 1, '2026-03-01 16:46:24', '2026-03-01 16:46:33'),
(18, 'free', 'free', NULL, 1, '2026-03-02 22:24:25', '2026-03-02 22:24:30'),
(19, 'uptime', 'uptime', NULL, 1, '2026-03-02 22:24:25', '2026-03-02 22:24:30'),
(20, 'contact', 'contact', NULL, 1, '2026-03-04 01:45:25', '2026-03-04 01:45:37'),
(21, 'iletisim', 'iletisim', NULL, 1, '2026-03-04 01:45:26', '2026-03-04 01:45:37'),
(22, 'tema', 'tema', NULL, 1, '2026-03-04 21:18:43', '2026-03-04 21:29:07');

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
(1, NULL, 1, 1, 3, 'MegaforBB v0.1.1 Yayınlandı', 'megaforbb-v011-yayinlandi-1', NULL, 'topic', 1, 0, 0, 0, NULL, 1, 102, 1, 3, '2026-02-23 06:31:13', 129, NULL, 'published', '2026-02-23 04:11:06', '2026-03-06 21:20:59', NULL, NULL),
(2, NULL, 4, 1, NULL, 'Megaforbb Kurulum', 'megaforbb-kurulum-2', NULL, 'topic', 0, 1, 0, 0, NULL, 0, 48, 2, 2, '2026-02-23 04:15:08', 1, NULL, 'published', '2026-02-23 04:15:08', '2026-03-06 21:19:23', NULL, NULL),
(5, NULL, 1, 1, NULL, 'Haftalık Güncelleme  - İlerleme konusu', 'haftalik-guncelleme-ilerleme-konusu-5', NULL, 'topic', 0, 0, 0, 0, NULL, 4, 110, 10, 92, '2026-03-04 02:26:46', 1, NULL, 'published', '2026-02-21 07:04:46', '2026-03-06 06:27:15', NULL, NULL),
(14, NULL, 4, 1, NULL, 'SEO Uyumlu Forum Altyapısı', 'seo-uyumlu-forum-altyapisi-14', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 56, 35, 35, '2026-02-09 07:04:46', 1, NULL, 'published', '2026-02-09 07:04:46', '2026-03-06 21:19:15', NULL, NULL),
(15, NULL, 4, 1, NULL, 'Arayüz Tasarımı Geri Bildirimleri', 'arayuz-tasarimi-geri-bildirimleri-15', NULL, 'topic', 0, 0, 0, 0, NULL, 1, 122, 36, 58, '2026-02-26 20:45:19', 1, NULL, 'published', '2026-02-10 07:04:46', '2026-03-07 02:05:39', NULL, NULL),
(19, NULL, 6, 1, NULL, 'Kullanıcı ve Post etiket test', 'kullanici-ve-post-etiket-test-19', NULL, 'topic', 0, 1, 0, 0, NULL, 2, 53, 45, 48, '2026-02-24 04:31:31', 1, NULL, 'published', '2026-02-24 00:54:25', '2026-03-06 21:23:42', NULL, NULL),
(20, NULL, 3, 129, NULL, 'Kullanıcı kayıt - Captcha sorunu', 'kullanici-kayit-captcha-sorunu-20', NULL, 'topic', 0, 1, 0, 0, NULL, 1, 29, 47, 62, '2026-02-26 21:20:12', 1, NULL, 'published', '2026-02-24 03:54:41', '2026-03-06 15:27:36', NULL, NULL),
(21, NULL, 3, 129, NULL, 'Konu başlığı Ön ek çalışmıyor - Görünmüyor', 'konu-basligi-on-ek-calismiyor-gorunmuyor-21', NULL, 'question', 0, 1, 0, 1, 50, 1, 37, 49, 50, '2026-02-24 04:44:44', 1, NULL, 'published', '2026-02-24 04:41:16', '2026-03-06 21:33:29', NULL, NULL),
(22, NULL, 4, 1, NULL, 'Kurulum ve güncelleme sistemi', 'kurulum-ve-guncelleme-sistemi-22', NULL, 'topic', 0, 0, 0, 0, NULL, 2, 29, 51, 71, '2026-02-28 02:37:48', 1, NULL, 'published', '2026-02-24 05:11:28', '2026-03-06 15:27:26', NULL, NULL),
(23, NULL, 4, 1, NULL, 'Konu düzenleme geçmişi ?', 'konu-duzenleme-gecmisi-23', NULL, 'question', 0, 1, 1, 0, NULL, 1, 19, 52, 53, '2026-02-24 11:03:52', 1, NULL, 'published', '2026-02-24 10:50:52', '2026-03-01 22:44:41', NULL, NULL),
(24, NULL, 4, 1, NULL, 'Tema motoru için Blade vs Twig', 'tema-motoru-icin-blade-vs-twig-24', NULL, 'topic', 0, 1, 0, 0, NULL, 1, 34, 54, 61, '2026-02-26 21:06:49', 1, NULL, 'published', '2026-02-24 15:08:04', '2026-03-06 07:31:56', NULL, NULL),
(25, NULL, 3, 129, NULL, 'Bildirim sisteminde hata', 'bildirim-sisteminde-hata-25', NULL, 'topic', 0, 0, 0, 0, NULL, 2, 59, 55, 64, '2026-02-27 15:55:56', 1, NULL, 'published', '2026-02-24 21:05:34', '2026-03-07 01:00:10', NULL, NULL),
(26, NULL, 1, 1, NULL, 'Sansür Koruma Sistemi', 'sansur-koruma-sistemi-26', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 15, 56, 56, '2026-02-26 20:39:11', 1, NULL, 'published', '2026-02-26 20:39:11', '2026-03-05 05:33:34', NULL, NULL),
(27, NULL, 1, 1, NULL, 'Dil Sistemi ve Twig Entegrasyonu', 'dil-sistemi-ve-twig-entegrasyonu-27', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 22, 59, 59, '2026-02-26 20:48:33', 1, NULL, 'published', '2026-02-26 20:48:33', '2026-03-05 06:11:22', NULL, NULL),
(28, NULL, 1, 1, NULL, 'Kullanıcı Hesabı Askıya Alma ve Kalıcı Kapatma', 'kullanici-hesabi-askiya-alma-ve-kalici-kapatma-28', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 26, 60, 60, '2026-02-26 21:03:33', 1, NULL, 'published', '2026-02-26 21:03:33', '2026-03-05 05:38:28', NULL, NULL),
(29, NULL, 6, 1, NULL, 'Konu dosya test ve Etiket Test', 'konu-dosya-test-ve-etiket-test-29', NULL, 'topic', 0, 0, 0, 0, NULL, 1, 41, 66, 81, '2026-03-01 23:25:10', 129, NULL, 'published', '2026-02-27 16:14:53', '2026-03-06 20:43:57', NULL, NULL),
(30, NULL, 3, 1, NULL, 'Mesaj gönderim hatası', 'mesaj-gonderim-hatasi-30', NULL, 'topic', 0, 0, 0, 0, NULL, 1, 29, 67, 68, '2026-02-27 18:08:37', 1, NULL, 'published', '2026-02-27 16:31:36', '2026-03-06 15:59:46', NULL, NULL),
(31, NULL, 3, 129, NULL, 'SEF Url desteği eklenmeli', 'sef-url-destegi-eklenmeli-31', NULL, 'topic', 0, 0, 0, 0, NULL, 1, 32, 69, 70, '2026-02-28 02:07:24', 1, NULL, 'published', '2026-02-28 01:58:35', '2026-03-06 22:52:20', NULL, NULL),
(32, NULL, 1, 1, 5, 'Kullanıcı Profil Yorumları', 'profil-yorumlari-32', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 37, 72, 72, '2026-02-28 03:24:13', 1, NULL, 'published', '2026-02-28 03:24:13', '2026-03-06 20:24:53', NULL, NULL),
(34, NULL, 6, 1, NULL, 'Planlanmış konu TEST', 'planlanmis-konu-test-34', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 23, 74, 74, '2026-02-28 23:09:00', 1, NULL, 'published', '2026-02-28 23:09:00', '2026-03-04 23:05:16', NULL, NULL),
(36, NULL, 8, 1, NULL, 'cPanel mi Plesk mi Tercih Edilmeli?', 'test-icin-kisa-makale-ornekleri-36', NULL, 'article', 0, 0, 0, 0, NULL, 0, 15, 76, 76, '2026-03-01 04:09:11', 1, NULL, 'published', '2026-03-01 04:09:11', '2026-03-04 23:15:11', NULL, NULL),
(37, NULL, 8, 1, NULL, 'Sunucu durumu (server status) Nedir ?', 'mevlanaya-gore-insanin-mahiyeti-ve-kamil-insan-olma-37', NULL, 'article', 0, 0, 0, 0, NULL, 0, 22, 77, 77, '2026-03-01 04:09:55', 1, NULL, 'published', '2026-03-01 04:09:55', '2026-03-04 23:02:37', NULL, NULL),
(38, NULL, 1, 1, 5, 'Döküman Sistemi geliştirildi.', 'dokuman-sistemi-gelistirildi-38', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 20, 78, 78, '2026-03-01 16:46:33', 1, NULL, 'published', '2026-03-01 16:46:33', '2026-03-04 23:46:07', NULL, NULL),
(39, NULL, 6, 1, NULL, 'Özel -private konu test', 'ozel-private-konu-test-39', NULL, 'topic', 0, 0, 1, 0, NULL, 0, 12, 80, 80, '2026-03-01 22:56:42', 1, NULL, 'published', '2026-03-01 22:56:42', '2026-03-04 15:51:29', NULL, NULL),
(40, NULL, 6, 129, NULL, 'Anti Bump Mesaj - yorum artırma sistemi', 'anti-bump-mesaj-yorum-artirma-sistemi-40', NULL, 'topic', 0, 0, 0, 0, NULL, 2, 50, 83, 85, '2026-03-02 00:00:05', 1, NULL, 'published', '2026-03-01 23:43:52', '2026-03-07 01:40:09', NULL, NULL),
(41, NULL, 6, 1, NULL, 'Soru - Cevap - Çözüm -Test konusu', 'soru-cevap-test-konusu-41', NULL, 'question', 0, 0, 0, 1, 89, 3, 125, 86, 89, '2026-03-02 01:40:15', 129, NULL, 'published', '2026-03-02 00:14:11', '2026-03-07 01:47:22', NULL, NULL),
(42, NULL, 8, 1, NULL, 'Sucuri vs Cloudflare hangisini tercih etmeliyim ?', 'sucuri-vs-cloudflare-hangisini-tercih-etmeliyim-42', NULL, 'article', 0, 0, 0, 0, NULL, 0, 13, 90, 90, '2026-03-02 22:26:45', 1, NULL, 'published', '2026-03-02 22:26:45', '2026-03-04 22:39:05', NULL, NULL),
(43, NULL, 1, 1, 5, 'İletişim mesajları - Yönetim ve yanıtlama', 'iletisim-mesajlari-yonetim-ve-yanitlama-43', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 13, 91, 91, '2026-03-04 01:45:37', 1, NULL, 'published', '2026-03-04 01:45:37', '2026-03-04 22:34:38', NULL, NULL),
(44, NULL, 1, 1, 3, 'Güvenlik Güncellemesi – Altyapı ve Panel Sertleştirmeleri', 'guvenlik-guncellemesi-altyapi-ve-panel-sertlestirmeleri-44', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 20, 93, 93, '2026-03-04 02:42:21', 1, NULL, 'published', '2026-03-04 02:42:21', '2026-03-04 23:54:22', NULL, NULL),
(45, NULL, 6, 1, NULL, 'MegaforBB – Büyük Veri (4.3M Konu / 29M Mesaj', 'megaforbb-buyuk-veri-43m-konu-29m-mesaj-45', NULL, 'topic', 0, 0, 1, 0, NULL, 0, 1, 94, 94, '2026-03-04 17:47:18', 1, NULL, 'published', '2026-03-04 17:47:18', '2026-03-04 17:47:19', NULL, NULL),
(46, NULL, 6, 1, NULL, 'DataLife Engine (DLE) vs MegaforBB Karşılaştırması', 'datalife-engine-dle-vs-megaforbb-karsilastirmasi-46', NULL, 'topic', 0, 0, 1, 0, NULL, 0, 5, 95, 95, '2026-03-04 18:04:43', 1, NULL, 'published', '2026-03-04 18:04:43', '2026-03-07 01:15:06', NULL, NULL),
(47, NULL, 1, 1, 3, 'MegaforBB Eklenti ve Kanca Sistemi Güncellemesi', 'megaforbb-eklenti-ve-kanca-sistemi-guncellemesi-47', NULL, 'topic', 0, 0, 1, 0, NULL, 0, 6, 96, 96, '2026-03-04 20:53:18', 1, NULL, 'published', '2026-03-04 20:53:18', '2026-03-05 00:26:05', NULL, NULL),
(48, NULL, 1, 1, 5, 'MegaforBB Tema Geliştirici Kılavuzu', 'megaforbb-tema-gelistirici-kilavuzu-48', NULL, 'topic', 1, 0, 0, 0, NULL, 0, 18, 97, 97, '2026-03-04 21:18:54', 1, NULL, 'published', '2026-03-04 21:18:54', '2026-03-07 01:39:59', NULL, NULL),
(49, NULL, 1, 1, 5, 'MegaforBB Eklenti geliştirme Klavuzu', 'megaforbb-eklenti-gelistirme-klavuzu-49', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 10, 98, 98, '2026-03-04 21:32:46', 1, NULL, 'published', '2026-03-04 21:32:46', '2026-03-06 16:20:15', NULL, NULL),
(50, NULL, 1, 1, 5, 'MegaforBB Güncelleme Notları -Geliştirmeler', 'megaforbb-guncelleme-notlari-gelistirmeler-50', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 19, 99, 99, '2026-03-04 23:23:20', 1, NULL, 'published', '2026-03-04 23:23:20', '2026-03-07 03:13:16', NULL, NULL),
(51, NULL, 3, 132, NULL, 'Sayfa İçeriği Güncellenirken WYSIWYG Çalışmıyor', 'sayfa-icerigi-guncellenirken-wysiwyg-calismiyor-51', NULL, 'topic', 0, 0, 0, 0, NULL, 2, 28, 100, 102, '2026-03-07 00:42:30', 1, NULL, 'published', '2026-03-05 23:49:41', '2026-03-07 01:48:31', NULL, NULL),
(52, NULL, 1, 1, 3, 'MegaforBB Güvenlik Güncellemesi v1.1.1', 'megaforbb-guvenlik-guncellemesi-v111-52', NULL, 'topic', 0, 0, 0, 0, NULL, 0, 2, 103, 103, '2026-03-07 01:37:25', 1, NULL, 'published', '2026-03-07 01:37:25', '2026-03-07 02:28:41', NULL, NULL);

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
(39, 129, '2026-03-01 23:13:46'),
(41, 129, '2026-03-02 01:56:47');

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
(1, 1, '2026-03-04 23:49:10'),
(1, 2, '2026-02-27 16:11:05'),
(1, 3, '2026-02-23 18:10:34'),
(1, 4, '2026-02-23 18:12:17'),
(1, 5, '2026-03-04 23:48:44'),
(1, 6, '2026-02-24 00:32:45'),
(1, 7, '2026-02-23 18:14:23'),
(1, 8, '2026-02-24 00:32:53'),
(1, 9, '2026-02-24 00:33:21'),
(1, 10, '2026-02-24 00:33:05'),
(1, 11, '2026-02-24 00:33:15'),
(1, 12, '2026-02-23 18:17:01'),
(1, 13, '2026-02-23 18:16:50'),
(1, 14, '2026-03-01 17:32:45'),
(1, 15, '2026-03-04 02:32:54'),
(1, 16, '2026-02-23 18:08:02'),
(1, 17, '2026-02-23 18:04:42'),
(1, 18, '2026-02-23 18:10:46'),
(1, 19, '2026-03-01 00:38:23'),
(1, 20, '2026-02-26 21:20:16'),
(1, 21, '2026-03-04 22:31:14'),
(1, 22, '2026-02-28 20:19:59'),
(1, 23, '2026-03-01 22:44:41'),
(1, 24, '2026-02-26 21:13:00'),
(1, 25, '2026-03-07 01:00:10'),
(1, 26, '2026-03-04 15:53:27'),
(1, 27, '2026-03-04 15:58:20'),
(1, 28, '2026-03-02 23:12:03'),
(1, 29, '2026-03-04 02:24:25'),
(1, 30, '2026-03-04 15:13:02'),
(1, 31, '2026-03-05 17:51:51'),
(1, 32, '2026-02-28 20:26:10'),
(1, 33, '2026-02-28 23:08:05'),
(1, 34, '2026-03-04 15:52:24'),
(1, 36, '2026-03-02 22:25:31'),
(1, 37, '2026-03-02 22:24:30'),
(1, 38, '2026-03-04 23:46:07'),
(1, 39, '2026-03-04 15:51:29'),
(1, 40, '2026-03-04 21:40:42'),
(1, 41, '2026-03-07 01:47:22'),
(1, 43, '2026-03-04 16:22:40'),
(1, 44, '2026-03-04 23:54:22'),
(1, 45, '2026-03-04 17:47:19'),
(1, 46, '2026-03-07 01:15:06'),
(1, 47, '2026-03-05 00:26:05'),
(1, 48, '2026-03-04 23:41:44'),
(1, 49, '2026-03-04 23:20:38'),
(1, 50, '2026-03-07 00:50:17'),
(1, 51, '2026-03-07 01:30:53'),
(1, 52, '2026-03-07 02:28:41'),
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
(129, 38, '2026-03-02 01:11:04'),
(129, 39, '2026-03-01 23:16:18'),
(129, 40, '2026-03-02 00:11:28'),
(129, 41, '2026-03-02 22:39:52'),
(129, 43, '2026-03-04 02:04:30'),
(131, 20, '2026-02-26 21:41:55'),
(131, 22, '2026-02-27 16:09:32'),
(131, 25, '2026-02-27 16:29:33'),
(131, 27, '2026-02-27 15:32:37'),
(131, 28, '2026-02-27 15:35:55'),
(132, 22, '2026-03-03 02:29:22'),
(132, 40, '2026-03-03 02:21:37'),
(132, 41, '2026-03-03 02:07:30'),
(132, 48, '2026-03-05 23:52:00'),
(132, 50, '2026-03-05 23:44:21'),
(132, 51, '2026-03-07 00:10:53');

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
(13, 38, 17, '2026-03-01 14:46:33'),
(14, 37, 18, '2026-03-02 20:24:30'),
(15, 37, 19, '2026-03-02 20:24:30'),
(16, 43, 7, '2026-03-03 23:45:37'),
(17, 43, 20, '2026-03-03 23:45:37'),
(18, 43, 21, '2026-03-03 23:45:37'),
(23, 48, 7, '2026-03-04 19:29:07'),
(24, 48, 22, '2026-03-04 19:29:07');

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
  `last_ip` varchar(45) DEFAULT NULL,
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

INSERT INTO `users` (`id`, `username`, `custom_title`, `email`, `password_hash`, `role_id`, `approved_at`, `locale`, `avatar_path`, `cover_photo_path`, `reputation_positive`, `reputation_negative`, `location`, `website`, `bio`, `signature`, `first_name`, `last_name`, `show_name`, `birthday`, `is_verified`, `is_banned`, `warning_points`, `reward_points`, `remember_token`, `last_activity_at`, `last_ip`, `created_at`, `updated_at`, `email_verified_at`, `email_verification_token`, `admin_twofa_question`, `admin_twofa_answer_hash`, `available_invites`, `trust_score`, `message_count`, `is_suspended`, `suspended_at`, `closed_at`, `url_key`) VALUES
(1, 'Sinek10', 'Forum Yöneticisi', 'sys@rootali.net', '$2y$12$LbjGJsrgj/mLYaIEerLzhuA9W2BpVegGHOU2u9pgeakf2X0/T7J0S', 1, NULL, 'tr', 'uploads/avatars/2026/02/u1_9f8a4d1c.png', 'uploads/covers/2026/02/u1_138eabc1.jpg', 3, 0, 'Trabzon', 'https://www.megaforbb.com.tr', 'Hakkımda bilinen şeyler çok az', 'Yazdığımız şeyler bizi temsil eder, Efendilik iyidir.', 'Sinek', 'Onlu', 1, '1993-07-24', 1, 0, 0, 150, NULL, '2026-03-07 02:36:22', NULL, '2026-02-20 08:15:02', '2026-03-07 02:36:22', '2026-02-21 15:39:55', NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL),
(129, 'kaan', 'Test Kullanıcı', 'kaan@kaan.com', '$2y$10$nCdypWVFYxK.C.eGAKtedOAjBRBUNBxTVgeHv0ifR0HBMkW/YnO0S', 3, '2026-02-23 05:11:01', 'tr', 'uploads/avatars/2026/02/u121_b830268c.png', NULL, 0, 0, 'İsveç', 'https://www.megaforbb.com.tr', 'Hakkımda fazla şey bilinmez', 'Burada benim imzam olması gerekiyormuş öyle söylüyorlar.', 'Kaan', 'Demo', 0, '2026-02-23', 0, 0, 15, 15, NULL, '2026-03-04 03:29:08', NULL, '2026-02-26 18:45:11', '2026-03-04 03:29:08', NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL),
(130, 'softwarencoder', '', 'softwarencoder@yavuz-selim.com', '$2y$12$02lsCT1glzprK02qr/MWNuoVoI3aEdRGlzXHQlYO7476hAFILi22a', 2, '2026-02-23 20:50:11', 'en', 'https://www.gravatar.com/avatar/c4aa5045243955ac2ef60112e7e427f6?d=mp&s=200', NULL, 0, 0, '', '', '', NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, NULL, '2026-02-24 11:11:49', NULL, '2026-02-26 18:45:11', '2026-03-04 01:36:44', '2026-02-23 18:53:24', NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL),
(131, 'slaweally', '', 'slaweally@hotmail.com', '$2y$12$R2lZIKEP2Upi/MdJQvNBt.26Z1VEvNPZ7OWArZS/.yEG6kUw8nGtm', 3, '2026-02-26 21:38:25', 'tr', 'https://www.gravatar.com/avatar/2f282f3bcba16bede1e498e35ced9908?d=mp&s=200', NULL, 0, 0, '', '', '', NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, NULL, '2026-02-27 17:04:34', NULL, '2026-02-26 21:38:25', '2026-03-02 22:43:51', '2026-02-26 21:39:14', NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL),
(132, 'esw0rmer', NULL, 'yunusemregeldegul@gmail.com', '$2y$12$a/qQM4H3LyIsg5OzV1UuBe4p201AY.0eK5AZorh5n4/0FsIYphvCe', 3, '2026-03-03 02:02:15', 'tr', 'uploads/avatars/2026/03/u132_86a4feb3.webp', NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, NULL, '2026-03-07 00:10:50', NULL, '2026-03-03 02:02:15', '2026-03-07 00:10:50', '2026-03-03 02:02:36', NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL);

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
(74, 1, 'post_created', 88, '{\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\",\"body_snippet\":\"kaan yazdı:Şu anda mantık olarak sistemde çalışıyor ancak ne derece iy…\"}', '2026-03-02 00:17:59'),
(75, 1, 'like_given', 87, '{\"topic_id\":41,\"owner_id\":129,\"topic_title\":\"Soru - Cevap -Test konusu\"}', '2026-03-02 01:20:48'),
(76, 129, 'post_created', 89, '{\"topic_id\":41,\"topic_title\":\"Soru - Cevap -Test konusu\",\"body_snippet\":\"Sinek10 yazdı:kaan yazdı:Şu anda mantık olarak sistemde çalışıyor anca…\"}', '2026-03-02 01:40:15'),
(77, 1, 'topic_created', 43, '{\"title\":\"İletişim mesajları - Yönetim ve yanıtlama\",\"slug\":\"iletisim-mesajlari-yonetim-ve-yanitlama-43\",\"forum_id\":1,\"forum_name\":\"Duyuru ve Güncelleme\"}', '2026-03-04 01:45:37'),
(78, 1, 'post_created', 92, '{\"topic_id\":5,\"topic_title\":\"Haftalık Güncelleme  - İlerleme konusu\",\"body_snippet\":\"Postbit sadeleştirildi sadece kullanıcı adı, özel başlık, mesaj ve beğ…\"}', '2026-03-04 02:26:46'),
(79, 1, 'topic_created', 44, '{\"title\":\"Güvenlik Güncellemesi – Altyapı ve Panel Sertleştirmeleri\",\"slug\":\"guvenlik-guncellemesi-altyapi-ve-panel-sertlestirmeleri-44\",\"forum_id\":1,\"forum_name\":\"Duyuru ve Güncelleme\"}', '2026-03-04 02:42:21'),
(80, 1, 'topic_created', 45, '{\"title\":\"MegaforBB – Büyük Veri (4.3M Konu \\/ 29M Mesaj\",\"slug\":\"megaforbb-buyuk-veri-43m-konu-29m-mesaj-45\",\"forum_id\":6,\"forum_name\":\"Test ve Demo\"}', '2026-03-04 17:47:18'),
(81, 1, 'topic_created', 46, '{\"title\":\"DataLife Engine (DLE) vs MegaforBB Karşılaştırması\",\"slug\":\"datalife-engine-dle-vs-megaforbb-karsilastirmasi-46\",\"forum_id\":6,\"forum_name\":\"Test ve Demo\"}', '2026-03-04 18:04:43'),
(82, 1, 'topic_created', 47, '{\"title\":\"MegaforBB Eklenti ve Kanca Sistemi Güncellemesi\",\"slug\":\"megaforbb-eklenti-ve-kanca-sistemi-guncellemesi-47\",\"forum_id\":1,\"forum_name\":\"Duyuru ve Güncelleme\"}', '2026-03-04 20:53:18'),
(83, 1, 'topic_created', 48, '{\"title\":\"MegaforBB Tema Geliştirici Kılavuzu\",\"slug\":\"megaforbb-tema-gelistirici-kilavuzu-48\",\"forum_id\":1,\"forum_name\":\"Duyuru ve Güncelleme\"}', '2026-03-04 21:18:54'),
(84, 1, 'topic_created', 49, '{\"title\":\"MegaforBB Eklenti geliştirme Klavuzu\",\"slug\":\"megaforbb-eklenti-gelistirme-klavuzu-49\",\"forum_id\":1,\"forum_name\":\"Duyuru ve Güncelleme\"}', '2026-03-04 21:32:46'),
(85, 1, 'topic_created', 50, '{\"title\":\"MegaforBB Güncelleme Notları -Geliştirmeler\",\"slug\":\"megaforbb-guncelleme-notlari-gelistirmeler-50\",\"forum_id\":1,\"forum_name\":\"Duyuru ve Güncelleme\"}', '2026-03-04 23:23:20'),
(86, 132, 'like_given', 99, '{\"topic_id\":50,\"owner_id\":1,\"topic_title\":\"MegaforBB Güncelleme Notl…\"}', '2026-03-05 23:44:38'),
(87, 132, 'rep_given', 1, '{\"value\":1,\"target_username\":\"Sinek10\",\"post_id\":99,\"topic_id\":50,\"topic_title\":\"MegaforBB Güncelleme Notl…\"}', '2026-03-05 23:44:44'),
(88, 132, 'rep_given', 1, '{\"value\":1,\"target_username\":\"Sinek10\"}', '2026-03-05 23:44:57'),
(89, 132, 'topic_created', 51, '{\"title\":\"Sayfa İçeriği Güncellenirken WYSIWYG Çalışmıyor\",\"slug\":\"sayfa-icerigi-guncellenirken-wysiwyg-calismiyor-51\",\"forum_id\":3,\"forum_name\":\"Bug Reports\"}', '2026-03-05 23:49:41'),
(90, 1, 'post_created', 101, '{\"topic_id\":51,\"topic_title\":\"Sayfa İçeriği Güncellenirken WYSIWYG Çalışmıyor\",\"body_snippet\":\"Konu hakkında inceleme yapıp güncelleme yayınlarız. Bildirim için teşe…\"}', '2026-03-06 17:14:27'),
(91, 132, 'like_given', 101, '{\"topic_id\":51,\"owner_id\":1,\"topic_title\":\"Sayfa İçeriği Güncellenir…\"}', '2026-03-07 00:09:11'),
(92, 1, 'post_created', 102, '{\"topic_id\":51,\"topic_title\":\"Sayfa İçeriği Güncellenirken WYSIWYG Çalışmıyor\",\"body_snippet\":\"ASCII ve Kaynak Kod çözüldühelpers.php içindeki core_sanitize_html fon…\"}', '2026-03-07 00:42:30'),
(93, 1, 'topic_created', 52, '{\"title\":\"MegaforBB Güvenlik Güncellemesi v1.1.1\",\"slug\":\"megaforbb-guvenlik-guncellemesi-v111-52\",\"forum_id\":1,\"forum_name\":\"Duyuru ve Güncelleme\"}', '2026-03-07 01:37:25');

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
(1, 131, 1, 60, 1, 'Bravo', '2026-02-27 15:36:02'),
(2, 132, 1, 99, 1, 'Harika', '2026-03-05 23:44:44'),
(3, 132, 1, NULL, 1, 'Harika', '2026-03-05 23:44:57');

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `content_permissions`
--
ALTER TABLE `content_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `conversation_user`
--
ALTER TABLE `conversation_user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `doc_pages`
--
ALTER TABLE `doc_pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `doc_sections`
--
ALTER TABLE `doc_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- Tablo için AUTO_INCREMENT değeri `post_edits`
--
ALTER TABLE `post_edits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Tablo için AUTO_INCREMENT değeri `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Tablo için AUTO_INCREMENT değeri `post_reports`
--
ALTER TABLE `post_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `post_votes`
--
ALTER TABLE `post_votes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `prefixes`
--
ALTER TABLE `prefixes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=213;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Tablo için AUTO_INCREMENT değeri `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- Tablo için AUTO_INCREMENT değeri `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- Tablo için AUTO_INCREMENT değeri `user_bans`
--
ALTER TABLE `user_bans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `user_blocks`
--
ALTER TABLE `user_blocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
