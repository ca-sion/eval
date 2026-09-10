<?php

namespace App\Models;

use App\Enums\ArbitrageMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'arbitration_mode' => ArbitrageMode::class,
            'default_sessions_per_week' => 'integer',
            'default_competitions_planned' => 'integer',
            'quota_places' => 'integer',
            'min_score' => 'float',
            'max_volunteering_age' => 'integer',
            'required_volunteering_count' => 'integer',
            'is_training_group' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Group $group): void {
            if (empty($group->slug)) {
                $baseSlug = Str::slug($group->name) ?: 'groupe';
                $slug = $baseSlug;
                $count = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$count}";
                    $count++;
                }
                $group->slug = $slug;
            }

            if (empty($group->access_token)) {
                $group->access_token = Str::random(64);
            }

            if ($group->default_sessions_per_week === null) {
                $group->default_sessions_per_week = (int) config('evaluation.defaults.sessions_per_week', 3);
            }

            if ($group->default_competitions_planned === null) {
                $group->default_competitions_planned = (int) config('evaluation.defaults.competitions_planned', 6);
            }

            if ($group->min_score === null) {
                $group->min_score = (float) config('evaluation.defaults.min_score', 6.50);
            }

            if ($group->quota_places === null) {
                $group->quota_places = (int) config('evaluation.defaults.quota_places', 12);
            }

            if ($group->max_volunteering_age === null) {
                $group->max_volunteering_age = (int) config('evaluation.defaults.max_volunteering_age', 14);
            }

            if ($group->required_volunteering_count === null) {
                $group->required_volunteering_count = (int) config('evaluation.defaults.required_volunteering_count', 2);
            }
        });
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function getMobileUrl(): string
    {
        return route('group.mobile', ['group' => $this->access_token]);
    }

    public function getWhatsAppShareUrl(): string
    {
        $text = rawurlencode("Lien d'accès à l'évaluation pour le groupe {$this->name} : ".$this->getMobileUrl());

        return "https://api.whatsapp.com/send?text={$text}";
    }

    public function regenerateAccessToken(): string
    {
        $this->update(['access_token' => Str::random(64)]);

        return $this->access_token;
    }
}
