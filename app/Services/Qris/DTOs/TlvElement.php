<?php

namespace App\Services\Qris\DTOs;

class TlvElement
{
    /**
     * @param TlvElement[]|null $children
     */
    public function __construct(
        public string $tag,
        public string $name,
        public int $length,
        public string $value,
        public ?array $children = null
    ) {}

    public function toArray(): array
    {
        return [
            'tag' => $this->tag,
            'name' => $this->name,
            'length' => $this->length,
            'value' => $this->value,
            'children' => $this->children ? array_map(fn (TlvElement $c) => $c->toArray(), $this->children) : null,
        ];
    }
}
