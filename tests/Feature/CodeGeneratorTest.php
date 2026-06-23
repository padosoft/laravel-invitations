<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\InviteCode;
use Padosoft\Invitations\Services\CodeGenerator;
use Padosoft\Invitations\Tests\TestCase;

final class CodeGeneratorTest extends TestCase
{
    public function test_generate_random_persists_an_active_unredeemed_code(): void
    {
        $code = app(CodeGenerator::class)->generateRandom();

        $this->assertDatabaseHas('invite_codes', [
            'id' => $code->id,
            'state' => InviteCode::STATE_ACTIVE,
            'code_kind' => InviteCode::KIND_RANDOM,
        ]);
        $this->assertSame(0, $code->current_uses);
        $this->assertSame('default', $code->tenant_id);
    }

    public function test_random_codes_use_only_the_crockford_alphabet(): void
    {
        $code = app(CodeGenerator::class)->generateRandom();

        // Crockford Base32 omits I, L, O, U.
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]+$/', $code->code);
    }

    public function test_generate_batch_returns_distinct_codes(): void
    {
        $codes = app(CodeGenerator::class)->generateBatch(5);

        $this->assertCount(5, $codes);
        $values = array_map(static fn (InviteCode $c): string => $c->code, $codes);
        $this->assertCount(5, array_unique($values), 'batch codes must be unique');
    }
}
