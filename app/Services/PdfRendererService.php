<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfRendererService
{
    public function download(string $view, array $data, string $filename)
    {
        if (!class_exists(Pdf::class)) {
            throw new \RuntimeException('PDF renderer unavailable. Install and enable barryvdh/laravel-dompdf.');
        }

        return Pdf::loadView($view, $data)->download($filename);
    }
}
