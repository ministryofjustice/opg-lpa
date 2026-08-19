<?php

declare(strict_types=1);

namespace MakeShared\Telemetry\Attribute;

use JsonSerializable;

class Aws implements JsonSerializable
{
    private array $ecsAttributes = [];

    public static function detectECS(): ?self
    {
        // curl the value of ${ECS_CONTAINER_METADATA_URI_V4}, which should return JSON
        curl_setopt_array($ch = curl_init($_SERVER['ECS_CONTAINER_METADATA_URI_V4'] ?? ''), [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 1,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        $self = new self();
        $self->ecsAttributes = [
            'container' => $data['Name'] ?? null,
            'container_id' => $data['DockerId'] ?? null,
            'container_arn' => $data['ContainerARN'] ?? null,
        ];
        return $self;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'ecs' => json_encode($this->ecsAttributes),
        ];
    }
}
