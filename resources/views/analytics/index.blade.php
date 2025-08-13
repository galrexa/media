@extends('layouts.admin')

@section('title', 'Analytics Dashboard')

@section('styles')
<style>
    :root {
        --analytics-primary: #4361ee;
        --analytics-success: #1cc88a;
        --analytics-info: #36b9cc;
        --analytics-warning: #f6c23e;
        --analytics-danger: #e74a3b;
        --analytics-secondary: #858796;
        --analytics-dark: #2c3e50;
        --analytics-light: #f8f9fc;
        --analytics-gradient: linear-gradient(135deg, var(--analytics-primary) 0%, #667eea 100%);
    }

    /* Role-specific header colors */
    .analytics-header--admin {
        background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%);
    }
    
    .analytics-header--editor {
        background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    }
    
    .analytics-header--verifikator1 {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    }
    
    .analytics-header--verifikator2 {
        background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
    }
    
    .analytics-header--viewer {
        background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
    }

    /* Enhanced Header */
    .analytics-header {
        color: white;
        padding: 2rem 1.5rem;
        margin: -1.5rem -1.5rem 2rem -1.5rem;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.15);
    }

    .analytics-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .analytics-header p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 0;
    }

    /* Access Level Indicator */
    .access-level-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.15);
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-size: 0.9rem;
        backdrop-filter: blur(10px);
        margin-top: 1rem;
    }

    .access-level-indicator i {
        font-size: 1rem;
    }

    /* Real-time Status */
    .real-time-status {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: rgba(255, 255, 255, 0.15);
        padding: 0.75rem 1rem;
        border-radius: 50px;
        backdrop-filter: blur(10px);
    }

    .real-time-indicator {
        width: 12px;
        height: 12px;
        background: var(--analytics-success);
        border-radius: 50%;
        animation: pulse-glow 2s infinite;
    }

    @keyframes pulse-glow {
        0% { 
            opacity: 1; 
            box-shadow: 0 0 0 0 rgba(28, 200, 138, 0.7);
        }
        70% { 
            opacity: 0.7; 
            box-shadow: 0 0 0 8px rgba(28, 200, 138, 0);
        }
        100% { 
            opacity: 1; 
            box-shadow: 0 0 0 0 rgba(28, 200, 138, 0);
        }
    }

    /* Enhanced Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 18px;
        padding: 2rem 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--card-color, var(--analytics-primary));
        border-radius: 18px 18px 0 0;
    }

    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
    }

    .stat-card--primary { --card-color: var(--analytics-primary); }
    .stat-card--success { --card-color: var(--analytics-success); }
    .stat-card--info { --card-color: var(--analytics-info); }
    .stat-card--danger { --card-color: var(--analytics-danger); }

    .stat-content {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        font-size: 1.8rem;
        color: white;
        background: var(--card-color, var(--analytics-primary));
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .stat-info h3 {
        font-size: 2.8rem;
        font-weight: 800;
        color: var(--analytics-dark);
        margin: 0;
        line-height: 1;
    }

    .stat-info p {
        color: var(--analytics-secondary);
        font-size: 1rem;
        font-weight: 500;
        margin: 0.25rem 0 0 0;
    }

    /* Role Statistics Section (Admin Only) */
    .role-stats-section {
        margin-bottom: 2rem;
    }

    .role-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .role-stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-left: 4px solid var(--role-color);
        transition: all 0.3s ease;
    }

    .role-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .role-stat-card--admin { --role-color: #e74a3b; }
    .role-stat-card--editor { --role-color: #f39c12; }
    .role-stat-card--verifikator1 { --role-color: #3498db; }
    .role-stat-card--verifikator2 { --role-color: #9b59b6; }
    .role-stat-card--viewer { --role-color: #1cc88a; }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        background: var(--role-color);
    }

    .role-stat-metrics {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }

    .role-metric {
        text-align: center;
    }

    .role-metric-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--analytics-dark);
        margin: 0;
    }

    .role-metric-label {
        font-size: 0.75rem;
        color: var(--analytics-secondary);
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Chart Sections */
    .chart-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .chart-container {
        background: white;
        border-radius: 18px;
        padding: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid rgba(67, 97, 238, 0.1);
    }

    .chart-title-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .chart-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: var(--analytics-dark);
        margin: 0;
    }

    .chart-icon {
        font-size: 1.5rem;
        color: var(--analytics-primary);
    }

    .chart-subtitle {
        font-size: 0.9rem;
        color: var(--analytics-secondary);
        margin: 0.25rem 0 0 2.25rem;
    }

    /* User Table Enhancements */
    .table-container {
        background: white;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .table-header {
        background: var(--analytics-gradient);
        color: white;
        padding: 1.5rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .table-header h3 {
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .analytics-table {
        width: 100%;
        border-collapse: collapse;
    }

    .analytics-table tbody tr {
        border-bottom: 1px solid #f1f3f4;
        transition: background-color 0.2s ease;
    }

    .analytics-table tbody tr:hover {
        background-color: rgba(67, 97, 238, 0.05);
    }

    .analytics-table td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: var(--analytics-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1rem;
        box-shadow: 0 2px 8px rgba(67, 97, 238, 0.2);
    }

    /* Action Buttons */
    .action-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .action-card {
        background: white;
        border-radius: 18px;
        padding: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Period Selector */
    .period-selector {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(67, 97, 238, 0.1);
    }

    .btn-period {
        border: 2px solid var(--analytics-primary);
        color: var(--analytics-primary);
        background: white;
        border-radius: 25px;
        padding: 0.6rem 1.2rem;
        margin: 0.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-period:hover {
        background: var(--analytics-primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }

    .btn-period.active {
        background: var(--analytics-primary);
        color: white;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.4);
    }

    /* Custom Date Range */
    .custom-date-range {
        background: rgba(67, 97, 238, 0.05);
        border-radius: 12px;
        padding: 1rem;
        border: 2px dashed var(--analytics-primary);
        transition: all 0.3s ease;
    }

    .custom-date-range.show {
        background: rgba(67, 97, 238, 0.1);
        border-style: solid;
    }

    /* Loading States */
    .loading-container {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        flex-direction: column;
        gap: 1rem;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(67, 97, 238, 0.1);
        border-left: 4px solid var(--analytics-primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Badge Styles */
    .badge-admin { background: #e74a3b !important; }
    .badge-editor { background: #f39c12 !important; color: #333 !important; }
    .badge-verifikator1 { background: #3498db !important; }
    .badge-verifikator2 { background: #9b59b6 !important; }
    .badge-viewer { background: #1cc88a !important; }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .chart-section {
            grid-template-columns: 1fr;
        }
        
        .role-stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .analytics-header {
            padding: 1.5rem 1rem;
            margin: -1rem -1rem 1.5rem -1rem;
        }
        
        .analytics-header h1 {
            font-size: 2rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-section {
            grid-template-columns: 1fr;
        }
        
        .role-stats-grid {
            grid-template-columns: 1fr;
        }
        
        .chart-section {
            grid-template-columns: 1fr;
        }
    }

    /* Error State */
    .error-state {
        background: #fff5f5;
        border: 1px solid #fed7d7;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        color: #c53030;
        margin: 2rem 0;
    }

    .error-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>
@endsection

@section('content')
<div class="analytics-header analytics-header--{{ $userRole }}">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="mb-1">
                <i class="fas fa-chart-line me-3"></i>Analytics Dashboard
            </h1>
            <p class="mb-0">{{ $accessInfo['description'] }}</p>
            <div class="access-level-indicator">
                <i class="fas fa-{{ $userRole === 'admin' ? 'crown' : ($accessInfo['can_see_viewer_only'] ? 'users' : 'user') }}"></i>
                <span>{{ ucfirst($userRole) }} Access Level</span>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <div class="real-time-status">
                <span class="real-time-indicator"></span>
                <span>Live Data</span>
                <small class="ms-2" id="lastUpdated">--:--:--</small>
            </div>
        </div>
    </div>
</div>

@if(isset($error) && $error)
<div class="error-state">
    <i class="fas fa-exclamation-triangle"></i>
    <h3>Error Loading Analytics Data</h3>
    <p>Terjadi kesalahan saat memuat data analytics. Data default ditampilkan.</p>
</div>
@endif

<div class="period-selector">
    <div class="row align-items-center">
        <div class="col-lg-8 col-md-7">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <label class="fw-semibold text-muted me-2">Periode:</label>
                <button class="btn btn-period {{ $period === 'today' ? 'active' : '' }}" data-period="today">Hari Ini</button>
                <button class="btn btn-period {{ $period === 'week' ? 'active' : '' }}" data-period="week">7 Hari</button>
                <button class="btn btn-period {{ $period === 'month' ? 'active' : '' }}" data-period="month">30 Hari</button>
                <button class="btn btn-period {{ $period === '3months' ? 'active' : '' }}" data-period="3months">3 Bulan</button>
                <button class="btn btn-period {{ $period === 'custom' ? 'active' : '' }}" data-period="custom">Custom Range</button>
            </div>
        </div>
        <div class="col-lg-4 col-md-5">
            <div class="custom-date-range {{ $period === 'custom' ? 'show' : '' }}" id="customDateRange" style="{{ $period === 'custom' ? 'display: block;' : 'display: none;' }}">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small">Dari:</label>
                        <input type="date" class="form-control form-control-sm" id="startDate" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Sampai:</label>
                        <input type="date" class="form-control form-control-sm" id="endDate" value="{{ request('end_date') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="stats-grid">
    @if($overview['data_source'] === 'analytics')
    <div class="stat-card stat-card--primary">
        <div class="stat-content">
            <div class="stat-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-info">
                <h3 id="totalPageViews">{{ number_format($overview['total_page_views'] ?? 0) }}</h3>
                <p>Total Page Views</p>
            </div>
        </div>
    </div>
    @endif
    
    <div class="stat-card stat-card--success">
        <div class="stat-content">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3 id="uniqueVisitors">{{ number_format($overview['unique_visitors'] ?? 0) }}</h3>
                <p>{{ $overview['data_source'] === 'analytics' ? 'Unique Visitors' : 'Active Users' }}</p>
            </div>
        </div>
    </div>

    <div class="stat-card stat-card--info">
        <div class="stat-content">
            <div class="stat-icon">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="stat-info">
                <h3 id="totalLogins">{{ number_format($overview['total_logins'] ?? 0) }}</h3>
                <p>Total Logins</p>
            </div>
        </div>
    </div>

    <div class="stat-card stat-card--danger">
        <div class="stat-content">
            <div class="stat-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="stat-info">
                <h3 id="activeNow">0</h3>
                <p>Online Now</p>
            </div>
        </div>
    </div>
</div>

@if($userRole === 'admin' && count($roleStats) > 0)
<div class="role-stats-section">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="fas fa-user-tag text-primary" style="font-size: 1.5rem;"></i>
        <h3 class="mb-0">Statistik per Role</h3>
    </div>
    
    <div class="role-stats-grid">
        @foreach($roleStats as $roleStat)
        <div class="role-stat-card role-stat-card--{{ str_replace(' ', '', $roleStat['role_label'] ?? 'unknown') }}">
            <div class="role-stat-header">
                <span class="role-badge">
                    <i class="fas fa-{{ ($roleStat['role_label'] ?? '') === 'Admin' ? 'crown' : (($roleStat['role_label'] ?? '') === 'Viewer' ? 'eye' : 'user-edit') }}"></i>
                    {{ $roleStat['role_label'] }}
                </span>
            </div>
            <div class="role-stat-metrics">
                <div class="role-metric">
                    <p class="role-metric-value">{{ number_format($roleStat['total_visits']) }}</p>
                    <p class="role-metric-label">Visits</p>
                </div>
                <div class="role-metric">
                    <p class="role-metric-value">{{ number_format($roleStat['unique_visitors']) }}</p>
                    <p class="role-metric-label">Users</p>
                </div>
                <div class="role-metric">
                    <p class="role-metric-value">{{ number_format($roleStat['total_logins']) }}</p>
                    <p class="role-metric-label">Logins</p>
                </div>
                <div class="role-metric">
                    <p class="role-metric-value">{{ number_format($roleStat['pages_accessed']) }}</p>
                    <p class="role-metric-label">Pages</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="chart-section">
    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title-group">
                <i class="fas fa-chart-area chart-icon"></i>
                <div>
                    <h3 class="chart-title">
                        {{ $overview['data_source'] === 'analytics' ? 'Trend Aktivitas Harian' : 'Trend Login Harian' }}
                    </h3>
                    <p class="chart-subtitle">
                        {{ $userRole === 'admin' ? 'Semua role pengguna' : ($accessInfo['can_see_viewer_only'] ? 'Pengguna viewer' : 'Aktivitas Anda') }}
                    </p>
                </div>
            </div>
            <div class="text-muted small">
                {{ $startDate->format('d M') }} - {{ $endDate->format('d M Y') }}
            </div>
        </div>
        <div class="chart-body">
            <canvas id="dailyTrendsChart" height="120"></canvas>
        </div>
    </div>

    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title-group">
                <i class="fas fa-business-time chart-icon"></i>
                <div>
                    <h3 class="chart-title">Peak Hours</h3>
                    <p class="chart-subtitle">Aktivitas per jam</p>
                </div>
            </div>
        </div>
        <div class="chart-body">
            <canvas id="peakHoursChart" height="240"></canvas>
        </div>
    </div>
</div>

@if(count($popularPages) > 0)
<div class="chart-container mb-4">
    <div class="chart-header">
        <div class="chart-title-group">
            <i class="fas fa-fire chart-icon"></i>
            <div>
                <h3 class="chart-title">Halaman Populer</h3>
                <p class="chart-subtitle">Halaman yang paling sering dikunjungi</p>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Halaman</th>
                    @if($userRole === 'admin')
                    <th>Role</th>
                    @endif
                    <th class="text-center">Visits</th>
                    <th class="text-center">Unique Users</th>
                    <th class="text-center">Avg Duration</th>
                    <th class="text-center">Bounce Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($popularPages->take(10) as $page)
                <tr>
                    <td>
                        <div>
                            <strong>{{ $page['page_title'] }}</strong>
                            <!-- <small class="text-muted d-block">{{ $page['page_name'] }}</small> -->
                        </div>
                    </td>
                    @if($userRole === 'admin')
                    <td>
                        <span class="badge badge-{{ str_replace(' ', '', $page['role_name']) }}">{{ ucfirst($page['role_name']) }}</span>
                    </td>
                    @endif
                    <td class="text-center">{{ number_format($page['total_visits']) }}</td>
                    <td class="text-center">{{ number_format($page['unique_visitors']) }}</td>
                    <td class="text-center">{{ number_format($page['avg_duration']) }}s</td>
                    <td class="text-center">{{ number_format($page['bounce_rate'], 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="table-container">
    <div class="table-header">
        <h3>
            <i class="fas fa-user-friends"></i>
            {{ $userRole === 'admin' ? 'Pengguna Teraktif (Semua Role)' : ($accessInfo['can_see_viewer_only'] ? 'Pengguna Viewer Teraktif' : 'Aktivitas Anda') }}
        </h3>
        <small class="opacity-75">
            {{ count($activeUsers) }} pengguna dalam periode ini
        </small>
    </div>
    <div class="table-responsive">
        <table class="analytics-table">
            <tbody id="activeUsersTable">
                @if(count($activeUsers) > 0)
                    @foreach($activeUsers as $userData)
                    <tr>
                        <td width="60">
                            <div class="user-avatar">
                                {{ strtoupper(substr($userData['user']->name ?? 'U', 0, 2)) }}
                            </div>
                        </td>
                        <td>
                            <div>
                                <div class="fw-semibold">{{ $userData['user']->name ?? 'Unknown User' }}</div>
                                <small class="text-muted">
                                    {{ $userData['user']->position ?? 'Tidak ada jabatan' }} • {{ $userData['user']->department ?? 'No Dept' }}
                                </small>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="fw-semibold text-primary">{{ number_format($userData['total_visits']) }}</div>
                            <small class="text-muted">
                                {{ $overview['data_source'] === 'analytics' ? 'page views' : 'aktivitas' }}
                            </small>
                        </td>
                        <td class="text-center">
                            <div class="fw-semibold text-info">{{ number_format($userData['total_logins']) }}</div>
                            <small class="text-muted">logins</small>
                        </td>
                        <td class="text-center">
                            <div class="fw-semibold text-success">{{ number_format($userData['pages_visited']) }}</div>
                            <small class="text-muted">pages</small>
                        </td>
                        <td class="text-end">
                            <small class="text-muted">
                                @if($userData['last_visit'])
                                    {{ $userData['last_visit']->diffForHumans() }}
                                @else
                                    Tidak pernah login
                                @endif
                            </small>
                        </td>
                    </tr>
                    @endforeach
                @else
                <tr>
                    <td colspan="6">
                        <div class="loading-container">
                            <i class="fas fa-users text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">Tidak ada data pengguna aktif untuk periode ini</p>
                        </div>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- @if($userRole === 'admin')
<div class="action-section mt-4">
    <div class="action-card">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width: 50px; height: 50px; background: var(--analytics-info); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-download text-white" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h5 class="mb-1">Export Analytics Data</h5>
                <p class="text-muted mb-0">Download laporan analytics dalam format CSV</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-info btn-sm" onclick="exportData()">
                <i class="fas fa-file-csv me-1"></i> Export Data
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="exportData('logins')">
                <i class="fas fa-sign-in-alt me-1"></i> Login History
            </button>
        </div>
    </div>

    <div class="action-card" style="border-left: 4px solid var(--analytics-warning); background: linear-gradient(135deg, #fff9e6 0%, #fff 100%);">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width: 50px; height: 50px; background: var(--analytics-warning); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-tools text-white" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h5 class="mb-1">Database Maintenance</h5>
                <p class="text-muted mb-0">Optimize dan cleanup data lama untuk performa</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                <i class="fas fa-trash-alt me-1"></i> Cleanup Data
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="optimizeDatabase()">
                <i class="fas fa-cog me-1"></i> Optimize DB
            </button>
        </div>
    </div>
</div>
@endif

@if($userRole === 'admin')
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--analytics-warning); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cleanup Analytics Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Peringatan:</strong> Tindakan ini akan menghapus data analytics dan login history secara permanen.
                </div>
                
                <form id="cleanupForm" action="{{ route('analytics.cleanup') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="beforeDate" class="form-label">Hapus data sebelum tanggal:</label>
                        <input type="date" class="form-control" id="beforeDate" name="before_date" required>
                        <div class="form-text">Data sebelum tanggal ini akan dihapus permanen</div>
                    </div>
                    
                    <div id="cleanupEstimate" class="alert alert-info" style="display: none;">
                        <div class="d-flex justify-content-between">
                            <span>Analytics Records:</span>
                            <strong id="estimateAnalytics">-</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Login History:</span>
                            <strong id="estimateLogins">-</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span><strong>Total Records:</strong></span>
                            <strong id="estimateTotal">-</strong>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="estimateCleanup()">
                    <i class="fas fa-calculator me-1"></i> Estimate
                </button>
                <button type="submit" form="cleanupForm" class="btn btn-danger" id="confirmCleanup" disabled>
                    <i class="fas fa-trash-alt me-1"></i> Confirm Cleanup
                </button>
            </div>
        </div>
    </div>
</div>
@endif -->

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    window.analyticsInstance = new AnalyticsDashboard();
    window.analyticsInstance.init();
});

class AnalyticsDashboard {
    constructor() {
        this.charts = {};
        this.currentPeriod = '{{ $period }}';
        this.customStartDate = '{{ request("start_date") }}';
        this.customEndDate = '{{ request("end_date") }}';
        this.realTimeInterval = null;
        this.userRole = '{{ $userRole }}';
    }

    init() {
        this.initializeCharts();
        this.setupEventListeners();
        this.loadInitialData();
        this.startRealTimeUpdates();
    }

    initializeCharts() {
        // Destroy existing charts if they exist
        if (this.charts.dailyTrends) this.charts.dailyTrends.destroy();
        if (this.charts.peakHours) this.charts.peakHours.destroy();

        // Daily Trends Chart
        const dailyCtx = document.getElementById('dailyTrendsChart');
        if (!dailyCtx) {
            console.error('dailyTrendsChart canvas not found');
            return;
        }

        const dailyTrendsData = @json($dailyTrends ?? []);
        let datasets = [];
        
        if (dailyTrendsData.length > 0 && dailyTrendsData[0].dashboard_admin !== undefined) {
            datasets = [
                {
                    label: 'Dashboard Viewer',
                    data: dailyTrendsData.map(item => item.dashboard_viewer),
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Dashboard Admin',
                    data: dailyTrendsData.map(item => item.dashboard_admin),
                    borderColor: '#e74a3b',
                    backgroundColor: 'rgba(231, 74, 59, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Isu Management',
                    data: dailyTrendsData.map(item => item.isu_management),
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }
            ];
        } else {
            datasets = [
                {
                    label: 'Page Views',
                    data: dailyTrendsData.map(item => item.total_visits),
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Unique Visitors',
                    data: dailyTrendsData.map(item => item.unique_visitors),
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }
            ];
        }

        this.charts.dailyTrends = new Chart(dailyCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: dailyTrendsData.map(item => item.date_label),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 12, weight: '500' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#2c3e50',
                        bodyColor: '#2c3e50',
                        borderColor: '#e9ecef',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#858796', font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { color: '#858796', font: { size: 11 } }
                    }
                }
            }
        });

        // Peak Hours Chart
        const peakCtx = document.getElementById('peakHoursChart');
        if (!peakCtx) {
            console.error('peakHoursChart canvas not found');
            return;
        }

        const peakHoursData = @json($peakHours ?? []);

        this.charts.peakHours = new Chart(peakCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: peakHoursData.map(item => item.hour_label),
                datasets: [{
                    label: 'Aktivitas',
                    data: peakHoursData.map(item => item.visits),
                    backgroundColor: (ctx) => {
                        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 400);
                        gradient.addColorStop(0, 'rgba(54, 185, 204, 0.8)');
                        gradient.addColorStop(1, 'rgba(54, 185, 204, 0.2)');
                        return gradient;
                    },
                    borderColor: '#36b9cc',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#2c3e50',
                        bodyColor: '#2c3e50',
                        borderColor: '#e9ecef',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#858796', font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { color: '#858796', font: { size: 10 } }
                    }
                }
            }
        });
    }

    setupEventListeners() {
        document.querySelectorAll('.btn-period').forEach(button => {
            button.addEventListener('click', (e) => this.handlePeriodChange(e));
        });

        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        
        if (startDate && endDate) {
            startDate.addEventListener('change', () => this.handleCustomDateChange());
            endDate.addEventListener('change', () => this.handleCustomDateChange());
        }

        @if($userRole === 'admin')
        const cleanupModal = document.getElementById('cleanupModal');
        if (cleanupModal) {
            cleanupModal.addEventListener('show.bs.modal', function () {
                document.getElementById('beforeDate').value = '';
                document.getElementById('cleanupEstimate').style.display = 'none';
                document.getElementById('confirmCleanup').disabled = true;
            });
        }
        @endif
    }

    handlePeriodChange(e) {
        const button = e.target;
        const period = button.dataset.period;
        
        document.querySelectorAll('.btn-period').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        
        this.currentPeriod = period;
        
        const customDateRange = document.getElementById('customDateRange');
        if (period === 'custom') {
            customDateRange.style.display = 'block';
            customDateRange.classList.add('show');
        } else {
            customDateRange.style.display = 'none';
            customDateRange.classList.remove('show');
            this.customStartDate = null;
            this.customEndDate = null;
            this.loadAnalyticsData();
        }
    }

    handleCustomDateChange() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (startDate && endDate) {
            this.customStartDate = startDate;
            this.customEndDate = endDate;
            this.loadAnalyticsData();
        }
    }

    loadInitialData() {
        this.loadAnalyticsData();
    }

    loadAnalyticsData() {
        this.showLoadingIndicators();
        
        const params = this.buildRequestParams();
        
        Promise.allSettled([
            this.fetchData('overview', params),
            this.fetchData('daily', params),
            this.fetchData('hourly', params),
            this.fetchData('users', params)
        ]).then(results => {
            const [overview, daily, hourly, users] = results;
            
            this.updateOverviewStats(overview.status === 'fulfilled' ? overview.value : {});
            this.updateDailyTrendsChart(daily.status === 'fulfilled' ? daily.value : []);
            this.updatePeakHoursChart(hourly.status === 'fulfilled' ? hourly.value : this.getEmptyHourlyData());
            this.updateActiveUsersTable(users.status === 'fulfilled' ? users.value : []);
            
            this.hideLoadingIndicators();
        }).catch(error => {
            console.error('Error loading analytics data:', error);
            // Handling general errors
            this.showError('Gagal memuat data analytics');
            this.hideLoadingIndicators();
        });
    }

    buildRequestParams() {
        const params = new URLSearchParams({
            period: this.currentPeriod,
            user_role: this.userRole
        });
        
        if (this.currentPeriod === 'custom' && this.customStartDate && this.customEndDate) {
            params.append('start_date', this.customStartDate);
            params.append('end_date', this.customEndDate);
        }
        
        return params.toString();
    }

    async fetchData(type, params) {
        const url = `{{ route('analytics.chart-data') }}?type=${type}&${params}`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }

    updateOverviewStats(data) {
        const updates = [
            { id: 'totalPageViews', value: data.total_page_views || 0 },
            { id: 'uniqueVisitors', value: data.unique_visitors || 0 },
            { id: 'totalLogins', value: data.total_logins || 0 }
        ];
        
        updates.forEach(update => {
            const element = document.getElementById(update.id);
            if (element) {
                this.animateValue(element, this.formatNumber(update.value));
            }
        });
    }

    updateDailyTrendsChart(data) {
        const chart = this.charts.dailyTrends;
        if (!chart) return;
        
        chart.data.labels = data.map(item => item.date_label);
        
        if (this.userRole === 'admin' && data.length > 0 && data[0].dashboard_admin !== undefined) {
            chart.data.datasets[0].data = data.map(item => item.dashboard_viewer);
            chart.data.datasets[1].data = data.map(item => item.dashboard_admin);
            chart.data.datasets[2].data = data.map(item => item.isu_management);
        } else {
            chart.data.datasets[0].data = data.map(item => item.total_visits);
            if (chart.data.datasets[1]) {
                chart.data.datasets[1].data = data.map(item => item.unique_visitors);
            }
        }
        
        chart.update('active');
    }

    updatePeakHoursChart(data) {
        const chart = this.charts.peakHours;
        if (!chart) return;
        
        chart.data.labels = data.map(item => item.hour_label);
        chart.data.datasets[0].data = data.map(item => item.visits);
        
        chart.update('active');
    }

    updateActiveUsersTable(data) {
        const tbody = document.getElementById('activeUsersTable');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (!data || data.length === 0) {
            tbody.innerHTML = this.createEmptyState(
                'Tidak ada data pengguna aktif untuk periode ini', 
                'fas fa-users', 
                6
            );
            return;
        }
        
        data.forEach((userData) => {
            const row = this.createUserRow(userData);
            tbody.appendChild(row);
        });
    }

    createUserRow(userData) {
        const row = document.createElement('tr');
        const initials = this.getUserInitials(userData.user?.name);
        
        let lastVisitText = 'Tidak pernah login';
        if (userData.last_visit) {
            const lastVisit = new Date(userData.last_visit);
            lastVisitText = this.timeAgo(lastVisit);
        }
        
        row.innerHTML = `
            <td width="60">
                <div class="user-avatar">${initials}</div>
            </td>
            <td>
                <div>
                    <div class="fw-semibold">${userData.user?.name || 'Unknown User'}</div>
                    <small class="text-muted">
                        ${userData.user?.position || 'Tidak ada jabatan'} • ${userData.user?.department || 'No Dept'}
                    </small>
                </div>
            </td>
            <td class="text-center">
                <div class="fw-semibold text-primary">${this.formatNumber(userData.total_visits)}</div>
                <small class="text-muted">
                    {{ $overview['data_source'] === 'analytics' ? 'page views' : 'aktivitas' }}
                </small>
            </td>
            <td class="text-center">
                <div class="fw-semibold text-info">${this.formatNumber(userData.total_logins)}</div>
                <small class="text-muted">logins</small>
            </td>
            <td class="text-center">
                <div class="fw-semibold text-success">${this.formatNumber(userData.pages_visited)}</div>
                <small class="text-muted">pages</small>
            </td>
            <td class="text-end">
                <small class="text-muted">${lastVisitText}</small>
            </td>
        `;
        return row;
    }

    startRealTimeUpdates() {
        this.updateRealTimeData();
        this.realTimeInterval = setInterval(() => {
            this.updateRealTimeData();
        }, 30000);
    }

    async updateRealTimeData() {
        try {
            const response = await fetch('{{ route("analytics.real-time") }}');
            const data = await response.json();
            
            const activeNowElement = document.getElementById('activeNow');
            const lastUpdatedElement = document.getElementById('lastUpdated');
            
            if (activeNowElement) {
                this.animateValue(activeNowElement, this.formatNumber(data.active_now || 0));
            }
            
            if (lastUpdatedElement) {
                const now = new Date();
                lastUpdatedElement.textContent = now.toLocaleTimeString('en-US', { hour12: false });
            }
        } catch (error) {
            console.error('Error updating real-time data:', error);
        }
    }

    // Helper methods
    createEmptyState(message, iconClass, colspan = 6) {
        return `
            <tr>
                <td colspan="${colspan}">
                    <div class="loading-container">
                        <i class="${iconClass} text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="text-muted mt-2">${message}</p>
                    </div>
                </td>
            </tr>
        `;
    }

    showLoadingIndicators() {
        // Implement loading state logic here if needed
        // For example, add a spinner to a central location
    }

    hideLoadingIndicators() {
        // Hide loading state indicators
    }

    animateValue(element, newValue) {
        element.style.transform = 'scale(1.1)';
        element.style.transition = 'transform 0.2s ease';
        
        setTimeout(() => {
            element.textContent = newValue;
            element.style.transform = 'scale(1)';
        }, 100);
    }

    getEmptyHourlyData() {
        const data = [];
        for (let i = 0; i < 24; i++) {
            data.push({
                hour_label: String(i).padStart(2, '0') + ':00',
                visits: 0
            });
        }
        return data;
    }

    formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    getUserInitials(name) {
        if (!name) return 'U';
        const parts = name.split(' ');
        if (parts.length > 1) {
            return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
        }
        return parts[0].substring(0, 2).toUpperCase();
    }

    timeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + " tahun yang lalu";
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + " bulan yang lalu";
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + " hari yang lalu";
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + " jam yang lalu";
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + " menit yang lalu";
        return "baru saja";
    }

    showError(message) {
        console.error(message);
        // Implementasi notifikasi error (misal: toast)
    }
}

// Track page duration - PERBAIKAN
let pageStartTime = Date.now();
let analyticsId = @json(session('current_analytics_id'));

console.log('Analytics ID from session:', analyticsId);
console.log('Page start time:', pageStartTime);

// Send duration when user leaves page
window.addEventListener('beforeunload', function() {
    if (analyticsId) {
        const duration = Math.max(0, Math.floor((Date.now() - pageStartTime) / 1000));
        
        console.log('Sending duration on beforeunload:', duration);
        
        // Validasi tambahan
        if (duration >= 0 && duration <= 86400) { // Max 24 jam
            navigator.sendBeacon('/api/analytics/update-duration', JSON.stringify({
                analytics_id: analyticsId,
                duration: duration
            }));
        }
    } else {
        console.log('No analytics ID available for tracking');
    }
});

// Alternative: Send duration every 30 seconds (for long sessions)
setInterval(function() {
    if (analyticsId) {
        const duration = Math.max(0, Math.floor((Date.now() - pageStartTime) / 1000));
        
        fetch('/api/analytics/update-duration', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                analytics_id: analyticsId,
                duration: duration
            })
        }).then(response => response.json())
        .then(data => {
            console.log('Periodic duration update:', data);
        })
        .catch(error => {
            console.log('Duration update failed:', error);
        });
    }
}, 30000);

// Test manual update - HAPUS SETELAH TESTING
if (analyticsId) {
    console.log('Sending test duration update...');
    fetch('/api/analytics/update-duration', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            analytics_id: analyticsId,
            duration: 30 // Test 30 seconds
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Duration update response:', data);
    })
    .catch(error => {
        console.error('Duration update error:', error);
    });
} else {
    console.log('No analytics ID for manual test');
}

