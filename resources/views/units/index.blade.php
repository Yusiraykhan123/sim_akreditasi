@extends('layouts.app')

@section('title', 'Pengelolaan Unit')
@section('heading', 'Pengelolaan Unit')

@section('content')
    <div class="page-intro-row">
        <p class="page-description">Kelola daftar unit yang digunakan untuk mengelompokkan dokumen rumah sakit.</p>
        <a class="button button-primary" href="{{ route('units.create') }}">
            <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Tambah Unit
        </a>
    </div>

    <section class="panel glass units-panel">
        <div class="panel-heading">
            <div><span class="eyebrow">MASTER UNIT</span><h2>Daftar Unit Rumah Sakit</h2></div>
            <span class="data-summary"><span>{{ $search !== '' ? 'Hasil' : 'Total unit' }} <b>{{ $units->total() }}</b></span></span>
        </div>
        <form class="search-bar catalog-search" action="{{ route('units.index') }}" method="GET" role="search">
            <div class="search-input-wrap">
                <span class="search-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg></span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama unit..." aria-label="Cari nama unit" maxlength="120">
            </div>
            <button class="button button-primary" type="submit">Cari</button>
            @if ($search !== '')
                <a class="button button-quiet" href="{{ route('units.index') }}">Reset</a>
            @endif
        </form>
        <div class="table-wrap">
            <table class="data-table units-table">
                <colgroup>
                    <col class="unit-col-number">
                    <col class="unit-col-name">
                    <col class="unit-col-documents">
                    <col class="unit-col-actions">
                </colgroup>
                <thead><tr><th>No</th><th>Nama Unit</th><th>Dokumen</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($units as $index => $unit)
                        <tr>
                            <td class="row-number">{{ $units->firstItem() + $index }}</td>
                            <td><span class="unit-tag">{{ $unit->name }}</span></td>
                            <td class="unit-document-cell"><a class="unit-document-count" href="{{ route('documents.index', ['unit_id' => $unit->id]) }}"><strong>{{ $unit->documents_count }}</strong><span>dokumen</span></a></td>
                            <td class="unit-action-cell">
                                <div class="action-buttons">
                                    <a class="icon-button edit-button" href="{{ route('units.edit', $unit) }}" title="Ubah unit" aria-label="Ubah unit {{ $unit->name }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 10.7-10.7a2.1 2.1 0 0 0-3-3L5.5 16z"/></svg>
                                    </a>
                                    <form action="{{ route('units.destroy', $unit) }}" method="POST" data-confirm data-confirm-title="Hapus unit?" data-confirm-message="Unit {{ $unit->name }} akan dihapus. Unit yang masih dipakai dokumen tidak bisa dihapus.">
                                        @csrf @method('DELETE')
                                        <button class="icon-button delete-button" type="submit" title="Hapus unit" aria-label="Hapus unit {{ $unit->name }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state table-empty"><span class="empty-icon">⌕</span><strong>{{ $search !== '' ? 'Tidak ada unit yang cocok' : 'Belum ada unit' }}</strong><p>{{ $search !== '' ? 'Coba kata kunci lain atau reset pencarian.' : 'Tambahkan unit agar dapat digunakan sebagai filter dokumen.' }}</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($units->hasPages())
            @include('shared.pagination', ['paginator' => $units, 'label' => 'unit', 'ariaLabel' => 'Navigasi halaman unit'])
        @endif
    </section>
@endsection
