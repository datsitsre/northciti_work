<?php
return [
    "allowed_origins" => explode(",", $_ENV["CORS_ALLOWED_ORIGINS"] ?? "*"),
    "allowed_methods" => ["GET", "POST", "PUT", "DELETE", "OPTIONS", "PATCH", "HEAD"],
    "allowed_headers" => ["Content-Type", "Authorization", "X-Requested-With", "X-API-Key","Accept", "Origin", "X-Auth-Token", "X-API-Key"],
    "allow_credentials" => true,
    "max_age" => 86400,
];
