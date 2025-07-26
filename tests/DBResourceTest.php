<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource\Tests;

use Imponeer\Smarty\Extensions\DatabaseResource\DBResource;
use PDO;
use PHPUnit\Framework\TestCase;
use Smarty\Smarty;
use Smarty\Exception as SmartyException;

class DBResourceTest extends TestCase
{
    private Smarty $smarty;
    private PDO $pdo;

    public function testInvokeWithNonExistingFile(): void
    {
        $this->expectException(SmartyException::class);
        $this->renderTemplateFromString('{include file="db:/images/image.tpl"}');
    }

    /**
     * @throws SmartyException
     */
    private function renderTemplateFromString(string $source): string
    {
        $src = urlencode($source);
        return trim(
            $this->smarty->fetch('eval:urlencode:' . $src)
        );
    }

    public function testInvokeWithExistingFileButWithoutDatabaseRecord(): void
    {
        $this->expectException(SmartyException::class);
        $this->renderTemplateFromString('{include file="db:test.tpl"}');
    }

    /**
     * @throws SmartyException
     */
    public function testInvokeWithExistingFileOnce(): void
    {
        $this->createTestTplRecord();

        $ret = $this->renderTemplateFromString('{include file="db:test.tpl"}');

        $this->assertSame('test', $ret);
    }

    /**
     * @noinspection SqlNoDataSourceInspection
     */
    private function createTestTplRecord(): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO tplfile (
                                 `tpl_tplset`,
                                 `tpl_file`,
                                 `tpl_desc`,
                                 `tpl_lastmodified`,
                                 `tpl_lastimported`,
                                 `tpl_type`
                                 )
                   VALUES (
                           :tpl_tplset,
                           :tpl_file,
                           :tpl_desc,
                           :tpl_lastmodified,
                           :tpl_lastimported,
                           :tpl_type
                           )
        SQL);
        $statement->execute([
            ':tpl_tplset' => 'default',
            ':tpl_file' => 'test.tpl',
            ':tpl_desc' => 'Simple file for the test',
            ':tpl_lastmodified' => time(),
            ':tpl_lastimported' => time(),
            ':tpl_type' => 'default',
        ]);
    }

    /**
     * @throws SmartyException
     */
    public function testInvokeWithExistingFileMultiple(): void
    {
        $this->createTestTplRecord();

        for ($i = 1; $i < 5; $i++) {
            $this->assertSame(
                'test',
                $this->renderTemplateFromString('{include file="db:test.tpl"}'),
                sprintf("Rendering failed (iteration - %d)", $i)
            );
        }
    }

    public function setUp(): void
    {
        $this->pdo = new PDO("sqlite::memory:");
        $this->createTable();

        $plugin = new DBResource(
            pdo: $this->pdo,
            tplSetName: 'default',
            templatesTableName: 'tplfile',
            templateSourceColumnName: 'tpl_source',
            templateModificationColumnName: 'tpl_lastmodified',
            tplSetColumnName: 'tpl_tplset',
            templateNameColumnName: 'tpl_file',
            templatePathGetter: function (array $row): string {
                return __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $row['tpl_file'];
            },
            defaultTplSetName: 'default'
        );

        $this->smarty = new Smarty();
        $this->smarty->caching = Smarty::CACHING_OFF;
        $this->smarty->registerResource(
            'db',
            $plugin
        );

        parent::setUp();
    }

    /**
     * @noinspection SqlNoDataSourceInspection
     */
    private function createTable(): void
    {
        $this->pdo->exec(<<<SQL
            CREATE TABLE `tplfile` (
                `tpl_id` MEDIUMINT UNSIGNED AUTO_INCREMENT,
                `tpl_refid` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
                `tpl_tplset` VARCHAR(50) NOT NULL DEFAULT 'default',
                `tpl_file` VARCHAR(50) NOT NULL DEFAULT '',
                `tpl_desc` VARCHAR(255) NOT NULL DEFAULT '',
                `tpl_lastmodified` INT UNSIGNED NOT NULL DEFAULT '0',
                `tpl_lastimported` INT UNSIGNED NOT NULL DEFAULT '0',
                `tpl_type` VARCHAR(20) NOT NULL DEFAULT '',
                PRIMARY KEY (`tpl_id`)
            );
        SQL
        );
    }
}
