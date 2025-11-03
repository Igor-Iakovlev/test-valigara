<?php

namespace App\ClientBuilder;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class MockClientBuilder extends MockHandler
{
    /**
     * @param string $sellerId
     * @param string $tracking
     * @return $this
     */
    public function withSuccess(string $sellerId = 'MCF-16400', string $tracking = '1Z999AA1234567890'): self
    {
        $this->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->createSuccessPayload()
        ));

        $this->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->getFulfillmentOrderPayload($sellerId, $tracking)
        ));

        return $this;
    }

    /**
     * @param string $sellerId
     * @param string $tracking
     * @return $this
     */
    public function withPartialError(string $sellerId = 'MCF-16400', string $tracking = '1Z999AA1234567890'): self
    {
        $this->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->createPartialErrorPayload()
        ));

        $this->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->getFulfillmentOrderPayload($sellerId, $tracking)
        ));
        return $this;
    }

    /**
     * @param string $sellerId
     * @return $this
     */
    public function withErrorStatus(string $sellerId = 'MCF-16400'): self
    {
        $this->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->createSuccessPayload()
        ));

        $this->append(new Response(
            400,
            ['Content-Type' => 'application/json'],
            json_encode([
                'errors' => [
                    ['code' => 'InvalidInput', 'message' => 'Order not found or invalid status'],
                ],
        ])));

        return $this;
    }

    /**
     * @param string $sellerId
     * @return $this
     */
    public function withoutTracking(string $sellerId = 'MCF-16400'): self
    {
        $this->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->createSuccessPayload()
        ));

        $this->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->getFulfillmentOrderPayload($sellerId, null, true)
        ));

        return $this;
    }


    /**
     * @return Client
     */
    public function build(): Client
    {
        $handlerStack = HandlerStack::create($this);
        return new Client(['handler' => $handlerStack, 'base_uri' => 'http://fake-fba.local/']);
    }

    /**
     * @return string
     */
    private function createSuccessPayload(): string
    {
        return json_encode([
            'payload' => [
                'fulfillmentOrder' => ['sellerFulfillmentOrderId' => 'MCF-16400', 'fulfillmentOrderStatus' => 'Processing'],
                'fulfillmentOrderItems' => [['sellerSku' => 'SKU1', 'quantity' => ['value' => 1]]],
            ],
            'errors' => [],
        ]);
    }

    /**
     * @return string
     */
    private function createPartialErrorPayload(): string
    {
        return json_encode([
            'payload' => [
                'fulfillmentOrder' => ['sellerFulfillmentOrderId' => 'MCF-16400', 'fulfillmentOrderStatus' => 'Processing'],
                'fulfillmentOrderItems' => [
                    ['sellerSku' => 'SKU1', 'quantity' => ['value' => 1]],
                    ['sellerSku' => 'INVALID-SKU', 'unfulfillableQuantity' => ['value' => 1]],
                ],
            ],
            'errors' => [
                ['code' => 'SkuNotFound', 'message' => 'SKU INVALID-SKU not in inventory'],
            ],
        ]);
    }

    /**
     * @param string $sellerId
     * @param string|null $tracking
     * @param bool $noPackages
     * @return string
     */
    private function getFulfillmentOrderPayload(
        string $sellerId,
        ?string $tracking = null,
        bool $noPackages = false
    ): string {
        $shipments = [
            [
                'fulfillmentShipmentId' => 'AMZ-SH-123',
                'shipmentStatus' => 'Shipped',
                'fulfillmentShipmentPackage' => $noPackages ? [] : [
                    ['packageNumber' => 1, 'trackingNumber' => $tracking],
                ],
            ],
        ];

        return json_encode([
            'payload' => [
                'fulfillmentOrder' => ['sellerFulfillmentOrderId' => $sellerId, 'fulfillmentOrderStatus' => 'Shipped'],
                'fulfillmentShipments' => $shipments,
            ],
            'errors' => [],
        ]);
    }
}
