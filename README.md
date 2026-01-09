# CodeIgniter 4 HMVC support by FMS

[![Official CodeIgniter](https://img.shields.io/badge/Official_Website-Visit-yellow)](https://www.codeigniter.com) 

### Prerequisites
1. PHP 8.2 or above
2. Composer required
3. CodeIgniter 4.6.4

### Installation Guide

Clone project to your project root folder
```bash
git clone https://github.com/faizmsyam/codeigniter4-hmvc.git ci4_hmvc_fms
```
Then
```bash
cd ci4_hmvc_fms
```

Copy some require file to root folder (Upgrading to v4.6.4)
```bash
composer update
cp vendor/codeigniter4/framework/public/index.php public/index.php
cp vendor/codeigniter4/framework/spark spark
```

Copy `env` file
```bash
cp env .env
```

Run the app, using different port, add options `--port=9000`
```bash
php spark serve
```

---
## Module Commands
```bash
php spark make:module [module] 
```

### Example
Create a home module
```bash
php spark make:module Home
```

###  Result Directory
    App 
    ├── Config       
    │   └── Routes.php (Added group called home)
    ├── Modules      
    │   └── Home
    │       ├──  Controllers
    │           └──  HomeController.php
    │       ├──  Models
    │           └──  HomeModel.php
    │       └──  Views
    │           └──  index.php
    └── ...  

### Route Group
After generate `Home` Module, add a route group for the module at `App\Config\Routes.php`
```php
$routes->group(
    'home', ['namespace' => 'App\Modules\Home\Controllers'], function ($routes) {
        $routes->get('/', 'Home::index');
    }
);
```
