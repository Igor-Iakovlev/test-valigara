<?php

namespace App\Data;

use InvalidArgumentException;
use RuntimeException;

class FileOrder extends AbstractOrder
{
    /**
     * @param int $id
     * @return array
     */
    protected function loadOrderData(int $id): array
    {
        $filePath = sprintf('%s/../../mock/order.%s.json', __DIR__, $id);
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Order file not found: {$filePath}");
        }
        $jsonContent = file_get_contents($filePath);
        if ($jsonContent === false) {
            throw new RuntimeException("Failed to read order file: {$filePath}");
        }

        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in order file: " . json_last_error_msg());
        }

        if (!isset($data['order_id']) || (int) $data['order_id'] !== $id) {
            throw new InvalidArgumentException("Order ID mismatch in file: expected {$id}");
        }

        return $data;
    }
}
