<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Element;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ElementController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::withCount('elements')->orderBy('id')->get();
        $category = $request->filled('category')
            ? Category::findOrFail($request->integer('category'))
            : $categories->first();

        $standards = $category
            ? $category->elements()->orderBy('sort_order')->pluck('standard')->unique()->values()
            : collect();
        $eps = $category
            ? $category->elements()->select('ep')->distinct()->orderBy('ep')->pluck('ep')
            : collect();

        $validatedSearch = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'standard' => [
                'nullable',
                'string',
                'max:50',
                Rule::in($standards->all()),
            ],
            'ep' => [
                'nullable',
                'string',
                'max:20',
                Rule::in($eps->all()),
            ],
        ]);
        $search = trim($validatedSearch['q'] ?? '');
        $standard = $validatedSearch['standard'] ?? '';
        $ep = $validatedSearch['ep'] ?? '';

        if ($category) {
            $query = $category->elements()
                ->withCount('documents')
                ->with('documents.unitRecord');

            if ($standard !== '') {
                $query->where('standard', $standard);
            }

            if ($ep !== '') {
                $query->where('ep', $ep);
            }

            if ($search !== '') {
                $query->where(function (Builder $query) use ($search) {
                    $standardSearch = rtrim($search, '.');

                    if ($standardSearch !== '') {
                        $query->where('standard', 'like', '%'.$standardSearch.'%');
                    }

                    $query->orWhere('ep', 'like', '%'.$search.'%')
                        ->orWhere('content', 'like', '%'.$search.'%')
                        ->orWhere('rdwos', 'like', '%'.$search.'%');
                });
            }

            $elements = $query->orderBy('sort_order')->orderBy('id')->paginate(12)->withQueryString();
        } else {
            $elements = Element::query()->whereRaw('1 = 0')->paginate(12);
        }

        $units = Unit::orderBy('name')->get();

        return view('elements.index', compact('categories', 'category', 'elements', 'search', 'standard', 'ep', 'standards', 'eps', 'units'));
    }

    public function create(): View
    {
        return view('elements.form', [
            'element' => new Element,
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['sort_order'] = (int) Element::query()
            ->where('category_id', $validated['category_id'])
            ->max('sort_order') + 1;

        $element = Element::create($validated);

        return redirect()
            ->route('elements.index', ['category' => $element->category_id])
            ->with('success', 'Elemen berhasil ditambahkan.');
    }

    public function edit(Element $element): View
    {
        return view('elements.form', [
            'element' => $element,
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Element $element): RedirectResponse
    {
        $validated = $this->validatedData($request);

        if ((int) $validated['category_id'] !== (int) $element->category_id) {
            $validated['sort_order'] = (int) Element::query()
                ->where('category_id', $validated['category_id'])
                ->max('sort_order') + 1;
        }

        $element->update($validated);

        return redirect()
            ->route('elements.index', ['category' => $element->category_id])
            ->with('success', 'Elemen berhasil diperbarui.');
    }

    public function destroy(Element $element): RedirectResponse
    {
        $element->load('documents');
        $storage = Storage::disk('local');

        foreach ($element->documents as $document) {
            if ($storage->exists($document->path) && ! $storage->delete($document->path)) {
                return back()->withErrors(['document' => "Dokumen {$document->original_name} gagal dihapus."]);
            }
        }

        $categoryId = $element->category_id;
        $element->delete();

        return redirect()
            ->route('elements.index', ['category' => $categoryId])
            ->with('success', 'Elemen dan dokumen terkait berhasil dihapus.');
    }

    public function storeDocument(Request $request, Element $element): RedirectResponse
    {
        $validated = $request->validate([
            'element_id' => ['required', 'integer', 'in:'.$element->id],
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['required', 'integer', 'distinct', 'exists:units,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('document');
        $storage = Storage::disk('local');
        $path = $file->store('documents', 'local');

        if (! $path) {
            return back()->withErrors(['document' => 'Dokumen gagal disimpan. Silakan coba lagi.']);
        }

        $unitIds = array_map('intval', $validated['unit_ids']);
        $units = Unit::whereIn('id', $unitIds)->get()->keyBy('id');
        $paths = [$path];
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        foreach (array_slice($unitIds, 1) as $unitId) {
            $copyPath = 'documents/'.Str::uuid().($extension !== '' ? '.'.$extension : '');

            if (! $storage->copy($path, $copyPath)) {
                $this->deleteStoredFiles($paths);

                return back()->withErrors(['document' => 'File tidak dapat disalin untuk seluruh unit yang dipilih. Silakan coba lagi.']);
            }

            $paths[] = $copyPath;
        }

        try {
            DB::transaction(function () use ($element, $file, $unitIds, $units, $paths, $validated) {
                foreach ($unitIds as $index => $unitId) {
                    $unit = $units->get($unitId);

                    if (! $unit) {
                        throw new RuntimeException('Unit yang dipilih tidak lagi tersedia.');
                    }

                    $element->documents()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $paths[$index],
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'unit_id' => $unit->id,
                        'unit' => $unit->name,
                        'description' => $validated['description'] ?? null,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($paths);

            throw $exception;
        }

        return redirect()
            ->route('elements.index', ['category' => $element->category_id])
            ->with('success', 'Dokumen berhasil disimpan untuk '.count($unitIds).' unit.');
    }

    private function deleteStoredFiles(array $paths): void
    {
        $storage = Storage::disk('local');

        foreach ($paths as $path) {
            if ($storage->exists($path) && ! $storage->delete($path)) {
                throw new RuntimeException("File sementara {$path} gagal dibersihkan.");
            }
        }
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'standard' => ['required', 'string', 'max:50'],
            'ep' => ['required', 'string', 'max:20'],
            'content' => ['required', 'string', 'max:10000'],
            'rdwos' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
