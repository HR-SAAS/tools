<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    //

    protected function success($data, $message = ''): \Illuminate\Http\JsonResponse
    {
        return $this->transformer(0, $message, $data);

    }

    protected function error($message, $data = []): \Illuminate\Http\JsonResponse
    {
        return $this->transformer(1, $message, $data);
    }

    protected function transformer($code, $message, $data): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ]);
    }
}

