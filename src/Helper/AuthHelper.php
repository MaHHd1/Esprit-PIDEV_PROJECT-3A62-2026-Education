<?php

namespace App\Helper;

use App\Service\AuthChecker;

class AuthHelper
{
    private AuthChecker $authChecker;

    public function __construct(AuthChecker $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function isUserLoggedIn(): bool
    {
        return $this->authChecker->isLoggedIn();
    }

    public function getUserName(): ?string
    {
        return $this->authChecker->getUserName();
    }

    public function getUserType(): ?string
    {
        if (!$this->isUserLoggedIn()) {
            return null;
        }

        return $this->authChecker->getCurrentUser()?->getType();
    }
    public function getCurrentUser(): mixed
{
    return $this->authChecker->getCurrentUser();
}
}