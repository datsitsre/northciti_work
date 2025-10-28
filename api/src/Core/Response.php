<?php

// api/src/Core/Response.php - HTTP Response Handler

declare(strict_types=1);

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function json(array $data, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->header('Content-Type', 'application/json');
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function text(string $content, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->header('Content-Type', 'text/plain');
        $this->content = $content;
        return $this;
    }

    public function html(string $content, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->header('Content-Type', 'text/html');
        $this->content = $content;
        return $this;
    }

    public function redirect(string $url, int $status = 302): self
    {
        $this->statusCode = $status;
        $this->header('Location', $url);
        return $this;
    }

    public function download(string $filePath, string $filename = null): self
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File not found');
        }

        $filename = $filename ?? basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->header('Content-Type', $mimeType);
        $this->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->header('Content-Length', (string)filesize($filePath));
        $this->content = file_get_contents($filePath);

        return $this;
    }

    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Send content
        echo $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}