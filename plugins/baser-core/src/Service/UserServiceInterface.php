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

namespace BaserCore\Service;

use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use BaserCore\Model\Entity\User;
use Cake\Core\Exception\Exception;
use Cake\Datasource\EntityInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface UserServiceInterface
 * @package BaserCore\Service
 */
interface UserServiceInterface
{

    /**
     * ユーザーを取得する
     * @param int $id
     * @return User
     */
    public function get($id): User;

    /**
     * ユーザー一覧を取得
     * @param array $queryParams
     * @return Query
     */
    public function getIndex(array $queryParams): Query;

    /**
     * 新しいデータの初期値を取得する
     * @return EntityInterface
     */
    public function getNew(): EntityInterface;

    /**
     * 新規登録する
     * @param array $postData
     * @return EntityInterface
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     */
    public function create(array $postData);

    /**
     * 編集する
     * @param EntityInterface $target
     * @param array $postData
     * @return mixed
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     */
    public function update(EntityInterface $target, array $postData);

    /**
     * 削除する
     * @param int $id
     * @return mixed
     */
    public function delete(int $id);

    /**
     * ログイン
     * @param ServerRequest $request
     * @param ResponseInterface $response
     * @param $id
     * @return array|false
     */
    public function login(ServerRequest $request, ResponseInterface $response, $id);

    /**
     * ログアウト
     * @param ServerRequest $request
     * @param ResponseInterface $response
     * @return array|false
     */
    public function logout(ServerRequest $request, ResponseInterface $response, $id);

    /**
     * 再ログイン
     * @param ServerRequest $request
     * @param ResponseInterface $response
     * @return array|false
     */
    public function reLogin(ServerRequest $request, ResponseInterface $response);

    /**
     * ログイン状態の保存のキー送信
     * @param ResponseInterface
     * @param int $id
     * @return ResponseInterface
     * @see https://book.cakephp.org/4/ja/controllers/request-response.html#response-cookies
     */
    public function setCookieAutoLoginKey($response, $id): ResponseInterface;

    /**
     * ログインキーを削除する
     * @param int $id
     * @return int 削除行数
     */
    public function removeLoginKey($id);

    /**
     * ログイン状態の保存確認
     * @return ResponseInterface
     */
    public function checkAutoLogin(ServerRequest $request, ResponseInterface $response): ResponseInterface;

    /**
     * 代理ログインを行う
     * @param ServerRequest $request
     * @param int $id
     * @param string $referer
     */
    public function loginToAgent(ServerRequest $request, ResponseInterface $response, $id, $referer = ''): bool;

    /**
     * 代理ログインから元のユーザーに戻る
     * @param ServerRequest $request
     * @param ResponseInterface $response
     * @return array|mixed|string
     * @throws Exception
     */
    public function returnLoginUserFromAgent(ServerRequest $request, ResponseInterface $response);

    /**
     * ログイン情報をリロードする
     *
     * @param ServerRequest $request
     * @return bool
     */
    public function reload(ServerRequest $request);

    /**
     * ログインユーザー自身のIDか確認
     * @param int $id
     * @return mixed
     */
    public function isSelf(?int $id);

}
