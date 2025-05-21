<!-- resources/views/errors/403.blade.php -->
@extends('errors.template')

@section('title', 'Akses Ditolak')

@section('meta_description', 'Anda tidak memiliki izin untuk mengakses halaman ini.')

@section('code', '403')

@section('background-class', 'bg-gradient-to-b from-purple-100 to-cyan-100')

@section('error-code-color', 'text-purple-500')

@section('message', 'Maaf, akses ke halaman ini ditolak.')

@section('description')
    Sepertinya Anda tidak memiliki izin untuk melihat konten ini. Ini bisa terjadi karena batasan izin atau kebijakan keamanan. Silakan hubungi administrator situs atau coba masuk dengan akun yang tepat untuk melanjutkan.
@endsection