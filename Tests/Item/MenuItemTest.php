<?php

namespace c33s\MenuBundle\Item\Test;

use c33s\MenuBundle\Item\MenuItem;

/**
 * MenuItemTest.
 *
 * @author Michael Hirschler <michael.vhirsch@gmail.com>
 */
class MenuItemTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var MenuItem
     */
    protected $SUT = null;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $menu = $this->getMockBuilder('c33s\\MenuBundle\\Menu\\Menu')->disableOriginalConstructor()->getMock();
        $this->SUT = new MenuItem('/route', array('title' => 'Route'), $menu);
    }

    public function testFetchTitle()
    {
        $this->assertSame('Route', $this->SUT->getTitle());
    }
}
