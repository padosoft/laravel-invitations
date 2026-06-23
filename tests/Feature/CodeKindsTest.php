<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\InviteCode;
use Padosoft\Invitations\Services\CodeGenerator;
use Padosoft\Invitations\Support\CodeGenerationException;
use Padosoft\Invitations\Tests\TestCase;

final class CodeKindsTest extends TestCase
{
    public function test_vanity_code_is_uppercased_and_crockford_folded(): void
    {
        // Crockford input-folding maps confusables: L→1, O→0 (and I→1).
        $code = app(CodeGenerator::class)->mintVanity('welcome2025');

        $this->assertSame(InviteCode::KIND_VANITY, $code->code_kind);
        $this->assertSame('WE1C0ME2025', $code->code);
    }

    public function test_reserved_vanity_code_is_rejected(): void
    {
        $this->expectException(CodeGenerationException::class);

        app(CodeGenerator::class)->mintVanity('ADMIN');
    }

    public function test_signed_code_round_trips_and_verifies(): void
    {
        $generator = app(CodeGenerator::class);

        $code = $generator->mintSigned([
            'campaign' => 'promo',
            'capacity' => 100,
            'exp' => 9_999_999_999,
        ]);

        $this->assertSame(InviteCode::KIND_SIGNED, $code->code_kind);

        $verified = $generator->verifySigned($code->code);
        $this->assertTrue($verified['ok']);
        $this->assertSame('promo', $verified['payload']['campaign']);
    }

    public function test_a_tampered_signed_code_fails_verification(): void
    {
        $generator = app(CodeGenerator::class);
        $code = $generator->mintSigned(['campaign' => 'promo', 'capacity' => 5, 'exp' => 9_999_999_999]);

        $verified = $generator->verifySigned($code->code.'X');

        $this->assertFalse($verified['ok']);
    }
}
