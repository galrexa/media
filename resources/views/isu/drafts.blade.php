<!-- resources/views/isu/drafts.blade.php -->
@extends('layouts.admin')

@section('title', 'Draft Isu')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Draft Isu</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('isu.index') }}">Isu</a></li>
        <li class="breadcrumb-item active">Draft</li>
    </ol>


    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Daftar Draft Isu
            </div>
            <div>
                <a href="{{ route('isu.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Baru
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($drafts->isEmpty())
                <div class="alert alert-info">
                    Tidak ada draft isu. <a href="{{ route('isu.create') }}">Buat isu baru</a>.
                </div>
            @else
                <div class="table-responsive">
                    <table id="drafts-table" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Judul</th>
                                <th>Status</th>
                                <th>Skala</th>
                                <th>Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($drafts as $draft)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($draft->tanggal)->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('isu.showDraft', $draft->id) }}">
                                            {{ Str::limit($draft->judul, 50) }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($draft->status)
                                            <span class="badge" style="background-color: {{ $draft->status->warna }}">
                                                {{ $draft->status->nama }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Tidak ada status</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($draft->skalaRef)
                                            <span class="badge" style="background-color: {{ $draft->skalaRef->warna }}">
                                                {{ $draft->skalaRef->nama }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($draft->kategoris && $draft->kategoris->count() > 0)
                                            @foreach($draft->kategoris as $kategori)
                                                <span class="badge bg-info">{{ $kategori->nama }}</span>
                                            @endforeach
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('isu.showDraft', $draft->id) }}" class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if($draft->status && $draft->status->kode == 'draft')
                                                <a href="{{ route('isu.editDraft', $draft->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <form action="{{ route('isu.submitForVerification', $draft->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" title="Kirim untuk Verifikasi"
                                                            onclick="return confirm('Apakah Anda yakin ingin mengirim isu ini untuk diverifikasi?')">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                </form>

                                                <form action="{{ route('isu.destroyDraft', $draft->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"
                                                            onclick="return confirm('Apakah Anda yakin ingin menghapus draft ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @elseif($draft->status && in_array($draft->status->kode, ['revisi_editor', 'revisi_ke_v1']))
                                                <a href="{{ route('isu.editDraft', $draft->id) }}" class="btn btn-warning btn-sm" title="Revisi">
                                                    <i class="fas fa-edit"></i> Revisi
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $drafts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#drafts-table').DataTable({
            paging: false,
            searching: true,
            ordering: true,
            info: false,
            responsive: true
        });
    });
</script>
@endsection
