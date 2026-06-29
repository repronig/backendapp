<?php

namespace App\Support\Pdf;

use App\Support\MemberWorkImports\MemberWorkImportColumnReference;

class MemberWorkImportPdfPresenter
{
    use ResolvesPdfPlatformName;

    /**
     * @return array<string, mixed>
     */
    public function columnReferenceData(): array
    {
        return [
            'brandLogoHtml' => PdfAssets::brandLogoBlock(190, 8),
            'watermarkDataUri' => PdfAssets::brandLogoDataUri(),
            'generatedAt' => now()->format('j M Y, H:i'),
            'platformName' => $this->pdfPlatformDisplayName(),
            'columns' => MemberWorkImportColumnReference::orderedRows(),
            'zipFiles' => MemberWorkImportColumnReference::zipFilePatterns(),
        ];
    }
}
