<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource\Drivers;

use Exception;
use PDO;

abstract class AbstractPdoDriver
{
    public function __construct(
        protected readonly PDO $pdo,
        protected readonly string $tplSetName,
        protected readonly string $templatesTableName,
        protected readonly string $templateSourceColumnName,
        protected readonly string $templateModificationColumnName,
        protected readonly string $tplSetColumnName,
        protected readonly string $templateNameColumnName,
        protected readonly string $defaultTplSetName = 'default'
    ) {
    }

    /**
     * Returns query for selecting template data
     *
     * @return string
     */
    abstract protected function getSelectQuery(): string;

    /**
     * Fetches template modify date info
     *
     * @param string $template Template to find in DB
     *
     * @return array<string|int, mixed>|null
     *
     * @throws Exception
     */
    public function fetchFromDatabase(string $template): ?array
    {
        $stm = $this->pdo->prepare(
            $this->getSelectQuery()
        );
        $stm->bindValue('template', $template, PDO::PARAM_STR);
        $stm->bindValue('tplset', $this->tplSetName, PDO::PARAM_STR);
        $stm->bindValue('defaultTplSet', $this->defaultTplSetName, PDO::PARAM_STR);
        if (!$stm->execute()) {
            throw new \Smarty\Exception('Failed to execute database query');
        }

        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        assert(is_array($row));

        return $row;
    }
}
