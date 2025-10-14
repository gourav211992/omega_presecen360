<?php

namespace App\Http\Controllers\API\Integration;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Integration\ConsigneeService;
use App\Services\Integration\SaleOrderService;
use App\Services\Integration\DeliveryNoteService;
use App\Http\Requests\Integration\ConsigneeRequest;
use App\Http\Requests\Integration\StoreFurlencoDeliveryNoteRequest;
use App\Repositories\StockLedgerRepository;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Integration\StoreFurlencoSaleOrderRequest;
use App\Http\Requests\Integration\UpdateFurlencoSaleOrderRequest;
use App\Http\Requests\Integration\WarehouseRequest as WmsValidator;
use App\Http\Resources\Integration\BarCodeDetailResource;
use App\Models\ERP\StockStoreMapping;
use App\Repositories\ItemUniqueCodeRepository;

class FurlencoController extends Controller
{

    public function __construct(
        private ConsigneeService $consigneeService,
        private SaleOrderService $saleOrderService,
        private DeliveryNoteService $dnoteService
    ) {}

    public function consigneeStoreOrUpdate(ConsigneeRequest $request)
    {
        $results = $this->consigneeService->storeOrUpdate(
            $request->organization_id,
            $request->consignees
        );

        return [
            "message" => "Consignees created successfully.",
            "data" => $results
        ];
    }

    public function createSaleOrders(StoreFurlencoSaleOrderRequest $request)
    {
        $result = $this->saleOrderService->create($request,  $request->user());

        if (!$result['status']) {
            return response()->json([
                'error'   => 'Server Error',
                'message'=> $result['message'],
                'exception' => 'Error occurred while creating the record.',
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }

    public function updateSaleOrders(UpdateFurlencoSaleOrderRequest $request)
    {
        $result = $this->saleOrderService->update($request,  $request->user());

        if (!$result['status']) {
            return response()->json([
                'error'   => 'Server Error',
                'message'=> $result['message'],
                'exception' => 'Error occurred while creating the record.',
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }

    public function stockReport(Request $request, StockLedgerRepository $stockLedgerRepository){
        $validator = (new WmsValidator($request))->stockLedger();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $subStoreIds = StockStoreMapping::where('stock_type',$request->stock_type)
            ->where('store_id',$request->store_id)
            ->where('organization_id',$request->organization_id)
            ->pluck('sub_store_id')
            ->toArray();

        $itemId = $request->input('item_id') ?? NULL;
        $inventoryReports = $stockLedgerRepository->getStocks(
            $request->organization_id,
            $request->store_id,
            $subStoreIds,
            $itemId
        );

        return [
            "data" => $inventoryReports,
        ];

    }

    public function getBarcodeDetail(Request $request, ItemUniqueCodeRepository $itemUniqueCode){
        $validator = (new WmsValidator($request))->barcodeDetail();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $itemUniqueCode->getDetail(
            $request->barcode,
            $request->trip_no
        );

        if(!$data['status']){
            throw ValidationException::withMessages([
                'barcode' => $data['message'],
            ]);
        }

        return [
            "data" => new BarCodeDetailResource($data['data']),
        ];

    }

    public function createDeliveryNote(StoreFurlencoDeliveryNoteRequest $request){
        $result = $this->dnoteService->create($request,  $request->user());

        if (!$result['status']) {
            return response()->json([
                'error'   => 'Server Error',
                'message'=> $result['message'],
                'exception' => 'Error occurred while creating the record.',
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }
}
