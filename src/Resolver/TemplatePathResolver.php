<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource\Resolver;

/**
 * Template path resolver that converts database row data to file paths
 *
 * This class provides a callable implementation for resolving template file paths
 * from database records. It can be used as a replacement for closure functions
 * in dependency injection containers and provides better testability.
 */
class TemplatePathResolver
{
    /**
     * Constructor.
     *
     * @param string $templateBasePath Base path where template files are stored
     * @param string $templateFileColumn Column name containing the template filename (default: 'tpl_file')
     */
    public function __construct(
        private readonly string $templateBasePath,
        private readonly string $templateFileColumn = 'tpl_file'
    ) {
    }

    /**
     * Resolves template file path from database row data
     *
     * @param array<string|int, mixed> $row Database row containing template information
     * @return string|null Full path to template file, or null if resolution fails
     */
    public function __invoke(array $row): ?string
    {
        if (!isset($row[$this->templateFileColumn]) || empty($row[$this->templateFileColumn])) {
            return null;
        }

        $templateFile = $row[$this->templateFileColumn];
        if (!is_string($templateFile)) {
            return null;
        }

        // Normalize path separators and remove any potential directory traversal
        $templateFile = str_replace(['\\', '..'], ['/', ''], $templateFile);
        $templateFile = ltrim($templateFile, '/');

        if (empty($templateFile)) {
            return null;
        }

        return $this->templateBasePath . DIRECTORY_SEPARATOR . $templateFile;
    }

    /**
     * Gets the configured template base path
     *
     * @return string
     */
    public function getTemplateBasePath(): string
    {
        return $this->templateBasePath;
    }

    /**
     * Gets the configured template file column name
     *
     * @return string
     */
    public function getTemplateFileColumn(): string
    {
        return $this->templateFileColumn;
    }
}
