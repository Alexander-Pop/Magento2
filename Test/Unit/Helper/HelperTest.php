<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license   http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\Buckaroo\Test\Helper;

use \Mockery as m;
use TIG\Buckaroo\Test\BaseTest;
use TIG\Buckaroo\Helper\Data;

class HelperTest extends BaseTest
{
    /**
     * @var Data
     */
    protected $helper;

    public function setUp()
    {
        parent::setUp();

        $this->helper = $this->objectManagerHelper->getObject(Data::class);
    }

    public function testGetStatusCode()
    {
        $this->assertNull($this->helper->getStatusCode(''));

        foreach ($this->helper->getStatusCodes() as $name => $code) {
            $this->assertEquals($code, $this->helper->getStatusCode($name));
        }
    }

    public function testGetStatusByValue()
    {
        $this->assertNull($this->helper->getStatusByValue(''));

        foreach ($this->helper->getStatusCodes() as $name => $code) {
            $this->assertEquals($name, $this->helper->getStatusByValue($code));
        }
    }

    public function testGetStatusCodes()
    {
        $this->assertNotEquals(0, count($this->helper->getStatusCodes()));
    }
}