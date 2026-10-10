<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Label;

/**
 * Merges several single-label PDFs into one printable PDF with Zend_Pdf (the
 * library Magento uses for invoices).
 *
 * Returns null when a label cannot be parsed. Every parcel label is still stored
 * separately, and "download all" then falls back to a ZIP.
 */
class PdfMerger
{
    /**
     * @param string[] $pdfs binary PDF contents
     */
    public function merge(array $pdfs): ?string
    {
        $pdfs = array_values($pdfs);
        if (count($pdfs) === 1) {
            return $pdfs[0];
        }
        if ($pdfs === [] || !class_exists(\Zend_Pdf::class)) {
            return null;
        }

        try {
            $merged = new \Zend_Pdf();
            // The cloned pages still resolve objects through their source document,
            // so the parsed sources must stay alive until render().
            $sources = [];
            foreach ($pdfs as $binary) {
                $sources[] = $source = \Zend_Pdf::parse($binary);
                foreach ($source->pages as $page) {
                    $merged->pages[] = clone $page;
                }
            }
            $output = $merged->render();
            unset($sources);
            return str_starts_with($output, '%PDF-') ? $output : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
