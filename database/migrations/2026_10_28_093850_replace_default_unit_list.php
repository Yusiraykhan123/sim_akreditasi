<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const UNIT_NAMES = [
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
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            DB::table('units')->insertOrIgnore(array_map(
                fn (string $name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now],
                self::UNIT_NAMES,
            ));

            $obsoleteUnitIds = DB::table('units')
                ->whereNotIn('name', self::UNIT_NAMES)
                ->pluck('id');

            DB::table('documents')
                ->whereIn('unit_id', $obsoleteUnitIds)
                ->update(['unit_id' => null, 'unit' => null]);

            DB::table('documents')
                ->whereNull('unit_id')
                ->whereNotNull('unit')
                ->whereNotIn('unit', self::UNIT_NAMES)
                ->update(['unit' => null]);

            DB::table('units')
                ->whereNotIn('name', self::UNIT_NAMES)
                ->delete();
        });
    }

    public function down(): void
    {
        throw new LogicException('This migration detaches existing documents from obsolete units and cannot be reversed safely.');
    }
};
