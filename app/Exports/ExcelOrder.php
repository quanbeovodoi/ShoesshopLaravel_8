<?php

namespace App\Exports;

use App\Models\Order as ModelsOrder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;


class ExcelOrder implements WithHeadings,WithMultipleSheets,FromQuery
{
    public function headings():array{
        return [
            'donhang_id',
            'madonhang',
            'ngaydathang',
            'ghichu',
            'tongtien',
            'thanhtoan',
            'trangthai',
            'magiamgia',
            'giamgia',
            'phivanchuyen',
            'khachhang_id'
        ];
    }
    // /**
    // * @return \Illuminate\Support\Collection
    // */
    use Exportable;

    public function __construct($fromday,$today)
    {
        $this->fromday = $fromday;
        $this->today = $today;
    }

    public function query()
    {
        return ModelsOrder::query()->whereDate('dondathang_ngay_dat_hang','>=', $this->fromday)
        ->whereDate('dondathang_ngay_dat_hang','<=', $this->today);
    }

    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new ExcelOrder($this->fromday,$this->today);
        $sheets[] = new ExcelOrderSize($this->fromday,$this->today);
        $sheets[] = new ExcelOrderProduct($this->fromday,$this->today);
        $sheets[] = new ExcelOrderDetail($this->fromday,$this->today);
        return $sheets;
    }
}
