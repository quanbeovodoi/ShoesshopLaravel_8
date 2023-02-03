<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>SHOES SHOP</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('public/frontend/img/favicon.png')}}">

    <!-- all css here -->
    <link rel="stylesheet" href="{{asset('public/frontend/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/plugin.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/bundle.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/style.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/lightgallery.min.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/responsive.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/lightslider.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/prettify.css')}}">
    <link rel="stylesheet" href="{{asset('public/frontend/css/rate.css')}}">
    <script src="{{asset('public/frontend/js/vendor/modernizr-2.8.3.min.js')}}"></script>
    <link href="{{URL::asset('public/frontend/css/sweetalert.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css">
    <style>
    a {
        text-transform: uppercase;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        display: inline-block;
        padding: 8px 23px;
        background: red;
        border-radius: 5px;
    }
    .center{
        text-align:center;
        padding:5%;
    }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <section class="content">
            <div class="full-screen" style="min-height: 549px; background: #fff">
                <div class="center" style="padding-top: 5%">
                    <div class="center logo">
                        <img src="{{asset('public/frontend/img/logo/logo.png')}}" width="128px"
                                height="89px" alt="">
                    </div>
                    <h3 class="text-center" style="color: red">Rất tiếc, trang bạn tìm không tồn tại !</h3>
                    <p class="text-center" style="padding-left: 20px;">Nhấn vào
                        <a href="./">đây</a> để ở về trang chủ !!
                    </p>
                </div>
            </div>
        </section>
    </div>
</body>

</html>