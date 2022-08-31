# CakePdf plugin

[![Build Status](https://img.shields.io/github/workflow/status/FriendsOfCake/CakePdf/CI/master?style=flat-square)](https://github.com/FriendsOfCake/CakePdf/actions?query=workflow%3ACI+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/friendsofcake/CakePdf.svg?style=flat-square)](https://packagist.org/packages/friendsofcake/CakePdf)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://packagist.org/packages/friendsofcake/CakePdf)

Plugin containing CakePdf lib which will use a PDF engine to convert HTML to PDF.

Engines included in the plugin:
* DomPdf (^0.8. Using ^2.0 is highly recommended as lower versions have various security vulnerabilities)
* Mpdf (^8.0.4)
* Tcpdf (^6.3)
* WkHtmlToPdf **RECOMMENDED ENGINE**

Community maintained engines:
* [PDFreactor](https://github.com/jmischer/cake-pdfreactor)


## Requirements

* One of the following render engines: DomPdf, Mpdf, Tcpdf or wkhtmltopdf
* pdftk (optional) See: http://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/


## Installation

Using [Composer](http://getcomposer.org):

```
composer require friendsofcake/cakepdf
```

CakePdf does not include any of the supported PDF engines, you need to install
the ones you intend to use yourself.

Packages for the recommend wkhtmltopdf engine can be downloaded from https://wkhtmltopdf.org/downloads.html.
DomPdf, Mpdf and Tcpdf can be installed via composer using one of the following commands:

```
composer require dompdf/dompdf
composer require tecnickcom/tcpdf
composer require mpdf/mpdf
```

## Setup

Loading the plugin using CakePHP's console:

```
./bin/cake plugin load CakePdf
```

If you plan to use [the PDF view functionality](#1-render-as-pdf-including-forced-download-in-the-browser-with-pdfview)
that automatically renders and returns the PDF for sending it to the browser,
you should also register the `pdf` extension in your `config/routes.php` file:

```php
$routes->scope('/', function (\Cake\Routing\RouteBuilder $routes) {
    $routes->setExtensions(['pdf']);
    // ...
});
```

Further setup information can be found in the usage section.

## Configuration

Use `Configure::write('CakePdf', $config);` or in controller use view builder to
set view option named `pdfConfig` (only when used with PdfView). You need to
define at least `$config['engine']`. When using CakePdf directly you can also
pass the config array to constructor. The value for engine should have the
`Plugin.ClassName` format without the Engine suffix.

Configuration options:
* engine: Engine to be used (required), or an array of engine config options
  * className: Engine class to use
  * binary: Binary file to use (Only for wkhtmltopdf)
  * cwd: current working directory (Only for wkhtmltopdf)
  * options: Engine specific options. Currently used for following engine:
    * `WkHtmlToPdfEngine`: The options are passed as CLI arguments
    * `TexToPdfEngine`: The options are passed as CLI arguments
    * `DomPdfEngine`: The options are passed to constructor of `Dompdf` class
    * `MpdfEngine`: The options are passed to constructor of `Mpdf` class
* crypto: Crypto engine to be used, or an array of crypto config options
  * className: Crypto class to use
  * binary: Binary file to use
* pageSize: Change the default size, defaults to A4
* orientation: Change the default orientation, defaults to portrait
* margin: Array or margins with the keys: bottom, left, right, top and their values
* title: Title of the document
* delay: A delay in milliseconds to wait before rendering the pdf
* windowStatus: The required window status before rendering the pdf
* encoding: Change the encoding, defaults to UTF-8
* download: Set to true to force a download, only when using PdfView
* filename: Filename for the document when using forced download

Example:
```php
Configure::write('CakePdf', [
    'engine' => 'CakePdf.WkHtmlToPdf',
    'margin' => [
        'bottom' => 15,
        'left' => 50,
        'right' => 30,
        'top' => 45
    ],
    'orientation' => 'landscape',
    'download' => true
]);
```

```php
class InvoicesController extends AppController
{
    // In your Invoices controller you could set additional configs,
    // or override the global ones:
    public function view($id = null)
    {
        $invoice = $this->Invoice->get($id);
        $this->viewBuilder()->setOption(
            'pdfConfig',
            [
                'orientation' => 'portrait',
                'filename' => 'Invoice_' . $id
            ]
        );
        $this->set('invoice', $invoice);
    }
}
```

The `engine` and `crypto` config options can also be arrays with configuration
options for the relevant class. For example:

```php
Configure::write('CakePdf', [
    'engine' => [
        'className' => 'CakePdf.WkHtmlToPdf',
        // Options usable depend on the engine used.
        'options' => [
            'print-media-type' => false,
            'outline' => true,
            'dpi' => 96,
            'cover' => [
                'url' => 'cover.html',
                'enable-smart-shrinking' => true,
            ],
            'toc' => true,
        ],

        /**
         * For Mac OS X / Linux by default the `wkhtmltopdf` binary should
         * be available through environment path or you can specify location as:
         */
        // 'binary' => '/usr/local/bin/wkhtmltopdf',

        /**
         * On Windows the engine uses the path shown below as default.
         * You NEED to use the path like old fashioned MS-DOS Paths,
         * otherwise you will get error like:
         * "WKHTMLTOPDF didn't return any data"
         */
        // 'binary' => 'C:\\Progra~1\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
    ],
]);
```

## Usage

You can use CakePdf in two ways, read carefully which one you actually need.
Many people mix both ways and don't get the expected results.


### 1: Render as PDF (including forced download) in the browser with PdfView

You can create PDF view and layout files for your controller actions and have
them automatically rendered. Place the view templates in a 'pdf' subdir, for
instance `templates/Invoices/pdf/view.php`, layouts will be in
`templates/layout/pdf/default.php`.

Make sure your `InvoicesController` class
[loads the `RequestHandler` component](http://book.cakephp.org/3.0/en/controllers/components/request-handling.html)
and browse to `http://localhost/invoices/view/1.pdf`

Additionally you can map resources by adding `Router::mapResources(['Invoices']);`
to your routes file and you can access the same document at
`http://localhost/invoices/1.pdf`.

In case you don't want to use the `pdf` extension in your URLs, you can omit
registering it in your routes configuration. Then in your controller action
specify the view class to be used:

```php
$this->viewBuilder()->setClassName('CakePdf.Pdf');
```

Instead of having the pdf rendered in browser itself you can force it to be
downloaded by using `download` option. Additionally you can specify custom filename
using `filename` options.

```php
$this->viewBuilder()->setOption(
    'pdfConfig',
    [
        'download' => true, // This can be omitted if "filename" is specified.
        'filename' => 'Invoice_' . $id // This can be omitted if you want file name based on URL.
    ]
);
```

### 2: Create PDF for email attachment, file storage etc.

You can use CakePdf lib to create raw PDF data with a view template.
The view file path would look like `templates/pdf/newsletter.php`.
Layout file path would be like `templates/layout/pdf/default.php`
Note that layouts for both usage types are within same directory, but the view
templates use different file paths Optionally you can also write the raw data to
file.

Example:
```php
$CakePdf = new \CakePdf\Pdf\CakePdf();
$CakePdf->template('newsletter', 'default');
$CakePdf->viewVars(['key' => 'value']);
// Get the PDF string returned
$pdf = $CakePdf->output();
// Or write it to file directly
$pdf = $CakePdf->write(APP . 'files' . DS . 'newsletter.pdf');
```


## Encryption

You can optionally encrypt the PDF with permissions

To use encryption you first need to select a crypto engine. Currently we support
the following crypto engines:
* Pdftk


### Usage

Add the following in your bootstrap.

```php
Configure::write('CakePdf.crypto', 'CakePdf.Pdftk');
```

Options in pdfConfig:
* protect: Set to true to enable encryption
* userPassword (optional): Set a password to open the PDF file
* ownerPassword (optional): Set the password to unlock the locked permissions
* one of the above must be present, either userPassword or ownerPassword
* permissions (optional): Define the permissions

Permissions:

By default, we deny all permissions.

To allow all permissions:

Set 'permission' to true

To allow specific permissions:

Set 'permissions' to an array with a combination of the following available permissions:
* print
* degraded_print
* modify,
* assembly,
* copy_contents,
* screen_readers,
* annotate,
* fill_in

## How to

## Ensure css, images etc. are loaded in PDF

Use absolute URLs for static assets in your view templates for PDFs.
If you use `HtmlHelper::image()`, or `HtmlHelper::css()`
make sure you have set `fullBase` option to `true`.

For example
```php
echo $this->Html->image('logo.png', ['fullBase' => true]);
echo $this->Html->css('bootstrap.css', ['fullBase' => true]);
```

If you are enable to get URLs for assets working properly, you can
try using file system paths instead for the assets.

```
<img src="<?= WWW_ROOT ?>img/logo.png" />
```

**Note:** Since v0.12.16 wkhtmltopdf requires the option `enable-local-file-access`
to be able to use local filesytem paths for assets. You can enable it by setting
`'enable-local-file-access' => true` in the engine config array.

## Get header and footer on all pages

Here are a couple of CSS based solutions you can refer to for easily
getting header footer on all PDF pages.

* https://ourcodeworld.com/articles/read/687/how-to-configure-a-header-and-footer-in-dompdf
* https://jessicaschillinger.com/print-repeating-header-browser/
