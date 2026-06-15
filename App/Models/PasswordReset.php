<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Şifre sıfırlama token'ları. Token süresi kontrolü ve tek kullanımlık token yönetimi.
 */
class PasswordReset extends Model
{
    protected $table = 'password_resets';

    public $timestamps = false;

    protected $fillable = ['email', 'token', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Token'ın geçerli olup olmadığını kontrol et (süre aşımı).
     */
    public function isExpired(int $maxMinutes = 60): bool
    {
        if (!$this->created_at) {
            return true;
        }
        return $this->created_at->diffInMinutes(now(), false) > $maxMinutes;
    }

    /**
     * Geçerli (süresi dolmamış) token ile kayıt bul; yoksa null.
     */
    public static function findValidToken(string $token, int $maxMinutes = 60): ?self
    {
        $row = static::where('token', $token)->first();
        if (!$row || $row->isExpired($maxMinutes)) {
            return null;
        }
        return $row;
    }

    /**
     * E-posta için mevcut token'ları sil (yeni talep öncesi).
     */
    public static function deleteByEmail(string $email): void
    {
        static::where('email', $email)->delete();
    }
}
