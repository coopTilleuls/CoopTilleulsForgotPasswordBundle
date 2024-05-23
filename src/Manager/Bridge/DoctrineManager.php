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

namespace CoopTilleuls\ForgotPasswordBundle\Manager\Bridge;

use Doctrine\Common\Persistence\ManagerRegistry as LegacyManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class DoctrineManager implements ManagerInterface
{
    public function __construct(private readonly LegacyManagerRegistry|ManagerRegistry $registry)
    {
    }

    public function findOneBy($class, array $criteria)
    {
        return $this->registry->getManagerForClass($class)
            ->getRepository($class)
            ->findOneBy($criteria);
    }

    public function persist($object): void
    {
        $manager = $this->registry->getManagerForClass($object::class);
        $manager->persist($object);
        $manager->flush();
    }

    public function remove($object): void
    {
        $manager = $this->registry->getManagerForClass($object::class);
        $manager->remove($object);
        $manager->flush();
    }
}
