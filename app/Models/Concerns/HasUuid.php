<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * HasUuid — Trait for models using UUID primary keys.
 *
 * Configures the model to use non-incrementing UUID strings as PKs.
 * PostgreSQL handles UUID generation via gen_random_uuid(), but this
 * trait provides a fallback for application-level UUID generation.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
