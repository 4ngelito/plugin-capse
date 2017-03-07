<?php namespace Anguro\Capse\Classes;

use File;
use Log;
use GuzzleHttp\Client as GuzzleClient;
use Anguro\Capse\Models\Setting;

class DireccionManager {

    public $direccion;
    
    public $region;

    public $provincia;

    public $comuna;

    public $direccionCompleta;
    

    public static function getRegiones(){
        
        $json = self::leeRegiones();
        
        $regiones = null;
        foreach ($json as $region) {
            $regiones[$region->region_id] = $region->name;
        }
        
        return $regiones;
    }
    
    public static function getProvincias($region){
        if($region == NULL)
            return [ '' => '-- Seleccione Regi&oacute;n --'];
        
        $json = self::leeProvincias();

        $provincias = null;
        
        foreach ($json as $region_id => $p) {
            if($region_id == $region){
                foreach ($p as $prov) {
                    $provincias[$prov->provincia_id] = $prov->name;
                }
            }
        }
        
        return $provincias;
    }
    
    public static function getComunas($provincia){
        if($provincia == NULL)
            return [ '' => '-- Seleccione Provincia --'];

        $json = self::leeComunas();
        $comunas = null;
        
        foreach ($json as $provincia_id => $c) {
            if($provincia_id == $provincia){
                foreach ($c as $comu) {
                    $comunas[$comu->comuna_id] = $comu->name;
                }
            }
        }
        
        return $comunas;
    }

    public static function leeRegiones(){
        $jsonFile = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_Regiones.min.json';
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        return $json;
    }

    public static function leeProvincias(){
        $jsonFile = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_ProvinciaRegion.min.json';
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        return $json;
    }

    public static function leeComunas(){
        $jsonFile = __DIR__ . '/../assets/js/bdcut-cl/BDCUT_CL_ComunaProvincia.min.json';
        $json = json_decode(File::get($jsonFile), false, 512, JSON_UNESCAPED_UNICODE);
        return $json;
    }

    public static function getGeocode($direccion){

        $url = "https://maps.googleapis.com/maps/api/geocode/json?";
        $addr = "address=" . $direccion;
        $key = "key=" . Setting::get('google_maps_key');
        $urlConcat = $url . $addr . "&" . $key;

        $client = new GuzzleClient();

        Log::info('[GOOGLE MAPS API] Generando consulta');

        $apiResponse = $client->get($urlConcat);
        if($apiResponse->getStatusCode() == 200){
            $response = json_decode($apiResponse->getBody());
        }

        Log::info('[GOOGLE MAPS API] Respuesta ' . $apiResponse->getStatusCode());

        $geocode = null;
        if($response->status === 'OK'){
            $res = $response->results[0];
            $geocode = [
                'location' => [
                    'lat' => $res->geometry->location->lat,
                    'lng' => $res->geometry->location->lng
                ],
                'place_id' => $res->place_id
            ];
        }

        return $geocode;
    }
}