<?php

namespace App\Exports;

use App\Models\ProductInStock as ModelsInStock;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;


class ExcelInStock implements FromCollection,WithHeadings,WithMultipleSheets
{
    public function headings():array{
        return [
            'matonkho',
            'soluongdaban',
            'soluongton',
            'sanpham_id',
            'size_id'
        ];
    }
   /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ModelsInStock::all();

    }

    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new ExcelInStock();
        $sheets[] = new ExcelInStockSize();
        $sheets[] = new ExcelInStockProduct();
        return $sheets;
    }
}
