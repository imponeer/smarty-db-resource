<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource\Drivers;

class MysqlPdoDriver extends AbstractPdoDriver
{

    /**
     * @inheritDoc
     */
    protected function getSelectQuery(): string
    {
        return sprintf(
            'SELECT * 
                    FROM `%1$s`
                    WHERE `%2$s` = :template 
                      AND `%3$s` IN (:tplset, :defaultTplSet)
                    ORDER BY IF(`%3$s` = :defaultTplSet, 1, 0) ASC LIMIT 1',
            $this->templatesTableName,
            $this->templateNameColumnName,
            $this->tplSetColumnName
        );
    }
}