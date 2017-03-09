<?php namespace Anguro\Capse\Models;

use Model;
use October\Rain\Database\Attach\Resizer;

/**
 * Socio Model
 */
class Socio extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'anguro_capse_socios';

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['nombre'
        , 'url'
        , 'imagen'];
    
    /**
     * Validation
     */
    public $rules = ['nombre' => 'required|unique:anguro_capse_socios'
        , 'imagen' => 'required'
        , 'url' => 'active_url'
    ];
    
    /**
     * The attributes on which the post list can be ordered
     * @var array
     */
    public static $allowedSortingOptions = array(
        'nombre asc' => 'Nombre (ascending)',
        'nombre desc' => 'Nombre (descending)',
        'created_at asc' => 'Created (ascending)',
        'created_at desc' => 'Created (descending)',
        'updated_at asc' => 'Updated (ascending)',
        'updated_at desc' => 'Updated (descending)',
        'random' => 'Random'
    );

    /**
     * @var array Relations
     */
    public $attachOne = [
        'imagen' => ['System\Models\File']
    ];
    
    public function beforeSave(){        
        $img = $this->imagen;        
        if($img){
            $resizer = Resizer::open(".".$img->getPath());                        
            $resizer->resize(265, 150, ['mode' => 'auto'])->save(".".$img->getPath(), 100);
        }
        
    }
}
