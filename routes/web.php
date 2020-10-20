<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/




//Route::get('/filterparse', 'Controller@filterparse')->name('filterparse');
//Route::get('/launch', 'AGGREGATOR\Services\autoria\LaunchController@parse');
//Route::get('/aggregator', 'AGGREGATOR\Kernel\Core@startProcedure');
//Route::get('/home', 'HomeController@index')->name('home');
//Auth::routes();
//->name('m.ru.')->namespace('OLEGYERA\FrontBox')->namespace('OLEGYERA\FrontBox')

Route::get('/', 'Site\Auto\PageController@start');
Route::get('/filter', 'Site\Auto\PageController@filter')->name('filter');
Route::get('/card', 'Site\Auto\PageController@card')->name('card');
Route::get('/favorite', 'Site\Auto\PageController@favorite')->name('favorite');
Route::get('/extended', 'Site\Auto\PageController@extended')->name('extended');
Route::get('/presentation', 'Site\Auto\PageController@presentation')->name('presentation');
Route::get('/faq', 'Site\Auto\PageController@faq')->name('faq');
Route::get('/about', 'Site\Auto\PageController@about')->name('about');
Route::get('/convention', 'Site\Auto\PageController@convention')->name('convention');
Route::get('/confidentiality', 'Site\Auto\PageController@confidentiality')->name('confidentiality');
Route::get('/marks', 'Site\Auto\PageController@marks')->name('marks');
Route::get('/catbody', 'Site\Auto\PageController@catbody')->name('catbody');
Route::get('/changelog', 'Site\Auto\PageController@changelog')->name('changelog');
Route::get('/update', 'Site\Auto\PageController@update')->name('update');

//Route::get('/img/{alias}', 'Auto\PageController@generateURL')->name('lol');




//->name('m.ru.')->namespace('OLEGYERA\FrontBox')->namespace('OLEGYERA\FrontBox')
