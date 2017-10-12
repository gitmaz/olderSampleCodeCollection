<?php
//namespace database\seeds;

use Illuminate\Database\Seeder;
//use database\seeds\CsvMappingTableSeeder;
use Illuminate\Database\Eloquent\Model;
use App\CsvMapping;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //$this->call(CsvMappingTableSeeder::class);
        Model::unguard();

        DB::table('csv_mappings')->delete();

        $csvMappings = array(
            ['id' => 1, 'name' => 'countries and cities', 'mapping' => ' [
                 "Country" => [
                                        ["col_name" =>"id", "csv_index" => 1],
                                        ["col_name" =>"name", "csv_index" => 2],
                                        ["col_name" =>"population", "csv_index" => 3],
                                        ["col_name" =>"area", "csv_index" => 4],
                                        ["col_name" =>"rank", "csv_index" => 5]
                                       ],
                 "City"=>[
                                        ["col_name" =>"id", "csv_index" => 6],
                                        ["col_name" =>"name", "csv_index" => 7],
                                        ["col_name" =>"population", "csv_index" => 8],
                                        ["col_name" =>"area", "csv_index" => 9],
                                        ["col_name" =>"rank", "csv_index" => 10],
                                        ["col_name" =>"country_id", "csv_index" => 11]
                                      ]
                ]'
            ],
            ['id' => 2, 'name' => 'for GeneralTestModels (unit tests)', 'mapping' => ' [
                 "GeneralTestModel" => [
                                        ["col_name" =>"a", "csv_index" => 1],
                                        ["col_name" =>"b", "csv_index" => 2],
                                        ["col_name" =>"c", "csv_index" => 3]
                                       ],
                 "GeneralTestModel2"=>[
                                        ["col_name" =>"e", "csv_index" => 1],
                                        ["col_name" =>"f", "csv_index" => 2],
                                        ["col_name" =>"g", "csv_index" => 3]
                                      ]
                ]'
            ]

        );

        foreach ($csvMappings as $csvMapping) {
            CsvMapping::create($csvMapping);
        }

        Model::reguard();
    }
}
