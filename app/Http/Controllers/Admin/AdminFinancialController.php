<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Report\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFinancialController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $customerId = $request->input('customer_id');

        $data = $this->reportService->getOverview($startDate, $endDate, $customerId ? (int) $customerId : null);
        return ApiResponse::success($data);
    }

    public function rankings(Request $request): JsonResponse
    {
        $customerRankings = $this->reportService->getCustomerRankings(10);
        $merchantRankings = $this->reportService->getMerchantRankings(null, 10);

        return ApiResponse::success([
            'customers' => $customerRankings,
            'merchants' => $merchantRankings,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $customerId = $request->input('customer_id');

        $csvData = $this->reportService->exportTransactionsCsv($startDate, $endDate, $customerId ? (int) $customerId : null);
        $filename = 'transactions_report_' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
