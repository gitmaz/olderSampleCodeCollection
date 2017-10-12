<?php

namespace App;

use Illuminate\Database\Eloquent\Model as Model;

class GeneralTestModel2 extends Model
{
    protected $table = 'general_test2';
    protected $primaryKey = 'e';
    protected $fillable = ['e', 'f', 'g'];
    public $timestamps = false;

}
