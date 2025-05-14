<?php
// app/Helpers/AlertHelper.php

namespace App\Helpers;

class AlertHelper
{
    /**
     * Tipe alert yang didukung
     */
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';
    const INFO = 'info';
    const QUESTION = 'question';

    /**
     * ==============================
     * ALERT DASAR
     * ==============================
     */

    /**
     * Alert dasar sederhana
     *
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function basic(string $message, array $options = [])
    {
        $data = array_merge([
            'text' => self::sanitize($message),
        ], $options);

        self::flash($data);
    }

    /**
     * Alert dasar dengan judul
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function basicWithTitle(string $title, string $message, array $options = [])
    {
        $data = array_merge([
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
        ], $options);

        self::flash($data);
    }

    /**
     * Alert dasar dengan footer
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $footer Teks footer
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function basicWithFooter(string $title, string $message, string $footer, array $options = [])
    {
        $data = array_merge([
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'footer' => self::sanitize($footer),
        ], $options);

        self::flash($data);
    }

    /**
     * Alert dasar dengan HTML
     *
     * @param string $title Judul alert
     * @param string $htmlContent Konten HTML
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function basicHtml(string $title, string $htmlContent, array $options = [])
    {
        // Catatan: HTML tidak disanitasi untuk memungkinkan markup
        $data = array_merge([
            'title' => self::sanitize($title),
            'html' => $htmlContent,
        ], $options);

        self::flash($data);
    }

    /**
     * ==============================
     * TIPE ALERT BERDASARKAN IKON
     * ==============================
     */

    /**
     * Alert sukses
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function success(string $title, string $message = '', array $options = [])
    {
        $data = [
            'icon' => self::SUCCESS,
            'title' => self::sanitize($title),
        ];

        if (!empty($message)) {
            $data['text'] = self::sanitize($message);
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert error
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function error(string $title, string $message = '', array $options = [])
    {
        $data = [
            'icon' => self::ERROR,
            'title' => self::sanitize($title),
        ];

        if (!empty($message)) {
            $data['text'] = self::sanitize($message);
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert warning
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function warning(string $title, string $message = '', array $options = [])
    {
        $data = [
            'icon' => self::WARNING,
            'title' => self::sanitize($title),
        ];

        if (!empty($message)) {
            $data['text'] = self::sanitize($message);
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert info
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function info(string $title, string $message = '', array $options = [])
    {
        $data = [
            'icon' => self::INFO,
            'title' => self::sanitize($title),
        ];

        if (!empty($message)) {
            $data['text'] = self::sanitize($message);
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert question
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function question(string $title, string $message = '', array $options = [])
    {
        $data = [
            'icon' => self::QUESTION,
            'title' => self::sanitize($title),
        ];

        if (!empty($message)) {
            $data['text'] = self::sanitize($message);
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * ==============================
     * POSISI ALERT
     * ==============================
     */

    /**
     * Mengatur posisi alert
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $position Posisi alert (top-start|top-center|top-end|center|bottom-start|bottom-center|bottom-end)
     * @param string $icon Icon alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function position(string $title, string $message, string $position, string $icon = '', array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'position' => $position,
        ];

        if (!empty($icon)) {
            $data['icon'] = $icon;
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert posisi top-start
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $icon Icon alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function positionTopStart(string $title, string $message, string $icon = '', array $options = [])
    {
        self::position($title, $message, 'top-start', $icon, $options);
    }

    /**
     * Alert posisi top-center
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $icon Icon alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function positionTopCenter(string $title, string $message, string $icon = '', array $options = [])
    {
        self::position($title, $message, 'top-center', $icon, $options);
    }

    /**
     * Alert posisi top-end
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $icon Icon alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function positionTopEnd(string $title, string $message, string $icon = '', array $options = [])
    {
        self::position($title, $message, 'top-end', $icon, $options);
    }

    /**
     * Alert posisi center
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $icon Icon alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function positionCenter(string $title, string $message, string $icon = '', array $options = [])
    {
        self::position($title, $message, 'center', $icon, $options);
    }

    /**
     * Alert posisi bottom-start
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $icon Icon alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function positionBottomStart(string $title, string $message, string $icon = '', array $options = [])
    {
        self::position($title, $message, 'bottom-start', $icon, $options);
    }

    /**
     * Alert posisi bottom-center
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $icon Icon alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function positionBottomCenter(string $title, string $message, string $icon = '', array $options = [])
    {
        self::position($title, $message, 'bottom-center', $icon, $options);
    }

    /**
     * Alert posisi bottom-end
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $icon Icon alert (opsional)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function positionBottomEnd(string $title, string $message, string $icon = '', array $options = [])
    {
        self::position($title, $message, 'bottom-end', $icon, $options);
    }

    /**
     * ==============================
     * EFEK ANIMASI
     * ==============================
     */

