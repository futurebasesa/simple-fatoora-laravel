<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ZatcaPhase2Resource extends Resource
{
    public function status(): ApiResponse
    {
        return $this->transport->request(Endpoint::GetZatcaPhase2Status);
    }

    /** @param array<string, mixed>|ArrayPayload $business */
    public function saveDraft(array|ArrayPayload $business): ApiResponse
    {
        return $this->transport->request(Endpoint::SaveZatcaPhase2Draft, payload: Payload::normalize($business));
    }

    /** @param array<string, mixed>|ArrayPayload $business */
    public function generateCsr(array|ArrayPayload $business): ApiResponse
    {
        return $this->transport->request(Endpoint::GenerateZatcaCsr, payload: Payload::normalize($business));
    }

    public function submitOtp(string $otp, string $sourceChannel = 'api'): ApiResponse
    {
        return $this->transport->request(Endpoint::SubmitZatcaOtp, payload: [
            'source_channel' => $sourceChannel,
            'otp' => $otp,
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function retryCompliance(array $payload = []): ApiResponse
    {
        return $this->transport->request(Endpoint::RetryZatcaCompliance, payload: $payload);
    }

    /** @param array<string, mixed> $payload */
    public function refreshStatus(array $payload = []): ApiResponse
    {
        return $this->transport->request(Endpoint::RefreshZatcaPhase2Status, payload: $payload);
    }

    public function renew(string $sourceChannel = 'api'): ApiResponse
    {
        return $this->transport->request(Endpoint::RenewZatcaPhase2, payload: [
            'source_channel' => $sourceChannel,
        ]);
    }
}
