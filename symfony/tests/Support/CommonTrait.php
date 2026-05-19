<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Fixture\Test\UserFixture;

trait CommonTrait
{
    /**
     * Authenticate through the real login form.
     *
     * Portable across the Functional (Symfony BrowserKit) and Acceptance
     * (PhpBrowser) suites, since both only rely on amOnPage()/submitForm().
     */
    public function loginAsAUser(string $email, string $password = UserFixture::PASSWORD): void
    {
        $this->amOnPage('/login');
        $this->submitForm('form', [
            '_username' => $email,
            '_password' => $password,
        ]);
    }
}
