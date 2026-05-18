<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, yet powerful, providing tools needed for large, robust applications. A superb combination of simplicity, elegance, and innovation give you tools you need to build any application with which you are tasked.

## Learning Laravel

Laravel has the most extensive and thorough documentation and video tutorial library of any modern web application framework. The [Laravel documentation](https://laravel.com/docs) is thorough, complete, and makes it a breeze to get started learning the framework.

If you're not in the mood to read, [Laracasts](https://laracasts.com) contains over 900 video tutorials on a range of topics including Laravel, modern PHP, unit testing, JavaScript, and more. Boost the skill level of yourself and your entire team by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for helping fund on-going Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](http://patreon.com/taylorotwell):

- **[Vehikl](http://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[British Software Development](https://www.britishsoftware.co)**
- **[Styde](https://styde.net)**
- [Fragrantica](https://www.fragrantica.com)
- [SOFTonSOFA](https://softonsofa.com/)

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](http://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

```
inventori_umum
├─ app
│  ├─ BonDetail.php
│  ├─ BonHeader.php
│  ├─ Category.php
│  ├─ Console
│  │  └─ Kernel.php
│  ├─ Department.php
│  ├─ DepartmentGroup.php
│  ├─ DepartmentItemQuota.php
│  ├─ Exceptions
│  │  └─ Handler.php
│  ├─ Http
│  │  ├─ Controllers
│  │  │  ├─ Auth
│  │  │  │  ├─ ForgotPasswordController.php
│  │  │  │  ├─ RegisterController.php
│  │  │  │  └─ ResetPasswordController.php
│  │  │  ├─ BonController.php
│  │  │  ├─ BufferAlertController.php
│  │  │  ├─ Controller.php
│  │  │  ├─ DashboardController.php
│  │  │  ├─ DepartmentController.php
│  │  │  ├─ DepartmentToolsController.php
│  │  │  ├─ HomeController.php
│  │  │  ├─ ImportController.php
│  │  │  ├─ InventoryMonthlyReportController.php
│  │  │  ├─ ItemController.php
│  │  │  ├─ ItemToolsController.php
│  │  │  ├─ LpbController.php
│  │  │  ├─ LpbQuotaController.php
│  │  │  ├─ OpnameController.php
│  │  │  ├─ ReportController.php
│  │  │  ├─ RequestController.php
│  │  │  └─ StockOpnameController.php
│  │  ├─ Kernel.php
│  │  └─ Middleware
│  │     ├─ EncryptCookies.php
│  │     ├─ RedirectIfAuthenticated.php
│  │     ├─ TrimStrings.php
│  │     └─ VerifyCsrfToken.php
│  ├─ Item.php
│  ├─ ItemDepartmentBuffer.php
│  ├─ LpbDetail.php
│  ├─ LpbHeader.php
│  ├─ Models
│  │  ├─ Department.php
│  │  ├─ IssueDet.php
│  │  ├─ IssueHdr.php
│  │  ├─ Item.php
│  │  ├─ LPBDet.php
│  │  ├─ LPBHdr.php
│  │  ├─ Opname.php
│  │  ├─ RequestDet.php
│  │  └─ RequestHdr.php
│  ├─ Providers
│  │  ├─ AppServiceProvider.php
│  │  ├─ AuthServiceProvider.php
│  │  ├─ BroadcastServiceProvider.php
│  │  ├─ EventServiceProvider.php
│  │  └─ RouteServiceProvider.php
│  ├─ Stock.php
│  ├─ StockOpnameDetail.php
│  ├─ StockOpnameHeader.php
│  └─ User.php
├─ artisan
├─ bootstrap
│  ├─ app.php
│  ├─ autoload.php
│  └─ cache
│     └─ services.php
├─ composer.json
├─ composer.lock
├─ config
│  ├─ app.php
│  ├─ auth.php
│  ├─ broadcasting.php
│  ├─ cache.php
│  ├─ database.php
│  ├─ filesystems.php
│  ├─ mail.php
│  ├─ otto.php
│  ├─ queue.php
│  ├─ services.php
│  ├─ session.php
│  └─ view.php
├─ database
│  ├─ factories
│  │  └─ ModelFactory.php
│  ├─ migrations
│  └─ seeds
│     ├─ DatabaseSeeder.php
│     ├─ DepartmentSeeder.php
│     ├─ ItemDataSeeder.php
│     ├─ ItemExcelSeeder.php
│     └─ UserSeeder.php
├─ package.json
├─ phpunit.xml
├─ public
│  ├─ .htaccess
│  ├─ css
│  │  └─ app.css
│  ├─ excel
│  │  ├─ NAMA_KODEBRG.csv
│  │  └─ NAMA_KODEBRG_ATK.csv
│  ├─ favicon.ico
│  ├─ index.php
│  ├─ js
│  │  └─ app.js
│  ├─ robots.txt
│  └─ web.config
├─ readme.md
├─ resources
│  ├─ assets
│  │  ├─ js
│  │  │  ├─ app.js
│  │  │  ├─ bootstrap.js
│  │  │  └─ components
│  │  │     └─ Example.vue
│  │  └─ sass
│  │     ├─ app.scss
│  │     └─ _variables.scss
│  ├─ lang
│  │  └─ en
│  │     ├─ auth.php
│  │     ├─ pagination.php
│  │     ├─ passwords.php
│  │     └─ validation.php
│  └─ views
│     ├─ alerts
│     │  └─ index.blade.php
│     ├─ bons
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ index.blade.php
│     │  ├─ partials
│     │  │  └─ badge.blade.php
│     │  ├─ print.blade.php
│     │  ├─ report_per_item.blade.php
│     │  └─ show.blade.php
│     ├─ dashboard
│     │  └─ index.blade.php
│     ├─ dashboard.blade.php
│     ├─ departments
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ import.blade.php
│     │  └─ index.blade.php
│     ├─ home.blade.php
│     ├─ items
│     │  ├─ buffer_alerts.blade.php
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ import.blade.php
│     │  ├─ index.blade.php
│     │  ├─ show.blade.php
│     │  └─ _form.blade.php
│     ├─ layouts
│     │  └─ app.blade.php
│     ├─ lpbs
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ index.blade.php
│     │  ├─ print.blade.php
│     │  └─ quotas.blade.php
│     ├─ partials
│     │  ├─ flash.blade.php
│     │  ├─ navbar.blade.php
│     │  └─ sidebar.blade.php
│     ├─ reports
│     │  ├─ department_usage.blade.php
│     │  ├─ index.blade.php
│     │  ├─ inventory_monthly.blade.php
│     │  ├─ saldo.blade.php
│     │  └─ stock_card.blade.php
│     ├─ stock_opnames
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ index.blade.php
│     │  └─ show.blade.php
│     └─ welcome.blade.php
├─ routes
│  ├─ api.php
│  ├─ channels.php
│  ├─ console.php
│  └─ web.php
├─ server.php
├─ storage
│  ├─ app
│  │  └─ public
│  │     ├─ 3. P. UMUM OKT'25 UP. TEH SHINTA.xlsx
│  │     └─ ATK P4 SEPT '25.xlsx
│  ├─ framework
│  │  ├─ cache
│  │  │  └─ data
│  │  │     └─ 01
│  │  │        └─ 23
│  │  │           └─ 0123fdb1e3a27b68d6a6d2154962a8c0662f98b6
│  │  ├─ sessions
│  │  │  └─ QlSlIlyQ1GZ4FCCegkBsLvrdbhryrTRZarEkn4hc
│  │  ├─ testing
│  │  └─ views
│  │     ├─ 5c72ef71ce0046e6c4bacf0e042b062cf7b22fed.php
│  │     ├─ f56b7a44d9db6ff4f94d6cbb9862910a58b62c88.php
│  │     ├─ f5cea2d42695994bf332e94eb09b1347af7e1737.php
│  │     ├─ f8e549b6c2123ce34541efdb2a6da633c0894020.php
│  │     ├─ feaa8b017af4e3577a87144881e06d7b1e295bd9.php
│  │     └─ fecb5377b5133c91810d23950d0786676c2a1c1a.php
│  └─ logs
│     └─ laravel.log
├─ struktur.txt
├─ tests
│  ├─ CreatesApplication.php
│  ├─ Feature
│  │  └─ ExampleTest.php
│  ├─ TestCase.php
│  └─ Unit
│     └─ ExampleTest.php
└─ webpack.mix.js

```
```
inventori_umum
├─ app
│  ├─ BonDetail.php
│  ├─ BonHeader.php
│  ├─ Category.php
│  ├─ Console
│  │  └─ Kernel.php
│  ├─ Department.php
│  ├─ DepartmentGroup.php
│  ├─ DepartmentItemQuota.php
│  ├─ Exceptions
│  │  └─ Handler.php
│  ├─ Http
│  │  ├─ Controllers
│  │  │  ├─ Auth
│  │  │  │  ├─ ForgotPasswordController.php
│  │  │  │  ├─ RegisterController.php
│  │  │  │  └─ ResetPasswordController.php
│  │  │  ├─ BonController.php
│  │  │  ├─ BufferAlertController.php
│  │  │  ├─ Controller.php
│  │  │  ├─ DashboardController.php
│  │  │  ├─ DepartmentController.php
│  │  │  ├─ DepartmentToolsController.php
│  │  │  ├─ HomeController.php
│  │  │  ├─ ImportController.php
│  │  │  ├─ InventoryMonthlyReportController.php
│  │  │  ├─ ItemController.php
│  │  │  ├─ ItemToolsController.php
│  │  │  ├─ LpbController.php
│  │  │  ├─ LpbQuotaController.php
│  │  │  ├─ OpnameController.php
│  │  │  ├─ ReportController.php
│  │  │  ├─ RequestController.php
│  │  │  └─ StockOpnameController.php
│  │  ├─ Kernel.php
│  │  └─ Middleware
│  │     ├─ EncryptCookies.php
│  │     ├─ RedirectIfAuthenticated.php
│  │     ├─ TrimStrings.php
│  │     └─ VerifyCsrfToken.php
│  ├─ Item.php
│  ├─ ItemDepartmentBuffer.php
│  ├─ LpbDetail.php
│  ├─ LpbHeader.php
│  ├─ Models
│  │  ├─ Department.php
│  │  ├─ IssueDet.php
│  │  ├─ IssueHdr.php
│  │  ├─ Item.php
│  │  ├─ LPBDet.php
│  │  ├─ LPBHdr.php
│  │  ├─ Opname.php
│  │  ├─ RequestDet.php
│  │  └─ RequestHdr.php
│  ├─ Providers
│  │  ├─ AppServiceProvider.php
│  │  ├─ AuthServiceProvider.php
│  │  ├─ BroadcastServiceProvider.php
│  │  ├─ EventServiceProvider.php
│  │  └─ RouteServiceProvider.php
│  ├─ Stock.php
│  ├─ StockOpnameDetail.php
│  ├─ StockOpnameHeader.php
│  └─ User.php
├─ artisan
├─ bootstrap
│  ├─ app.php
│  ├─ autoload.php
│  └─ cache
│     └─ services.php
├─ composer.json
├─ composer.lock
├─ config
│  ├─ app.php
│  ├─ auth.php
│  ├─ broadcasting.php
│  ├─ cache.php
│  ├─ database.php
│  ├─ filesystems.php
│  ├─ mail.php
│  ├─ otto.php
│  ├─ queue.php
│  ├─ services.php
│  ├─ session.php
│  └─ view.php
├─ database
│  ├─ factories
│  │  └─ ModelFactory.php
│  ├─ migrations
│  └─ seeds
│     ├─ DatabaseSeeder.php
│     ├─ DepartmentSeeder.php
│     ├─ ItemDataSeeder.php
│     ├─ ItemExcelSeeder.php
│     └─ UserSeeder.php
├─ package.json
├─ phpunit.xml
├─ public
│  ├─ .htaccess
│  ├─ css
│  │  └─ app.css
│  ├─ excel
│  │  ├─ NAMA_KODEBRG.csv
│  │  └─ NAMA_KODEBRG_ATK.csv
│  ├─ favicon.ico
│  ├─ index.php
│  ├─ js
│  │  └─ app.js
│  ├─ robots.txt
│  └─ web.config
├─ readme.md
├─ resources
│  ├─ assets
│  │  ├─ js
│  │  │  ├─ app.js
│  │  │  ├─ bootstrap.js
│  │  │  └─ components
│  │  │     └─ Example.vue
│  │  └─ sass
│  │     ├─ app.scss
│  │     └─ _variables.scss
│  ├─ lang
│  │  └─ en
│  │     ├─ auth.php
│  │     ├─ pagination.php
│  │     ├─ passwords.php
│  │     └─ validation.php
│  └─ views
│     ├─ alerts
│     │  └─ index.blade.php
│     ├─ bons
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ index.blade.php
│     │  ├─ partials
│     │  │  └─ badge.blade.php
│     │  ├─ print.blade.php
│     │  ├─ report_per_item.blade.php
│     │  └─ show.blade.php
│     ├─ dashboard
│     │  └─ index.blade.php
│     ├─ dashboard.blade.php
│     ├─ departments
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ import.blade.php
│     │  └─ index.blade.php
│     ├─ home.blade.php
│     ├─ items
│     │  ├─ buffer_alerts.blade.php
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ import.blade.php
│     │  ├─ index.blade.php
│     │  ├─ show.blade.php
│     │  └─ _form.blade.php
│     ├─ layouts
│     │  └─ app.blade.php
│     ├─ lpbs
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ index.blade.php
│     │  ├─ print.blade.php
│     │  └─ quotas.blade.php
│     ├─ partials
│     │  ├─ flash.blade.php
│     │  ├─ navbar.blade.php
│     │  └─ sidebar.blade.php
│     ├─ reports
│     │  ├─ department_usage.blade.php
│     │  ├─ index.blade.php
│     │  ├─ inventory_monthly.blade.php
│     │  ├─ saldo.blade.php
│     │  └─ stock_card.blade.php
│     ├─ stock_opnames
│     │  ├─ create.blade.php
│     │  ├─ edit.blade.php
│     │  ├─ index.blade.php
│     │  └─ show.blade.php
│     └─ welcome.blade.php
├─ routes
│  ├─ api.php
│  ├─ channels.php
│  ├─ console.php
│  └─ web.php
├─ server.php
├─ storage
│  ├─ app
│  │  └─ public
│  │     ├─ 3. P. UMUM OKT'25 UP. TEH SHINTA.xlsx
│  │     └─ ATK P4 SEPT '25.xlsx
│  ├─ framework
│  │  ├─ cache
│  │  │  └─ data
│  │  │     └─ 01
│  │  │        └─ 23
│  │  │           └─ 0123fdb1e3a27b68d6a6d2154962a8c0662f98b6
│  │  ├─ sessions
│  │  │  └─ QlSlIlyQ1GZ4FCCegkBsLvrdbhryrTRZarEkn4hc
│  │  ├─ testing
│  │  └─ views
│  │     ├─ 5c72ef71ce0046e6c4bacf0e042b062cf7b22fed.php
│  │     ├─ f56b7a44d9db6ff4f94d6cbb9862910a58b62c88.php
│  │     ├─ f5cea2d42695994bf332e94eb09b1347af7e1737.php
│  │     ├─ f8e549b6c2123ce34541efdb2a6da633c0894020.php
│  │     ├─ feaa8b017af4e3577a87144881e06d7b1e295bd9.php
│  │     └─ fecb5377b5133c91810d23950d0786676c2a1c1a.php
│  └─ logs
│     └─ laravel.log
├─ struktur.txt
├─ tests
│  ├─ CreatesApplication.php
│  ├─ Feature
│  │  └─ ExampleTest.php
│  ├─ TestCase.php
│  └─ Unit
│     └─ ExampleTest.php
└─ webpack.mix.js

```