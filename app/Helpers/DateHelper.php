<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Format tanggal ke Bahasa Indonesia
     *
     * @param mixed $date
     * @param string $format
     * @return string
     */
    public static function formatIndonesian($date, $format = 'd F Y')
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }
        
        // Nama bulan dalam Bahasa Indonesia
        $indonesianMonths = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        // Nama hari dalam Bahasa Indonesia
        $indonesianDays = [
            'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
        ];
        
        $month = $indonesianMonths[$date->month - 1];
        $day = $indonesianDays[$date->dayOfWeek];
        
        // Ganti nama bulan dan hari dalam format
        $result = str_replace('F', $month, $date->format($format));
        $result = str_replace('l', $day, $result);
        
        return $result;
    }

    /**
     * Format tanggal lengkap ke Bahasa Indonesia
     *
     * @param mixed $date
     * @return string
     */
    public static function fullDate($date)
    {
        return self::formatIndonesian($date, 'l, d F Y');
    }

    /**
     * Format tanggal dan waktu ke Bahasa Indonesia
     *
     * @param mixed $date
     * @return string
     */
    public static function dateTime($date)
    {
        return self::formatIndonesian($date, 'd F Y H:i');
    }
}