<?php

use Prokl\ServiceProvider\Tests\Cases\fixtures\Services\SampleService;
use Prokl\ServiceProvider\Tests\Cases\fixtures\Services\SampleWithArguments;

return [
    'parameters' => [
        'value' => [
            'rabbitmq.connection.class' => 'PhpAmqpLib\Connection\AMQPConnection',
            'rabbitmq.socket_connection.class' => 'PhpAmqpLib\Connection\AMQPSocketConnection',
            'rabbitmq.lazy.class' => 'PhpAmqpLib\Connection\AMQPLazyConnection',
            'rabbitmq.lazy.socket_connection.class' => 'PhpAmqpLib\Connection\AMQPLazySocketConnection',
            'rabbitmq.connection_factory.class' => 'Yngc0der\RabbitMq\RabbitMq\AMQPConnectionFactory',
            'rabbitmq.binding.class' => 'Yngc0der\RabbitMq\RabbitMq\Binding',
            'rabbitmq.producer.class' => 'Yngc0der\RabbitMq\RabbitMq\Producer',
            'rabbitmq.consumer.class' => 'Yngc0der\RabbitMq\RabbitMq\Consumer',
            'rabbitmq.multi_consumer.class' => '',
            'rabbitmq.dynamic_consumer.class' => '',
            'rabbitmq.batch_consumer.class' => '',
            'rabbitmq.anon_consumer.class' => '',
            'rabbitmq.rpc_client.class' => '',
            'rabbitmq.rpc_server.class' => '',
            'rabbitmq.logged.channel.class' => '',
            'rabbitmq.parts_holder.class' => 'Yngc0der\RabbitMq\RabbitMq\AmqpPartsHolder',
            'rabbitmq.fallback.class' => 'Yngc0der\RabbitMq\RabbitMq\Fallback',
        ],
        'readonly' => false,
    ],
    'services' => [
        'value' => [
            'foo.service' => [
                'constructor' => static function () {
                    return new SampleService();
                }
            ],
            'foo.service.ignore' => [
                'constructor' => static function () {
                    return new SampleService();
                },
                'ignore' => true
            ],
            'someGoodServiceName' => [
                'className' => SampleWithArguments::class,
                'constructorParams' => ['foo', 'bar'],
            ],
            'someModule.someServiceName' => [
                'className' => SampleWithArguments::class,
                'constructorParams' => static function (){
                    return ['foo', 'bar'];
                },
            ],
            'someModule.someAnotherServiceName' => [
                'constructor' => static function () {
                    return new SampleWithArguments('foo', 'bar');
                },
            ]
        ],
        'readonly' => false,
    ],
];
