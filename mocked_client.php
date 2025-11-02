<?php

use App\FbaShippingService;
use App\Support\MockClientBuilder;
use GuzzleHttp\ClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function DI\autowire;
use function DI\create;
use function DI\get;

return [
    MockClientBuilder::class => autowire(),
    'mock.client' => function (ContainerInterface $di): ClientInterface {
        /** @var MockClientBuilder $clientBuilder */
        $clientBuilder = $di->get(MockClientBuilder::class);
        return $clientBuilder->withSuccess()->build();
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
