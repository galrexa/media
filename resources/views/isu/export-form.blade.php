{{-- ULTRA SIMPLE VERSION - Jika masih ada conflict --}}
{{-- resources/views/isu/export-form-simple.blade.php --}}
@extends('layouts.admin')

@section('title', 'Export Laporan PDF')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-file-pdf text-danger me-2"></i>Export Laporan Isu PDF</h5>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        Export isu yang dipublikasikan pada tanggal tertentu ke dokumen PDF
                    </div>

                    {{-- Form Utama --}}
                    <form action="{{ route('isu.export.daily.pdf') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="tanggal" class="form-label">Tanggal</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="tanggal" 
                                       name="tanggal" 
                                       value="{{ date('Y-m-d') }}"
                                       max="{{ date('Y-m-d') }}"
                                       required>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-primary me-2" id="checkBtn">
                                    Cek Data
                                </button>
                                
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-download me-1"></i>
                                    Export PDF
                                </button>
                            </div>
                        </div>
                        
                        <div id="result" class="mt-3" style="display:none;"></div>
                    </form>

                    @if(isset($availableDates) && $availableDates->count() > 0)
                    <hr>
                    <h6>Tanggal Tersedia:</h6>
                    <div class="row">
                        @foreach($availableDates as $date)
                        <div class="col-md-2 col-4 mb-2">
                            <button type="button" 
                                    class="btn btn-outline-secondary btn-sm w-100 date-btn"
                                    data-date="{{ $date->date }}">
                                {{ date('d/m/y', strtotime($date->date)) }}
                                <div>
                                    <small class="d-block">({{ $date->count }})</small>
                                </div>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalInput = document.getElementById('tanggal');
    const checkBtn = document.getElementById('checkBtn');
    const result = document.getElementById('result');

    // Check button
    checkBtn.addEventListener('click', function() {
        const date = tanggalInput.value;
        if (!date) {
            alert('Pilih tanggal dulu');
            return;
        }

        result.style.display = 'block';
        result.className = 'alert alert-info';
        result.innerHTML = 'Checking...';

        fetch(`{{ route('isu.export.preview.count') }}?tanggal=${date}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.count > 0) {
                result.className = 'alert alert-success';
                result.innerHTML = `✅ ${data.count} isu tersedia untuk export`;
            } else {
                result.className = 'alert alert-warning';
                result.innerHTML = '⚠️ Tidak ada isu pada tanggal ini';
            }
        })
        .catch(e => {
            result.className = 'alert alert-danger';
            result.innerHTML = '❌ Error: ' + e.message;
        });
    });

    // Date buttons
    document.querySelectorAll('.date-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const selectedDate = this.dataset.date;
            tanggalInput.value = selectedDate;
            
            // Highlight
            document.querySelectorAll('.date-btn').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.add('btn-primary');
            this.classList.remove('btn-outline-secondary');
            
            // Auto check
            setTimeout(() => checkBtn.click(), 100);
        });
    });
});
</script>
@endsection