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

namespace BaserCore\Test\TestCase\Utility;

use App\Application;
use BaserCore\Service\UserServiceInterface;
use BaserCore\TestSuite\BcTestCase;
use BaserCore\Utility\BcContainerTrait;

/**
 * Class BcContainerTraitTest
 * @package BaserCore\Test\TestCase\Utility
 */
class BcContainerTraitTest extends BcTestCase
{

   /**
     * set up
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * test getService
     */
    public function testGetService()
    {
        $app = new Application(ROOT . '/config');
        $app->getContainer();
        $bcContainerTrait = new class { use BcContainerTrait; };
        $this->assertEquals('BaserCore\Service\UserService', get_class($bcContainerTrait->getService(UserServiceInterface::class)));
    }

}
