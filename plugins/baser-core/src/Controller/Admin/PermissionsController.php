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

namespace BaserCore\Controller\Admin;

use Cake\Event\EventInterface;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;
use BaserCore\Annotation\UnitTest;
use BaserCore\Model\Table\UserGroupsTable;
use BaserCore\Model\Table\PermissionsTable;
use BaserCore\Service\PermissionServiceInterface;
use BaserCore\Service\UserGroupServiceInterface;
use BaserCore\Controller\Component\BcMessageComponent;
use Authentication\Controller\Component\AuthenticationComponent;

/**
 * Class PermissionsController
 * @package BaserCore\Controller\Admin
 * @property UserGroupsTable $UserGroups
 * @property PermissionsTable $Permissions
 * @property AuthenticationComponent $Authentication
 * @property BcMessageComponent $BcMessage
 */
class PermissionsController extends BcAdminAppController
{

	/**
	 * beforeFilter
     *
	 * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
	 */
	public function beforeFilter(EventInterface $event)
	{
		parent::beforeFilter($event);
        $this->loadModel('BaserCore.Permissions');
        $this->viewBuilder()->setHelpers(
            ['BcTime',
            // 'BcFreeze'
        ]);
        $this->Security->setConfig('unlockedActions', [
            'update_sort',
            'batch',
        ]);
	}

	/**
	 * アクセス制限設定の一覧を表示する
	 *
	 * @return void
     * @checked
     * @unitTest
     * @noTodo
	 */
	public function index(PermissionServiceInterface $permissionService, UserGroupServiceInterface $userGroups, $userGroupId = '')
	{
		$currentUserGroup = $userGroups->get($userGroupId);

        $this->request = $this->request->withQueryParams(['user_group_id' => $userGroupId]);
        $this->setViewConditions('Permission', ['default' => ['query' => [
            'sort' => 'sort',
            'direction' => 'asc',
        ]]]);

        $this->set('currentUserGroup', $currentUserGroup);
        $this->set('permissions', $permissionService->getIndex($this->request->getQueryParams()));

		$this->set('sortmode', $this->request->getQuery('sortmode'));
	}

	/**
	 * [ADMIN] 登録処理
     *
     * @param PermissionServiceInterface $userService
     * @param UserGroupServiceInterface $userGroups
     * @param int $userGroupId
     * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
	 */
	public function add(PermissionServiceInterface $permissionService, UserGroupServiceInterface $userGroups, $userGroupId)
	{
		$currentUserGroup = $userGroups->get($userGroupId);
        if ($this->request->is('post')) {
            try {
                $permission = $permissionService->create($this->request->withData('user_group_id', $currentUserGroup->id)->getData());
                $this->BcMessage->setSuccess(sprintf(__d('baser', '新規アクセス制限設定「%s」を追加しました。'), $permission->name));
                return $this->redirect(['action' => 'index', $userGroupId]);
            } catch (\Cake\ORM\Exception\PersistenceFailedException $e) {
                $permission = $e->getEntity();
                $this->BcMessage->setError(__d('baser', '入力エラーです。内容を修正してください。'));
            }
        }
        $this->set('permission', $permission ?? $permissionService->getNew($userGroupId));
        $this->set('currentUserGroup', $currentUserGroup);
	}

    /**
     * [ADMIN] 編集処理
     *
     * @param PermissionServiceInterface $userService
     * @param UserGroupServiceInterface $userGroups
     * @param int $userGroupId
     * @param int $permissionId
     * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
     */

