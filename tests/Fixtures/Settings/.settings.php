<?php

return array (
    'utf_mode' =>
        array (
            'value' => true,
            'readonly' => true,
        ),
    'cache_flags' =>
        array (
            'value' =>
                array (
                    'config_options' => 3600.0,
                    'site_domain' => 3600.0,
                ),
            'readonly' => false,
        ),
    'cookies' =>
        array (
            'value' =>
                array (
                    'secure' => false,
                    'http_only' => true,
                ),
            'readonly' => false,
        ),
    'exception_handling' =>
        array (
            'value' =>
                array (
                    'handled_errors_types' => 4437,
                    'exception_errors_types' => 4437,
                    'ignore_silence' => false,
                    'assertion_throws_exception' => true,
                    'assertion_error_type' => 256,
                    'log' => array(
                        'class_name' => '\Bex\Monolog\ExceptionHandlerLog',
                        'settings' => array(
                            'logger' => 'app',
                            'rules' => [],
                            'context' => [],
                        ),
                    ),
                ),
            'readonly' => false,
        ),
    'cache' => '',
    'rabbitmq' => [
        'value' => [
            'connections' => [
                'default' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/',
                    'lazy' => false,
                    'connection_timeout' => 3.0,
                    'read_write_timeout' => 3.0,
                    'keepalive' => false,
                    'heartbeat' => 0,
                    'use_socket' => true,
                ],
            ],
            'producers' => [
                'upload_picture' => [
                    'connection' => 'default',
                    'exchange_options' => [
                        'name' => 'upload_picture',
                        'type' => 'direct',
                    ],
                ],
            ],
            'consumers' => [
                'upload_picture' => [
                    'connection' => 'default',
                    'exchange_options' => [
                        'name' => 'upload_picture',
                        'type' => 'direct',
                    ],
                    'queue_options' => [
                        'name' => 'upload_picture',
                    ],
                    'callback' => 'Yngc0der\RabbitMq\Consumers\UploadPictureConsumer',
                ],
            ],
        ],
        'readonly' => false,
    ],
);
