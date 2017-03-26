<?php namespace Anguro\Capse\Models;

use Model;
use Str;
use Html;
use Markdown;
use Storage;
use Log;
use RainLab\Blog\Classes\TagProcessor;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Anguro\Capse\Classes\DireccionManager as Direccion;
use GuzzleHttp\Client as GuzzleClient;
use Anguro\Capse\Models\Setting;

/**
 * Evento Model
 */
class Evento extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'anguro_capse_eventos';

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['titulo',
        'descripcion',
        'descripcion_html',
        'direccion',
        'cuando',
        'slug'
        ];

    /*
     * Validation
     */
    public $rules = [
        'titulo' => 'required',
        'slug' => ['required', 'regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique:anguro_capse_eventos'],
        'descripcion' => 'required',
        'cuando' => 'required|date',
        'direccion' => 'required'
    ];

    /**
     * @var array Attributes that support translation, if available.
     */
    public $translatable = [
        'titulo',
        'descripcion',
        ['slug', 'index' => true]
    ];

    /**
     * The attributes that should be mutated to dates.
     * @var array
     */
    protected $dates = ['cuando'];

    protected $jsonable = ['geocode'];

    /**
     * The attributes on which the post list can be ordered
     * @var array
     */
    public static $allowedSortingOptions = array(
        'titulo asc' => 'Titulo (ascending)',
        'titulo desc' => 'Titulo (descending)',
        'created_at asc' => 'Created (ascending)',
        'created_at desc' => 'Created (descending)',
        'updated_at asc' => 'Updated (ascending)',
        'updated_at desc' => 'Updated (descending)',
        'cuando asc' => 'Cuando? (ascending)',
        'cuando desc' => 'Cuando? (descending)',
        'random' => 'Random'
    );

    /*
     * Relations
     */
    public $belongsTo = [
        'user' => ['Backend\Models\User']
    ];

    public $attachMany = [
        'featured_images' => ['System\Models\File', 'order' => 'sort_order'],
        'content_images' => ['System\Models\File'],
    ];
    
    public $attachOne = [
        'map_image' => ['System\Models\File']
    ];

    /**
     * @var array The accessors to append to the model's array form.
     */
    protected $appends = ['summary', 'has_summary'];

    public $preview = null;

    public function beforeSave()
    {
        $this->descripcion_html = self::formatHtml($this->descripcion);
        if($this->getOriginal('direccion') !== $this->direccion){
            $this->setGeocode();
            $this->getImagenMapa();
        }
    }
    
    public function getFeaturedImagePath(){
        $path = null;
        if($this->featured_images->count()){
            $path = $this->featured_images->first()->getPath();
        }
        return $path;
    }
    
    public function getImagenMapa(){
        $lat = (double)$this->geocode['location']['lat'];
        $lng = (double)$this->geocode['location']['lng'];
        $url = "https://maps.googleapis.com/maps/api/staticmap?";
        $center = "center=" . $lat .','. ($lng - 0.001);
        $params = [
            "size" => "250x400",
            "zoom" => 16,
            "markers" => $this->direccion
        ];
        $key = "key=" . Setting::get('google_maps_key');
        
        $param = '';
        foreach($params as $k => $v){
            $param .= $k . '=' . urlencode($v) . '&';
        }
        
        $urlConcat = $url . $center . "&" . $param . $key;
        
        $client = new GuzzleClient();
        
        Log::info('[GOOGLE STATIC MAP API] Generando consulta');
        $apiResponse = $client->get($urlConcat);
        
        if($apiResponse->getStatusCode() == 200){
            $data = $apiResponse->getBody();
        }
        
        Log::info('[GOOGLE STATIC MAP API] Respuesta ' . $apiResponse->getStatusCode());        
        
        $aux = $this->map_image;
        $file = new \System\Models\File;
        if(Storage::put('mapa.png', $data)){
            if($imagen = $this->map_image){
                $imagen->delete();
            }
            $file->fromFile('storage/app/mapa.png');
            $file->is_public = true;
            $file->save();
            $aux = $file;
            Storage::delete('mapa.png');
        }
        
        return $this->map_image = $aux;       
    }
    
    public function setMapUrl(){
        return $this->mapUrl = $this->map_image->getPath();
    }

    /**
     * Sets the "url" attribute with a URL to this object
     * @param string $pageName
     * @param Cms\Classes\Controller $controller
     */
    public function setUrl($pageName, $controller)
    {
        $params = [
            'id' => $this->id,
            'slug' => $this->slug,
        ];
        
        //expose published year, month and day as URL parameters
        if ($this->cuando) {
            $params['year'] = $this->cuando->format('Y');
            $params['month'] = $this->cuando->format('m');
            $params['day'] = $this->cuando->format('d');
        }
        
        return $this->url = $controller->pageUrl($pageName, $params);
    }
    
    /**
     * Used to test if a certain user has permission to edit post,
     * returns TRUE if the user is the owner or has other posts access.
     * @param User $user
     * @return bool
     */
    public function canEdit(User $user)
    {
        return ($this->user_id == $user->id) || $user->hasAnyAccess(['anguro.capse.access_other_eventos']);
    }

    public static function formatHtml($input, $preview = false)
    {
        $result = Markdown::parse(trim($input));

        if ($preview) {
            $result = str_replace('<pre>', '<pre class="prettyprint">', $result);
        }

        $result = TagProcessor::instance()->processTags($result, $preview);

        return $result;
    }

    //
    // Summary / Excerpt
    //

    /**
     * Used by "has_summary", returns true if this post uses a summary (more tag)
     * @return boolean
     */
    public function getHasSummaryAttribute()
    {
        $more = '<!-- mas -->';

        return (
            !!strlen(trim($this->excerpt)) ||
            strpos($this->descripcion_html, $more) !== false ||
            strlen(Html::strip($this->descripcion_html)) > 600
        );
    }

    /**
     * Used by "summary", if no excerpt is provided, generate one from the content.
     * Returns the HTML content before the <!-- more --> tag or a limited 600
     * character version.
     *
     * @return string
     */
    public function getSummaryAttribute()
    {
        $excerpt = $this->excerpt;
        if (strlen(trim($excerpt))) {
            return $excerpt;
        }

        $more = '<!-- mas -->';
        if (strpos($this->descripcion_html, $more) !== false) {
            $parts = explode($more, $this->descripcion_html);
            return array_get($parts, 0);
        }

        return Str::limit(Html::strip($this->descripcion_html), 600);
    }

    //
    // Menu helpers
    //

    /**
     * Handler for the pages.menuitem.getTypeInfo event.
     * Returns a menu item type information. The type information is returned as array
     * with the following elements:
     * - references - a list of the item type reference options. The options are returned in the
     *   ["key"] => "title" format for options that don't have sub-options, and in the format
     *   ["key"] => ["title"=>"Option title", "items"=>[...]] for options that have sub-options. Optional,
     *   required only if the menu item type requires references.
     * - nesting - Boolean value indicating whether the item type supports nested items. Optional,
     *   false if omitted.
     * - dynamicItems - Boolean value indicating whether the item type could generate new menu items.
     *   Optional, false if omitted.
     * - cmsPages - a list of CMS pages (objects of the Cms\Classes\Page class), if the item type requires a CMS page reference to
     *   resolve the item URL.
     * @param string $type Specifies the menu item type
     * @return array Returns an array
     */
    public static function getMenuTypeInfo($type)
    {
        $result = [];

        if ($type == 'capse-evento') {

            $references = [];
            $eventos = self::orderBy('titulo')->get();
            foreach ($eventos as $evento) {
                $references[$evento->id] = $evento->titulo;
            }

            $result = [
                'references'   => $references,
                'nesting'      => false,
                'dynamicItems' => false
            ];
        }

        if ($type == 'capse-eventos') {
            $result = [
                'dynamicItems' => true
            ];
        }

        if ($result) {
            $theme = Theme::getActiveTheme();

            $pages = CmsPage::listInTheme($theme, true);
            $cmsPages = [];
            foreach ($pages as $page) {
                if (!$page->hasComponent('capseEvento'))
                    continue;

                /*
                 * Component must use a categoryPage filter with a routing parameter and post slug
                 * eg: categoryPage = "{{ :somevalue }}", slug = "{{ :somevalue }}"
                 */
                $properties = $page->getComponentProperties('capseEvento');
                if (!isset($properties['categoryPage']) || !preg_match('/{{\s*:/', $properties['slug']))
                    continue;

                $cmsPages[] = $page;
            }

            $result['cmsPages'] = $cmsPages;
        }

        return $result;
    }

    /**
     * Handler for the pages.menuitem.resolveItem event.
     * Returns information about a menu item. The result is an array
     * with the following keys:
     * - url - the menu item URL. Not required for menu item types that return all available records.
     *   The URL should be returned relative to the website root and include the subdirectory, if any.
     *   Use the Url::to() helper to generate the URLs.
     * - isActive - determines whether the menu item is active. Not required for menu item types that
     *   return all available records.
     * - items - an array of arrays with the same keys (url, isActive, items) + the title key.
     *   The items array should be added only if the $item's $nesting property value is TRUE.
     * @param \RainLab\Pages\Classes\MenuItem $item Specifies the menu item.
     * @param \Cms\Classes\Theme $theme Specifies the current theme.
     * @param string $url Specifies the current page URL, normalized, in lower case
     * The URL is specified relative to the website root, it includes the subdirectory name, if any.
     * @return mixed Returns an array. Returns null if the item cannot be resolved.
     */
    public static function resolveMenuItem($item, $url, $theme)
    {
        $result = null;

        if ($item->type == 'capse-evento') {
            if (!$item->reference || !$item->cmsPage)
                return;

            $category = self::find($item->reference);
            if (!$category)
                return;

            $page = CmsPage::loadCached($theme, $pageCode);
            if (!$page) return;

            $pageUrl = CmsPage::url($page->getBaseFileName());

            //$pageUrl = self::getEventoPageUrl($item->cmsPage, $category, $theme);
            if (!$pageUrl)
                return;

            $pageUrl = Url::to($pageUrl);

            $result = [];
            $result['url'] = $pageUrl;
            $result['isActive'] = $pageUrl == $url;
            //$result['mtime'] = $category->updated_at;
        }
        elseif ($item->type == 'capse-eventos') {
            $result = [
                'items' => []
            ];

            $eventos = self::orderBy('title')->get();
            foreach ($eventos as $evento) {
                $eventoItem = [
                    'titulo' => $evento->titulo,
                    'url'   => self::getEventoPageUrl($item->cmsPage, $evento, $theme),
                    'mtime' => $evento->updated_at,
                ];

                $eventoItem['isActive'] = $eventoItem['url'] == $url;

                $result['items'][] = $eventoItem;
            }
        }

        return $result;
    }

    /**
     * Returns URL of a post page.
     */
    protected static function getEventoPageUrl($pageCode, $category, $theme)
    {
        $page = CmsPage::loadCached($theme, $pageCode);
        if (!$page) return;

        $properties = $page->getComponentProperties('capseEvento');
        if (!isset($properties['slug'])) {
            return;
        }

        /*
         * Extract the routing parameter name from the category filter
         * eg: {{ :someRouteParam }}
         */
        if (!preg_match('/^\{\{([^\}]+)\}\}$/', $properties['slug'], $matches)) {
            return;
        }

        $paramName = substr(trim($matches[1]), 1);
        $url = CmsPage::url($page->getBaseFileName(), [$paramName => $category->slug]);

        return $url;
    }
    
    protected function setGeocode(){        
        return $this->geocode = Direccion::getGeocode($this->direccion);
    }
    
    /**
     * Lists eventos for the front end
     * @param  array $options Display options
     * @return self
     */
    public function scopeListFrontEnd($query, $options)
    {
        /*
         * Default options
         */
        extract(array_merge([
            'page'       => 1,
            'perPage'    => 30,
            'sort'       => 'created_at',
            'search'     => '',
        ], $options));

        $searchableFields = ['titulo', 'slug', 'descripcion'];

        /*
         * Sorting
         */
        if (!is_array($sort)) {
            $sort = [$sort];
        }

        foreach ($sort as $_sort) {

            if (in_array($_sort, array_keys(self::$allowedSortingOptions))) {
                $parts = explode(' ', $_sort);
                if (count($parts) < 2) {
                    array_push($parts, 'desc');
                }
                list($sortField, $sortDirection) = $parts;
                if ($sortField == 'random') {
                    $sortField = Db::raw('RAND()');
                }
                $query->orderBy($sortField, $sortDirection);
            }
        }

        /*
         * Search
         */
        $search = trim($search);
        if (strlen($search)) {
            $query->searchWhere($search, $searchableFields);
        }

        return $query->paginate($perPage, $page);
    }

}