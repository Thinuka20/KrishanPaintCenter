<?php

require_once('tcpdf/tcpdf.php');

class ReportPDFinvoices extends TCPDF {
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
    
        $img_file = 'uploads/letterhead.png';
        // $img_file = 'uploads/letterhead.jpg';
        if (file_exists($img_file)) {
            $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        }
    
        // Restore original margins
        $this->SetMargins($currentLeftMargin, $currentTopMargin, $currentRightMargin);
        $this->SetAutoPageBreak(true, 10);
    }

    public function generateItemInvoice($invoice, $items, $payments) {
        $this->AddPage('P', 'A4');
        $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
        $this->SetY($this->topMargin - 10);
        
        // Invoice Title
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell($this->contentWidth, 10, 'INVOICE', 0, 1, 'C');
        
        // Invoice and Customer Details
        $this->SetFont('helvetica', 'B', 10);
        $colWidth = ($this->contentWidth - 10) / 2;
        
        // Two column headers
        $this->Cell($colWidth + 35, 7, 'Customer Details:', 0, 0);
        $this->Cell($colWidth, 7, 'Invoice Details:', 0, 1);
        
        $this->SetFont('helvetica', '', 10);
        $labelWidth = 30;
        $valueWidth = $colWidth - $labelWidth;
        
        // Customer Details
        $this->Cell($labelWidth, 6, 'Name:', 0);
        $this->Cell($valueWidth + 35, 6, $invoice['customer_name'], 0, 0);
        $this->Cell($labelWidth, 6, 'Invoice No:', 0);
        $this->Cell($valueWidth, 6, $invoice['invoice_number'], 0, 1);
        
        if (!empty($invoice['phone'])) {
            $this->Cell($labelWidth, 6, 'Phone:', 0);
            $this->Cell($valueWidth + 35, 6, $invoice['phone'], 0, 0);
        }
        $this->Cell($labelWidth, 6, 'Date:', 0);
        $this->Cell($valueWidth, 6, date('Y-m-d', strtotime($invoice['invoice_date'])), 0, 1);
        
        if (!empty($invoice['address'])) {
            $this->Cell($labelWidth, 6, 'Address:', 0);
            $this->MultiCell($valueWidth + 35, 6, $invoice['address'], 0, 'L');
        }
        
        $this->Ln(5);
        
        // Items Table
        $this->SetFillColor(240, 240, 240);
        $this->SetFont('helvetica', 'B', 10);
        
        // Calculate column widths
        $descWidth = $this->contentWidth - 85; // Adjust based on other columns
        $qtyWidth = 25;
        $priceWidth = 30;
        $totalWidth = 30;
        
        // Table headers
        $this->Cell($descWidth, 10, 'Description', 1, 0, 'C', true);
        $this->Cell($qtyWidth, 10, 'Qty', 1, 0, 'C', true);
        $this->Cell($priceWidth, 10, 'Unit Price', 1, 0, 'C', true);
        $this->Cell($totalWidth, 10, 'Subtotal', 1, 1, 'C', true);
        
        // Table content
        $this->SetFont('helvetica', '', 10);
        foreach ($items as $item) {
            // Check if we need a new page
            if ($this->GetY() > $this->getPageHeight() - 30) {
                $this->AddPage();
                $this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
                $this->SetY($this->topMargin);
                
                // Reprint headers
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell($descWidth, 10, 'Description', 1, 0, 'C', true);
                $this->Cell($qtyWidth, 10, 'Qty', 1, 0, 'C', true);
                $this->Cell($priceWidth, 10, 'Unit Price', 1, 0, 'C', true);
                $this->Cell($totalWidth, 10, 'Subtotal', 1, 1, 'C', true);
                $this->SetFont('helvetica', '', 10);
            }
            
            $this->Cell($descWidth, 10, $item['item_name'], 1);
            $this->Cell($qtyWidth, 10, $item['quantity'], 1, 0, 'C');
            $this->Cell($priceWidth, 10, number_format($item['unit_price'], 2), 1, 0, 'R');
            $this->Cell($totalWidth, 10, number_format($item['subtotal'], 2), 1, 1, 'R');
        }
        
        // Totals Section
        $this->SetFont('helvetica', 'B', 10);
        $totalLabelWidth = $descWidth + $qtyWidth + $priceWidth;
        
        $this->Cell($totalLabelWidth, 10, 'Total Amount', 1, 0, 'R', true);
        $this->Cell($totalWidth, 10, number_format($invoice['total_amount'], 2), 1, 1, 'R', true);
        
        // Payment Information
        $total_paid = 0;
        foreach ($payments as $payment) {
            $total_paid += $payment['amount'];
        }
        
        $this->Cell($totalLabelWidth, 10, 'Total Paid', 1, 0, 'R', true);
        $this->Cell($totalWidth, 10, number_format($total_paid, 2), 1, 1, 'R', true);
        
        if ($total_paid != $invoice['total_amount']) {
            $this->Cell($totalLabelWidth, 10, 'Balance Due', 1, 0, 'R', true);
            $this->Cell($totalWidth, 10, number_format($invoice['total_amount'] - $total_paid, 2), 1, 1, 'R', true);
        }
        
        // Notes Section
        if (!empty($invoice['notes'])) {
            $this->Ln(5);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell($this->contentWidth, 10, 'Notes:', 0, 1);
            $this->SetFont('helvetica', '', 10);
            $this->MultiCell($this->contentWidth, 10, $invoice['notes'], 0, 'L');
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