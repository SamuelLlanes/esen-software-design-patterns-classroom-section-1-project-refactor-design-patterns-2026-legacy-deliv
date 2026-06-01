<?php
namespace App\Reports;

class CsvReportGenerator extends ReportGenerator
{
    protected function format($orders): string
    {
        $lines = ["id,customer,vendor,total,status"];
        foreach ($orders as $order) {
            $lines[] = implode(',', [
                $order->id,
                $order->customer->user->name ?? '',
                $order->vendor->business_name ?? '',
                $order->total,
                $order->status,
            ]);
        }
        return implode("\n", $lines);
    }

    protected function getFilename(): string
    {
        return 'report_' . \now()->format('Ymd_His') . '.csv';
    }

    protected function getReportType(): string
    {
        return 'CSV';
    }
}
