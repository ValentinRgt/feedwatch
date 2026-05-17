<?php

declare(strict_types=1);

namespace App\Interface;

use App\Entity\User;

interface UserInterface
{
    /**
     * Checks if a user with the given email already exists.
     *
     * @param string $email
     */
    public function alreadyExist(string $email): bool;

    /**
     * Returns the user with the given email, or null if none exists.
     *
     * @param string $email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Creates an admin user with the given email, username, and password.
     *
     * @param string $email
     * @param string $username
     * @param string $password
     */
    public function createAdmin(string $email, string $username, string $password): void;

    /**
     * Updates an admin user's username, and password when provided.
     *
     * @param User $user
     * @param string $username
     * @param string|null $password
     */
    public function updateAdmin(User $user, string $username, ?string $password): void;

    /**
     * Deletes the given admin user.
     *
     * @param User $user
     */
    public function deleteAdmin(User $user): void;
}
