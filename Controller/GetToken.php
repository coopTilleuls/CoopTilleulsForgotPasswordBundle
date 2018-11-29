<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class GetToken
{
    private $normalizer;
    private $groups;

    /**
     * @param NormalizerInterface $normalizer
     * @param array               $groups
     */
    public function __construct(NormalizerInterface $normalizer, array $groups)
    {
        $this->normalizer = $normalizer;
        $this->groups = $groups;
    }

    /**
     * @param AbstractPasswordToken $token
     *
     * @return JsonResponse
     */
    public function __invoke(AbstractPasswordToken $token)
    {
        return new JsonResponse(
            $this->normalizer->normalize($token, 'json', $this->groups ? ['groups' => $this->groups] : [])
        );
    }
}
