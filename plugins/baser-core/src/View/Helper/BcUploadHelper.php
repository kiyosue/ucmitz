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
use Cake\Utility\Inflector;
use BaserCore\Utility\BcUtil;
use BaserCore\Annotation\NoTodo;
use BaserCore\Error\BcException;
use BaserCore\Annotation\Checked;
use BaserCore\Annotation\UnitTest;
use BaserCore\Utility\BcContainerTrait;
use BaserCore\Event\BcEventDispatcherTrait;
use BaserCore\Service\SiteConfigServiceInterface;

/**
 * アップロードヘルパー
 *
 * @package Baser.View.Helper
 * @property HtmlHelper $Html
 * @property SiteConfigServiceInterface $siteConfigService
 */
class BcUploadHelper  extends Helper
{
    /**
     * Trait
     */
    use BcEventDispatcherTrait;
    use BcContainerTrait;

    /**
     * ヘルパ
     *
     * @var array
     */
    public $helpers = ['Html', 'BcAdminForm'];

    /**
     * BcUploadHelperで使用するテーブル
     * initFieldにて$fieldNameに基づき設定
     *
     * @var Table
     */
    public $table;

    /**
     * initialize
     *
     * @param  array $config
     * @return void
     * @checked
     * @noTodo
     * @unitTest
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->siteConfigService = $this->getService(SiteConfigServiceInterface::class);
    }

    /**
     * ファイルへのリンクを取得する
     *
     * @param string $fieldName
     * @param array $options
     * @return string
     * @checked
     * @noTodo
     * @unitTest
     */
    public function fileLink($fieldName, $options = [])
    {
        $options = array_merge([
            'imgsize' => 'medium', // 画像サイズ
            'rel' => '', // rel属性
            'title' => '', // タイトル属性
            'link' => true, // 大きいサイズの画像へのリンク有無
            'force' => false,
            'width' => '', // 横幅
            'height' => '', // 高さ
            'figure' => null,
            'img' => ['class' => ''],
            'figcaption' => null
        ], $options);

        if (strpos($fieldName, '.') === false) {
            throw new BcException(__d('baser', 'BcUploadHelper を利用するには、$fieldName に、モデル名とフィールド名をドットで区切って指定する必要があります。'));
        }
        $field = $this->initField($fieldName);

        $tmp = false;

        try {
            $settings = $this->getBcUploadSetting();
        } catch (BcException $e) {
            throw $e;
        }

        // EVENT BcUpload.beforeFileLInk
        $event = $this->dispatchLayerEvent('beforeFileLink', [
            'formId' => $this->__id,
            'settings' => $settings,
            'fieldName' => $fieldName,
            'options' => $options
        ], ['class' => 'BcUpload', 'plugin' => '']);
        if ($event !== false) {
            $options = ($event->getResult() === null || $event->getResult() === true)? $event->getData('options') : $event->getResult();
            $settings = $event->getData('settings');
        }

        $this->setBcUploadSetting($settings);

        $basePath = '/files/' . str_replace(DS, '/', $settings['saveDir']) . '/';

        if (empty($options['value'])) {
            $value = $this->BcAdminForm->getSourceValue($fieldName);
        } else {
            $value = $options['value'];
        }

        $sessionKey = $this->BcAdminForm->getSourceValue($fieldName . '_tmp');
        if ($sessionKey) {
            $tmp = true;
            $value = str_replace('/', '_', $sessionKey);
            $basePath = '/uploads/tmp/';
        }

        /* ファイルのパスを取得 */
        /* 画像の場合はサイズを指定する */
        if (isset($settings['saveDir'])) {
            if ($value && !is_array($value)) {
                $uploadSettings = $settings['fields'][$field];
                $ext = BcUtil::decodeContent('', $value);
                $figureOptions = $figcaptionOptions = [];
                if (!empty($options['figcaption'])) {
                    $figcaptionOptions = $options['figcaption'];
                }
                if (!empty($options['figure'])) {
                    $figureOptions = $options['figure'];
                }
                if (!empty($figcaptionOptions['class'])) {
                    $figcaptionOptions['class'] .= ' file-name';
                } else {
                    $figcaptionOptions['class'] = 'file-name';
                }
                if ($uploadSettings['type'] == 'image' || in_array($ext, $this->table->getBehavior('BcUpload')->BcFileUploader[$this->table->getAlias()]->imgExts)) {
                    $imgOptions = array_merge([
                        'imgsize' => $options['imgsize'],
                        'rel' => $options['rel'],
                        'title' => $options['title'],
                        'link' => $options['link'],
                        'force' => $options['force'],
                        'width' => $options['width'], // 横幅
                        'height' => $options['height'] // 高さ
                    ], $options['img']);
                    if ($tmp) {
                        $imgOptions['tmp'] = true;
                    }
                    $out = $this->Html->tag('figure', $this->uploadImage($fieldName, $value, $imgOptions) . '<br>' . $this->Html->tag('figcaption', BcUtil::mb_basename($value), $figcaptionOptions), $figureOptions);
                } else {
                    $filePath = $basePath . $value;
                    $linkOptions = ['target' => '_blank'];
                    if (is_array($options['link'])) {
                        $linkOptions = array_merge($linkOptions, $options['link']);
                    }
                    $out = $this->Html->tag('figure', $this->Html->link(__d('baser', 'ダウンロード') . ' ≫', $filePath, $linkOptions) . '<br>' . $this->Html->tag('figcaption', BcUtil::mb_basename($value), $figcaptionOptions), $figureOptions);
                }
            } else {
                $out = $value;
            }
        } else {
            $out = false;
        }

        // EVENT BcUpload.afterFileLink
        $event = $this->dispatchLayerEvent('afterFileLink', [
            'data' => $this->getView()->getRequest()->getData(),
            'fieldName' => $fieldName,
            'out' => $out
        ], ['class' => 'BcUpload', 'plugin' => '']);
        if ($event !== false) {
            $out = ($event->getResult() === null || $event->getResult() === true)? $event->getData('out') : $event->getResult();
        }

        return $out;
    }