    /**
     * Alert dengan animasi
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $animation Jenis animasi (pop|fade|flip|bounce|custom|none)
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function animation(string $title, string $message, string $animation, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
        ];

        switch ($animation) {
            case 'pop':
                $data['showClass'] = ['popup' => 'animate__animated animate__popIn'];
                $data['hideClass'] = ['popup' => 'animate__animated animate__popOut'];
                break;
            case 'fade':
                $data['showClass'] = ['popup' => 'animate__animated animate__fadeIn'];
                $data['hideClass'] = ['popup' => 'animate__animated animate__fadeOut'];
                break;
            case 'flip':
                $data['showClass'] = ['popup' => 'animate__animated animate__flipInX'];
                $data['hideClass'] = ['popup' => 'animate__animated animate__flipOutX'];
                break;
            case 'bounce':
                $data['showClass'] = ['popup' => 'animate__animated animate__bounceIn'];
                $data['hideClass'] = ['popup' => 'animate__animated animate__bounceOut'];
                break;
            case 'custom':
                // Akan diisi oleh opsi tambahan
                break;
            case 'none':
                $data['showClass'] = ['popup' => ''];
                $data['hideClass'] = ['popup' => ''];
                break;
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert dengan animasi pop
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function animationPop(string $title, string $message, array $options = [])
    {
        self::animation($title, $message, 'pop', $options);
    }

    /**
     * Alert dengan animasi fade
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function animationFade(string $title, string $message, array $options = [])
    {
        self::animation($title, $message, 'fade', $options);
    }

    /**
     * Alert dengan animasi flip
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function animationFlip(string $title, string $message, array $options = [])
    {
        self::animation($title, $message, 'flip', $options);
    }

    /**
     * Alert dengan animasi bounce
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function animationBounce(string $title, string $message, array $options = [])
    {
        self::animation($title, $message, 'bounce', $options);
    }

    /**
     * Alert dengan animasi kustom
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $showClass Array kelas animasi show
     * @param array $hideClass Array kelas animasi hide
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function animationCustom(string $title, string $message, array $showClass, array $hideClass, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'showClass' => $showClass,
            'hideClass' => $hideClass,
        ];

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert tanpa animasi
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function animationNone(string $title, string $message, array $options = [])
    {
        self::animation($title, $message, 'none', $options);
    }

    /**
     * ==============================
     * ALERT DENGAN INPUT
     * ==============================
     */

