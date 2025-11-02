<?php

use App\Data\FileBuyer;
use App\Data\FileOrder;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/vendor/autoload.php';
$container = require 'di.php';

$args = $argv;
$scriptName = array_shift($args);
if (count($args) < 2) {
    echo "Usage: php {$scriptName} <order_id> <buyer_id>\n";
    echo "Example: php {$scriptName} 16400 29664\n";
    exit(1);
}

$orderId = $args[0];
$buyerId = $args[1];

$service = $container->get('fba.mock');
$order = new FileOrder($orderId);
$buyer = new FileBuyer();
$buyer->load($buyerId);
$result = $service->ship($order, $buyer);
$logger = $container->get(LoggerInterface::class);
$logger->info($result);
echo $result;
