<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['image', 'title_en', 'title_ar', 'deleted'];

    public function products() {
        return $this->hasMany('App\Product', 'category_id');
    }
}
