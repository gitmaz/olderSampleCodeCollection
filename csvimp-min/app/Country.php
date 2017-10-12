<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'population', 'area', 'rank'];

    function fetchTableRecords()
    {

        $all = Self::all()->toArray();

        return json_encode($all);

    }
}
