<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;

final readonly class ZatcaBusinessData implements ArrayPayload
{
    public function __construct(
        public string $companyName,
        public string $organizationName,
        public string $organizationIdentifier,
        public string $organizationUnitName,
        public string $companyRegistrationNumber,
        public string $companyVatNumber,
        public string $industryBusinessCategory,
        public string $streetName,
        public string $buildingNumber,
        public string $district,
        public string $city,
        public string $postalCode,
        public string $countryName,
        public string $sourceChannel = 'api',
    ) {}

    public function toArray(): array
    {
        return [
            'source_channel' => $this->sourceChannel,
            'company_name' => $this->companyName,
            'organization_name' => $this->organizationName,
            'organization_identifier' => $this->organizationIdentifier,
            'organization_unit_name' => $this->organizationUnitName,
            'company_registration_number' => $this->companyRegistrationNumber,
            'company_vat_number' => $this->companyVatNumber,
            'industry_business_category' => $this->industryBusinessCategory,
            'street_name' => $this->streetName,
            'building_number' => $this->buildingNumber,
            'district' => $this->district,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'country_name' => $this->countryName,
        ];
    }
}