// Global functions for admin actions
@if($userRole === 'admin')
function exportData(type = 'analytics') {
    const period = window.analyticsInstance.currentPeriod;
    const startDate = window.analyticsInstance.customStartDate;
    const endDate = window.analyticsInstance.customEndDate;
    
    let url = '{{ route("analytics.export") }}?type=' + type + '&format=csv&period=' + period;
    
    if (period === 'custom' && startDate && endDate) {
        url += '&start_date=' + startDate + '&end_date=' + endDate;
    }
    
    window.location.href = url;
}

function estimateCleanup() {
    const beforeDate = document.getElementById('beforeDate').value;
    if (!beforeDate) {
        alert('Pilih tanggal terlebih dahulu');
        return;
    }
    
    fetch('{{ route("analytics.estimate-cleanup") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ before_date: beforeDate })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('estimateAnalytics').textContent = data.analytics_records.toLocaleString();
            document.getElementById('estimateLogins').textContent = data.login_history_records.toLocaleString();
            document.getElementById('estimateTotal').textContent = data.total_records.toLocaleString();
            document.getElementById('cleanupEstimate').style.display = 'block';
            document.getElementById('confirmCleanup').disabled = false;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengestimasi cleanup');
    });
}

function optimizeDatabase() {
    if (!confirm('Optimasi database mungkin membutuhkan waktu beberapa menit. Lanjutkan?')) {
        return;
    }
    
    fetch('{{ route("analytics.optimize") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Database berhasil dioptimasi');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengoptimasi database');
    });
}
@endif
</script>
@endsection