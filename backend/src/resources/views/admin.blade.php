<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'РК ПРОФИ') }} — Админка</title>
    {{-- Metronic только в админке; публичный CSS сюда не попадает --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
    <link rel="stylesheet" href="/metronic/plugins/global/plugins.bundle.css">
    <link rel="stylesheet" href="/metronic/css/style.bundle.css">
    @vite(['resources/js/admin.js'])
    @inertiaHead
</head>
<body id="kt_body">
    @inertia
</body>
</html>
