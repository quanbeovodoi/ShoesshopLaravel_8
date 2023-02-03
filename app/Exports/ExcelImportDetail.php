<?php

namespace App\Exports;

use App\Models\ProductImport as ModelsImport;
use App\Models\ProductImportDetail as ModelsImportDetail;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;


class ExcelImportDetail implements FromQuery,WithHeadings
{
    public function headings():array{
        return [
            'chitietnhap_id',
            'soluongnhap',
            'gianhap',
            'sanpham_id',
            'donnhap_id',
            'size_id',
            'madonnhap'
        ];
    }

    use Exportable;

    public function __construct($fromday,$today)
    {
        $this->fromday = $fromday;
        $this->today = $today;
    }

    public function query()
    {
        $order=ModelsImport::query()->select('id')->whereDate('donnhaphang_ngay_nhap','>=', $this->fromday)
        ->whereDate('donnhaphang_ngay_nhap','<=', $this->today);
        return ModelsImportDetail::query()->whereIn('donnhaphang_id',$order);
    }

}
