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

namespace BaserCore\Test\TestCase\View\Helper;

use BaserCore\View\BcAdminAppView;
use BaserCore\View\Helper\BcAdminUserHelper;

/**
 * Class BcAdminUserHelperTest
 *
 * @package BaserCore\Test\TestCase\View\Helper
 */
class BcAdminUserHelperTest extends \BaserCore\TestSuite\BcTestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.BaserCore.Users',
        'plugin.BaserCore.UsersUserGroups',
        'plugin.BaserCore.UserGroups',
    ];

    /**
     * BcAdminUserHelper
     * @var BcAdminUserHelper
     */

    public $BcAdminUser;

    /**
     * setUp
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->BcAdminUser = new BcAdminUserHelper(new BcAdminAppView($this->getRequest('/')));
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        unset($this->BcAdminUser);
        parent::tearDown();
    }

    /**
     * Test isEditable
     * @param int $loginId
     * @param int $postId
     * @param bool $expected
     * @dataProvider isEditableDataProvider
     */
    public function testIsEditable($loginId, $postId, $expected)
    {
        $request = $this->getRequest();
        if ($loginId) {
            $this->loginAdmin($request, $loginId);
        }
        $result = $this->BcAdminUser->isEditable($postId);
        $this->assertEquals($expected, $result);
    }

    public function isEditableDataProvider()
    {
        return [
            [null, null, false],  // 未ログイン新規
            [1, null, false], //ログイン新規
            [null, 1, false],   // 未ログイン更新
            [1, 1, true],   // ログイン更新
            [2, 1, false]   // 管理者以外ログイン更新
        ];
    }

    /**
     * Test isDeletable
     * @param int $loginId
     * @param int $postId
     * @param bool $expected
     * @dataProvider isDeletableDataProvider
     */
    public function testIsDeletable($loginId, $postId, $expected)
    {
        $request = $this->getRequest();
        if ($loginId) {
            $this->loginAdmin($request, $loginId);
        }
        $result = $this->BcAdminUser->isDeletable($postId);
        $this->assertEquals($expected, $result);
    }

    public function isDeletableDataProvider()
    {
        return [
            [null, null, false],  // 未ログインデータ不完全
            [null, 1, false],   // 未ログイン削除
            [1, 2, true],   // 管理者ログイン削除
            [1, 1, false],   // 管理者ログイン自分を削除
            [2, 1, true]   // 非管理者ログイン削除
        ];
    }

    /**
     * Test getUserGroupList
     */
    public function testGetUserGroupList()
    {
        $this->assertIsArray($this->BcAdminUser->getUserGroupList());
    }

}
