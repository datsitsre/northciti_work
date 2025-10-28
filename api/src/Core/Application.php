<?php

// api/src/Core/Application.php - Updated to register new services

declare(strict_types=1);

namespace App\Core;

use App\Core\Container;
use App\Core\Router;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CorsMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\LoggingMiddleware;
use App\Middleware\AuthMiddleware;

class Application
{
    private Container $container;
    private Router $router;
    private array $middleware = [];

    public function __construct()
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
        
        $this->registerServices();
        $this->registerMiddleware();
        $this->loadRoutes();
    }

    private function registerServices(): void
    {
        // Register database connection
        $this->container->singleton(Database::class, function () {
            return new Database([
                'host' => $_ENV['DB_HOST'],
                'dbname' => $_ENV['DB_NAME'],
                'username' => $_ENV['DB_USER'],
                'password' => $_ENV['DB_PASS'],
                'charset' => 'utf8mb4',
                'options' => [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ]);
        });

        // Register models
        $this->container->singleton(\App\Models\User::class);
        $this->container->singleton(\App\Models\News::class);
        $this->container->singleton(\App\Models\Event::class);
        $this->container->singleton(\App\Models\Comment::class);
        $this->container->singleton(\App\Models\Media::class);
        $this->container->singleton(\App\Models\Tag::class);
        $this->container->singleton(\App\Models\Category::class);
        $this->container->singleton(\App\Models\Settings::class);

        // Register core services
        $this->container->singleton(\App\Services\CacheService::class);
        $this->container->singleton(\App\Services\JWTService::class);
        $this->container->singleton(\App\Services\EmailService::class);
        $this->container->singleton(\App\Services\FileUploadService::class);
        
        // Register moderation services
        $this->container->singleton(\App\Services\ModerationService::class);
        $this->container->singleton(\App\Services\CommentModerationService::class);
        $this->container->singleton(\App\Services\ModerationQueueService::class);

        // Register validators
        $this->container->singleton(\App\Validators\NewsValidator::class);
        $this->container->singleton(\App\Validators\EventValidator::class);
        $this->container->singleton(\App\Validators\CommentValidator::class);
        $this->container->singleton(\App\Validators\TagValidator::class);
        
        // Register controllers
        $this->container->singleton(\App\Controllers\Api\NewsController::class);
        $this->container->singleton(\App\Controllers\Api\EventController::class);
        $this->container->singleton(\App\Controllers\Api\MediaController::class);
        $this->container->singleton(\App\Controllers\Api\CommentController::class);
        $this->container->singleton(\App\Controllers\Admin\CommentModerationController::class);
        $this->container->singleton(\App\Controllers\Admin\ContentModerationController::class);
    }

    private function registerMiddleware(): void
    {
        $this->middleware = [
            CorsMiddleware::class,
            LoggingMiddleware::class,
            RateLimitMiddleware::class,
            // SecurityMiddleware::class,
            // RoleMiddleware::class,
            // ValidationMiddleware::class,
        ];
    }

    private function loadRoutes(): void
    {
        // Load route files
        require_once __DIR__ . '/../../routes/api.php';
        require_once __DIR__ . '/../../routes/auth.php';
        require_once __DIR__ . '/../../routes/admin.php';
        
        // Pass router to route files
        $router = $this->router;
        
        // Include route definitions
        if (function_exists('registerApiRoutes')) {
            registerApiRoutes($router);
        }
        if (function_exists('registerAuthRoutes')) {
            registerAuthRoutes($router);
        }
        if (function_exists('registerAdminRoutes')) {
            registerAdminRoutes($router);
        }
    }

    public function handle(Request $request): Response
    {
        try {
            // Apply global middleware
            foreach ($this->middleware as $middlewareClass) {
                if (is_object($middlewareClass)) {
                    $middleware = $middlewareClass;
                } elseif (is_string($middlewareClass)) {
                    $middleware = $this->container->get($middlewareClass);
                } else {
                    throw new \InvalidArgumentException('Invalid middleware type. Expected string or object.');
                }
                $request = $middleware->process($request);
            }

            // Route the request
            return $this->router->dispatch($request);
            
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->createErrorResponse($e->getMessage(), $e->getErrors(), 400);
        } catch (\App\Exceptions\AuthenticationException $e) {
            return $this->createErrorResponse('Authentication required', [], 401);
        } catch (\App\Exceptions\AuthorizationException $e) {
            return $this->createErrorResponse('Access denied', [], 403);
        } catch (\App\Exceptions\NotFoundException $e) {
            return $this->createErrorResponse('Resource not found', [], 404);
        } catch (\App\Exceptions\RateLimitException $e) {
            return $this->createErrorResponse('Rate limit exceeded', [], 429);
        } catch (\Throwable $e) {
            // Log the error
            error_log($e->getMessage() . "\n" . $e->getTraceAsString());
            
            return $this->createErrorResponse('Internal server error'.$e->getMessage() . "\n" . $e->getTraceAsString(), [], 500);
        }
    }

    private function createErrorResponse(string $message, array $errors = [], int $status = 400): Response
    {
        $response = new Response();
        return $response->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}