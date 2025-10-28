<?php
// api/src/Controllers/Base/BaseController.php - Simple Version Without Singleton

declare(strict_types=1);

namespace App\Controllers\Base;

use App\Core\Response;
use App\Core\Database;

abstract class BaseController
{
    protected Response $response;
    protected \App\Core\Database $db;

    public function __construct()
    {
        $this->response = new Response();
        // Don't try to initialize database here - it will be injected if needed
    }

    public function setDatabase(Database $db): void
    {
        $this->db = $db;
    }

    protected function getDatabase(): ?Database
    {
        return $this->db;
    }

    protected function successResponse(array $data = [], string $message = '', int $status = 200): Response
    {
        return $this->response->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    protected function errorResponse(string $message, array $errors = [], int $status = 400): Response
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    protected function paginatedResponse(array $data, array $pagination, array $meta = []): Response
    {
        $responseData = [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'pagination' => $pagination
            ], $meta)
        ];
        
        return $this->response->json($responseData);
    }

    protected function foramtDatetime(string $dateTime): string
    {
        $newDateTime = null;

        if (isset($dateTime)) {
            $timestamp = strtotime($dateTime);
            if ($timestamp !== false) {
                $newDateTime = date("Y-m-d H:i:s", $timestamp);
            }
        }

        return $newDateTime;
    }

}