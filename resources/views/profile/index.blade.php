<!-- resources/views/profile/index.blade.php -->
@extends(
    auth()->check() &&
    (
        auth()->user()->isAdmin() ||
        auth()->user()->isEditor() ||
        auth()->user()->isVerifikator1() ||
        auth()->user()->isVerifikator2()
    )
    ? 'layouts.admin'
    : 'layouts.app'
)


@section('title', 'Profil Pengguna')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <!-- Card Profil -->
            <div class="card mb-4 profile-card">
                <div class="card-body text-center">
                    <div class="profile-avatar-container mb-3">
                        <i class="bi bi-person-circle profile-avatar-icon"></i>
                    </div>
                    <h5 class="card-title mb-1">{{ $user->name }}</h5>
                    <p class="text-muted">
                        {{ $user->getHighestRoleName() === 'admin' ? 'Administrator' : ucfirst($user->getHighestRoleName()) }}
                    </p>
                    <p class="card-text text-muted mt-2">{{ $user->email }}</p>
                </div>
            </div>

            <!-- Menu Navigasi -->
            <div class="card profile-menu-card">
                <div class="list-group list-group-flush">
                    <a href="{{ route('profile.index') }}" class="list-group-item list-group-item-action active">
                        <i class="bi bi-person me-2"></i> Informasi Profil
                    </a>
                    <!-- <a href="{{ route('profile.password') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-key me-2"></i> Ubah Password
                    </a> -->
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <!-- Card Informasi Profil -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Informasi Profil</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" value="{{ $user->username }}" disabled>
                            <div class="form-text text-muted">Username tidak dapat diubah</div>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Peran</label>
                            <input type="text" class="form-control" id="role" value="{{ $user->getHighestRoleName() === 'admin' ? 'Administrator' : ucfirst($user->getHighestRoleName()) }}" disabled>
                            <div class="form-text text-muted">Peran hanya dapat diubah oleh administrator</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Styling untuk halaman profil */
    .profile-card {
        border-radius: 10px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.3s ease;
    }

    .profile-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .profile-avatar-container {
        width: 100px;
        height: 100px;
        margin: 0 auto;
        border-radius: 50%;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 5px solid #e9ecef;
    }

    .profile-avatar-icon {
        font-size: 3.5rem;
        color: #6c757d;
    }

    .profile-menu-card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .profile-menu-card .list-group-item {
        border-left: 0;
        border-right: 0;
        padding: 0.75rem 1.25rem;
        transition: all 0.2s ease;
    }

    .profile-menu-card .list-group-item:first-child {
        border-top: 0;
    }

    .profile-menu-card .list-group-item.active {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .profile-menu-card .list-group-item:not(.active):hover {
        background-color: #f8f9fa;
    }
</style>
@endsection
