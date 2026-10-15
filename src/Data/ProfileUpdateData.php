<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ProfileUpdateData implements ArrayPayload
{
    /** @param array{reason_code: string, category?: string, reason?: string}|null $zeroVatDefault */
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
        public ?array $zeroVatDefault = null,
        public ?int $invoiceLogo = null,
        public ?int $showSimplifiedCustomerInfo = null,
        public ?int $showPdfItemId = null,
        public ?int $showPdfIssuerAddress = null,
        public ?int $showPdfIssuerMobile = null,
        public ?int $showPdfIssuerEmail = null,
        public ?int $showPdfCustomerAddress = null,
        public ?int $showPdfCustomerMobile = null,
        public ?int $showPdfCustomerEmail = null,
        public ?string $streetName = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?string $buildingNumber = null,
        public ?string $postalCode = null,
    ) {}

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'zero_vat_default' => $this->zeroVatDefault,
            'invoice_logo' => $this->invoiceLogo,
            'show_simplified_customer_info' => $this->showSimplifiedCustomerInfo,
            'show_pdf_item_id' => $this->showPdfItemId,
            'show_pdf_issuer_address' => $this->showPdfIssuerAddress,
            'show_pdf_issuer_mobile' => $this->showPdfIssuerMobile,
            'show_pdf_issuer_email' => $this->showPdfIssuerEmail,
            'show_pdf_customer_address' => $this->showPdfCustomerAddress,
            'show_pdf_customer_mobile' => $this->showPdfCustomerMobile,
            'show_pdf_customer_email' => $this->showPdfCustomerEmail,
            'street_name' => $this->streetName,
            'city' => $this->city,
            'district' => $this->district,
            'building_number' => $this->buildingNumber,
            'postal_code' => $this->postalCode,

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
