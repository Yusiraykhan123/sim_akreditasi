@extends('layouts.app')

@section('title', $element->exists ? 'Ubah Elemen' : 'Tambah Elemen')
@section('heading', $element->exists ? 'Ubah Elemen' : 'Tambah Elemen')

@section('content')
    <div class="page-intro-row">
        <p class="page-description">Isi informasi standar dan elemen penilaian yang akan dikelola.</p>
        <a class="button button-quiet" href="{{ route('elements.index', ['category' => $element->category_id]) }}">← Kembali</a>
    </div>
    <section class="panel glass form-panel">
        <div class="panel-heading">
            <div><span class="eyebrow">MASTER DATA</span><h2>{{ $element->exists ? 'Perbarui elemen' : 'Elemen penilaian baru' }}</h2></div>
            <span class="form-step">01 <i>/</i> 01</span>
        </div>
        <form action="{{ $element->exists ? route('elements.update', $element) : route('elements.store') }}" method="POST" class="data-form">
            @csrf
            @if ($element->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="field-group">
                    <label for="category_id">Bab akreditasi <span>*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Pilih bab</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $element->category_id) == $category->id)>{{ $category->group_name }} — {{ $category->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-group">
                    <label for="standard">Standar <span>*</span></label>
                    <input id="standard" name="standard" type="text" value="{{ old('standard', $element->standard) }}" placeholder="Contoh: TKRS.1" maxlength="50" required>
                </div>
                <div class="field-group">
                    <label for="ep">Elemen Penilaian (EP) <span>*</span></label>
                    <input id="ep" name="ep" type="text" value="{{ old('ep', $element->ep) }}" placeholder="Contoh: A" maxlength="20" required>
                </div>
                <div class="field-group">
                    <label for="rdwos">RDWOS</label>
                    <input id="rdwos" name="rdwos" type="text" value="{{ old('rdwos', $element->rdwos) }}" placeholder="Contoh: R atau D-W" maxlength="100">
                    <small class="field-hint">Kolom ini bersifat opsional.</small>
                </div>
                <div class="field-group field-full">
                    <label for="content">Isi EP <span>*</span></label>
                    <textarea id="content" name="content" rows="6" placeholder="Tuliskan isi elemen penilaian..." maxlength="10000" required>{{ old('content', $element->content) }}</textarea>
                </div>
            </div>
            <div class="form-actions">
                <a class="button button-quiet" href="{{ route('elements.index', ['category' => $element->category_id]) }}">Batal</a>
                <button class="button button-primary" type="submit">{{ $element->exists ? 'Simpan Perubahan' : 'Simpan Elemen' }} <span>→</span></button>
            </div>
        </form>
    </section>
@endsection
