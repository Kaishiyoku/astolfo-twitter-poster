<?php

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migration;

class TruncatePostLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * @var $db DatabaseManager
         */
        $db = app('db');

        $db->table('post_logs')->truncate();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
