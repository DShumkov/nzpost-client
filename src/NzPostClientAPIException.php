<?php

namespace DShumkov\NzPostClient;

use Exception;
use Throwable;

class NzPostClientAPIException extends Exception
{
    public function __construct($code = 0)
    {
        $message = $this->handleWithAPIErrors($code);
        parent::__construct($message, $code);
    }

    protected function handleWithAPIErrors($responseCode)
    {
        switch ($responseCode) {
            case 400:
                return '400: Bad request';
            case 401:
                return '401: User not authenticated';
            case 403:
                return '403: Bad OAuth request';
            case 404:
                return '404: Not found';
            default:
                return $responseCode.': API error.';
        }
    }
}