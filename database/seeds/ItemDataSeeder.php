<?php

use Illuminate\Database\Seeder;
use App\Item;
use App\ItemDepartmentBuffer;
use App\LpbHeader;
use App\LpbDetail;
use Carbon\Carbon;

class ItemDataSeeder extends Seeder
{
    public function run()
    {
        // Import data dari Excel menggunakan ImportController
        $importController = app()->make('App\Http\Controllers\ImportController');
        $importController->importExcel();
    }
}