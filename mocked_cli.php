<?php

use App\Data\FileBuyer;
use App\Data\FileOrder;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/vendor/autoload.php';
$container = require 'di.php';
$service = $container->get('fba.mock');
$order = new FileOrder(16400);
$buyer = new FileBuyer();
$buyer->load(29664);
$result = $service->ship($order, $buyer);
$logger = $container->get(LoggerInterface::class);
$logger->info($result);
echo $result;
