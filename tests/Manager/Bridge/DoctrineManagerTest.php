<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\ForgotPasswordBundle\Manager\Bridge;

use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\DoctrineManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Tests\ForgotPasswordBundle\ProphecyTrait;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class DoctrineManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var DoctrineManager
     */
    private $doctrineManager;
    private $registryMock;
    private $managerMock;
    private $repositoryMock;
    private $objectMock;

    protected function setUp(): void
    {
        $this->registryMock = $this->prophesize(Registry::class);
        $this->managerMock = $this->prophesize(EntityManagerInterface::class);
        $this->repositoryMock = $this->prophesize(EntityRepository::class);
        $this->objectMock = $this->prophesize(\stdClass::class);

        $this->doctrineManager = new DoctrineManager($this->registryMock->reveal());
    }

    public function testFindOneBy(): void
    {
        $this->registryMock->getManagerForClass('class')->willReturn($this->managerMock->reveal())->shouldBeCalledOnce();
        $this->managerMock->getRepository('class')->willReturn($this->repositoryMock->reveal())->shouldBeCalledOnce();
        $this->repositoryMock->findOneBy(['criteria'])->willReturn('foo')->shouldBeCalledOnce();

        $this->assertEquals('foo', $this->doctrineManager->findOneBy('class', ['criteria']));
    }

    public function testPersist(): void
    {
        $this->registryMock->getManagerForClass(\get_class($this->objectMock->reveal()))->willReturn($this->managerMock->reveal())->shouldBeCalledOnce();
        $this->managerMock->persist($this->objectMock->reveal())->shouldBeCalledOnce();
        $this->managerMock->flush()->shouldBeCalledOnce();

        $this->doctrineManager->persist($this->objectMock->reveal());
    }

    public function testRemove(): void
    {
        $this->registryMock->getManagerForClass(\get_class($this->objectMock->reveal()))->willReturn($this->managerMock->reveal())->shouldBeCalledOnce();
        $this->managerMock->remove($this->objectMock->reveal())->shouldBeCalledOnce();
        $this->managerMock->flush()->shouldBeCalledOnce();

        $this->doctrineManager->remove($this->objectMock->reveal());
    }
}
