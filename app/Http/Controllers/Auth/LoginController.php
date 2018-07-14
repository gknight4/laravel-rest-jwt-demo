<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use JWTAuth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
  public function login(Request $request)
  {
    $this->validateLogin($request);
    if ($this->hasTooManyLoginAttempts($request)) {
      $this->fireLockoutEvent($request);
      return $this->sendLockoutResponse($request);
    }
    $credentials = $request->only('name', 'email', 'password');
    try {
      // attempt to verify the credentials and create a token for the user
      if ($token = JWTAuth::attempt($credentials)) {
        return response()
          ->json(['message' => 'User is logged in.'])
          ->header('Authorization', 'Bearer ' . $token);
      } else {
          return response()->json(['message' => 'No such user and password.'], 401);
      }
    } catch (JWTException $e) {
      // something went wrong whilst attempting to encode the token
      return response()->json(['message' => 'Login error.'], 500);
    }
    $this->incrementLoginAttempts($request);
    return $this->sendFailedLoginResponse($request);
  }
}
