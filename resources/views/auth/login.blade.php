<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="erpkin-kahkin traders">
    <meta name="author" content="Sinnerman084">
    <meta name="keywords" content="ek-pos">

    <!-- Title Page-->
    <title>Login</title>
    <link rel="icon" href="{{ asset('images/icon/favicon.ico') }}">

    <!-- Fontfaces CSS-->
    <link href="{{ asset('css/font-face.css') }}" rel="stylesheet" media="all">
    <link href="{{ asset('vendor/font-awesome-4.7/css/font-awesome.min.css') }}" rel="stylesheet" media="all">
    <link href="{{ asset('vendor/font-awesome-5/css/fontawesome-all.min.css') }}" rel="stylesheet" media="all">
    <link href="{{ asset('vendor/mdi-font/css/material-design-iconic-font.min.css') }}" rel="stylesheet" media="all">

    <!-- Bootstrap CSS-->
    <link href="{{ asset('vendor/bootstrap-4.1/bootstrap.min.css') }}" rel="stylesheet" media="all">

    <!-- Vendor CSS-->
    <link href="{{ asset('vendor/animsition/animsition.min.css') }}" rel="stylesheet" media="all">
    <link href="{{ asset('vendor/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css') }}" rel="stylesheet"
        media="all">
    <link href="{{ asset('vendor/wow/animate.css') }}" rel="stylesheet" media="all">
    <link href="{{ asset('vendor/css-hamburgers/hamburgers.min.css') }}" rel="stylesheet" media="all">
    <link href="{{ asset('vendor/slick/slick.css') }}" rel="stylesheet" media="all">
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" media="all">
    <link href="{{ asset('vendor/perfect-scrollbar/perfect-scrollbar.css') }}" rel="stylesheet" media="all">

    <!-- Main CSS-->
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet" media="all">

    <!--Custom CSS-->
    <link href="{{ asset('css/main.css') }}" rel="stylesheet" media="all">

    <style>
        .btn {
            display: inline-block;
            *display: inline;
            *zoom: 1;
            padding: 4px 10px 4px;
            margin-bottom: 0;
            font-size: 13px;
            line-height: 18px;
            color: #333333;
            text-align: center;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
            vertical-align: middle;
            background-color: #f5f5f5;
            background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
            background-image: -ms-linear-gradient(top, #ffffff, #e6e6e6);
            background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
            background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
            background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
            background-image: linear-gradient(top, #ffffff, #e6e6e6);
            background-repeat: repeat-x;
            filter: progid:dximagetransform.microsoft.gradient(startColorstr=#ffffff, endColorstr=#e6e6e6, GradientType=0);
            border-color: #e6e6e6 #e6e6e6 #e6e6e6;
            border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
            border: 1px solid #e6e6e6;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
            -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
            -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            *margin-left: .3em;
        }

        .btn:hover,
        .btn:active,
        .btn.active,
        .btn.disabled,
        .btn[disabled] {
            background-color: #e6e6e6;
        }

        .btn-large {
            padding: 9px 14px;
            font-size: 15px;
            line-height: normal;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            border-radius: 5px;
        }

        .btn:hover {
            color: #333333;
            text-decoration: none;
            background-color: #e6e6e6;
            background-position: 0 -15px;
            -webkit-transition: background-position 0.1s linear;
            -moz-transition: background-position 0.1s linear;
            -ms-transition: background-position 0.1s linear;
            -o-transition: background-position 0.1s linear;
            transition: background-position 0.1s linear;
        }

        .btn-primary,
        .btn-primary:hover {
            text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
            color: #ffffff;
        }

        .btn-primary.active {
            color: rgba(255, 255, 255, 0.75);
        }

        .btn-primary {
            background-color: #4a77d4;
            background-image: -moz-linear-gradient(top, #6eb6de, #4a77d4);
            background-image: -ms-linear-gradient(top, #6eb6de, #4a77d4);
            background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#6eb6de), to(#4a77d4));
            background-image: -webkit-linear-gradient(top, #6eb6de, #4a77d4);
            background-image: -o-linear-gradient(top, #6eb6de, #4a77d4);
            background-image: linear-gradient(top, #6eb6de, #4a77d4);
            background-repeat: repeat-x;
            filter: progid:dximagetransform.microsoft.gradient(startColorstr=#6eb6de, endColorstr=#4a77d4, GradientType=0);
            border: 1px solid #3762bc;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.4);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .btn-primary:hover,
        .btn-primary:active,
        .btn-primary.active,
        .btn-primary.disabled,
        .btn-primary[disabled] {
            filter: none;
            background-color: #4a77d4;
        }

        .btn-block {
            width: 100%;
            display: block;
        }

        * {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            -ms-box-sizing: border-box;
            -o-box-sizing: border-box;
            box-sizing: border-box;
        }

        html {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        body {
            width: 100%;
            height: 100%;
            font-family: 'Open Sans', sans-serif;
        }

        .login {
            position: absolute;
            top: 50%;
            left: 50%;
            margin: -150px 0 0 -150px;
            width: 300px;
            height: 300px;
        }

        .login h1 {
            color: #fff;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
            text-align: center;
        }

        input {
            width: 100%;
            margin-bottom: 10px;
            background: rgba(0, 0, 0, 0.3);
            border: none;
            outline: none;
            padding: 10px;
            font-size: 13px;
            color: #fff;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 0, 0, 0.3);
            border-radius: 4px;
            box-shadow: inset 0 -5px 45px rgba(100, 100, 100, 0.2), 0 1px 1px rgba(255, 255, 255, 0.2);
            -webkit-transition: box-shadow .5s ease;
            -moz-transition: box-shadow .5s ease;
            -o-transition: box-shadow .5s ease;
            -ms-transition: box-shadow .5s ease;
            transition: box-shadow .5s ease;
        }

        input:focus {
            box-shadow: inset 0 -5px 45px rgba(100, 100, 100, 0.4), 0 1px 1px rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="page-content--bge5">
            <div class="container">
                <div class="login-wrap">
                    <div class="login ">
                        <div class="login-logo">
                            <a href="#">
                                <img src="{{ asset('images/icon/logo-default.png') }}" alt="erpKIN">
                            </a>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif
                        <form method="post" action="{{ route('login-auth') }}" autocomplete="off">
                            @csrf
                            <input id="username" type="text" name="username" value="{{ old('username') }}"
                                placeholder="Username"
                                class="input-sm form-control-sm form-control  @error('username') is-invalid @enderror"
                                required="required">
                            @error('username')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <input id="password" type="password" name="password" placeholder="Password" value=""
                                class="input-sm form-control-sm form-control  @error('password') is-invalid @enderror"
                                required="required">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <button type="submit" class="btn btn-primary btn-block btn-large">Sign In.</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Jquery JS-->
    <script src="{{ asset('vendor/jquery-3.6.x.min.js') }}"></script>
    <!-- Bootstrap JS-->
    <script src="{{ asset('vendor/bootstrap-4.1/popper.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap-4.1/bootstrap.min.js') }}"></script>
    <!-- Vendor JS       -->
    <script src="{{ asset('vendor/slick/slick.min.js') }}"></script>
    <script src="{{ asset('vendor/wow/wow.min.js') }}"></script>
    <script src="{{ asset('vendor/animsition/animsition.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap-progressbar/bootstrap-progressbar.min.js') }}"></script>
    <script src="{{ asset('vendor/counter-up/jquery.waypoints.min.js') }}"></script>
    <script src="{{ asset('vendor/counter-up/jquery.counterup.min.js') }}"></script>
    <script src="{{ asset('vendor/circle-progress/circle-progress.min.js') }}"></script>
    <script src="{{ asset('vendor/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('vendor/chartjs/Chart.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>

</body>

</html>
<!-- end document-->
