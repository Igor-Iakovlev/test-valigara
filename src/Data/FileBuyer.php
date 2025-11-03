<?php

namespace App\Data;

use InvalidArgumentException;
use RuntimeException;

class FileBuyer implements BuyerInterface
{
    public array $container = [];

    /**
     * @param int $id
     * @return $this
     */
    public function load(int $id): self
    {
        $filePath = sprintf('%s/../../mock/buyer.%s.json', __DIR__, $id);
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Buyer file not found: {$filePath}");
        }
        $jsonContent = file_get_contents($filePath);
        if ($jsonContent === false) {
            throw new RuntimeException("Failed to read buyer file: {$filePath}");
        }
        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in buyer file: " . json_last_error_msg());
        }
        $this->container = $data;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset): mixed
    {
        return $this->container[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->container[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }
}
