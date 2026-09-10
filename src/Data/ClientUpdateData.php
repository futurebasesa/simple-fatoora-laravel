<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ClientUpdateData implements ArrayPayload
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $mobileNumber = null,
        public ?string $countryCode = null,
        public ?string $email = null,
        public ?string $address = null,
        public ?string $companyRegistrationNumber = null,
        public ?string $companyVatNumber = null,
        public ?string $categoryId = null,
    ) {}

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'id' => $this->id,
            'first_name' => $this->name,
            'email_id' => $this->email,
            'mobile_number' => $this->mobileNumber,
            'country_code' => $this->countryCode,
            'address' => $this->address,
            'company_registration_number' => $this->companyRegistrationNumber,
            'company_vat_number' => $this->companyVatNumber,
            'category_id' => $this->categoryId,
        ]);
    }
}
