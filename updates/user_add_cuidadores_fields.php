<?php namespace RainLab\UserPlus\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UserAddCuidadoresFields extends Migration
{
    private $columnas = [
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
                ];
    private $columnasExistentes = [];
    
    public function up()
    {
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
        
        foreach($this->columnas as $c){
            if (Schema::hasColumn('users', $c)) {
                array_push($this->columnasExistentes,$c);
            }
        }
        
        if(count($this->columnasExistentes) > 0){
            Schema::table('users', function($table){
                $table->dropColumn($this->columnasExistentes);
            });
        }
    }

}
