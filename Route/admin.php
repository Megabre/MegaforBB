<?php

declare(strict_types=1);

/**
 * Admin routes. Loaded by public/index.php and passed to Application router.
 */

return function (\Forecor\Core\Router $router) {
    // Admin Panel (Dynamic Route)
    $adminPath = env('ADMIN_PATH', 'admin');
    $router->get('/' . $adminPath, 'AdminController@index');
    $router->get('/' . $adminPath . '/api/search', 'AdminApiController@search');

    // Admin: Smiley / Emoji
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

    // Admin: Sayfalar
    $router->get('/' . $adminPath . '/pages', 'AdminPagesController@index');
    $router->get('/' . $adminPath . '/pages/create', 'AdminPagesController@create');
    $router->post('/' . $adminPath . '/pages/store', 'AdminPagesController@store');
    $router->get('/' . $adminPath . '/pages/edit/{id}', 'AdminPagesController@edit');
    $router->post('/' . $adminPath . '/pages/update/{id}', 'AdminPagesController@update');
    $router->post('/' . $adminPath . '/pages/delete/{id}', 'AdminPagesController@delete');

    // Admin: Widget
    $router->get('/' . $adminPath . '/widgets', 'AdminWidgetController@index');
    $router->get('/' . $adminPath . '/widgets/create', 'AdminWidgetController@create');
    $router->post('/' . $adminPath . '/widgets/store', 'AdminWidgetController@store');
    $router->get('/' . $adminPath . '/widgets/edit/{id}', 'AdminWidgetController@edit');
    $router->post('/' . $adminPath . '/widgets/update/{id}', 'AdminWidgetController@update');
    $router->post('/' . $adminPath . '/widgets/delete/{id}', 'AdminWidgetController@delete');
    $router->post('/' . $adminPath . '/widgets/reorder', 'AdminWidgetController@reorder');

    // Admin: Eklentiler
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

    // Admin: User Policies
    $router->get('/' . $adminPath . '/policies/bans', 'AdminUserPolicyController@bans');
    $router->get('/' . $adminPath . '/policies/warnings', 'AdminUserPolicyController@warnings');

    // Admin: Prefixes
    $router->get('/' . $adminPath . '/prefixes', 'AdminPrefixController@index');
    $router->get('/' . $adminPath . '/prefixes/create', 'AdminPrefixController@create');
    $router->post('/' . $adminPath . '/prefixes/store', 'AdminPrefixController@store');
    $router->get('/' . $adminPath . '/prefixes/edit/{id}', 'AdminPrefixController@edit');
    $router->post('/' . $adminPath . '/prefixes/update/{id}', 'AdminPrefixController@update');
    $router->post('/' . $adminPath . '/prefixes/delete/{id}', 'AdminPrefixController@delete');

    // Admin: Tags
    $router->get('/' . $adminPath . '/tags', 'AdminTagController@index');
    $router->get('/' . $adminPath . '/tags/create', 'AdminTagController@create');
    $router->post('/' . $adminPath . '/tags/store', 'AdminTagController@store');
    $router->get('/' . $adminPath . '/tags/edit/{id}', 'AdminTagController@edit');
    $router->post('/' . $adminPath . '/tags/update/{id}', 'AdminTagController@update');
    $router->post('/' . $adminPath . '/tags/delete/{id}', 'AdminTagController@delete');

    // Admin: Role Management
    $router->get('/' . $adminPath . '/roles', 'AdminRoleController@index');
    $router->get('/' . $adminPath . '/roles/create', 'AdminRoleController@create');
    $router->post('/' . $adminPath . '/roles/store', 'AdminRoleController@store');
    $router->get('/' . $adminPath . '/roles/edit/{id}', 'AdminRoleController@edit');
    $router->post('/' . $adminPath . '/roles/update/{id}', 'AdminRoleController@update');
    $router->post('/' . $adminPath . '/roles/delete/{id}', 'AdminRoleController@delete');

    // Admin: Permission Definitions
    $router->get('/' . $adminPath . '/permissions', 'AdminPermissionController@index');
    $router->get('/' . $adminPath . '/permissions/create', 'AdminPermissionController@create');
    $router->post('/' . $adminPath . '/permissions/store', 'AdminPermissionController@store');
    $router->get('/' . $adminPath . '/permissions/edit/{id}', 'AdminPermissionController@edit');
    $router->post('/' . $adminPath . '/permissions/update/{id}', 'AdminPermissionController@update');
    $router->post('/' . $adminPath . '/permissions/delete/{id}', 'AdminPermissionController@delete');

    $router->get('/' . $adminPath . '/group-permissions', 'AdminGroupPermissionController@index');
    $router->get('/' . $adminPath . '/group-permissions/edit/{id}', 'AdminGroupPermissionController@edit');
    $router->post('/' . $adminPath . '/group-permissions/update/{id}', 'AdminGroupPermissionController@update');

    // Admin: Custom Fields
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

    // Admin: Topic & Post Settings
    $router->get('/' . $adminPath . '/topic-post-settings', 'AdminTopicPostSettingsController@index');
    $router->post('/' . $adminPath . '/topic-post-settings/update', 'AdminTopicPostSettingsController@update');

    $router->get('/' . $adminPath . '/topic-settings', 'AdminTopicSettingsController@index');
    $router->get('/' . $adminPath . '/topic-settings/edit/{id}', 'AdminTopicSettingsController@edit');
    $router->post('/' . $adminPath . '/topic-settings/update/{id}', 'AdminTopicSettingsController@update');
    $router->post('/' . $adminPath . '/topic-settings/bulk', 'AdminTopicSettingsController@bulk');

    // Admin: Portal
    $router->get('/' . $adminPath . '/portal-settings', 'AdminPortalSettingsController@index');
    $router->post('/' . $adminPath . '/portal-settings/update', 'AdminPortalSettingsController@update');

    // Admin: PWA
    $router->get('/' . $adminPath . '/pwa-settings', 'AdminPwaSettingsController@index');
    $router->post('/' . $adminPath . '/pwa-settings/update', 'AdminPwaSettingsController@update');

    // Admin: Dosya doğrulama
    $router->get('/' . $adminPath . '/file-verification', 'AdminFileVerificationController@index');
    $router->post('/' . $adminPath . '/file-verification/generate', 'AdminFileVerificationController@generateManifest');
    $router->post('/' . $adminPath . '/file-verification/sync', 'AdminFileVerificationController@syncManifest');

    // Admin: Documentation
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

    // Admin: Son Olaylar
    $router->get('/' . $adminPath . '/son-olaylar', 'AdminSonOlaylarController@index');
    $router->post('/' . $adminPath . '/son-olaylar/update', 'AdminSonOlaylarController@update');

    // Admin: Hero
    $router->get('/' . $adminPath . '/hero', 'AdminHeroController@index');
    $router->post('/' . $adminPath . '/hero/update', 'AdminHeroController@update');

    // Admin: System Settings
    $router->get('/' . $adminPath . '/themes', 'AdminThemeController@index');
    $router->get('/' . $adminPath . '/themes/activate-frontend/{slug}', 'AdminThemeController@activateFrontend');
    $router->get('/' . $adminPath . '/themes/activate-admin/{slug}', 'AdminThemeController@activateAdmin');
    $router->get('/' . $adminPath . '/themes/preview/{type}/{slug}', 'AdminThemeController@previewImage');
    $router->get('/' . $adminPath . '/themes/editor/{slug}', 'AdminThemeController@editor');
    $router->get('/' . $adminPath . '/themes/editor/{slug}/search-files', 'AdminThemeController@editorSearchFiles');
    $router->get('/' . $adminPath . '/themes/editor/{slug}/search-content', 'AdminThemeController@editorSearchContent');
    $router->post('/' . $adminPath . '/themes/editor/{slug}', 'AdminThemeController@editorSave');
    $router->get('/' . $adminPath . '/themes/settings', 'AdminThemeController@simpleSettings');
    $router->post('/' . $adminPath . '/themes/settings', 'AdminThemeController@simpleSettingsSave');
    // Ayarlar: path tabanlı (tab yok), menüden dallanır
    $router->get('/' . $adminPath . '/settings/{section}', 'AdminSystemSettingsController@showSection');
    $router->get('/' . $adminPath . '/system-settings', 'AdminSystemSettingsController@index');
    $router->post('/' . $adminPath . '/system-settings/update', 'AdminSystemSettingsController@update');
    $router->post('/' . $adminPath . '/system-settings/storage-sync', 'AdminSystemSettingsController@storageSync');
    $router->get('/' . $adminPath . '/user-settings', 'AdminUserSettingsController@index');
    $router->post('/' . $adminPath . '/user-settings/update', 'AdminUserSettingsController@update');
    $router->post('/' . $adminPath . '/system-settings/rebuild-sef-urls', 'AdminSystemSettingsController@rebuildSefUrls');
    $router->post('/' . $adminPath . '/system-settings/rebuild-sitemap', 'AdminSystemSettingsController@rebuildSitemap');
    $router->post('/' . $adminPath . '/system-settings/mail-test', 'AdminSystemSettingsController@mailTest');

    // Admin: Announcements
    $router->get('/' . $adminPath . '/announcements', 'AdminAnnouncementsController@index');
    $router->get('/' . $adminPath . '/announcements/create', 'AdminAnnouncementsController@create');
    $router->post('/' . $adminPath . '/announcements/store', 'AdminAnnouncementsController@store');
    $router->get('/' . $adminPath . '/announcements/edit/{id}', 'AdminAnnouncementsController@edit');
    $router->post('/' . $adminPath . '/announcements/update/{id}', 'AdminAnnouncementsController@update');
    $router->post('/' . $adminPath . '/announcements/delete/{id}', 'AdminAnnouncementsController@delete');

    // Admin: Ads
    $router->get('/' . $adminPath . '/ads', 'AdminAdsController@index');
    $router->get('/' . $adminPath . '/ads/create', 'AdminAdsController@create');
    $router->post('/' . $adminPath . '/ads/store', 'AdminAdsController@store');
    $router->get('/' . $adminPath . '/ads/edit/{id}', 'AdminAdsController@edit');
    $router->post('/' . $adminPath . '/ads/update/{id}', 'AdminAdsController@update');
    $router->post('/' . $adminPath . '/ads/delete/{id}', 'AdminAdsController@delete');

    // Admin: Security & 2FA
    $router->get('/' . $adminPath . '/twofa', 'AdminTwoFaController@index');
    $router->post('/' . $adminPath . '/twofa', 'AdminTwoFaController@verify');
    // Sansür: path tabanlı (tab yok)
    $router->get('/' . $adminPath . '/censorship/{section}', 'AdminCensorshipController@showSection');
    $router->get('/' . $adminPath . '/censorship', 'AdminCensorshipController@index');
    $router->post('/' . $adminPath . '/censorship/settings/update', 'AdminCensorshipController@updateSettings');
    $router->post('/' . $adminPath . '/censorship/words/store', 'AdminCensorshipController@storeWord');
    $router->post('/' . $adminPath . '/censorship/words/delete/{id}', 'AdminCensorshipController@deleteWord');
    $router->post('/' . $adminPath . '/censorship/usernames/store', 'AdminCensorshipController@storeUsername');
    $router->post('/' . $adminPath . '/censorship/usernames/delete/{id}', 'AdminCensorshipController@deleteUsername');
    $router->post('/' . $adminPath . '/censorship/domains/store', 'AdminCensorshipController@storeDomain');
    $router->post('/' . $adminPath . '/censorship/domains/delete/{id}', 'AdminCensorshipController@deleteDomain');
    $router->get('/' . $adminPath . '/security', 'AdminSecurityController@index');
    $router->get('/' . $adminPath . '/security/twofa', 'AdminTwoFaController@settingsForm');
    $router->post('/' . $adminPath . '/security/twofa', 'AdminTwoFaController@settingsUpdate');
    $router->get('/' . $adminPath . '/security/log', 'AdminSecurityController@log');
    $router->post('/' . $adminPath . '/security/log/purge', 'AdminSecurityController@logPurge');
    $router->post('/' . $adminPath . '/security/log/delete-all', 'AdminSecurityController@logDeleteAll');
    $router->post('/' . $adminPath . '/security/update', 'AdminSecurityController@update');
    $router->post('/' . $adminPath . '/security/rtbh-refresh', 'AdminSecurityController@rtbhRefresh');
    $router->post('/' . $adminPath . '/security/toggle-attack-mode', 'AdminSecurityController@toggleAttackMode');

    // Admin: Analytics
    $router->get('/' . $adminPath . '/analytics', 'AdminSecurityController@analytics');
    $router->get('/' . $adminPath . '/analytics/feed', 'AdminSecurityController@analyticsFeed');
    $router->post('/' . $adminPath . '/analytics/purge', 'AdminSecurityController@analyticsPurge');
    $router->post('/' . $adminPath . '/analytics/delete-all', 'AdminSecurityController@analyticsDeleteAll');

    // Admin: Spam & Zombie
    $router->get('/' . $adminPath . '/spam-zombie', 'AdminSpamZombieController@index');
    $router->post('/' . $adminPath . '/spam-zombie/save', 'AdminSpamZombieController@save');
    $router->post('/' . $adminPath . '/spam-zombie/unsuspend/{id}', 'AdminSpamZombieController@unsuspend');

    // Admin: Performance — path tabanlı (tab yok), menüden dallanır
    $router->get('/' . $adminPath . '/performance/{section}', 'AdminPerformanceController@showSection');
    $router->get('/' . $adminPath . '/performance', 'AdminPerformanceController@index');
    $router->post('/' . $adminPath . '/performance/update', 'AdminPerformanceController@update');
    $router->post('/' . $adminPath . '/performance/redis-test', 'AdminPerformanceController@redisTest');
    $router->post('/' . $adminPath . '/performance/clear-cache', 'AdminPerformanceController@clearCache');

    // Admin: Cronjobs
    $router->get('/' . $adminPath . '/cronjobs', 'AdminCronjobsController@index');
    $router->post('/' . $adminPath . '/cronjobs/run-full', 'AdminCronjobsController@runFull');

    // Admin: Rebuild
    $router->get('/' . $adminPath . '/rebuild', 'AdminRebuildController@index');
    $router->post('/' . $adminPath . '/rebuild/run', 'AdminRebuildController@run');
    $router->post('/' . $adminPath . '/rebuild/composer-install', 'AdminRebuildController@composerInstall');
    $router->post('/' . $adminPath . '/rebuild/run-migrations', 'AdminRebuildController@runMigrations');

    // Admin: Error Log (Araçlar)
    $router->get('/' . $adminPath . '/error-log', 'AdminErrorLogController@index');

    // Admin: Stop Forum Spam (Araçlar)
    $router->get('/' . $adminPath . '/stop-forum-spam', 'AdminStopForumSpamController@index');
    $router->post('/' . $adminPath . '/stop-forum-spam/save', 'AdminStopForumSpamController@save');
    $router->post('/' . $adminPath . '/stop-forum-spam/test', 'AdminStopForumSpamController@test');

    // Admin: Backup (Araçlar)
    $router->get('/' . $adminPath . '/backup', 'AdminBackupController@index');
    $router->post('/' . $adminPath . '/backup/create-db', 'AdminBackupController@createDb');
    $router->post('/' . $adminPath . '/backup/create-files', 'AdminBackupController@createFiles');
    $router->get('/' . $adminPath . '/backup/download', 'AdminBackupController@download');
    $router->post('/' . $adminPath . '/backup/delete', 'AdminBackupController@delete');
    $router->post('/' . $adminPath . '/backup/delete-all', 'AdminBackupController@deleteAll');

    // Admin: Communication Settings
    $router->get('/' . $adminPath . '/communication-settings', 'AdminCommunicationSettingsController@index');
    $router->post('/' . $adminPath . '/communication-settings/update', 'AdminCommunicationSettingsController@update');

    // Admin: Rewards
    $router->get('/' . $adminPath . '/rewards', 'AdminRewardController@index');
    $router->get('/' . $adminPath . '/rewards/create', 'AdminRewardController@create');
    $router->post('/' . $adminPath . '/rewards/store', 'AdminRewardController@store');
    $router->get('/' . $adminPath . '/rewards/edit/{id}', 'AdminRewardController@edit');
    $router->post('/' . $adminPath . '/rewards/update/{id}', 'AdminRewardController@update');
    $router->post('/' . $adminPath . '/rewards/delete/{id}', 'AdminRewardController@delete');

    // Admin: Trash Management
    $router->get('/' . $adminPath . '/trash', 'AdminTrashController@index');
    $router->post('/' . $adminPath . '/trash/restore/{type}/{id}', 'AdminTrashController@restore');
    $router->post('/' . $adminPath . '/trash/purge/{type}/{id}', 'AdminTrashController@purge');
    $router->post('/' . $adminPath . '/trash/empty', 'AdminTrashController@emptyTrash');

    // Admin: Import
    $router->get('/' . $adminPath . '/import', 'AdminImportController@index');
    $router->post('/' . $adminPath . '/import/test-connection', 'AdminImportController@testSourceConnection');
    $router->post('/' . $adminPath . '/import/clear-connection', 'AdminImportController@clearSourceConnection');
    $router->post('/' . $adminPath . '/import/run-step', 'AdminImportController@runStep');
    $router->post('/' . $adminPath . '/import/reset', 'AdminImportController@resetImport');

    // Admin: Reset
    $router->get('/' . $adminPath . '/reset', 'AdminResetController@index');
    $router->post('/' . $adminPath . '/reset/execute', 'AdminResetController@execute');

    // Admin: Contact Messages (İletişim → Gelen mesajlar)
    $router->get('/' . $adminPath . '/contact', 'AdminContactController@index');
    $router->get('/' . $adminPath . '/contact/show/{id}', 'AdminContactController@show');
    $router->post('/' . $adminPath . '/contact/reply/{id}', 'AdminContactController@reply');
    $router->post('/' . $adminPath . '/contact/delete/{id}', 'AdminContactController@delete');

    // Admin: Communication — Mesaj gönder, toplu mesaj, şablonlar
    $router->get('/' . $adminPath . '/communication/send', 'AdminCommunicationController@sendForm');
    $router->post('/' . $adminPath . '/communication/send', 'AdminCommunicationController@sendPost');
    $router->get('/' . $adminPath . '/communication/bulk-mail', 'AdminCommunicationController@bulkMailForm');
    $router->post('/' . $adminPath . '/communication/bulk-mail', 'AdminCommunicationController@bulkMailPost');
    $router->get('/' . $adminPath . '/communication/bulk', 'AdminCommunicationController@bulkForm');
    $router->post('/' . $adminPath . '/communication/bulk', 'AdminCommunicationController@bulkPost');
    $router->get('/' . $adminPath . '/communication/message-templates', 'AdminCommunicationController@messageTemplatesIndex');
    $router->get('/' . $adminPath . '/communication/message-templates/create', 'AdminCommunicationController@messageTemplateCreate');
    $router->post('/' . $adminPath . '/communication/message-templates/store', 'AdminCommunicationController@messageTemplateStore');
    $router->get('/' . $adminPath . '/communication/message-templates/edit/{id}', 'AdminCommunicationController@messageTemplateEdit');
    $router->post('/' . $adminPath . '/communication/message-templates/update/{id}', 'AdminCommunicationController@messageTemplateUpdate');
    $router->post('/' . $adminPath . '/communication/message-templates/delete/{id}', 'AdminCommunicationController@messageTemplateDelete');
    $router->get('/' . $adminPath . '/communication/mail-templates', 'AdminCommunicationController@mailTemplatesIndex');
    $router->get('/' . $adminPath . '/communication/mail-templates/edit/{id}', 'AdminCommunicationController@mailTemplateEdit');
    $router->post('/' . $adminPath . '/communication/mail-templates/update/{id}', 'AdminCommunicationController@mailTemplateUpdate');

    // Admin: Languages
    $router->get('/' . $adminPath . '/languages', 'AdminLanguageController@index');
    $router->get('/' . $adminPath . '/languages/create', 'AdminLanguageController@create');
    $router->post('/' . $adminPath . '/languages/store', 'AdminLanguageController@store');
    $router->get('/' . $adminPath . '/languages/edit/{code}', 'AdminLanguageController@edit');
    $router->post('/' . $adminPath . '/languages/update/{code}', 'AdminLanguageController@update');
    $router->post('/' . $adminPath . '/languages/delete/{code}', 'AdminLanguageController@destroy');
    $router->post('/' . $adminPath . '/languages/set-default/{code}', 'AdminLanguageController@setDefault');
    $router->get('/' . $adminPath . '/languages/export/{code}', 'AdminLanguageController@export');
    $router->post('/' . $adminPath . '/languages/import', 'AdminLanguageController@import');
    // Admin: License Management (Botble)
    $router->get('/' . $adminPath . '/license', 'AdminLicenseController@index');
    $router->post('/' . $adminPath . '/license/activate', 'AdminLicenseController@activate');
    $router->post('/' . $adminPath . '/license/deactivate', 'AdminLicenseController@deactivate');
    $router->post('/' . $adminPath . '/license/recheck', 'AdminLicenseController@recheck');
};
