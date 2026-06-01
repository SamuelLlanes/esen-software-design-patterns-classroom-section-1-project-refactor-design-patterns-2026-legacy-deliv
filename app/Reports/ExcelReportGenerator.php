<?php
namespace App\Reports;

class ExcelReportGenerator extends ReportGenerator
{
    protected function format($orders): string
    {
        // Stub: retorna contenido binario dummy representando un xlsx vacío
        return "PK\x03\x04Excel stub with " . $orders->count() . " orders";
    }

    protected function getFilename(): string
    {
        return 'report_' . \now()->format('Ymd_His') . '.xlsx';
    }

    protected function getReportType(): string
    {
        return 'Excel';
    }
}
