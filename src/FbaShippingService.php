<?php

namespace App;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;
use App\Dto\CreateFullfillmentOrderParameters;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FbaShippingService implements ShippingServiceInterface
{
    private const CREATE_ORDER_URI = '/fba/outbound/2020-07-01/fulfillmentOrders';
    private const GET_ORDER_URI = '/fba/outbound/2020-07-01/fulfillmentOrders/{orderId}';

    /**
     * @param ClientInterface $client
     * @param ValidatorInterface $validator
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ClientInterface $client,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * @param AbstractOrder $order
     * @param BuyerInterface $buyer
     * @return string
     * @throws GuzzleException
     */
    public function ship(AbstractOrder $order, BuyerInterface $buyer): string
    {
        $order->load();
        $parameters = (new CreateFullfillmentOrderParameters())
            ->fillFromOrderData($order->data)
            ->fillFromBuyer($buyer);
        $violations = $this->validator->validate($parameters);
        if (count($violations) > 0) {
            throw new InvalidArgumentException(sprintf('Validation failed: %s', (string) $violations));
        }

        $orderId = $parameters->sellerFulfillmentOrderId;
        $payload = $parameters->toPayload();
        $createResponse = $this->client->request(
            'POST',
            self::CREATE_ORDER_URI,
            [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json'
                ],
                'json' => $payload,
            ]
        );

        $createData = json_decode($createResponse->getBody(), true);

        if (!empty($createData['errors'])) {
            $this->logger->warning('Partial FBA errors: ' . json_encode($createData['errors']));
        }

        $orderResponse = $this->client->request('GET', str_replace('{orderId}', $orderId, self::GET_ORDER_URI));
        $orderData = json_decode($orderResponse->getBody(), true);

        $trackingNumber = $orderData['payload']['fulfillmentShipments'][0]['fulfillmentShipmentPackage'][0]['trackingNumber'] ?? null;

        if (empty($trackingNumber)) {
            throw new RuntimeException('No tracking number available');
        }

        return $trackingNumber;
    }
}
