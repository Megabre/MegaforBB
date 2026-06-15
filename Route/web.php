<?php

declare(strict_types=1);

/**
 * Web routes. Loaded by public/index.php and passed to Application router.
 */

return function (\Forecor\Core\Router $router) {
    $router->get('/uploads/{*path}', 'UploadsServeController@serve');
    $router->get('/theme-assets/{*path}', 'ThemeAssetController@serve');
    $router->get('/ui-vendor/ckeditor5/{*path}', 'CkeditorAssetController@serve');
    $router->get('/ui-vendor/toastui-editor/{*path}', 'ToastUIAssetController@serve');
    $router->get('/ui-vendor/tinymce/{*path}', 'TinyMCEAssetController@serve');
    $router->get('/ckeditor5/{*path}', 'CkeditorAssetController@serve');
    $router->get('/toastui-editor/{*path}', 'ToastUIAssetController@serve');
    $router->get('/', 'HomeController@index');
    $router->get('/sitemap.xml', 'SitemapController@sitemap');
    $router->get('/robots.txt', 'SitemapController@robots');
    $router->get('/manifest.json', 'PwaController@manifest');
    $router->get('/forum', 'HomeController@forumIndex');
    $router->get('/security-check', 'SecurityCheckController@show');
    $router->post('/security-check', 'SecurityCheckController@verify');
    $router->get('/login', 'AuthController@loginForm');
    $router->post('/login', 'AuthController@login');
    $router->get('/login/verify', 'AuthController@loginVerifyForm');
    $router->post('/login/verify', 'AuthController@loginVerify');
    $router->get('/reactivate-account', 'AuthController@reactivateForm');
    $router->post('/reactivate-account', 'AuthController@reactivate');
    $router->get('/register', 'AuthController@registerForm');
    $router->post('/register', 'AuthController@register');
    $router->get('/register/pending', 'AuthController@registerPending');
    $router->get('/verify-email', 'AuthController@verifyEmail');
    $router->get('/logout', 'AuthController@logout');
    $router->post('/logout', 'AuthController@logout');
    $router->get('/forgot-password', 'PasswordResetController@forgotForm');
    $router->post('/forgot-password', 'PasswordResetController@forgot');
    $router->get('/reset-password', 'PasswordResetController@resetForm');
    $router->post('/reset-password', 'PasswordResetController@reset');
    $router->post('/mark-all-read', 'ForumController@markAllRead');
    // new-topic route'ları forum gösterim route'larından ÖNCE olmalı; yoksa /forum/ana-forum-slug/new-topic
    // 3 segment olunca {parentSlug}/{childSlug} ile eşleşip childSlug=new-topic sanılıyor ve 404 veriyor
    $router->get('/forum/{slug}/new-topic', 'ForumController@createTopic');
    $router->post('/forum/{slug}/new-topic', 'ForumController@storeTopic');
    $router->get('/forum/{parentSlug}/{childSlug}/new-topic', 'ForumController@createTopicSubforum');
    $router->post('/forum/{parentSlug}/{childSlug}/new-topic', 'ForumController@storeTopicSubforum');
    $router->get('/forum/{parentSlug}/{childSlug}', 'ForumController@showSubforum');
    $router->get('/forum/{slug}', 'ForumController@show');
    $router->get('/topic/{id}', 'TopicController@show');
    $router->get('/articles', 'ArticleController@index');
    $router->get('/articles/new', 'ArticleController@create');
    $router->post('/articles/store', 'ArticleController@store');
    $router->get('/articles/{categorySlug}/{articleSlug}', 'ArticleController@showByCategoryAndSlug');
    $router->get('/article/{id}', 'ArticleController@show');
    $router->get('/search', 'SearchController@index');
    $router->get('/members', 'MemberController@index');
    $router->get('/online', 'OnlineController@index');
    $router->get('/member/{username}', 'MemberController@profile');
    $router->get('/member/{username}/about', 'MemberController@about');
    $router->get('/member/{username}/stats', 'MemberController@stats');
    $router->get('/member/{username}/activity', 'MemberController@activityAjax');
    $router->get('/member/{username}/viewers', 'MemberController@viewersAjax');
    $router->get('/member/{username}/forum', 'MemberController@forum');
    $router->get('/member/{username}/topics', 'MemberController@topics');
    $router->get('/member/{username}/posts', 'MemberController@posts');
    $router->get('/member/{username}/articles', 'MemberController@articles');
    $router->get('/member/{username}/ideas', 'MemberController@ideas');
    $router->get('/member/{username}/likes', 'MemberController@likes');
    $router->get('/member/{username}/reputation', 'MemberController@reputation');
    $router->get('/member/{username}/subscriptions', 'MemberController@subscriptions');
    $router->post('/member/{username}/give-rep', 'MemberController@giveRep');
    $router->post('/member/{username}/block', 'MemberController@block');
    $router->post('/member/{username}/unblock', 'MemberController@unblock');
    $router->post('/member/{username}/profile-comments', 'MemberController@addProfileComment');
    $router->post('/member/{username}/profile-comments/{id}/delete', 'MemberController@deleteProfileComment');
    $router->get('/conversations', 'ConversationController@index');
    $router->get('/conversations/new', 'ConversationController@createForm');
    $router->get('/conversations/new/{username}', 'ConversationController@createForm');
    $router->post('/conversations', 'ConversationController@store');
    $router->post('/conversations/purge', 'ConversationController@purgeInbox');
    $router->post('/conversations/{id}/hide', 'ConversationController@hideConversation');
    $router->post('/conversations/{id}/messages/delete', 'ConversationController@deleteMessages');
    $router->post('/conversations/{id}/messages/purge-thread', 'ConversationController@purgeThreadMessages');
    $router->get('/conversations/{id}', 'ConversationController@show');
    $router->post('/conversations/{id}/reply', 'ConversationController@storeReply');

    $router->get('/notifications', 'NotificationController@index');
    $router->get('/notifications/{id}/read', 'NotificationController@markRead');
    $router->post('/notifications/read-all', 'NotificationController@markAllRead');

    $router->get('/profile/edit', 'ProfileController@editForm');
    $router->post('/profile/update', 'ProfileController@update');
    $router->get('/profile/account', 'ProfileController@accountForm');
    $router->post('/profile/suspend-account', 'ProfileController@suspendAccount');
    $router->post('/profile/close-account', 'ProfileController@closeAccount');
    $router->get('/profile/preferences', 'ProfileController@preferencesForm');
    $router->post('/profile/preferences', 'ProfileController@preferencesUpdate');
    $router->get('/profile/password', 'ProfileController@passwordForm');
    $router->post('/profile/password', 'ProfileController@passwordUpdate');
    $router->post('/profile/cover-upload', 'ProfileController@coverUpload');
    $router->post('/profile/avatar-upload', 'ProfileController@avatarUpload');
    $router->get('/page/{slug}', 'PageController@show');

    // Documentation (core, Docusaurus-style; path-based: /documentation, /documentation/section, /documentation/section/page, /documentation/section/subsection/page)
    $router->get('/documentation', 'DocumentationController@index');
    $router->get('/documentation/{*path}', 'DocumentationController@showByPath');

    // Admin Panel (Dynamic Route)
    $adminPath = env('ADMIN_PATH', 'admin');
    $router->get('/' . $adminPath . '/smileys', 'AdminSmileyController@index');
    $router->post('/' . $adminPath . '/smileys/settings', 'AdminSmileyController@updateSettings');
    $router->get('/' . $adminPath . '/smileys/create', 'AdminSmileyController@create');
    $router->post('/' . $adminPath . '/smileys/store', 'AdminSmileyController@store');
    $router->get('/' . $adminPath . '/smileys/edit/{id}', 'AdminSmileyController@edit');
    $router->post('/' . $adminPath . '/smileys/update/{id}', 'AdminSmileyController@update');
    $router->post('/' . $adminPath . '/smileys/delete/{id}', 'AdminSmileyController@delete');

    // Admin: Category & Forum Management
    $router->get('/' . $adminPath . '/forums', 'AdminForumController@index');

    $router->get('/' . $adminPath . '/categories/create', 'AdminForumController@createCategory');
    $router->post('/' . $adminPath . '/categories/store', 'AdminForumController@storeCategory');
    $router->get('/' . $adminPath . '/categories/edit/{id}', 'AdminForumController@editCategory');
    $router->post('/' . $adminPath . '/categories/update/{id}', 'AdminForumController@updateCategory');
    $router->post('/' . $adminPath . '/categories/delete/{id}', 'AdminForumController@deleteCategory');

    $router->get('/' . $adminPath . '/forums/create', 'AdminForumController@createForum');
    $router->post('/' . $adminPath . '/forums/store', 'AdminForumController@storeForum');
    $router->get('/' . $adminPath . '/forums/edit/{id}', 'AdminForumController@editForum');
    $router->post('/' . $adminPath . '/forums/update/{id}', 'AdminForumController@updateForum');
    $router->post('/' . $adminPath . '/forums/delete/{id}', 'AdminForumController@deleteForum');
    $router->post('/' . $adminPath . '/forums/reorder', 'AdminForumController@reorder');

    // Admin: RSS → konu içe aktarma
    $router->get('/' . $adminPath . '/rss-feeds', 'AdminRssFeedController@index');
    $router->get('/' . $adminPath . '/rss-feeds/create', 'AdminRssFeedController@create');
    $router->post('/' . $adminPath . '/rss-feeds/store', 'AdminRssFeedController@store');
    $router->get('/' . $adminPath . '/rss-feeds/edit/{id}', 'AdminRssFeedController@edit');
    $router->post('/' . $adminPath . '/rss-feeds/update/{id}', 'AdminRssFeedController@update');
    $router->post('/' . $adminPath . '/rss-feeds/delete/{id}', 'AdminRssFeedController@delete');
    $router->post('/' . $adminPath . '/rss-feeds/import-now/{id}', 'AdminRssFeedController@importNow');
    $router->get('/' . $adminPath . '/rss-feeds/preview', 'AdminRssFeedController@preview');

    // Admin: Sayfalar (statik sayfalar CRUD)
    $router->get('/' . $adminPath . '/pages', 'AdminPagesController@index');
    $router->get('/' . $adminPath . '/pages/create', 'AdminPagesController@create');
    $router->post('/' . $adminPath . '/pages/store', 'AdminPagesController@store');
    $router->get('/' . $adminPath . '/pages/edit/{id}', 'AdminPagesController@edit');
    $router->post('/' . $adminPath . '/pages/update/{id}', 'AdminPagesController@update');
    $router->post('/' . $adminPath . '/pages/delete/{id}', 'AdminPagesController@delete');

    // Admin: Widget - Sidebar yönetimi
    $router->get('/' . $adminPath . '/widgets', 'AdminWidgetController@index');
    $router->get('/' . $adminPath . '/widgets/create', 'AdminWidgetController@create');
    $router->post('/' . $adminPath . '/widgets/store', 'AdminWidgetController@store');
    $router->get('/' . $adminPath . '/widgets/edit/{id}', 'AdminWidgetController@edit');
    $router->post('/' . $adminPath . '/widgets/update/{id}', 'AdminWidgetController@update');
    $router->post('/' . $adminPath . '/widgets/delete/{id}', 'AdminWidgetController@delete');
    $router->post('/' . $adminPath . '/widgets/reorder', 'AdminWidgetController@reorder');

    // Admin: Eklentiler (plugins)
    $router->get('/' . $adminPath . '/plugins', 'AdminPluginsController@index');
    $router->post('/' . $adminPath . '/plugins/toggle', 'AdminPluginsController@toggle');
    $router->post('/' . $adminPath . '/plugins/uninstall', 'AdminPluginsController@uninstall');

    // Admin: User Management
    $router->get('/' . $adminPath . '/users', 'AdminUserController@index');
    $router->get('/' . $adminPath . '/users/create', 'AdminUserController@create');
    $router->post('/' . $adminPath . '/users/store', 'AdminUserController@store');
    $router->get('/' . $adminPath . '/users/edit/{id}', 'AdminUserController@edit');
    $router->post('/' . $adminPath . '/users/update/{id}', 'AdminUserController@update');
    $router->post('/' . $adminPath . '/users/delete/{id}', 'AdminUserController@delete');
    $router->post('/' . $adminPath . '/users/bulk', 'AdminUserController@bulk');

    // Admin: Invitations
    $router->get('/' . $adminPath . '/invitations', 'AdminInvitationController@index');
    $router->post('/' . $adminPath . '/invitations/generate', 'AdminInvitationController@generate');
    $router->post('/' . $adminPath . '/invitations/revoke/{id}', 'AdminInvitationController@revoke');

    // Admin: User Policies (Bans & Warnings)
    $router->get('/' . $adminPath . '/policies/bans', 'AdminUserPolicyController@bans');
    $router->get('/' . $adminPath . '/policies/warnings', 'AdminUserPolicyController@warnings');

    // Admin: Prefixes Management
    $router->get('/' . $adminPath . '/prefixes', 'AdminPrefixController@index');
    $router->get('/' . $adminPath . '/prefixes/create', 'AdminPrefixController@create');
    $router->post('/' . $adminPath . '/prefixes/store', 'AdminPrefixController@store');
    $router->get('/' . $adminPath . '/prefixes/edit/{id}', 'AdminPrefixController@edit');
    $router->post('/' . $adminPath . '/prefixes/update/{id}', 'AdminPrefixController@update');
    $router->post('/' . $adminPath . '/prefixes/delete/{id}', 'AdminPrefixController@delete');

    // Admin: Tags (Etiket) Management
    $router->get('/' . $adminPath . '/tags', 'AdminTagController@index');
    $router->get('/' . $adminPath . '/tags/create', 'AdminTagController@create');
    $router->post('/' . $adminPath . '/tags/store', 'AdminTagController@store');
    $router->get('/' . $adminPath . '/tags/edit/{id}', 'AdminTagController@edit');
    $router->post('/' . $adminPath . '/tags/update/{id}', 'AdminTagController@update');
    $router->post('/' . $adminPath . '/tags/delete/{id}', 'AdminTagController@delete');

    // Admin: Role (Group) Management
    $router->get('/' . $adminPath . '/roles', 'AdminRoleController@index');
    $router->get('/' . $adminPath . '/roles/create', 'AdminRoleController@create');
    $router->post('/' . $adminPath . '/roles/store', 'AdminRoleController@store');
    $router->get('/' . $adminPath . '/roles/edit/{id}', 'AdminRoleController@edit');
    $router->post('/' . $adminPath . '/roles/update/{id}', 'AdminRoleController@update');
    $router->post('/' . $adminPath . '/roles/delete/{id}', 'AdminRoleController@delete');


    // Admin: Permission Definitions (CRUD) + Group Permissions (ACL Mapping)
    $router->get('/' . $adminPath . '/permissions', 'AdminPermissionController@index');
    $router->get('/' . $adminPath . '/permissions/create', 'AdminPermissionController@create');
    $router->post('/' . $adminPath . '/permissions/store', 'AdminPermissionController@store');
    $router->get('/' . $adminPath . '/permissions/edit/{id}', 'AdminPermissionController@edit');
    $router->post('/' . $adminPath . '/permissions/update/{id}', 'AdminPermissionController@update');
    $router->post('/' . $adminPath . '/permissions/delete/{id}', 'AdminPermissionController@delete');

    $router->get('/' . $adminPath . '/group-permissions', 'AdminGroupPermissionController@index');
    $router->get('/' . $adminPath . '/group-permissions/edit/{id}', 'AdminGroupPermissionController@edit');
    $router->post('/' . $adminPath . '/group-permissions/update/{id}', 'AdminGroupPermissionController@update');

    // Admin: Kullanıcı özel alanları
    $router->get('/' . $adminPath . '/custom-fields', 'AdminCustomFieldController@index');
    $router->get('/' . $adminPath . '/custom-fields/create', 'AdminCustomFieldController@create');
    $router->post('/' . $adminPath . '/custom-fields/store', 'AdminCustomFieldController@store');
    $router->get('/' . $adminPath . '/custom-fields/edit/{id}', 'AdminCustomFieldController@edit');
    $router->post('/' . $adminPath . '/custom-fields/update/{id}', 'AdminCustomFieldController@update');
    $router->post('/' . $adminPath . '/custom-fields/delete/{id}', 'AdminCustomFieldController@delete');

    // Admin: Reputation
    $router->get('/' . $adminPath . '/reputations', 'AdminReputationController@index');
    $router->get('/' . $adminPath . '/reputations/edit/{id}', 'AdminReputationController@edit');
    $router->post('/' . $adminPath . '/reputations/update/{id}', 'AdminReputationController@update');
    $router->post('/' . $adminPath . '/reputations/delete/{id}', 'AdminReputationController@delete');
    $router->post('/' . $adminPath . '/reputations/toggle-setting', 'AdminReputationController@toggleSetting');

    // Admin: Konu ve Post Ayarları
    $router->get('/' . $adminPath . '/topic-post-settings', 'AdminTopicPostSettingsController@index');
    $router->post('/' . $adminPath . '/topic-post-settings/update', 'AdminTopicPostSettingsController@update');

    // Admin: Konu Ayarları (tüm konu işlemleri: listeleme, filtreleme, düzenleme, toplu işlemler)
    $router->get('/' . $adminPath . '/topic-settings', 'AdminTopicSettingsController@index');
    $router->get('/' . $adminPath . '/topic-settings/edit/{id}', 'AdminTopicSettingsController@edit');
    $router->post('/' . $adminPath . '/topic-settings/update/{id}', 'AdminTopicSettingsController@update');
    $router->post('/' . $adminPath . '/topic-settings/bulk', 'AdminTopicSettingsController@bulk');

    // Admin: Portal ve Makaleler
    $router->get('/' . $adminPath . '/portal-settings', 'AdminPortalSettingsController@index');
    $router->post('/' . $adminPath . '/portal-settings/update', 'AdminPortalSettingsController@update');

    // Admin: Documentation (Docusaurus-style, core)
    $router->get('/' . $adminPath . '/documentation-settings', 'AdminDocumentationController@index');
    $router->post('/' . $adminPath . '/documentation-settings/update', 'AdminDocumentationController@update');
    $router->post('/' . $adminPath . '/documentation-settings/sections/store', 'AdminDocumentationController@storeSection');
    $router->get('/' . $adminPath . '/documentation-settings/sections/edit/{id}', 'AdminDocumentationController@editSection');
    $router->post('/' . $adminPath . '/documentation-settings/sections/update/{id}', 'AdminDocumentationController@updateSection');
    $router->post('/' . $adminPath . '/documentation-settings/sections/delete/{id}', 'AdminDocumentationController@deleteSection');
    $router->post('/' . $adminPath . '/documentation-settings/sections/reorder', 'AdminDocumentationController@reorderSections');
    $router->post('/' . $adminPath . '/documentation-settings/pages/reorder', 'AdminDocumentationController@reorderPages');
    $router->post('/' . $adminPath . '/documentation-settings/pages/store', 'AdminDocumentationController@storePage');
    $router->get('/' . $adminPath . '/documentation-settings/pages/edit/{id}', 'AdminDocumentationController@editPage');
    $router->post('/' . $adminPath . '/documentation-settings/pages/update/{id}', 'AdminDocumentationController@updatePage');
    $router->post('/' . $adminPath . '/documentation-settings/pages/delete/{id}', 'AdminDocumentationController@deletePage');

    // Admin: Son Olaylar Kartı
    $router->get('/' . $adminPath . '/son-olaylar', 'AdminSonOlaylarController@index');
    $router->post('/' . $adminPath . '/son-olaylar/update', 'AdminSonOlaylarController@update');

    // Admin: Hero / Giriş kartı
    $router->get('/' . $adminPath . '/hero', 'AdminHeroController@index');
    $router->post('/' . $adminPath . '/hero/update', 'AdminHeroController@update');

    // Admin: Sistem Ayarları (logo, admin URL, üst menü, mail)
    $router->get('/' . $adminPath . '/themes', 'AdminThemeController@index');
    $router->get('/' . $adminPath . '/themes/activate-frontend/{slug}', 'AdminThemeController@activateFrontend');
    $router->get('/' . $adminPath . '/themes/activate-admin/{slug}', 'AdminThemeController@activateAdmin');
    $router->get('/' . $adminPath . '/themes/preview/{type}/{slug}', 'AdminThemeController@previewImage');
    $router->get('/' . $adminPath . '/themes/editor/{slug}', 'AdminThemeController@editor');
    $router->post('/' . $adminPath . '/themes/editor/{slug}', 'AdminThemeController@editorSave');
    $router->get('/' . $adminPath . '/themes/settings', 'AdminThemeController@simpleSettings');
    $router->post('/' . $adminPath . '/themes/settings', 'AdminThemeController@simpleSettingsSave');
    $router->get('/' . $adminPath . '/system-settings', 'AdminSystemSettingsController@index');
    $router->post('/' . $adminPath . '/system-settings/update', 'AdminSystemSettingsController@update');
    $router->get('/' . $adminPath . '/user-settings', 'AdminUserSettingsController@index');
    $router->post('/' . $adminPath . '/user-settings/update', 'AdminUserSettingsController@update');
    $router->post('/' . $adminPath . '/system-settings/rebuild-sef-urls', 'AdminSystemSettingsController@rebuildSefUrls');
    $router->post('/' . $adminPath . '/system-settings/rebuild-sitemap', 'AdminSystemSettingsController@rebuildSitemap');
    $router->post('/' . $adminPath . '/system-settings/mail-test', 'AdminSystemSettingsController@mailTest');

    // Admin: Duyurular
    $router->get('/' . $adminPath . '/announcements', 'AdminAnnouncementsController@index');
    $router->get('/' . $adminPath . '/announcements/create', 'AdminAnnouncementsController@create');
    $router->post('/' . $adminPath . '/announcements/store', 'AdminAnnouncementsController@store');
    $router->get('/' . $adminPath . '/announcements/edit/{id}', 'AdminAnnouncementsController@edit');
    $router->post('/' . $adminPath . '/announcements/update/{id}', 'AdminAnnouncementsController@update');
    $router->post('/' . $adminPath . '/announcements/delete/{id}', 'AdminAnnouncementsController@delete');

    // Admin: Reklamlar
    $router->get('/' . $adminPath . '/ads', 'AdminAdsController@index');
    $router->get('/' . $adminPath . '/ads/create', 'AdminAdsController@create');
    $router->post('/' . $adminPath . '/ads/store', 'AdminAdsController@store');
    $router->get('/' . $adminPath . '/ads/edit/{id}', 'AdminAdsController@edit');
    $router->post('/' . $adminPath . '/ads/update/{id}', 'AdminAdsController@update');
    $router->post('/' . $adminPath . '/ads/delete/{id}', 'AdminAdsController@delete');

    // Admin: 2FA (panele giriş soru-cevap)
    $router->get('/' . $adminPath . '/twofa', 'AdminTwoFaController@index');
    $router->post('/' . $adminPath . '/twofa', 'AdminTwoFaController@verify');

    // Admin: Sansür koruma (yasak kelimeler, yasak kullanıcı adları, temp mail)
    $router->get('/' . $adminPath . '/censorship', 'AdminCensorshipController@index');
    $router->post('/' . $adminPath . '/censorship/settings/update', 'AdminCensorshipController@updateSettings');
    $router->post('/' . $adminPath . '/censorship/words/store', 'AdminCensorshipController@storeWord');
    $router->post('/' . $adminPath . '/censorship/words/delete/{id}', 'AdminCensorshipController@deleteWord');
    $router->post('/' . $adminPath . '/censorship/usernames/store', 'AdminCensorshipController@storeUsername');
    $router->post('/' . $adminPath . '/censorship/usernames/delete/{id}', 'AdminCensorshipController@deleteUsername');
    $router->post('/' . $adminPath . '/censorship/domains/store', 'AdminCensorshipController@storeDomain');
    $router->post('/' . $adminPath . '/censorship/domains/delete/{id}', 'AdminCensorshipController@deleteDomain');

    // Admin: Güvenlik (merkezi anti-bump, cooldown, geçici engel)
    $router->get('/' . $adminPath . '/security', 'AdminSecurityController@index');
    $router->get('/' . $adminPath . '/security/twofa', 'AdminTwoFaController@settingsForm');
    $router->post('/' . $adminPath . '/security/twofa', 'AdminTwoFaController@settingsUpdate');
    $router->get('/' . $adminPath . '/security/log', 'AdminSecurityController@log');
    $router->post('/' . $adminPath . '/security/log/purge', 'AdminSecurityController@logPurge');
    $router->post('/' . $adminPath . '/security/log/delete-all', 'AdminSecurityController@logDeleteAll');
    $router->post('/' . $adminPath . '/security/update', 'AdminSecurityController@update');
    $router->post('/' . $adminPath . '/security/rtbh-refresh', 'AdminSecurityController@rtbhRefresh');
    $router->post('/' . $adminPath . '/security/toggle-attack-mode', 'AdminSecurityController@toggleAttackMode');

    // Admin: Canlı trafik / sistem günlüğü (ziyaretçi, bot, giriş/çıkış, güvenlik olayları)
    $router->get('/' . $adminPath . '/analytics', 'AdminSecurityController@analytics');
    $router->get('/' . $adminPath . '/analytics/feed', 'AdminSecurityController@analyticsFeed');
    $router->post('/' . $adminPath . '/analytics/purge', 'AdminSecurityController@analyticsPurge');
    $router->post('/' . $adminPath . '/analytics/delete-all', 'AdminSecurityController@analyticsDeleteAll');

    // Admin: Spam & Zombie (kısa mesaj engeli, pasif kullanıcı askıya alma)
    $router->get('/' . $adminPath . '/spam-zombie', 'AdminSpamZombieController@index');
    $router->post('/' . $adminPath . '/spam-zombie/save', 'AdminSpamZombieController@save');
    $router->post('/' . $adminPath . '/spam-zombie/unsuspend/{id}', 'AdminSpamZombieController@unsuspend');

    // Admin: Performans (cache, Redis, minify, CDN, lazy load)
    $router->get('/' . $adminPath . '/performance', 'AdminPerformanceController@index');
    $router->post('/' . $adminPath . '/performance/update', 'AdminPerformanceController@update');
    $router->post('/' . $adminPath . '/performance/redis-test', 'AdminPerformanceController@redisTest');
    $router->post('/' . $adminPath . '/performance/clear-cache', 'AdminPerformanceController@clearCache');

    // Admin: Cronjobs (Araçlar)
    $router->get('/' . $adminPath . '/cronjobs', 'AdminCronjobsController@index');
    $router->post('/' . $adminPath . '/cronjobs/run-full', 'AdminCronjobsController@runFull');

    // Admin: Sistem Rebuild
    $router->get('/' . $adminPath . '/rebuild', 'AdminRebuildController@index');
    $router->post('/' . $adminPath . '/rebuild/run', 'AdminRebuildController@run');
    $router->post('/' . $adminPath . '/rebuild/composer-install', 'AdminRebuildController@composerInstall');
    $router->post('/' . $adminPath . '/rebuild/run-migrations', 'AdminRebuildController@runMigrations');

    // Admin: Mesaj ve Bildirim Ayarları
    $router->get('/' . $adminPath . '/communication-settings', 'AdminCommunicationSettingsController@index');
    $router->post('/' . $adminPath . '/communication-settings/update', 'AdminCommunicationSettingsController@update');

    // Admin: Ödül Seviyeleri
    $router->get('/' . $adminPath . '/rewards', 'AdminRewardController@index');
    $router->get('/' . $adminPath . '/rewards/create', 'AdminRewardController@create');
    $router->post('/' . $adminPath . '/rewards/store', 'AdminRewardController@store');
    $router->get('/' . $adminPath . '/rewards/edit/{id}', 'AdminRewardController@edit');
    $router->post('/' . $adminPath . '/rewards/update/{id}', 'AdminRewardController@update');
    $router->post('/' . $adminPath . '/rewards/delete/{id}', 'AdminRewardController@delete');

    // Frontend Topic/Post Actions
    $router->get('/topic/{id}/edit', 'TopicController@edit');
    $router->post('/topic/{id}/edit', 'TopicController@update');
    $router->post('/topic/{id}/delete', 'TopicController@delete');

    $router->get('/topic/{id}/move', 'TopicController@moveForm');
    $router->post('/topic/{id}/move', 'TopicController@move');

    $router->get('/topic/{id}/merge', 'TopicController@mergeForm');
    $router->post('/topic/{id}/merge', 'TopicController@merge');

    $router->post('/topic/{id}/toggle-lock', 'TopicController@toggleLock');
    $router->post('/topic/{id}/toggle-sticky', 'TopicController@toggleSticky');
    $router->post('/topic/{id}/bump', 'TopicController@bump');
    $router->post('/topic/{id}/convert-to-article', 'TopicController@convertToArticle');
    $router->post('/article/{id}/convert-to-forum', 'TopicController@convertToForum');

    // Mesaj toplu işlem (birleştir / sil / raporla) — sadece moderatör/admin
    $router->post('/topic/{id}/posts-bulk', 'TopicController@postsBulk');
    $router->post('/topic/{id}/posts-bulk-report', 'TopicController@postsBulkReport');

    // Konu abonelik
    $router->post('/topic/{id}/subscribe', 'TopicController@subscribe');
    $router->post('/topic/{id}/unsubscribe', 'TopicController@unsubscribe');

    // Frontend Reply
    $router->post('/topic/{id}/reply', 'TopicController@storeReply');

    // Mesaj (cevap) düzenleme — yazar veya admin/moderatör
    $router->get('/post/{id}/edit', 'TopicController@editPost');
    $router->post('/post/{id}/edit', 'TopicController@updatePost');

    // Mesaj beğeni (toggle)
    $router->post('/post/{id}/like', 'TopicController@togglePostLike');

    // Soru/çözüm: mesaja yukarı-aşağı oy
    $router->post('/post/{id}/vote', 'TopicController@votePost');
    // Soru/çözüm: konu sahibi çözüm kabul eder / çözümü kaldırır
    $router->post('/topic/{id}/accept-solution', 'TopicController@acceptSolution');
    $router->post('/topic/{id}/remove-solution', 'TopicController@removeSolution');

    // Mesaj raporlama
    $router->post('/post/{id}/report', 'TopicController@reportPost');

    // Moderasyon (admin/moderatör)
    $router->get('/moderation/reports', 'ModerationController@reports');
    $router->post('/moderation/reports/bulk-reviewed', 'ModerationController@reportsBulkReviewed');
    $router->post('/moderation/reports/bulk-dismiss', 'ModerationController@reportsBulkDismiss');
    $router->post('/moderation/reports/{id}/reviewed', 'ModerationController@markReviewed');
    $router->post('/moderation/reports/{id}/dismiss', 'ModerationController@dismiss');
    $router->get('/moderation/approvals', 'ModerationController@approvals');
    $router->post('/moderation/approvals/bulk-approve', 'ModerationController@approvalsBulkApprove');
    $router->post('/moderation/approvals/bulk-reject', 'ModerationController@approvalsBulkReject');
    $router->post('/moderation/approvals/{id}/approve', 'ModerationController@approveUser');

    // CKEditor resim yükleme (giriş gerekli)
    $router->post('/upload/image', 'UploadController@image');

    // Attachments
    $router->post('/upload/attachment', 'AttachmentController@upload');
    $router->get('/attachment/{id}/download', 'AttachmentController@download');
    $router->post('/attachment/{id}/delete', 'AttachmentController@delete');

    // Polls
    $router->post('/topic/{id}/poll/vote', 'PollController@vote');

    // Post edit history
    $router->get('/post/{id}/edit-history', 'TopicController@editHistory');

    // Admin: soft-delete trash management
    $router->get('/' . $adminPath . '/trash', 'AdminTrashController@index');
    $router->post('/' . $adminPath . '/trash/restore/{type}/{id}', 'AdminTrashController@restore');
    $router->post('/' . $adminPath . '/trash/purge/{type}/{id}', 'AdminTrashController@purge');
    $router->post('/' . $adminPath . '/trash/empty', 'AdminTrashController@emptyTrash');

    // Admin: Veri aktarımı (XenForo, MyBB, SMF vb.)
    $router->get('/' . $adminPath . '/import', 'AdminImportController@index');
    $router->post('/' . $adminPath . '/import/test-connection', 'AdminImportController@testSourceConnection');
    $router->post('/' . $adminPath . '/import/clear-connection', 'AdminImportController@clearSourceConnection');
    $router->post('/' . $adminPath . '/import/run-step', 'AdminImportController@runStep');
    $router->post('/' . $adminPath . '/import/reset', 'AdminImportController@resetImport');

    // Admin: system reset
    $router->get('/' . $adminPath . '/reset', 'AdminResetController@index');
    $router->post('/' . $adminPath . '/reset/execute', 'AdminResetController@execute');

    // Admin: Gelen İletişim Mesajları
    $router->get('/' . $adminPath . '/contact', 'AdminContactController@index');
    $router->get('/' . $adminPath . '/contact/show/{id}', 'AdminContactController@show');
    $router->post('/' . $adminPath . '/contact/reply/{id}', 'AdminContactController@reply');
    $router->post('/' . $adminPath . '/contact/delete/{id}', 'AdminContactController@delete');

    // Timeline and Contact Features
    $router->get('/timeline', 'TimelineController@index');
    $router->get('/iletisim', 'ContactController@index');
    $router->post('/iletisim', 'ContactController@submit');

    // Admin: Dil Yönetimi
    $router->get('/' . $adminPath . '/languages', 'AdminLanguageController@index');
    $router->get('/' . $adminPath . '/languages/create', 'AdminLanguageController@create');
    $router->post('/' . $adminPath . '/languages/store', 'AdminLanguageController@store');
    $router->get('/' . $adminPath . '/languages/edit/{code}', 'AdminLanguageController@edit');
    $router->post('/' . $adminPath . '/languages/update/{code}', 'AdminLanguageController@update');
    $router->post('/' . $adminPath . '/languages/delete/{code}', 'AdminLanguageController@destroy');
    $router->post('/' . $adminPath . '/languages/set-default/{code}', 'AdminLanguageController@setDefault');
    $router->get('/' . $adminPath . '/languages/export/{code}', 'AdminLanguageController@export');
    $router->post('/' . $adminPath . '/languages/import', 'AdminLanguageController@import');

    // Locale Switch (kullanıcı dil değiştirme) — GET misafir için (sadece session), POST CSRF ile kalıcı
    $router->get('/set-locale', 'LocaleController@switchGet');
    $router->post('/set-locale', 'LocaleController@switch');

    $idelistRoutes = require __DIR__ . '/idelist.php';
    $idelistRoutes($router);
};
