<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Content extends Model
{
    use HasTranslations;

    protected $fillable = [
        'title',
        'body',
        'slug',
        'category_id',
        'subcategory_id',
        'tags',
        'is_published',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public $translatable = ['title', 'body'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }
}
