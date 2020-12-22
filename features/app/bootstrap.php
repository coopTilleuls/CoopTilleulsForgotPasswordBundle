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

use DG\BypassFinals;
use Doctrine\Common\Annotations\AnnotationRegistry;

date_default_timezone_set('UTC');

// PHPUnit's autoloader
if (!file_exists($phpUnitAutoloaderPath = __DIR__.'/../../vendor/bin/.phpunit/phpunit/vendor/autoload.php')) {
    exit('PHPUnit is not installed. Please run vendor/bin/simple-phpunit --version to install it');
}

$phpunitLoader = require $phpUnitAutoloaderPath;
// Don't register the PHPUnit autoloader before the normal autoloader to prevent weird issues
$phpunitLoader->unregister();
$phpunitLoader->register();

$loader = require __DIR__.'/../../vendor/autoload.php';
BypassFinals::enable();
require 'AppKernel.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
