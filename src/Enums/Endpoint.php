<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Enums;

enum Endpoint: string
{
    case CreateRegistrationIntent = 'createRegistrationIntent';
    case SendEmailOtp = 'sendEmailOtp';
    case VerifyEmailOtp = 'verifyEmailOtp';
    case CompleteRegistration = 'completeRegistration';
    case GetApiKeysFromCredentials = 'getApiKeysFromCredentials';
    case ValidateApiKey = 'validateApiKey';
    case ResetPassword = 'resetPassword';
    case GetBusinessProfile = 'getBusinessProfile';
    case UpdateBusinessProfile = 'updateBusinessProfile';
    case UploadCompanyLogo = 'uploadCompanyLogo';
    case ListClientCategories = 'listClientCategories';
    case CreateClient = 'createClient';
    case UpdateClient = 'updateClient';
    case GetClient = 'getClient';
    case ListClients = 'listClients';
    case DeleteClient = 'deleteClient';
    case SearchProducts = 'searchProducts';
    case ListProducts = 'listProducts';
    case CreateInvoice = 'createInvoice';
    case ListInvoices = 'listInvoices';
    case GetInvoice = 'getInvoice';
    case DownloadInvoicePdf = 'downloadInvoicePdf';
    case DownloadInvoiceXml = 'downloadInvoiceXml';
    case ExportInvoiceArchive = 'exportInvoiceArchive';
    case CreateQuotation = 'createQuotation';
    case UpdateQuotation = 'updateQuotation';
    case ListQuotations = 'listQuotations';
    case GetQuotation = 'getQuotation';
    case DownloadQuotationPdf = 'downloadQuotationPdf';
    case DeleteQuotation = 'deleteQuotation';
    case GetSalesReport = 'getSalesReport';
    case GetVatReturnReport = 'getVatReturnReport';
    case GetZatcaPhase2Status = 'getZatcaPhase2Status';
    case SaveZatcaPhase2Draft = 'saveZatcaPhase2Draft';
    case GenerateZatcaCsr = 'generateZatcaCsr';
    case SubmitZatcaOtp = 'submitZatcaOtp';
    case RetryZatcaCompliance = 'retryZatcaCompliance';
    case RefreshZatcaPhase2Status = 'refreshZatcaPhase2Status';
    case RenewZatcaPhase2 = 'renewZatcaPhase2';

    public function method(): HttpMethod
    {
        return match ($this) {
            self::GetBusinessProfile,
            self::ListClientCategories,
            self::GetClient,
            self::SearchProducts,
            self::GetInvoice,
            self::DownloadInvoicePdf,
            self::DownloadInvoiceXml,
            self::GetQuotation,
            self::DownloadQuotationPdf,
            self::GetZatcaPhase2Status => HttpMethod::Get,
            self::DeleteClient,
            self::DeleteQuotation => HttpMethod::Delete,
            default => HttpMethod::Post,
        };
    }

    public function path(): string
    {
        return match ($this) {
            self::CreateRegistrationIntent => '/users/registration_intent',
            self::SendEmailOtp => '/users/email_send_otp',
            self::VerifyEmailOtp => '/users/email_verify_otp',
            self::CompleteRegistration => '/users/register_from_intent',
            self::GetApiKeysFromCredentials => '/users/validate_user',
            self::ValidateApiKey => '/users/validate_user_api_key',
            self::ResetPassword => '/users/update_password',
            self::GetBusinessProfile => '/users/get_profile',
            self::UpdateBusinessProfile => '/users/update_profile',
            self::UploadCompanyLogo => '/users/upload_profile_image',
            self::ListClientCategories => '/client/kyc_category_list',
            self::CreateClient => '/client/create',
            self::UpdateClient => '/client/update',
            self::GetClient => '/client/get_byid/{client_id}',
            self::ListClients => '/client/get_all',
            self::DeleteClient => '/client/delete/{client_id}',
            self::SearchProducts => '/invoice/products/search',
            self::ListProducts => '/invoice/products/list',
            self::CreateInvoice => '/invoice/create',
            self::ListInvoices => '/invoice/get_all',
            self::GetInvoice => '/invoice/get_byid/{document_id}',
            self::DownloadInvoicePdf => '/invoice/pdf/{document_id}',
            self::DownloadInvoiceXml => '/invoice/xml/{document_id}',
            self::ExportInvoiceArchive => '/invoice/archive/export',
            self::CreateQuotation => '/drafts/create',
            self::UpdateQuotation => '/drafts/update',
            self::ListQuotations => '/drafts/get_all',
            self::GetQuotation => '/drafts/get_byid/{quotation_id}',
            self::DownloadQuotationPdf => '/drafts/quotation_pdf/{quotation_id}',
            self::DeleteQuotation => '/drafts/delete/{quotation_id}',
            self::GetSalesReport => '/invoice/get_report',
            self::GetVatReturnReport => '/invoice/get_vat_return_report',
            self::GetZatcaPhase2Status => '/zatca-phase2/status',
            self::SaveZatcaPhase2Draft => '/zatca-phase2/save-draft',
            self::GenerateZatcaCsr => '/zatca-phase2/generate-csr',
            self::SubmitZatcaOtp => '/zatca-phase2/submit-otp',
            self::RetryZatcaCompliance => '/zatca-phase2/retry-compliance',
            self::RefreshZatcaPhase2Status => '/zatca-phase2/refresh-status',
            self::RenewZatcaPhase2 => '/zatca-phase2/renew',
        };
    }

    public function requiresAuthentication(): bool
    {
        return ! in_array($this, [
            self::CreateRegistrationIntent,
            self::SendEmailOtp,
            self::VerifyEmailOtp,
            self::CompleteRegistration,
            self::GetApiKeysFromCredentials,
            self::ValidateApiKey,
            self::ResetPassword,
        ], true);
    }

    public function isDownload(): bool
    {
        return in_array($this, [
            self::DownloadInvoicePdf,
            self::DownloadInvoiceXml,
            self::ExportInvoiceArchive,
            self::DownloadQuotationPdf,
        ], true);
    }

    public function mayRetry(): bool
    {
        return $this->method() === HttpMethod::Get;
    }

    public function accept(): string
    {
        return match ($this) {
            self::DownloadInvoicePdf,
            self::DownloadQuotationPdf => 'application/pdf',
            self::DownloadInvoiceXml => 'application/xml',
            self::ExportInvoiceArchive => 'application/zip',
            default => 'application/json',
        };
    }
}
