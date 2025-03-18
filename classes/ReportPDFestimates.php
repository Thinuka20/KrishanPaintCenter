<?php

require_once('tcpdf/tcpdf.php');

class ReportPDF extends TCPDF {
    private $leftMargin = 15;
    private $rightMargin = 15;
    private $topMargin = 65;
    private $contentWidth;

    public function __construct($orientation = 'P', $title = '') {
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8', false);
        
        // Initially set zero margins for the background
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(true, 12);
        
        // Calculate content width
        $this->contentWidth = 210 - ($this->leftMargin + $this->rightMargin); // 210 is A4 width
    }

    public function Header() {
        // Save current margins
        $currentLeftMargin = $this->lMargin;
        $currentTopMargin = $this->tMargin;
        $currentRightMargin = $this->rMargin;
    
        // Temporarily remove margins for the full-page image
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false, 0);
    
        // $img_file = 'uploads/letterhead.png';
        $img_file = 'uploads/letterhead.jpg';
        if (file_exists($img_file)) {
            $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        }
    
        // Restore original margins **after** setting the image
        $this->SetMargins($currentLeftMargin, $currentTopMargin, $currentRightMargin);
        $this->SetAutoPageBreak(true, 10);
    }
    

    public function generateEstimate($estimate, $items, $totals) {
        $this->AddPage('P', 'A4');
        $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
        $this->SetY($this->topMargin - 10);
        
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell($this->contentWidth, 10, 'ESTIMATE', 0, 1, 'C');

        $this->generateCommonContent($estimate, $items, $totals);
    }

    public function generateSupplementaryEstimate($estimate, $items, $totals) {
        $this->AddPage('P', 'A4');
        $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
        $this->SetY($this->topMargin - 10);
        
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell($this->contentWidth, 10, 'SUPPLEMENTARY ESTIMATE', 0, 1, 'C');

        $this->generateCommonContent($estimate, $items, $totals);
    }

    public function generateSparePartsEstimate($estimate, $items, $totals) {
        $this->AddPage('P', 'A4');
        $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
        $this->SetY($this->topMargin - 10);
        
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell($this->contentWidth, 10, 'SPARE PARTS ESTIMATE', 0, 1, 'C');

        $this->generateCommonContent($estimate, $items, $totals, true);
    }

    public function generateSparePartsSupEstimate($estimate, $items, $totals) {
        $this->AddPage('P', 'A4');
        $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
        $this->SetY($this->topMargin - 10);
        
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell($this->contentWidth, 10, 'SPARE PARTS SUPPLEMENTARY ESTIMATE', 0, 1, 'C');

        $this->generateCommonContent($estimate, $items, $totals, true);
    }

    private function generateCommonContent($estimate, $items, $totals, $isSparePartsEstimate = false) {
        // Vehicle and Estimate Details section
        $this->SetFont('helvetica', 'B', 10);
        $colWidth = ($this->contentWidth - 10) / 2;
        
        // Two column headers
        $this->Cell($colWidth + 35, 7, 'Vehicle Details:', 0, 0);
        $this->Cell($colWidth, 7, 'Estimate Details:', 0, 1);

        $this->SetFont('helvetica', '', 10);
        $labelWidth = 30;
        $valueWidth = $colWidth - $labelWidth;
        
        // Vehicle Details
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

        // Items Table
        $this->SetFillColor(240, 240, 240);
        $this->SetFont('helvetica', 'B', 10);
        
        $descWidth = $this->contentWidth - 40;
        
        // Table headers
        $this->Cell($descWidth, 7, 'Description', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Amount', 1, 1, 'C', true);

        // Table content with page break handling
        $this->SetFont('helvetica', '', 10);
        foreach ($items as $item) {
            // Check if we need a new page
            if ($this->GetY() > $this->getPageHeight() - 30) {
                $this->AddPage();
                $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
                $this->SetY($this->topMargin);
                
                // Reprint headers
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell($descWidth, 7, 'Description', 1, 0, 'C', true);
                $this->Cell(40, 7, 'Amount', 1, 1, 'C', true);
                $this->SetFont('helvetica', '', 10);
            }
            
            $this->Cell($descWidth, 6, $item['description'], 1);
            // Handle amounts differently for spare parts estimates
            if ($isSparePartsEstimate && (empty($item['price']) || $item['price'] <= 0)) {
                $this->Cell(40, 6, '--', 1, 1, 'R');
            } else {
                $this->Cell(40, 6, number_format($item['price'], 2), 1, 1, 'R');
            }
        }

        // Total
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell($descWidth, 7, 'Total Amount    ', 1, 0, 'R', true);
        // Handle total differently for spare parts estimates
        if ($isSparePartsEstimate && (empty($totals['total_amount']) || $totals['total_amount'] <= 0)) {
            $this->Cell(40, 7, '--', 1, 1, 'R', true);
        } else {
            $this->Cell(40, 7, number_format($totals['total_amount'], 2), 1, 1, 'R', true);
        }

        if (!empty($estimate['notes'])) {
            $this->Ln(5);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell($this->contentWidth, 10, 'Notes:', 0, 1);
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell($this->contentWidth, 10, $estimate['notes'], 0, 'L');
        }

        // Signature Section
        $this->Ln(15);
        $this->Cell(60, 6, '............................................', 0, 1, 'L');
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(60, 6, 'W.Nimal Thushara Lowe', 0, 1, 'L');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(60, 6, 'Proprietor', 0, 1, 'L');
    }
}