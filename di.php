<?php

use App\FbaShippingService;
use DI\ContainerBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function DI\create;
use function DI\get;

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAttributes(true);
$containerBuilder->addDefinitions(array_merge(
    [
        Serializer::class => function (): Serializer {
            $encoders = [new JsonEncoder()];
            $normalizers = [
                new BackedEnumNormalizer(),
                new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s']),
                new ObjectNormalizer(
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    [
                        AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                    ]
                ),
            ];
            return new Serializer($normalizers, $encoders);
        },
        NormalizerInterface::class => get(Serializer::class),
        LoggerInterface::class => function (): LoggerInterface {
            $logger = new Logger('fba');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/logs/fba.log', Level::Info));
            return $logger;
        },
        ValidatorInterface::class => function (): ValidatorInterface {
            return Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator();
        },
        RateLimiterFactory::class => function (): RateLimiterFactory {
            $config = [
                'id' => 'rate_limit',
                'policy' => 'token_bucket',
                'limit' => 30,
                'rate' => [
                    'interval' => '1 second',
                    'amount' => 2,
                ],
            ];
            return new RateLimiterFactory(
                $config,
                new InMemoryStorage(),
                new LockFactory(
                    new InMemoryStore(),
                ),
            );
        },
        'fba.client' => function (): ClientInterface {
            return new Client(['base_uri' => ' https://sellingpartnerapi-na.amazon.com/']);
        },
        'fba.service' => create(FbaShippingService::class)
            ->constructor(
                get('fba.client'),
                get(ValidatorInterface::class),
                get(RateLimiterFactory::class),
                get(NormalizerInterface::class),
                get(LoggerInterface::class),
            ),
    ],
    require 'mocked_client.php',
));

return $containerBuilder->build();

