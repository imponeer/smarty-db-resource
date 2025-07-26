<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource\Dto;

class TemplateInfo
{
    public function __construct(
        public readonly ?string $source = null,
        public readonly ?int $mtime = null
    ) {
    }

    public function toArray(): array
    {
        return [
            $this->source,
            $this->mtime
        ];
    }
}
