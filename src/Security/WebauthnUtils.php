<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\SecurityBundle\Security;

use Assert\Assertion;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\Bundle\Service\PublicKeyCredentialRequestOptionsFactory;
use Webauthn\SecurityBundle\Model\CanHaveRegisteredSecurityDevices;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebauthnUtils
{
    /**
     * @var AuthenticationUtils
     */
    private $authenticationUtils;

    /**
     * @var PublicKeyCredentialRequestOptionsFactory
     */
    private $publicKeyCredentialRequestOptionsFactory;

    public function __construct(PublicKeyCredentialRequestOptionsFactory $publicKeyCredentialRequestOptionsFactory, RequestStack $requestStack)
    {
        $this->authenticationUtils = new AuthenticationUtils($requestStack);
        $this->publicKeyCredentialRequestOptionsFactory = $publicKeyCredentialRequestOptionsFactory;
    }

    public function getLastAuthenticationError(bool $clearSession = true): ?AuthenticationException
    {
        return $this->authenticationUtils->getLastAuthenticationError($clearSession);
    }

    public function getLastUsername(): string
    {
        return $this->authenticationUtils->getLastUsername();
    }

    public function generateRequestFromProfile(string $key, UserInterface $user): PublicKeyCredentialRequestOptions
    {
        $allowedCredentials = $this->getAllowedCredentials($user);

        return $this->publicKeyCredentialRequestOptionsFactory->create($key, $allowedCredentials);
    }

    public function generateRequest(UserInterface $user, int $challengeLength = 16, int $timeout = 60000, string $rpId = null, string $userVerification = PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED, ?AuthenticationExtensionsClientInputs $extensions = null): PublicKeyCredentialRequestOptions
    {
        Assertion::min($timeout, 1, 'Invalid timeout');
        Assertion::min($challengeLength, 1, 'Invalid challenge length');
        $allowedCredentials = $this->getAllowedCredentials($user);

        return new PublicKeyCredentialRequestOptions(
            random_bytes($challengeLength),
            $timeout,
            $rpId,
            $allowedCredentials,
            $userVerification,
            $extensions
        );
    }

    /**
     * @return PublicKeyCredentialDescriptor[]
     */
    private function getAllowedCredentials(UserInterface $user): array
    {
        if (!$user instanceof CanHaveRegisteredSecurityDevices) {
            throw new \InvalidArgumentException('The user must implement the interface "Webauthn\SecurityBundle\Model\CanHaveRegisteredSecurityDevices"');
        }

        $credentials = [];
        foreach ($user->getSecurityDeviceCredentialIds() as $publicKeyCredentialDescriptor) {
            Assertion::isInstanceOf($publicKeyCredentialDescriptor, PublicKeyCredentialDescriptor::class, \Safe\sprintf('Invalid credential. Must be of type "Webauthn\PublicKeyCredentialDescriptor", got "%s".', \gettype($publicKeyCredentialDescriptor)));
            $credentials[] = $publicKeyCredentialDescriptor;
        }

        return $credentials;
    }
}
