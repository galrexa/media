@extends('layouts.admin')
@section('title', 'Pengaturan Aplikasi')
@section('styles')
<style>
    .settings-container {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }

    .settings-header {
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
        padding-bottom: 10px;
    }

    .settings-header h2 {
        color: #333;
        font-weight: 600;
    }

    .settings-section {
        margin-bottom: 30px;
    }

    .settings-section h3 {
        color: #444;
        font-weight: 500;
        margin-bottom: 15px;
    }

    .setting-item {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .setting-item:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }

    .setting-key {
        font-weight: 500;
        color: #495057;
        margin-bottom: 8px;
    }

    .btn-settings {
        border-radius: 5px;
        font-weight: 500;
        padding: 8px 15px;
        transition: all 0.3s ease;
    }

    .preview-modal {
        margin-top: 20px;
        padding: 15px;
        border: 1px dashed #ddd;
        border-radius: 8px;
        background-color: #f8f9fa;
    }

    .modal-preview-title {
        font-weight: 600;
        margin-bottom: 15px;
        color: #212529;
    }

    .modal-content-preview {
        margin-bottom: 10px;
    }

    .nav-tabs .nav-link {
        color: #495057;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 10px 15px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link:hover {
        color: #0d6efd;
        border-color: transparent;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background-color: transparent;
        border-bottom: 2px solid #0d6efd;
    }

    .ck-editor__editable {
        min-height: 200px;
    }
</style>
<!-- Include CKEditor 5 from CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm mb-4 p-4">
        <div class="settings-header">
            <h2><i class="bi bi-gear-fill me-2"></i>Pengaturan Aplikasi</h2>
            <!-- <p class="text-muted">Aplikasi Media Monitoring</p> -->
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="modal-tab" data-bs-toggle="tab" data-bs-target="#modal" type="button" role="tab" aria-controls="modal" aria-selected="true">
                    <i class="bi bi-window me-1"></i>Splash Screen
                </button>
            </li>
            <!-- <li class="nav-item" role="presentation">
                <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="false">
                    <i class="bi bi-sliders me-1"></i>Umum
                </button>
            </li> -->
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="settingsTabContent">
            <!-- Modal Settings Tab -->
            <div class="tab-pane fade show active" id="modal" role="tabpanel" aria-labelledby="modal-tab">
                <div class="settings-section">
                    <!-- Modal Content Setting -->
                    <div class="setting-item">
                    <p class="text-muted mb-3">
                        Isi konten modal yang akan ditampilkan kepada pengguna. Gunakan editor untuk memformat teks.<br>
                        <strong>Tip:</strong> Ketik {tanggal} di tempat di mana Anda ingin tanggal saat ini ditampilkan.
                    </p>

                        @php
                            $modalContent = $modalSettings->first() ? $modalSettings->first()->value : '';
                        @endphp

                        <form action="{{ route('settings.update', $modalSettings->first() ? $modalSettings->first()->id : 'create') }}" method="POST">
                            @csrf
                            @if($modalSettings->first())
                                @method('PUT')
                            @endif

                            <div class="mb-3">
                                <textarea id="editor" name="value" class="form-control">{!! $modalContent !!}</textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-floppy-disk"></i>Simpan
                                </button>
                            </div>
                        </form>
                        <div class="d-grid gap-2 mt-3">
                            <button class="btn btn-primary" id="previewModalBtn">
                                <i class="fas fa-pencil me-1"></i>Lihat Preview Aktual
                            </button>
                        </div>
                    </div>


                </div>
            </div>

            <!-- General Settings Tab -->
            <!-- <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="settings-section">
                    <h3>Pengaturan Umum</h3>
                    <p class="text-muted">Fitur ini akan segera tersedia.</p>
                </div>
            </div> -->
        </div>
    </div>
</div>

<!-- Preview Modal (Actual) -->
<div class="modal fade" id="welcomeModalPreview" tabindex="-1" aria-labelledby="welcomeModalPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <h2 class="fw-bold mb-4">Tentang Media Monitoring</h2>
                <div class="text-start">
                    {!! $modalContent !!}
                </div>
            </div>
            <div class="modal-footer justify-content-center border-0">
                <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Mengerti</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize CKEditor
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'blockQuote', 'insertTable', 'undo', 'redo'],
            })
            .catch(error => {
                console.error(error);
            });

        // Preview modal button event listener
        const previewModalBtn = document.getElementById('previewModalBtn');

        if (previewModalBtn) {
            previewModalBtn.addEventListener('click', function() {
                const previewModal = new bootstrap.Modal(document.getElementById('welcomeModalPreview'));
                previewModal.show();
            });
        }

        // Flash messages auto-hide
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>
@endsection
