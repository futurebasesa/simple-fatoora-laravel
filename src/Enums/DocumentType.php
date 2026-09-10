<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Enums;

enum DocumentType: int
{
    case SimplifiedInvoice = 0;
    case StandardInvoice = 1;
    case PurchaseInvoice = 2;
    case CreditNote = 3;
    case DebitNote = 4;
}
