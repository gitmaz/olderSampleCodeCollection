<?php

  namespace App;

  use Illuminate\Database\Eloquent\Model as Model;
  use Illuminate\Database\Capsule\Manager as Capsule;

  class WorkOrders extends Model {
    const tableName = "work_orders";
    protected $table = 'work_orders';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $fillable = ['order_id','order_code', 'order_desc', 'order_hash', 'project_id', 'created_by'];

    const SEARCH_ALL           = 0;
    const SEARCH_ACTIVE        = 1;
    const SEARCH_UPCOMING      = 2;
    const SEARCH_RECENTLY_USED = 3;

    /**
     *  sets the start and end dates from minimum and maximum dates found in operation table for each order
     * @return void
     */
    public static function updateOrderStartAndEndTimesFromOperations(){
      //todo: convert this to pure Eloquent sql expressions
      $model = new WorkOrders();
      $builder = $model->getConnection()->getSchemaBuilder();
      $builder->getConnection()->select("UPDATE eify_work_orders wo
                                                SET order_start = 
                                                      ( SELECT MIN(start_date) 
                                                        FROM eify_work_operations wop
                                                        WHERE wo.id = wop.order_id
                                                      ),
                                                 order_end = 
                                                      ( SELECT MAX(start_date) 
                                                        FROM eify_work_operations wop
                                                        WHERE wo.id = wop.order_id
                                                      ) WHERE order_start='0000-00-00 00:00:00'");

    }

  }
