<?php

// admin/config/router.php - Simple Router for Admin Dashboard

// Prevent direct access
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted');
}

class Router
{
    private array $routes = [];
    private string $basePath;
    private array $middleware = [];
    
    public function __construct(string $basePath = '') 
    {
        $this->basePath = rtrim($basePath, '/');
    }
    
    public function get(string $path, $handler): void 
    {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post(string $path, $handler): void 
    {
        $this->addRoute('POST', $path, $handler);
    }
    
    public function put(string $path, $handler): void 
    {
        $this->addRoute('PUT', $path, $handler);
    }
    
    public function delete(string $path, $handler): void 
    {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    private function addRoute(string $method, string $path, $handler): void 
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->basePath . $path,
            'handler' => $handler,
            'pattern' => $this->pathToRegex($this->basePath . $path)
        ];
    }
    
    public function dispatch(): void 
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                if (preg_match($route['pattern'], $requestPath, $matches)) {
                    array_shift($matches); // Remove full match
                    
                    if (is_callable($route['handler'])) {
                        call_user_func_array($route['handler'], $matches);
                        return;
                    } elseif (is_string($route['handler'])) {
                        // Handle string-based routes (Controller@method)
                        $this->handleStringRoute($route['handler'], $matches);
                        return;
                    }
                }
            }
        }
        
        // No route found - 404
        $this->handle404();
    }
    
    private function pathToRegex(string $path): string 
    {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    private function handleStringRoute(string $handler, array $params): void 
    {
        if (strpos($handler, '@') !== false) {
            [$controllerClass, $method] = explode('@', $handler);
            
            if (class_exists($controllerClass) && method_exists($controllerClass, $method)) {
                call_user_func_array([$controllerClass, $method], $params);
                return;
            }
        }
        
        throw new Exception("Route handler not found: {$handler}");
    }
    
    private function handle404(): void 
    {
        http_response_code(404);
        include __DIR__ . '/../templates/errors/404.php';
    }
    
    public static function url(string $path = ''): string 
    {
        $basePath = '/northcity/admin';
        if (empty($path) || $path === '/') {
            return $basePath;
        }
        return $basePath . '/' . ltrim($path, '/');
    }
    
    public function middleware(array $middleware): self 
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }
}