    /**
     * Alert dengan input
     *
     * @param string $title Judul alert
     * @param string $inputType Tipe input (text|email|password|number|tel|textarea|select|radio|checkbox|file|range)
     * @param array $inputOptions Opsi untuk input
     * @param array $options Opsi tambahan untuk SweetAlert
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function input(string $title, string $inputType, array $inputOptions = [], array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'input' => $inputType,
            'showCancelButton' => true,
            'returnFocus' => false, // Mencegah fokus kembali ke elemen sebelumnya
        ];

        // Gabungkan inputOptions jika disediakan
        if (!empty($inputOptions)) {
            foreach ($inputOptions as $key => $value) {
                $data["input{$key}"] = $value;
            }
        }

        return array_merge($data, $options);
    }

    /**
     * Alert dengan text input
     *
     * @param string $title Judul alert
     * @param string $placeholder Placeholder input
     * @param string $defaultValue Nilai default
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputText(string $title, string $placeholder = '', string $defaultValue = '', array $options = [])
    {
        $inputOptions = [];
        
        if (!empty($placeholder)) {
            $inputOptions['Placeholder'] = self::sanitize($placeholder);
        }
        
        if (!empty($defaultValue)) {
            $inputOptions['Value'] = self::sanitize($defaultValue);
        }

        return self::input($title, 'text', $inputOptions, $options);
    }

    /**
     * Alert dengan email input
     *
     * @param string $title Judul alert
     * @param string $placeholder Placeholder input
     * @param string $defaultValue Nilai default
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputEmail(string $title, string $placeholder = '', string $defaultValue = '', array $options = [])
    {
        $inputOptions = [];
        
        if (!empty($placeholder)) {
            $inputOptions['Placeholder'] = self::sanitize($placeholder);
        }
        
        if (!empty($defaultValue)) {
            $inputOptions['Value'] = self::sanitize($defaultValue);
        }

        return self::input($title, 'email', $inputOptions, $options);
    }

    /**
     * Alert dengan password input
     *
     * @param string $title Judul alert
     * @param string $placeholder Placeholder input
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputPassword(string $title, string $placeholder = '', array $options = [])
    {
        $inputOptions = [];
        
        if (!empty($placeholder)) {
            $inputOptions['Placeholder'] = self::sanitize($placeholder);
        }

        return self::input($title, 'password', $inputOptions, $options);
    }

    /**
     * Alert dengan textarea input
     *
     * @param string $title Judul alert
     * @param string $placeholder Placeholder input
     * @param string $defaultValue Nilai default
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputTextarea(string $title, string $placeholder = '', string $defaultValue = '', array $options = [])
    {
        $inputOptions = [];
        
        if (!empty($placeholder)) {
            $inputOptions['Placeholder'] = self::sanitize($placeholder);
        }
        
        if (!empty($defaultValue)) {
            $inputOptions['Value'] = self::sanitize($defaultValue);
        }

        return self::input($title, 'textarea', $inputOptions, $options);
    }

    /**
     * Alert dengan select input
     *
     * @param string $title Judul alert
     * @param array $options Array opsi select [value => label]
     * @param string $defaultValue Nilai default
     * @param array $swalOptions Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputSelect(string $title, array $options, string $defaultValue = '', array $swalOptions = [])
    {
        $sanitizedOptions = [];
        foreach ($options as $value => $label) {
            $sanitizedOptions[self::sanitize($value)] = self::sanitize($label);
        }

        $inputOptions = [
            'Options' => $sanitizedOptions
        ];
        
        if (!empty($defaultValue)) {
            $inputOptions['Value'] = self::sanitize($defaultValue);
        }

        return self::input($title, 'select', $inputOptions, $swalOptions);
    }

    /**
     * Alert dengan radio input
     *
     * @param string $title Judul alert
     * @param array $options Array opsi radio [value => label]
     * @param string $defaultValue Nilai default
     * @param array $swalOptions Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputRadio(string $title, array $options, string $defaultValue = '', array $swalOptions = [])
    {
        $sanitizedOptions = [];
        foreach ($options as $value => $label) {
            $sanitizedOptions[self::sanitize($value)] = self::sanitize($label);
        }

        $inputOptions = [
            'Options' => $sanitizedOptions
        ];
        
        if (!empty($defaultValue)) {
            $inputOptions['Value'] = self::sanitize($defaultValue);
        }

        return self::input($title, 'radio', $inputOptions, $swalOptions);
    }

    /**
     * Alert dengan checkbox input
     *
     * @param string $title Judul alert
     * @param string $checkboxLabel Label checkbox
     * @param bool $checked Apakah checkbox dicentang
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputCheckbox(string $title, string $checkboxLabel, bool $checked = false, array $options = [])
    {
        $inputOptions = [
            'Value' => $checked ? 1 : 0,
            'Label' => self::sanitize($checkboxLabel)
        ];

        return self::input($title, 'checkbox', $inputOptions, $options);
    }

    /**
     * Alert dengan file input
     *
     * @param string $title Judul alert
     * @param string $acceptTypes Tipe file yang diterima (e.g., "image/*")
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputFile(string $title, string $acceptTypes = '', array $options = [])
    {
        $inputOptions = [];
        
        if (!empty($acceptTypes)) {
            $inputOptions['Accept'] = $acceptTypes;
        }

        return self::input($title, 'file', $inputOptions, $options);
    }

    /**
     * Alert dengan range input
     *
     * @param string $title Judul alert
     * @param int $min Nilai minimum
     * @param int $max Nilai maksimum
     * @param int $step Langkah
     * @param int $defaultValue Nilai default
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputRange(string $title, int $min, int $max, int $step = 1, int $defaultValue = 0, array $options = [])
    {
        $inputOptions = [
            'Range' => [
                'min' => $min,
                'max' => $max,
                'step' => $step
            ],
            'Value' => $defaultValue
        ];

        return self::input($title, 'range', $inputOptions, $options);
    }

    /**
     * Alert dengan multiple input (konfigurasi lanjutan)
     *
     * @param string $title Judul alert
     * @param array $inputConfig Konfigurasi input dari SweetAlert
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function inputMultiple(string $title, array $inputConfig, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'html' => $inputConfig['html'] ?? '',
            'focusConfirm' => false,
            'showCancelButton' => true,
            'preConfirm' => $inputConfig['preConfirm'] ?? 'function() { return [] }',
        ];

        return array_merge($data, $options);
    }

    /**
     * ==============================
     * KONFIRMASI & NAVIGASI
     * ==============================
     */

