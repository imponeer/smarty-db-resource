<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource;

use Closure;
use Exception;
use Imponeer\Smarty\Extensions\DatabaseResource\Drivers\AbstractPdoDriver;
use Imponeer\Smarty\Extensions\DatabaseResource\Drivers\MysqlPdoDriver;
use Imponeer\Smarty\Extensions\DatabaseResource\Drivers\SqlitePdoDriver;
use Imponeer\Smarty\Extensions\DatabaseResource\Dto\TemplateInfo;
use PDO;
use Smarty\Resource\CustomPlugin as SmartyResourceCustom;

/**
 * Smarty resource type to fetch template from database
 *
 * @package Imponeer\Smarty\Extensions\DBResource
 */
class DatabaseResource extends SmartyResourceCustom
{
    private AbstractPdoDriver $driver;

    /**
     * @var callable
     */
    private $templatePathGetter;

    /**
     * Constructor.
     *
     * @param PDO $pdo PDO compatible database connection instance
     * @param string $tplSetName Current template set name
     * @param string $templatesTableName Table name where all template data are located
     * @param string $templateSourceColumnName Column name that is used to store template source code
     * @param string $templateModificationColumnName Column name that is used to store template modification timestamp
     * @param string $tplSetColumnName Column name that identifies template related template set for core
     * @param string $templateNameColumnName Column name that identifies template file name
     * @param callable(array<int|string, mixed> $row): ?string $templatePathGetter Callable that is for to converting
     *                                                                       from database fetched data into real
     *                                                                       template path
     * @param string $defaultTplSetName Default template set name
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $tplSetName,
        private readonly string $templatesTableName,
        private readonly string $templateSourceColumnName,
        private readonly string $templateModificationColumnName,
        private readonly string $tplSetColumnName,
        private readonly string $templateNameColumnName,
        callable $templatePathGetter,
        private readonly string $defaultTplSetName = 'default'
    ) {
        $this->driver = $this->createInstanceDriver();
        $this->templatePathGetter = $templatePathGetter;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     *
     * @param-out string|null $source
     * @param-out int|null $mtime
     */
    protected function fetch($name, &$source, &$mtime): void
    {
        [$source, $mtime] = $this->getInfo($name)->toArray();
    }

    /**
     * Gets info for template
     *
     * @param string $template Template file name
     *
     * @return TemplateInfo
     *
     * @throws Exception
     */
    private function getInfo(string $template): TemplateInfo
    {
        $data = $this->driver->fetchFromDatabase($template);

        if ($data === null) {
            $content = new TemplateInfo();
        } elseif ($data[$this->tplSetColumnName] !== $this->defaultTplSetName) {
            assert(
                is_string($data[$this->templateSourceColumnName]) ||
                is_null($data[$this->templateSourceColumnName])
            );
            assert(
                is_int($data[$this->templateModificationColumnName]) ||
                is_null($data[$this->templateModificationColumnName])
            );

            $content = new TemplateInfo(
                $data[$this->templateSourceColumnName],
                $data[$this->templateModificationColumnName],
            );
        } else {
            $ret = call_user_func($this->templatePathGetter, $data);
            if ($ret === null || !is_string($ret)) {
                return new TemplateInfo();
            }

            if (!is_file($ret) || !is_readable($ret)) {
                return new TemplateInfo();
            }

            $fileContents = file_get_contents($ret);
            if ($fileContents === false) {
                return new TemplateInfo();
            }

            $fileMTime = filemtime($ret);
            if ($fileMTime === false) {
                return new TemplateInfo();
            }

            $content = new TemplateInfo($fileContents, $fileMTime);
        }

        return $content;
    }

    /**
     * Gets database driver for dealing with templates
     *
     * @return AbstractPdoDriver
     */
    private function createInstanceDriver(): AbstractPdoDriver
    {
        return match ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            'sqlite', 'sqlite3' => new SqlitePdoDriver(
                $this->pdo,
                $this->tplSetName,
                $this->templatesTableName,
                $this->templateSourceColumnName,
                $this->templateModificationColumnName,
                $this->tplSetColumnName,
                $this->templateNameColumnName,
                $this->defaultTplSetName
            ),
            default => new MysqlPdoDriver(
                $this->pdo,
                $this->tplSetName,
                $this->templatesTableName,
                $this->templateSourceColumnName,
                $this->templateModificationColumnName,
                $this->tplSetColumnName,
                $this->templateNameColumnName,
                $this->defaultTplSetName
            ),
        };
    }
}
