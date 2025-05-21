<!-- resources/views/errors/404.blade.php -->
@extends('errors.template')

@section('title', 'Halaman Tidak Ditemukan')

@section('meta_description', 'Oops! Halaman yang Anda cari sepertinya hilang.')

@section('code', '404')

@section('background-class', 'bg-gradient-to-b from-pink-100 to-lime-100')

@section('error-code-color', 'text-blue-500')

@section('message', 'Ups, kami kehilangan halaman ini!')

@section('description')
    Sepertinya halaman yang anda tuju tidak tersedia. Jangan khawatir, coba kembali ke beranda untuk melanjutkan petualangan Anda. Jika masalah berlanjut, beri tahu kami!
@endsection