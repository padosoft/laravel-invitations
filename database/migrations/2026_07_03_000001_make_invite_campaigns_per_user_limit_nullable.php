<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make `invite_campaigns.per_user_limit` NULLABLE.
 *
 * The original create migration declared it `->default(1)` NOT NULL, but the
 * field is treated as OPTIONAL everywhere else — `InviteCampaignStoreRequest`
 * validates it `nullable`, and a consumer's "clear the per-user limit" edit
 * sends an explicit `null`. Against a NOT NULL column that explicit null bypasses
 * the default and raises a NOT NULL constraint violation (a 500 on create/update).
 *
 * `per_user_limit` is stored config only — it is not enforced anywhere in the
 * redemption path yet — so `null` (= no per-user limit / unset, mirroring
 * `max_redemptions_total`'s nullable = unlimited) is safe and carries no
 * behaviour change. Keep the `default(1)` so an omitted value still lands at 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invite_campaigns') && Schema::hasColumn('invite_campaigns', 'per_user_limit')) {
            Schema::table('invite_campaigns', function (Blueprint $table): void {
                $table->unsignedInteger('per_user_limit')->nullable()->default(1)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invite_campaigns') && Schema::hasColumn('invite_campaigns', 'per_user_limit')) {
            // Coalesce any nulls to the default before tightening to NOT NULL so
            // the ALTER cannot fail on existing rows.
            DB::table('invite_campaigns')
                ->whereNull('per_user_limit')
                ->update(['per_user_limit' => 1]);

            Schema::table('invite_campaigns', function (Blueprint $table): void {
                $table->unsignedInteger('per_user_limit')->default(1)->nullable(false)->change();
            });
        }
    }
};
