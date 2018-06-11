<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'Sample App') - Laravel</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
@include('layouts._header')
<body>

<div class="container">
    @yield('content')
    @include('layouts._footer')
</div>
</body>
</html>