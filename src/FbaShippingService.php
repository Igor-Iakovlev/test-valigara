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
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FbaShippingService implements ShippingServiceInterface
{
    use NormalizerAwareTrait;

    private const CREATE_ORDER_URI = '/fba/outbound/2020-07-01/fulfillmentOrders';
    private const GET_ORDER_URI = '/fba/outbound/2020-07-01/fulfillmentOrders/{orderId}';

    private LimiterInterface $createOrderLimit;
    private LimiterInterface $getOrderLimit;

    /**
     * @param ClientInterface $client
     * @param ValidatorInterface $validator
     * @param RateLimiterFactory $rateLimiterFactory
     * @param NormalizerInterface $normalizer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ClientInterface $client,
        private ValidatorInterface $validator,
        private RateLimiterFactory $rateLimiterFactory,
        NormalizerInterface $normalizer,
        private LoggerInterface $logger
    ) {
        $this->setNormalizer($normalizer);
        $this->createOrderLimit = $this->rateLimiterFactory->create('create');
        $this->getOrderLimit = $this->rateLimiterFactory->create('get');
    }

    /**
     * @param AbstractOrder $order
     * @param BuyerInterface $buyer
     * @return string
     * @throws GuzzleException
     * @throws ExceptionInterface
     */
    public function ship(AbstractOrder $order, BuyerInterface $buyer): string
    {
        $parameters = $this->prepareParameters($order, $buyer);
        $this->createFullfillmentOrder($parameters);
        return $this->getTrackingNumber($parameters->sellerFulfillmentOrderId);
    }

    /**
     * @param AbstractOrder $order
     * @param BuyerInterface $buyer
     * @return CreateFullfillmentOrderParameters
     */
    private function prepareParameters(AbstractOrder $order, BuyerInterface $buyer): CreateFullfillmentOrderParameters
    {
        $order->load();
        $parameters = (new CreateFullfillmentOrderParameters())
            ->fillFromOrderData($order->data)
            ->fillFromBuyer($buyer);
        $violations = $this->validator->validate($parameters);
        if (count($violations) > 0) {
            throw new InvalidArgumentException(sprintf('Validation failed: %s', (string) $violations));
        }
        return $parameters;
    }

    /**
     * @param CreateFullfillmentOrderParameters $parameters
     * @throws ExceptionInterface
     * @throws GuzzleException
     */
    private function createFullfillmentOrder(CreateFullfillmentOrderParameters $parameters)
    {
        $payload = $this->normalizer->normalize($parameters);
        $this->createOrderLimit->reserve(1)->wait();
        $response = $this->client->request(
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
        $data = json_decode($response->getBody(), true);
        if (!empty($data['errors'])) {
            $this->logger->warning('Partial FBA errors: ' . json_encode($data['errors']));
        }
    }

    /**
     * @param string $orderId
     * @return string
     * @throws GuzzleException
     */
    private function getTrackingNumber(string $orderId): string
    {
        $this->getOrderLimit->reserve(1)->wait();
        $orderResponse = $this->client->request('GET', str_replace('{orderId}', $orderId, self::GET_ORDER_URI));
        $orderData = json_decode($orderResponse->getBody(), true);

        $trackingNumber = $orderData['payload']['fulfillmentShipments'][0]['fulfillmentShipmentPackage'][0]['trackingNumber'] ?? null;

        if (empty($trackingNumber)) {
            throw new RuntimeException('No tracking number available');
        }

        return $trackingNumber;
    }
}
