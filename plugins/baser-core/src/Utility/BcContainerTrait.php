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

namespace BaserCore\Utility;

/**
 * Trait BcContainerTrait
 * @package BaserCore\Utility
 */
trait BcContainerTrait
{
    /**
     * Get Service
     * @param $service
     * @return array|mixed|object
     */
    public function getService($service)
    {
        return BcContainer::get()->get($service);
    }

}
