<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Доска задач (Kanban/Trello доска).
 */
class Board extends Model
{
    protected $table = 'boards';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'description',
        'owner_id',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Владелец доски.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Колонки доски.
     */
    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class, 'board_id')->orderBy('position', 'asc');
    }

    /**
     * Все карточки доски.
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'board_id');
    }
}
