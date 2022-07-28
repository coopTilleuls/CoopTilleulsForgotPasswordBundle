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

namespace CoopTilleuls\ForgotPasswordBundle\Manager\Bridge;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class DoctrineManager implements ManagerInterface
{
    private $registry;

    /**
     * @var \Doctrine\Common\Persistence\ManagerRegistry|\Doctrine\Persistence\ManagerRegistry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    public function findOneBy($class, array $criteria)
    {
        return $this->registry->getManagerForClass($class)
            ->getRepository($class)
            ->findOneBy($criteria);
    }

    public function persist($object): void
    {
        $manager = $this->registry->getManagerForClass(\get_class($object));
        $manager->persist($object);
        $manager->flush();
    }

    public function remove($object): void
    {
        $manager = $this->registry->getManagerForClass(\get_class($object));
        $manager->remove($object);
        $manager->flush();
    }
}
