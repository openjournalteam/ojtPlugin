<?php

namespace Openjournalteam\OjtPlugin\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class v2_2_0_6 extends Migration
{
    public function up()
    {
        if (Capsule::schema()->hasTable('ojt_job_tracking')
            && !Capsule::schema()->hasColumn('ojt_job_tracking', 'queue_wait_seconds')) {
            Capsule::schema()->table('ojt_job_tracking', function (Blueprint $table) {
                $table->unsignedInteger('queue_wait_seconds')->nullable();
            });
        }
    }

    public function down()
    {
        if (Capsule::schema()->hasColumn('ojt_job_tracking', 'queue_wait_seconds')) {
            Capsule::schema()->table('ojt_job_tracking', function (Blueprint $table) {
                $table->dropColumn('queue_wait_seconds');
            });
        }
    }
}
