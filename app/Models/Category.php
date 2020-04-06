<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'is_directory', 'level', 'path'];
    protected $casts = [
        'is_directory'  => 'boolean',
    ];


    protected static function boot()
    {
        parent::boot();
        static::creating(function (Category $category) {
            if (is_null($category->parent_id)) {
                $category->level = 0;
                $category->path = '-';
            } else {
                $category->level = $category->parent->level + 1;
                $category->path = $category->parent->path . $category->parent_id . '-';
            }
        });
    }

    // 取祖类 ID 数组
    public function getPathIdsAttribute()
    {
        return array_filter(explode('-', trim($this->path, '-')));
    }

    // 取完整分类名
    public function getFullNameAttribute()
    {
        return $this->ancestors
                    ->pluck('name')
                    ->push($this->name)
                    ->implode(' - ');
    }

    // 按层级排序取祖类
    // 这不会套娃🐴
    public function getAncestorsAttribute()
    {
        return Category::query()
            ->whereIn('id', $this->path_ids)
            ->orderBy('level')
            ->get();
    }


    // 反向一对多
    public function parent()
    {
        return $this->beLongsTo(Category::class);
    }

    // 一对多
    public function children()
    {
        return $this->hasMany(Category::class);
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
