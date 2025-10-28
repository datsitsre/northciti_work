<?php
// api/cors.php - Handles CORS preflight requests

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-Auth-Token, X-API-Key');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Credentials: true');

// Return 200 for OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}