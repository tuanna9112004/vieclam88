<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <h1>{{ config('app.name', 'vieclam88') }}</h1>
            <p class="text-secondary">Laravel {{ app()->version() }} — toolchain khởi tạo (Bootstrap 5.3 + Alpine.js).</p>
        </div>
    </body>
</html>
