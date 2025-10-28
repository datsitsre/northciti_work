<?php

// api/src/Core/Router.php - Request Router

declare(strict_types=1);

namespace App\Core;

use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function patch(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'middleware' => $middleware,
            'pattern' => $this->pathToRegex($path)
        ];
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousMiddleware = $this->middleware;
        $this->middleware = array_merge($this->middleware, $middleware);
        
        $previousRoutes = $this->routes;
        $this->routes = [];
        
        $callback($this);
        
        $groupRoutes = $this->routes;
        $this->routes = $previousRoutes;
        $this->middleware = $previousMiddleware;
        
        foreach ($groupRoutes as $route) {
            $route['path'] = $this->normalizePath($prefix . $route['path']);
            $route['middleware'] = array_merge($middleware, $route['middleware']);
            $route['pattern'] = $this->pathToRegex($route['path']);
            $this->routes[] = $route;
        }
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $this->normalizePath($request->getPath());

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $matches = [];
            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract parameters
                $params = [];
                for ($i = 1; $i < count($matches); $i++) {
                    $params[] = $matches[$i];
                }

                // Apply route middleware
                foreach ($route['middleware'] as $middlewareItem) {
                    // Check if it's already an instance or a class name
                    if (is_object($middlewareItem)) {
                        $middleware = $middlewareItem;
                    } elseif (is_string($middlewareItem)) {
                        $middleware = $this->container->get($middlewareItem);
                    } else {
                        throw new \InvalidArgumentException('Invalid middleware type. Expected string or object.');
                    }
                    
                    $request = $middleware->process($request);
                }

                // Execute handler
                return $this->executeHandler($route['handler'], $request, $params);
            }
        }

        throw new NotFoundException('Route not found');
    }

    private function executeHandler($handler, Request $request, array $params): Response
    {
        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controllerClass, $method] = explode('@', $handler);
            $controller = $this->container->get($controllerClass);
            return $controller->$method($request, ...$params);
        }

        if (is_callable($handler)) {
            return $handler($request, ...$params);
        }

        throw new \InvalidArgumentException('Invalid route handler');
    }

    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }

    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}