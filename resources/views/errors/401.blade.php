<!-- resources/views/errors/401.blade.php -->
@extends('errors.template')

@section('title', 'Tidak Diizinkan')

@section('meta_description', 'Anda perlu masuk untuk mengakses halaman ini.')

@section('code', '401')

@section('message', 'Anda Belum Masuk')

@section('description')
    Halaman ini memerlukan otentikasi. Silakan masuk dengan akun Anda untuk melanjutkan. Jika Anda lupa kata sandi, gunakan opsi pemulihan akun. Jika Anda merasa ini adalah kesalahan, hubungi tim dukungan kami.
@endsection