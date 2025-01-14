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

use DG\BypassFinals;
use Doctrine\Common\Annotations\AnnotationRegistry;

date_default_timezone_set('UTC');

$loader = require __DIR__.'/../../vendor/autoload.php';
BypassFinals::enable();
require 'AppKernel.php';

if (method_exists(AnnotationRegistry::class, 'registerLoader')) {
    AnnotationRegistry::registerLoader([$loader, 'loadClass']);
}

return $loader;
