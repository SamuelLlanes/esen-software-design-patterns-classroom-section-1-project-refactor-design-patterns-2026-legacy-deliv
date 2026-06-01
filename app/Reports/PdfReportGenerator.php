<?php
namespace App\Reports;

class PdfReportGenerator extends ReportGenerator
{
    protected function format($orders): string
    {
        // Stub: retorna contenido dummy
        return "%PDF-1.4 Report with " . $orders->count() . " orders";
    }

    protected function getFilename(): string
    {
        return 'report_' . \now()->format('Ymd_His') . '.pdf';
    }

    protected function getReportType(): string
    {
        return 'PDF';
    }
}
