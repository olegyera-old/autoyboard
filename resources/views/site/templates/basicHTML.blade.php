<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0 height=device-height"">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Главная</title>
    <link rel="stylesheet" href="{{asset('css/site.css')}}">
    <link rel="stylesheet" href="{{asset('fonts/AlegreyaSans/alegreyasans.css')}}">
    <link rel="stylesheet" href="{{asset('fonts/Raleway/raleway.css')}}">
    <link rel="stylesheet" href="{{asset('fonts/Montserrat/montserrat.css')}}">
    <link rel="shortcut icon" href="{{asset('img/favicon1.png')}}" type="image/x-icon" sizes="25x25">
    <script src="{{asset('libs/Jquery.min.js')}}"></script>

    <link rel="stylesheet" href="{{asset('fonts/YboardFonts/style.css')}}">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous"/>
</head>
<body>
<div id="yb-site">
    @yield('header')
    @yield('content')
    @yield('footer')
</div>

<script src="{{asset('js/site.js')}}"></script>
<script src="{{asset('js/servis.js')}}"></script>
</body>
</html>
