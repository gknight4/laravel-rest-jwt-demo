This tutorial makes very minimal changes to the default Laravel project to implement a simple, JSON Web Token secured, ReSTful interface.

One of my pet peeves is tutorials that can't be followed exactly. Maybe versions have changed, or maybe the author had software installed on her system that I don't have on mine, or he left out steps, or there are typos. For whatever reason, I get partway through the tutorial, and the situation that I have is *not* what the tutorial writer had in mind. Now, mind you, *after* I've 
gotten through a tutorial or two on the subject, I can see what the problem was, and fix it. But the reason I'm following the tutorial is because I DON'T KNOW THAT STUFF YET!

So, I ran these steps through a clean Docker instance, and had to install everything from scratch.

### To skip the step-by-step, or just to have it sitting alongside as you follow along, install the completed code from Git:
git clone https://github.com/gknight4/laravel-rest-jwt-demo

### use mysql to create the database and user:
mysql -uroot -p(your mysql root password)\
create database jwtdemo;\
grant all privileges on jwtdemo.* to homestead@localhost identified by 'bwXY2Xjr' ;\
quit


then:\
cd laravel-rest-jwt-demo\
composer update\
php artisan migrate

Then jump down to the "test it out in Postman" items below.

## Or, to *truly* start from scratch, fire up a clean Docker container, with a couple of open ports:

docker run -p 8000:8000 -p 8022:22 --expose 8000 --expose 8022 -it ubuntu:18.04

### note that these instructions can be followed on *any* Debian/Ubuntu machine, and don't *have* to be on most. If you already have reasonably current versions of PHP and MySQL, skip on down to "install composer".

apt-get update
### install the basics
apt-get install -y dialog software-properties-common sudo curl git nano net-tools 
### install apache2, php7.2, mysql, and openssh-server:
apt-get install -y apache2 php7.2 mysql-server openssh-server libapache2-mod-php7.2 php7.2 php7.2-xml php7.2-gd php7.2-opcache php7.2-mbstring 7.2-zip php7.2-mysql \
usermod -d /var/lib/mysql/ mysql\
service mysql start\
mysql_secure_installation
### if you want to be able to ssh into the docker instance, uncomment this line in /etc/ssh/sshd_config:
ListenAddress 0.0.0.0\
service ssh restart

### install composer:
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
### create a 'lara' user, su to it, and then install Laravel in the /home/lara directory
adduser lara
### (answer the questions), then

su lara\
composer global require "laravel/installer"

cd /home/lara
### add the laravel path
echo 'PATH="$HOME/.composer/vendor/bin:$PATH"' >> .bashrc
### start using the updated path
source .bashrc
#finally,
laravel new <app name>

### cd into the newly created <app name> directory

### edit the composer.json file, to add the tymon/jwt-auth line:
```json
    "require": {
        "php": "^7.1.3",
        "fideloper/proxy": "^4.0",
        "laravel/framework": "5.6.*",
        "laravel/tinker": "^1.0",
        "tymon/jwt-auth": "^1.0.0-rc.1"
    },
```    

### edit the .env file, to provide access to the mysql database:

DB_CONNECTION=mysql\
DB_HOST=127.0.0.1\
DB_PORT=3306\
DB_DATABASE=jwtdemo\
DB_USERNAME=homestead\
DB_PASSWORD=bwXY2Xjr 

### use mysql to create the database and user:
mysql -uroot -p(your mysql root password)\

create database jwtdemo;\
grant all privileges on jwtdemo.* to homestead@localhost identified by 'bwXY2Xjr' ;\
quit

composer update\
composer install

### run the standard migration, to create the tables that laravel wants:

php artisan migrate

this will create migrations, password_resets, and users tables.

### create a secret key for JWT:
php artisan jwt:secret

### run the server: (the --host parameter is only necessary if running in a docker container)
php artisan serve --host=0.0.0.0

### edit routes/api.php to add the register, login routes, and set 'jwt.auth' as the middleware authentication:

```php
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
```

## Register

edit app/Http/Controllers/Auth/RegisterController to add a new register function:

### add Request and Registered:
```php
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
```


### add the method:
```php
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();
        event(new Registered($user = $this->create($request->all())));
        $this->guard()->login($user);
        return $this->registered($request, $user)
                        ?:  response()->json(['message' => 'The user was registered.']);
    }
```

### modify the validator to remove the password "confirmed" requirement:
```php
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6', // |confirmed
        ]);
```

### test it out in Postman:
Method: POST\
Url: localhost:8000/api/register\
Headers:\
Content-Type: application/json\
Accept: application/json\
Body:\
{ "name": "jack", "email": "here@there.com", "password": "password"}

Response:
```json
{"message":"The user was registered."}
```

## Login

modify the app/User.php description, to implement the JWTSubject interface:

add this line:
```php
use Tymon\JWTAuth\Contracts\JWTSubject;
```

modify this line:
```php
class User extends Authenticatable implements JWTSubject
```

add these functions:
```php
    public function getJWTIdentifier(){return $this->getKey();}
    public function getJWTCustomClaims(){return [];}
```    

add the login method to app/Http/Controlles/Auth/LoginController.php,
again, this comes from the original, in AuthenticatesUsers.php:

```php
use Illuminate\Http\Request;
use JWTAuth;

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
          ->json(['message' => 'logged in'])
          ->header('Authorization', 'Bearer ' . $token);
      } else {
          return response()->json(['message' => 'no such user and password'], 401);
      }
    } catch (JWTException $e) {
      // something went wrong whilst attempting to encode the token
      return response()->json(['message' => 'login error'], 500);
    }
    $this->incrementLoginAttempts($request);
    return $this->sendFailedLoginResponse($request);
  }
```

### test it out in Postman:\
Method: POST\
Url: localhost:8000/api/login\
Headers:\
Content-Type: application/json\
Accept: application/json\
Body:\
{ "name": "jack", "email": "here@there.com", "password": "password"}

Response:
```json
{"message":"User is logged in."}
```

and the Authorization header:\
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwO...

## Authorized Request

### copy the Authorization header received from login, into the next Authorized Request:

### test it out in Postman:\
Method: GET\
Url: localhost:8000/api/user\
Headers:\
Content-Type: application/json\
Accept: application/json\
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwO...

Response:
```json
{
    "id": 3,
    "name": "jack",
    "email": "here@there.com",
    "created_at": "2018-07-14 14:30:55",
    "updated_at": "2018-07-14 14:30:55"
}
```
