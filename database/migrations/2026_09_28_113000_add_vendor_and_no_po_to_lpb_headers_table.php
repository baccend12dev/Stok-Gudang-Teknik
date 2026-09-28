<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVendorAndNoPoToLpbHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lpb_headers', function (Blueprint $table) {
            $table->string('vendor', 255)->nullable()->after('date');
            $table->string('no_po', 100)->nullable()->after('vendor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lpb_headers', function (Blueprint $table) {
            $table->dropColumn(['vendor', 'no_po']);
        });
    }
}
