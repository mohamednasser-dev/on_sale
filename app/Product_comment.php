<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product_comment extends Model
{
    protected $fillable = ['comment', 'user_id', 'product_id','status'];
//    protected $attributes = ['user_name'];
    public function user() {
        return $this->belongsTo('App\User', 'user_id')->select('id','name');
    }

//    public function getUserNameAttribute($user)
//    {
//        $user = User::find($this->user_id);
//        return $user->name;
//    }
}
