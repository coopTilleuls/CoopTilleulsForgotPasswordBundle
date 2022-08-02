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

namespace CoopTilleuls\ForgotPasswordBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class RouteLoader extends Loader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->add(
            'coop_tilleuls_forgot_password.reset',
            (new Route('/', [
                '_controller' => 'coop_tilleuls_forgot_password.controller.reset_password',
            ]))->setMethods('POST')
        );
        $collection->add(
            'coop_tilleuls_forgot_password.update',
            (new Route('/{tokenValue}', [
                '_controller' => 'coop_tilleuls_forgot_password.controller.update_password',
            ]))->setMethods('POST')
        );
        $collection->add(
            'coop_tilleuls_forgot_password.get_token',
            (new Route('/{tokenValue}', [
                '_controller' => 'coop_tilleuls_forgot_password.controller.get_token',
            ]))->setMethods('GET')
        );

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return 'coop_tilleuls_forgot_password' === $type;
    }
}
