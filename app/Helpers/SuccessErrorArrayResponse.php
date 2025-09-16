<?php
namespace App\Helpers;

class SuccessErrorArrayResponse
{
    const DEFAULT_SUCCESS_MESSAGE = "Success";
    const DEFAULT_ERROR_MESSAGE = "Error Occured";

    public static function errorResponse(string $message = self::DEFAULT_ERROR_MESSAGE, mixed $data = null) : array
    {
        return [
            "status" => "error",
            "code" => 500,
            "message" => $message,
            "data" => $data,
        ];
    }

    public static function successResponse(string $message = self::DEFAULT_SUCCESS_MESSAGE, mixed $data = null) : array
    {
        return [
            "status" => "success",
            "code" => 200,
            "message" => $message,
            "data" => $data
        ];
    }
}
