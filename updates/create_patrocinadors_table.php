<?php namespace Anguro\Capse\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreatePatrocinadorsTable extends Migration
{
    public function up()
    {
        Schema::create('anguro_capse_patrocinadors', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->string('nombre')->index();
            $table->string('url')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('anguro_capse_patrocinadors');
    }
}
