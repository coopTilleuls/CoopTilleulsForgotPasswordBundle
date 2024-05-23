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

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\NormalizerInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class GetToken
{
    public function __construct(private readonly NormalizerInterface $normalizer)
    {
    }

    /**
     * @return JsonResponse
     */
    public function __invoke(AbstractPasswordToken $token, ProviderInterface $provider)
    {
        $groups = $provider->getPasswordTokenSerializationGroups();

        return new JsonResponse(
            $this->normalizer->normalize($token, 'json', $groups ? ['groups' => $groups] : [])
        );
    }
}
