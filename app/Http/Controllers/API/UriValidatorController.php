<?php

namespace App\Http\Controllers\API;
use App\Brand;
use App\Http\Controllers\API\BasicController;

use App\TransportType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UriValidatorController extends BasicController
{
    public $jSON_RESPONSE = [];
    public $verifiedData = [];

    public function validateSearch(Request $request){
        parse_str($request->uri, $uris);
        $this->verifyMainQueries($uris);
        foreach ($uris as $alias => $uri){
            switch ($alias){
                case 'transport':
                    $this->analizeTransportAlias($uri);
                    break;
                case 'rbmy':
                    $rbmyFullStores = [];
                    foreach ($uri as $k => $rbmy){
                        $rbmyFullStore = [];
                        if(isset($rbmy['reg'])){
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'regionChoose', intval($rbmy['reg']));
                            $brands = $this->verifiedData['transport_type']->brands()->where('manufacture_id', intval($rbmy['reg']))->select(['brands.id as val', 'title as name'])->get();
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'brands', $brands);
                        }
                        else{
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'regionChoose', null);
                            $brands = [];
                        }
                        $rbmyFullStore = Arr::add($rbmyFullStore, 'brands', $brands);

                        if(isset($rbmy['brand'])){
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'brandChoose', intval($rbmy['brand']));
                            $brand = Brand::select(['brands.id as val', 'title as name'])->find($rbmy['brand']);
                            $models = $brand->modelsWithTransportType($this->verifiedData['transport_type']->val)->select(['id as val', 'title as name'])->get();
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'models', $models);
                        }
                        else{
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'brandChoose', null);
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'models', []);
                        }
                        if(isset($rbmy['model']) && isset($rbmy['brand'])){
                            $modelArr = [];
                            foreach ($rbmy['model'] as $model){
                                array_push($modelArr, intval($model));
                            }
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'modelsChoose', $modelArr);
                        }
                        else{
                            $rbmyFullStore = Arr::add($rbmyFullStore, 'modelsChoose', []);
                        }

                        $yearForm = isset($rbmy['yearF']) ? intval($rbmy['yearF']) : null;
                        $yearTo = isset($rbmy['yearT']) ? intval($rbmy['yearT']) : null;
                        if($yearForm > $yearTo && $yearTo !== null){
                            $tempYear = $yearForm;
                            $yearForm = $yearTo;
                            $yearTo = $tempYear;
                        }
                        $rbmyFullStore = Arr::add($rbmyFullStore, 'yearFrom', $yearForm);
                        $rbmyFullStore = Arr::add($rbmyFullStore, 'yearTo', $yearTo);
                        array_push($rbmyFullStores, $rbmyFullStore);

                    }
                    $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'rbmyFullStore', $rbmyFullStores);
                    break;
                case 'region':
                    $regionFullStores = [];
                    $regionsArr = [];
                    if(isset($uri['reg'])){
                        foreach ($uri['reg'] as $region){
                            array_push($regionsArr, intval($region));
                        }
                    }
                    $regionFullStores = Arr::add($regionFullStores, 'choosedRegions', $regionsArr);

                    $citiesArr = [];
                    if(isset($uri['city'])){
                        foreach ($uri['city'] as $city){
                            array_push($citiesArr, intval($city));
                        }
                    }
                    $regionFullStores = Arr::add($regionFullStores, 'choosedCities', $citiesArr);
                    $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'regionFullStore', $regionFullStores);
                    break;
            }
        }


        return response()->json($this->jSON_RESPONSE, 200);
    }

    private function verifyMainQueries($data){
        $this->validateTransportMain($data);
        return true;
    }
    protected function validateTransportMain($data){
        $tempTTID = 1;
        if(!isset($data['transport']['type'])){
            $tempTT = null;
        }
        else {
            $tempTT = TransportType::select('id as val')->find(intval($data['transport']['type']));
        }

        $tt = $tempTT == null ? TransportType::select('id as val')->find($tempTTID) : $tempTT;
        $this->verifiedData['transport_type'] = $tt;

        if(!isset($data['transport'])){
            $this->analizeTransportAlias();
        }

        return true;
    }


    private function analizeTransportAlias($uri = []){
        $transportFullStores = [];
        $transportFullStores = Arr::add($transportFullStores, 'typeChoosed', $this->verifiedData['transport_type']->val);
        $transportFullStores = Arr::add($transportFullStores, 'transportTypes', TransportType::select(['id as val', 'rtitle as name'])->get());
        $bodies = $this->verifiedData['transport_type']->bodies()->select('id as val', 'rtitle as name')->get();
        $transportFullStores = Arr::add($transportFullStores, 'transportBodies', $bodies);

        if(isset($uri['bodies'])){
            $bodyArr = [];
            foreach ($uri['bodies'] as $body){
                array_push($bodyArr, intval($body));
            }
            $transportFullStores = Arr::add($transportFullStores, 'bodiesChoosed', $bodyArr);
        }
        else{
            $transportFullStores = Arr::add($transportFullStores, 'bodiesChoosed', []);
        }
        $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'transportFullStore', $transportFullStores);
    }
}