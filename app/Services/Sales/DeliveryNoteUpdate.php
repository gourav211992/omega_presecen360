<?php
namespace App\Services\Sales;

use App\Helpers\ConstantHelper;
use App\Helpers\SuccessErrorArrayResponse;
use App\Models\ErpInvoiceItem;
use App\Models\ErpSaleInvoice;

class DeliveryNoteUpdate
{
    public function updateItem(ErpInvoiceItem|null $oldSiItem, ErpInvoiceItem $siItem, ErpSaleInvoice $saleInvoice, float $currentQty, int $currentItemIndex) : array
    {
        if ($saleInvoice -> document_type === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS) {
            if (isset($oldSiItem)) {
                //Check if PWO qty is there
                if ($oldSiItem -> invoice_qty > $currentQty) {
                    $pulledQty = $oldSiItem -> invoice_qty;
                    return SuccessErrorArrayResponse::errorResponse("Invoice of qty - $pulledQty has been generated");
                }
            }
        }
        return SuccessErrorArrayResponse::successResponse("Items Updated successfully");
    }
}
