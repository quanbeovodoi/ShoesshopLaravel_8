<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    public $timestamps = false;
    protected $fillable = [
    	'size','size_thu_tu', 'size_trang_thai'
    ];
    protected $primaryKey = 'id';
 	protected $table = 'tbl_size';

    public function ProductImportDetail(){
        return $this->hasMany('App\Models\ProductImportDetail');
    }
    public function OrderDetail(){
        return $this->hasMany('App\Models\OrderDetail');
    }
    public function ProductInStock(){
        return $this->hasMany('App\Models\ProductInStock');
    }
}
