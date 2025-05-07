<?php

namespace App\Exports;

use App\Models\Isu;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IsusExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $isus;

    /**
     * Constructor untuk menerima koleksi isu yang sudah difilter
     *
     * @param \Illuminate\Database\Eloquent\Collection $isus
     */
    public function __construct($isus)
    {
        $this->isus = $isus;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->isus;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Judul',
            'Tanggal',
            'Kategori',
            'Tone',
            'Skala',
            'Status',
            'Dibuat Oleh',
            'Isu Strategis',
            'Tanggal Dibuat',
            'Terakhir Diupdate',
        ];
    }

    /**
     * @param mixed $isu
     * @return array
     */
    public function map($isu): array
    {
        return [
            $isu->id,
            $isu->judul,
            $isu->tanggal ? $isu->tanggal->format('d/m/Y') : '-',
            $isu->kategoris->pluck('nama')->implode(', '),
            $isu->refTone ? $isu->refTone->nama : '-',
            $isu->refSkala ? $isu->refSkala->nama : '-',
            $isu->status ? $isu->status->nama : 'Draft',
            $isu->creator ? $isu->creator->name : '-',
            $isu->isu_strategis ? 'Ya' : 'Tidak',
            $isu->created_at ? $isu->created_at->format('d/m/Y H:i') : '-',
            $isu->updated_at ? $isu->updated_at->format('d/m/Y H:i') : '-',
        ];
    }

    /**
     * Styling untuk worksheet
     *
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style untuk header row
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E2EFDA',
                ],
            ],
        ]);

        // Auto filter untuk memudahkan pengguna menyaring data
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
    }
}
