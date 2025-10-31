<?php

use App\FbaShippingService;
use DI\ContainerBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function DI\create;
use function DI\get;

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAttributes(true);
$containerBuilder->addDefinitions(array_merge(
    [
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
        'fba.client' => function (): ClientInterface {
            return new Client(['base_uri' => ' https://sellingpartnerapi-na.amazon.com/']);
        },
        'fba.service' => create(FbaShippingService::class)
            ->constructor(
                get('fba.client'),
                get(ValidatorInterface::class),
                get(LoggerInterface::class)
            ),
    ],
    require 'mocked_client.php',
));
return $containerBuilder->build();

