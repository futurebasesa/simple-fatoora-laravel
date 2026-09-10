<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ProfileResource extends Resource
{
    public function get(): ApiResponse
    {
        return $this->transport->request(Endpoint::GetBusinessProfile);
    }

    /** @param array<string, mixed>|ArrayPayload $profile */
    public function update(array|ArrayPayload $profile): ApiResponse
    {
        return $this->transport->request(
            Endpoint::UpdateBusinessProfile,
            payload: Payload::normalize($profile),
        );
    }

    public function uploadLogo(string $filePath): ApiResponse
    {
        return $this->transport->upload(Endpoint::UploadCompanyLogo, $filePath);
    }
}
