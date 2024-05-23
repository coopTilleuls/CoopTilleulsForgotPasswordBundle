<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace CoopTilleuls\ForgotPasswordBundle\Tests\Manager\Bridge;

use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\DoctrineManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class DoctrineManagerTest extends TestCase
{
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
        $this->registryMock = $this->createMock(Registry::class);
        $this->managerMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(EntityRepository::class);
        $this->objectMock = $this->createMock(\stdClass::class);

        $this->doctrineManager = new DoctrineManager($this->registryMock);
    }

    public function testFindOneBy(): void
    {
        $this->registryMock->expects($this->once())->method('getManagerForClass')->with('class')->willReturn($this->managerMock);
        $this->managerMock->expects($this->once())->method('getRepository')->with('class')->willReturn($this->repositoryMock);
        $this->repositoryMock->expects($this->once())->method('findOneBy')->with(['criteria'])->willReturn('foo');

        $this->assertEquals('foo', $this->doctrineManager->findOneBy('class', ['criteria']));
    }

    public function testPersist(): void
    {
        $this->registryMock->expects($this->once())->method('getManagerForClass')->with($this->objectMock::class)->willReturn($this->managerMock);
        $this->managerMock->expects($this->once())->method('persist')->with($this->objectMock);
        $this->managerMock->expects($this->once())->method('flush');

        $this->doctrineManager->persist($this->objectMock);
    }

    public function testRemove(): void
    {
        $this->registryMock->expects($this->once())->method('getManagerForClass')->with($this->objectMock::class)->willReturn($this->managerMock);
        $this->managerMock->expects($this->once())->method('remove')->with($this->objectMock);
        $this->managerMock->expects($this->once())->method('flush');

        $this->doctrineManager->remove($this->objectMock);
    }
}
