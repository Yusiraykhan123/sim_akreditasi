@extends('layouts.app')

@section('title', 'Master Data')
@section('heading', 'Master Data')

@section('content')
    <div class="page-intro-row">
        <p class="page-description">Kelola bab, standar, elemen penilaian, dan dokumen pendukung.</p>
        @can('manage-elements')
            <button class="button button-primary" type="button" data-open-element-modal>
                <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Tambah Elemen
            </button>
        @endcan
    </div>

    @if (! $category)
        <section class="panel glass empty-state">
            <span class="empty-icon">▤</span><h2>Bab belum tersedia</h2>
            <p>Jalankan seeder database untuk menambahkan daftar bab akreditasi.</p>
        </section>
    @else
        <section class="category-toolbar glass">
            @foreach (['Manajemen', 'Pelayanan'] as $group)
                @php($groupCategories = $categories->where('group_name', $group))
                @if ($groupCategories->isNotEmpty())
                    <div class="chapter-group">
                        <span class="chapter-group-label {{ $group === 'Manajemen' ? 'label-blue' : 'label-purple' }}">
                            <span class="group-dot {{ $group === 'Manajemen' ? 'dot-blue' : 'dot-purple' }}"></span>{{ $group }}
                            <small>{{ $groupCategories->count() }}</small>
                        </span>
                        <div class="chapter-tabs">
                            @foreach ($groupCategories as $chapter)
                                <a class="chapter-tab {{ $category->id === $chapter->id ? 'selected' : '' }}" href="{{ route('elements.index', ['category' => $chapter->id]) }}">
                                    {{ $chapter->code }}<span>{{ $chapter->elements_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </section>

        <section class="panel data-panel glass">
            <div class="data-panel-heading">
                <div class="data-title">
                    <span class="list-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11"/><path d="m3.5 6 .8.8L6 5m-2.5 7 .8.8L6 11m-2.5 7 .8.8L6 17"/></svg></span>
                    <div><h2>Daftar Elemen Penilaian <span>— BAB <b>{{ $category->code }}</b></span></h2><p>{{ $category->group_name }} · {{ $category->name }}</p></div>
                </div>
                <div class="data-summary">
                    <span>{{ $search !== '' || $standard !== '' || $ep !== '' ? 'Hasil' : 'Elemen' }} <b>{{ $search !== '' || $standard !== '' || $ep !== '' ? $elements->total() : $category->elements_count }}</b></span>
                    <span>Dokumen <b>{{ $category->elements()->withCount('documents')->get()->sum('documents_count') }}</b></span>
                </div>
            </div>
            <form class="search-bar catalog-search" action="{{ route('elements.index') }}" method="GET" role="search">
                <input type="hidden" name="category" value="{{ $category->id }}">
                <select class="catalog-search-field" name="standard" aria-label="Pilih Standar">
                    <option value="">Semua Standar</option>
                    @foreach ($standards as $standardOption)
                        <option value="{{ $standardOption }}" @selected($standard === $standardOption)>{{ $standardOption }}</option>
                    @endforeach
                </select>
                <select class="catalog-search-field" name="ep" aria-label="Pilih EP">
                    <option value="">Semua EP</option>
                    @foreach ($eps as $epOption)
                        <option value="{{ $epOption }}" @selected($ep === $epOption)>EP {{ $epOption }}</option>
                    @endforeach
                </select>
                <div class="search-input-wrap catalog-search-all">
                    <span class="search-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg></span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari standar, EP, atau isi EP..." aria-label="Cari data bab {{ $category->code }}" maxlength="120">
                </div>
                <button class="button button-primary" type="submit">Cari</button>
                @if ($search !== '' || $standard !== '' || $ep !== '')
                    <a class="button button-quiet" href="{{ route('elements.index', ['category' => $category->id]) }}">Reset</a>
                @endif
            </form>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>No</th><th>Standar</th><th>EP</th><th>Isi EP</th><th>RDWOS</th><th>Dokumen</th>@can('manage-elements')<th>Aksi</th>@endcan</tr>
                    </thead>
                    <tbody>
                        @forelse ($elements as $index => $element)
                            <tr>
                                <td class="row-number">{{ $elements->firstItem() + $index }}</td>
                                <td><span class="standard-tag">{{ $element->standard }}</span></td>
                                <td><span class="ep-tag">{{ $element->ep }}</span></td>
                                <td class="content-cell">{{ $element->content }}</td>
                                <td><span class="rdwos-tag">{{ $element->rdwos ?: '—' }}</span></td>
                                <td>
                                    <div class="document-cell">
                                        <span class="document-count">{{ $element->documents_count }} file</span>
                                        <button class="upload-button detail-button" type="button" title="Lihat daftar dokumen" data-open-document-list data-dialog-id="documents-modal-{{ $element->id }}">
                                            Lihat detail
                                        </button>
                                        <button class="upload-button" type="button" title="Unggah dokumen" data-open-upload-modal data-element-id="{{ $element->id }}" data-element-label="{{ $element->standard }} · EP {{ $element->ep }}">
                                            <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/></svg> Unggah
                                        </button>
                                    </div>
                                </td>
                                @can('manage-elements')
                                    <td>
                                        <div class="action-buttons">
                                            <button class="icon-button edit-button" type="button" title="Ubah elemen" aria-label="Ubah {{ $element->standard }} EP {{ $element->ep }}"
                                                data-edit-element
                                                data-action="{{ route('elements.update', $element) }}"
                                                data-category="{{ $element->category_id }}"
                                                data-standard="{{ $element->standard }}"
                                                data-ep="{{ $element->ep }}"
                                                data-rdwos="{{ $element->rdwos }}"
                                                data-content="{{ $element->content }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 10.7-10.7a2.1 2.1 0 0 0-3-3L5.5 16z"/></svg>
                                            </button>
                                            <form action="{{ route('elements.destroy', $element) }}" method="POST" data-confirm data-confirm-title="Hapus elemen?" data-confirm-message="Elemen {{ $element->standard }} EP {{ $element->ep }} dan dokumen yang terkait akan dihapus permanen.">
                                                @csrf @method('DELETE')
                                                <button class="icon-button delete-button" type="submit" title="Hapus elemen" aria-label="Hapus elemen"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg></button>
                                            </form>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr><td colspan="{{ auth()->user()->can('manage-elements') ? 7 : 6 }}"><div class="empty-state table-empty"><span class="empty-icon">▤</span><strong>{{ $search !== '' || $standard !== '' || $ep !== '' ? 'Tidak ada elemen yang cocok' : 'Belum ada elemen di bab ini' }}</strong><p>{{ $search !== '' || $standard !== '' || $ep !== '' ? 'Coba kata kunci lain atau reset pencarian.' : 'Tambahkan elemen penilaian pertama untuk memulai.' }}</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @foreach ($elements as $element)
                <dialog class="element-dialog document-detail-dialog" id="documents-modal-{{ $element->id }}" aria-labelledby="documents-title-{{ $element->id }}">
                    <div class="element-modal-card">
                        <div class="modal-heading">
                            <div class="modal-heading-mark upload-heading-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7M8 15h8M8 18h6"/></svg></div>
                            <div><span class="eyebrow">DOKUMEN PENDUKUNG</span><h2 id="documents-title-{{ $element->id }}">Daftar File</h2><p>{{ $element->standard }} · EP {{ $element->ep }}</p></div>
                            <button class="modal-close" type="button" data-close-document-list aria-label="Tutup">×</button>
                        </div>
                        @if ($element->documents->isEmpty())
                            <div class="empty-state table-empty document-list-empty"><strong>Belum ada dokumen</strong><p>Unggah dokumen untuk elemen ini terlebih dahulu.</p></div>
                        @else
                            <div class="document-list">
                                @foreach ($element->documents as $document)
                                    @php($isPdf = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION)) === 'pdf')
                                    <article class="document-list-item">
                                        <span class="file-icon">{{ strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}</span>
                                        <div class="document-list-copy">
                                            <strong title="{{ $document->original_name }}">{{ $document->original_name }}</strong>
                                            <small>{{ $document->unitRecord?->name ?: ($document->unit ?: 'Belum diatur') }} · {{ number_format($document->size / 1024, 0) }} KB</small>
                                            @if ($document->description)
                                                <p>{{ $document->description }}</p>
                                            @endif
                                        </div>
                                        <div class="document-list-actions">
                                            @if ($isPdf)
                                                <button class="button button-primary" type="button" data-open-document-preview data-preview-url="{{ route('documents.preview', $document) }}" data-preview-title="{{ $document->original_name }}">Preview PDF</button>
                                            @endif
                                            <form action="{{ route('documents.destroy', $document) }}" method="POST" data-confirm data-confirm-title="Hapus dokumen?" data-confirm-message="Dokumen {{ $document->original_name }} akan dihapus permanen.">
                                                @csrf @method('DELETE')
                                                <button class="icon-button delete-button" type="submit" title="Hapus dokumen" aria-label="Hapus {{ $document->original_name }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg></button>
                                            </form>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </dialog>
            @endforeach
            @if ($elements->hasPages())
                @include('shared.pagination', ['paginator' => $elements, 'label' => 'elemen', 'ariaLabel' => 'Navigasi halaman elemen'])
            @endif
        </section>

        @can('manage-elements')
            <dialog class="element-dialog" id="element-modal" @if ($errors->any() && old('standard')) open @endif>
                <form action="{{ old('_element_id') ? route('elements.update', old('_element_id')) : route('elements.store') }}" method="POST" class="element-modal-card" id="element-form"
                    data-create-action="{{ route('elements.store') }}" data-update-template="{{ url('/elemen/__ID__') }}" data-category="{{ $category->id }}">
                    @csrf
                    <input type="hidden" name="_method" id="element-method" value="{{ old('_method', 'POST') }}">
                    <input type="hidden" name="_element_id" id="element-id" value="{{ old('_element_id') }}">
                    <div class="modal-heading">
                        <div class="modal-heading-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></div>
                        <div><span class="eyebrow">MASTER DATA</span><h2 id="element-modal-title">Tambah Elemen</h2><p>Lengkapi informasi elemen penilaian berikut.</p></div>
                        <button class="modal-close" type="button" data-close-element-modal aria-label="Tutup">×</button>
                    </div>
                    <div class="form-grid">
                        <div class="field-group">
                            <label for="modal-category-id">Bab akreditasi <span>*</span></label>
                            <select id="modal-category-id" name="category_id" required>
                                <option value="">Pilih bab</option>
                                @foreach ($categories as $chapter)
                                    <option value="{{ $chapter->id }}" @selected(old('category_id', $category->id) == $chapter->id)>{{ $chapter->group_name }} — {{ $chapter->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="modal-standard">Standar <span>*</span></label>
                            <input id="modal-standard" name="standard" type="text" value="{{ old('standard') }}" placeholder="Contoh: TKRS.1" maxlength="50" required>
                        </div>
                        <div class="field-group">
                            <label for="modal-ep">Elemen Penilaian (EP) <span>*</span></label>
                            <input id="modal-ep" name="ep" type="text" value="{{ old('ep') }}" placeholder="Contoh: A" maxlength="20" required>
                        </div>
                        <div class="field-group">
                            <label for="modal-rdwos">RDWOS</label>
                            <input id="modal-rdwos" name="rdwos" type="text" value="{{ old('rdwos') }}" placeholder="Contoh: R atau D-W" maxlength="100">
                            <small class="field-hint">Kolom ini bersifat opsional.</small>
                        </div>
                        <div class="field-group field-full">
                            <label for="modal-content">Isi EP <span>*</span></label>
                            <textarea id="modal-content" name="content" rows="5" placeholder="Tuliskan isi elemen penilaian..." maxlength="10000" required>{{ old('content') }}</textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="button button-quiet" type="button" data-close-element-modal>Batal</button>
                        <button class="button button-primary" type="submit"><svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><span id="element-submit-label">Simpan Elemen</span></button>
                    </div>
                </form>
            </dialog>
        @endcan
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
        <dialog class="element-dialog upload-dialog" id="upload-modal" @if ($errors->any() && old('element_id')) open @endif>
            <form action="{{ old('element_id') ? route('elements.documents.store', old('element_id')) : route('elements.documents.store', $elements->first()?->id ?? 0) }}"
                method="POST" enctype="multipart/form-data" class="element-modal-card" id="upload-form"
                data-upload-template="{{ url('/elemen/__ID__/dokumen') }}">
                @csrf
                <input type="hidden" name="element_id" id="upload-element-id" value="{{ old('element_id') }}">
                <div class="modal-heading">
                    <div class="modal-heading-mark upload-heading-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/></svg></div>
                    <div><span class="eyebrow">DOKUMEN PENDUKUNG</span><h2>Unggah Dokumen</h2><p id="upload-element-label">Pilih unit dan lengkapi keterangan dokumen.</p></div>
                    <button class="modal-close" type="button" data-close-upload-modal aria-label="Tutup">×</button>
                </div>
                <div class="upload-modal-fields">
                    <div class="field-group">
                        <span class="field-label">Unit tujuan <span>*</span></span>
                        <details class="unit-picker" id="upload-unit-picker">
                            <summary class="unit-picker-trigger">
                                <span class="unit-picker-trigger-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20v-1a7 7 0 0 1 14 0v1"/></svg></span>
                                <span class="unit-picker-summary" data-unit-summary>Pilih satu atau beberapa unit</span>
                                <span class="unit-picker-chevron"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></span>
                            </summary>
                            <div class="unit-picker-menu">
                                <label class="unit-option unit-select-all">
                                    <input type="checkbox" data-unit-select-all>
                                    <span class="unit-checkbox-mark"></span>
                                    <span><strong>Pilih semua unit</strong><small>Gunakan file yang sama untuk semua unit</small></span>
                                </label>
                                <div class="unit-picker-divider"></div>
                                <div class="unit-option-list">
                                    @foreach ($units as $unit)
                                        <label class="unit-option">
                                            <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}" @checked(in_array($unit->id, (array) old('unit_ids', []))) data-unit-option>
                                            <span class="unit-checkbox-mark"></span>
                                            <span class="unit-option-name">{{ $unit->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                        <small class="field-hint">
                            Pilih satu unit, beberapa unit, atau semua unit.
                            @can('manage-units')
                                <a class="text-link" href="{{ route('units.create') }}">Tambah unit</a>
                            @else
                                Hubungi administrator jika unit belum tersedia.
                            @endcan
                        </small>
                        @error('unit_ids')<small class="field-error">{{ $message }}</small>@enderror
                        @error('unit_ids.*')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field-group">
                        <label for="upload-description">Keterangan</label>
                        <textarea id="upload-description" name="description" rows="3" maxlength="2000" placeholder="Contoh: SOP pelayanan pasien rawat inap tahun 2026">{{ old('description') }}</textarea>
                        <small class="field-hint">Opsional, maksimal 2.000 karakter.</small>
                    </div>
                    <div class="field-group">
                        <label for="upload-document">Pilih file <span>*</span></label>
                        <input id="upload-document" class="file-input" type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png" required data-upload-input>
                        <small class="field-hint">PDF, Word, Excel, PowerPoint, atau gambar. Maksimal 10 MB.</small>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="button button-quiet" type="button" data-close-upload-modal>Batal</button>
                    <button class="button button-primary" type="submit"><svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/></svg> Unggah Dokumen</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
