<?php

namespace Tests\Core\View;

use PHPUnit\Framework\TestCase;
use App\Core\View\Engine;

class EngineTest extends TestCase
{
    protected $engine;
    protected $viewsPath;

    protected function setUp(): void
    {
        $this->viewsPath = __DIR__ . '/test_views';
        if (!is_dir($this->viewsPath)) {
            mkdir($this->viewsPath, 0777, true);
        }
        
        $this->engine = new Engine($this->viewsPath);
    }

    protected function tearDown(): void
    {
        // Cleanup test views
        $files = glob($this->viewsPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->viewsPath)) {
            rmdir($this->viewsPath);
        }
    }

    public function testRenderSimpleView()
    {
        file_put_contents($this->viewsPath . '/simple.php', 'Hello <?= $name ?>');
        
        $output = $this->engine->render('simple', ['name' => 'World'], true);
        
        $this->assertEquals('Hello World', $output);
    }

    public function testRenderWithLayout()
    {
        // Create Layout
        file_put_contents($this->viewsPath . '/layout.php', 'Header-<?= $this->renderSection("content") ?>-Footer');
        
        // Create View
        $viewContent = <<<PHP
<?php \$this->extend('layout'); ?>
<?php \$this->section('content'); ?>
Body Content
<?php \$this->endSection(); ?>
PHP;
        file_put_contents($this->viewsPath . '/view.php', $viewContent);

        $output = $this->engine->render('view', [], true);
        
        $this->assertEquals('Header-Body Content-Footer', trim(str_replace("\n", "", $output)));
    }
}
