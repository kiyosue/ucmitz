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

/**
 * コンテンツ更新情報
 * @var \BaserCore\View\BcAdminAppView $this
 */
?>

<?php if (!$this->BcBaser->isHome()): ?>
	<div class="update-info clearfix">
		<dl>
			<?php if ($createdDate): ?>
				<dt><?php echo __d('baser', '作成日') ?></dt>
				<dd><?php echo $createdDate ?></dd>
			<?php endif ?>
			<?php if ($modifiedDate): ?>
				<dt><?php echo __d('baser', '最終更新日') ?></dt>
				<dd><?php echo $modifiedDate ?></dd>
			<?php endif ?>
		</dl>
	</div>
<?php endif ?>
