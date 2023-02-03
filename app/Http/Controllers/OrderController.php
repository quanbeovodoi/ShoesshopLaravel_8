<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use File;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductInStock;
use App\Models\Size;
use App\Models\City;
use App\Models\Province;
use App\Models\TransportFee;
use App\Models\Wards;
use App\Models\Coupon;
use App\Models\Delivery;
use App\Models\Customer;
use Session;
use Carbon\Carbon;
use Mail;
use Illuminate\Mail\Transport\Transport;

session_start();
use Illuminate\Support\Facades\Redirect;
class OrderController extends Controller
{

    public function Index(){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $all_order = Order::orderBy('id', 'DESC')->paginate(5);
            return view('admin.pages.order.order') ->with(compact('all_order'));
        }
    }

    public function AuthLogin(){
        $admin_id = Session::get('admin_id');
        if($admin_id){
            return Redirect::to('/dashboard');
        }else{
            return Redirect::to('/admin')->send();
        }
    }
    public function OrderAdd(){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $all_product=Product::where('sanpham_trang_thai', 1)->get();
            if ($all_product->count()>0) {
                foreach ($all_product as $key=> $value) {
                    $product_id[]=$value->id;
                }
            } else {
                $product_id=null;
            }
            if ($product_id!=null) {
                $all_product_in_stock = ProductInStock::where('sanphamtonkho_so_luong_ton', '>', 0)
            ->whereIn('sanpham_id', $product_id)
            ->orderby('sanpham_id', 'desc')->paginate(5);//lấy sản phẩm tồn kho
                $city=City::orderby('id', 'ASC')->get();
            } else {
                $all_product_in_stock = null;
                $city=City::orderby('id', 'ASC')->get();
            }
            return view('admin.pages.order.order_add')
        ->with('city', $city)
        ->with('all_product_in_stock', $all_product_in_stock);
        }
    }

    public function OrderAddShowProduct(){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $get_product=Product::first();
            if (!$get_product) {
                return Redirect::to('/order')->with('error', 'product not found');
            } else {
                $all_product=Product::where('sanpham_trang_thai', 1)->get();
                if ($all_product->count()>0) {
                    foreach ($all_product as $key=> $value) {
                        $product_id[]=$value->id;
                    }
                } else {
                    $product_id=null;
                }
                $all_product_in_stock = ProductInStock::where('sanphamtonkho_so_luong_ton', '>', 0)
            ->whereIn('sanpham_id', $product_id)
            ->orderby('sanpham_id', 'desc')->paginate(5);//lấy sản phẩm tồn kho
                return view('admin.pages.order.order_add_show_product')
            ->with('all_product_in_stock', $all_product_in_stock);
            }
        }
    }

    public function OrderAdminAddRow(Request $request) {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $data = $request->all();
            $session_id = substr(md5(microtime()) . rand(0, 26), 5);
            $order_admin = Session::get('order_admin');
            $qty = 1;
            if ($order_admin) {
                $is_ava=0;
                foreach ($order_admin as $key => $value) {
                    if ($value['product_size_id']==$data['product_size_id'] && $value['product_id']==$data['product_id']) {
                        $order_admin[$key]['product_quantity'] = $value['product_quantity'] + 1;
                        $is_ava++;
                    }
                }
                if ($is_ava==0) {
                    $order_admin[] = array(
                    'session_id' => $session_id,
                    'product_size_name' =>$data['product_size_name'],
                    'product_size_id' => $data['product_size_id'],
                    'product_name' => $data['product_name'],
                    'product_in_stock' => $data['product_in_stock'],
                    'product_id' => $data['product_id'],
                    'product_quantity' => $qty,
                    'product_price' => $data['product_price'],
                    'product_image' => $data['product_image'],
                );
                }
            } else {
                $order_admin[] = array(
                'session_id' => $session_id,
                'product_size_name' =>$data['product_size_name'],
                'product_size_id' => $data['product_size_id'],
                'product_name' => $data['product_name'],
                'product_in_stock' => $data['product_in_stock'],
                'product_id' => $data['product_id'],
                'product_quantity' => $qty,
                'product_price' => $data['product_price'],
                'product_image' => $data['product_image'],
            );
            }
            Session::put('order_admin', $order_admin);
            Session::save();
        }
	}

	public function OrderAdminDeleteRow(Request $request) {
		$this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $data = $request->all();
            $order_admin = Session::get('order_admin');
            if ($order_admin == true) {
                foreach ($order_admin as $key => $value) {
                    if ($value['session_id'] == $data['product_session_id']) {
                        unset($order_admin[$key]);
                    }
                }
                Session::put('order_admin', $order_admin);
                Session::save();
            }
        }
	}

    // public function CheckCoupon(Request $request){
    //     $this->AuthLogin();
	// 	$data = $request->all();
    //     $coupon=Coupon::where('makhuyenmai_so_luong','>',0)->get();
    //     foreach($coupon as $key => $value){
    //         if($value->makhuyenmai_ma==$data['product_order_coupon']){
    //             if($value->makhuyenmai_loai_ma==1){
    //                 echo '&nbsp;&nbsp;&nbsp;'.number_format( $value->makhuyenmai_gia_tri,0,',','.' )." VND";
    //                 $cou[]=array(
    //                     'coupon_code'=>$value->id,
    //                     'coupon_code'=>$value->makhuyenmai_ma,
    //                     'coupon_type'=>$value->makhuyenmai_loai_ma,
    //                     'coupon_number'=>$value->makhuyenmai_gia_tri,
    //                     'coupon_quantity'=>$value->makhuyenmai_so_luong,
    //                 );
    //                 Session::put('coupon', $cou);
    //              }
    //              else{
    //                  echo '&nbsp;&nbsp;&nbsp;'.number_format( $value->makhuyenmai_gia_tri,0,',','.' )." %";
    //                  $cou[]=array(
    //                     'coupon_code'=>$value->id,
    //                     'coupon_code'=>$value->makhuyenmai_ma,
    //                     'coupon_type'=>$value->makhuyenmai_loai_ma,
    //                     'coupon_number'=>$value->makhuyenmai_gia_tri,
    //                     'coupon_quantity'=>$value->makhuyenmai_so_luong,
    //                 );
    //                 Session::put('coupon', $cou);
    //              }
    //         }else{
    //             Session::put('message', 'Add Fail');
    //         }
    //     }
    // }
    // public function CheckTransportFee(Request $request){
    //     $this->AuthLogin();
	// 	$data = $request->all();
    //     $transport_fee=TransportFee::all();
    //     foreach($transport_fee as $key =>$value){
    //         if($data['city']==$value->tinhthanhpho_id && $data['province']==$value->quanhuyen_id && $data['wards']==$value->xaphuong_id){
    //             echo number_format( $value->phivanchuyen_phi_van_chuyen,0,',','.' )." VND";
    //             $fee[]=array(
    //                 'fee_id'=>$value->id,
    //                 'fee'=>$value->phivanchuyen_phi_van_chuyen,
    //                 'fee_day'=>$value->phivanchuyen_ngay_giao_hang_du_kien
    //             );
    //             Session::put('fee', $fee);
    //         }else{
    //             echo '&nbsp;&nbsp;&nbsp;'.number_format( 25000,0,',','.' )." VND";;
    //             $fee[]=array(
    //                 'fee'=>25000,
    //                 'fee_day'=>3
    //             );
    //             Session::put('fee', $fee);
    //         }
    //         break;
    //     }
    // }

	public function OrderAddSave(Request $request) {
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $data = $request->all();
            $this->validate($request,[
                'order_customer' => 'bail|required|max:255|min:6',
                'order_email' => 'bail|required|email',
                'order_phone_number' => 'bail|required|max:255|min:10',
                'order_address' => 'bail|required|max:255|min:20',
            ],
            [
                'required' => 'Không được để trống',
                'min' => 'Quá ngắn',
                'max' => 'Quá dài',
            ]);
            $order_admin_detail = Session::get('order_admin');
            $transport_fee=TransportFee::where('tinhthanhpho_id', $data['city'])
            ->where('quanhuyen_id', $data['province'])
            ->where('xaphuong_id', $data['wards'])->first();
            $order_code=substr(str_shuffle(str_repeat("SHOES", 5)), 0, 2).substr(str_shuffle(str_repeat("0123456789", 5)), 0, 6);
            $order_old=Order::where('dondathang_ma_don_dat_hang', $order_code)->first();
            if (!$order_admin_detail) {
                return Redirect::to('/order-add')->with('error','Thêm không thành công, chưa chọn sản phẩm!');
            } else {
                if (!$order_old) {
                    $order_code = substr(str_shuffle(str_repeat("RGWUB", 5)), 0, 2).substr(str_shuffle(str_repeat("0123456789", 5)), 0, 6);
                    date_default_timezone_set('Asia/Ho_Chi_Minh');
                    $order_date = Carbon::now('Asia/Ho_Chi_Minh')->format('Y-m-d');
                    $order_admin=new Order();
                    $order_admin->dondathang_ma_don_dat_hang=$order_code;//mã đơn hàng
                    $order_admin->dondathang_ngay_dat_hang=$order_date;//ngày đặt
                    if ($data['order_note']) {//ghi chú đơn hàng
                        $order_admin->dondathang_ghi_chu=$data['order_note'];
                    } else {
                        $order_admin->dondathang_ghi_chu='';
                    }
                    $order_admin->dondathang_tinh_trang_thanh_toan=$data['order_payment'];//1 đã thanh toán CK,0 chưa thanh toán COD
                    $order_admin->dondathang_trang_thai=1; //trạng thái đơn hàng 1 = đã xác nhận
                    $order_delivery=new Delivery();
                    $ci=City::find($data['city']);
                    $prov=Province::find($data['province']);
                    $wards=Wards::find($data['wards']);
                    if ($ci && $prov && $wards) {
                        $address=$data['order_address'].','.$wards->xaphuongthitran_name.','.$prov->quanhuyen_name.','.$ci->tinhthanhpho_name;
                        $order_delivery->giaohang_nguoi_nhan_dia_chi=$address;
                    } else {
                        $order_delivery->giaohang_nguoi_nhan_dia_chi=$data['order_address'];
                    }
                    $order_delivery->giaohang_nguoi_nhan=$data['order_customer'];
                    $order_delivery->giaohang_nguoi_nhan_email=$data['order_email'];
                    $order_delivery->giaohang_nguoi_nhan_so_dien_thoai=$data['order_phone_number'];
                    $order_delivery->giaohang_trang_thai=0;//chưa giao, đang giao
                    $order_delivery->giaohang_ma_don_dat_hang=$order_code;
                    $total=0;
                    foreach ($order_admin_detail as $key=>$value) {
                        $order_detail=new OrderDetail();
                        foreach ($data['product_quantity'] as $k =>$qty) {
                            if ($data['order_payment']==1) {
                                $product_in_stock=ProductInStock::where('sanpham_id', $value['product_id'])
                                ->where('size_id', $value['product_size_id'])->first();
                                $product_in_stock_update=ProductInStock::find($product_in_stock->id);
                                $product_in_stock_update->sanphamtonkho_so_luong_ton -=$qty;
                                $product_in_stock_update->sanphamtonkho_so_luong_da_ban +=$qty;
                                $product_in_stock_update->save();
                            }
                            $order_detail->chitietdondathang_so_luong=$qty;
                            $total+=($value['product_price']*$qty);
                            break;
                        }
                        $order_detail->sanpham_id=$value['product_id'];
                        $order_detail->size_id=$value['product_size_id'];
                        $order_detail->chitietdondathang_ma_don_dat_hang=$order_code;
                        $order_detail->chitietdondathang_don_gia=$value['product_price'];
                        $order_detail->save();
                    }
                    if (!$data['product_order_discount'] && !$transport_fee) {
                        $subtotal=$total + 35000;
                        $order_admin->dondathang_tong_tien=$total + 35000;
                        $order_admin->dondathang_giam_gia=0;
                        $order_admin->dondathang_phi_van_chuyen=35000;
                    } elseif ($data['product_order_discount'] && !$transport_fee) {
                        $subtotal=$total - $data['product_order_discount'] +35000;
                        $order_admin->dondathang_tong_tien=$total - $data['product_order_discount'] +35000;
                        $order_admin->dondathang_giam_gia=$data['product_order_discount'];
                        $order_admin->dondathang_phi_van_chuyen=35000;
                    } elseif (!$data['product_order_discount'] && $transport_fee) {
                        $subtotal=$total + $transport_fee->phivanchuyen_phi_van_chuyen;
                        $order_admin->dondathang_tong_tien=$total + $transport_fee->phivanchuyen_phi_van_chuyen;
                        $order_admin->dondathang_giam_gia=0;
                        $order_admin->dondathang_phi_van_chuyen=$transport_fee->phivanchuyen_phi_van_chuyen;
                    } else {
                        $subtotal=$total + $transport_fee->phivanchuyen_phi_van_chuyen -$data['product_order_discount'];
                        $order_admin->dondathang_tong_tien=$total + $transport_fee->phivanchuyen_phi_van_chuyen -$data['product_order_discount'];
                        $order_admin->dondathang_giam_gia=$data['product_order_discount'];
                        $order_admin->dondathang_phi_van_chuyen=$transport_fee->phivanchuyen_phi_van_chuyen;
                    }
                    if ($data['order_payment']==1) {//đã thanh toán
                    $order_delivery->giaohang_phuong_thuc_thanh_toan=1;// phương thức thanh toán 1 CK
                    $order_delivery->giaohang_tong_tien_thanh_toan=0;
                    } else {//chưa thanh toán
                    $order_delivery->giaohang_phuong_thuc_thanh_toan=0;// phương thức thanh toán 0 COD
                    $order_delivery->giaohang_tong_tien_thanh_toan=$subtotal;
                    }
                    $order_admin->save();
                    $order_delivery->save();
                } else {
                    $order_code = substr(str_shuffle(str_repeat("SHOES", 5)), 0, 2).substr(str_shuffle(str_repeat("0123456789", 5)), 0, 6);
                    date_default_timezone_set('Asia/Ho_Chi_Minh');
                    $order_date = Carbon::now('Asia/Ho_Chi_Minh')->format('Y-m-d');
                    $order_admin=new Order();
                    $order_admin->dondathang_ma_don_dat_hang=$order_code;
                    $order_admin->dondathang_ngay_dat_hang=$order_date;
                    if ($data['order_note']) {
                        $order_admin->dondathang_ghi_chu=$data['order_note'];
                    } else {
                        $order_admin->dondathang_ghi_chu='';
                    }
                    $order_admin->dondathang_tinh_trang_thanh_toan=$data['order_payment'];//1 đã thanh toán CK,0 chưa thanh toán COD
                    $order_admin->dondathang_trang_thai=1;
                    $order_delivery=new Delivery();
                    $ci=City::find($data['city']);
                    $prov=Province::find($data['province']);
                    $wards=Wards::find($data['wards']);
                    if ($ci && $prov && $wards) {
                        $address=$data['order_address'].','.$wards->xaphuongthitran_name.','.$prov->quanhuyen_name.','.$ci->tinhthanhpho_name;
                        $order_delivery->giaohang_nguoi_nhan_dia_chi=$address;
                    } else {
                        $order_delivery->giaohang_nguoi_nhan_dia_chi=$data['order_address'];
                    }
                    $order_delivery->giaohang_nguoi_nhan=$data['order_customer'];
                    $order_delivery->giaohang_nguoi_nhan_email=$data['order_email'];
                    $order_delivery->giaohang_nguoi_nhan_so_dien_thoai=$data['order_phone_number'];
                    $order_delivery->giaohang_trang_thai=0;//chưa giao, đang giao
                    $order_delivery->giaohang_ma_don_dat_hang=$order_code;
                    $total=0;
                    foreach ($order_admin_detail as $key=>$value) {
                        $order_detail=new OrderDetail();
                        foreach ($data['product_quantity'] as $k =>$qty) {
                            if ($data['order_payment']==1) {
                                $product_in_stock=ProductInStock::where('sanpham_id', $value['product_id'])
                                ->where('size_id', $value['product_size_id'])->first();
                                $product_in_stock_update=ProductInStock::find($product_in_stock->id);
                                $product_in_stock_update->sanphamtonkho_so_luong_ton -=$qty;
                                $product_in_stock_update->sanphamtonkho_so_luong_da_ban +=$qty;
                                $product_in_stock_update->save();
                            }
                            $order_detail->chitietdondathang_so_luong=$qty;
                            $total+=($value['product_price']*$qty);
                            break;
                        }
                        $order_detail->sanpham_id=$value['product_id'];
                        $order_detail->size_id=$value['product_size_id'];
                        $order_detail->chitietdondathang_ma_don_dat_hang=$order_code;
                        $order_detail->chitietdondathang_don_gia=$value['product_price'];
                        $order_detail->save();
                        // print_r($product_in_stock_update->sanphamtonkho_so_luong_ton);
                    }
                    if (!$data['product_order_discount'] && !$transport_fee) {
                        $subtotal=$total + 35000;
                        $order_admin->dondathang_tong_tien=$total + 35000;
                        $order_admin->dondathang_giam_gia=0;
                        $order_admin->dondathang_phi_van_chuyen=35000;
                    } elseif ($data['product_order_discount'] && !$transport_fee) {
                        $subtotal=$total - $data['product_order_discount'] +35000;
                        $order_admin->dondathang_tong_tien=$total - $data['product_order_discount'] +35000;
                        $order_admin->dondathang_giam_gia=$data['product_order_discount'];
                        $order_admin->dondathang_phi_van_chuyen=35000;
                    } elseif (!$data['product_order_discount'] && $transport_fee) {
                        $subtotal=$total + $transport_fee->phivanchuyen_phi_van_chuyen;
                        $order_admin->dondathang_tong_tien=$total + $transport_fee->phivanchuyen_phi_van_chuyen;
                        $order_admin->dondathang_giam_gia=0;
                        $order_admin->dondathang_phi_van_chuyen=$transport_fee->phivanchuyen_phi_van_chuyen;
                    } else {
                        $subtotal=$total + $transport_fee->phivanchuyen_phi_van_chuyen -$data['product_order_discount'];
                        $order_admin->dondathang_tong_tien=$total + $transport_fee->phivanchuyen_phi_van_chuyen -$data['product_order_discount'];
                        $order_admin->dondathang_giam_gia=$data['product_order_discount'];
                        $order_admin->dondathang_phi_van_chuyen=$transport_fee->phivanchuyen_phi_van_chuyen;
                    }
                    if ($data['order_payment']==1) {//đã thanh toán
                    $order_delivery->giaohang_phuong_thuc_thanh_toan=1;// phương thức thanh toán 1 CK
                    $order_delivery->giaohang_tong_tien_thanh_toan=0;
                    } else {//chưa thanh toán
                    $order_delivery->giaohang_phuong_thuc_thanh_toan=0;// phương thức thanh toán 0 COD
                    $order_delivery->giaohang_tong_tien_thanh_toan=$subtotal;
                    }
                    $order_admin->save();
                    $order_delivery->save();
                }
            }
            Session::forget('order_admin');
            return Redirect::to('/order')->with('message','Tạo đơn hàng thành công!');
        }
    }

    public function OrderConfirm($order_id){
        $this->AuthLogin();
        if (Session::get('admin_role')==3) {
            return Redirect::to('/dashboard');
        } else {
            $order=Order::find($order_id);
            $order_delivery=Delivery::where('giaohang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->first();
            $order_detail=OrderDetail::where('chitietdondathang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->get();
            foreach ($order_detail as $key =>$value) {
                $cart_array[] = array(
                'product_name' => $value->Product->sanpham_ten,
                'product_price' => $value->chitietdondathang_don_gia,
                'product_size' => $value->Size->size,
                'product_qty' => $value->chitietdondathang_so_luong
              );
                $order_detail_update=OrderDetail::find($value->id);
                $order_detail_update->dondathang_id=$order_id;
                $order_detail_update->save();
            }
            $order_delivery_update=Delivery::find($order_delivery->id);
            $order_delivery_update->dondathang_id=$order_id;
            if ($order->dondathang_trang_thai==0) {//chưa xác nhận
                $order->dondathang_trang_thai=1;
            }
            $title_mail = "Order confirmed".' - Order Code: '.$order->dondathang_ma_don_dat_hang;
            $data['email'][] = $order->Customer->khachhang_email;
            $shipping_array = array(
                'shipping_name' => $order_delivery->giaohang_nguoi_nhan,
                'shipping_day' => $order->dondathang_ngay_dat_hang,
                'shipping_email' => $order_delivery->giaohang_nguoi_nhan_email,
                'shipping_phone' => $order_delivery->giaohang_nguoi_nhan_so_dien_thoai,
                'shipping_address' => $order_delivery->giaohang_nguoi_nhan_dia_chi,
                'shipping_notes' => $order->dondathang_ghi_chu,
                'shipping_method' => $order_delivery->giaohang_phuong_thuc_thanh_toan,
                'shipping_total' => $order_delivery->giaohang_tong_tien_thanh_toan
            );
            //lay ma giam gia, lay coupon code
            $ordercode_mail = array(
                'coupon_code' => $order->dondathang_ma_giam_gia,
                'order_code' => $order->dondathang_ma_don_dat_hang,
                'feeship' =>  $order->dondathang_phi_van_chuyen,
                'discount' =>  $order->dondathang_giam_gia,
                'order_total' => $order->dondathang_tong_tien
            );
            $get_coupon_code = Coupon::where('makhuyenmai_trang_thai', 1)
            ->where('makhuyenmai_so_luong','>', 0)->first();
            if($get_coupon_code){
                $to_name="SHOES";
                $title_mail_coupon = "Gửi Bạn Mã Giảm Giá Từ SHOES SHOP";
                $data_coupon=array("name"=>"SHOES SHOP","code"=>$get_coupon_code->makhuyenmai_ma,"name_code"=>$get_coupon_code->makhuyenmai_ma);
                Mail::send('layout.send_mail_coupon_code', $data_coupon, function ($message) use ($title_mail_coupon,$to_name,$data) {
                    $message->to($data['email'])->subject($title_mail_coupon);//send this mail with subject
                $message->from($data['email'], $title_mail_coupon,$to_name);//send from this mail
                });
            }
            Mail::send('layout.send_mail_confirm_order', ['cart_array'=>$cart_array, 'shipping_array'=>$shipping_array ,'code'=>$ordercode_mail], function ($message) use ($title_mail, $data) {
                $message->to($data['email'])->subject($title_mail);//send this mail with subject
            $message->from($data['email'], $title_mail);//send from this mail
            });
            $order_delivery_update->save();
            $order->save();
            return Redirect::to('/order-show-detail/'.$order_id)->with('message','Xác nhận đơn hàng thành công!');
        }
    }

    public function OrderConfirmPayment($order_id){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $order=Order::find($order_id);
            if (!$order) {
                return Redirect::to('/order')->with('error', 'Đơn hàng không tồn tại!');
            } else {
                $order_delivery=Delivery::where('giaohang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->first();
                $order_detail=OrderDetail::where('chitietdondathang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->get();
                foreach ($order_detail as $key =>$value) {
                    $order_detail_update=OrderDetail::find($value->id);
                    $order_detail_update->dondathang_id=$order_id;
                    $order_detail_update->save();
                    $product_in_stock=ProductInStock::where('sanpham_id', $value->sanpham_id)
                    ->where('size_id', $value->size_id)->first();
                    $product_in_stock_update=ProductInStock::find($product_in_stock->id);
                    $product_in_stock_update->sanphamtonkho_so_luong_ton -=$value->chitietdondathang_so_luong;
                    $product_in_stock_update->sanphamtonkho_so_luong_da_ban +=$value->chitietdondathang_so_luong;
                    $product_in_stock_update->save();
                }
                $order_delivery_update=Delivery::find($order_delivery->id);
                $order_delivery_update->dondathang_id=$order_id;
                if ($order->dondathang_tinh_trang_thanh_toan==0) {//chưa thanh toán
                    $order->dondathang_tinh_trang_thanh_toan=1;
                }
                $order_delivery_update->save();
                $order->save();
                return Redirect::to('/order-show-detail/'.$order_id)->with('message', 'Xác nhận thanh toán thành công!');
            }
        }
    }

    public function OrderCanceled($order_id){
        $this->AuthLogin();
        $order=Order::find($order_id);
        if(!$order){
            return Redirect::to('/order')->with('error','Đơn hàng không tồn tại!');
        }else{
            $order_delivery=Delivery::where('giaohang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->first();
            $order_delivery_update=Delivery::find($order_delivery->id);
            $order_delivery_update->giaohang_trang_thai=3;
            $order->dondathang_trang_thai=4;
            $order_detail=OrderDetail::where('chitietdondathang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->get();
            if ($order->dondathang_tinh_trang_thanh_toan==1) {
                foreach ($order_detail as $key => $value) {
                    $product_in_stock=ProductInStock::where('sanpham_id', $value->sanpham_id)
                    ->where('size_id', $value->size_id)->first();
                    $product_in_stock_update=ProductInStock::find($product_in_stock->id);
                    $product_in_stock_update->sanphamtonkho_so_luong_ton += $value->chitietdondathang_so_luong;
                    $product_in_stock_update->sanphamtonkho_so_luong_da_ban -= $value->chitietdondathang_so_luong;
                    $product_in_stock_update->save();
                }
                $order->dondathang_tinh_trang_thanh_toan=2;//đã hủy hoàn tiền lại
            } elseif ($order->dondathang_tinh_trang_thanh_toan==0) {
                $order->dondathang_tinh_trang_thanh_toan=3;//đã hủy k hoàn tiền lại
            }
            $order->save();
            $order_delivery_update->save();
            return Redirect::to('/order-show-detail/'.$order_id)->with('message', 'Hủy đơn hàng thành công!');
        }
    }

    public function OrderInTransit($order_id){
        $this->AuthLogin();
        $order=Order::find($order_id);
        if(!$order){
            return Redirect::to('/order')->with('error','Đơn hàng không tồn tại!');
        }else{
            $order_delivery=Delivery::where('giaohang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->first();
            $order_delivery_update=Delivery::find($order_delivery->id);
            $order_delivery_update->giaohang_trang_thai=1;
            $order->dondathang_trang_thai=2;
            $order->save();
            $order_delivery_update->save();
            return Redirect::to('/delivery-show-detail/'.$order_id)->with('message', 'Xác nhận lấy hàng thành công!');
        }
    }

    public function OrderConfirmDelivery($order_id){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $order=Order::find($order_id);
            if (!$order) {
                return Redirect::to('/order')->with('error','Đơn hàng không tồn tại!');
            } else {
                $order_delivery=Delivery::where('giaohang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->first();
                $order_delivery_update=Delivery::find($order_delivery->id);
                $order_delivery_update->giaohang_trang_thai=2;
                $order->dondathang_trang_thai=3;
                $order_detail=OrderDetail::where('chitietdondathang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->get();
                if ($order->dondathang_tinh_trang_thanh_toan==0) {
                    foreach ($order_detail as $key => $value) {
                        $product_in_stock=ProductInStock::where('sanpham_id', $value->sanpham_id)
                        ->where('size_id', $value->size_id)->first();
                        $product_in_stock_update=ProductInStock::find($product_in_stock->id);
                        $product_in_stock_update->sanphamtonkho_so_luong_ton -= $value->chitietdondathang_so_luong;
                        $product_in_stock_update->sanphamtonkho_so_luong_da_ban += $value->chitietdondathang_so_luong;
                        $product_in_stock_update->save();
                    }
                    $order->dondathang_tinh_trang_thanh_toan=1;
                }
                $order->save();
                $order_delivery_update->save();
                return Redirect::to('/delivery-show-detail/'.$order_id)->with('message', 'Xác nhận giao hàng thành công!');
            }
        }
    }

    public function OrderShowDetail($order_id){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $order=Order::find($order_id);
            if (!$order) {
                return Redirect::to('/order')->with('error', 'Đơn hàng không tồn tại!');
            } else {
                $order_detail=OrderDetail::where('chitietdondathang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->get();
                $order_delivery=Delivery::where('giaohang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->first();
                $order_customer=Customer::find($order->khachhang_id);
                $order_transport=TransportFee::find($order->phivanchuyen_id);
                $order_coupon=Coupon::find($order->makhuyenmai_id);
                foreach ($order_detail as $key =>$value) {
                    $order_detail_update=OrderDetail::find($value->id);
                    $order_detail_update->dondathang_id=$order_id;
                    $order_detail_update->save();
                }
                $order_delivery_update=Delivery::find($order_delivery->id);
                $order_delivery_update->dondathang_id=$order_id;
                $order->save();
                $order_delivery_update->save();
                return view('admin.pages.order.order_show_detail')
                ->with('order', $order)
                ->with('order_detail', $order_detail)
                ->with('order_delivery', $order_delivery)
                ->with('order_customer', $order_customer)
                ->with('order_transport', $order_transport)
                ->with('order_coupon', $order_coupon);
            }
        }
    }

    public function DeliveryShowDetail($order_id){
        $this->AuthLogin();
        $order=Order::find($order_id);
        if(!$order){
            return Redirect::to('/order')->with('error','Đơn hàng không tồn tại!');
        }else{
            $order_detail=OrderDetail::where('chitietdondathang_ma_don_dat_hang',$order->dondathang_ma_don_dat_hang)->get();
            $order_delivery=Delivery::where('giaohang_ma_don_dat_hang',$order->dondathang_ma_don_dat_hang)->first();
            $order_customer=Customer::find($order->khachhang_id);
            $order_transport=TransportFee::find($order->phivanchuyen_id);
            $order_coupon=Coupon::find($order->makhuyenmai_id);
            foreach($order_detail as $key =>$value){
                $order_detail_update=OrderDetail::find($value->id);
                $order_detail_update->dondathang_id=$order_id;
                $order_detail_update->save();
            }
            $order_delivery_update=Delivery::find($order_delivery->id);
            $order_delivery_update->dondathang_id=$order_id;
            $order->save();
            $order_delivery_update->save();
            return view('admin.pages.order.delivery_show_detail')
            ->with('order',$order)
            ->with('order_detail',$order_detail)
            ->with('order_delivery',$order_delivery)
            ->with('order_customer',$order_customer)
            ->with('order_transport',$order_transport)
            ->with('order_coupon',$order_coupon);
        }
    }

    public function OrderPrintPdf($order_id){
        $this->AuthLogin();
        if(Session::get('admin_role')==3){
            return Redirect::to('/dashboard');
        }else{
            $order=Order::find($order_id);
            if (!$order) {
                return Redirect::to('/order')->with('error','Đơn hàng không tồn tại!');
            } else {
                if ($order->dondathang_tinh_trang_thanh_toan==0 && $order->dondathang_trang_thai==0) {
                    $order->dondathang_trang_thai=0;
                } elseif ($order->dondathang_tinh_trang_thanh_toan==1 && $order->dondathang_tinh_trang_giao_hang==0) {
                    $order->dondathang_trang_thai=1;
                } else {
                    $order->dondathang_trang_thai=2;
                }
                $order->save();
                $order_detail=OrderDetail::where('chitietdondathang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->get();
                $order_delivery=Delivery::where('giaohang_ma_don_dat_hang', $order->dondathang_ma_don_dat_hang)->first();
                $order_customer=Customer::find($order->khachang_id);
                $order_transport=TransportFee::find($order->phivanchuyen_id);
                $order_coupon=Coupon::find($order->makhuyenmai_id);
                foreach ($order_detail as $key =>$value) {
                    $pro_id[]=$value->sanpham_id;
                }
                $product=Product::whereIn('id', $pro_id)->get();
                return view('admin.pages.order.order_print_pdf')
                ->with('order', $order)
                ->with('product', $product)
                ->with('order_detail', $order_detail)
                ->with('order_delivery', $order_delivery)
                ->with('order_customer', $order_customer)
                ->with('order_transport', $order_transport)
                ->with('order_coupon', $order_coupon);
            }
        }
    }
    public function UpdateOrderIdDelivery(){
        $this->AuthLogin();
        $all_delivery=Delivery::orderby('id','DESC')->get();
        $all_order=Order::all();
        foreach($all_order as $key =>$order){
            foreach($all_delivery as $key =>$delivery){
                if($order->dondathang_ma_don_dat_hang==$delivery->giaohang_ma_don_dat_hang){
                    $delivery_update=Delivery::find($delivery->id);
                    $delivery_update->dondathang_id=$order->id;
                    $delivery_update->save();
                }
            }
        }
        return Redirect::to('/delivery');
    }
    public function GetDelivery(){
        $this->AuthLogin();
        $all_delivery=Delivery::orderby('id','DESC')->paginate(5);
        $all_order=Order::all();
        foreach($all_order as $key =>$order){
            foreach($all_delivery as $key =>$delivery){
                if($order->dondathang_ma_don_dat_hang==$delivery->giaohang_ma_don_dat_hang){
                    $delivery_update=Delivery::find($delivery->id);
                    $delivery_update->dondathang_id=$order->id;
                    $delivery_update->save();
                }
            }
        }
        return view('admin.pages.order.delivery')
        ->with('all_delivery',$all_delivery);
    }
}
