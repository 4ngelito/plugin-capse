<?php namespace Anguro\Capse\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Faqs Back-end Controller
 */
class Faqs extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    
    public $requiredPermissions = ['anguro.capse.access_faqs'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Anguro.Capse', 'faqs', 'faqs');
    }
}
