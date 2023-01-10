<?php

use Imponeer\Smarty\Extensions\DBResource\DBResource;
use PHPUnit\Framework\TestCase;

class DBResourceTest extends TestCase
{

    /**
     * @var DBResource
     */
    private $plugin;
    /**
     * @var Smarty
     */
    private $smarty;
    /**
     * @var PDO
     */
    private $pdo;

    public function testGetName(): void
    {
        $this->assertSame('db', $this->plugin->getName());
    }

    public function testInvokeWithNonExistingFile(): void
    {
        $this->expectException(SmartyException::class);
        $this->renderTemplateFromString('{include file="db:/images/image.tpl"}');
    }

    /**
     * @throws SmartyException
     */
    protected function renderTemplateFromString(string $source): string
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

    public function testInvokeWithExistingFileOnce(): void
    {
        $this->createTestTplRecord();

        $ret = $this->renderTemplateFromString('{include file="db:test.tpl"}');

        $this->assertSame('test', $ret);
    }

    protected function createTestTplRecord(): void
    {
        $statemenet = $this->pdo->prepare(
            'INSERT INTO tplfile (`tpl_tplset`, `tpl_file`, `tpl_desc`, `tpl_lastmodified`, `tpl_lastimported`, `tpl_type`)
                   VALUES (:tpl_tplset, :tpl_file, :tpl_desc, :tpl_lastmodified, :tpl_lastimported, :tpl_type)'
        );
        $statemenet->execute([
            ':tpl_tplset' => 'default',
            ':tpl_file' => 'test.tpl',
            ':tpl_desc' => 'Simple file for the test',
            ':tpl_lastmodified' => time(),
            ':tpl_lastimported' => time(),
            ':tpl_type' => 'default',
        ]);
    }

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

    protected function setUp(): void
    {
        $this->pdo = new PDO("sqlite::memory:");
        $this->createTable();

        $this->plugin = new DBResource(
            $this->pdo, // PDO compatible database connection
            'default', // current template set name
            'tplfile',
            'tpl_source',
            'tpl_lastmodified',
            'tpl_tplset',
            'tpl_file',
            function (array $row): string { // function that converts database row info into string of real file
                return __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $row['tpl_file'];
            },
            'default'
        );

        $this->smarty = new Smarty();
        $this->smarty->caching = Smarty::CACHING_OFF;
        $this->smarty->registerResource(
            $this->plugin->getName(),
            $this->plugin
        );

        parent::setUp();
    }

    protected function createTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE `tplfile` (
	`tpl_id` MEDIUMINT UNSIGNED AUTO_INCREMENT,
	`tpl_refid` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
	`tpl_tplset` VARCHAR(50) NOT NULL DEFAULT 'default',
	`tpl_file` VARCHAR(50) NOT NULL DEFAULT '',
	`tpl_desc` VARCHAR(255) NOT NULL DEFAULT '',
	`tpl_lastmodified` INT UNSIGNED NOT NULL DEFAULT '0',
	`tpl_lastimported` INT UNSIGNED NOT NULL DEFAULT '0',
	`tpl_type` VARCHAR(20) NOT NULL DEFAULT '',
	PRIMARY KEY (`tpl_id`)
);"
        );
    }

}