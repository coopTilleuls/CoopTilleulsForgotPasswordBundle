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
interface ManagerInterface
{
    /**
     * @param string $class
     *
     * @return mixed|null
     */
    public function findOneBy($class, array $criteria);

    public function persist($object);

    public function remove($object);
}
