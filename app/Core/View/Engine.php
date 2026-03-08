<?php

namespace App\Core\View;

use Exception;
use LogicException;

/**
 * View Engine
 * 
 * Extracts the CI4-like view rendering logic out from BaseController
 * allowing for a clean separation of concerns.
 */
class Engine
{
    protected $basePath;
    protected $layout = 'default';
    protected $useLayout = true;
    protected $viewData = [];
    protected $sections = [];
    protected $currentSection = null;
    
    // Composers
    protected $composers = [];

    // Controller Proxy
    protected $controller = null;

    // Asset stacks for scripts and styles
    protected $stacks = [];
    protected $currentStack = null;
    
    // Slot system for components
    protected $slots = [];
    protected $currentSlot = null;

    /**
     * @param string $basePath The root directory for views
     */
    public function __construct(string $basePath = null)
    {
        // Default assuming it's called from index.php or Controller
        $this->basePath = rtrim($basePath ?: __DIR__ . '/../../Views', '/');
    }

    /**
     * Get shared view data
     */
    public function getViewData(): array
    {
        return $this->viewData;
    }

    /**
     * Set the parent controller for method proxying
     */
    public function setController($controller): self
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Proxy unknown methods to the controller (for backward compatibility with legacy view helpers)
     */
    public function __call($method, $args)
    {
        if ($this->controller && method_exists($this->controller, $method)) {
            return call_user_func_array([$this->controller, $method], $args);
        }
        throw new \BadMethodCallException("Method {$method} does not exist on Engine or its bound Controller.");
    }

    /**
     * Share data across all views
     */
    public function share($key, $value = null): self
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }
        return $this;
    }

    /**
     * Register a view composer
     */
    public function addComposer($views, $callback): self
    {
        $views = (array) $views;
        foreach ($views as $view) {
            if (!isset($this->composers[$view])) {
                $this->composers[$view] = [];
            }
            $this->composers[$view][] = $callback;
        }
        return $this;
    }

    /**
     * Run composers for a given view
     */
    protected function runComposers(string $view)
    {
        $composersToRun = array_merge(
            $this->composers['*'] ?? [],
            $this->composers[$view] ?? []
        );

        foreach (array_unique($composersToRun) as $composer) {
            if (is_callable($composer)) {
                $composer($this);
            } elseif (is_string($composer) && strpos($composer, '@') !== false) {
                list($class, $method) = explode('@', $composer);
                if (class_exists($class)) {
                    $instance = new $class();
                    if (method_exists($instance, $method)) {
                        $instance->$method($this);
                    }
                }
            }
        }
    }

    /**
     * Render a view file
     */
    public function render(string $view, array $data = [], bool $return = false)
    {
        $this->viewData = array_merge($this->viewData, $data);
        $this->runComposers($view);

        $viewFile = $this->getViewPath($view);

        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }

        ob_start();
        extract($this->viewData, EXTR_SKIP);
        require $viewFile;
        // The main view might have generated content OR just registered sections
        $viewContent = ob_get_clean();

        // If layout was set inside the view using $this->extend()
        if ($this->useLayout && $this->layout && !$this->shouldExcludeFromLayout($view)) {
            // Put whatever wasn't explicitly in a section into 'content'
            if (!isset($this->sections['content']) || empty($this->sections['content'])) {
                $this->sections['content'] = $viewContent;
            }
            $this->runComposers($this->layout);
            $content = $this->renderWithLayout($this->layout);
        } else {
            $content = $viewContent;
        }

        if ($return) {
            return $content;
        }

        echo $content;
    }

    /**
     * Check if view should be excluded from layout rendering
     */
    protected function shouldExcludeFromLayout(string $view): bool
    {
        $excludedViews = [
            'home/index',
            'welcome_message',
            'home/home',
            'welcome'
        ];
        
        return in_array($view, $excludedViews);
    }

    /**
     * Get full path to view file
     */
    protected function getViewPath(string $view): string
    {
        return $this->basePath . '/' . ltrim($view, '/') . '.php';
    }

    /**
     * Render layout with current sections
     */
    protected function renderWithLayout(string $layout): string
    {
        $layoutFile = $this->getLayoutPath($layout);

        if (!file_exists($layoutFile)) {
            // Fallback to purely content if layout doesn't exist
            return $this->sections['content'] ?? '';
        }

        ob_start();
        extract($this->viewData, EXTR_SKIP);
        require $layoutFile;
        return ob_get_clean();
    }

    /**
     * Get full path to layout file
     */
    protected function getLayoutPath(string $layout): string
    {
        $layout = preg_replace('/\.php$/', '', $layout);
        $layoutPath = $this->basePath . '/' . ltrim($layout, '/') . '.php';

        if (!file_exists($layoutPath)) {
            $layoutPath = $this->basePath . '/layouts/' . ltrim($layout, '/') . '.php';
        }

        return $layoutPath;
    }

    /**
     * Extend a layout (used inside views)
     */
    public function extend(string $layout): self
    {
        $this->layout = $layout;
        $this->useLayout = true;
        return $this;
    }

    /**
     * Specify no layout for this view
     */
    public function noLayout(): self
    {
        $this->useLayout = false;
        return $this;
    }

    /**
     * Start defining a section
     */
    public function section(string $name): void
    {
        if ($this->currentSection) {
            throw new LogicException("Cannot nest sections: already in section '{$this->currentSection}'");
        }

        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End current section
     */
    public function endSection(): void
    {
        if (!$this->currentSection) {
            throw new LogicException("No section started");
        }

        $content = ob_get_clean();
        $this->sections[$this->currentSection] = $content;
        $this->currentSection = null;
    }

    /**
     * Render a section (used inside layouts)
     */
    public function renderSection(string $name, bool $escape = false): string
    {
        if (!isset($this->sections[$name])) {
            return '';
        }

        $content = $this->sections[$name];

        if ($escape) {
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        return $content;
    }

    /**
     * Start pushing to a stack (scripts, styles)
     */
    public function push(string $name): void
    {
        if ($this->currentStack) {
            throw new LogicException("Cannot nest stacks: already in stack '{$this->currentStack}'");
        }

        $this->currentStack = $name;
        ob_start();
    }

    /**
     * End pushing to current stack
     */
    public function endPush(): void
    {
        if (!$this->currentStack) {
            throw new LogicException("No stack started");
        }

        $content = ob_get_clean();
        
        if (!isset($this->stacks[$this->currentStack])) {
            $this->stacks[$this->currentStack] = [];
        }
        
        $this->stacks[$this->currentStack][] = $content;
        $this->currentStack = null;
    }

    /**
     * Render a stack
     */
    public function renderStack(string $name): string
    {
        if (!isset($this->stacks[$name])) {
            return '';
        }

        return implode("\n", $this->stacks[$name]);
    }
}
