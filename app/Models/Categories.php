<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * Модель для роботи з категоріями в базі даних.
 */
class Categories extends Model
{
    use HasFactory;
    /**
     * Масив полів, які можуть бути заповнені при створенні нової категорії.
     * Колонки "name" та "image" вважаються заповнюваними
     */
    protected $fillable = ["name", "image"];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
