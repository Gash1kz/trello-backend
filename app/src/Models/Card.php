<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Карточка задачи внутри колонки доски.
 */
class Card extends Model
{
    protected $table = 'cards';

    public $timestamps = true;

    protected $fillable = [
        'board_column_id',
        'board_id',
        'user_id',
        'title',
        'description',
        'position',
        'status',
    ];

    /**
     * Колонка, в которой находится карточка.
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'board_column_id');
    }

    /**
     * Доска, к которой относится карточка.
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id');
    }

    /**
     * Пользователь, на которого назначена карточка.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
