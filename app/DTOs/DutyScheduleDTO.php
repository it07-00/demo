<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class DutyScheduleDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $location,
        public string $start_at,
        public ?string $end_at,
        public string $label_color,
        public bool $is_private = false,
        public ?int $created_by = null,
        public array $userIds = [],
    ) {}

    /**
     * @param array{
     *     title: string,
     *     description?: string|null,
     *     location?: string|null,
     *     start_at: string,
     *     end_at?: string|null,
     *     label_color?: string|null,
     *     is_private?: bool|null,
     *     created_by?: int|null,
     *     user_ids?: array|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: trim($data['title']),
            description: isset($data['description']) && filled($data['description']) ? trim($data['description']) : null,
            location: isset($data['location']) && filled($data['location']) ? trim($data['location']) : null,
            start_at: $data['start_at'],
            end_at: isset($data['end_at']) && filled($data['end_at']) ? $data['end_at'] : null,
            label_color: $data['label_color'] ?? 'primary',
            is_private: (bool) ($data['is_private'] ?? false),
            created_by: $data['created_by'] ?? null,
            userIds: $data['user_ids'] ?? [],
        );
    }
}
