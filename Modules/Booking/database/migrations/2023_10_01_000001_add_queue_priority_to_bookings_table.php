<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQueuePriorityToBookingsTableMigration extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('queue_priority')->nullable()->after('queue_status');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('queue_priority');
        });
    }
}
