<!-- resources/views/errors/429.blade.php -->
@extends('errors.template')

@section('title', 'Terlalu Banyak Permintaan')

@section('meta_description', 'Anda telah melampaui batas permintaan yang diizinkan.')

@section('code', '429')

@section('background-class', 'bg-gradient-to-b from-yellow-100 to-green-100')

@section('error-code-color', 'text-yellow-500')

@section('message', 'Wah, Anda terlalu bersemangat!')

@section('description')
    Sepertinya Anda telah mengirim terlalu banyak permintaan dalam waktu singkat. Untuk menjaga performa layanan, kami perlu memberi jeda sejenak. Silakan tunggu beberapa saat sebelum mencoba lagi, atau periksa batas penggunaan Anda.
@endsection