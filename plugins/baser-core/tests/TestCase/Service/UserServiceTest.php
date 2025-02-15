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

namespace BaserCore\Test\TestCase\Service;

use Cake\Http\Response;
use Cake\Routing\Router;
use BaserCore\Service\UserService;
use BaserCore\TestSuite\BcTestCase;
use BaserCore\Model\Table\LoginStoresTable;

/**
 * Class UserServiceTest
 * @package BaserCore\Test\TestCase\Service
 * @property UserService $Users
 */
class UserServiceTest extends BcTestCase
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
        'plugin.BaserCore.LoginStores',
        'plugin.BaserCore.Sites',
    ];

    /**
     * @var UserService|null
     */
    public $Users = null;

    /**
     * Set Up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Users = new UserService();
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Users);
        Router::reload();
        parent::tearDown();
    }

    /**
     * Test getNew
     */
    public function testGetNew()
    {
        $this->assertEquals(1, $this->Users->getNew()->user_groups[0]->id);
    }

    /**
     * Test get
     */
    public function testGet()
    {
        $user = $this->Users->get(1);
        $this->assertEquals('baser admin', $user->name);
    }

    /**
     * Test getIndex
     */
    public function testGetIndex()
    {
        $request = $this->getRequest('/');

        $users = $this->Users->getIndex($request->getQueryParams());
        $this->assertEquals('baser admin', $users->first()->name);

        $request = $this->getRequest('/?user_group_id=2');
        $users = $this->Users->getIndex($request->getQueryParams());
        $this->assertEquals('baser operator', $users->first()->name);

        $request = $this->getRequest('/?name=baser');
        $users = $this->Users->getIndex($request->getQueryParams());
        $this->assertEquals(3, $users->all()->count());

        $request = $this->getRequest('/?limit=1');
        $users = $this->Users->getIndex($request->getQueryParams());
        $this->assertEquals(1, $users->all()->count());
    }

    /**
     * Test create
     */
    public function testCreate()
    {
        $request = $this->getRequest('/');
        $request = $request->withParsedBody([
            'name' => 'ucmitz',
            'user_groups' => [
                '_ids' => [1]
            ],
            'email' => 'example@example.com',
            'real_name_1' => 'test',
            'password_1' => 'aaaaaaaaaaaaaa',
            'password_2' => 'aaaaaaaaaaaaaa'
        ]);
        $request = $request->withData('password', $request->getData('password_1'));
        $this->Users->create($request->getData());
        $request = $this->getRequest('/?name=ucmitz');
        $users = $this->Users->getIndex($request->getQueryParams());
        $this->assertEquals(1, $users->all()->count());
    }

    /**
     * Test update
     */
    public function testUpdate()
    {
        $data = [
            'name' => 'ucmitz',
            'user_groups' => ['_ids' => [1]]
        ];
        $request = $this->loginAdmin($this->getRequest('/')->withParsedBody($data));
        $user = $this->Users->get(1);
        Router::setRequest($request);
        $this->Users->update($user, $request->getData());
        $request = $this->getRequest('/?name=ucmitz');
        $users = $this->Users->getIndex($request->getQueryParams());
        $this->assertEquals(1, $users->all()->count());
    }

    /**
     * Test delete
     */
    public function testDelete()
    {
        $this->Users->delete(3);
        $request = $this->getRequest('/');
        $users = $this->Users->getIndex($request->getQueryParams());
        $this->assertEquals(2, $users->all()->count());
    }

    /**
     * Test Last Admin Delete
     */
    public function testLastAdminDelete()
    {
        $this->expectException("Cake\Core\Exception\Exception");
        $this->Users->delete(1);
    }

    /**
     * test Login
     */
    public function testLoginAndLogout()
    {
        $request = $this->getRequest('/baser/admin/users/index');
        $authentication = $this->BaserCore->getAuthenticationService($request);
        $request = $request->withAttribute('authentication', $authentication);
        $response = new Response();
        $request = $this->Users->login($request, $response, 1)['request'];
        $this->assertEquals(1, $request->getAttribute('identity')->id);
        $this->assertEquals(1, $request->getSession()->read('AuthAdmin')->id);
        $this->Users->logout($request, $response, 1);
        $this->assertNull($request->getSession()->read('AuthAdmin'));
    }

    /**
     * test reLogin
     */
    public function testReLogin()
    {
        $request = $this->loginAdmin($this->getRequest('/baser/admin/baser-core/users/index'));
        $this->Users->update($request->getAttribute('identity')->getOriginalData(), ['name' => 'test']);
        $request = $this->Users->reLogin($request, new Response())['request'];
        $this->assertEquals('test', $request->getAttribute('identity')->name);
    }

    /**
     * test setCookieAutoLoginKey
     */
    public function testSetCookieAutoLoginKey()
    {
        $response = $this->Users->setCookieAutoLoginKey(new Response(), 1);
        $cookie = $response->getCookie(LoginStoresTable::KEY_NAME);
        $this->assertNotEmpty($cookie['value']);
    }

    /**
     * test checkAutoLogin
     */
    public function testCheckAutoLogin()
    {
        $response = $this->Users->setCookieAutoLoginKey(new Response(), 1);
        $request = $this->getRequest('/baser/admin/users/');
        $beforeCookie = $response->getCookie(LoginStoresTable::KEY_NAME);
        $request = $request->withCookieParams([LoginStoresTable::KEY_NAME => $beforeCookie['value']]);
        $response = $this->Users->checkAutoLogin($request, $response);
        $afterCookie = $response->getCookie(LoginStoresTable::KEY_NAME);
        $this->assertNotEmpty($afterCookie['value']);
        $this->assertNotEquals($beforeCookie['value'], $afterCookie['value']);
    }

    /**
     * test loginToAgent
     */
    public function testLoginToAgentAndReturnLoginUserFromAgent()
    {
        $request = $this->loginAdmin($this->getRequest('/baser/admin/baser-core/users/'));
        $response = new Response();
        $this->Users->loginToAgent($request, $response, 2);
        $this->assertSession(1, 'AuthAgent.User.id');
        $this->assertSession(2, 'AuthAdmin.id');
        $this->Users->returnLoginUserFromAgent($request, $response);
        $this->assertSession(null, 'AuthAgent.User.id');
        $this->assertSession(1, 'AuthAdmin.id');
    }

    /**
     * test reload
     *
     * @return void
     */
    public function testReload()
    {
        // 未ログイン
        $request = $this->loginAdmin($this->getRequest('/baser/admin/users/index'));
        $noLoginUser = $this->Users->reload($request);
        $this->assertTrue($noLoginUser);

        $authentication = $this->BaserCore->getAuthenticationService($request);
        $request = $request->withAttribute('authentication', $authentication);
        $response = new Response();
        $request = $this->Users->login($request, $response, 1)['request'];

        // 通常読込
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get(1);
        $user->name = 'modified name';
        $users->save($user);
        $this->Users->reload($request);
        $this->assertSession('modified name', 'AuthAdmin.name');

        // 削除
        $users->delete($user);
        $this->Users->reload($request);
        $this->assertSessionNotHasKey('AuthAdmin');
    }

    /**
     * Test isSelf
     * @param int $loginId
     * @param int $postId
     * @param bool $expected
     * @dataProvider isSelfUpdateDataProvider
     */
    public function testIsSelf($loginId, $postId, $expected)
    {
        $request = $this->getRequest();
        if ($loginId) {
            $request = $this->loginAdmin($request, $loginId);
        }
        Router::setRequest($request);
        $result = $this->Users->isSelf($postId);
        $this->assertEquals($expected, $result);
    }

    public function isSelfUpdateDataProvider()
    {
        return [
            [1, 1, true],        // 自身を更新
            [1, 2, false],       // 他人を更新
            [null, null, false], // 新規登録
            [null, 1, false]     // 更新
        ];
    }

}
