<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Finance\ApplyInvoiceAdjustmentAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreInvoiceAdjustmentRequest;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class AdminFinanceAdjustmentController extends BaseApiController
{
    public function store(StoreInvoiceAdjustmentRequest $request, Invoice $invoice, ApplyInvoiceAdjustmentAction $action): JsonResponse
    {
        $adjusted = $action->execute($invoice, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Invoice adjusted successfully.', new InvoiceResource($adjusted));
    }
}
