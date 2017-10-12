<?php

namespace App;

use Illuminate\Database\Eloquent\Model as Model;

class GeneralTestModel extends Model
{
    protected $table = 'general_test';
    protected $primaryKey = 'a';
    protected $fillable = ['a', 'b', 'c'];
    public $timestamps = false;

}
