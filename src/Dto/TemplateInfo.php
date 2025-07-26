<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource\Dto;

class TemplateInfo
{
    public function __construct(
        public readonly ?string $source = null,
        public readonly ?int $mtime = null
    ) {
    }

    /**
     * @return array{0: ?string, 1: ?int}
     */
    public function toArray(): array
    {
        return [
            $this->source,
            $this->mtime
        ];
    }
}
