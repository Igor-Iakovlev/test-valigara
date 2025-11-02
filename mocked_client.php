<?php

use App\FbaShippingService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function DI\create;
use function DI\get;

return [
    'mock.client' => function (): ClientInterface {
        $mock = new MockHandler([
            New Response(200, [], json_encode([
                'errors' => [],
            ])),
            new Response(200, [], json_encode([
                'payload' => [
                    'fulfillmentShipments' => [
                        [
                            'fulfillmentShipmentPackage' => [
                                [
                                    'trackingNumber' => '1Z999AA1234567890',
                                ],
                            ],
                        ],
                    ],
                ],
                'errors' => [],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack, 'base_uri' => 'http://fake-fba.local/']);
    },
    'fba.mock' => create(FbaShippingService::class)
        ->constructor(
            get('mock.client'),
            get(ValidatorInterface::class),
            get(RateLimiterFactory::class),
            get(NormalizerInterface::class),
            get(LoggerInterface::class)
        ),
];
