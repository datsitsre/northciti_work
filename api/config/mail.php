<?php
return [
    "default" => $_ENV["MAIL_MAILER"] ?? "smtp",
    "mailers" => [
        "smtp" => [
            "transport" => "smtp",
            "host" => $_ENV["MAIL_HOST"] ?? "10.30.252.49",
            "port" => $_ENV["MAIL_PORT"] ?? 587,
            "encryption" => $_ENV["MAIL_ENCRYPTION"] ?? "tls",
            "username" => $_ENV["MAIL_USERNAME"],
            "password" => $_ENV["MAIL_PASSWORD"],
        ],
    ],
    "from" => [
        "address" => $_ENV["MAIL_FROM_ADDRESS"] ?? "noreply@10.30.252.49",
        "name" => $_ENV["MAIL_FROM_NAME"] ?? "News Platform",
    ],
];
