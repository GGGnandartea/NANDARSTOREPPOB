@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-12 text-center">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-10 max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Dashboard Reseller</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">Selamat datang, {{ Auth::user()->name }}! Fitur riwayat transaksi dan panel reseller sedang dalam pengembangan.</p>
        
        <a href="{{ route('home') }}" class="inline-block bg-orange-500 text-white font-bold py-3 px-6 rounded-xl hover:bg-orange-600 transition">Mulai Belanja</a>
        
        <form action="{{ route('logout') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit" class="text-red-500 hover:underline">Logout</button>
        </form>
    </div>
</div>
@endsection