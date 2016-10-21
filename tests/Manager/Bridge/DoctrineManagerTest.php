<?php

/*
 * This file is part of the forgot-password-bundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ForgotPasswordBundle\Manager\Bridge;

use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\DoctrineManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class DoctrineManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineManager
     */
    private $doctrineManager;
    private $registryMock;
    private $managerMock;
    private $repositoryMock;
    private $objectMock;

    protected function setUp()
    {
        $this->registryMock = $this->prophesize(ManagerRegistry::class);
        $this->managerMock = $this->prophesize(ObjectManager::class);
        $this->repositoryMock = $this->prophesize(ObjectRepository::class);
        $this->objectMock = $this->prophesize(\stdClass::class);

        $this->doctrineManager = new DoctrineManager($this->registryMock->reveal());
    }

    public function testFindOneBy()
    {
        $this->registryMock->getManagerForClass('class')->willReturn($this->managerMock->reveal())->shouldBeCalledTimes(1);
        $this->managerMock->getRepository('class')->willReturn($this->repositoryMock->reveal())->shouldBeCalledTimes(1);
        $this->repositoryMock->findOneBy(['criteria'])->willReturn('foo')->shouldBeCalledTimes(1);;

        $this->assertEquals('foo', $this->doctrineManager->findOneBy('class', ['criteria']));
    }

    public function testPersist()
    {
        $this->registryMock->getManagerForClass(get_class($this->objectMock->reveal()))->willReturn($this->managerMock->reveal())->shouldBeCalledTimes(1);
        $this->managerMock->persist($this->objectMock->reveal())->shouldBeCalledTimes(1);
        $this->managerMock->flush()->shouldBeCalledTimes(1);

        $this->doctrineManager->persist($this->objectMock->reveal());
    }

    public function testRemove()
    {
        $this->registryMock->getManagerForClass(get_class($this->objectMock->reveal()))->willReturn($this->managerMock->reveal())->shouldBeCalledTimes(1);
        $this->managerMock->remove($this->objectMock->reveal())->shouldBeCalledTimes(1);
        $this->managerMock->flush()->shouldBeCalledTimes(1);

        $this->doctrineManager->remove($this->objectMock->reveal());
    }
}
