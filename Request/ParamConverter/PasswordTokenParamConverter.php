<?php

namespace CoopTilleuls\ForgotPasswordBundle\Request\ParamConverter;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PasswordTokenParamConverter implements ParamConverterInterface
{
    /**
     * @var PasswordTokenManager
     */
    private $passwordTokenManager;

    /**
     * @param PasswordTokenManager $passwordTokenManager
     */
    public function __construct(PasswordTokenManager $passwordTokenManager)
    {
        $this->passwordTokenManager = $passwordTokenManager;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $param = $configuration->getName();

        if (null === ($value = $request->get($param))) {
            return false;
        }

        /** @var AbstractPasswordToken $token */
        if (null === ($token = $this->passwordTokenManager->findOneByToken($value))) {
            throw new NotFoundHttpException('Invalid token.');
        } elseif (time() > $token->getExpiresAt()->getTimestamp()) {
            throw new NotFoundHttpException('The token has expired.');
        }

        $request->attributes->set($param, $token);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration)
    {
        return AbstractPasswordToken::class === $configuration->getClass();
    }
}
