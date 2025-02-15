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

namespace BaserCore\Test\TestCase\Model\Table;

use ArrayObject;
use BaserCore\Model\Entity\Page;
use Cake\Validation\Validator;
use BaserCore\TestSuite\BcTestCase;

/**
 * Class PagesTable Test
 *
 * @package Baser.Test.Case.Model
 * @property Page $Page
 */
class PagesTableTest extends BcTestCase
{

    public $fixtures = [
        // 'baser.Model.Content.ContentStatusCheck',
        // 'plugin.BaserCore.BlogContents',
        // 'plugin.BaserCore.BlogCategorys',
        // 'plugin.BaserCore.BlogPosts',
        // 'plugin.BaserCore.BlogPostsBlogTags',
        // 'plugin.BaserCore.BlogTags',
        'plugin.BaserCore.SiteConfigs',
        // 'baser.Model.Page.PagePageModel',
        'plugin.BaserCore.Permissions',
        'plugin.BaserCore.Plugins',
        'plugin.BaserCore.Users',
        'plugin.BaserCore.UsersUserGroups',
        'plugin.BaserCore.UserGroups',
        'plugin.BaserCore.Sites',
        'plugin.BaserCore.Contents',
        'plugin.BaserCore.ContentFolders',
        'plugin.BaserCore.Pages',
        'plugin.BaserCore.SearchIndexes',
        // 'plugin.BaserCore.Favorites'
    ];

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Pages') ? [] : ['className' => 'BaserCore\Model\Table\PagesTable'];
        $this->Pages = $this->getTableLocator()->get('Pages', $config);
        $this->SearchIndexes = $this->getTableLocator()->get('SearchIndexes');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Pages, $this->SearchIndexes);
        parent::tearDown();
    }

    /**
     * testInitialize
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->assertTrue($this->Pages->hasBehavior('BcContents'));
        $this->assertTrue($this->Pages->hasBehavior('BcSearchIndexManager'));
        $this->assertTrue($this->Pages->hasBehavior('Timestamp'));
    }

    /**
     * testValidationDefault
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $validator = $this->Pages->validationDefault(new Validator());
        $fields = [];
        foreach($validator->getIterator() as $key => $value) {
            $fields[] = $key;
        }
        $this->assertEquals(['id','contents', 'draft'], $fields);
    }

    public function test既存ページチェック正常()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $this->Pages->create([
            'Page' => [
                'name' => 'test',
                'page_category_id' => '1',
            ]
        ]);
        $this->assertTrue($this->Pages->validates());
    }

    /**
     * スクリプトがが埋め込まれているかチェックする
     * - 管理グループの場合は無条件に true を返却
     * - 管理グループ以外の場合に許可されている場合は無条件に true を返却
     * @param array $check
     * @param bool $expected
     * @dataProvider cotainsScriptRegularDataProvider
     */
    public function testCotainsScriptRegular($check, $expected)
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $allowedPhpOtherThanAdmins = Configure::read('BcApp.allowedPhpOtherThanAdmins');
        Configure::write('BcApp.allowedPhpOtherThanAdmins', false);
        $this->Pages->create([
            'Page' => [
                'name' => 'test',
                'contents' => $check,
            ]
        ]);
        $this->assertEquals($expected, $this->Pages->validates());
        Configure::write('BcApp.allowedPhpOtherThanAdmins', $allowedPhpOtherThanAdmins);
    }

    public function cotainsScriptRegularDataProvider()
    {
        return [
            ['<?php echo "正しい"; ?>', false],
            ['<?PHP echo "正しい"; ?>', false],
            ['<script></script>', false],
            ['<a onclick="alert(\'test\')>"', false],
            ['<img onMouseOver="">', false],
            ['<a href="javascript:alert(\'test\')">', false],
            ['<a href=\'javascript:alert("test")\'>', false],
            ['<a href="https://basercms.net">baserCMS<\/a>', true]
        ];
    }

    public function testCotainsScriptIrregular()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $this->Pages->create([
            'Page' => [
                'name' => 'test',
                'contents' => '<?php ??>',
            ]
        ]);
        $this->assertFalse($this->Pages->validates());
        $this->assertArrayHasKey('contents', $this->Pages->validationErrors);
        $this->assertEquals("PHPの構文エラーです： \nPHP Parse error:  syntax error, unexpected '?' in - on line 1 \nErrors parsing -", current($this->Pages->validationErrors['contents']));
    }

    /**
     * afterSave
     *
     * @param boolean $created
     * @param array $options
     * @dataProvider afterSaveDataProvider
     */
    public function testAfterSave($exclude_search, $exist)
    {
        $page = $this->Pages->find()->contain(['Contents' => ['Sites']])->where(['Pages.id' => 5])->first();
        if ($exclude_search) {
            $page->content->exclude_search = $exclude_search;
            $this->assertEquals(false, $this->SearchIndexes->findByModelId($page->id)->isEmpty());
        } else {
            $page->id = 100; // 存在しない新規のIDを入れた場合
        }
        $this->Pages->dispatchEvent('Model.afterSave', [$page, new ArrayObject()]);
        $this->assertEquals($exist, $this->SearchIndexes->findByModelId($page->id)->isEmpty());
    }

    public function afterSaveDataProvider()
    {
        return [
            // exclude_searchがある場合削除されているかを確認
            [1, true],
            // exclude_searchがなく、なおかつ新規の場合索引が作成されて存在するかをテスト
            [0, false]
        ];
    }

    /**
     * 検索用データを生成する
     */
    public function testCreateSearchIndex()
    {
        $page = $this->Pages->find()->contain(['Contents' => ['Sites']])->first();
        $expected = [
            'model_id' => $page->id,
            'type' => 'ページ',
            'content_id' => $page->content->id,
            'title' => $page->content->title,
            'detail' => $page->content->description . ' ' . $page->contents,
            'url' => $page->content->url,
            'status' => $page->content->status,
            'site_id' => $page->content->site_id,
            'publish_begin' => $page->content->publish_begin ?? '',
            'publish_end' => $page->content->publish_end ?? '',
        ];
        $result = $this->Pages->createSearchIndex($page);
        $this->assertEquals($expected, $result, '検索用データを正しく生成できません');
    }

            /**
     * ページデータをコピーする
     *
     * @param array $postData 送信するデータ
     * @dataProvider copyDataProvider
     */
    public function testCopy($id, $newParentId, $newTitle, $newAuthorId, $newSiteId)
    {
        $this->loginAdmin($this->getRequest());
        $result = $this->Pages->copy($id, $newParentId, $newTitle, $newAuthorId, $newSiteId);
        $page = $this->Pages->get($result->id, ['contain' => ['Contents' => ['Sites']]]);
        $this->assertStringContainsString("_2", $page->content->name);
        $this->assertEquals("hoge1", $page->content->title);
        $this->assertEquals(10, $page->content->author_id);
    }

    public function copyDataProvider()
    {
        return [
            [2, 1, 'hoge1', 10, 1]
        ];
    }
}
