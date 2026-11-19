<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Document;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = [
            'Manajemen' => ['TKRS', 'KPS', 'MFK', 'PMKP', 'MRMIK', 'PPI', 'PPK', 'PROGNAS'],
            'Pelayanan' => ['AKP', 'HPK', 'PP', 'PAP', 'PAB', 'PKPO', 'KE', 'SKP'],
        ];

        foreach ($categories as $group => $codes) {
            foreach ($codes as $code) {
                Category::updateOrCreate(
                    ['code' => $code],
                    ['name' => $code, 'group_name' => $group],
                );
            }
        }

        foreach (config('units.options') as $unitName) {
            $this->linkLegacyDocumentsToUnit($unitName);
        }

        $legacyUnitNames = Document::query()
            ->whereNotNull('unit')
            ->where('unit', '!=', '')
            ->distinct()
            ->pluck('unit');

        foreach ($legacyUnitNames as $unitName) {
            $this->linkLegacyDocumentsToUnit($unitName);
        }

        $accounts = [
            [
                'name' => config('admin.name'),
                'username' => config('admin.username'),
                'password' => config('admin.password'),
                'role' => 'admin',
            ],
            [
                'name' => config('admin.user.name'),
                'username' => config('admin.user.username'),
                'password' => config('admin.user.password'),
                'role' => 'user',
            ],
        ];

        if ($accounts[0]['username'] === $accounts[1]['username']) {
            throw new RuntimeException('ADMIN_USERNAME and USER_USERNAME must be different.');
        }

        foreach ($accounts as $account) {
            if (blank($account['username']) || blank($account['password'])) {
                throw new RuntimeException('Set ADMIN_USERNAME, ADMIN_PASSWORD, USER_USERNAME, and USER_PASSWORD before seeding user accounts.');
            }

            User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'password' => $account['password'],
                    'role' => $account['role'],
                ],
            );
        }
    }

    private function linkLegacyDocumentsToUnit(string $unitName): void
    {
        $unit = Unit::firstOrCreate(['name' => $unitName]);

        Document::query()
            ->whereNull('unit_id')
            ->where('unit', $unitName)
            ->update(['unit_id' => $unit->id]);
    }
}
