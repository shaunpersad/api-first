<?php


namespace Api\Exceptions;


use Api\Http\ErrorResponse;
use Exception;

class ApiException extends Exception {


    public $description;

    public static function make($message, $code = 500, $description = 'An unknown error occurred.') {

        $exception = new ApiException($message, $code);

        $exception->description = $description;

        return $exception;
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function toErrorResponse() {

        return ErrorResponse::make($this->message, $this->description, $this->code);
    }
} 