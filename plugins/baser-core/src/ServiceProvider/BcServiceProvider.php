<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       http://basercms.net/license/index.html MIT License
 */

namespace BaserCore\ServiceProvider;

use BaserCore\Service\PagesDisplayService;
use BaserCore\Service\PagesDisplayServiceInterface;
use Cake\Core\ServiceProvider;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;
use BaserCore\Annotation\UnitTest;
use BaserCore\Service\PageService;
use BaserCore\Service\SiteService;
use BaserCore\Service\UserService;
use BaserCore\Service\DblogService;
use BaserCore\Service\PluginService;
use BaserCore\Service\BcAdminService;
use BaserCore\Service\BcFrontService;
use BaserCore\Service\ContentService;
use BaserCore\Service\UserGroupService;
use BaserCore\Service\PermissionService;
use BaserCore\Service\SiteConfigService;
use BaserCore\Service\SearchIndexService;
use BaserCore\Service\ContentFolderService;
use BaserCore\Service\PageServiceInterface;
use BaserCore\Service\SiteServiceInterface;
use BaserCore\Service\UserServiceInterface;
use BaserCore\Service\DblogServiceInterface;
use BaserCore\Service\PluginServiceInterface;
use BaserCore\Service\BcAdminServiceInterface;
use BaserCore\Service\BcFrontServiceInterface;
use BaserCore\Service\ContentServiceInterface;
use BaserCore\Service\UserGroupServiceInterface;
use BaserCore\Service\PermissionServiceInterface;
use BaserCore\Service\SiteConfigServiceInterface;
use BaserCore\Service\SearchIndexServiceInterface;
use BaserCore\Service\ContentFolderServiceInterface;

/**
 * Class BcServiceProvider
 * @package BaserCore\ServiceProvider
 */
class BcServiceProvider extends ServiceProvider
{

    /**
     * Provides
     * @var string[]
     */
    protected $provides = [
        BcAdminServiceInterface::class,
        UserServiceInterface::class,
        UserGroupServiceInterface::class,
        PluginServiceInterface::class,
        PluginServiceInterface::class,
        SiteServiceInterface::class,
        BcFrontServiceInterface::class,
        SiteConfigServiceInterface::class,
        PermissionServiceInterface::class,
        DblogServiceInterface::class,
        ContentServiceInterface::class,
        ContentFolderServiceInterface::class,
        PageServiceInterface::class,
        PagesDisplayServiceInterface::class,
        SearchIndexServiceInterface::class,
    ];

    /**
     * Services
     * @param \Cake\Core\ContainerInterface $container
     * @checked
     * @noTodo
     * @unitTest
     */
    public function services($container): void
    {
        // BcAdminサービス
        $container->add(BcAdminServiceInterface::class, BcAdminService::class);
        // Usersサービス
        $container->add(UserServiceInterface::class, UserService::class);
        // UserGroupsサービス
        $container->add(UserGroupServiceInterface::class, UserGroupService::class, true);
        // Pluginsサービス
        $container->add(PluginServiceInterface::class, PluginService::class, true);
        // Sites サービス
        $container->add(SiteServiceInterface::class, SiteService::class, true);
        $container->add(BcFrontServiceInterface::class, BcFrontService::class, true);
        // SiteConfigsサービス
        $container->add(SiteConfigServiceInterface::class, SiteConfigService::class, true);
        // Permissionsサービス
        $container->add(PermissionServiceInterface::class, PermissionService::class);
        // Dblogsサービス
        $container->add(DblogServiceInterface::class, DblogService::class, true);
        // Contentsサービス
        $container->add(ContentServiceInterface::class, ContentService::class, true);
        // ContentFoldersサービス
        $container->add(ContentFolderServiceInterface::class, ContentFolderService::class, true);
        // Pagesサービス
        $container->add(PageServiceInterface::class, PageService::class, true);
        // PagesDisplayサービス
        $container->add(PagesDisplayServiceInterface::class, PagesDisplayService::class, true);
        // SearchIndexesサービス
        $container->add(SearchIndexServiceInterface::class, SearchIndexService::class, true);

    }

}
