<?php

namespace App\Exports;

use App\Models\Order as ModelsOrder;
use App\Models\OrderDetail as ModelsOrderDetail;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;


class ExcelOrderDetail implements FromQuery,WithHeadings
{
    public function headings():array{
        return [
            'chitietdonhang_id',
            'sanpham_id',
            'size_id',
            'madonhang',
            'dongia',
            'soluong',
            'donhang_id'
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
        $order=ModelsOrder::query()->select('id')->whereDate('dondathang_ngay_dat_hang','>=', $this->fromday)
        ->whereDate('dondathang_ngay_dat_hang','<=', $this->today);
        return ModelsOrderDetail::query()->whereIn('dondathang_id',$order);
    }

}