    /**
     * アップロードした画像のタグをリンク付きで出力する
     * Uploadビヘイビアの設定による
     * 上から順に大きい画像を並べている事が前提で
     * 指定したサイズ内で最大の画像を出力
     * リンク先は存在する最大の画像へのリンクとなる
     *
     * @param string $fieldName
     * @param string $fileName
     * @param array $options
     * @return string
     * @checked
     * @unitTest
     * @noTodo
     */
    public function uploadImage($fieldName, $fileName, $options = [])
    {
        $options = array_merge([
            'imgsize' => 'medium', // 画像サイズ
            'escape' => false, // エスケープ
            'mobile' => false, // モバイル
            'alt' => '', // alt属性
            'width' => '', // 横幅
            'height' => '', // 高さ
            'noimage' => '', // 画像がなかった場合に表示する画像
            'tmp' => false,
            'force' => false,
            'output' => '', // 出力タイプ tag ,url を指定、未指定(or false)の場合は、tagで出力(互換性のため)
            'limited' => false,  // 公開制限フォルダを利用する場合にフォルダ名を設定する
            'link' => true, // 大きいサイズの画像へのリンク有無
            'img' => null,
            'class' => ''
        ], $options);

        if (strpos($fieldName, '.') === false) {
			throw new BcException(__d('baser', 'BcUploadHelper を利用するには、$fieldName に、モデル名とフィールド名をドットで区切って指定する必要があります。'));
		}
        $field = $this->initField($fieldName);
        try {
            $settings = $this->getBcUploadSetting();
        } catch (BcException $e) {
            throw $e;
        }

        // EVENT BcUpload.beforeUploadImage
        $event = $this->dispatchLayerEvent('beforeUploadImage', [
            'formId' => $this->__id,
            'settings' => $settings,
            'fieldName' => $fieldName,
            'options' => $options
        ], ['class' => 'BcUpload', 'plugin' => '']);
        if ($event !== false) {
            $options = ($event->getResult() === null || $event->getResult() === true)? $event->getData('options') : $event->getResult();
            $settings = $event->getData('settings');
        }

        $this->setBcUploadSetting($settings);

        $imgOptions = [
            'alt' => $options['alt'],
            'width' => $options['width'],
            'height' => $options['height'],
            'class' => $options['class']
        ];
        if (empty($imgOptions['class'])) {
            unset($imgOptions['class']);
        }
        if ($imgOptions['width'] === '') {
            unset($imgOptions['width']);
        }
        if ($imgOptions['height'] === '') {
            unset($imgOptions['height']);
        }
        $linkOptions = [
            'rel' => 'colorbox',
            'escape' => $options['escape']
        ];
        if (!empty($options['link']) && is_array($options['link'])) {
            $linkOptions = array_merge($linkOptions, $options['link']);
        }
        if (empty($linkOptions['class'])) {
            unset($linkOptions['class']);
        }

        $sessionKey = $this->BcAdminForm->getSourceValue($fieldName . '_tmp');
        if ($sessionKey) {
            $fileName = $sessionKey;
            $options['tmp'] = true;
        }

        if ($options['noimage']) {
            if (!$fileName) {
                $fileName = $options['noimage'];
            }
        } else {
            if (!$fileName) {
                return '';
            }
        }

        if (strpos($fieldName, '.') === false) {
            trigger_error(__d('baser', 'フィールド名は、 ModelName.field_name で指定してください。'), E_USER_WARNING);
            return false;
        }

        $fileUrl = '/files/' . str_replace(DS, '/', $settings['saveDir']) . '/';
        $saveDir = $this->table->getSaveDir(false, $options['limited']);

        if (isset($settings['fields'][$field]['imagecopy'])) {
            $copySettings = $settings['fields'][$field]['imagecopy'];
        } else {
            $copySettings = "";
        }

        if (!$options['imgsize']) {
            $options['imgsize'] = 'default';
        }
        if ($options['tmp']) {
            $options['link'] = false;
            $fileUrl = '/uploads/tmp/';
            if ($options['imgsize']) {
                $fileUrl .= $options['imgsize'] . '/';
            }
        }

        if ($fileName == $options['noimage']) {
            $mostSizeUrl = $fileName;
        } elseif ($options['tmp']) {
            $mostSizeUrl = $fileUrl . str_replace(['.', '/'], ['_', '_'], $fileName);
        } else {
            $check = false;
            $maxSizeExists = false;
            $mostSizeExists = false;

            if ($copySettings && ($options['imgsize'] != 'default')) {

                foreach($copySettings as $key => $copySetting) {

                    if ($key == $options['imgsize']) {
                        $check = true;
                    }

                    if (isset($copySetting['mobile'])) {
                        if ($copySetting['mobile'] != $options['mobile']) {
                            continue;
                        }
                    } else {
                        if ($options['mobile'] != preg_match('/^mobile_/', $key)) {
                            continue;
                        }
                    }

                    $imgPrefix = '';
                    $imgSuffix = '';

                    if (isset($copySetting['suffix'])) {
                        $imgSuffix = $copySetting['suffix'];
                    }
                    if (isset($copySetting['prefix'])) {
                        $imgPrefix = $copySetting['prefix'];
                    }

                    $pathinfo = pathinfo($fileName);
                    $ext = $pathinfo['extension'];
                    $basename = basename($fileName, '.' . $ext);

                    $subdir = str_replace($basename . '.' . $ext, '', $fileName);
                    $file = str_replace('/', DS, $subdir) . $imgPrefix . $basename . $imgSuffix . '.' . $ext;

                    $fileExists = false;
                    if (file_exists($saveDir . $file)) {
                        $fileExists = true;
                    }

                    if ($fileExists || $options['force']) {
                        if ($check && !$mostSizeExists) {
                            $mostSizeUrl = $fileUrl . $subdir . $imgPrefix . $basename . $imgSuffix . '.' . $ext . '?' . rand();
                            $mostSizeExists = true;
                        } elseif (!$mostSizeExists && !$maxSizeExists) {
                            $maxSizeUrl = $fileUrl . $subdir . $imgPrefix . $basename . $imgSuffix . '.' . $ext . '?' . rand();
                            $maxSizeExists = true;
                        }
                    }
                }
            }

            if (!isset($mostSizeUrl)) {
                $mostSizeUrl = $fileUrl . $fileName . '?' . rand();
            }
            if (!isset($maxSizeUrl)) {
                $maxSizeUrl = $fileUrl . $fileName . '?' . rand();
            }
        }

        $output = $options['output'];
        $link = $options['link'];
        $noimage = $options['noimage'];
        unset($options['imgsize']);
        unset($options['link']);
        unset($options['escape']);
        unset($options['mobile']);
        unset($options['alt']);
        unset($options['width']);
        unset($options['height']);
        unset($options['noimage']);
        unset($options['tmp']);
        unset($options['force']);
        unset($options['output']);
        unset($options['class']);

        switch($output) {
            case 'url' :
                $out = $mostSizeUrl;
                break;
            case 'tag' :
                $out = $this->Html->image($mostSizeUrl, array_merge($options, $imgOptions));
                break;
            default :
                if ($link && !($noimage == $fileName)) {
                    $out = $this->Html->link($this->Html->image($mostSizeUrl, $imgOptions), $maxSizeUrl, array_merge($options, $linkOptions));
                } else {
                    $out = $this->Html->image($mostSizeUrl, array_merge($options, $imgOptions));
                }
        }

        // EVENT BcUpload.afterUploadImage
        $event = $this->dispatchLayerEvent('afterUploadImage', [
            'data' => $this->getView()->getRequest()->getData(),
            'fieldName' => $fieldName,
            'out' => $out
        ], ['class' => 'BcUpload', 'plugin' => '']);
        if ($event !== false) {
            $out = ($event->getResult() === null || $event->getResult() === true)? $event->getData('out') : $event->getResult();
        }
        return $out;
    }

    /**
     * アップロードの設定を取得する
     *
     * @return array
     * @checked
     * @noTodo
     * @unitTest
     */
    protected function getBcUploadSetting()
    {
        return $this->table->getSettings();
    }

    /**
     * setBcUploadSetting
     *
     * @param  mixed $settings
     * @return void
     * @checked
     * @noTodo
     * @unitTest
     */
    protected function setBcUploadSetting($settings)
    {
        $this->table->setSettings($settings);
    }

    /**
     * initField
     *
     * @param  string $fieldName
     * @return string $field
     * @checked
     * @noTodo
     * @unitTest
     */
    protected function initField($fieldName)
    {
        $targetField = explode('.', $fieldName);
        $field = array_pop($targetField);
        while (true) {
            $table = array_pop($targetField);
            if (!is_numeric($table)) break;
        }
        if (is_null($this->table)) {
            $this->table = TableRegistry::getTableLocator()->get('BaserCore.' . Inflector::pluralize($table));
        }
        if (!$this->table->hasBehavior('BcUpload')) {
            throw new BcException(__d('baser', 'BcUploadHelper を利用するには、モデルで BcUploadBehavior の利用設定が必要です。'));
        }
        return $field;
    }
}
