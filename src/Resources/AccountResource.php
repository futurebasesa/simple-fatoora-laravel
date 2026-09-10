<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class AccountResource extends Resource
{
    /** @param array<string, mixed>|ArrayPayload $registration */
    public function createRegistrationIntent(array|ArrayPayload $registration, string $journeyId): ApiResponse
    {
        return $this->transport->request(Endpoint::CreateRegistrationIntent, payload: [
            'registration_data' => Payload::normalize($registration),
            'registration_journey_id' => $journeyId,
        ]);
    }

    public function sendEmailOtp(string $email): ApiResponse
    {
        return $this->transport->request(Endpoint::SendEmailOtp, payload: ['email_id' => $email]);
    }

    public function verifyEmailOtp(string $email, int $otp): ApiResponse
    {
        return $this->transport->request(Endpoint::VerifyEmailOtp, payload: [
            'email_id' => $email,
            'otp' => $otp,
        ]);
    }

    public function completeRegistration(string $registrationIntent): ApiResponse
    {
        return $this->transport->request(Endpoint::CompleteRegistration, payload: [
            'registration_intent' => $registrationIntent,
        ]);
    }

    public function retrieveApiKeys(string $username, string $password): ApiResponse
    {
        return $this->transport->request(Endpoint::GetApiKeysFromCredentials, payload: [
            'user_name' => $username,
            'user_pass' => $password,
        ]);
    }

    public function validateApiKey(string $apiKey): ApiResponse
    {
        return $this->transport->request(Endpoint::ValidateApiKey, payload: ['api_key' => $apiKey]);
    }

    public function resetPassword(string $uuid, string $password, string $confirmation): ApiResponse
    {
        return $this->transport->request(Endpoint::ResetPassword, payload: [
            'uuid' => $uuid,
            'password' => $password,
            'confirm_password' => $confirmation,
        ]);
    }
}
