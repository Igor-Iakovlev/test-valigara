<?php

namespace App\Dto;

use BackedEnum;
use DateTimeInterface;
use InvalidArgumentException;

abstract class AbstractDto
{
    /**
     * Recursively builds a payload array from object properties.
     * @return array
     */
    public function toPayload(): array
    {
        return $this->buildPayloadFromProperties($this);
    }

    /**
     * @param AbstractDto $obj
     * @return array
     */
    private function buildPayloadFromProperties(AbstractDto $obj): array
    {
        $properties = get_object_vars($obj);
        $payload = [];
        foreach ($properties as $key => $value) {
            if ($value === null) {
                continue;
            }
            $processedValue = $this->processValue($value);
            $payload[$key] = $processedValue;
        }

        return $payload;
    }

    /**
     * @param mixed $value
     * @return string|array|bool|int|float
     */
    private function processValue(mixed $value): string|array|bool|int|float
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if ($value instanceof self) {
            return $this->buildPayloadFromProperties($value);
        }
        if (is_scalar($value)) {
            return $value;
        }
        if (is_array($value)) {
            return $this->processArray($value);
        }
        echo get_class($value);
        throw new InvalidArgumentException("Unsupported type: " . gettype($value));
    }

    /**
     * @param array $array
     * @return array
     */
    private function processArray(array $array): array
    {
        return array_map(function ($item) {
            return $this->processValue($item);
        }, $array);
    }
}
