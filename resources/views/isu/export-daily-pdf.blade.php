{{-- resources/views/isu/export-daily-pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Eksport Isu Harian - {{ date('d F Y', strtotime($tanggal)) }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: A4 portrait;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 210mm;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #0066cc;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        .isu-container {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        
        .isu-header {
            background-color: #0066cc;
            color: white;
            padding: 10px;
            border-radius: 8px 8px 0 0;
            display: grid;
            grid-template-columns: 1fr 90px 90px;
            gap: 8px;
            align-items: center;
        }
        
        .isu-header-content {
            grid-column: 1;
            min-width: 0;
        }
        
        .isu-header h3 {
            margin: 0;
            font-size: 12px;
            font-weight: bold;
            line-height: 1.2;
            overflow-wrap: break-word;
        }
        
        .isu-meta {
            font-size: 8px;
            margin-top: 3px;
            opacity: 0.9;
            line-height: 1.1;
        }
        
        .header-tone-box, .header-skala-box {
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 3px;
            padding: 4px;
            text-align: center;
            background-color: rgba(255,255,255,0.1);
            font-size: 8px;
            line-height: 1.1;
            width: 85px;
            height: auto;
            min-height: 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .header-tone-box .field-label, .header-skala-box .field-label {
            font-size: 6px;
            margin-bottom: 2px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header-tone-positive { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .header-tone-negative { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .header-tone-neutral { background-color: #fff3cd; color: #856404; border-color: #ffeaa7; }
        
        .header-skala-nasional { background-color: #dc3545; color: white; }
        .header-skala-regional { background-color: #fd7e14; color: white; }
        .header-skala-lokal { background-color: #ffc107; color: #212529; }
        
        .isu-content {
            padding: 15px;
        }
        
        /* Tone dan Skala positioning - dipindah ke atas, setelah header */
        .tone-skala-row {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tone-box, .skala-box {
            width: 120px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px 8px;
            text-align: center;
            background-color: white;
            font-size: 10px;
        }
        
        .tone-box .field-label, .skala-box .field-label {
            font-size: 8px;
            margin-bottom: 3px;
        }
        
        .tone-positive { background-color: #d4edda; border-color: #c3e6cb; }
        .tone-negative { background-color: #f8d7da; border-color: #f5c6cb; }
        .tone-neutral { background-color: #fff3cd; border-color: #ffeaa7; }
        
        .skala-nasional { background-color: #dc3545; color: white; }
        .skala-regional { background-color: #fd7e14; color: white; }
        .skala-lokal { background-color: #ffc107; color: #212529; }
        
        .field-group {
            margin-bottom: 15px;
        }
        
        .field-label {
            font-weight: bold;
            color: #0066cc;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }
        
        .field-value {
            font-size: 11px;
            line-height: 1.5;
            text-align: justify;
        }
        
        .referensi-list {
            margin-top: 10px;
        }
        
        .referensi-item {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
        }
        
        .referensi-title {
            font-weight: bold;
            color: #495057;
            font-size: 10px;
            margin-bottom: 3px;
        }
        
        .referensi-url {
            font-size: 9px;
            color: #007bff;
            word-break: break-all;
        }
        
        .footer {
            position: fixed;
            bottom: 15mm;
            left: 15mm;
            right: 15mm;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 3px;
        }
        
        .page-number:before {
            content: "Halaman " counter(page);
        }
        
        .no-data {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>RINGKASAN ISU HARIAN</h1>
        <h2>Kantor Staf Presiden</h2>
        <h2>{{ date('d F Y', strtotime($tanggal)) }}</h2>
    </div>

    {{-- Konten Isu --}}
    @if($isus->count() > 0)
        @foreach($isus as $index => $isu)
        <div class="isu-container">
            {{-- Header Isu dengan 6 kolom --}}
            <div class="isu-header">
                <div class="isu-header-content">
                    <h3>{{ $index + 1 }}. {{ $isu->judul }}</h3>
                    <div class="isu-meta">
                        @if($isu->isu_strategis)
                            <strong>ISU STRATEGIS</strong> |
                        @else
                            <strong>ISU REGIONAL</strong> |
                        @endif
                        Dibuat: {{ $isu->created_at->format('d/m/Y H:i') }} |
                        Oleh: {{ $isu->creator->name ?? 'Unknown' }}
                    </div>
                </div>
                
                {{-- Tone di kolom 5 --}}
                <div class="header-tone-box 
                    @if($isu->refTone && strtolower($isu->refTone->nama) == 'positif') header-tone-positive 
                    @elseif($isu->refTone && strtolower($isu->refTone->nama) == 'negatif') header-tone-negative 
                    @else header-tone-neutral @endif">
                    <div class="field-label">Tone</div>
                    <strong>{{ $isu->refTone->nama ?? 'N/A' }}</strong>
                </div>
                
                {{-- Skala di kolom 6 --}}
                <div class="header-skala-box 
                    @if($isu->refSkala && strtolower($isu->refSkala->nama) == 'nasional') header-skala-nasional 
                    @elseif($isu->refSkala && strtolower($isu->refSkala->nama) == 'regional') header-skala-regional 
                    @else header-skala-lokal @endif">
                    <div class="field-label">Skala</div>
                    <strong>{{ $isu->refSkala->nama ?? 'N/A' }}</strong>
                </div>
            </div>

            {{-- Konten Isu --}}
            <div class="isu-content">
                {{-- Resume/Rangkuman --}}
                <div class="field-group">
                    <span class="field-label">Resume</span>
                    <div class="field-value">
                        {!! $isu->rangkuman ?? '<em>Tidak ada resume</em>' !!}
                    </div>
                </div>

                {{-- Narasi Positif --}}
                <div class="field-group">
                    <span class="field-label">Narasi Positif</span>
                    <div class="field-value">
                        {!! $isu->narasi_positif ?? '<em>Tidak ada narasi positif</em>' !!}
                    </div>
                </div>

                {{-- Narasi Negatif --}}
                <div class="field-group">
                    <span class="field-label">Narasi Negatif</span>
                    <div class="field-value">
                        {!! $isu->narasi_negatif ?? '<em>Tidak ada narasi negatif</em>' !!}
                    </div>
                </div>

                {{-- URL Sumber Berita --}}
                @if($isu->referensi && $isu->referensi->count() > 0)
                <div class="field-group">
                    <span class="field-label">Sumber Berita ({{ $isu->referensi->count() }} referensi)</span>
                    <div class="referensi-list">
                        @foreach($isu->referensi as $ref)
                        <div class="referensi-item">
                            <div class="referensi-title">{{ $ref->judul ?? 'Untitled' }}</div>
                            <div class="referensi-url">{{ $ref->url }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="field-group">
                    <span class="field-label">Sumber Berita</span>
                    <div class="field-value"><em>Tidak ada referensi</em></div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    @else
        <div class="no-data">
            Tidak ada isu yang dipublikasikan pada tanggal {{ date('d F Y', strtotime($tanggal)) }}
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div>Ringkasan Isu Harian KSP - Diekspor pada {{ $exported_at->format('d/m/Y H:i:s') }} WIB</div>
        <div class="page-number"></div>
    </div>
</body>
</html>