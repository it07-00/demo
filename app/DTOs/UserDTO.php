<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UserDTO
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $name,
        public string $username,
        public string $email,
        public ?string $password = null,
        public array $roles = [],
    ) {}

    /**
     * @param  array{name?: string, username?: string, email?: string, password?: string|null, roles?: array<int, string>|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) ($data['name'] ?? '')),
            username: strtolower(trim((string) ($data['username'] ?? ''))),
            email: strtolower(trim((string) ($data['email'] ?? ''))),
            password: filled($data['password'] ?? null) ? (string) $data['password'] : null,
            roles: array_values(array_filter(array_map(
                static fn (mixed $role): string => trim((string) $role),
                (array) ($data['roles'] ?? []),
            ))),
        );
    }
}
