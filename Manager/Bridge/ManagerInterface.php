<?php

namespace CoopTilleuls\ForgotPasswordBundle\Manager\Bridge;

interface ManagerInterface
{
    /**
     * @param string $class
     * @param array  $criteria
     *
     * @return null|mixed
     */
    public function findOneBy($class, array $criteria);

    /**
     * @param mixed $object
     */
    public function persist($object);

    /**
     * @param mixed $object
     */
    public function remove($object);
}
