<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    
    //Relacion de uno  muchos
    public function posts(){
        return $this->hasMany('App\Post');
        //sacame todo los posts relacionados con esta categoria
    }
}
