@extends('layouts.app')

@section('title', $unit->exists ? 'Ubah Unit' : 'Tambah Unit')
@section('heading', $unit->exists ? 'Ubah Unit' : 'Tambah Unit')

@section('content')
    <div class="page-intro-row">
        <p class="page-description">Nama unit tersedia pada pilihan unggah dan filter dokumen.</p>
        <a class="button button-quiet" href="{{ route('units.index') }}">← Kembali</a>
    </div>
    <section class="panel glass form-panel unit-form-panel">
        <div class="panel-heading">
            <div><span class="eyebrow">MASTER UNIT</span><h2>{{ $unit->exists ? 'Perbarui unit' : 'Unit baru' }}</h2></div>
        </div>
        <form action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}" method="POST" class="data-form">
            @csrf
            @if ($unit->exists) @method('PUT') @endif
            <div class="field-group">
                <label for="name">Nama unit <span>*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name', $unit->name) }}" placeholder="Contoh: Rawat Inap" maxlength="120" required autofocus>
                <small class="field-hint">Nama harus unik. Perubahan nama akan diterapkan pada dokumen yang terkait.</small>
            </div>
            <div class="form-actions">
                <a class="button button-quiet" href="{{ route('units.index') }}">Batal</a>
                <button class="button button-primary" type="submit">{{ $unit->exists ? 'Simpan Perubahan' : 'Simpan Unit' }} <span>→</span></button>
            </div>
        </form>
    </section>
@endsection
