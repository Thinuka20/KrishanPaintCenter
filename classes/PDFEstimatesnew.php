<?php

require_once('tcpdf/tcpdf.php');

class ReportPDF extends TCPDF
{
    private $leftMargin = 15;
    private $rightMargin = 15;
    private $topMargin = 65;
    private $contentWidth;

    public function __construct($orientation = 'P', $title = '')
    {
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8', false);

        // Initially set zero margins for the background
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(true, 12);

        // Calculate content width
        $this->contentWidth = 210 - ($this->leftMargin + $this->rightMargin); // 210 is A4 width
    }

    public function Header()
    {
        // Save current margins
        $currentLeftMargin = $this->lMargin;
        $currentTopMargin = $this->tMargin;
        $currentRightMargin = $this->rMargin;

        // Temporarily remove margins for the full-page image
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false, 0);

        $img_file = 'uploads/letterhead.png';
        // $img_file = 'uploads/letterhead.jpg';
        if (file_exists($img_file)) {
            $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        }

        // Restore original margins **after** setting the image
        $this->SetMargins($currentLeftMargin, $currentTopMargin, $currentRightMargin);
        $this->SetAutoPageBreak(true, 10);
    }


    public function generateEstimate($estimate, $items, $totals)
    {
        $this->AddPage('P', 'A4');
        $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
        $this->SetY($this->topMargin - 10);

        $this->SetFont('helvetica', 'B', 14);
        $this->Cell($this->contentWidth, 10, 'ESTIMATE', 0, 1, 'C');

        $this->generateCommonContent($estimate, $items, $totals);
    }

    public function generateSupplementaryEstimate($estimate, $items, $totals)
    {
        $this->AddPage('P', 'A4');
        $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
        $this->SetY($this->topMargin - 10);

        $this->SetFont('helvetica', 'B', 14);
        $this->Cell($this->contentWidth, 10, 'SUPPLEMENTARY ESTIMATE', 0, 1, 'C');

        $this->generateCommonContent($estimate, $items, $totals);
    }
    private function generateCommonContent($estimate, $items, $totals, $isSparePartsEstimate = false)
    {
        // Vehicle and Estimate Details section
        $this->SetFont('helvetica', 'B', 10);
        $colWidth = ($this->contentWidth - 10) / 2;

        // Two column headers with better spacing
        $this->Cell($colWidth + 35, 7, 'Vehicle Details:', 0, 0);
        $this->Cell($colWidth, 7, 'Estimate Details:', 0, 1);

        $this->SetFont('helvetica', '', 10);
        $labelWidth = 30;
        $valueWidth = $colWidth - $labelWidth;

        // Vehicle Details with improved alignment
        $this->Cell($labelWidth, 6, 'Registration:', 0);
        $this->Cell($valueWidth + 35, 6, $estimate['registration_number'], 0, 0);
        $this->Cell($labelWidth, 6, 'Estimate No:', 0);
        $this->Cell($valueWidth, 6, $estimate['estimate_number'], 0, 1);

        $this->Cell($labelWidth, 6, 'Make/Model:', 0);
        $this->Cell($valueWidth + 35, 6, $estimate['make'] . ' ' . $estimate['model'], 0, 0);
        $this->Cell($labelWidth, 6, 'Date:', 0);
        $this->Cell($valueWidth, 6, date('Y-m-d', strtotime($estimate['estimate_date'])), 0, 1);

        $this->Cell($labelWidth, 6, 'Year:', 0);
        $this->Cell($valueWidth, 6, $estimate['year'], 0, 1);

        $this->Ln(5);

        // Improved table formatting
        $this->SetFillColor(245, 245, 245);
        $this->SetFont('helvetica', 'B', 10);

        $categories = [
            'removing' => 'Removing and Refitting',
            'repairing' => 'Repairing',
            'replacing' => 'Replacing',
            'repainting' => 'Repainting',
            'spares' => 'Spare Parts'
        ];

        // Group items by category
        $groupedItems = [];
        foreach ($items as $item) {
            $category = isset($item['category']) ? $item['category'] : 'removing';
            if (!isset($groupedItems[$category])) {
                $groupedItems[$category] = [];
            }
            $groupedItems[$category][] = $item;
        }

        // Adjust column widths for better text display
        $descWidth = $this->contentWidth - 70; // Increased description width
        $amountWidth = 35; // Fixed width for amount
        $spacerWidth = 35; // Width for empty column

        foreach ($categories as $categoryKey => $categoryName) {
            if (isset($groupedItems[$categoryKey]) && !empty($groupedItems[$categoryKey])) {
                // Category header
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell($this->contentWidth, 7, $categoryName, 1, 1, 'L', true);

                // Column headers
                $this->Cell($descWidth, 7, 'Description', 1, 0, 'L', true);
                $this->Cell($amountWidth, 7, 'Amount', 1, 0, 'R', true);
                $this->Cell($spacerWidth, 7, '', 1, 1, 'C', true);

                // Items with word-wrapped description
                $this->SetFont('helvetica', '', 10);
                $categoryTotal = 0;

                foreach ($groupedItems[$categoryKey] as $item) {
                    // Calculate required height for description
                    $description = $item['description'];
                    $lineHeight = 6;
                    $topPadding = 1; // Add padding to top
                    $lines = $this->calculateNumLines($description, $descWidth);
                    $cellHeight = max($lineHeight * $lines - $lineHeight - 3, $lineHeight);

                    $this->setCellPaddings(1, $topPadding, 1, 1);


                    // Print cells with proper height
                    $startY = $this->GetY();
                    $this->MultiCell($descWidth, $cellHeight, $description, 1, 'L', false, 0);

                    $this->setCellPaddings(1, 1, 1, 1);


                    if (empty($item['price']) || $item['price'] <= 0) {
                        $this->Cell($amountWidth, $cellHeight, '--', 1, 0, 'R');
                    } else {
                        $this->Cell($amountWidth, $cellHeight, number_format($item['price'], 2), 1, 0, 'R');
                        $categoryTotal += $item['price'];
                    }
                    $this->Cell($spacerWidth, $cellHeight, '', 1, 1, 'R');
                }

                // Category subtotal with improved formatting
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell($descWidth, 7, $categoryName . ' Total', 1, 0, 'R', true);
                if (empty($categoryTotal) || $categoryTotal <= 0) {
                    $this->Cell($amountWidth, 7, '--', 1, 0, 'R', true);
                } else {
                    $this->Cell($amountWidth, 7, number_format($categoryTotal, 2), 1, 0, 'R', true);
                }
                $this->Cell($spacerWidth, 7, '', 1, 1, 'R', true);
                $this->Ln(3);
            }
        }

        // Grand Total with consistent formatting
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell($descWidth, 7, 'Grand Total', 1, 0, 'R', true);
        if (empty($totals['total_amount']) || $totals['total_amount'] <= 0) {
            $this->Cell($amountWidth, 7, '--', 1, 0, 'R', true);
        } else {
            $this->Cell($amountWidth, 7, number_format($totals['total_amount'], 2), 1, 0, 'R', true);
        }
        $this->Cell($spacerWidth, 7, '', 1, 1, 'R', true);

        // Notes section with proper spacing
        if (!empty($estimate['notes'])) {
            $this->Ln(5);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell($this->contentWidth, 7, 'Notes:', 0, 1);
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell($this->contentWidth, 6, $estimate['notes'], 0, 'L');
        }

        // Signature section with consistent spacing
        $this->Ln(25);
        $this->Cell(60, 6, '............................................', 0, 1, 'L');
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(60, 6, 'W.Nimal Thushara Lowe', 0, 1, 'L');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(60, 6, 'Proprietor', 0, 1, 'L');
    }

    // Helper function to calculate number of lines needed for text
    private function calculateNumLines($text, $width)
    {
        // Get the approximate number of characters that fit in the width
        $avgCharWidth = $this->GetStringWidth('a');
        $charsPerLine = floor($width / $avgCharWidth);

        // Split text into words
        $words = explode(' ', $text);
        $currentLine = '';
        $lineCount = 1;

        foreach ($words as $word) {
            $testLine = $currentLine . ' ' . $word;
            if ($this->GetStringWidth($testLine) > $width) {
                $currentLine = $word;
                $lineCount++;
            } else {
                $currentLine = $testLine;
            }
        }

        return $lineCount;
    }
}
