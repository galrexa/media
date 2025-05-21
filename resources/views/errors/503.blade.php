<!-- resources/views/errors/503.blade.php -->
@extends('errors.template')

@section('title', 'Layanan Tidak Tersedia')

@section('meta_description', 'Layanan sementara tidak tersedia karena pemeliharaan atau overload.')

@section('code', '503')

@section('background-class', 'bg-gradient-to-b from-gray-100 to-blue-100')

@section('error-code-color', 'text-gray-500')

@section('message', 'Maaf, layanan kami sedang offline untuk sementara.')

@section('description')
    Kami sedang melakukan pemeliharaan atau mengalami lonjakan lalu lintas yang tinggi, sehingga layanan ini sementara tidak tersedia. Mohon bersabar, dan coba lagi dalam beberapa menit. Terima kasih atas pengertiannya!
@endsection