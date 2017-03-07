<?php namespace RainLab\UserPlus\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UserAddCuidadoresFields extends Migration
{

    public function up()
    {
        if (Schema::hasColumns('users', ['rut', 'sexo'])) {
            return;
        }

        Schema::table('users', function($table)
        {
            $table->string('rut', 15)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sexo', 10)->nullable();
            $table->json('telefonos')->nullable();
            $table->integer('region')->nullable();
            $table->integer('provincia')->nullable();
            $table->integer('comuna')->nullable();
            $table->text('direccion')->nullable();
            $table->json('pacientes')->nullable();            
            $table->json('geocode')->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumns('users', ['rut', 'sexo', 'geocode'])) {
            
            Schema::table('users', function($table)
            {
                $table->dropColumn([
                    'rut',
                    'fecha_nacimiento',
                    'sexo',
                    'telefonos',
                    'direccion',
                    'comuna',
                    'provincia',
                    'region',
                    'pacientes',
                    'geocode'
                ]);
            });
        }
    }

}
