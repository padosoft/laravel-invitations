<?php

declare(strict_types=1);

namespace Padosoft\Invitations;

use Illuminate\Database\Eloquent\Model;

/**
 * Small static accessors for package-wide configuration that model
 * relationships need at definition time.
 */
final class Invitations
{
    /**
     * The host's configured user/account model class.
     *
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        /** @var class-string<Model> $model */
        $model = config('invitations.user_model', 'App\\Models\\User');

        return $model;
    }
}
