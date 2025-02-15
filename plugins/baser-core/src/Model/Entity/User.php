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

namespace BaserCore\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\I18n\Time as TimeAlias;
use Cake\ORM\Entity as EntityAlias;
use BaserCore\Annotation\UnitTest;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;
use Cake\Utility\Hash;

/**
 * Class User
 * @package BaserCore\Model\Entity
 * @property int $id
 * @property string $name
 * @property string $password
 * @property string $real_name_1
 * @property string $real_name_2
 * @property string $email
 * @property string $nickname
 * @property TimeAlias $created
 * @property TimeAlias $modified
 */
class User extends EntityAlias
{

    /**
     * Accessible
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];

    /**
     * Hidden
     *
     * @var array
     */
    protected $_hidden = [
        'password'
    ];

    /**
     * Set Password
     *
     * @param $value
     * @return bool|string
     * @checked
     * @noTodo
     * @unitTest
     */
    protected function _setPassword($value)
    {
        if ($value) {
            $hasher = new DefaultPasswordHasher();
            return $hasher->hash($value);
        } else {
            return false;
        }
    }

    /**
     * 管理ユーザーかどうか判定する
     * @return bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function isAdmin()
    {
        if (empty($this->user_groups)) {
            return false;
        }
        $userGroupId = Hash::extract($this->user_groups, '{n}.id');
        return in_array(Configure::read('BcApp.adminGroupId'), $userGroupId);
    }

    /**
     * 整形されたユーザー名を取得する
     * @return string
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getDisplayName()
    {
        if (!empty($this->nickname)) {
            return $this->nickname;
        }
        $userName = [];
        if (!empty($this->real_name_1)) {
            $userName[] = $this->real_name_1;
        }
        if (!empty($this->real_name_2)) {
            $userName[] = $this->real_name_2;
        }
        $userName = implode(' ', $userName);
        return $userName;
    }

}
