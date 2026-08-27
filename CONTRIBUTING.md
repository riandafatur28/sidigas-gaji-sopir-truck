# Contributing

Terima kasih telah berkontribusi ke SIDIGAS! Berikut panduan untuk berkontribusi.

## Development Setup

```bash
# Clone & setup
git clone https://github.com/riandafatur28/backup-llm-gajisopir.git
cd distribusi-gaji-baru
composer setup

# Jalankan development
composer dev
```

## Code Style

### PHP

- **`declare(strict_types=1)`** wajib di semua file PHP
- **Type hints** wajib untuk semua parameter dan return value
- **Max 100 baris** per controller method
- **Max 250 baris** per file (service harus di-split jika lebih)
- Ikuti style existing — jangan campur aduk

### Blade

- Gunakan **shared components** (`<x-pagination>`, `<x-form-tambah>`) yang sudah ada
- Ekstrak logic ke service, bukan di blade

### Naming

| Tipe | Convention | Contoh |
|------|-----------|--------|
| Model | Singular, PascalCase | `Sopir`, `Periode` |
| Controller | PascalCase + Controller | `RitaseController` |
| Service | PascalCase + Service | `RitaseService` |
| Migration | snake_case | `create_ritases_table` |
| Route name | dot notation | `ritase.store`, `gaji.edit` |

## Testing

### Wajib

- Setiap fitur baru **wajib** punya test
- Test harus pass sebelum commit
- Gunakan SQLite in-memory (default di phpunit.xml)

### Menjalankan Test

```bash
# Semua test
php artisan test

# Test tertentu
php artisan test tests/Feature/RitaseCrudTest.php

# Specific test method
php artisan test --filter=test_store_creates_ritase
```

### Test Structure

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_auth(): void
    {
        $this->get('/sopir')->assertRedirect();
    }

    public function test_store_creates_record(): void
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        $response = $this->post('/sopir', ['nama' => 'Budi']);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('sopirs', ['nama' => 'Budi']);
    }
}
```

## Architecture Rules

### Controllers (≤100 baris)

- Hanya handle request → response
- Delegate ke service untuk business logic
- Gunakan try-catch + `report($e)` untuk error handling

### Services (split jika >250 baris)

- Satu service = satu responsibility
- Gunakan dependency injection
- Service boleh panggil service lain

### Models

- Relationship di声明 di model
- Gunakan `HasUniqueKode` trait untuk auto-generate kode
- Sync status otomatis via boot method

### Database

- Selalu gunakan migration, bukan raw SQL
- Tambah index untuk foreign key & kolom yang sering di-query
- Gunakan foreign key constraint

## Commit Message

Format:

```
<type>: <description singkat>

<deskripsi detail (opsional)>
```

Types:
- `feat` — fitur baru
- `fix` — bug fix
- `test` — menambah/mengubah test
- `docs` — dokumentasi
- `refactor` — refactor tanpa ubah behavior
- `chore` — dependency, config, dll

Contoh:
```
feat: add periode overlap validation
fix: resolve N+1 query in RitaseService
test: add delete guard tests for SopirController
docs: update README with API routes
```

## Pull Request

1. Buat branch dari `dev`: `git checkout -b feat/nama-fitur`
2. Buat perubahan
3. Jalankan test: `php artisan test`
4. Commit dengan format yang benar
5. Push: `git push origin feat/nama-fitur`
6. Buka PR ke branch `dev`

## Questions?

Buka issue di GitHub atau hubungi maintainer.
