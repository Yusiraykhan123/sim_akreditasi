<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Document::query()->with(['element.category', 'unitRecord']);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'pokja' => ['nullable', 'in:Manajemen,Pelayanan'],
        ]);
        $search = trim((string) $request->query('q', ''));
        $unitId = $filters['unit_id'] ?? null;
        $pokja = $filters['pokja'] ?? null;

        if ($unitId) {
            $unit = Unit::findOrFail($unitId);
            $query->where(function ($unitQuery) use ($unit) {
                $unitQuery->where('unit_id', $unit->id)
                    ->orWhere(function ($legacyQuery) use ($unit) {
                        $legacyQuery->whereNull('unit_id')->where('unit', $unit->name);
                    });
            });
        }

        if ($pokja) {
            $query->whereHas('element.category', fn ($categoryQuery) => $categoryQuery->where('group_name', $pokja));
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('original_name', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('element', function ($elementQuery) use ($search) {
                        $elementQuery->where('standard', 'like', "%{$search}%")
                            ->orWhere('ep', 'like', "%{$search}%")
                            ->orWhere('content', 'like', "%{$search}%")
                            ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('code', 'like', "%{$search}%"));
                    });
            });
        }

        return view('documents.index', [
            'documents' => $query->latest()->paginate(20)->withQueryString(),
            'categories' => Category::orderBy('id')->get(),
            'units' => Unit::orderBy('name')->get(),
            'search' => $search,
            'unitId' => $unitId,
            'pokja' => $pokja,
        ]);
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'Dokumen tidak ditemukan.');

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function preview(Document $document): BinaryFileResponse
    {
        abort_unless(strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION)) === 'pdf', 404, 'Pratinjau hanya tersedia untuk file PDF.');

        $storage = Storage::disk('local');
        abort_unless($storage->exists($document->path), 404, 'Dokumen tidak ditemukan.');

        return response()->file($storage->path($document->path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $storage = Storage::disk('local');

        if ($storage->exists($document->path) && ! $storage->delete($document->path)) {
            return back()->withErrors(['document' => "Dokumen {$document->original_name} gagal dihapus."]);
        }

        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
