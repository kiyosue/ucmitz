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

use BaserCore\Utility\BcUtil;
use Cake\Core\Configure;
use BaserCore\Annotation\UnitTest;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;
use BaserCore\Annotation\Note;

/**
 * BcAdminSiteConfigHelper
 */
class BcAdminSiteConfigHelper extends BcSiteConfigHelper
{
    /**
     * .env が書き込み可能かどうか
     * @return bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function isWritableEnv()
    {
        return $this->SiteConfigService->isWritableEnv();
    }

    /**
     * 管理画面テーマリストを取得
     * @return array
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getAdminThemeList()
    {
        return BcUtil::getAdminThemeList();
    }

    /**
     * ウィジェットエリアリストを取得
     * @return array
     * @checked
     * @unitTest
     * @note(value="ウィジェットエリアを実装後に対応")
     */
    public function getWidgetAreaList()
    {
        // TODO ucmitz 未実装のため代替措置
        // >>>
        //$this->BcAdminForm->getControlSource('WidgetArea.id'), 'empty' => __d('baser', 'なし')]
        // ---
        return [];
        // <<<
    }

    /**
     * エディタリストを取得
     * @return array|false[]|mixed
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getEditorList()
    {
        return Configure::read('BcApp.editors');
    }

    /**
     * メールエンコードリストを取得
     * @return array|false[]|mixed
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getMailEncodeList()
    {
        return Configure::read('BcEncode.mail');
    }

    /**
     * アプリケーションモードリストを取得
     * @return array
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getModeList()
    {
        return $this->SiteConfigService->getModeList();
    }

}
