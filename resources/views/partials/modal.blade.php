<!-- resources/views/partials/modal.blade.php -->
<div class="modal fade" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
            <h2 class="fw-bold mb-4">Tentang Media Monitoring</h2>
            
            <div class="text-start">
                @php
                    $modalContent = \App\Models\Setting::getValue('modal_content', '');
                    // Cek jika konten berisi placeholder tanggal dan ganti dengan tanggal saat ini
                    $modalContent = str_replace('{tanggal}', session('latestIsuDate', now()->translatedFormat('d F Y')), $modalContent);
                @endphp
                
                {!! $modalContent !!}
            </div>
        </div>
            <div class="modal-footer justify-content-center border-0">
                <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Mengerti</button>
            </div>
        </div>
    </div>
</div>