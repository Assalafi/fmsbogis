<?php

namespace App\Exports;

use App\Models\Account;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashbookExport implements FromArray, WithEvents, WithStrictNullComparison, WithTitle
{
    private const ACCOUNTING_FORMAT = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';

    public function __construct(
        private readonly Account $account,
        private readonly Collection $cashbookRows,
        private readonly array $summary,
        private readonly string $periodLabel,
        private readonly string $organizationName,
        private readonly string $mdaCode = 'BOGIS',
    ) {}

    public function title(): string
    {
        return 'Cashbook';
    }

    public function array(): array
    {
        $rows = [
            $this->row('BORNO STATE GOVERNMENT OF NIGERIA'),
            $this->row('OFFICE OF THE ACCOUNTANT GENERAL'),
            $this->row('Treasury Cash Book for the Month of '.$this->periodLabel),
            $this->row(),
            $this->row(null, 'NAME OF MDA:', $this->organizationName, null, null, null, null, null, null, null, null, 'MDA CODE:', null, $this->mdaCode),
            $this->row('DR.', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, 'CR.'),
            [
                'DATE', 'TREASURY RECEIPT No.', 'BANK CREDIT SLIP No.', 'FROM WHOM RECEIVED',
                'TREASURY VOUCHER No.', 'EXPENDITURE CREDITS', 'ECONOMIC CODE', "GROSS\n₦", "CASH\n₦", "BANK\n₦",
                'DATE', 'TO WHOM PAID', 'DEPT. VOUCHER No.', 'TREASURY VOUCHER No.',
                'CHEQUE/MANDATE No.', 'ECONOMIC CODE', "GROSS\n₦", "CASH\n₦", "BANK\n₦",
            ],
        ];

        foreach ($this->cashbookRows as $cashbookRow) {
            $debit = $cashbookRow['debit'];
            $credit = $cashbookRow['credit'];

            $rows[] = [
                $debit ? Date::dateTimeToExcel($debit['date']) : null,
                $debit['treasury_receipt_number'] ?? null,
                $debit['bank_credit_slip_number'] ?? null,
                $debit['from_whom_received'] ?? null,
                $debit['treasury_voucher_number'] ?? null,
                $debit['expenditure_credits'] ?? null,
                $debit['economic_code'] ?? null,
                $debit['gross'] ?? null,
                $debit['cash'] ?? null,
                $debit['bank'] ?? null,
                $credit ? Date::dateTimeToExcel($credit['date']) : null,
                $credit['to_whom_paid'] ?? null,
                $credit['dept_voucher_number'] ?? null,
                $credit['treasury_voucher_number'] ?? null,
                $credit['cheque_mandate_number'] ?? null,
                $credit['economic_code'] ?? null,
                $credit['gross'] ?? null,
                $credit['cash'] ?? null,
                $credit['bank'] ?? null,
            ];
        }

        while (count($rows) < $this->totalRow() - 1) {
            $rows[] = $this->row();
        }

        $dataEndRow = $this->totalRow() - 1;
        $rows[] = $this->row(
            null, null, null, null, null, null, null,
            "=SUM(H8:H{$dataEndRow})", "=SUM(I8:I{$dataEndRow})", "=SUM(J8:J{$dataEndRow})",
            null, null, null, null, null, null,
            "=SUM(Q8:Q{$dataEndRow})", "=SUM(R8:R{$dataEndRow})", "=SUM(S8:S{$dataEndRow})",
        );
        $rows[] = $this->row();
        $rows[] = $this->row();
        $rows[] = $this->row(null, null, null, null, null, 'CASH BOOK SUMMARY FOR THE MONTH OF');
        $rows[] = $this->row(null, null, null, null, null, null, null, 'GROSS');
        $rows[] = $this->row(null, null, null, null, null, null, null, '₦');
        $rows[] = $this->row(null, null, null, null, null, 'Opening Balance', null, (float) $this->summary['opening_balance']);
        $rows[] = $this->row(null, null, null, null, null, 'Add: Receipts', null, '=H'.$this->totalRow());
        $rows[] = $this->row(null, null, null, null, null, 'Funds Available', null, '=H'.$this->summaryOpeningRow().'+H'.$this->summaryReceiptsRow());
        $rows[] = $this->row(null, null, null, null, null, 'Less: Payment', null, '=Q'.$this->totalRow());
        $rows[] = $this->row(null, null, null, null, null, 'Closing Balance', null, '=H'.$this->summaryFundsRow().'-H'.$this->summaryPaymentsRow());

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $this->formatSheet($sheet);
            },
        ];
    }

    private function formatSheet(Worksheet $sheet): void
    {
        $totalRow = $this->totalRow();
        $summaryTitleRow = $this->summaryTitleRow();
        $summaryEndRow = $this->summaryClosingRow();

        $sheet->mergeCells('A1:S1');
        $sheet->mergeCells('A2:S2');
        $sheet->mergeCells('A3:S3');
        $sheet->mergeCells('C5:K5');
        $sheet->mergeCells('L5:M5');
        $sheet->mergeCells('N5:R5');

        $sheet->getStyle("A1:S{$summaryEndRow}")->getFont()->setName('Calibri')->setSize(11);
        $sheet->getStyle('A1:S3')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1:S3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5')->getFont()->setBold(true);
        $sheet->getStyle('C5:K5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('L5:M5')->getFont()->setBold(true);
        $sheet->getStyle('L5:M5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('N5:R5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:S6')->getFont()->setBold(true);

        $sheet->getStyle("A7:S{$totalRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FF000000');
        $sheet->getStyle('A7:S7')->getFont()->setBold(true);
        $sheet->getStyle('A7:S7')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_BOTTOM)
            ->setWrapText(true);
        $sheet->getStyle("A8:S{$totalRow}")->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
        $sheet->getStyle("B8:C{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("M8:P{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D8:D{$totalRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("L8:L{$totalRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A{$totalRow}:S{$totalRow}")->getFont()->setBold(true);

        $sheet->getStyle("A8:A{$totalRow}")->getNumberFormat()->setFormatCode('dd/mmm/yyyy');
        $sheet->getStyle("K8:K{$totalRow}")->getNumberFormat()->setFormatCode('dd/mmm/yyyy');
        $sheet->getStyle("B8:G{$totalRow}")->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle("M8:P{$totalRow}")->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle("H8:J{$totalRow}")->getNumberFormat()->setFormatCode(self::ACCOUNTING_FORMAT);
        $sheet->getStyle("Q8:S{$totalRow}")->getNumberFormat()->setFormatCode(self::ACCOUNTING_FORMAT);

        $sheet->getStyle("F{$summaryTitleRow}")->getFont()->setBold(true);
        $sheet->getStyle("F{$summaryTitleRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle('H'.$this->summaryGrossRow().':H'.$this->summaryCurrencyRow())->getFont()->setBold(true);
        $sheet->getStyle('H'.$this->summaryGrossRow().':H'.$this->summaryCurrencyRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H'.$this->summaryOpeningRow().':H'.$this->summaryClosingRow())->getNumberFormat()->setFormatCode(self::ACCOUNTING_FORMAT);
        $sheet->getStyle('F'.$this->summaryClosingRow().':H'.$this->summaryClosingRow())->getFont()->setBold(true);

        foreach ($this->columnWidths() as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->getRowDimension(1)->setRowHeight(21);
        $sheet->getRowDimension(2)->setRowHeight(21);
        $sheet->getRowDimension(3)->setRowHeight(21);
        $sheet->getRowDimension(7)->setRowHeight(63);
        $sheet->getRowDimension($summaryTitleRow)->setRowHeight(48);

        $sheet->setShowGridlines(true);
        $sheet->getSheetView()->setZoomScale(70);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(1)
            ->setPrintArea("A1:S{$summaryEndRow}");
        $sheet->getPageMargins()
            ->setLeft(0.2)
            ->setRight(0.2)
            ->setTop(0.75)
            ->setBottom(0.5)
            ->setHeader(0.3)
            ->setFooter(0.3);

        $sheet->getParent()->getProperties()
            ->setTitle('Cashbook - '.$this->account->account_name)
            ->setSubject('Treasury cashbook for '.$this->periodLabel)
            ->setDescription($this->organizationName.' treasury cashbook');
    }

    private function totalRow(): int
    {
        return 8 + max(36, $this->cashbookRows->count());
    }

    private function summaryTitleRow(): int
    {
        return $this->totalRow() + 3;
    }

    private function summaryGrossRow(): int
    {
        return $this->summaryTitleRow() + 1;
    }

    private function summaryCurrencyRow(): int
    {
        return $this->summaryTitleRow() + 2;
    }

    private function summaryOpeningRow(): int
    {
        return $this->summaryTitleRow() + 3;
    }

    private function summaryReceiptsRow(): int
    {
        return $this->summaryTitleRow() + 4;
    }

    private function summaryFundsRow(): int
    {
        return $this->summaryTitleRow() + 5;
    }

    private function summaryPaymentsRow(): int
    {
        return $this->summaryTitleRow() + 6;
    }

    private function summaryClosingRow(): int
    {
        return $this->summaryTitleRow() + 7;
    }

    private function columnWidths(): array
    {
        return [
            'A' => 13.44, 'B' => 13.11, 'C' => 11.55, 'D' => 28.89, 'E' => 13.11,
            'F' => 12.89, 'G' => 12, 'H' => 18.89, 'I' => 16, 'J' => 17.89,
            'K' => 17.11, 'L' => 41.11, 'M' => 12.33, 'N' => 14.66, 'O' => 14.11,
            'P' => 12.33, 'Q' => 19.11, 'R' => 16.33, 'S' => 16.11,
        ];
    }

    private function row(mixed ...$values): array
    {
        return array_pad($values, 19, null);
    }
}
