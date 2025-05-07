@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Penolakan Isu</h5>
                </div>
                <div class="card-body">
                    <h5>{{ $isu->judul }}</h5>
                    <p class="text-muted">Tanggal: {{ $isu->tanggal->format('d M Y H:i') }}</p>

                    <form action="{{ route('isu.process-penolakan', $isu) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="alasan_penolakan" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('alasan_penolakan') is-invalid @enderror"
                                id="alasan_penolakan" name="alasan_penolakan" rows="5"
                                placeholder="Tuliskan alasan penolakan isu ini...">{{ old('alasan_penolakan') }}</textarea>
                            @error('alasan_penolakan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('isu.edit', $isu) }}" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-ban me-1"></i> Tolak Isu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
