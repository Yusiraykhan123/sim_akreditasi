<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ])['q'] ?? '');

        $units = Unit::withCount('documents')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('units.index', [
            'units' => $units,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('units.form', ['unit' => new Unit]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        Unit::create(['name' => trim($validated['name'])]);

        return redirect()->route('units.index')->with('success', 'Unit berhasil ditambahkan.');
    }

    public function edit(Unit $unit): View
    {
        return view('units.form', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $this->validatedData($request, $unit);
        $name = trim($validated['name']);
        $oldName = $unit->name;

        DB::transaction(function () use ($unit, $name, $oldName) {
            $unit->update(['name' => $name]);
            Document::query()
                ->where(function ($query) use ($unit, $oldName) {
                    $query->where('unit_id', $unit->id)
                        ->orWhere(function ($legacyQuery) use ($oldName) {
                            $legacyQuery->whereNull('unit_id')->where('unit', $oldName);
                        });
                })
                ->update(['unit_id' => $unit->id, 'unit' => $name]);
        });

        return redirect()->route('units.index')->with('success', 'Unit dan nama unit pada dokumen terkait berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $isUsed = Document::query()
            ->where('unit_id', $unit->id)
            ->orWhere(function ($query) use ($unit) {
                $query->whereNull('unit_id')->where('unit', $unit->name);
            })
            ->exists();

        if ($isUsed) {
            return back()->withErrors([
                'unit' => "Unit {$unit->name} masih digunakan oleh dokumen. Pindahkan dokumen ke unit lain sebelum menghapus unit ini.",
            ]);
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Unit berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Unit $unit = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('units', 'name')->ignore($unit?->id)],
        ]);
    }
}
