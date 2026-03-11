<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cross-Origin Resource Sharing (CORS) Configuration
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors extends BaseConfig
{
    /**
     * The default CORS configuration.
     *
     * @var array{
     *      allowedOrigins: list<string>,
     *      allowedOriginsPatterns: list<string>,
     *      supportsCredentials: bool,
     *      allowedHeaders: list<string>,
     *      exposedHeaders: list<string>,
     *      allowedMethods: list<string>,
     *      maxAge: int,
     *  }
     */
    public array $default = [
        /**
         * Origins for the `Access-Control-Allow-Origin` header.
         * Allows all localhost origins and the WAMP frontend URL.
         */
        'allowedOrigins' => [
            'https://employee-profile.webetron.in',
            'http://localhost',
            'http://localhost:80',
            'http://localhost:8080',
            'http://localhost:3000',
            'http://127.0.0.1',
            'http://127.0.0.1:80',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:3000',
        ],

        /**
         * Regex patterns — allow any localhost port for development flexibility.
         */
        'allowedOriginsPatterns' => [
            'https?://employee-profile\.webetron\.in',
            'http://localhost(:\d+)?',
            'http://127\.0\.0\.1(:\d+)?',
        ],

        'supportsCredentials' => false,

        /**
         * Headers the browser is allowed to send.
         */
        'allowedHeaders' => [
            'Origin',
            'Accept',
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'X-CSRF-Token',
        ],

        'exposedHeaders' => [],

        /**
         * HTTP methods allowed for cross-origin requests.
         */
        'allowedMethods' => [
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'OPTIONS',
        ],

        'maxAge' => 7200,
    ];
}
