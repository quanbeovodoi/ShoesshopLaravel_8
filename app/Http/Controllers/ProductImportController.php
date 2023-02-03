<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductImport;
use App\Models\ProductInStock;
use App\Models\ProductImportDetail;
use App\Models\Size;
use App\Models\Supplier;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Session;

session_start();

class ProductImportController extends Controller {
	public function Index() {
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $this->UpdateIdImportDetail();
            $all_product_import = ProductImport::orderby('id', 'desc')->paginate(5);
            return view('admin.pages.product_import.product_import')->with('all_product_import', $all_product_import);
        }
    }
    public function UpdateIdImportDetail(){
        $all_detail=ProductImportDetail::all();
        $all_import=ProductImport::all();
        foreach($all_import as $key=>$import){
            foreach($all_detail as $k=>$detail){
                if($import->donnhaphang_ma_don_nhap_hang==$detail->chitietnhap_ma_don_nhap_hang){
                    $detail_update=ProductImportDetail::find($detail->id);
                    $detail_update->donnhaphang_id=$import->id;
                    $detail_update->save();
                }
            }
        }
    }
	public function AuthLogin() {
		$admin_id = Session::get('admin_id');
		if ($admin_id) {
			return Redirect::to('/dashboard');
		} else {
			return Redirect::to('/admin')->send();
		}
	}
	public function ProductImportAddMultiple() {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $this->UpdateIdImportDetail();
            $admin = Admin::where('user_id', Session::get('admin_id'))->get();
            $all_product = Product::orderby('tbl_sanpham.id', 'desc')->paginate(5);
            $all_supplier = Supplier::orderby('id', 'desc')->get();
            $all_size = Size::orderby('id', 'desc')->get();
            return view('admin.pages.product_import.product_import_add_multiple')
            ->with('all_product', $all_product)
            ->with('get_admin', $admin)
            ->with('all_size', $all_size)
            ->with('all_supplier', $all_supplier);
        }
	}
	public function ProductImportAddQueue(Request $request) {
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $data = $request->all();
            $session_id = substr(md5(microtime()) . rand(0, 26), 5);
            $queue = Session::get('queue');
            $price = 1;
            $qty = 1;
            if ($queue) {
                $queue[] = array(
                    'session_id' => $session_id,
                    'product_name' => $data['product_name'],
                    'product_image' => $data['product_image'],
                    'product_id' => $data['product_id'],
                    'product_quantity' => $qty,
                    'product_price' => $price,
                    'product_total' => $price * $qty,
                );
            } else {
                $queue[] = array(
                    'session_id' => $session_id,
                    'product_name' => $data['product_name'],
                    'product_image' => $data['product_image'],
                    'product_id' => $data['product_id'],
                    'product_quantity' => $qty,
                    'product_price' => $price,
                    'product_total' => $price * $qty,
                );
            }
            Session::put('queue', $queue);
            Session::save();
        }
    }

	public function ProductImportDeleteRowQueue(Request $request) {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $data = $request->all();
            $queue = Session::get('queue');
            if ($queue == true) {
                foreach ($queue as $key => $value) {
                    if ($value['session_id'] == $data['product_session_id']) {
                        unset($queue[$key]);
                    }
                }
                Session::put('queue', $queue);
                Session::save();
            }
        }
	}

	public function ProductImportAddMultipleSave(Request $request) {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $data = $request->all();
            $this->validate($request,[
                'product_import_no' => 'bail|required|max:255|min:6',
            ],
            [
                'required' => 'Không được để trống',
                'min' => 'Quá ngắn',
                'max' => 'Quá dài',
            ]);
            $admin = Admin::where('user_id', Session::get('admin_id'))->first();
            $all_product_import = ProductImport::where('donnhaphang_ma_don_nhap_hang', '=', $data['product_import_no'])->exists();
            if ($all_product_import) {
                return Redirect::to('/product-import-add-multiple')->with('error', 'Thêm không thành công, đơn nhập đã tồn tại!');
            } else {
                $product_import = new ProductImport();
                $product_import->donnhaphang_ma_don_nhap_hang = $data['product_import_no'];
                $product_import->donnhaphang_ngay_nhap = $data['product_import_day'];
                $product_import->donnhaphang_trang_thai = $data['product_import_status'];
                $product_import->nhacungcap_id = $data['product_import_supplier'];
                $product_import->admin_id = $admin->id;
                $queue = Session::get('queue');
                foreach ($data['session_id'] as $key => $value) {
                    foreach ($queue as $k => $v) {
                        if ($v['session_id'] == $key) {
                            $queue[$k]['product_quantity'] = $data['product_quantity'][$key];
                            $queue[$k]['product_price'] = $data['product_price'][$key];
                            $queue[$k]['product_size_id'] = $data['product_size_id'][$key];
                            $queue[$k]['product_total'] = $data['product_total'][$key];
                        }
                    }
                }
                $total = 0;
                foreach ($queue as $key => $value) {
                    $get_product_import_detail = ProductImportDetail::where('sanpham_id', '=', $value['product_id'])
                    ->where('size_id', '=', $value['product_size_id'])->first();
                    $get_product_in_stock = ProductInstock::where('sanpham_id', '=', $value['product_id'])
                    ->where('size_id', '=', $value['product_size_id'])->first();
                    if (!$get_product_import_detail && !$get_product_in_stock) {//sp mới chưa tồn kho
                        $import_product_detail = new ProductImportDetail();
                        $import_product_detail->chitietnhap_so_luong_nhap = $value['product_quantity'];
                        $import_product_detail->chitietnhap_gia_nhap = $value['product_price'];
                        $import_product_detail->size_id = $value['product_size_id'];
                        $import_product_detail->sanpham_id = $value['product_id'];
                        $import_product_detail->chitietnhap_ma_don_nhap_hang = $data['product_import_no'];
                        $product_in_stock= new ProductInstock();
                        $product_in_stock->sanpham_id=$value['product_id'];
                        $product_in_stock->sanphamtonkho_so_luong_da_ban=0;
                        $product_in_stock->sanphamtonkho_so_luong_ton=$value['product_quantity'];
                        $product_in_stock->size_id=$value['product_size_id'];
                        $product_in_stock->save();
                        $import_product_detail->save();
                        $total += $value['product_total'];
                    }else{ // có tồn kho
                        if(!$get_product_import_detail){
                            $import_product_detail = new ProductImportDetail();
                            $import_product_detail->chitietnhap_so_luong_nhap = $value['product_quantity'];
                            $import_product_detail->chitietnhap_gia_nhap = $value['product_price'];
                            $import_product_detail->size_id =  $value['product_size_id'];
                            $import_product_detail->sanpham_id = $value['product_id'];
                            $import_product_detail->chitietnhap_ma_don_nhap_hang = $data['product_import_no'];
                            $import_product_in_stock_update=ProductInstock::find($get_product_in_stock->id);
                            $import_product_in_stock_update->sanphamtonkho_so_luong_ton += $value['product_quantity'];
                            $import_product_in_stock_update->save();
                            $import_product_detail->save();
                            $total += $value['product_total'];
                        }else{
                            $import_product_detail = new ProductImportDetail();
                            $import_product_detail->chitietnhap_so_luong_nhap = $value['product_quantity'];
                            $import_product_detail->chitietnhap_gia_nhap = $value['product_price'];
                            $import_product_detail->size_id =  $value['product_size_id'];
                            $import_product_detail->sanpham_id = $value['product_id'];
                            $import_product_detail->chitietnhap_ma_don_nhap_hang = $data['product_import_no'];
                            $import_product_in_stock_update=ProductInstock::find($get_product_in_stock->id);
                            $import_product_in_stock_update->sanphamtonkho_so_luong_ton += $value['product_quantity'];
                            $import_product_in_stock_update->save();
                            $import_product_detail->save();
                            $total += $value['product_total'];
                        }
                    }
                }
                $product_import->donnhaphang_tong_tien = $total;
                $product_import->save();
                Session::forget('queue');
                return Redirect::to('/product-import')->with('message', 'Thêm thành công!');
            }
        }
	}

	public function UnactiveProductImport($product_import_id) {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $active_product_import = ProductImport::find($product_import_id);
            if (!$active_product_import) {
                return Redirect::to('/product-import')->with('error', 'Không tồn tại!');
            } else {
                $all_import_detail=ProductImportDetail::where('donnhaphang_id',$product_import_id)->get();
                $active_product_import->donnhaphang_trang_thai = 0;
                $count=0;
                foreach ($all_import_detail as $key => $value) {
                    $product_in_stock=ProductInStock::where('sanpham_id', $value->sanpham_id)
                    ->where('size_id', $value->size_id)->first();
                    if($value->chitietnhap_so_luong_nhap <= $product_in_stock->sanphamtonkho_so_luong_ton){
                        $count++;
                    }else{
                        return Redirect::to('/product-import')->with('error', 'Hủy đơn nhập không thành công!');
                    }
                }
                if($count==$all_import_detail->count()){
                    foreach ($all_import_detail as $k => $val) {
                        $product_in_stock_first=ProductInStock::where('sanpham_id', $val->sanpham_id)
                        ->where('size_id', $val->size_id)->first();
                        $product_in_stock_update=ProductInStock::find($product_in_stock_first->id);
                        $product_in_stock_update->sanphamtonkho_so_luong_ton -= $val->chitietnhap_so_luong_nhap;
                        $product_in_stock_update->save();
                    }
                }elseif($count<$all_import_detail->count()){
                    return Redirect::to('/product-import')->with('error', 'Hủy đơn nhập không thành công!');
                }
                $active_product_import->save();
                return Redirect::to('/product-import')->with('message', 'Hủy đơn nhập thành công!');
            }
        }
	}
	public function ActiveProductImport($product_import_id) {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $active_product_import = ProductImport::find($product_import_id);
            if (!$active_product_import) {
                return Redirect::to('/product-import')->with('error', 'Không tồn tại!');
            } else {
                $all_import_detail=ProductImportDetail::where('donnhaphang_id',$product_import_id)->get();
                $active_product_import->donnhaphang_trang_thai = 1;
                foreach ($all_import_detail as $key => $value) {
                    $product_in_stock=ProductInStock::where('sanpham_id', $value->sanpham_id)
                    ->where('size_id', $value->size_id)->first();
                    $product_in_stock_update=ProductInStock::find($product_in_stock->id);
                    $product_in_stock_update->sanphamtonkho_so_luong_ton += $value->chitietnhap_so_luong_nhap;
                    $product_in_stock_update->save();
                }
                $active_product_import->save();
                return Redirect::to('/product-import')->with('message', 'Hoàn tác thành công!');
            }
        }
	}

    public function DeleteProductImport($product_import_id){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $active_product_import = ProductImport::find($product_import_id);
            if (!$active_product_import) {
                return Redirect::to('/product-import')->with('error', 'Không tồn tại!');
            } else {
                if($active_product_import->donnhaphang_trang_thai==1){
                    return Redirect::to('/product-import')->with('error', 'Xóa không thành công!');
                }elseif($active_product_import->donnhaphang_trang_thai==0){
                    $all_import_detail=ProductImportDetail::where('donnhaphang_id',$product_import_id)->get();
                    foreach ($all_import_detail as $key => $value) {
                        $delete_detail=ProductImportDetail::find($value->id);
                        $delete_detail->delete();
                    }
                    $active_product_import->delete();
                    return Redirect::to('/product-import')->with('message', 'Xóa thành công!');
                }
            }
        }
    }

    public function ProductImportAdd(){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $admin = Admin::where('user_id', Session::get('admin_id'))->get();
            $all_supplier = Supplier::orderby('id', 'desc')->get();
            $all_product_import = ProductImport::where('donnhaphang_trang_thai', 0)->paginate(5);
            return view('admin.pages.product_import.product_import_add')
        ->with('get_admin', $admin)
        ->with('all_product_import', $all_product_import)
        ->with('all_supplier', $all_supplier);
        }
    }

    public function ProductImportAddSave(Request $request){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $data=$request->all();
            $admin = Admin::where('user_id', Session::get('admin_id'))->first();
            $all_product_import = ProductImport::where('donnhaphang_ma_don_nhap_hang', '=', $data['product_import_no'])->exists();
            if ($all_product_import) {
                return Redirect::to('/product-import-add')->with('error', 'Add Fail, Already Exist');
            } else {
                $product_import = new ProductImport();
                $product_import->donnhaphang_ma_don_nhap_hang = $data['product_import_no'];
                $product_import->donnhaphang_ngay_nhap = $data['product_import_day'];
                $product_import->donnhaphang_trang_thai = $data['product_import_status'];
                $product_import->nhacungcap_id = $data['product_import_supplier'];
                $product_import->admin_id = $admin->id;
                $product_import->donnhaphang_tong_tien = 0;
                $product_import->save();
            }
            return Redirect::to('/product-import-add')->with('message', 'Add Success');
        }
    }


	public function ProductImportEdit($product_import_id) {
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $admin = Admin::where('user_id', Session::get('admin_id'))->get();
            $all_product = Product::orderby('tbl_sanpham.id', 'desc')->get();
            $all_supplier = Supplier::orderby('id', 'desc')->get();
            $all_size = Size::orderby('id', 'desc')->get();
            $product_import = ProductImport::find($product_import_id);
            $get_product_import_detail = ProductImportDetail::where('chitietnhap_ma_don_nhap_hang', $product_import->donnhaphang_ma_don_nhap_hang)->get();
            return view('admin.pages.product_import.product_import_edit')
            ->with('all_product', $all_product)
            ->with('get_admin', $admin)
            ->with('all_size', $all_size)
            ->with('product_import', $product_import)
            ->with('get_product_import_detail', $get_product_import_detail)
            ->with('all_supplier', $all_supplier);
        }
    }

    public function ProductImportShowDetail($product_import_id){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $product_import=ProductImport::find($product_import_id);
            if (!$product_import) {
                return Redirect::to('/product-import-add')->with('error', 'Không tồn tại!');
            } else {
                $product_import_detail=ProductImportDetail::where('donnhaphang_id', $product_import_id)->get();
                return view('admin.pages.product_import.product_import_show_detail')
                ->with('product_import', $product_import)
                ->with('product_import_detail', $product_import_detail);
            }
        }
    }

	public function ProductImportEditSave(Request $request, $product_import_id) {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $product_import=ProductImport::find($product_import_id);
            if (!$product_import) {
                return Redirect::to('/product-import-add')->with('error', 'Không tồn tại!');
            } else {
                $data = $request->all();
                $all_product_import = ProductImport::where('donnhaphang_ma_don_nhap_hang', '=', $data['product_import_no'])->whereNotIn('id', [$product_import_id])->exists();
                if ($all_product_import) {
                    return Redirect::to('/product-import-edit/'.$product_import_id)->with('error', 'Cập nhật không thành công, đã tồn tại đơn nhập!');
                } else {
                    $product_import = ProductImport::find($product_import_id);
                    $product_import->donnhaphang_ngay_nhap = $data['product_import_day'];
                    $product_import->donnhaphang_trang_thai = $data['product_import_status'];
                    $product_import->nhacungcap_id = $data['product_import_supplier'];
                    $product_import->save();
                    return Redirect::to('/product-import')->with('message', 'Cập nhật thành công!');
                }
            }
        }
	}
    public function ProductImportAddDetail($product_import_id){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $product_import=ProductImport::find($product_import_id);
            if (!$product_import) {
                return Redirect::to('/product-import-add')->with('error', 'Không tồn tại!');
            } else {
                $all_size = Size::all();
                $all_product = Product::orderBy('id', 'DESC')->get();
                $product_import = ProductImport::find($product_import_id);
                return view('admin.pages.product_import.product_import_add_detail')
                ->with('product_import', $product_import)
                ->with('all_size', $all_size)
                ->with('all_product', $all_product);
            }
        }
    }

    public function ProductImportAddDetailSave(Request $request,$product_import_id){
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $product_import=ProductImport::find($product_import_id);
            if (!$product_import) {
                return Redirect::to('/product-import-add')->with('error', 'Không tồn tại!');
            } else {
                $data=$request->all();
                $get_product_import_detail = ProductImportDetail::where('sanpham_id', '=', $data['product_import_detail_product_id'])
                ->where('donnhaphang_id', $product_import_id)
                ->where('size_id', '=', $data['product_import_detail_size_id'])->first();
                $product_in_stock=ProductInstock::where('sanpham_id', '=', $data['product_import_detail_product_id'])
                ->where('size_id', '=', $data['product_import_detail_size_id'])->first();
                if (!$get_product_import_detail && !$product_in_stock) {//thêm sản phẩm mới detail k tồn kho
                    $product_update_price=Product::find($data['product_import_detail_product_id']);
                    $import_product_detail = new ProductImportDetail();
                    $import_product_in_stock= new ProductInStock();
                    $import_product_in_stock->sanphamtonkho_so_luong_ton = $data['product_import_detail_quantity'];
                    $import_product_in_stock->sanphamtonkho_so_luong_da_ban = 0;
                    $import_product_in_stock->sanpham_id = $data['product_import_detail_product_id'];
                    $import_product_in_stock->size_id = $data['product_import_detail_size_id'];
                    $import_product_in_stock->save();//save in stock
                    $import_product_detail->chitietnhap_so_luong_nhap = $data['product_import_detail_quantity'];
                    $import_product_detail->chitietnhap_gia_nhap = $data['product_import_detail_price'];
                    $import_product_detail->size_id = $data['product_import_detail_size_id'];
                    $import_product_detail->sanpham_id = $data['product_import_detail_product_id'];
                    $import_product_detail->chitietnhap_ma_don_nhap_hang = $data['product_import_no'];
                    $import_product_detail->donnhaphang_id = $data['product_import_id'];
                    $import_product=ProductImport::find($product_import_id);
                    $import_product->donnhaphang_tong_tien =  $import_product->donnhaphang_tong_tien + ($data['product_import_detail_price']*$data['product_import_detail_quantity']);
                    $import_product->save();
                    $import_product_detail->save();//save detail
                    $product_update_price->save();
                } elseif (!$get_product_import_detail && $product_in_stock) { //thêm sản phẩm mới của detail có tồn kho
                    $import_product_detail = new ProductImportDetail();
                    $import_product_detail->chitietnhap_so_luong_nhap = $data['product_import_detail_quantity'];
                    $import_product_detail->chitietnhap_gia_nhap = $data['product_import_detail_price'];
                    $import_product_detail->size_id = $data['product_import_detail_size_id'];
                    $import_product_detail->sanpham_id = $data['product_import_detail_product_id'];
                    $import_product_detail->chitietnhap_ma_don_nhap_hang = $data['product_import_no'];
                    $import_product_detail->donnhaphang_id = $data['product_import_id'];
                    $import_product_in_stock_update=ProductInstock::find($product_in_stock->id);
                    $import_product_in_stock_update->sanphamtonkho_so_luong_ton = $data['product_import_detail_quantity'] + $product_in_stock->sanphamtonkho_so_luong_ton;
                    $import_product_in_stock_update->save();
                    $import_product=ProductImport::find($product_import_id);
                    $import_product->donnhaphang_tong_tien =  $import_product->donnhaphang_tong_tien + ($data['product_import_detail_price']*$data['product_import_detail_quantity']);
                    $product_update_price=Product::find($data['product_import_detail_product_id']);
                    $product_update_price->save();
                    $import_product_detail->save();
                    $import_product->save();
                } else {// thêm sp cũ detail có tồn kho
                    $import_product_detail = ProductImportDetail::find($get_product_import_detail->id);
                    $import_product_detail->chitietnhap_so_luong_nhap = $data['product_import_detail_quantity'] +$get_product_import_detail->chitietnhap_so_luong_nhap;
                    $import_product_detail->chitietnhap_gia_nhap = $data['product_import_detail_price'];
                    $import_product_in_stock_update=ProductInstock::find($product_in_stock->id);
                    $import_product_in_stock_update->sanphamtonkho_so_luong_ton = $data['product_import_detail_quantity'] + $product_in_stock->sanphamtonkho_so_luong_ton;
                    $import_product_in_stock_update->save();
                    $import_product=ProductImport::find($product_import_id);
                    $import_product->donnhaphang_tong_tien =  $import_product->donnhaphang_tong_tien + ($data['product_import_detail_price']*$data['product_import_detail_quantity']);
                    $product_update_price=Product::find($data['product_import_detail_product_id']);
                    $product_update_price->save();
                    $import_product_detail->save();
                    $import_product->save();
                }
                return Redirect::to('/product-import-edit/'.$product_import_id);
            }
        }
    }

	public function ProductImportEditDetail($product_import_detail_id) {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $product_import_detail=ProductImportDetail::find($product_import_detail_id);
            if (!$product_import_detail) {
                return Redirect::to('/product-import-add')->with('error', 'Không tồn tại!');
            } else {
                $all_size = Size::all();
                $all_product = Product::orderBy('id', 'DESC')->get();
                $get_product_in_stock = ProductInstock::where('sanpham_id', $product_import_detail->sanpham_id)
                ->where('size_id', $product_import_detail->size_id)->first();
                return view('admin.pages.product_import.product_import_edit_detail')
                ->with('product_import_detail', $product_import_detail)
                ->with('all_size', $all_size)
                ->with('product_in_stock', $get_product_in_stock)
                ->with('all_product', $all_product);
            }
        }
	}

	public function ProductImportEditDetailSave(Request $request, $product_import_detail_id) {
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $data = $request->all();
            $get_product_import_detail= ProductImportDetail::where('sanpham_id', '=', $data['product_import_detail_product_id'])
            ->where('size_id', '=', $data['product_import_detail_size_id'])->first();
            $product_in_stock=ProductInstock::where('sanpham_id', '=', $data['product_import_detail_product_id'])
            ->where('size_id', '=', $data['product_import_detail_size_id'])->first();
            $get_product_in_stock_old= ProductInstock::where('sanpham_id', '=', $data['product_import_detail_product_id'])
            ->where('size_id', '=', $data['product_import_detail_size_id_old'])->first();
            $import_product_detail = ProductImportDetail::find($product_import_detail_id);
            $import_product=ProductImport::find($data['product_import_id']);
            $import_product_in_stock_old=ProductInstock::find($get_product_in_stock_old->id);
            if (($import_product_in_stock_old->sanphamtonkho_so_luong_ton - $data['product_import_detail_quantity_old']) < 0) {
                return Redirect::to('/product-import-show-detail/'.$import_product_detail->donnhaphang_id)->with('message', 'Updete Fail, Quantity Invalid');
            } elseif (!$get_product_import_detail && !$product_in_stock) {//sản phẩm cũ size mới chưa có tồn kho
            $import_product_in_stock= new ProductInstock();//tạo tồn kho mới
            $import_product_in_stock->sanphamtonkho_so_luong_ton = $data['product_import_detail_quantity'];
                $import_product_in_stock->sanphamtonkho_so_luong_da_ban = 0;
                $import_product_in_stock->sanpham_id = $data['product_import_detail_product_id'];
                $import_product_in_stock->size_id = $data['product_import_detail_size_id'];
                $import_product_in_stock->save();//save in stock
                $import_product_in_stock_old->sanphamtonkho_so_luong_ton = $import_product_in_stock_old->sanphamtonkho_so_luong_ton - $data['product_import_detail_quantity_old'];
                $import_product_in_stock_old->save();//update in stock old old
                $import_product_detail->chitietnhap_so_luong_nhap = $data['product_import_detail_quantity'];
                $import_product_detail->chitietnhap_gia_nhap = $data['product_import_detail_price'];
                $import_product_detail->size_id = $data['product_import_detail_size_id'];
                $import_product_detail->save();//update detail
                $import_product->donnhaphang_tong_tien = $import_product->donnhaphang_tong_tien +
            (($data['product_import_detail_price']*$data['product_import_detail_quantity'])-($data['product_import_detail_quantity_old']*$data['product_import_detail_price_old']));
                $import_product->save();//update total
                $product_update_price=Product::find($data['product_import_detail_product_id']);
                $product_update_price->sanpham_gia_ban=$data['product_import_detail_price_retail'];
                $product_update_price->save();
            } elseif (!$get_product_import_detail && $product_in_stock) {//sửa sản phẩm cũ size mới đã có tồn kho
                $import_product_in_stock= ProductInstock::find($product_in_stock->id);
                $import_product_in_stock->sanphamtonkho_so_luong_ton = $import_product_in_stock->sanphamtonkho_so_luong_ton + $data['product_import_detail_quantity'];
                $import_product_in_stock->save();//update in stock old new
                $import_product_in_stock_old->sanphamtonkho_so_luong_ton = $import_product_in_stock_old->sanphamtonkho_so_luong_ton -$data['product_import_detail_quantity_old'];
                $import_product_in_stock_old->save();//update in stock old old
                $import_product_detail->chitietnhap_so_luong_nhap = $data['product_import_detail_quantity'];
                $import_product_detail->chitietnhap_gia_nhap = $data['product_import_detail_price'];
                $import_product_detail->size_id = $data['product_import_detail_size_id'];
                $import_product_detail->save();//update detail
                $import_product->donnhaphang_tong_tien = $import_product->donnhaphang_tong_tien +
            (($data['product_import_detail_price']*$data['product_import_detail_quantity'])-($data['product_import_detail_quantity_old']*$data['product_import_detail_price_old']));
                $import_product->save();//update total
                $product_update_price=Product::find($data['product_import_detail_product_id']);
                $product_update_price->sanpham_gia_ban=$data['product_import_detail_price_retail'];
                $product_update_price->save();
            } else {//sp cũ size cũ
                $import_product_in_stock= ProductInstock::find($product_in_stock->id);
                $import_product_in_stock->sanphamtonkho_so_luong_ton = $import_product_in_stock->sanphamtonkho_so_luong_ton -$data['product_import_detail_quantity_old'] + $data['product_import_detail_quantity'];
                $import_product_in_stock->save();//update in stock old new
                $import_product_detail->chitietnhap_so_luong_nhap = $data['product_import_detail_quantity'];
                $import_product_detail->chitietnhap_gia_nhap = $data['product_import_detail_price'];
                $import_product_detail->save();//update detail
                $import_product->donnhaphang_tong_tien = $import_product->donnhaphang_tong_tien +
            (($data['product_import_detail_price']*$data['product_import_detail_quantity'])-($data['product_import_detail_quantity_old']*$data['product_import_detail_price_old']));
                $import_product->save();//update total
                $product_update_price=Product::find($data['product_import_detail_product_id']);
                $product_update_price->sanpham_gia_ban=$data['product_import_detail_price_retail'];
                $product_update_price->save();
            }
            return Redirect::to('/product-import-show-detail/'.$data['product_import_id']);
        }
    }

    public function ProductImportDeletetDetail($product_import_detail_id){
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $product_import_detail=ProductImportDetail::find($product_import_detail_id);
            $product_in_stock=ProductInstock::where('sanpham_id', '=', $product_import_detail->sanpham_id)
            ->where('size_id', '=', $product_import_detail->size_id)->first();
            if (($product_in_stock->sanphamtonkho_so_luong_ton-$product_import_detail->chitietnhap_so_luong_nhap) < 0) {
                return Redirect::to('/product-import-show-detail/'.$product_import_detail->donnhaphang_id)->with('message', 'Delete Fail, Quantity Invalid');
            } elseif (($product_in_stock->sanphamtonkho_so_luong_ton - $product_import_detail->chitietnhap_so_luong_nhap) == 0) {
                $product_import_update=ProductImport::find($product_import_detail->donnhaphang_id);
                $product_import_update->donnhaphang_tong_tien=$product_import_update->donnhaphang_tong_tien -($product_import_detail->chitietnhap_so_luong_nhap*$product_import_detail->chitietnhap_gia_nhap);
                $product_import_update->save();
                $product_in_stock->sanphamtonkho_so_luong_ton=0;
                $product_in_stock->save();
                $product=Product::find($product_import_detail->sanpham_id);
                $product->sanpham_trang_thai=2;//tạm hết hàng
                $product->save();
            } else {
                $product_import_update=ProductImport::find($product_import_detail->donnhaphang_id);
                $product_import_update->donnhaphang_tong_tien=$product_import_update->donnhaphang_tong_tien -($product_import_detail->chitietnhap_so_luong_nhap*$product_import_detail->chitietnhap_gia_nhap);
                $product_import_update->save();
                $product_in_stock->sanphamtonkho_so_luong_ton =$product_in_stock->sanphamtonkho_so_luong_ton - $product_import_detail->chitietnhap_so_luong_nhap;
                $product_in_stock->save();
                print_r($product_in_stock->sanphamtonkho_so_luong_ton);
            }
            $product_import_detail->delete();
            return Redirect::to('/product-import-show-detail/'.$product_import_detail->donnhaphang_id);
        }
    }

    public function SelectImageProduct(Request $request){
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $product_id=$request->product_id;
            $product=Product::find($product_id);
            $output = '<img class=" mt-3" width="300px" height="370px" id="image" src="'.asset('public/uploads/admin/product/'.$product->sanpham_anh).'" />';
            echo  $output;
        }
    }
}