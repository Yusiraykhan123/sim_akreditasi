@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <section class="welcome-banner glass">
        <img class="welcome-building" src="{{ asset('images/gedung-rsu-pku.png') }}" alt="">
        <div class="welcome-copy">
            <span class="welcome-kicker">RSU PKU MUHAMMADIYAH DELANGGU</span>
            <h2>Selamat datang, {{ auth()->user()->name }}</h2>
            <p>Pantau data elemen dan kelengkapan dokumen akreditasi rumah sakit Anda.</p>
            <a class="button button-light" href="{{ route('elements.index') }}">Kelola Master Data <span>→</span></a>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card stat-card-blue glass">
            <div class="stat-top"><span class="stat-icon icon-blue"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21z"/><path d="M4 5.5v13A2.5 2.5 0 0 1 6.5 16H20M8 7h8M8 10h6"/></svg></span><span class="stat-label">Total Bab</span></div>
            <div class="stat-number">{{ $categoryCount }}</div>
            <div class="stat-foot">Kategori manajemen & pelayanan</div>
        </article>
        <article class="stat-card stat-card-purple glass">
            <div class="stat-top"><span class="stat-icon icon-purple"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h11a2 2 0 0 1 2 2v14H8a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3z"/><path d="M5 7H3v10a3 3 0 0 0 3 3M10 9h7M10 13h7M10 17h4"/></svg></span><span class="stat-label">Elemen Penilaian</span></div>
            <div class="stat-number">{{ number_format($elementCount) }}</div>
            <div class="stat-foot">Elemen yang tercatat</div>
        </article>
        <article class="stat-card stat-card-green glass">
            <div class="stat-top"><span class="stat-icon icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7M8 15h8M8 18h5"/></svg></span><span class="stat-label">Dokumen</span></div>
            <div class="stat-number">{{ number_format($documentCount) }}</div>
            <div class="stat-foot">Bukti dokumen terunggah</div>
        </article>
    </section>

    <section class="dashboard-grid">
        <article class="panel glass dashboard-panel chapter-panel">
            <div class="panel-heading">
                <div class="dashboard-panel-title"><span class="dashboard-panel-icon chapter-panel-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21z"/><path d="M4 5.5v13A2.5 2.5 0 0 1 6.5 16H20M8 7h8M8 10h6"/></svg></span><span><span class="eyebrow">RINGKASAN</span><h2>Bab Akreditasi</h2></span></div>
                <a class="text-link" href="{{ route('elements.index') }}">Lihat master data <span>→</span></a>
            </div>
            <div class="category-groups">
                @foreach ($categoryGroups as $group => $categories)
                    <div class="category-group {{ $group === 'Manajemen' ? 'category-group-blue' : 'category-group-purple' }}">
                        <div class="group-title"><span class="group-dot {{ $group === 'Manajemen' ? 'dot-blue' : 'dot-purple' }}"></span>{{ $group }}<span class="group-count">{{ $categories->count() }} bab</span></div>
                        <div class="category-chips">
                            @foreach ($categories as $category)
                                <a class="category-chip" href="{{ route('elements.index', ['category' => $category->id]) }}">
                                    <span class="category-chip-top"><strong>{{ $category->code }}</strong><span class="category-chip-arrow" aria-hidden="true">↗</span></span><span>{{ $category->elements_count }} elemen</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel glass dashboard-panel recent-panel">
            <div class="panel-heading">
                <div class="dashboard-panel-title"><span class="dashboard-panel-icon recent-panel-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7M8 15h8M8 18h5"/></svg></span><span><span class="eyebrow">TERBARU</span><h2>Dokumen terbaru</h2></span></div>
                <a class="text-link" href="{{ route('documents.index') }}">Semua <span>→</span></a>
            </div>
            @forelse ($recentDocuments as $document)
                <a class="recent-document" href="{{ route('documents.download', $document) }}">
                    <span class="file-icon">{{ strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}</span>
                    <span class="recent-copy"><strong>{{ $document->original_name }}</strong><small>{{ $document->unit ?: 'Unit belum diatur' }} · {{ $document->element->category->code }} / {{ $document->element->standard }} EP {{ $document->element->ep }}</small></span>
                    <span class="recent-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 17 17 7M8 7h9v9"/></svg></span>
                </a>
            @empty
                <div class="empty-state compact-empty"><span class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7M8 15h8M8 18h5"/></svg></span><strong>Belum ada dokumen</strong><p>Dokumen yang diunggah akan tampil di sini.</p></div>
            @endforelse
        </article>
    </section>
@endsection
