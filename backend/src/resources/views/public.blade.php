<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <title inertia>{{ config('app.name', 'РК ПРОФИ') }}</title>
    {{-- Порядок CSS как в grabber/contacts (без adblock-дампа main(1).css) --}}
    <link rel="stylesheet" href="/template/main.css">
    <link rel="stylesheet" href="/template/vendors.css">
    <link rel="stylesheet" href="/template/content.css">
    <link rel="stylesheet" href="/template/request-form.css">
    <link rel="stylesheet" href="/template/site-menu.css">
    <link rel="stylesheet" href="/template/catalog-demo.css">
    <link rel="stylesheet" href="/template/product-gallery.css">
    @vite(['resources/js/public.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
