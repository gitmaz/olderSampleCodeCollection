<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Services\CsvImporter;
use App\GeneralTestModel;
use App\GeneralTestModel2;

class CsvImportTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * testItCanImportToOneModel
     */
    public function testItCanImportToOneModel()
    {

        $importer = new CsvImporter(__DIR__ . "/sample_data/sample_csv_tiny.healthy.txt", ["GeneralTestModel" => [["col_name" => "a", "csv_index" => 1]
            , ["col_name" => "b", "csv_index" => 2]
            , ["col_name" => "c", "csv_index" => 3]
        ]
        ]);
        $importer->import();
    }

    /**
     * testItCanImportToMultipleModels todo: complete this
     */
    public function testItCanImportToMultipleModels()
    {
        $importer = new CsvImporter(__DIR__ . "/sample_data/sample_csv_tiny.healthy.txt", ["GeneralTestModel" => [["col_name" => "a", "csv_index" => 1]
            , ["col_name" => "b", "csv_index" => 2]
            , ["col_name" => "c", "csv_index" => 3]
        ]
            , "GeneralTestModel2" => [["col_name" => "e", "csv_index" => 1]
                , ["col_name" => "f", "csv_index" => 2]
                , ["col_name" => "g", "csv_index" => 3]
            ]
        ], true, false);
        $importer->import();

        self::assertEquals("0,0", $importer->getMismatchCount(), "there are some records with mismatch types in csv");
    }


    /**
     * testItCanFindMismatchesInMultipleModels
     */
    public function testItCanFindMismatchesInMultipleModels()
    {
        $importer = new CsvImporter(__DIR__ . "/sample_data/sample_csv_tiny.type_mismatch.txt", ["GeneralTestModel" => [["col_name" => "a", "csv_index" => 1]
            , ["col_name" => "b", "csv_index" => 2]
            , ["col_name" => "c", "csv_index" => 3]
        ]
            , "GeneralTestModel2" => [["col_name" => "e", "csv_index" => 1]
                , ["col_name" => "f", "csv_index" => 2]
                , ["col_name" => "g", "csv_index" => 3]
            ]
        ], true, false);
        $importer->import();

        self::assertEquals("2,0", $importer->getMismatchCount(), "there are some records with mismatch types in csv");
    }

    /**
     * testItCountsMismatchRecords
     */
    public function testItCountsMismatchRecords()
    {
        $importer = new CsvImporter(__DIR__ . "/sample_data/sample_csv_tiny.type_mismatch.txt", ["GeneralTestModel" => [["col_name" => "a", "csv_index" => 1]
            , ["col_name" => "b", "csv_index" => 2]
            , ["col_name" => "c", "csv_index" => 3]
        ]
        ]);
        $importer->import();

        self::assertEquals("2", $importer->getMismatchCount(), "there are some records with mismatch types in csv");
    }


}