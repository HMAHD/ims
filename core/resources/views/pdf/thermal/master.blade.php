<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ gs()->siteName($pageTitle ?? '') }}</title>
    <!-- favicon -->
    <link rel="shortcut icon" type="image/png" href="{{ getImage(getFilePath('logoIcon') . '/favicon.png') }}">

    <link rel="stylesheet" href="{{ asset('assets/admin/css/thermal.css') }}">
</head>

<body>
    <div class="header">
        @if(gs('site_address'))
        <div class="text-sm">{{ __(gs('site_address')) }}</div>
        @endif
        @if(gs('site_phone'))
        <div class="text-sm">Tel: {{ __(gs('site_phone')) }}</div>
        @endif
        @if(gs('site_email'))
        <div class="text-sm">Email: {{ __(gs('site_email')) }}</div>
        @endif
    </div>

    <div class="content">
        @yield('main-content')
    </div>

    <div class="footer">
        <div class="thank-you">@lang('Thank You!')</div>
        <div class="text-xs">@lang('Powered by AiSoftware.lk')</div>
        <div class="cut-line"></div>
    </div>
</body>

</html>