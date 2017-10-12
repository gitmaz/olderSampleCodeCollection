<?php

  namespace App;

  use Illuminate\Database\Eloquent\Model as Model;

  class WorkOperations extends Model {
    protected $table = 'work_operations';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $fillable = ['unique_id', 'order_id', 'sequence', 'item_name', 'start_date', 'end_date', 'recipient', 'resource_modifier', 'estimated_time', 'operation_hash', 'created_by', 'updated_at'];

  }
