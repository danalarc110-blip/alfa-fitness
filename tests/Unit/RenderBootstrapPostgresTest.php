<?php

namespace Tests\Unit;

use App\Support\RenderBootstrap;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RenderBootstrapPostgresTest extends TestCase
{
    private ?PDO $pdo = null;

    private ?string $schema = null;

    protected function setUp(): void
    {
        parent::setUp();
        $dsn = getenv('ALPHA_TEST_PG_DSN');
        if (! $dsn) {
            $this->markTestSkipped('Integración PostgreSQL requiere ALPHA_TEST_PG_DSN y credenciales privadas.');
        }
        try {
            $this->pdo = new PDO($dsn, getenv('ALPHA_TEST_PG_USER') ?: null, getenv('ALPHA_TEST_PG_PASSWORD') ?: null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
        } catch (\Throwable) {
            $this->markTestSkipped('La conexión PostgreSQL de prueba no está disponible.');
        }
        $this->schema = 'alpha_test_'.bin2hex(random_bytes(8));
        $this->pdo->exec('CREATE SCHEMA "'.$this->schema.'"');
        $this->pdo->exec('SET search_path TO "'.$this->schema.'"');
        $this->pdo->exec('CREATE TABLE users (id bigserial PRIMARY KEY, password text NOT NULL, activo boolean NOT NULL, created_at timestamp NOT NULL)');
        $this->pdo->exec('CREATE TABLE clientes (id bigserial PRIMARY KEY, user_id bigint REFERENCES users(id), activo boolean NOT NULL)');
        $this->pdo->exec('CREATE TABLE rutinas (id bigserial PRIMARY KEY, cliente_id bigint REFERENCES clientes(id), asignado_por_id bigint REFERENCES users(id))');
    }

    protected function tearDown(): void
    {
        if ($this->pdo && $this->schema && preg_match('/^alpha_test_[a-f0-9]{16}$/', $this->schema)) {
            $this->pdo->exec('DROP SCHEMA "'.$this->schema.'" CASCADE');
        }
        $this->pdo = null;
        parent::tearDown();
    }

    private function tables(): array
    {
        return [
            ['name' => 'rutinas', 'columns' => ['id', 'cliente_id', 'asignado_por_id'], 'rows' => [[401, 301, 201]], 'depends_on' => ['users', 'clientes']],
            ['name' => 'clientes', 'columns' => ['id', 'user_id', 'activo'], 'rows' => [[301, 201, 0]], 'depends_on' => ['users']],
            ['name' => 'users', 'columns' => ['id', 'password', 'activo', 'created_at'], 'rows' => [[201, 'existing-password-hash', 1, '2026-10-28 12:34:56']], 'depends_on' => []],
        ];
    }

    public function test_preserves_ids_password_hashes_booleans_foreign_keys_and_sequences_without_reimport(): void
    {
        $this->assertSame('imported', RenderBootstrap::importPostgres($this->pdo, $this->tables(), hash('sha256', 'source')));
        $this->assertSame('existing-password-hash', $this->pdo->query('SELECT password FROM users WHERE id = 201')->fetchColumn());
        $this->assertContains($this->pdo->query('SELECT activo FROM clientes WHERE id = 301')->fetchColumn(), [false, 'f', 0, '0']);
        $this->assertSame(301, (int) $this->pdo->query('SELECT cliente_id FROM rutinas WHERE id = 401')->fetchColumn());
        $this->assertSame('2026-10-28 12:34:56', $this->pdo->query('SELECT created_at FROM users WHERE id = 201')->fetchColumn());
        $this->assertSame(202, (int) $this->pdo->query("INSERT INTO users (password, activo, created_at) VALUES ('later-password', true, NOW()) RETURNING id")->fetchColumn());
        $this->assertSame('existing', RenderBootstrap::importPostgres($this->pdo, $this->tables(), hash('sha256', 'new-source')));
        $this->assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
    }

    public function test_failed_foreign_key_import_rolls_back_every_business_row(): void
    {
        $tables = $this->tables();
        $tables[1]['rows'][0][1] = 999;
        try {
            RenderBootstrap::importPostgres($this->pdo, $tables, hash('sha256', 'source'));
            $this->fail('La relación inválida debe revertir toda la importación.');
        } catch (RuntimeException) {
            $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
            $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn());
            $this->assertFalse($this->pdo->inTransaction());
        }
    }
}
