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

namespace Webauthn\SecurityBundle\Security\Authentication\Token;

use Assert\Assertion;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebauthnToken extends AbstractToken
{
    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var PublicKeyCredentialRequestOptions
     */
    private $publicKeyCredentialRequestOptions;

    /**
     * @var PublicKeyCredentialDescriptor
     */
    private $publicKeyCredentialDescriptor;

    public function __construct(string $username, PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, PublicKeyCredentialDescriptor $publicKeyCredentialDescriptor, string $providerKey, array $roles = [])
    {
        parent::__construct($roles);
        Assertion::notEmpty($providerKey, '$providerKey must not be empty.');

        $this->setUser($username);
        $this->providerKey = $providerKey;
        $this->publicKeyCredentialRequestOptions = $publicKeyCredentialRequestOptions;
        $this->publicKeyCredentialDescriptor = $publicKeyCredentialDescriptor;
    }

    public function getPublicKeyCredentialRequestOptions(): PublicKeyCredentialRequestOptions
    {
        return $this->publicKeyCredentialRequestOptions;
    }

    public function getCredentials(): PublicKeyCredentialDescriptor
    {
        return $this->publicKeyCredentialDescriptor;
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }

    public function serialize(): string
    {
        return serialize([\Safe\json_encode($this->publicKeyCredentialRequestOptions), \Safe\json_encode($this->publicKeyCredentialDescriptor), $this->providerKey, parent::serialize()]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized): self
    {
        list($publicKeyCredentialRequestOptions, $publicKeyCredentialDescriptor, $this->providerKey, $parentStr) = unserialize($serialized);
        $data = \Safe\json_decode($publicKeyCredentialRequestOptions, true);
        $this->publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::createFromJson($data);

        $data = \Safe\json_decode($publicKeyCredentialDescriptor, true);
        $this->publicKeyCredentialDescriptor = PublicKeyCredentialDescriptor::createFromJson($data);

        parent::unserialize($parentStr);
    }
}