    /**
     * Alert konfirmasi dialog
     *
     * @param string $title Judul konfirmasi
     * @param string $message Pesan konfirmasi
     * @param string $confirmText Teks tombol konfirmasi
     * @param string $cancelText Teks tombol batal
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function confirmDialog(string $title, string $message = '', string $confirmText = 'Ya', string $cancelText = 'Tidak', array $options = [])
    {
        $defaultOptions = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'icon' => self::WARNING,
            'showCancelButton' => true,
            'confirmButtonText' => self::sanitize($confirmText),
            'cancelButtonText' => self::sanitize($cancelText),
            'reverseButtons' => true,
        ];

        return array_merge($defaultOptions, $options);
    }

    /**
     * Alert konfirmasi dengan cancel
     *
     * @param string $title Judul konfirmasi
     * @param string $message Pesan konfirmasi
     * @param string $confirmText Teks tombol konfirmasi
     * @param string $cancelText Teks tombol batal
     * @param string $denyText Teks tombol deny
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function confirmWithCancel(string $title, string $message = '', string $confirmText = 'Ya', string $cancelText = 'Batal', string $denyText = 'Tidak', array $options = [])
    {
        $defaultOptions = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'icon' => self::WARNING,
            'showDenyButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => self::sanitize($confirmText),
            'denyButtonText' => self::sanitize($denyText),
            'cancelButtonText' => self::sanitize($cancelText),
        ];

        return array_merge($defaultOptions, $options);
    }

    /**
     * Alert konfirmasi dengan auto close
     *
     * @param string $title Judul konfirmasi
     * @param string $message Pesan konfirmasi
     * @param int $timer Waktu auto close dalam milidetik
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk digunakan di view
     */
    public static function confirmAutoClose(string $title, string $message = '', int $timer = 5000, array $options = [])
    {
        $defaultOptions = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'icon' => self::SUCCESS,
            'timer' => $timer,
            'timerProgressBar' => true,
            'showConfirmButton' => false,
        ];

