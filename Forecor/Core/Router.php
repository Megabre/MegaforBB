<?php

declare(strict_types=1);

namespace Forecor\Core;

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class Router
{
    public const ROUTES_CACHE_FILENAME = 'routes_core_compiled.php';

    /** Eski sürüm önbellek dosyası (temizlikte silinir) */
    public const ROUTES_LEGACY_CACHE_FILENAME = 'routes_compiled.php';

    protected RouteCollection $routes;
    protected string $basePath = '';
    protected int $routeIndex = 0;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
        $this->routes = new RouteCollection();
    }

    /** Symfony RouteCollection (eklentiler için). */
    public function getCollection(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Önbellek dosyasından rotaları yükle (web.php çalıştırılmaz).
     * @return bool Başarılıysa true, dosya yok/geçersizse false
     */
    public function loadFromCompiledFile(string $cachePath): bool
    {
        if (!is_file($cachePath)) {
            return false;
        }
        try {
            $definitions = require $cachePath;
            if (!is_array($definitions) || $definitions === []) {
                return false;
            }
            $this->routeIndex = 0;
            $this->routes = new RouteCollection();
            foreach ($definitions as $def) {
                $this->addRouteFromDefinition($def);
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Mevcut RouteCollection'ı önbellek dosyasına yazar.
     */
    public function dumpToCompiledFile(string $cachePath): void
    {
        $definitions = $this->exportDefinitions();
        $dir = dirname($cachePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $content = "<?php\n// Compiled routes – do not edit. Clear cache in admin to regenerate.\nreturn " . var_export($definitions, true) . ";\n";
        @file_put_contents($cachePath, $content, LOCK_EX);
    }

    /**
     * RouteCollection'dan dump edilebilir tanım dizisi üretir.
     * @return array<int, array{path: string, method: string, handler: string, param_order: array, requirements: array}>
     */
    protected function exportDefinitions(): array
    {
        $out = [];
        foreach ($this->routes as $name => $route) {
            $defaults = $route->getDefaults();
            $handler = $defaults['_handler'] ?? '';
            $paramOrder = $defaults['_param_order'] ?? [];
            $methods = $route->getMethods();
            $method = $methods[0] ?? 'GET';
            $out[] = [
                'path' => $route->getPath(),
                'method' => $method,
                'handler' => $handler,
                'param_order' => is_array($paramOrder) ? $paramOrder : [],
                'requirements' => $route->getRequirements(),
            ];
        }
        return $out;
    }

    /**
     * Dump tanımından tek rota ekler (loadFromCompiledFile için).
     */
    protected function addRouteFromDefinition(array $def): void
    {
        $path = $def['path'] ?? '/';
        $method = $def['method'] ?? 'GET';
        $handler = $def['handler'] ?? '';
        $paramOrder = $def['param_order'] ?? [];
        $requirements = $def['requirements'] ?? [];
        $name = '__forecor_' . (++$this->routeIndex);
        $route = new Route($path, ['_handler' => $handler, '_param_order' => $paramOrder], $requirements, [], '', [], [$method]);
        $this->routes->add($name, $route);
    }

    public function get(string $uri, string $handler): self
    {
        $this->addRoute('GET', $uri, $handler);
        return $this;
    }

    public function post(string $uri, string $handler): self
    {
        $this->addRoute('POST', $uri, $handler);
        return $this;
    }

    protected function addRoute(string $method, string $uri, string $handler): void
    {
        $path = $this->normalizePath($uri);
        $requirements = [];
        $path = $this->forecorPathToSymfony($path, $requirements);
        $paramOrder = $this->extractParamOrder($path);
        $name = '__forecor_' . (++$this->routeIndex);
        $route = new Route($path, ['_handler' => $handler, '_param_order' => $paramOrder], $requirements, [], '', [], [$method]);
        $this->routes->add($name, $route);
    }

    /** Path'teki parametre adlarını sırayla döndürür (controller argüman sırası için). */
    protected function extractParamOrder(string $path): array
    {
        if (!preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', $path, $m)) {
            return [];
        }
        return $m[1];
    }

    /**
     * Forecor path formatını Symfony'ye uyarla: {*path} → {path} + requirement .+
     * {param} aynı kalır.
     */
    protected function forecorPathToSymfony(string $path, array &$requirements): string
    {
        if (preg_match('#\{\*([a-zA-Z_][a-zA-Z0-9_]*)\}#', $path, $m)) {
            $param = $m[1];
            $path = str_replace($m[0], '{' . $param . '}', $path);
            $requirements[$param] = '.+';
        }
        return $path;
    }

    /**
     * HTTP method ve URI'ye göre eşleme. Önce Symfony UrlMatcher kullanılır.
     * @return array{handler: string, params: array<string, string>}|null
     */
    public function match(string $method, string $uri): ?array
    {
        $uri = $uri === '' ? '/' : '/' . trim($uri, '/');
        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath)) ?: '/';
        }
        $uri = $uri === '' ? '/' : $uri;
        // Gelen path'teki segmentleri Türkçe/özel karakterden arındır (platform bağımsız, eski linkler çalışsın)
        if (function_exists('url_path_sanitize')) {
            $uri = '/' . trim(url_path_sanitize(trim($uri, '/')), '/');
            $uri = $uri === '' ? '/' : $uri;
        }

        $context = new RequestContext();
        $context->setMethod($method);
        $context->setBaseUrl($this->basePath);
        $context->setPathInfo($uri);
        $context->setHost($_SERVER['HTTP_HOST'] ?? 'localhost');
        $context->setScheme(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');

        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $attributes = $matcher->match($uri);
        } catch (ResourceNotFoundException | MethodNotAllowedException $e) {
            return null;
        }

        $handler = $attributes['_handler'] ?? null;
        if ($handler === null) {
            return null;
        }

        $paramOrder = $attributes['_param_order'] ?? [];
        $params = [];
        if (is_array($paramOrder)) {
            foreach ($paramOrder as $name) {
                if (isset($attributes[$name])) {
                    $params[$name] = $attributes[$name];
                }
            }
        }
        // Sıralı dizi (Application controller->method(...$params) için)
        $paramsOrdered = array_values($params);

        return ['handler' => $handler, 'params' => $paramsOrdered];
    }

    protected function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * Eski pathToRegex artık kullanılmıyor (Symfony eşleme). Geri uyumluluk için bırakıldı.
     */
    protected function pathToRegex(string $path): string
    {
        $path = preg_replace('#\{(\*[a-zA-Z_][a-zA-Z0-9_]*)\}#', '___CATCHALL___', $path);
        $path = preg_quote($path, '#');
        $path = str_replace('___CATCHALL___', '(?P<path>.+)', $path);
        $path = preg_replace('#\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $path . '$#';
    }
}
