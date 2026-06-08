<?php
namespace App\Reports;

abstract class ReportGenerator
{
    /**
     * Template Method: Define la estructura del algoritmo de generación de reportes.
     * Los 5 pasos son comunes; solo el paso 3 (formateo) varía.
     */
    public function generate(array $params): string
    {
        // Paso 1: Validar parámetros
        $this->validateParams($params);

        // Paso 2: Consultar datos
        $orders = $this->fetchData($params);

        // Paso 3: Formatear (HOOK - implementado por cada subclase)
        $content = $this->format($orders);

        // Paso 4: Persistir en storage
        $filename = $this->getFilename();
        $path = $this->persist($content, $filename);

        // Paso 5: Notificar
        $this->notify($filename);

        return $path;
    }

    protected function validateParams(array $params): void
    {
        if (empty($params['from']) || empty($params['to'])) {
            throw new \InvalidArgumentException('Date range required.');
        }
    }

    protected function fetchData(array $params)
    {
        return \App\Models\Order::whereBetween('created_at', [$params['from'], $params['to']])
            ->with(['customer.user', 'vendor', 'items'])
            ->get();
    }

    protected function persist(string $content, string $filename): string
    {
        $path = storage_path("app/reports/{$filename}");
        file_put_contents($path, $content);
        return $path;
    }

    protected function notify(string $filename): void
    {
        app(\App\Support\Logger::class)->log("{$this->getReportType()} report generated: {$filename}");
    }

    /**
     * Hook abstracto: cada subclase implementa su propia estrategia de formateo.
     */
    abstract protected function format($orders): string;

    /**
     * Hook abstracto: retorna la extensión/tipo del reporte.
     */
    abstract protected function getFilename(): string;

    /**
     * Hook abstracto: retorna el nombre del tipo de reporte para logs.
     */
    abstract protected function getReportType(): string;
}
