<!-- resources/views/errors/500.blade.php -->
@extends('errors.template')

@section('title', 'Kesalahan Server Internal')

@section('meta_description', 'Terjadi kesalahan internal pada server kami.')

@section('code', '500')

@section('background-class', 'bg-gradient-to-b from-red-100 to-amber-100')

@section('error-code-color', 'text-red-500')

@section('message', 'Ups, sepertinya ada masalah di sisi kami.')

@section('description')
    Kami sedang mengalami masalah teknis di server kami, yang menyebabkan halaman ini tidak dapat dimuat. Tim kami bekerja keras untuk memperbaikinya secepat mungkin. Silakan coba lagi nanti atau hubungi dukungan jika masalah berlanjut.
@endsection