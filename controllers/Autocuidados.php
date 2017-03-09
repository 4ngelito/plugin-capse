<?php namespace Anguro\Capse\Controllers;

use BackendMenu;
use RainLab\Blog\Controllers\Posts as PostsController;
use RainLab\Blog\Models\Category as CategoryModel;
use RainLab\Blog\Models\Post;

/**
 * Autocuidados Back-end Controller
 */
class Autocuidados extends PostsController
{
    /*
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];
    */
    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $importExportConfig = 'config_import_export.yaml';
    
    /**
     *  id de la categoria que pertenecen los posts
     * @var type int
     */
    private $idCategoria = 1;
    
    public function __construct()
    {
        parent::__construct();
        
        $cat = CategoryModel::whereSlug(str_slug('Autocuidados'))->first();
        if($cat){
            $this->idCategoria = $cat->id;
        }
        BackendMenu::setContext('Anguro.Capse', 'autocuidados', 'posts');
    }
    
    public function index($userId = null)
    {
        $this->vars['postsTotal'] = Post::whereHas('categories', function($q) {
            $q->where('id', $this->idCategoria);
            })
            ->count();
        $this->vars['postsPublished'] = Post::whereHas('categories', function($q) {
            $q->where('id', $this->idCategoria);
            })
            ->isPublished()
            ->count();
        $this->vars['postsDrafts'] = $this->vars['postsTotal'] - $this->vars['postsPublished'];
        
        $this->asExtension('ListController')->index();
    }

    
    public function listExtendQuery($query)
    {
        if (!$this->user->hasAnyAccess(['anguro.capse.access_autocuidados'])) {
            $query->where('user_id', $this->user->id);
        }
        else {
            $query->whereHas('categories', function($q) {
                        $q->where('id', $this->idCategoria);
               })->where('user_id', $this->user->id);
        }
    }

    public function formExtendQuery($query)
    {
        if (!$this->user->hasAnyAccess(['anguro.capse.access_autocuidados'])) {
            $query->where('user_id', $this->user->id);
        }
        else {
            $query->whereHas('categories', function($q) {
                        $q->where('id', $this->idCategoria);
               })->where('user_id', $this->user->id);
        }
    }
}