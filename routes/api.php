<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('jwt.auth')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware' => ['jwt.auth']], function() {
  Route::get('user', function (Request $request) {return $request->user();});
//   Route::get('check', 'AuthController@check');// add new authenticated routes here
//   Route::post('stringstore', 'AuthController@addStringStore');
});

Route::post('register', 'Auth\RegisterController@register');// add new "open" routes here
Route::post('login', 'Auth\LoginController@login');
