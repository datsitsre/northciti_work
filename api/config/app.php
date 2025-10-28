<?php
return [
    "name" => $_ENV["APP_NAME"] ?? "NorthCity News Platform",
    "env" => $_ENV["APP_ENV"] ?? "production",
    "debug" => $_ENV["APP_DEBUG"] === "true",
    "url" => $_ENV["APP_URL"] ?? "http://10.30.252.49/northcity",
    "base_path" => $_ENV["BASE_PATH"] ?? "/northcity",
    "timezone" => $_ENV["APP_TIMEZONE"] ?? "UTC",
    "locale" => $_ENV["APP_LOCALE"] ?? "en",
    "fallback_locale" => "en",
    "key" => $_ENV["APP_KEY"] ?? "",
    "cipher" => "AES-256-CBC",
];
