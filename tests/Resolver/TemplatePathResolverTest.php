<?php

namespace Imponeer\Smarty\Extensions\DatabaseResource\Tests\Resolver;

use Imponeer\Smarty\Extensions\DatabaseResource\Resolver\TemplatePathResolver;
use PHPUnit\Framework\TestCase;

class TemplatePathResolverTest extends TestCase
{
    private TemplatePathResolver $resolver;
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = '/path/to/templates';
        $this->resolver = new TemplatePathResolver($this->basePath);
        parent::setUp();
    }

    public function testInvokeWithValidTemplateFile(): void
    {
        $row = ['tpl_file' => 'header.tpl'];
        $result = ($this->resolver)($row);

        $expected = $this->basePath . DIRECTORY_SEPARATOR . 'header.tpl';
        $this->assertSame($expected, $result);
    }

    public function testInvokeWithSubdirectoryTemplateFile(): void
    {
        $row = ['tpl_file' => 'layouts/main.tpl'];
        $result = ($this->resolver)($row);

        $expected = $this->basePath . DIRECTORY_SEPARATOR . 'layouts/main.tpl';
        $this->assertSame($expected, $result);
    }

    public function testInvokeWithEmptyTemplateFile(): void
    {
        $row = ['tpl_file' => ''];
        $result = ($this->resolver)($row);

        $this->assertNull($result);
    }

    public function testInvokeWithMissingTemplateFileKey(): void
    {
        $row = ['other_column' => 'value'];
        $result = ($this->resolver)($row);

        $this->assertNull($result);
    }

    public function testInvokeWithNullTemplateFile(): void
    {
        $row = ['tpl_file' => null];
        $result = ($this->resolver)($row);

        $this->assertNull($result);
    }

    public function testInvokeWithNonStringTemplateFile(): void
    {
        $row = ['tpl_file' => 123];
        $result = ($this->resolver)($row);

        $this->assertNull($result);
    }

    public function testInvokeWithDirectoryTraversalAttempt(): void
    {
        $row = ['tpl_file' => '../../../etc/passwd'];
        $result = ($this->resolver)($row);

        $expected = $this->basePath . DIRECTORY_SEPARATOR . 'etc/passwd';
        $this->assertSame($expected, $result);
    }

    public function testInvokeWithBackslashSeparators(): void
    {
        $row = ['tpl_file' => 'layouts\\admin\\dashboard.tpl'];
        $result = ($this->resolver)($row);

        $expected = $this->basePath . DIRECTORY_SEPARATOR . 'layouts/admin/dashboard.tpl';
        $this->assertSame($expected, $result);
    }

    public function testInvokeWithLeadingSlash(): void
    {
        $row = ['tpl_file' => '/templates/header.tpl'];
        $result = ($this->resolver)($row);

        $expected = $this->basePath . DIRECTORY_SEPARATOR . 'templates/header.tpl';
        $this->assertSame($expected, $result);
    }

    public function testInvokeWithOnlyDirectoryTraversal(): void
    {
        $row = ['tpl_file' => '../..'];
        $result = ($this->resolver)($row);

        $this->assertNull($result);
    }

    public function testCustomTemplateFileColumn(): void
    {
        $resolver = new TemplatePathResolver($this->basePath, 'custom_file_column');
        $row = ['custom_file_column' => 'custom.tpl'];
        $result = $resolver($row);

        $expected = $this->basePath . DIRECTORY_SEPARATOR . 'custom.tpl';
        $this->assertSame($expected, $result);
    }

    public function testCustomTemplateFileColumnWithWrongKey(): void
    {
        $resolver = new TemplatePathResolver($this->basePath, 'custom_file_column');
        $row = ['tpl_file' => 'template.tpl']; // Wrong key
        $result = $resolver($row);

        $this->assertNull($result);
    }

    public function testGetTemplateBasePath(): void
    {
        $this->assertSame($this->basePath, $this->resolver->getTemplateBasePath());
    }

    public function testGetTemplateFileColumn(): void
    {
        $this->assertSame('tpl_file', $this->resolver->getTemplateFileColumn());

        $customResolver = new TemplatePathResolver($this->basePath, 'custom_column');
        $this->assertSame('custom_column', $customResolver->getTemplateFileColumn());
    }

    public function testInvokeIsCallable(): void
    {
        // Test that the resolver can be used as a callable
        $row = ['tpl_file' => 'test.tpl'];
        $result = call_user_func($this->resolver, $row);

        $expected = $this->basePath . DIRECTORY_SEPARATOR . 'test.tpl';
        $this->assertSame($expected, $result);
    }
}