	public function edit(PermissionServiceInterface $permissionService, UserGroupServiceInterface $userGroups, $userGroupId, $permissionId)
    {
		$currentUserGroup = $userGroups->get($userGroupId);
        $permission = $permissionService->get($permissionId);
        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $permission = $permissionService->update($permission, $this->request->withData('user_group_id', $currentUserGroup->id)->getData());
                $this->BcMessage->setSuccess(sprintf(__d('baser', 'アクセス制限設定「%s」を更新しました。'), $permission->name));
                return $this->redirect(['action' => 'index', $userGroupId]);
            } catch (\Exception $e) {
                $this->BcMessage->setError(__d('baser', '入力エラーです。内容を修正してください。'));
            }
        }

        $this->set('permission', $permission);
        $this->set('currentUserGroup', $currentUserGroup);
    }

    /**
     * [ADMIN] 削除処理
     *
     * @param int $id
     * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
     */
	public function delete(PermissionServiceInterface $permissionService, $permissionId)
    {
        $permission = $permissionService->get($permissionId);
        $permissionName = $permission->name;
        $userGroupId = $permission->user_group_id;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $permission = $permissionService->delete($permissionId);
            $this->BcMessage->setSuccess(sprintf(__d('baser', 'アクセス制限設定「%s」を削除しました。'), $permissionName));
        }
        return $this->redirect(['action' => 'index', $userGroupId]);
    }

    /**
     * [ADMIN] 複製処理
     *
     * @param PermissionServiceInterface $userService
     * @param int $userGroupId
     * @param int $permissionId
     * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
     */
	public function copy(PermissionServiceInterface $permissionService, $permissionId)
    {
        $permission = $permissionService->get($permissionId);
        $userGroupId = $permission->user_group_id;

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($permissionService->copy($permissionId)) {
                $this->BcMessage->setSuccess(sprintf(__d('baser', 'アクセス制限設定「%s」を複製しました。'), $permission->name));
                return $this->redirect(['action' => 'index', $userGroupId]);
            }
            $this->BcMessage->setError(__d('baser', '入力エラーです。内容を修正してください。'));
        }
        return $this->redirect(['action' => 'index', $userGroupId]);
    }

    /**
     * [ADMIN] 無効状態にする（AJAX）
     *
     * @param $id
     * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
     */
	public function unpublish(PermissionServiceInterface $permissionService, $permissionId)
    {
        $permission = $permissionService->get($permissionId);
        $userGroupId = $permission->user_group_id;

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($permissionService->unpublish($permissionId)) {
                $this->BcMessage->setSuccess(sprintf(__d('baser', 'アクセス制限設定「%s」を無効にしました。'),
                    $permission->name));
            }
        }
        return $this->redirect(['action' => 'index', $userGroupId]);
    }

    /**
     * [ADMIN] 有効状態にする（AJAX）
     *
     * @param $id
     * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
     */
	public function publish(PermissionServiceInterface $permissionService, $permissionId)
    {
        $permission = $permissionService->get($permissionId);
        $userGroupId = $permission->user_group_id;

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($permissionService->publish($permissionId)) {
                $this->BcMessage->setSuccess(sprintf(__d('baser', 'アクセス制限設定「%s」を有効にしました。'),
                    $permission->name));
            }
        }
        return $this->redirect(['action' => 'index', $userGroupId]);
    }

    /**
     * 一括処理
     *
     * @return void|Response
     *
     * @checked
     * @noTodo
     * @unitTest
     */
    public function batch(PermissionServiceInterface $permissionService)
    {
        $this->disableAutoRender();
        $allowMethod = [
            'publish' => '有効化',
            'unpublish' => '無効化',
            'delete' => '削除',
        ];

        $method = $this->request->getData('ListTool.batch');
        if (!isset($allowMethod[$method])) {
            return;
        }

        $methodText = $allowMethod[$method];

        foreach($this->request->getData('ListTool.batch_targets') as $id) {
            $permission = $permissionService->get($id);
            if ($permissionService->$method($id)) {
                $this->BcMessage->setSuccess(
                    sprintf(__d('baser', 'プラグイン「%s」 を %sしました。'), $permission->name, $methodText),
                    true,
                    false
                );
            }
        }
        return $this->response->withStringBody('true');
    }

    /**
     * 並び替えを更新する [AJAX]
     *
     * @access    public
     * @param $userGroupId
     * @return void
     *
     * @checked
     * @noTodo
     * @unitTest
     */
    public function update_sort(PermissionServiceInterface $permissionService, $userGroupId)
    {

        $this->disableAutoRender();

        if (!$this->request->getData()) {
            $this->ajaxError(500, __d('baser', '無効な処理です。'));
            return;
        }

        $conditions = [
            'user_group_id' => $userGroupId,
        ];
        if (!$permissionService->changeSort($this->request->getData('Sort.id'), $this->request->getData('Sort.offset'), $conditions)) {
            $this->ajaxError(500, __d('baser', '一度リロードしてから再実行してみてください。'));
            return;
        }

        return $this->response->withStringBody('1');
    }
}
