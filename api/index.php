<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

// Load CORS configuration
#$corsConfig = require_once __DIR__ . '/../config/cors.php';

// Apply CORS headers if enabled

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

// Set error reporting
if ($_ENV["APP_ENV"] === "development") {
    error_reporting(E_ALL);
    ini_set("display_errors", "1");
} else {
    error_reporting(0);
    ini_set("display_errors", "0");
}

// Set timezone
date_default_timezone_set($_ENV["APP_TIMEZONE"] ?? "UTC");

try {
    // Initialize application
    $app = new Application();
    
    // Create request object
    $request = Request::createFromGlobals();
    
    // Process request and get response
    $response = $app->handle($request);
    
    // Send response
    $response->send();
    
} catch (Throwable $e) {
    // Handle uncaught exceptions
    $response = new Response();
    
    if ($_ENV["APP_ENV"] === "development") {
        $response->json([
            "success" => false,
            "message" => "Internal Server Error",
            "error" => $e->getMessage(),
            "trace" => $e->getTraceAsString()
        ], 500);
    } else {
        $response->json([
            "success" => false,
            "message" => "Internal Server Error"
        ], 500);
    }
    
    $response->send();
}
