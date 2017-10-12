<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'population', 'area', 'rank', 'country_id'];

    function fetchTableRecords()
    {

        $all = Self::all()->toArray();

        return json_encode($all);

    }
}
