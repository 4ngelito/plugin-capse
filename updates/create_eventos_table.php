<?php namespace Anguro\Capse\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateEventosTable extends Migration
{
    public function up()
    {
        Schema::create('anguro_capse_eventos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('titulo')->nullable();
            $table->string('slug')->index();
            $table->text('descripcion')->nullable();
            $table->text('descripcion_html')->nullable();
            $table->timestamp('cuando')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('anguro_capse_eventos');
    }
}
