<?php
// classes/ReportPDF.php
require_once('tcpdf/tcpdf.php');

class ReportPDF extends TCPDF
{
    private $report_title;

    public function __construct($orientation = 'P', $title = '')
    {
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

    protected function addReportHeader($date_range = '')
    {
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

    protected function addTableHeader($headers, $widths)
    {
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

    public function generateDailySalesReport($data, $totals, $date_range)
    {
        $this->AddPage('P', 'A4');
        $this->addReportHeader($date_range);

        // Define table structure
        $headers = array('Date', 'Repair Sales', 'Item Sales', 'Total Sales', 'No. of Invoices');
        $widths = array(35, 40, 40, 40, 35);

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
    }

    public function generateItemSalesReport($data, $totals, $date_range)
    {
        $this->AddPage('P', 'A4');
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
    }

    public function generateProfitLossReport($income_data, $expense_data, $totals, $date_range)
    {
        $this->AddPage('P', 'A4');
        $this->addReportHeader($date_range);

        // Income Section
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Income', 0, 1, 'L');

        // Income Table
        $headers = array('Income Category', 'Amount');
        $widths = array(120, 70);
        $this->addTableHeader($headers, $widths);

        // Income Data
        $this->SetFont('helvetica', '', 10);
        $this->Cell($widths[0], 7, 'Repair Services Income', 1);
        $this->Cell($widths[1], 7, number_format($income_data['repair_income'], 2), 1, 0, 'R');
        $this->Ln();
        $this->Cell($widths[0], 7, 'Item Sales Income', 1);
        $this->Cell($widths[1], 7, number_format($income_data['item_income'], 2), 1, 0, 'R');
        $this->Ln();

        // Total Income
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell($widths[0], 7, 'Total Income', 1, 0, 'L', true);
        $this->Cell($widths[1], 7, number_format($totals['total_income'], 2), 1, 0, 'R', true);
        $this->Ln(15);

        // Expenses Section
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Expenses', 0, 1, 'L');

        $headers = array('Expense Category', 'Amount');
        $this->addTableHeader($headers, $widths);

        // General Expenses
        $this->SetFont('helvetica', '', 10);
        foreach ($expense_data['general_expenses'] as $expense) {
            $this->Cell($widths[0], 7, $expense['category'], 1);
            $this->Cell($widths[1], 7, number_format($expense['total'], 2), 1, 0, 'R');
            $this->Ln();
        }

        // Supplier and Salary Payments
        $this->Cell($widths[0], 7, 'Supplier Payments', 1);
        $this->Cell($widths[1], 7, number_format($expense_data['supplier_payments'], 2), 1, 0, 'R');
        $this->Ln();

        $this->Cell($widths[0], 7, 'Salary Payments', 1);
        $this->Cell($widths[1], 7, number_format($expense_data['salary_payments'], 2), 1, 0, 'R');
        $this->Ln();

        // Total Expenses
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell($widths[0], 7, 'Total Expenses', 1, 0, 'L', true);
        $this->Cell($widths[1], 7, number_format($totals['total_expenses'], 2), 1, 0, 'R', true);
        $this->Ln(15);

        // Net Profit/Loss
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Summary', 0, 1, 'L');

        $this->SetFont('helvetica', 'B', 10);
        $profit_loss = $totals['total_income'] - $totals['total_expenses'];
        $profit_margin = $totals['total_income'] > 0 ? ($profit_loss / $totals['total_income'] * 100) : 0;

        $fillColor = $profit_loss >= 0 ? [200, 255, 200] : [255, 200, 200]; // Light green or light red
$this->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);

$this->Cell($widths[0], 7, 'Net ' . ($profit_loss >= 0 ? 'Profit' : 'Loss'), 1, 0, 'L', true);
$this->Cell($widths[1], 7, number_format(abs($profit_loss), 2), 1, 0, 'R', true);
$this->Ln();

        $this->Cell($widths[0], 7, 'Profit Margin', 1, 0, 'L', true);
        $this->Cell($widths[1], 7, number_format($profit_margin, 2) . '%', 1, 0, 'R', true);
        $this->Ln(20);
    }

    public function generateSalaryPaymentsReport($data, $totals, $date_range)
    {
        $this->AddPage();

        // Title and Date Range
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 10, 'Salary Payments Report', 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 10, 'Period: ' . $date_range, 0, 1, 'C');
        $this->Ln(5);

        // Table Header
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('helvetica', 'B', 10);

        $this->Cell(25, 7, 'Date', 1, 0, 'C', true);
        $this->Cell(50, 7, 'Employee', 1, 0, 'C', true);
        $this->Cell(30, 7, 'Phone', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Regular Amount', 1, 0, 'C', true);
        $this->Cell(35, 7, 'OT Amount', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Total Amount', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Status', 1, 1, 'C', true);

        // Table Data
        $this->SetFont('helvetica', '', 9);
        foreach ($data as $row) {
            $this->Cell(25, 6, date('Y-m-d', strtotime($row['payment_date'])), 1);
            $this->Cell(50, 6, $row['employee_name'], 1);
            $this->Cell(30, 6, $row['employee_phone'], 1);
            $this->Cell(35, 6, number_format($row['regular_amount'], 2), 1, 0, 'R');
            $this->Cell(35, 6, number_format($row['ot_amount'], 2), 1, 0, 'R');
            $this->Cell(35, 6, number_format($row['total_amount'], 2), 1, 0, 'R');
            $this->Cell(25, 6, ucfirst($row['payment_status']), 1, 1, 'C');
        }

        // Totals
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(105, 7, 'Totals (' . $totals['payments'] . ' payments)', 1, 0, 'R', true);
        $this->Cell(35, 7, number_format($totals['regular_amount'], 2), 1, 0, 'R', true);
        $this->Cell(35, 7, number_format($totals['ot_amount'], 2), 1, 0, 'R', true);
        $this->Cell(35, 7, number_format($totals['total_amount'], 2), 1, 0, 'R', true);
        $this->Cell(25, 7, '', 1, 1, 'C', true);
    }

    public function generateEmployeePerformanceReport($data, $totals, $date_range)
    {
        $this->AddPage('P', 'A4');

        // Title and Date Range
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 10, 'Employee Performance Report', 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 0, 'Period: ' . $date_range, 0, 1, 'C');

        // Employee Details
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Employee Details', 0, 1);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(30, 6, 'Name:', 0);
        $this->Cell(70, 6, $data[0]['employee_name'], 0);
        $this->Cell(30, 6, 'Phone:', 0);
        $this->Cell(0, 6, $data[0]['employee_phone'], 0, 1);
        $this->Ln(2);

        // Performance Summary
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Performance Summary', 0, 1);
        $this->SetFont('helvetica', '', 10);

        $this->Cell(50, 6, 'Total Working Hours:', 0);
        $this->Cell(50, 6, number_format($totals['working_hours'], 2) . ' hours', 0, 0);

        $this->Cell(50, 6, 'Total OT Hours:', 0);
        $this->Cell(50, 6, number_format($totals['ot_hours'], 2) . ' hours', 0, 1);

        $this->Cell(50, 6, 'Days Worked:', 0);
        $this->Cell(50, 6, number_format($totals['days_worked'], 1) . ' days', 0, 0);

        $this->Cell(50, 6, 'Total Earnings:', 0);
        $this->Cell(50, 6, 'Rs. ' . number_format($totals['total_amount'], 2), 0, 1);
        $this->Ln(2);

        // Detailed Performance Table
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Daily Performance Details', 0, 1);

        // Table Header
        $this->SetFillColor(220, 220, 220);
        $this->SetFont('helvetica', 'B', 9);

        $this->Cell(20, 7, 'Date', 1, 0, 'C', true);
        $this->Cell(20, 7, 'Status', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Time In', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Time Out', 1, 0, 'C', true);
        $this->Cell(20, 7, 'Work Hrs', 1, 0, 'C', true);
        $this->Cell(20, 7, 'OT Hrs', 1, 0, 'C', true);
        $this->Cell(30, 7, 'Regular Amount', 1, 0, 'C', true);
        $this->Cell(30, 7, 'OT Amount', 1, 1, 'C', true);

        // Table Data
        $this->SetFont('helvetica', '', 9);
        foreach ($data as $row) {
            $this->Cell(20, 6, date('Y-m-d', strtotime($row['attendance_date'])), 1, 0, 'C');
            $this->Cell(20, 6, ucfirst($row['status']), 1, 0, 'C');
            $this->Cell(25, 6, $row['time_in'], 1, 0, 'C');
            $this->Cell(25, 6, $row['time_out'], 1, 0, 'C');
            $this->Cell(20, 6, number_format($row['working_hours'], 2), 1, 0, 'C');
            $this->Cell(20, 6, number_format($row['ot_hours'], 2), 1, 0, 'C');
            $this->Cell(30, 6, number_format($row['day_amount'], 2), 1, 0, 'R');
            $this->Cell(30, 6, number_format($row['ot_amount'], 2), 1, 1, 'R');
        }

        // Totals
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(90, 7, 'Totals', 1, 0, 'R', true);
        $this->Cell(20, 7, number_format($totals['working_hours'], 2), 1, 0, 'C', true);
        $this->Cell(20, 7, number_format($totals['ot_hours'], 2), 1, 0, 'C', true);
        $this->Cell(30, 7, number_format($totals['regular_amount'], 2), 1, 0, 'R', true);
        $this->Cell(30, 7, number_format($totals['ot_amount'], 2), 1, 1, 'R', true);
    }

    public function generateSupplierTransactions($data, $totals, $date_range)
    {
        $this->AddPage('P', 'A4');

        // Title and Date Range
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 10, 'Supplier Balance Report', 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 10, 'Period: ' . $date_range, 0, 1, 'C');
        $this->Ln(5);

        // Supplier Details
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Supplier Details', 0, 1);
        $this->SetFont('helvetica', '', 10);

        $this->Cell(30, 6, 'Name:', 0);
        $this->Cell(70, 6, $data[0]['supplier_name'], 0);
        $this->Cell(30, 6, 'Phone:', 0);
        $this->Cell(0, 6, $data[0]['supplier_phone'], 0, 1);

        $this->Cell(30, 6, 'Contact:', 0);
        $this->Cell(70, 6, $data[0]['contact_person'], 0, 1);
        $this->Ln(5);

        // Balance Summary
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Balance Summary', 0, 1);
        $this->SetFont('helvetica', '', 10);

        $this->Cell(50, 6, 'Total Debit:', 0);
        $this->Cell(50, 6, 'Rs. ' . number_format($totals['debit'], 2), 0, 1);

        $this->Cell(50, 6, 'Total Credit:', 0);
        $this->Cell(50, 6, 'Rs. ' . number_format($totals['credit'], 2), 0, 1);

        $this->Cell(50, 6, 'Current Balance:', 0);
        $this->Cell(50, 6, 'Rs. ' . number_format($totals['balance'], 2), 0, 1);

        $this->Cell(50, 6, 'Total Transactions:', 0);
        $this->Cell(50, 6, $totals['transactions'], 0, 1);
        $this->Ln(5);

        // Transactions Table
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Transaction Details', 0, 1);

        // Table Header
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('helvetica', 'B', 9);

        $this->Cell(25, 7, 'Date', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Type', 1, 0, 'C', true);
        $this->Cell(65, 7, 'Description', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Debit', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Credit', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Balance', 1, 1, 'C', true);

        // Table Data
        $this->SetFont('helvetica', '', 9);
        foreach ($data as $row) {
            $this->Cell(25, 6, date('Y-m-d', strtotime($row['transaction_date'])), 1);
            $this->Cell(25, 6, ucfirst($row['transaction_type']), 1, 0, 'C');
            $this->Cell(65, 6, $row['description'], 1);
            $this->Cell(25, 6, $row['transaction_type'] == 'debit' ? number_format($row['amount'], 2) : '-', 1, 0, 'R');
            $this->Cell(25, 6, $row['transaction_type'] == 'credit' ? number_format($row['amount'], 2) : '-', 1, 0, 'R');
            $this->Cell(25, 6, number_format($row['balance'], 2), 1, 1, 'R');
        }

        // Totals
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(115, 7, 'Totals', 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totals['debit'], 2), 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totals['credit'], 2), 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totals['balance'], 2), 1, 1, 'R', true);
    }

    public function generateSupplierBalanceReport($data, $totals, $date_range)
    {
        $this->AddPage('P', 'A4');

        // Header
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 10, 'Supplier Balance Report', 0, 1, 'C');

        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 5, 'Period: ' . $date_range, 0, 1, 'C');
        $this->Ln(5);

        // Table Header
        $this->SetFillColor(220, 220, 220);
        $this->SetFont('helvetica', 'B', 10);

        $this->Cell(45, 7, 'Supplier Name', 1, 0, 'C', true);
        $this->Cell(45, 7, 'Contact Person', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Phone', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Total Debit', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Total Credit', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Balance', 1, 1, 'C', true);

        // Table Data
        $this->SetFont('helvetica', '', 9);
        foreach ($data as $row) {
            $this->Cell(45, 7, $row['supplier_name'], 1);
            $this->Cell(45, 7, $row['contact_person'], 1);
            $this->Cell(25, 7, $row['supplier_phone'], 1, 0, 'C');
            $this->Cell(25, 7, number_format($row['total_debit'], 2), 1, 0, 'R');
            $this->Cell(25, 7, number_format($row['total_credit'], 2), 1, 0, 'R');
            $this->Cell(25, 7, number_format($row['balance'], 2), 1, 1, 'R');
        }

        // Totals
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(115, 7, 'Totals', 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totals['debit'], 2), 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totals['credit'], 2), 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totals['balance'], 2), 1, 1, 'R', true);
    }

    public function generateCustomerHistoryReport($customer, $repair_data, $item_data, $pending_data, $totals, $date_range)
    {
        $this->AddPage('P', 'A4');

        // Header
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 10, 'Customer History Report', 0, 1, 'C');

        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 5, 'Period: ' . $date_range, 0, 1, 'C');
        $this->Ln(5);

        // Customer Details
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(30, 7, 'Customer:', 0);
        $this->SetFont('helvetica', '', 11);
        $this->Cell(100, 7, $customer['name'], 0);

        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(20, 7, 'Phone:', 0);
        $this->SetFont('helvetica', '', 11);
        $this->Cell(60, 7, $customer['phone'], 0, 1);

        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(30, 7, 'Address:', 0);
        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 7, $customer['address'], 0, 1);
        $this->Ln(5);

        // Repair History
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Repair History', 0, 1);

        // Table Header
        $this->SetFillColor(220, 220, 220);
        $this->SetFont('helvetica', 'B', 10);

        $this->Cell(25, 7, 'Date', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Invoice #', 1, 0, 'C', true);
        $this->Cell(70, 7, 'Vehicle', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Amount', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Status', 1, 1, 'C', true);

        // Repair Data
        $this->SetFont('helvetica', '', 9);
        $repair_total = 0;
        foreach ($repair_data as $row) {
            $vehicle_info = $row['registration_number'] . ' - ' . $row['make'] . ' ' . $row['model'];
            $this->Cell(25, 7, date('Y-m-d', strtotime($row['invoice_date'])), 1);
            $this->Cell(35, 7, $row['invoice_number'], 1);
            $this->Cell(70, 7, $vehicle_info, 1);
            $this->Cell(35, 7, number_format($row['total_amount'], 2), 1, 0, 'R');
            $this->Cell(25, 7, ucfirst($row['payment_status']), 1, 1, 'C');
            $repair_total += $row['total_amount'];
        }

        // Repair Total
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(130, 7, 'Total Repair Amount', 1, 0, 'R', true);
        $this->Cell(35, 7, number_format($repair_total, 2), 1, 0, 'R', true);
        $this->Cell(25, 7, '', 1, 1, '', true);
        $this->Ln(10);

        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Item Sales History', 0, 1);

        // Table Header
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(45, 7, 'Date', 1, 0, 'C', true);
        $this->Cell(50, 7, 'Invoice #', 1, 0, 'C', true);
        $this->Cell(45, 7, 'Status', 1, 0, 'C', true);
        $this->Cell(50, 7, 'Amount', 1, 1, 'C', true);

        // Item Data
        $this->SetFont('helvetica', '', 9);
        $item_total = 0;
        foreach ($item_data as $row) {
            $this->Cell(45, 7, date('Y-m-d', strtotime($row['invoice_date'])), 1, 0,'C');
            $this->Cell(50, 7, $row['invoice_number'], 1, 0,'C');
            $this->Cell(45, 7, ucfirst($row['payment_status']), 1, 0, 'C');
            $this->Cell(50, 7, number_format($row['total_amount'], 2), 1, 1, 'R');
            $item_total += $row['total_amount'];
        }

        // Item Total
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(140, 7, 'Total Item Amount', 1, 0, 'R', true);
        $this->Cell(50, 7, number_format($item_total, 2), 1, 0, 'R', true);
        $this->Ln(10);

        // Pending Payments
        if (!empty($pending_data)) {
            $this->Ln(10);

            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'Pending Payments', 0, 1);

            // Table Header
            $this->SetFillColor(220, 220, 220);
            $this->SetFont('helvetica', 'B', 10);

            $this->Cell(25, 7, 'Date', 1, 0, 'C', true);
            $this->Cell(25, 7, 'Type', 1, 0, 'C', true);
            $this->Cell(35, 7, 'Invoice #', 1, 0, 'C', true);
            $this->Cell(35, 7, 'Amount', 1, 0, 'C', true);
            $this->Cell(35, 7, 'Paid', 1, 0, 'C', true);
            $this->Cell(35, 7, 'Balance', 1, 1, 'C', true);

            // Pending Data
            $this->SetFont('helvetica', '', 9);
            $pending_total = 0;
            foreach ($pending_data as $row) {
                $balance = $row['total_amount'] - $row['paid_amount'];
                $pending_total += $balance;

                $this->Cell(25, 7, date('Y-m-d', strtotime($row['invoice_date'])), 1, 0, 'C');
                $this->Cell(25, 7, $row['type'], 1, 0, 'C');
                $this->Cell(35, 7, $row['invoice_number'], 1, 0, 'C');
                $this->Cell(35, 7, number_format($row['total_amount'], 2), 1, 0, 'R');
                $this->Cell(35, 7, number_format($row['paid_amount'], 2), 1, 0, 'R');
                $this->Cell(35, 7, number_format($balance, 2), 1, 1, 'R');
            }

            // Pending Total
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(155, 7, 'Total Pending Amount', 1, 0, 'R', true);
            $this->Cell(35, 7, number_format($pending_total, 2), 1, 1, 'R', true);
        }

        $this->Ln(10);

        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Summary', 0, 1);

        $this->SetFont('helvetica', '', 11);
        $this->Cell(60, 7, 'Total Repair Amount:', 0);
        $this->Cell(50, 7, formatCurrency($repair_total), 0, 1, 'R');

        $this->Cell(60, 7, 'Total Item Amount:', 0);
        $this->Cell(50, 7, formatCurrency($item_total), 0, 1, 'R');

        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(60, 7, 'Total Amount:', 0);
        $this->Cell(50, 7, formatCurrency($repair_total + $item_total), 0, 1, 'R');

        if (!empty($pending_data)) {
            $this->SetTextColor(255, 0, 0); // Red color for pending amount
            $this->Cell(60, 7, 'Total Pending Amount:', 0);
            $this->Cell(50, 7, formatCurrency($pending_total), 0, 1, 'R');
            $this->SetTextColor(0); // Reset to black
        }
    }
    public function generateVehicleHistoryReport($vehicle, $repair_data, $totals, $date_range) {
        $this->AddPage('P');  // Portrait orientation
    
        // Header
        $this->SetFont('Helvetica', 'B', 14);
        $this->Cell(0, 10, 'Vehicle History Report', 0, 1, 'C');
    
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(0, 5, 'Period: ' . $date_range, 0, 1, 'C');
        $this->Ln(10);
    
        // Vehicle Details
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(30, 6, 'Registration:', 0, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(70, 6, $vehicle['registration_number'], 0, 0);
        
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(30, 6, 'Make:', 0, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(60, 6, $vehicle['make'], 0, 1);
    
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(30, 6, 'Model:', 0, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(70, 6, $vehicle['model'], 0, 0);
    
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(30, 6, 'Year:', 0, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(60, 6, $vehicle['year'], 0, 1);
    
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(30, 6, 'Owner:', 0, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(70, 6, $vehicle['customer_name'], 0, 0);
    
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(30, 6, 'Phone:', 0, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(60, 6, $vehicle['customer_phone'], 0, 1);
        $this->Ln(10);
    
        // Repair History Section
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(0, 6, 'Repair History', 0, 1);
    
        // Table Header
        $col_widths = [25, 30, 90, 20, 25];
    
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell($col_widths[0], 7, 'Date', 1, 0, 'C');
        $this->Cell($col_widths[1], 7, 'Invoice #', 1, 0, 'C');
        $this->Cell($col_widths[2], 7, 'Repair Items', 1, 0, 'C');
        $this->Cell($col_widths[3], 7, 'Status', 1, 0, 'C');
        $this->Cell($col_widths[4], 7, 'Amount', 1, 1, 'C');
    
        // Table Data
        $this->SetFont('Helvetica', '', 10);
        foreach ($repair_data as $row) {
            $repair_items = explode("\n", $row['repair_items']);
            $item_count = count($repair_items);
            $first_item = true;
        
            foreach ($repair_items as $item) {
                if ($item_count == 1) {
                    $this->Cell($col_widths[0], 7, date('Y-m-d', strtotime($row['invoice_date'])), 1, 0, 'C');
                    $this->Cell($col_widths[1], 7, $row['invoice_number'], 1, 0, 'C');
                    $this->Cell($col_widths[2], 7, trim($item), 1, 0, 'L');
                    $this->Cell($col_widths[3], 7, ucfirst($row['payment_status']), 1, 0, 'C');
                    $this->Cell($col_widths[4], 7, number_format($row['total_amount'], 2), 1, 1, 'R');
                    $first_item = false;
                } else{
                    if ($first_item) {
                        // First row with all details
                        $this->Cell($col_widths[0], 5, date('Y-m-d', strtotime($row['invoice_date'])), 'LR', 0, 'C');
                        $this->Cell($col_widths[1], 5, $row['invoice_number'], 'LR', 0, 'C');
                        $this->Cell($col_widths[2], 5, trim($item), 'LR', 0, 'L');
                        $this->Cell($col_widths[3], 5, ucfirst($row['payment_status']), 'LR', 0, 'C');
                        $this->Cell($col_widths[4], 5, number_format($row['total_amount'], 2), 'LR', 1, 'R');
                        $first_item = false;
                    } else {
                        // Additional items without bottom lines
                        $this->Cell($col_widths[0], 5, '', 'LR', 0, 'C');
                        $this->Cell($col_widths[1], 5, '', 'LR', 0, 'C');
                        $this->Cell($col_widths[2], 5, trim($item), 'LR', 0, 'L');
                        $this->Cell($col_widths[3], 5, '', 'LR', 0, 'C');
                        $this->Cell($col_widths[4], 5, '', 'LR', 1, 'R');
                    }
                }
            }
        }
        
    
        // Total Row
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell($col_widths[0] + $col_widths[1] + $col_widths[2] + $col_widths[3], 7, 'Total Amount', 1, 0, 'R');
        $this->Cell($col_widths[4], 7, number_format($totals['repair_amount'], 2), 1, 1, 'R');
    }
}
