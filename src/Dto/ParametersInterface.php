<?php

namespace App\Dto;

interface ParametersInterface
{
    /**
     * @return array
     */
    public function toPayload(): array;
}
