<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Tests\Action;

use Thelia\Action\Hook;
use Thelia\Core\Event\Hook\HookCreateAllEvent;
use Thelia\Core\Event\Hook\HookDeactivationEvent;
use Thelia\Core\Event\Hook\HookDeleteEvent;
use Thelia\Core\Event\Hook\HookToggleActivationEvent;
use Thelia\Core\Event\Hook\HookUpdateEvent;
use Thelia\Model\Hook as HookModel;
use Thelia\Core\Event\Hook\HookCreateEvent;
use Thelia\Core\Template\TemplateDefinition;
use Thelia\Model\LangQuery;


/**
 * Class HookTest
 * @package Thelia\Tests\Action
 * @author Julien Chanséaume <jchanseaume@openstudio.fr>
 */
class HookTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface */
    protected $dispatcher;

    /** @var Hook $action */
    protected $action;

    protected $locale;

    public function setUp()
    {

        $this->locale = LangQuery::create()->findOne()->getLocale();

        $this->dispatcher = $this->getMock('\Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $stubContainer = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action = new Hook($stubContainer);
    }

    public function testCreate()
    {

        $event = new HookCreateEvent();
        $event
            ->setCode('test.hook-' . uniqid())
            ->setType(TemplateDefinition::FRONT_OFFICE)
            ->setLocale($this->locale)
            ->setActive(true)
            ->setNative(true)
            ->setTitle("Hook Test")
            ->setDispatcher($this->dispatcher);

        $this->action->create($event);

        $createdHook = $event->getHook();

        $this->assertInstanceOf('\Thelia\Model\Hook', $createdHook);
        $this->assertFalse($createdHook->isNew());
        $this->assertTrue($event->hasHook());

        $this->assertEquals($event->getCode(), $createdHook->getCode());
        $this->assertEquals($event->getType(), $createdHook->getType());
        $this->assertEquals($event->getLocale(), $createdHook->getLocale());
        $this->assertEquals($event->getActive(), $createdHook->getActivate());
        $this->assertEquals($event->getNative(), $createdHook->getNative());
        $this->assertEquals($event->getTitle(), $createdHook->getTitle());

        return $createdHook;

    }

    public function createAll(HookCreateAllEvent $event)
    {
        $event = new HookCreateAllEvent();
        $event
            ->setCode('test.hook-' . uniqid())
            ->setType(TemplateDefinition::FRONT_OFFICE)
            ->setLocale($this->locale)
            ->setActive(true)
            ->setNative(true)
            ->setTitle("Hook Test")
            ->setDescription("Hook Description")
            ->setChapo("Hook Chapo")
            ->setBlock(false)
            ->setByModule(false)
            ->setDispatcher($this->dispatcher);

        $this->action->createAll($event);

        $createdHook = $event->getHook();

        $this->assertInstanceOf('\Thelia\Model\Hook', $createdHook);
        $this->assertFalse($createdHook->isNew());
        $this->assertTrue($event->hasHook());

        $this->assertEquals($event->getCode(), $createdHook->getCode());
        $this->assertEquals($event->getType(), $createdHook->getType());
        $this->assertEquals($event->getLocale(), $createdHook->getLocale());
        $this->assertEquals($event->getActive(), $createdHook->getActivate());
        $this->assertEquals($event->getNative(), $createdHook->getNative());
        $this->assertEquals($event->getTitle(), $createdHook->getTitle());
        $this->assertEquals($event->getDescription(), $createdHook->getDescription());
        $this->assertEquals($event->getChapo(), $createdHook->getChapo());
        $this->assertEquals($event->getBlock(), $createdHook->getBlock());
        $this->assertEquals($event->getByModule(), $createdHook->getByModule());
    }

    /**
     * @params HookModel $hook
     * @depends testCreate
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testCreateDuplicate(HookModel $hook)
    {

        $event = new HookCreateEvent();
        $event
            ->setCode($hook->getCode())
            ->setType(TemplateDefinition::FRONT_OFFICE)
            ->setLocale($this->locale)
            ->setActive(true)
            ->setNative(true)
            ->setTitle("Hook Test")
            ->setDispatcher($this->dispatcher);

        $this->action->create($event);

        $createdHook = $event->getHook();

        $this->assertNull($createdHook);
        $this->assertFalse($event->hasHook());

    }

    /**
     * @params HookModel $hook
     * @depends testCreate
     */
    public function testDeactivation(HookModel $hook)
    {
        $event = new HookDeactivationEvent($hook->getId());
        $event->setDispatcher($this->dispatcher);

        $this->action->deactivation($event);
        $updatedHook = $event->getHook();

        $this->assertFalse($updatedHook->getActivate());

        return $hook;
    }

    /**
     * @params HookModel $hook
     * @depends testDeactivation
     */
    public function testToggleActivation(HookModel $hook)
    {
        $event = new HookToggleActivationEvent($hook->getId());
        $event->setDispatcher($this->dispatcher);

        $this->action->toggleActivation($event);
        $updatedHook = $event->getHook();

        $this->assertTrue($updatedHook->getActivate());

        return $hook;
    }

    /**
     * @params HookModel $hook
     * @depends testToggleActivation
     */
    public function testUpdate(HookModel $hook)
    {
        $event = new HookUpdateEvent($hook->getId());

        $event
            ->setCode('test.hook.' . uniqid())
            ->setType(TemplateDefinition::FRONT_OFFICE)
            ->setLocale($this->locale)
            ->setActive(false)
            ->setNative(false)
            ->setTitle("Updated Hook Test")
            ->setDescription("Updated Hook Description")
            ->setChapo("Updated Hook Chapo")
            ->setBlock(false)
            ->setByModule(false)
            ->setDispatcher($this->dispatcher);

        $this->action->update($event);

        $updatedHook = $event->getHook();

        $this->assertEquals($event->getCode(), $updatedHook->getCode());
        $this->assertEquals($event->getType(), $updatedHook->getType());
        $this->assertEquals($event->getLocale(), $updatedHook->getLocale());
        $this->assertEquals($event->getActive(), $updatedHook->getActivate());
        $this->assertEquals($event->getNative(), $updatedHook->getNative());
        $this->assertEquals($event->getTitle(), $updatedHook->getTitle());
        $this->assertEquals($event->getDescription(), $updatedHook->getDescription());
        $this->assertEquals($event->getChapo(), $updatedHook->getChapo());
        $this->assertEquals($event->getBlock(), $updatedHook->getBlock());
        $this->assertEquals($event->getByModule(), $updatedHook->getByModule());

        return $updatedHook;
    }

    /**
     * @params HookModel $hook
     * @depends testUpdate
     */
    public function testDelete(HookModel $hook)
    {
        $event = new HookDeleteEvent($hook->getId());

        $event->setDispatcher($this->dispatcher);

        $hookAction = $this->action->delete($event);

        $deletedHook = $event->getHook();

        $this->assertInstanceOf('Thelia\Model\Hook', $deletedHook);
        $this->assertTrue($deletedHook->isDeleted());
    }


} 