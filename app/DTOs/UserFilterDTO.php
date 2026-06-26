<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UserFilterDTO
{
    private const array SORTABLE_FIELDS = ['name', 'username', 'email', 'created_at'];

    private const array PER_PAGE_OPTIONS = [10, 15, 25, 50];

    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public string $sortField = 'created_at',
        public string $sortDirection = 'desc',
        public int $perPage = 10,
    ) {}

    /**
     * @param  array{search?: string|null, role?: string|null, sort_field?: string|null, sort_direction?: string|null, per_page?: int|string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        $sortField = (string) ($data['sort_field'] ?? 'created_at');
        $sortDirection = strtolower((string) ($data['sort_direction'] ?? 'desc'));
        $perPage = (int) ($data['per_page'] ?? 10);

        return new self(
            search: filled($data['search'] ?? null) ? trim((string) $data['search']) : null,
            role: filled($data['role'] ?? null) ? trim((string) $data['role']) : null,
            sortField: in_array($sortField, self::SORTABLE_FIELDS, true) ? $sortField : 'created_at',
            sortDirection: in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc',
            perPage: in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10,
        );
    }
}
