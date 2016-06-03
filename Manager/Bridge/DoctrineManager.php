<?php

namespace CoopTilleuls\ForgotPasswordBundle\Manager\Bridge;

use Doctrine\Common\Persistence\ManagerRegistry;

class DoctrineManager implements ManagerInterface
{
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy($class, array $criteria)
    {
        return $this->registry->getManagerForClass($class)
            ->getRepository($class)
            ->findOneBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($object)
    {
        $manager = $this->registry->getManagerForClass(get_class($object));
        $manager->persist($object);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($object)
    {
        $manager = $this->registry->getManagerForClass(get_class($object));
        $manager->remove($object);
        $manager->flush();
    }
}
