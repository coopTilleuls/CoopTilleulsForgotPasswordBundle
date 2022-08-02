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

namespace CoopTilleuls\ForgotPasswordBundle\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prediction\PredictionException;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

/**
 * Copied from https://github.com/phpspec/prophecy-phpunit for symfony/phpunit-bridge.
 *
 * @mixin TestCase
 */
trait ProphecyTrait
{
    /**
     * @var Prophet|null
     *
     * @internal
     */
    private $prophet;

    /**
     * @var bool
     *
     * @internal
     */
    private $prophecyAssertionsCounted = false;

    /**
     * @throws DoubleException
     * @throws InterfaceNotFoundException
     *
     * @psalm-param class-string|null $classOrInterface
     */
    protected function prophesize(?string $classOrInterface = null): ObjectProphecy
    {
        if (\is_string($classOrInterface)) {
            \assert($this instanceof TestCase);
            $this->recordDoubledType($classOrInterface);
        }

        return $this->getProphet()->prophesize($classOrInterface);
    }

    /**
     * @postCondition
     */
    protected function verifyProphecyDoubles(): void
    {
        if (null === $this->prophet) {
            return;
        }

        try {
            $this->prophet->checkPredictions();
        } catch (PredictionException $e) {
            throw new AssertionFailedError($e->getMessage());
        } finally {
            $this->countProphecyAssertions();
        }
    }

    /**
     * @after
     */
    protected function tearDownProphecy(): void
    {
        if (null !== $this->prophet && !$this->prophecyAssertionsCounted) {
            // Some Prophecy assertions may have been done in tests themselves even when a failure happened before checking mock objects.
            $this->countProphecyAssertions();
        }

        $this->prophet = null;
    }

    /**
     * @internal
     */
    private function countProphecyAssertions(): void
    {
        \assert($this instanceof TestCase);
        $this->prophecyAssertionsCounted = true;

        foreach ($this->prophet->getProphecies() as $objectProphecy) {
            foreach ($objectProphecy->getMethodProphecies() as $methodProphecies) {
                foreach ($methodProphecies as $methodProphecy) {
                    \assert($methodProphecy instanceof MethodProphecy);

                    $this->addToAssertionCount(\count($methodProphecy->getCheckedPredictions()));
                }
            }
        }
    }

    /**
     * @internal
     */
    private function getProphet(): Prophet
    {
        if (null === $this->prophet) {
            $this->prophet = new Prophet();
        }

        return $this->prophet;
    }
}
