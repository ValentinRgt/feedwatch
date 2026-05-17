<?php

declare(strict_types=1);

namespace App\Interface;

interface UserInterface
{
    /**
     * Checks if a user with the given email already exists.
     *
     * @param string $email
     */
    public function alreadyExist(string $email): bool;

    /**
     * Creates an admin user with the given email, username, and password.
     *
     * @param string $email
     * @param string $username
     * @param string $password
     */
    public function createAdmin(string $email, string $username, string $password): void;
}
