<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Element;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\AccreditationElementsSeeder;
use Database\Seeders\TkrsElementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccreditationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.name' => 'Administrator Uji',
            'admin.username' => 'adminakreditasi',
            'admin.password' => 'admin-password-test',
            'admin.user.name' => 'Pengguna Uji',
            'admin.user.username' => 'akreditasi',
            'admin.user.password' => 'user-password-test',
        ]);
    }

    public function test_tkrs_seeder_creates_the_complete_element_list_idempotently(): void
    {
        $this->seed();
        $this->seed(TkrsElementSeeder::class);
        $this->seed(TkrsElementSeeder::class);

        $category = Category::where('code', 'TKRS')->firstOrFail();
        $elements = Element::where('category_id', $category->id);

        $this->assertSame(71, $elements->count());
        $this->assertDatabaseHas('elements', [
            'category_id' => $category->id,
            'standard' => 'TKRS.1',
            'ep' => 'A',
            'content' => 'Representasi Pemilik/Dewan Pengawas dipilih dan ditetapkan oleh Pemilik.',
        ]);
        $this->assertDatabaseHas('elements', [
            'category_id' => $category->id,
            'standard' => 'TKRS.15.',
            'ep' => 'G',
            'content' => 'Seluruh kegiatan penelitian merupakan bagian dari program mutu rumah sakit dan dilakukan pemantauan serta evaluasinya secara berkala sesuai ketetapan rumah sakit.',
            'sort_order' => 71,
        ]);

        $this->assertSame(
            ['standard' => 'TKRS.1', 'ep' => 'A'],
            Element::query()
                ->where('category_id', $category->id)
                ->orderBy('sort_order')
                ->firstOrFail()
                ->only(['standard', 'ep']),
        );

        $this->actingAs(User::where('username', config('admin.username'))->firstOrFail())
            ->get(route('elements.index', ['category' => $category->id]))
            ->assertOk()
            ->assertSee('Representasi Pemilik/Dewan Pengawas dipilih dan ditetapkan oleh Pemilik.');
    }

    public function test_accreditation_workbook_seeder_imports_all_chapters_in_order_idempotently(): void
    {
        $this->seed();
        $this->seed(AccreditationElementsSeeder::class);
        $this->seed(AccreditationElementsSeeder::class);

        $expectedCounts = [
            'AKP' => 67,
            'HPK' => 39,
            'KE' => 25,
            'KPS' => 81,
            'MFK' => 72,
            'MRMIK' => 51,
            'PAP' => 52,
            'PAB' => 38,
            'PPI' => 62,
            'PPK' => 23,
            'PP' => 58,
            'PROGNAS' => 10,
            'SKP' => 24,
            'TKRS' => 71,
            'PMKP' => 44,
            'PKPO' => 53,
        ];
        ksort($expectedCounts);

        $actualCounts = Category::query()
            ->withCount('elements')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [$category->code => $category->elements_count])
            ->all();

        $this->assertSame($expectedCounts, $actualCounts);
        $this->assertSame(770, Element::count());
        $this->assertDatabaseHas('elements', [
            'category_id' => Category::where('code', 'KPS')->value('id'),
            'standard' => 'KPS.1.',
            'ep' => 'A',
            'content' => 'Direktur telah menetapkan regulasi terkait Kualifikasi Pendidikan dan staf meliputi poin a - f pada gambaran umum.',
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('elements', [
            'category_id' => Category::where('code', 'MFK')->value('id'),
            'standard' => 'MFK.11.',
            'ep' => 'H',
            'content' => 'Pelatihan tentang pengelolaan fasilitas dan program keselamatan mencakup vendor, pekerja kontrak, relawan, pelajar, peserta didik, peserta pelatihan, dan lainnya, sebagaimana berlaku untuk peran dan tanggung jawab individu, dan sebagaimana ditentukan oleh rumah sakit.',
            'sort_order' => 72,
        ]);

        $kps = Category::where('code', 'KPS')->firstOrFail();
        $this->actingAs(User::where('username', config('admin.username'))->firstOrFail())
            ->get(route('elements.index', ['category' => $kps->id]))
            ->assertOk()
            ->assertViewHas('elements', fn ($elements) => $elements->first()->standard === 'KPS.1.' && $elements->first()->ep === 'A');
    }

    public function test_seeding_creates_the_requested_master_unit_list(): void
    {
        $this->seed();

        $this->assertEqualsCanonicalizing([
            'Dokter spesialis',
            'Dokter umum',
            'Keperawatan',
            'IGD',
            'IBS',
            'ICU',
            'Hemodialisa',
            'Poli Rawat Jalan',
            'Ahmad Dahlan',
            'Bangsal Baru Bawah',
            'Hamka',
            'Bangsal Baru Atas',
            'Ar Fahrudin',
            'KBRT dan Picu Nicu',
            'VK',
            'Nifas',
            'Rekam Medis',
            'Laboratorium',
            'CSSD',
            'Radiologi',
            'Fisioterapi',
            'Farmasi',
            'Gizi',
            'Sekretariat',
            'Komite',
            'Bagian Umum',
            'Rumah Tangga',
            'SDI',
            'Diklat',
            'Humas',
            'Binroh',
            'Pelayanan Medis',
            'Penunjang Medis',
            'IPSRS',
            'Elektromedik',
            'Driver',
            'Laundry',
            'Pendorong pasien',
            'Custemer Service',
            'Operator',
            'Bagian Keuangan',
            'Pemasaran',
            'PDE',
        ], Unit::query()->pluck('name')->all());
    }

    public function test_replacing_default_units_detaches_legacy_documents_without_deleting_them(): void
    {
        $category = Category::create(['code' => 'TEST', 'name' => 'Test', 'group_name' => 'Manajemen']);
        $element = Element::create([
            'category_id' => $category->id,
            'standard' => 'TEST.1',
            'ep' => 'A',
            'content' => 'Elemen untuk menguji penggantian unit.',
        ]);
        $obsoleteUnit = Unit::create(['name' => 'Administrasi']);
        $keperawatan = Unit::where('name', 'Keperawatan')->firstOrFail();
        $document = $element->documents()->create([
            'unit_id' => $obsoleteUnit->id,
            'unit' => $obsoleteUnit->name,
            'original_name' => 'bukti-lama.pdf',
            'path' => 'documents/bukti-lama.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $migration = require database_path('migrations/2026_10_28_093850_replace_default_unit_list.php');
        $migration->up();

        $this->assertDatabaseMissing('units', ['name' => 'Administrasi']);
        $this->assertDatabaseHas('units', ['id' => $keperawatan->id, 'name' => 'Keperawatan']);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'unit_id' => null,
            'unit' => null,
        ]);
    }

    public function test_guest_is_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk ke akun')
            ->assertSee('Username')
            ->assertDontSee('Email')
            ->assertSee('data-password-toggle')
            ->assertSee('Tampilkan password')
            ->assertSee('login-visual')
            ->assertSee('images/gedung-rsu-pku.png')
            ->assertSee('Mutu pelayanan,')
            ->assertSee('js/app.js');
    }

    public function test_the_seeded_administrator_can_log_in_and_view_the_dashboard(): void
    {
        $this->seed();

        $this->post('/login', [
            'username' => config('admin.username'),
            'password' => config('admin.password'),
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Bab Akreditasi')
            ->assertSee('dashboard-panel chapter-panel')
            ->assertSee('dashboard-panel recent-panel')
            ->assertSee('category-chip')
            ->assertSee('Lihat master data')
            ->assertSee('Dokumen terbaru')
            ->assertSee('Setting Unit')
            ->assertSee('Administrator')
            ->assertSee('RSU PKU MUHAMMADIYAH DELANGGU')
            ->assertSee('images/logo-delanggu.png')
            ->assertSee('images/gedung-rsu-pku.png');
    }

    public function test_only_seeded_accounts_can_log_in(): void
    {
        $this->seed();
        User::create([
            'name' => 'Pengguna Lain',
            'username' => 'otheruser',
            'password' => 'other-password',
        ]);

        $this->post('/login', [
            'username' => 'otheruser',
            'password' => 'other-password',
        ])->assertSessionHasErrors('username');
    }

    public function test_seeded_user_can_use_application_features_but_cannot_access_unit_settings(): void
    {
        $this->seed();

        $this->post('/login', [
            'username' => config('admin.user.username'),
            'password' => config('admin.user.password'),
        ])->assertRedirect('/dashboard');

        $category = Category::where('code', 'TKRS')->firstOrFail();
        $element = Element::create([
            'category_id' => $category->id,
            'standard' => 'ROLE-TEST-1',
            'ep' => 'A',
            'content' => 'Elemen untuk pengujian hak akses role.',
        ]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('User')
            ->assertDontSee('Setting Unit')
            ->assertDontSee(route('units.index'));

        $this->get(route('elements.index'))
            ->assertOk()
            ->assertSee('Hubungi administrator jika unit belum tersedia.')
            ->assertDontSee('Tambah unit')
            ->assertDontSee('Tambah Elemen')
            ->assertDontSee('<th>Aksi</th>', false)
            ->assertDontSee('data-edit-element')
            ->assertDontSee('data-confirm-title="Hapus elemen?"', false)
            ->assertSee('data-open-upload-modal')
            ->assertSee('data-open-document-list');

        $this->get(route('elements.create'))->assertForbidden();
        $this->post(route('elements.store'), [
            'category_id' => $element->category_id,
            'standard' => 'TIDAK-DIIZINKAN',
            'ep' => 'A',
            'content' => 'Tidak boleh dibuat oleh pengguna.',
        ])->assertForbidden();
        $this->get(route('elements.edit', $element))->assertForbidden();
        $this->put(route('elements.update', $element), [
            'category_id' => $element->category_id,
            'standard' => $element->standard,
            'ep' => $element->ep,
            'content' => 'Tidak boleh diubah oleh pengguna.',
        ])->assertForbidden();
        $this->delete(route('elements.destroy', $element))->assertForbidden();
        $this->assertDatabaseMissing('elements', ['standard' => 'TIDAK-DIIZINKAN']);
        $this->assertDatabaseHas('elements', [
            'id' => $element->id,
            'content' => $element->content,
        ]);

        $this->get(route('documents.index'))->assertOk();
        $this->get(route('units.index'))->assertForbidden();
        $this->post(route('units.store'), ['name' => 'Unit Tidak Diizinkan'])->assertForbidden();
        $this->assertDatabaseMissing('units', ['name' => 'Unit Tidak Diizinkan']);
    }

    public function test_master_data_can_be_created_and_documents_can_be_searched_and_downloaded(): void
    {
        $this->seed();
        Storage::fake('local');
        $admin = User::where('username', config('admin.username'))->firstOrFail();
        $category = Category::where('code', 'TKRS')->firstOrFail();

        $this->actingAs($admin)
            ->get('/elemen')
            ->assertOk()
            ->assertSee('Daftar Elemen Penilaian')
            ->assertSee('data-open-element-modal')
            ->assertSee('element-modal')
            ->assertSee('upload-modal')
            ->assertSee('Pilih semua unit');

        $this->get('/elemen/tambah')->assertOk()->assertSee('Elemen penilaian baru');
        $this->get('/dokumen')
            ->assertOk()
            ->assertSee('Cari Dokumen')
            ->assertSee('unit_id')
            ->assertSee('pokja')
            ->assertSee('Pokja Manajemen')
            ->assertSee('Pokja Pelayanan')
            ->assertSee('Semua unit')
            ->assertViewHas('documents', fn ($documents) => $documents->perPage() === 20);

        $this->post('/elemen', [
            'category_id' => $category->id,
            'standard' => 'TKRS.1',
            'ep' => 'A',
            'content' => 'Representasi pemilik dipilih dan ditetapkan.',
            'rdwos' => 'R',
        ])->assertRedirect(route('elements.index', ['category' => $category->id]));

        $this->assertDatabaseHas('elements', [
            'category_id' => $category->id,
            'standard' => 'TKRS.1',
            'ep' => 'A',
            'sort_order' => 1,
        ]);

        $this->get(route('elements.index', ['category' => $category->id]))
            ->assertOk()
            ->assertSee('success-dialog')
            ->assertSee('Elemen berhasil ditambahkan.')
            ->assertSee('Tutup notifikasi');

        $this->get(route('elements.index', ['category' => $category->id]))
            ->assertOk()
            ->assertSee('data-edit-element')
            ->assertSee('data-confirm')
            ->assertSee('<th>Aksi</th>', false)
            ->assertSee('Tambah Elemen')
            ->assertSee('data-open-upload-modal');

        /*
         * Keep assertions about the persisted data separate from the views so
         * these requests also verify that all Blade pages compile.
         */
        $element = Element::firstOrFail();
        $this->get(route('elements.edit', $element))
            ->assertOk()
            ->assertSee('Perbarui elemen');

        $this->post(route('elements.documents.store', $element), [
            'element_id' => $element->id,
            'unit_ids' => [Unit::where('name', 'Keperawatan')->value('id')],
            'description' => 'SOP pelayanan pasien rawat inap tahun 2026.',
            'document' => UploadedFile::fake()->create('bukti-tkrs.pdf', 12, 'application/pdf'),
        ])->assertRedirect(route('elements.index', ['category' => $category->id]));

        $document = $element->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->path);
        $this->assertSame('Keperawatan', $document->unit);
        $this->assertSame('SOP pelayanan pasien rawat inap tahun 2026.', $document->description);
        $this->get(route('elements.index', ['category' => $category->id]))
            ->assertOk()
            ->assertSee('data-open-document-list')
            ->assertSee('documents-modal-'.$element->id)
            ->assertSee('bukti-tkrs.pdf')
            ->assertSee('Preview PDF')
            ->assertDontSee(route('documents.download', $document));

        $this->get(route('documents.index', ['q' => 'bukti-tkrs']))
            ->assertOk()
            ->assertSee('bukti-tkrs.pdf')
            ->assertSee('data-open-document-preview')
            ->assertSee('document-preview-modal')
            ->assertSee('Keperawatan')
            ->assertSee('SOP pelayanan pasien rawat inap tahun 2026.')
            ->assertSee('<th>No</th><th>Standar</th><th>EP</th><th>Isi EP</th><th>Unit</th><th>Keterangan</th><th>Dokumen</th><th>Aksi</th>', false)
            ->assertSee('TKRS.1')
            ->assertSee('Representasi pemilik dipilih dan ditetapkan.');

        $this->get(route('documents.index', ['q' => 'Rawat Inap']))
            ->assertOk()
            ->assertSee('bukti-tkrs.pdf');

        $this->get(route('documents.index', ['pokja' => 'Manajemen']))
            ->assertOk()
            ->assertSee('bukti-tkrs.pdf');
        $this->get(route('documents.index', ['pokja' => 'Pelayanan']))
            ->assertOk()
            ->assertDontSee('bukti-tkrs.pdf');

        $keperawatan = Unit::where('name', 'Keperawatan')->firstOrFail();
        $this->get(route('documents.index', ['unit_id' => $keperawatan->id]))
            ->assertOk()
            ->assertSee('bukti-tkrs.pdf');

        $this->get(route('documents.preview', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline');

        Storage::disk('local')->put('documents/bukti-tkrs.docx', 'dokumen biasa');
        $nonPdfDocument = $element->documents()->create([
            'original_name' => 'bukti-tkrs.docx',
            'path' => 'documents/bukti-tkrs.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size' => 13,
        ]);
        $this->get(route('documents.preview', $nonPdfDocument))->assertNotFound();
        $this->get(route('documents.index', ['q' => 'bukti-tkrs.docx']))
            ->assertOk()
            ->assertDontSee('data-open-document-preview');

        $this->get(route('documents.download', $document))->assertOk();
        $this->delete(route('documents.destroy', $document))
            ->assertSessionHas('success');
        Storage::disk('local')->assertMissing($document->path);
        $this->assertFalse(Schema::hasColumn('elements', 'nilai'));
        $this->assertFalse(Schema::hasColumn('elements', 'rekomendasi'));
        $this->assertFalse(Schema::hasColumn('elements', 'nilai_rs'));
        $this->assertFalse(Schema::hasColumn('elements', 'rekomendasi_surveyor'));
    }

    public function test_element_pagination_shows_compact_controls_and_preserves_the_category_filter(): void
    {
        $this->seed();
        $admin = User::where('username', config('admin.username'))->firstOrFail();
        $category = Category::where('code', 'TKRS')->firstOrFail();

        foreach (range(1, 13) as $index) {
            Element::create([
                'category_id' => $category->id,
                'standard' => 'TEST.'.$index,
                'ep' => 'A',
                'content' => 'Elemen uji '.$index,
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('elements.index', ['category' => $category->id, 'page' => 2]));

        $response->assertOk()
            ->assertViewHas('elements', fn ($elements) => $elements->currentPage() === 2 && $elements->hasPages())
            ->assertSee('Navigasi halaman elemen')
            ->assertSee('<strong>13–13</strong>', false)
            ->assertSee('category='.$category->id.'&amp;page=1', false)
            ->assertSee('Sebelumnya')
            ->assertSee('Selanjutnya');
    }

    public function test_unit_pagination_shows_compact_controls_for_multiple_pages(): void
    {
        $this->seed();
        $admin = User::where('username', config('admin.username'))->firstOrFail();

        foreach (range(1, 16) as $index) {
            Unit::create(['name' => 'Unit Uji '.$index]);
        }

        $response = $this->actingAs($admin)
            ->get(route('units.index', ['page' => 2]));

        $response->assertOk()
            ->assertViewHas('units', fn ($units) => $units->currentPage() === 2 && $units->hasPages())
            ->assertSee('Navigasi halaman unit')
            ->assertSee('<strong>16–30</strong>', false)
            ->assertSee('Sebelumnya')
            ->assertSee('Selanjutnya');
    }

    public function test_master_data_search_filters_each_field_within_the_selected_chapter(): void
    {
        $this->seed();
        $admin = User::where('username', config('admin.username'))->firstOrFail();
        $tkrs = Category::where('code', 'TKRS')->firstOrFail();
        $kps = Category::where('code', 'KPS')->firstOrFail();

        $standardMatch = Element::create([
            'category_id' => $tkrs->id,
            'standard' => 'FIND-STANDARD-999',
            'ep' => 'A',
            'content' => 'Isi standar yang tidak cocok.',
        ]);
        $epMatch = Element::create([
            'category_id' => $tkrs->id,
            'standard' => 'TKRS.1',
            'ep' => 'FIND-EP-999',
            'content' => 'Isi EP yang tidak cocok.',
        ]);
        $contentMatch = Element::create([
            'category_id' => $tkrs->id,
            'standard' => 'TKRS.2',
            'ep' => 'B',
            'content' => 'TeksIsiUnik999 di dalam elemen penilaian.',
        ]);
        Element::create([
            'category_id' => $kps->id,
            'standard' => 'KPS.1',
            'ep' => 'A',
            'content' => 'TeksIsiUnik999 dari bab lain.',
        ]);

        $searches = [
            [
                'filters' => ['standard' => 'FIND-STANDARD-999'],
                'id' => $standardMatch->id,
            ],
            [
                'filters' => ['ep' => 'FIND-EP-999'],
                'id' => $epMatch->id,
            ],
            [
                'filters' => ['standard' => 'FIND-STANDARD-999', 'ep' => 'A', 'q' => 'Isi standar yang tidak cocok'],
                'id' => $standardMatch->id,
            ],
            [
                'filters' => ['q' => 'TeksIsiUnik999'],
                'id' => $contentMatch->id,
            ],
        ];

        foreach ($searches as $search) {
            $this->actingAs($admin)
                ->get(route('elements.index', ['category' => $tkrs->id, ...$search['filters']]))
                ->assertOk()
                ->assertViewHas('elements', fn ($elements) => $elements->total() === 1 && $elements->first()->id === $search['id'])
                ->assertSee('name="standard"', false)
                ->assertSee('name="ep"', false)
                ->assertSee('name="q"', false)
                ->assertViewHas('standards', fn ($standards) => $standards->contains('FIND-STANDARD-999'))
                ->assertViewHas('eps', fn ($eps) => $eps->contains('FIND-EP-999'))
                ->assertViewHas('standard', $search['filters']['standard'] ?? '')
                ->assertViewHas('ep', $search['filters']['ep'] ?? '')
                ->assertSee('value="FIND-STANDARD-999"', false)
                ->assertSee('value="FIND-EP-999"', false);
        }

        $this->actingAs($admin)
            ->get(route('elements.index', ['category' => $tkrs->id, 'q' => '<script>alert(1)</script>']))
            ->assertOk()
            ->assertSee('value="&lt;script&gt;alert(1)&lt;/script&gt;"', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($admin)
            ->get(route('elements.index', ['category' => $tkrs->id, 'standard' => 'NOT-IN-THIS-CHAPTER']))
            ->assertRedirect()
            ->assertSessionHasErrors('standard');

        $this->get(route('elements.index', ['category' => $tkrs->id, 'ep' => 'NOT-IN-THIS-CHAPTER']))
            ->assertRedirect()
            ->assertSessionHasErrors('ep');
    }

    public function test_unit_search_filters_unit_names_and_preserves_the_search_query_in_pagination(): void
    {
        $this->seed();
        $admin = User::where('username', config('admin.username'))->firstOrFail();

        foreach (range(1, 16) as $index) {
            Unit::create(['name' => 'UnitCari999 '.str_pad((string) $index, 2, '0', STR_PAD_LEFT)]);
        }

        Unit::create(['name' => 'Unit Tidak Cocok']);

        $response = $this->actingAs($admin)
            ->get(route('units.index', ['q' => 'UnitCari999', 'page' => 2]));

        $response->assertOk()
            ->assertViewHas('units', fn ($units) => $units->total() === 16 && $units->currentPage() === 2)
            ->assertSee('name="q"', false)
            ->assertSee('value="UnitCari999"', false)
            ->assertSee('UnitCari999 16')
            ->assertDontSee('Unit Tidak Cocok')
            ->assertSee('q=UnitCari999&amp;page=1', false);

        $this->get(route('units.index', ['q' => '<script>alert(1)</script>']))
            ->assertOk()
            ->assertSee('value="&lt;script&gt;alert(1)&lt;/script&gt;"', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_catalog_search_rejects_non_string_and_oversized_queries(): void
    {
        $this->seed();
        $admin = User::where('username', config('admin.username'))->firstOrFail();
        $category = Category::where('code', 'TKRS')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('elements.index', ['category' => $category->id, 'q' => ['invalid']]))
            ->assertRedirect()
            ->assertSessionHasErrors('q');

        $this->get(route('elements.index', ['category' => $category->id, 'standard' => ['invalid']]))
            ->assertRedirect()
            ->assertSessionHasErrors('standard');

        $this->get(route('elements.index', ['category' => $category->id, 'ep' => ['invalid']]))
            ->assertRedirect()
            ->assertSessionHasErrors('ep');

        $this->get(route('units.index', ['q' => str_repeat('a', 121)]))
            ->assertRedirect()
            ->assertSessionHasErrors('q');
    }

    public function test_element_validation_rejects_missing_required_fields(): void
    {
        $this->seed();
        $admin = User::where('username', config('admin.username'))->firstOrFail();

        $this->actingAs($admin)
            ->from(route('elements.create'))
            ->followingRedirects()
            ->post('/elemen', [
                'category_id' => 'invalid',
            ])
            ->assertOk()
            ->assertSee('validation-dialog')
            ->assertSee('Periksa kembali data')
            ->assertSee('category_id');
    }

    public function test_document_upload_requires_a_valid_unit_and_description_is_optional(): void
    {
        $this->seed();
        Storage::fake('local');
        $admin = User::where('username', config('admin.username'))->firstOrFail();
        $category = Category::where('code', 'TKRS')->firstOrFail();
        $element = Element::create([
            'category_id' => $category->id,
            'standard' => 'TKRS.1',
            'ep' => 'A',
            'content' => 'Representasi pemilik dipilih dan ditetapkan.',
        ]);

        $this->actingAs($admin)
            ->post(route('elements.documents.store', $element), [
                'element_id' => $element->id,
                'unit_ids' => [99999],
                'document' => UploadedFile::fake()->create('bukti-tkrs.pdf', 12, 'application/pdf'),
            ])
            ->assertSessionHasErrors('unit_ids.0');

        $this->post(route('elements.documents.store', $element), [
            'element_id' => $element->id,
            'unit_ids' => [Unit::where('name', 'Keperawatan')->value('id')],
            'document' => UploadedFile::fake()->create('bukti-unit.pdf', 12, 'application/pdf'),
        ])->assertRedirect(route('elements.index', ['category' => $category->id]));

        $document = $element->documents()->firstOrFail();
        $this->assertSame('Keperawatan', $document->unit);
        $this->assertNotNull($document->unit_id);
        $this->assertNull($document->description);
    }

    public function test_one_uploaded_file_is_saved_as_separate_documents_for_each_selected_unit(): void
    {
        $this->seed();
        Storage::fake('local');
        $admin = User::where('username', config('admin.username'))->firstOrFail();
        $category = Category::where('code', 'TKRS')->firstOrFail();
        $element = Element::create([
            'category_id' => $category->id,
            'standard' => 'TKRS.1',
            'ep' => 'A',
            'content' => 'Representasi pemilik dipilih dan ditetapkan.',
        ]);
        $unitIds = Unit::whereIn('name', ['Keperawatan', 'Poli Rawat Jalan'])->pluck('id')->all();
        $file = UploadedFile::fake()->createWithContent('bukti-bersama.pdf', 'isi dokumen yang sama');

        $this->actingAs($admin)
            ->post(route('elements.documents.store', $element), [
                'element_id' => $element->id,
                'unit_ids' => $unitIds,
                'description' => 'Bukti yang digunakan bersama.',
                'document' => $file,
            ])
            ->assertRedirect(route('elements.index', ['category' => $category->id]))
            ->assertSessionHas('success', 'Dokumen berhasil disimpan untuk 2 unit.');

        $documents = $element->documents()->orderBy('unit_id')->get();
        $this->assertCount(2, $documents);
        $this->assertEqualsCanonicalizing($unitIds, $documents->pluck('unit_id')->all());
        $this->assertNotSame($documents[0]->path, $documents[1]->path);

        foreach ($documents as $document) {
            Storage::disk('local')->assertExists($document->path);
            $this->assertSame('isi dokumen yang sama', Storage::disk('local')->get($document->path));
            $this->assertSame('Bukti yang digunakan bersama.', $document->description);
        }

        $this->delete(route('documents.destroy', $documents[0]))->assertSessionHas('success');
        Storage::disk('local')->assertExists($documents[1]->path);
    }

    public function test_upload_requires_at_least_one_unit_and_rejects_duplicate_unit_selection(): void
    {
        $this->seed();
        Storage::fake('local');
        $admin = User::where('username', config('admin.username'))->firstOrFail();
        $category = Category::where('code', 'TKRS')->firstOrFail();
        $element = Element::create([
            'category_id' => $category->id,
            'standard' => 'TKRS.1',
            'ep' => 'A',
            'content' => 'Test element.',
        ]);
        $unitId = Unit::where('name', 'Keperawatan')->value('id');

        $this->actingAs($admin)
            ->post(route('elements.documents.store', $element), [
                'element_id' => $element->id,
                'unit_ids' => [],
                'document' => UploadedFile::fake()->create('bukti.pdf', 12, 'application/pdf'),
            ])
            ->assertSessionHasErrors('unit_ids');

        $this->post(route('elements.documents.store', $element), [
            'element_id' => $element->id,
            'unit_ids' => [$unitId, $unitId],
            'document' => UploadedFile::fake()->create('bukti-duplikat.pdf', 12, 'application/pdf'),
        ])->assertSessionHasErrors('unit_ids.1');
    }

    public function test_units_can_be_created_edited_filtered_and_deleted_when_unused(): void
    {
        $this->seed();
        $admin = User::where('username', config('admin.username'))->firstOrFail();

        $this->actingAs($admin)
            ->get(route('units.index'))
            ->assertOk()
            ->assertSee('Pengelolaan Unit');

        $this->post(route('units.store'), ['name' => 'Unit Uji'])
            ->assertRedirect(route('units.index'))
            ->assertSessionHas('success');

        $unit = Unit::where('name', 'Unit Uji')->firstOrFail();

        $this->put(route('units.update', $unit), ['name' => 'Unit Uji Baru'])
            ->assertRedirect(route('units.index'))
            ->assertSessionHas('success');

        $unit->refresh();
        $this->assertSame('Unit Uji Baru', $unit->name);

        $this->get(route('documents.index', ['unit_id' => $unit->id]))
            ->assertOk()
            ->assertSee('Unit Uji Baru');

        $this->delete(route('units.destroy', $unit))
            ->assertRedirect(route('units.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_a_unit_with_documents_cannot_be_deleted_and_renaming_updates_documents(): void
    {
        $this->seed();
        Storage::fake('local');
        $admin = User::where('username', config('admin.username'))->firstOrFail();
        $category = Category::where('code', 'TKRS')->firstOrFail();
        $element = Element::create([
            'category_id' => $category->id,
            'standard' => 'TKRS.1',
            'ep' => 'A',
            'content' => 'Test element.',
        ]);
        $unit = Unit::where('name', 'Keperawatan')->firstOrFail();
        $element->documents()->create([
            'unit_id' => $unit->id,
            'unit' => $unit->name,
            'original_name' => 'bukti.pdf',
            'path' => 'documents/bukti.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $this->actingAs($admin)
            ->delete(route('units.destroy', $unit))
            ->assertSessionHasErrors('unit');

        $this->put(route('units.update', $unit), ['name' => 'Keperawatan Utama'])
            ->assertRedirect(route('units.index'));

        $this->assertDatabaseHas('documents', [
            'unit_id' => $unit->id,
            'unit' => 'Keperawatan Utama',
        ]);
    }
}
