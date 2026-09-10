<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ProfileUpdateData implements ArrayPayload
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $companyName = null,
        public ?string $address = null,
        public ?string $countryCode = null,
        public ?string $mobileNumber = null,
        public ?string $taxSetupStatus = null,
        public ?string $companyRegistrationNumber = null,
        public ?string $companyVatNumber = null,
        public ?string $notes = null,
        public ?string $footerText = null,
        public ?bool $sendMerchantCopy = null,
        public ?bool $sendCustomerEmail = null,
    ) {}

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'company_name' => $this->companyName,
            'address' => $this->address,
            'country_code' => $this->countryCode,
            'mobile_number' => $this->mobileNumber,
            'tax_setup_status' => $this->taxSetupStatus,
            'company_registration_number' => $this->companyRegistrationNumber,
            'company_vat_number' => $this->companyVatNumber,
            'notes' => $this->notes,
            'footer_text' => $this->footerText,
            'send_mail' => $this->sendMerchantCopy === null ? null : (int) $this->sendMerchantCopy,
            'client_send_mail' => $this->sendCustomerEmail === null ? null : (int) $this->sendCustomerEmail,
        ]);
    }
}
