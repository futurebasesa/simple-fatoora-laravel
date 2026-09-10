<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class RegistrationData implements ArrayPayload
{
    public function __construct(
        public string $email,
        public string $password,
        public string $passwordConfirmation,
        public string $companyName,
        public bool $acceptedTerms,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $mobileNumber = null,
        public ?string $countryCode = null,
        public ?string $companyRegistrationNumber = null,
        public ?string $companyVatNumber = null,
        public ?string $address = null,
        public ?string $source = 'api',
    ) {}

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email_id' => $this->email,
            'password' => $this->password,
            'confirm_password' => $this->passwordConfirmation,
            'company_name' => $this->companyName,
            'mobile_number' => $this->mobileNumber,
            'country_code' => $this->countryCode,
            'company_registration_number' => $this->companyRegistrationNumber,
            'company_vat_number' => $this->companyVatNumber,
            'address' => $this->address,
            'terms_and_conditions' => (int) $this->acceptedTerms,
            'source' => $this->source,
        ]);
    }
}
