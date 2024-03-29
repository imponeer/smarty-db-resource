<?php

namespace Imponeer\Smarty\Extensions\DBResource;

use Exception;
use Imponeer\Contracts\Smarty\Extension\SmartyResourceInterface;
use PDO;
use Smarty_Resource_Custom;

/**
 * Smarty resource type to fetch template from database
 *
 * @package Imponeer\Smarty\Extensions\DBResource
 */
class DBResource extends Smarty_Resource_Custom implements SmartyResourceInterface
{
    /**
     * @var PDO
     */
    private $pdo;
    /**
     * @var string
     */
    private $templatesTableName;
    /**
     * @var string
     */
    private $templateSourceColumnName;
    /**
     * @var string
     */
    private $templateModificationColumnName;
    /**
     * @var string
     */
    private $tplSetName;
    /**
     * @var string
     */
    private $tplSetColumnName;
    /**
     * @var string
     */
    private $templateNameColumnName;
    /**
     * @var string
     */
    private $defaultTplSetName;

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
     * @param string $templateModificationColumnName Column name that is used to store template modification Unix timestamp
     * @param string $tplSetColumnName Column name that identifies template related teplate set for core
     * @param string $templateNameColumnName Column name that identifies template file name
     * @param callable $templatePathGetter Callable that is used to convert from database fetched data into real template path
     * @param string $defaultTplSetName Default template set name
     */
    public function __construct(
        PDO      $pdo,
        string   $tplSetName,
        string   $templatesTableName,
        string   $templateSourceColumnName,
        string   $templateModificationColumnName,
        string   $tplSetColumnName,
        string   $templateNameColumnName,
        callable $templatePathGetter,
        string   $defaultTplSetName = 'default'
    )
    {
        $this->pdo = $pdo;
        $this->templatesTableName = $templatesTableName;
        $this->templateSourceColumnName = $templateSourceColumnName;
        $this->templateModificationColumnName = $templateModificationColumnName;
        $this->tplSetName = $tplSetName;
        $this->tplSetColumnName = $tplSetColumnName;
        $this->templateNameColumnName = $templateNameColumnName;
        $this->defaultTplSetName = $defaultTplSetName;
        $this->templatePathGetter = $templatePathGetter;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'db';
    }

    /**
     * @inheritDoc
     */
    protected function fetch($name, &$source, &$mtime)
    {
        [$source, $mtime] = $this->getInfo($name);
    }

    /**
     * Gets info for template
     *
     * @param string $template Template file name
     *
     * @return array|null
     *
     * @throws Exception
     */
    private function getInfo(string $template): ?array
    {
        $data = $this->fetchTemplateDataFromDatabase($template);

        if ($data === null) {
            $content = $this->getCacheArrayForNotFoundItem();
        } elseif ($data[$this->tplSetColumnName] !== $this->defaultTplSetName) {
            $content = $this->getCacheArrayForDatabaseRow($data);
        } else {
            $ret = call_user_func($this->templatePathGetter, $data);
            if ($ret === null) {
                return null;
            }

            $content = $this->getCacheArrayForFile($ret);
        }

        return $content;
    }

    /**
     * Gets array that will be cached for empty item
     *
     * @return array
     */
    protected function getCacheArrayForNotFoundItem(): array
    {
        return [null, null];
    }

    /**
     * Gets array that will be cached for database item
     *
     * @param array $data Array row data (assoc)
     *
     * @return array
     */
    protected function getCacheArrayForDatabaseRow(array $data): array
    {
        return [
            $data[$this->templateSourceColumnName],
            $data[$this->templateModificationColumnName],
        ];
    }

    /**
     * Gets array that will be cached for real file
     *
     * @param string $file File for what to create this array
     *
     * @return array
     */
    protected function getCacheArrayForFile(string $file): array
    {
        return [
            file_get_contents($file),
            filemtime($file),
        ];
    }

    /**
     * Gets select query for the driver
     *
     * @return string
     */
    private function getSelectQuery(): string
    {
        switch ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'sqlite':
            case 'sqlite3':
                return 'SELECT *, CASE WHEN `%3$s` IS NOT NULL THEN 1 ELSE 0 END ___order  FROM `%1$s` WHERE `%2$s` = :template AND `%3$s` IN (:tplset, :defaultTplSet) ORDER BY ___order ASC LIMIT 1';
            default:
                return 'SELECT * FROM `%1$s` WHERE `%2$s` = :template AND `%3$s` IN (:tplset, :defaultTplSet) ORDER BY IF(`%3$s` = :defaultTplSet, 1, 0) ASC LIMIT 1';
        }

    }

    /**
     * Fetches template modify date info
     *
     * @param string $template Template to find in DB
     *
     * @return array|null
     *
     * @throws Exception
     */
    private function fetchTemplateDataFromDatabase(string $template): ?array
    {
        $stm = $this->pdo->prepare(
            sprintf(
                $this->getSelectQuery(),
                $this->templatesTableName,
                $this->templateNameColumnName,
                $this->tplSetColumnName
            )
        );
        $stm->bindValue('template', $template, PDO::PARAM_STR);
        $stm->bindValue('tplset', $this->tplSetName, PDO::PARAM_STR);
        $stm->bindValue('defaultTplSet', $this->defaultTplSetName, PDO::PARAM_STR);
        $stm->execute();

        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $row;
    }
}
