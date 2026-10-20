<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Пользователь системы.
 */
class User extends Model
{
    protected $table = 'users';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Доски, которыми владеет пользователь.
     */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class, 'owner_id');
    }

    /**
     * Карточки, назначенные на пользователя.
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'user_id');
    }
}
