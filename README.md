<h1 align="center">Welcome to lang2js 👋</h1>
<p>
  <img alt="Version" src="https://img.shields.io/badge/version-0.0.1-blue.svg?cacheSeconds=2592000" />
  <a href="https://github.com/developersunesis/lang2js#readme" target="_blank">
    <img alt="Documentation" src="https://img.shields.io/badge/documentation-yes-brightgreen.svg" />
  </a>
  <a href="#" target="_blank">
    <img alt="License: MIT" src="https://img.shields.io/badge/License-MIT-yellow.svg" />
  </a>
  <a href="https://twitter.com/developrsunesis" target="_blank">
    <img alt="Twitter: developrsunesis" src="https://img.shields.io/twitter/follow/developrsunesis.svg?style=social" />
  </a>
</p>

> A package that provides an easy way to export and sync Laravel localization files for JavaScript use
## Problem
I have a Laravel project/website, while thinking of how to sync my localization and translation files from the app with
for JavaScript usage; my first approach was to have the localization content stored in the `localStorage` of the browser 
when a user first visits the app. While this was a goto solution, I realized this wasn't efficient enough, because it meant
that the first page the user visits might not have its strings translated until the localization is available 
already in the `localStorage`. Another solution, was to inject the localization content directly into a `DOMElement`: 
`<data id='lang' value="{{getAllLangContent()}}" />`, clearly there was a trade-off as this drastically increased the page load time but solves the
problem of the translations not being available.

#### Final Solution
My final solution which is currently in use was to have a package periodically sync the localization files for JavaScript use.
The package reads the following files:
```
resources
├── lang
│   ├── en
│   │   ├── auth.php
│   │   └── dashboard.php
│   ├── fr
│   │   ├── auth.php
│   │   └── dashboard.php
```
and converts it to minified js files
```
public
├── js
│   ├── locales
│   │   ├── en.min.js
│   │   ├── fr.min.js
│   │   └── lang2js.min.js
```
So each locale that needs to be used is imported into my blade component 
```html
...
<footer>
    <script src="{{assets('js/locales/en.min.js')}}"></script>
    <script src="{{assets('js/locales/fr.min.js')}}"></script>
    <script src="{{assets('js/locales/lang2js.min.js')}}"></script>
    <script>
       let helloText = __("index.TEST_2", 'en') // this function is provided by `lang2js.min.js`
       document.getElementById("hellotext").innerHTML = helloText
    </script>
</footer>
...
```

## Install

```sh
composer require developersunesis/lang2js
```

## Usage
You can simply run a command
```sh
php artisan lang2js:export exportDir=:exportDir
```
The command above reads the translation files from Laravel default lang folder.
<br/><br/>But if you have a custom location you want the translation files to be read from, you can use the following
```shell
php artisan lang2js:export exportDir=:exportPath localesDir=:localesPath
```
The two commands above uses the base path of the app and the path you specified as their absolute path.
<br/>Example:
```shell
php artisan lang2js:export exportDir=/public/js/locales localesDir=/resources/lang

# Uses full path
# exportDir == {YOUR_CURRENT_APP_LOCATION}/public/js/locales
# localesDir == {YOUR_CURRENT_APP_LOCATION}/public/resources/lang
```
To disable to command from using your base app file, you can add an option to the command as below
```shell
php artisan lang2js:export exportDir=C:/manners/Documents/public/js/locales localesDir=C:/manners/Documents/resources/lang --useBasePath=false
```
There are various use cases, one of which is to create a schedule for the package to resync the JavaScript translations
periodically, this is very useful if you make use of laravel localizations that can be dynamically changed
```injectablephp
$command = "php artisan lang2js:export exportDir=/public/js/locales"
$schedule->command($command)
          ->weekdays()
          ->daily();

# or through a facade function call
$schedule->call(function () {
    $lang2js = new Lang2js();
    $lang2js->setExportsDir("resources/exports");
    $lang2js->export();
})->weekly()->daily();

# or through a facade function call
$schedule->call(function () {
    L2J::setExportsDir("/public/js/locales")->export();
})->weekly()->daily();
```

## Author

👤 **Uche Emmanuel**

* Website: https://developersunesis.com
* Twitter: [@developrsunesis](https://twitter.com/developrsunesis)
* Github: [@developersunesis](https://github.com/developersunesis)
* LinkedIn: [@developersunesis](https://linkedin.com/in/developersunesis)

## 🤝 Contributing

Contributions, issues and feature requests are welcome!<br />Feel free to check [issues page](https://github.com/developersunesis/lang2js/issues). 

## Show your support

Give a ⭐️ if this project helped you!

***
_This README was generated with ❤️ by [readme-md-generator](https://github.com/kefranabg/readme-md-generator)_