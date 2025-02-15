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
namespace BaserCore\View\Helper;

use Cake\View\Helper;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use BaserCore\Utility\BcUtil;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;
use BaserCore\Annotation\UnitTest;
use BaserCore\Model\Entity\Content;
use Cake\Datasource\EntityInterface;
use BaserCore\Service\ContentService;
use BaserCore\Utility\BcContainerTrait;
use BaserCore\Service\PermissionService;
use BaserCore\Service\UserServiceInterface;
use BaserCore\Service\ContentServiceInterface;
use BaserCore\Service\PermissionServiceInterface;

/**
 * BcAdminContentHelper
 * @property ContentService $ContentService
 * @property PermissionService $PermissionService
 */
class BcAdminContentHelper extends Helper
{

    /**
     * Trait
     */
    use BcContainerTrait;

    /**
     * helpers
     *
     * @var array
     */
    public $helpers = ['BaserCore.BcBaser'];

    /**
     * initialize
     * @param array $config
     * @checked
     * @noTodo
     * @unitTest
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->ContentService = $this->getService(ContentServiceInterface::class);
        $this->PermissionService = $this->getService(PermissionServiceInterface::class);
    }

    /**
     * 登録されているタイプの一覧を取得する
     * @return array
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getTypes(): array
    {
        $createdItems = BcUtil::getContentsItem();
        $types = [];
        foreach($createdItems as $key => $value) {
            $types[$key] = $value['title'];
        }
        return $types;
    }

    /**
     * 作成者一覧を取得する
     * @return mixed
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getAuthors()
    {
        return $this->getService(UserServiceInterface::class)->getList();
    }

    /**
     * コンテンツが削除可能かどうか
     *
     * @return bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function isContentDeletable(): bool
    {
        $userGroups = BcUtil::loginUser()->user_groups;

        if ($userGroups) {

            $userGroupIds = Hash::extract($userGroups, '{n}.id');

            if ($this->PermissionService->check(BcUtil::getPrefix() . '/baser-core/contents/delete', $userGroupIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * コンテンツフォルダーのリストを取得
     *
     * @param null $siteId
     * @param array $options
     * @return array|bool
     */
    public function getContentFolderList($siteId = null, $options = [])
    {
        return $this->ContentService->getContentFolderList($siteId, $options);
    }

    /**
     * コンテンツ管理上のURLを元に正式なURLを取得する
     *
     * ドメインからのフルパスでない場合、デフォルトでは、
     * サブフォルダ設置時等の baseUrl（サブフォルダまでのパス）は含まない
     *
     * @param string $url コンテンツ管理上のURL
     * @param bool $full http からのフルのURLかどうか
     * @param bool $useSubDomain サブドメインを利用しているかどうか
     * @param bool $base $full が false の場合、ベースとなるURLを含めるかどうか
     * @return string URL
     */
    public function getUrl($url, $full = false, $useSubDomain = false, $base = false)
    {
        return $this->ContentService->getUrl($url, $full, $useSubDomain, $base);
    }

    /**
     * コンテンツIDよりフルURLを取得する
     *
     * @param int $id コンテンツID
     * @return mixed
     */
    public function getUrlById($id, $full = false)
    {
        return $this->ContentService->getUrlById($id, $full);
    }

    /**
     * データが公開状態にあるか確認する
     *
     * @param array $data コンテンツデータ
     * @param bool $self コンテンツ自身の公開状態かどうか
     * @return mixed
     */
    public function isAllowPublish($data, $self = false)
    {
        return $this->ContentService->isAllowPublish($data, $self);
    }

    /**
     * サイトIDからサイトルートとなるコンテンツを取得する
     *
     * @param int $siteId
     * @return Content
     */
    public function getSiteRoot($siteId)
    {
        return $this->ContentService->getSiteRoot($siteId);
    }

    /**
     * サイトIDからサイトルートとなるコンテンツIDを取得する
     *
     * @param int $siteId
     * @return string|bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getSiteRootId($siteId)
    {
        $content = $this->getSiteRoot($siteId);
        if ($content) {
            return $content->id;
        } else {
            return false;
        }
    }

    /**
     * 現在のコンテンツが属するフォルダまでのフルパスを取得する
     * フォルダ名称部分にはフォルダ編集画面へのリンクを付与する
     * コンテンツ編集画面で利用
     * @param EntityInterface|null $content
     * @return string
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getCurrentFolderLinkedUrl(EntityInterface $content)
    {
        return $this->getFolderLinkedUrl($content);
    }

    /**
     * 対象コンテンツが属するフォルダまでのフルパスを取得する
     * フォルダ名称部分にはフォルダ編集画面へのリンクを付与する
     * コンテンツ編集画面で利用
     *
     * @param Content $content コンテンツデータ
     * @return string
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getFolderLinkedUrl(EntityInterface $content)
    {
        $urlArray = explode('/', preg_replace('/(^\/|\/$)/', '', $content->url));
        unset($urlArray[count($urlArray) - 1]);
        if ($content->site->same_main_url) {
            $sites = TableRegistry::getTableLocator()->get('BaserCore.Sites');
            $site = $sites->findById($content->site->main_site_id)->first();
            array_shift($urlArray);
            if ($site->alias) {
                $urlArray = explode('/', $site->alias) + $urlArray;
            }
        }
        if ($content->site->use_subdomain) {
            $host = $this->getUrl('/' . $urlArray[0] . '/', true, $content->site->use_subdomain);
            array_shift($urlArray);
        } else {
            $host = $this->getUrl('/', true, $content->site->use_subdomain);
        }

        $checkUrl = '/';
        $contentsTable = TableRegistry::getTableLocator()->get('BaserCore.Contents');
        foreach($urlArray as $key => $value) {
            $checkUrl .= $value . '/';
            $target = $contentsTable->find()->select('entity_id')->where(['url' => $checkUrl])->first();
            /* @var Content $target */
            $entityId = $target->entity_id;
            $urlArray[$key] = $this->BcBaser->getLink(rawurldecode($value), [
                'admin' => true,
                'plugin' => 'BaserCore',
                'controller' => 'content_folders',
                'action' => 'edit',
                $entityId
            ], ['forceTitle' => true]);
        }
        $folderLinkedUrl = $host;
        if ($urlArray) {
            $folderLinkedUrl .= implode('/', $urlArray) . '/';
        }
        return $folderLinkedUrl;
    }

    /**
     * getTargetPrefix
     * 重複のない適切なprefixを取得する
     *
     * @param  array $relatedContent
     * @return string $prefix
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getTargetPrefix($relatedContent)
    {
        $prefix = $relatedContent['Site']['name'];
        if ($relatedContent['Site']['alias']) {
            $prefix = $relatedContent['Site']['alias'];
            if($this->ContentService->existsContentByUrl("/$prefix")) {
                $prefix = $this->ContentService->getIndex(['site_id' => $relatedContent['Site']['id'], 'title' => $relatedContent['Site']['alias']])->first()->name;
            }
        }
        if ($prefix) $prefix = "/" . $prefix;
        return $prefix;
    }

}
