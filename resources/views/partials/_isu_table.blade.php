<!-- resources/views/partials/_isu_table.blade.php -->
@if($isus->isNotEmpty())
    <div class="selected-actions mb-3" style="display: none;">
        <div class="d-flex align-items-center">
            <span class="me-2 fw-medium"><span id="selected-count-{{ $tabId }}">0</span> item terpilih</span>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-tasks me-1"></i> Aksi Massal
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <!-- Hapus: Admin dan Editor -->
                    @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" id="delete-selected-{{ $tabId }}" data-action="delete">
                                <i class="fas fa-trash me-2 text-danger"></i> Hapus
                            </a>
                        </li>
                    @endif

                    <!-- Kirim: Admin, Editor ke Verifikator 1, Verifikator 1 ke Verifikator 2 -->
                    @if(Auth::user()->isAdmin())
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" id="send-to-verif1-selected-{{ $tabId }}" data-action="send-to-verif1">
                                <i class="fas fa-paper-plane me-2 text-primary"></i> Kirim ke Verifikator 1
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" id="send-to-verif2-selected-{{ $tabId }}" data-action="send-to-verif2">
                                <i class="fas fa-paper-plane me-2 text-primary"></i> Kirim ke Verifikator 2
                            </a>
                        </li>
                    @elseif(Auth::user()->isEditor())
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" id="send-to-verif1-selected-{{ $tabId }}" data-action="send-to-verif1">
                                <i class="fas fa-paper-plane me-2 text-primary"></i> Kirim ke Verifikator 1
                            </a>
                        </li>
                    @elseif(Auth::user()->hasRole('verifikator1'))
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" id="send-to-verif2-selected-{{ $tabId }}" data-action="send-to-verif2">
                                <i class="fas fa-paper-plane me-2 text-primary"></i> Kirim ke Verifikator 2
                            </a>
                        </li>
                    @endif

                    <!-- Tolak: Admin, Verifikator 1, dan Verifikator 2 -->
                    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('verifikator1') || Auth::user()->hasRole('verifikator2'))
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" id="reject-selected-{{ $tabId }}" data-action="reject" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="fas fa-ban me-2 text-danger"></i> Tolak
                            </a>
                        </li>
                    @endif

                    <!-- Publish: Admin, Verifikator 2 -->
                    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('verifikator2'))
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" id="publish-selected-{{ $tabId }}" data-action="publish">
                                <i class="fas fa-globe me-2 text-success"></i> Publikasikan
                            </a>
                        </li>
                    @endif

                    <!-- Export: Semua Role -->
                    <!-- <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="#" id="export-selected-{{ $tabId }}" data-action="export">
                            <i class="fas fa-file-export me-2 text-secondary"></i> Export
                        </a>
                    </li> -->
                </ul>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover custom-table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="40">
                            <div class="form-check">
                                <input class="form-check-input select-all" type="checkbox" id="select-all-{{ $tabId }}" aria-label="Pilih Semua">
                            </div>
                        </th>
                        <th>
                            Judul
                        </th>
                        <th width="120" class="sortable" data-sort="tanggal">
                            Tanggal
                            @if(request('sort') == 'tanggal')
                                <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                            @endif
                        </th>
                        <th width="300">Kategori</th>
                        <th width="100" class="sortable" data-sort="tone">
                            Tone
                            @if(request('sort') == 'tone')
                                <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                            @endif
                        </th>
                        <th width="100" class="sortable" data-sort="skala">
                            Skala
                            @if(request('sort') == 'skala')
                                <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                            @endif
                        </th>
                        <th width="200" class="sortable" data-sort="status_id">
                            Status
                            @if(request('sort') == 'status_id')
                                <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                            @endif
                        </th>
                        <th width="120" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($isus as $isu)
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input isu-checkbox" type="checkbox" value="{{ $isu->id }}" data-tab="{{ $tabId }}" id="isu-{{ $isu->id }}-{{ $tabId }}">
                                </div>
                            </td>
                            <td class="fw-medium text-wrap">{{ $isu->judul }}</td>
                            <td>{{ \Carbon\Carbon::parse($isu->tanggal)->format('d/m/Y') }}</td>
                            <td>
                                @if($isu->kategoris->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($isu->kategoris as $kategori)
                                            <span class="badge bg-secondary">{{ $kategori->nama }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-custom" style="background-color: {{ $isu->refTone && $isu->tone ? $isu->refTone->warna : '#d3d3d3' }}">
                                    {{ $isu->refTone && $isu->tone ? $isu->refTone->nama : '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-custom" style="background-color: {{ $isu->refSkala && $isu->skala ? $isu->refSkala->warna : '#d3d3d3' }}">
                                    {{ $isu->refSkala && $isu->skala ? $isu->refSkala->nama : '-' }}
                                </span>
                            </td>
                            <td>
                                @if($isu->status && $isu->status->nama == 'Ditolak' && $isu->alasan_penolakan)
                                    <span class="badge-custom position-relative"
                                        style="background-color: {{ $isu->status ? $isu->status->warna : '#d3d3d3' }}"
                                        data-bs-toggle="tooltip"
                                        data-bs-html="true"
                                        title="<strong>Alasan Penolakan:</strong><br>{{ htmlspecialchars($isu->alasan_penolakan) }}">
                                        {{ $isu->status ? $isu->status->nama : 'Draft' }}
                                        <i class="fas fa-info-circle ms-1"></i>
                                    </span>
                                @else
                                    <span class="badge-custom" style="background-color: {{ $isu->status ? $isu->status->warna : '#d3d3d3' }}">
                                        {{ $isu->status ? $isu->status->nama : 'Draft' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                            <div class="action-buttons">
                                @auth
                                    @if(auth()->user()->isAdmin() || $isu->canBeEditedBy(auth()->user()->getHighestRoleName()))
                                        <a href="{{ route('isu.edit', $isu) }}" class="btn-action btn-edit" title="Edit" aria-label="Edit isu">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('isu.history', $isu) }}" class="btn-action btn-log" title="Riwayat" aria-label="Lihat riwayat isu">
                                        <i class="fas fa-clock-rotate-left"></i>
                                    </a>

                                    @if(auth()->user()->isAdmin() || (auth()->user()->isEditor() && in_array($isu->status_id, [1, 7]) && $isu->created_by == auth()->id()))
                                        <a href="#" class="btn-action btn-delete" title="Hapus" aria-label="Hapus isu"
                                        data-url="{{ route('isu.destroy', $isu) }}"
                                        onclick="deleteIsu(event, this)">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    @endif
                                @endauth
                            </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination-container mt-4">
        {{ $isus->appends(request()->except(['page', $tabId == 'strategis' ? 'lainnya' : 'strategis']))->links() }}
    </div>
@else
    <div class="empty-state">
        <i class="fas fa-info-circle"></i>
        <p>{{ $emptyMessage }}</p>
        @auth
            @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                <a href="{{ route('isu.create') }}" class="btn btn-outline-primary mt-3">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Isu Baru
                </a>
            @endif
        @endauth
    </div>
@endif
<!-- Di bagian bawah file Blade -->
<script>
    function deleteIsu(event, element) {
        event.preventDefault(); // Mencegah aksi default link

        // Tampilkan konfirmasi (opsional: gunakan SweetAlert untuk UX lebih baik)
        if (!confirm('Apakah Anda yakin ingin menghapus isu ini?')) {
            return;
        }

        // Kirim permintaan DELETE menggunakan Fetch API
        fetch(element.getAttribute('data-url'), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hapus elemen dari DOM atau redirect
                element.closest('.action-buttons').parentElement.remove();
                alert(data.message || 'Isu berhasil dihapus!');
            } else {
                alert(data.message || 'Gagal menghapus isu.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus isu.');
        });
    }
</script>