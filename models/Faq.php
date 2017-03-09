<?php namespace Anguro\Capse\Models;

use Model;

/**
 * Faq Model
 */
class Faq extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'anguro_capse_faqs';
    
    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['pregunta',
        'respuesta'];
    
    /*
     * Validation
     */
    
    public $rules = [
        'pregunta' => 'required|unique:anguro_capse_faqs',
        'respuesta' => 'required'
    ];
    
    /*
     * Relations
     */
    public $belongsTo = [
        'user' => ['Backend\Models\User']
    ];

}
