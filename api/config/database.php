<?php
return [
    "default" => $_ENV["DB_CONNECTION"] ?? "mysql",
    "connections" => [
        "mysql" => [
            "driver" => "mysql",
            "host" => $_ENV["DB_HOST"] ?? "10.30.252.49",
            "port" => $_ENV["DB_PORT"] ?? "3306",
            "database" => $_ENV["DB_NAME"] ?? "northcity_db_2025",
            "username" => $_ENV["DB_USER"] ?? "root",
            "password" => $_ENV["DB_PASS"] ?? "",
            "charset" => "utf8mb4",
            "collation" => "utf8mb4_unicode_ci",
            "options" => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ]
    ]
];
