<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource\Drivers;

class SqlitePdoDriver extends AbstractPdoDriver
{

    /**
     * @inheritDoc
     */
    protected function getSelectQuery(): string
    {
        return sprintf(
            'SELECT 
                        *, 
                        CASE WHEN `%3$s` = :defaultTplSet THEN 1 ELSE 0 END ___order
                    FROM `%1$s`
                    WHERE 
                          `%2$s` = :template 
                      AND `%3$s` IN (:tplset, :defaultTplSet) 
                    ORDER BY ___order ASC LIMIT 1',
            $this->templatesTableName,
            $this->templateNameColumnName,
            $this->tplSetColumnName
        );
    }
}