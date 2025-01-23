<?php
// classes/ReportPDF.php
require_once('tcpdf/tcpdf.php');

class ReportPDF extends TCPDF {
    private $report_title;

    public function __construct($orientation = 'P', $title = '') {
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8', false);
        $this->report_title = $title;

        // Set document information
        $this->SetCreator('Krishan Paint Center');
        $this->SetAuthor('Krishan Paint Center');
        $this->SetTitle($title . ' ' . date('Y-m-d'));

        // Remove default header/footer
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        // Set margins
        $this->SetMargins(10, 10, 10);

        // Set auto page breaks
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    }

    protected function addReportHeader($date_range = '') {
        // Company name
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 10, 'KRISHAN PAINT CENTER', 0, 1, 'C');

        // Report title
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, $this->report_title, 0, 1, 'C');

        // Date range if provided
        if ($date_range) {
            $this->SetFont('helvetica', '', 11);
            $this->Cell(0, 5, 'Period: ' . $date_range, 0, 1, 'C');
        }

        $this->Ln(5);
    }

    protected function addTableHeader($headers, $widths) {
        // Colors for header
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetFont('helvetica', 'B', 10);

        // Print header
        foreach ($headers as $i => $h) {
            $this->Cell($widths[$i], 7, $h, 1, 0, 'C', true);
        }
        $this->Ln();

        // Reset font for data
        $this->SetFont('helvetica', '', 9);
        $this->SetFillColor(255, 255, 255);
    }

    protected function addSignatureSection() {
        $this->Ln(20);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(90, 6, '............................', 0, 0, 'C');
        $this->Cell(90, 6, '............................', 0, 0, 'C');
        $this->Cell(90, 6, '............................', 0, 0, 'C');
        $this->Ln();
        $this->Cell(90, 6, 'Prepared By', 0, 0, 'C');
        $this->Cell(90, 6, 'Checked By', 0, 0, 'C');
        $this->Cell(90, 6, 'Approved By', 0, 0, 'C');
    }

    public function generateDailySalesReport($data, $totals, $date_range) {
        $this->AddPage('L', 'A4');
        $this->addReportHeader($date_range);

        // Define table structure
        $headers = array('Date', 'Repair Sales', 'Item Sales', 'Total Sales', 'No. of Invoices');
        $widths = array(50, 55, 55, 55, 50);

        $this->addTableHeader($headers, $widths);

        // Add data rows
        foreach ($data as $row) {
            $this->Cell($widths[0], 6, date('Y-m-d', strtotime($row['date'])), 1);
            $this->Cell($widths[1], 6, number_format($row['repair_sales'], 2), 1, 0, 'R');
            $this->Cell($widths[2], 6, number_format($row['item_sales'], 2), 1, 0, 'R');
            $this->Cell($widths[3], 6, number_format($row['total_sales'], 2), 1, 0, 'R');
            $this->Cell($widths[4], 6, number_format($row['invoices']), 1, 0, 'C');
            $this->Ln();
        }

        // Add totals
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell($widths[0], 7, 'Total', 1, 0, 'C');
        $this->Cell($widths[1], 7, number_format($totals['repair_sales'], 2), 1, 0, 'R');
        $this->Cell($widths[2], 7, number_format($totals['item_sales'], 2), 1, 0, 'R');
        $this->Cell($widths[3], 7, number_format($totals['total_sales'], 2), 1, 0, 'R');
        $this->Cell($widths[4], 7, number_format($totals['invoices']), 1, 0, 'C');
        $this->Ln();

        $this->addSignatureSection();
    }

    public function generateItemSalesReport($data, $totals, $date_range) {
        $this->AddPage('L', 'A4');
        $this->addReportHeader($date_range);

        // Define table structure
        $headers = array('Item Code', 'Item Name', 'Quantity Sold', 'Total Sales', 'Average Price');
        $widths = array(30, 85, 50, 50, 50);

        $this->addTableHeader($headers, $widths);

        // Add data rows
        foreach ($data as $row) {
            $this->Cell($widths[0], 6, $row['item_code'], 1, 0, 'C');
            $this->Cell($widths[1], 6, $row['name'], 1);
            $this->Cell($widths[2], 6, number_format($row['quantity']), 1, 0, 'R');
            $this->Cell($widths[3], 6, number_format($row['total_sales'], 2), 1, 0, 'R');
            $this->Cell($widths[4], 6, number_format($row['avg_price'], 2), 1, 0, 'R');
            $this->Ln();
        }

        // Add totals
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell($widths[0] + $widths[1], 7, 'Total', 1, 0, 'C');
        $this->Cell($widths[2], 7, number_format($totals['quantity']), 1, 0, 'R');
        $this->Cell($widths[3], 7, number_format($totals['sales'], 2), 1, 0, 'R');
        $this->Cell($widths[4], 7, number_format($totals['avg_price'], 2), 1, 0, 'R');
        $this->Ln();

        $this->addSignatureSection();
    }
}
?>