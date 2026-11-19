@extends('layouts.app')

@section('title', 'Dokumen')
@section('heading', 'Dokumen')

@section('content')
    <div class="page-intro-row">
        <p class="page-description">Cari, unduh, dan kelola dokumen pendukung elemen akreditasi.</p>
        <div class="document-total"><span class="stat-icon icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7M8 15h8M8 18h5"/></svg></span><strong>{{ $documents->total() }}</strong><span>dokumen tersimpan</span></div>
    </div>
    <section class="panel glass documents-panel">
        <form action="{{ route('documents.index') }}" method="GET" class="search-bar">
            <div class="search-input-wrap"><span class="search-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg></span><input type="search" name="q" value="{{ $search }}" placeholder="Cari nama dokumen, standar, EP, unit, atau bab..."></div>
            <select class="unit-filter" name="pokja" aria-label="Filter berdasarkan pokja">
                <option value="">Semua pokja</option>
                <option value="Manajemen" @selected($pokja === 'Manajemen')>Pokja Manajemen</option>
                <option value="Pelayanan" @selected($pokja === 'Pelayanan')>Pokja Pelayanan</option>
            </select>
            <select class="unit-filter" name="unit_id" aria-label="Filter berdasarkan unit">
                <option value="">Semua unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected((string) $unitId === (string) $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
            <button class="button button-primary" type="submit">Cari Dokumen</button>
            @if ($search !== '' || $unitId || $pokja)<a class="button button-quiet" href="{{ route('documents.index') }}">Reset</a>@endif
        </form>
        <div class="table-wrap">
            <table class="data-table document-table">
                <colgroup>
                    <col class="col-number">
                    <col class="col-standard">
                    <col class="col-ep">
                    <col class="col-content">
                    <col class="col-unit">
                    <col class="col-description">
                    <col class="col-document">
                    <col class="col-actions">
                </colgroup>
                <thead>
                    <tr><th>No</th><th>Standar</th><th>EP</th><th>Isi EP</th><th>Unit</th><th>Keterangan</th><th>Dokumen</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($documents as $index => $document)
                        <tr>
                            <td class="row-number">{{ $documents->firstItem() + $index }}</td>
                            <td><span class="standard-tag">{{ $document->element->standard }}</span></td>
                            <td><span class="ep-tag">{{ $document->element->ep }}</span></td>
                            <td class="content-cell">{{ $document->element->content }}</td>
                            <td>
                                <span class="unit-tag">{{ $document->unitRecord?->name ?: ($document->unit ?: 'Belum diatur') }}</span>
                            </td>
                            <td class="description-cell">{{ $document->description ?: '—' }}</td>
                            <td>
                                <div class="file-name-cell">
                                    <span class="file-icon">{{ strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}</span>
                                    <div>
                                        <strong title="{{ $document->original_name }}">{{ $document->original_name }}</strong>
                                        <small>{{ number_format($document->size / 1024, 0) }} KB <span>·</span> {{ $document->created_at->format('d M Y') }}</small>
                                        @if (strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION)) === 'pdf')
                                            <button class="button button-quiet document-preview-button" type="button" data-open-document-preview data-preview-url="{{ route('documents.preview', $document) }}" data-preview-title="{{ $document->original_name }}">Preview PDF</button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td><div class="action-buttons document-actions">
                                <a class="icon-button download-button" href="{{ route('documents.download', $document) }}" title="Unduh dokumen" aria-label="Unduh dokumen"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v12m0 0 5-5m-5 5-5-5M5 20h14"/></svg></a>
                                <form action="{{ route('documents.destroy', $document) }}" method="POST" data-confirm data-confirm-title="Hapus dokumen?" data-confirm-message="Dokumen {{ $document->original_name }} akan dihapus permanen.">
                                    @csrf @method('DELETE')
                                    <button class="icon-button delete-button" type="submit" title="Hapus dokumen" aria-label="Hapus dokumen"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg></button>
                                </form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty-state table-empty"><span class="empty-icon">⌕</span><strong>{{ $search !== '' || $unitId || $pokja ? 'Dokumen tidak ditemukan' : 'Belum ada dokumen' }}</strong><p>{{ $search !== '' || $unitId || $pokja ? 'Coba ubah filter atau hapus pencarian.' : 'Unggah dokumen dari halaman Master Data.' }}</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($documents->hasPages())
            @include('shared.pagination', ['paginator' => $documents, 'label' => 'dokumen', 'ariaLabel' => 'Navigasi halaman dokumen'])
        @endif
    </section>
    <dialog class="element-dialog document-preview-dialog" id="document-preview-modal" aria-labelledby="document-preview-title">
        <div class="element-modal-card">
            <div class="modal-heading">
                <div class="modal-heading-mark upload-heading-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7"/></svg></div>
                <div><span class="eyebrow">PRATINJAU DOKUMEN</span><h2 id="document-preview-title">Preview PDF</h2></div>
                <button class="modal-close" type="button" data-close-document-preview aria-label="Tutup">×</button>
            </div>
            <iframe class="document-preview-frame" title="Pratinjau file PDF"></iframe>
            <p class="document-preview-status" role="status" aria-live="polite"></p>
        </div>
    </dialog>
@endsection
