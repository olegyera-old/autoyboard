<?php

namespace App\Http\Controllers\System;
use App\Http\Controllers\API\BasicController;
use App\Http\Controllers\System\SearchController;

use App\ManufactureCountry;
use App\TransportType;
use App\TransportChColor;
use App\TransportChState;
use App\TransportChFuel;
use App\SystemSorting;
use App\SystemPeriod;
use App\SystemRelevance;
use App\SystemShow;
use App\Brand;
use App\UkrainianRegionPart;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UriValidatorController extends BasicController
{
    public $jSON_RESPONSE = [];
    public $verifiedData = [];
    public $langTitle = [];

    public function validateSearch($searchData, $langTitle){
        $uris = $searchData;
        $this->langTitle = $langTitle;
        $this->verifyMainQueries($uris);
        foreach ($uris as $alias => $uri){
            switch ($alias){
                case 's':
                    $this->analizeSearchDetailAlias($uri);
                    break;
                case 't':
                    $this->analizeTransportAlias($uri);
                    break;
                case 'rbmy':
                    $this->analizeRbmyAlias($uri);
                    break;
                case 'r':
                    $this->analizeRegionAlias($uri);
                    break;
            }
        }

        return $this->jSON_RESPONSE;
    }

    private function verifyMainQueries($data){
        $this->validateSearchDetailMain($data);
        $this->validateTransportMain($data);
        $this->validateRbmyMain($data);
        $this->validateRegionMain($data);
        return true;
    }

    private function validateSearchDetailMain($data){
        $this->verifiedData['autoCond'] = (isset($data['s']['autoCond']) && $data['s']['autoCond'] <= 3 && $data['s']['autoCond'] >= 1) ? intval($data['s']['autoCond']) : 1;
        $this->verifiedData['curr'] = (isset($data['s']['curr']) && $data['s']['curr'] <= 3 && $data['s']['curr'] >= 1) ? intval($data['s']['curr']) : 1;
        $this->verifiedData['sorting'] = (isset($data['s']['sort']) && $data['s']['sort'] <= SystemSorting::count() && $data['s']['sort'] >= 1) ? intval($data['s']['sort']) : 1;
        $this->verifiedData['period'] = (isset($data['s']['period']) && $data['s']['period'] <= SystemPeriod::count() && $data['s']['period'] >= 1) ? intval($data['s']['period']) : 1;
        $this->verifiedData['relevance'] = (isset($data['s']['rel']) && $data['s']['rel'] <= SystemRelevance::count() && $data['s']['rel'] >= 1) ? intval($data['s']['rel']) : 1;
        $this->verifiedData['show'] = (isset($data['s']['show']) && $data['s']['show'] <= SystemShow::count() && $data['s']['show'] >= 1) ? intval($data['s']['show']) : 1;
        $this->verifiedData['page'] = (isset($data['s']['page']) && $data['s']['page'] >= 1) ? intval($data['s']['page']) : 1;
        if(!isset($data['s'])){
            $this->analizeSearchDetailAlias();
        }
        return true;
    }

    private function validateTransportMain($data){
        $tempTTID = 1;
        if(!isset($data['t']['type'])){
            $tempTT = null;
        }
        else {
            $tempTT = TransportType::select('id as val')->find(intval($data['t']['type']));
        }

        $tt = $tempTT == null ? TransportType::select('id as val')->find($tempTTID) : $tempTT;
        $this->verifiedData['transport_type'] = $tt;

        if(!isset($data['t'])){
            $this->analizeTransportAlias();
        }

        return true;
    }

    private function validateRbmyMain($data){
        $this->verifiedData['staticRbmy'] = [
            'manufactureRegions' => ManufactureCountry::select(['id as val', $this->langTitle . ' as name'])->get(),
            'brands' => $this->verifiedData['transport_type']->brands()->select('brands.id as val', 'title as name', 'manufacture_id as manufacture')->get(),
        ];
        if(!isset($data['rbmy'])){
            $this->analizeRbmyAlias();
        }
        return true;
    }

    private function validateRegionMain($data){
        $regions = [];
        $selecting_query = ['id as val', $this->langTitle . ' as name'];

        foreach (UkrainianRegionPart::select($selecting_query)->get() as $urp){
            $region_part = [];
            foreach ($urp->regions()->select($selecting_query)->get() as $ur){
                $ur->children = $ur->cities()->select($selecting_query)->orderBy('created_at', 'asc')->get();
                array_push($region_part, $ur);
            }
            $urp->children = $region_part;
            array_push($regions, $urp);
        }

        $this->verifiedData['staticRegion'] = $regions;

        if(!isset($data['r'])){
            $this->analizeRegionAlias();
        }
        return true;
    }


    private function analizeSearchDetailAlias($uri = []){
        $searchDetailFullStores = [];

        $searchPropsChoosed = [];
//        $searchPropsChoosed['fullResource'] = isset($uri['fr']) && boolval($uri['fr']);
//        $searchPropsChoosed['verifiedAuto'] = isset($uri['va']) && boolval($uri['va']);
        $searchPropsChoosed['withPhoto'] = isset($uri['wph']) ? false : true;

        $searchPropsChoosed['abroad'] = isset($uri['ab']) ? $this->bollStr($uri['ab']) : false;
        $searchPropsChoosed['credit'] = isset($uri['cr']) ? $this->bollStr($uri['cr']) : false;
        $searchPropsChoosed['customsСleared'] = isset($uri['cc']) ? $this->bollStr($uri['cc']) : false;
        $searchPropsChoosed['accident'] = isset($uri['acc']) ? $this->bollStr($uri['acc']) : false; // принудительный запрет
        $searchPropsChoosed['noMotion'] = isset($uri['nom']) ? $this->bollStr($uri['nom']) : false; // принудительный запрет

        $searchPropsChoosed['bargain'] = isset($uri['b']) && $this->bollStr($uri['b']);
        $searchPropsChoosed['exchange'] = isset($uri['e']) && $this->bollStr($uri['e']);

        $priceChoosed = [];
        $priceChoosed['currency'] = $this->verifiedData['curr'];
        $priceChoosed['from'] = (isset($uri['priceF']) && intval($uri['priceF']) !== 0) ? intval(substr($uri['priceF'], 0,10)) : null;
        $priceChoosed['to'] = (isset($uri['priceT']) && intval($uri['priceT']) !== 0) ? intval(substr($uri['priceT'], 0,5)) : null;

        if($priceChoosed['from'] > $priceChoosed['to'] && $priceChoosed['to'] !== null){
            $tempFrom = $priceChoosed['from'];
            $priceChoosed['from'] = $priceChoosed['to'];
            $priceChoosed['to'] = $tempFrom;
        }
        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'autoConditionChoosed', $this->verifiedData['autoCond']);
        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'searchPropsChoosed', $searchPropsChoosed);
        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'priceChoosed', $priceChoosed);

        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'sortingChoosed', $this->verifiedData['sorting']);
        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'systemSorting', SystemSorting::select(['id as val', 'rtitle as name'])->get());

        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'periodChoosed', $this->verifiedData['period']);
        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'systemPeriod', SystemPeriod::select(['id as val', 'rtitle as name'])->get());

        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'relevanceChoosed', $this->verifiedData['relevance']);
        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'systemRelevance', SystemRelevance::select(['id as val', 'rtitle as name'])->get());

        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'showChoosed', $this->verifiedData['show']);
        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'systemShow', SystemShow::select(['id as val', 'rtitle as name'])->get());

        $searchDetailFullStores = Arr::add($searchDetailFullStores, 'page', $this->verifiedData['page']);

        $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'searchDetailFullStore', $searchDetailFullStores);
    }

    private function analizeTransportAlias($uri = []){
        $transportFullStores = [];
        $transportFullStores = Arr::add($transportFullStores, 'typeChoosed', $this->verifiedData['transport_type']->val);
        $transportFullStores = Arr::add($transportFullStores, 'transportTypes', TransportType::select(['id as val', 'rtitle as name'])->get());
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
        $bodies = $this->verifiedData['transport_type']->bodies()->select('id as val', 'rtitle as name')->get();
        $transportFullStores = Arr::add($transportFullStores, 'transportBodies', $bodies);

        if(isset($uri['colors'])){
            $colorsArr = [];
            foreach ($uri['colors'] as $color){
                array_push($colorsArr, intval($color));
            }
            $transportFullStores = Arr::add($transportFullStores, 'colorsChoosed', $colorsArr);
        }
        else{
            $transportFullStores = Arr::add($transportFullStores, 'colorsChoosed', []);
        }
        $transportFullStores = Arr::add($transportFullStores, 'transportColors', TransportChColor::select(['id as val', 'rtitle as name', 'color', 'bg_color as bg'])->get());


        if(isset($uri['imp'])){
            $importerArr = [];
            foreach ($uri['imp'] as $importer){
                array_push($importerArr, intval($importer));
            }
            $transportFullStores = Arr::add($transportFullStores, 'importersChoosed', $importerArr);
        }
        else{
            $transportFullStores = Arr::add($transportFullStores, 'importersChoosed', []);
        }

        if(isset($uri['states'])){
            $stateArr = [];
            foreach ($uri['states'] as $state){
                array_push($stateArr, intval($state));
            }
            $transportFullStores = Arr::add($transportFullStores, 'statesChoosed', $stateArr);
        }
        else{
            $transportFullStores = Arr::add($transportFullStores, 'statesChoosed', []);
        }
        $transportFullStores = Arr::add($transportFullStores, 'transportStates', TransportChState::select(['id as val', 'rtitle as name'])->get());

        if(isset($uri['fuels'])){
            $fuelsArr = [];
            foreach ($uri['fuels'] as $fuel){
                array_push($fuelsArr, intval($fuel));
            }
            $transportFullStores = Arr::add($transportFullStores, 'fuelsChoosed', $fuelsArr);
        }
        else{
            $transportFullStores = Arr::add($transportFullStores, 'fuelsChoosed', []);
        }
        $transportFullStores = Arr::add($transportFullStores, 'transportFuels', TransportChFuel::select(['id as val', 'rtitle as name'])->get());

        $fuelConsumptionChoosed = [];
        $fuelConsumptionChoosed['from'] = isset($uri['fuelsF']) ? floatval(substr($uri['fuelsF'], 0,5)) : null;
        $fuelConsumptionChoosed['to'] = isset($uri['fuelsT']) ? floatval(substr($uri['fuelsT'], 0,5)) : null;
        if($fuelConsumptionChoosed['from'] > $fuelConsumptionChoosed['to'] && $fuelConsumptionChoosed['to'] !== null){
            $tempFrom = $fuelConsumptionChoosed['from'];
            $fuelConsumptionChoosed['from'] = $fuelConsumptionChoosed['to'];
            $fuelConsumptionChoosed['to'] = $tempFrom;
        }
        $transportFullStores = Arr::add($transportFullStores, 'fuelConsumptionChoosed', $fuelConsumptionChoosed);
        $mileageChoosed = [];
        $mileageChoosed['from'] = isset($uri['mileageF']) ? intval(substr($uri['mileageF'], 0,5)) : null;
        $mileageChoosed['to'] = isset($uri['mileageT']) ? intval(substr($uri['mileageT'], 0,5)) : null;
        if($mileageChoosed['from'] > $mileageChoosed['to'] && $mileageChoosed['to'] !== null){
            $tempFrom = $mileageChoosed['from'];
            $mileageChoosed['from'] = $mileageChoosed['to'];
            $mileageChoosed['to'] = $tempFrom;
        }
        $transportFullStores = Arr::add($transportFullStores, 'mileageChoosed', $mileageChoosed);

        if(isset($uri['trans'])){
            $transmissionArr = [];
            foreach ($uri['trans'] as $transmission){
                array_push($transmissionArr, intval($transmission));
            }
            $transportFullStores = Arr::add($transportFullStores, 'transmissionsChoosed', $transmissionArr);
        }
        else{
            $transportFullStores = Arr::add($transportFullStores, 'transmissionsChoosed', []);
        }
        $transportFullStores = Arr::add($transportFullStores, 'transportTransmissions', $this->verifiedData['transport_type']->transmissions()->select('tranport_ch_transmissions.id as val', 'rtitle as name')->get());

        $volumeChoosed = [];
        $volumeChoosed['from'] = isset($uri['volF']) ? floatval(substr($uri['volF'], 0,5)) : null;
        $volumeChoosed['to'] = isset($uri['volT']) ? floatval(substr($uri['volT'], 0,5)) : null;
        if($volumeChoosed['from'] > $volumeChoosed['to'] && $volumeChoosed['to'] !== null){
            $tempFrom = $volumeChoosed['from'];
            $volumeChoosed['from'] = $volumeChoosed['to'];
            $volumeChoosed['to'] = $tempFrom;
        }
        $transportFullStores = Arr::add($transportFullStores, 'volumeChoosed', $volumeChoosed);

        $doorsChoosed = [];
        $doorsChoosed['from'] = isset($uri['doorsF']) ? intval(substr($uri['doorsF'], 0,5)) : null;
        $doorsChoosed['to'] = isset($uri['doorsT']) ? intval(substr($uri['doorsT'], 0,5)) : null;
        if($doorsChoosed['from'] > $doorsChoosed['to'] && $doorsChoosed['to'] !== null){
            $tempFrom = $doorsChoosed['from'];
            $doorsChoosed['from'] = $doorsChoosed['to'];
            $doorsChoosed['to'] = $tempFrom;
        }
        $transportFullStores = Arr::add($transportFullStores, 'doorsChoosed', $doorsChoosed);

        if(isset($uri['gears'])){
            $gearsArr = [];
            foreach ($uri['gears'] as $gear){
                array_push($gearsArr, intval($gear));
            }
            $transportFullStores = Arr::add($transportFullStores, 'gearsChoosed', $gearsArr);
        }
        else{
            $transportFullStores = Arr::add($transportFullStores, 'gearsChoosed', []);
        }
        $transportFullStores = Arr::add($transportFullStores, 'transportGears', $this->verifiedData['transport_type']->gears()->select('tranport_ch_gears.id as val', 'rtitle as name')->get());

        $powerChoosed = [];
        $powerChoosed['from'] = isset($uri['powF']) ? floatval(substr($uri['powF'], 0,5)) : null;
        $powerChoosed['to'] = isset($uri['powT']) ? floatval(substr($uri['powT'], 0,5)) : null;
        if($powerChoosed['from'] > $powerChoosed['to'] && $powerChoosed['to'] !== null){
            $tempFrom = $powerChoosed['from'];
            $powerChoosed['from'] = $powerChoosed['to'];
            $powerChoosed['to'] = $tempFrom;
        }
        $transportFullStores = Arr::add($transportFullStores, 'powerChoosed', $powerChoosed);

        $seatsChoosed = [];
        $seatsChoosed['from'] = isset($uri['seatsF']) ? intval(substr($uri['seatsF'], 0,5)) : null;
        $seatsChoosed['to'] = isset($uri['seatsT']) ? intval(substr($uri['seatsT'], 0,5)) : null;
        if($seatsChoosed['from'] > $seatsChoosed['to'] && $seatsChoosed['to'] !== null){
            $tempFrom = $seatsChoosed['from'];
            $seatsChoosed['from'] = $seatsChoosed['to'];
            $seatsChoosed['to'] = $tempFrom;
        }
        $transportFullStores = Arr::add($transportFullStores, 'seatsChoosed', $seatsChoosed);



        $techsChoosed = [];
        if(isset($uri['secur'])){
            $techsChoosed['security'] = [];
            foreach ($uri['secur'] as $security){
                array_push($techsChoosed['security'], intval($security));
            }
        }
        else{
            $techsChoosed['security'] = [];
        }
        if(isset($uri['comf'])){
            $techsChoosed['comfort'] = [];
            foreach ($uri['comf'] as $security){
                array_push($techsChoosed['comfort'], intval($security));
            }
        }
        else{
            $techsChoosed['comfort'] = [];
        }
        if(isset($uri['mult'])){
            $techsChoosed['multimedia'] = [];
            foreach ($uri['mult'] as $security){
                array_push($techsChoosed['multimedia'], intval($security));
            }
        }
        else{
            $techsChoosed['multimedia'] = [];
        }
        if(isset($uri['oth'])){
            $techsChoosed['others'] = [];
            foreach ($uri['oth'] as $security){
                array_push($techsChoosed['others'], intval($security));
            }
        }
        else{
            $techsChoosed['others'] = [];
        }

        $transportFullStores = Arr::add($transportFullStores, 'techsChoosed', $techsChoosed);


        $transportTechs['security'] = $this->verifiedData['transport_type']->techs()->select('transport_ch_teches.id as val', 'rtitle as name')->where('type', 'security')->get();
        $transportTechs['comfort'] = $this->verifiedData['transport_type']->techs()->select('transport_ch_teches.id as val', 'rtitle as name')->where('type', 'comfort')->get();
        $transportTechs['multimedia'] = $this->verifiedData['transport_type']->techs()->select('transport_ch_teches.id as val', 'rtitle as name')->where('type', 'multimedia')->get();
        $transportTechs['others'] = $this->verifiedData['transport_type']->techs()->select('transport_ch_teches.id as val', 'rtitle as name')->where('type', 'others')->get();
        $transportFullStores = Arr::add($transportFullStores, 'transportTechs', $transportTechs);

        $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'transportFullStore', $transportFullStores);
    }

    private function analizeRbmyAlias($uri = []){
        $rbmyFullStores = [];
        if(!empty($uri)){
            foreach ($uri as $k => $rbmy){
                $rbmyFullStore = [];
                if(isset($rbmy['r'])){
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'regionChoose', intval($rbmy['r']));
                    $brands = $this->verifiedData['transport_type']->brands()->where('manufacture_id', intval($rbmy['r']))->select(['brands.id as val', 'title as name'])->get();
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'brands', $brands);
                }
                else{
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'regionChoose', null);
                    $brands = [];
                }
                $rbmyFullStore = Arr::add($rbmyFullStore, 'brands', $brands);

                if(isset($rbmy['b'])){
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'brandChoose', intval($rbmy['b']));
                    $brand = Brand::select(['brands.id as val', 'title as name'])->find($rbmy['b']);
                    $models = $brand->modelsWithTransportType($this->verifiedData['transport_type']->val)->select(['id as val', 'title as name'])->get();
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'models', $models);
                }
                else{
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'brandChoose', null);
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'models', []);
                }
                if(isset($rbmy['m']) && isset($rbmy['m'])){
                    $modelArr = [];
                    foreach ($rbmy['m'] as $model){
                        array_push($modelArr, intval($model));
                    }
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'modelsChoose', $modelArr);
                }
                else{
                    $rbmyFullStore = Arr::add($rbmyFullStore, 'modelsChoose', []);
                }

                $yearForm = isset($rbmy['f']) ? intval($rbmy['f']) : null;
                $yearTo = isset($rbmy['t']) ? intval($rbmy['t']) : null;
                if($yearForm > $yearTo && $yearTo !== null){
                    $tempYear = $yearForm;
                    $yearForm = $yearTo;
                    $yearTo = $tempYear;
                }
                $rbmyFullStore = Arr::add($rbmyFullStore, 'yearFrom', $yearForm);
                $rbmyFullStore = Arr::add($rbmyFullStore, 'yearTo', $yearTo);
                array_push($rbmyFullStores, $rbmyFullStore);

            }
            $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'rbmyFullStore', ['rbmys' => $rbmyFullStores, 'static' => $this->verifiedData['staticRbmy']]);
        } else{
            $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'rbmyFullStore', ['static' => $this->verifiedData['staticRbmy']]);
        }
    }

    private function analizeRegionAlias($uri = []){
        $regionFullStores = [];
        if(!empty($uri)){
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

            $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'regionFullStore', ['regions' => $regionFullStores, 'static' => $this->verifiedData['staticRegion']]);
        } else{
            $this->jSON_RESPONSE = Arr::add($this->jSON_RESPONSE, 'regionFullStore', ['static' => $this->verifiedData['staticRegion']]);
        }
    }

    public function bollStr($str){
        return $str == 'true' ? true : false;
    }

}