        return array_merge($defaultOptions, $options);
    }

    /**
     * Alert konfirmasi dengan redirect
     *
     * @param string $title Judul konfirmasi
     * @param string $message Pesan konfirmasi
     * @param string $redirectUrl URL untuk redirect
     * @param string $confirmText Teks tombol konfirmasi
     * @param string $cancelText Teks tombol batal
     * @param array $options Opsi tambahan
     * @return array Konfigurasi untuk penggunaan di JavaScript
     */
    public static function confirmWithRedirect(string $title, string $message = '', string $redirectUrl = '', string $confirmText = 'Ya', string $cancelText = 'Tidak', array $options = [])
    {
        $defaultOptions = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'icon' => self::WARNING,
            'showCancelButton' => true,
            'confirmButtonText' => self::sanitize($confirmText),
            'cancelButtonText' => self::sanitize($cancelText),
            'redirectUrl' => $redirectUrl, // Akan diproses di JavaScript
        ];

        return array_merge($defaultOptions, $options);
    }

    /**
     * ==============================
     * KUSTOM & FITUR LAIN
     * ==============================
     */

    /**
     * Alert dengan gambar
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $imageUrl URL gambar
     * @param string $imageAlt Alt text untuk gambar
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function customImage(string $title, string $message, string $imageUrl, string $imageAlt = '', array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'imageUrl' => $imageUrl,
            'imageHeight' => $options['imageHeight'] ?? 'auto',
            'imageWidth' => $options['imageWidth'] ?? 'auto',
        ];

        if (!empty($imageAlt)) {
            $data['imageAlt'] = self::sanitize($imageAlt);
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert dengan background kustom
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $background Background CSS
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function customBackground(string $title, string $message, string $background, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'background' => $background, // e.g. '#ff5555' or 'url(image.jpg)'
        ];

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert dengan lebar kustom
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param string $width Lebar CSS
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function customWidth(string $title, string $message, string $width, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'width' => $width, // e.g. '850px' or '80%'
        ];

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert dengan tombol kustom
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $buttonOptions Opsi tombol
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function customButtons(string $title, string $message, array $buttonOptions, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'customClass' => [
                'confirmButton' => $buttonOptions['confirmButtonClass'] ?? '',
                'cancelButton' => $buttonOptions['cancelButtonClass'] ?? '',
                'denyButton' => $buttonOptions['denyButtonClass'] ?? '',
            ],
        ];

        // Tambahkan warna tombol jika disediakan
        if (isset($buttonOptions['confirmButtonColor'])) {
            $data['confirmButtonColor'] = $buttonOptions['confirmButtonColor'];
        }
        
        if (isset($buttonOptions['cancelButtonColor'])) {
            $data['cancelButtonColor'] = $buttonOptions['cancelButtonColor'];
        }
        
        if (isset($buttonOptions['denyButtonColor'])) {
            $data['denyButtonColor'] = $buttonOptions['denyButtonColor'];
        }

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert dengan custom loader
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param bool $showLoader Tampilkan loader
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function customLoader(string $title, string $message, bool $showLoader = true, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'showLoaderOnConfirm' => $showLoader,
            'preConfirm' => $options['preConfirm'] ?? 'function() { return new Promise(resolve => setTimeout(resolve, 2000)) }',
            'allowOutsideClick' => false,
        ];

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert dengan support Right-to-Left (RTL)
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function customRtl(string $title, string $message, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'direction' => 'rtl',
        ];

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert dengan tema gelap
     *
     * @param string $title Judul alert
     * @param string $message Pesan alert
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function customTheme(string $title, string $message, array $options = [])
    {
        $data = [
            'title' => self::sanitize($title),
            'text' => self::sanitize($message),
            'background' => '#333',
            'customClass' => [
                'title' => 'text-light',
                'content' => 'text-light',
                'popup' => 'dark-theme-popup',
            ],
        ];

        self::flash(array_merge($data, $options));
    }

    /**
     * Alert dengan queue (akan ditampilkan secara berurutan)
     *
     * @param array $queueConfig Array konfigurasi alert
     * @return void
     */
    public static function customQueue(array $queueConfig)
    {
        session()->flash('sweet_alert_queue', $queueConfig);
    }

    /**
     * ==============================
     * TOAST NOTIFICATIONS
     * ==============================
     */

    /**
     * Toast notification
     *
     * @param string $message Pesan toast
     * @param string $icon Icon toast (success, error, warning, info)
     * @param string $position Posisi toast
     * @param int $timer Waktu tampil dalam milidetik
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function toast(string $message, string $icon = self::INFO, string $position = 'top-end', int $timer = 3000, array $options = [])
    {
        $defaultOptions = [
            'toast' => true,
            'position' => $position,
            'showConfirmButton' => false,
            'timer' => $timer,
            'timerProgressBar' => true,
            'icon' => $icon,
            'title' => self::sanitize($message),
        ];

        self::flash(array_merge($defaultOptions, $options), 'sweet_alert_toast');
    }

    /**
     * Toast success
     *
     * @param string $message Pesan toast
     * @param string $position Posisi toast
     * @param int $timer Waktu tampil dalam milidetik
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function toastSuccess(string $message, string $position = 'top-end', int $timer = 3000, array $options = [])
    {
        self::toast($message, self::SUCCESS, $position, $timer, $options);
    }

    /**
     * Toast error
     *
     * @param string $message Pesan toast
     * @param string $position Posisi toast
     * @param int $timer Waktu tampil dalam milidetik
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function toastError(string $message, string $position = 'top-end', int $timer = 3000, array $options = [])
    {
        self::toast($message, self::ERROR, $position, $timer, $options);
    }

    /**
     * Toast warning
     *
     * @param string $message Pesan toast
     * @param string $position Posisi toast
     * @param int $timer Waktu tampil dalam milidetik
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function toastWarning(string $message, string $position = 'top-end', int $timer = 3000, array $options = [])
    {
        self::toast($message, self::WARNING, $position, $timer, $options);
    }

    /**
     * Toast info
     *
     * @param string $message Pesan toast
     * @param string $position Posisi toast
     * @param int $timer Waktu tampil dalam milidetik
     * @param array $options Opsi tambahan
     * @return void
     */
    public static function toastInfo(string $message, string $position = 'top-end', int $timer = 3000, array $options = [])
    {
        self::toast($message, self::INFO, $position, $timer, $options);
    }

    /**
     * ==============================
     * METODE UTILITAS
     * ==============================
     */

    /**
     * Flash alert ke session
     *
     * @param array $data Data alert
     * @param string $key Kunci session
     * @return void
     */
    protected static function flash(array $data, string $key = 'sweet_alert')
    {
        session()->flash($key, $data);
    }

    /**
     * Sanitasi input untuk mencegah XSS
     *
     * @param string $input
     * @return string
     */
    protected static function sanitize(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Cek apakah ada alert dalam session
     *
     * @return bool
     */
    public static function hasAlert(): bool
    {
        return session()->has('sweet_alert') || 
               session()->has('sweet_alert_toast') || 
               session()->has('sweet_alert_queue');
    }

    /**
     * Ambil data alert dari session
     *
     * @return array|null
     */
    public static function getAlert(): ?array
    {
        if (session()->has('sweet_alert')) {
            return session()->get('sweet_alert');
        }

        if (session()->has('sweet_alert_toast')) {
            return session()->get('sweet_alert_toast');
        }
        
        if (session()->has('sweet_alert_queue')) {
            return session()->get('sweet_alert_queue');
        }

        return null;
    }
    
    /**
     * Hapus semua data alert dari session
     *
     * @return void
     */
    public static function clear(): void
    {
        session()->forget('sweet_alert');
        session()->forget('sweet_alert_toast');
        session()->forget('sweet_alert_queue');
    }
}