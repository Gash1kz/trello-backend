<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Колонка (этап) внутри доски.
 */
class BoardColumn extends Model
{
    protected $table = 'board_columns';

    public $timestamps = true;

    protected $fillable = [
        'board_id',
        'title',
        'position',
    ];

    /**
     * Доска, к которой относится колонка.
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id');
    }

    /**
     * Карточки в этой колонке.
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'board_column_id')->orderBy('position', 'asc');
    }
}
