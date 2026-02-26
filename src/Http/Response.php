<?php

namespace WCDO\Http;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        self::headers($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data, int $status = 200): void
    {
        self::json(['success' => true, 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400): void
    {
        self::json(['success' => false, 'error' => $message], $status);
    }

    public static function notFound(string $message = 'Ressource introuvable'): void
    {
        self::error($message, 404);
    }

    private static function headers(int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
}
