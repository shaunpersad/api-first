<?php


namespace Api\Http;


use App;
use Response;

class ErrorResponse {

    public static function make($code, $description = '', $status = 200, array $headers = array(), $options = 0 ) {

        $response = array(
            'content' => null,
            'error' => array(
                'flag' => true,
                'code' => $code,
                'description' => $description
            )
        );

        return Response::json($response, $status, $headers, $options);
    }
} 