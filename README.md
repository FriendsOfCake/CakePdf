# CakePdf plugin

[![Build Status](https://travis-ci.org/FriendsOfCake/CakePdf.svg?branch=master)](https://travis-ci.org/FriendsOfCake/CakePdf)
[![License](https://poser.pugx.org/FriendsOfCake/CakePdf/license.png)](https://packagist.org/packages/FriendsOfCake/CakePdf)

Plugin containing CakePdf lib which will use a PDF engine to convert HTML to PDF.

Current engines:
* DomPdf
* Mpdf
* Tcpdf
* WkHtmlToPdf (requires additional installation) **RECOMMENDED ENGINE**


## Requirements

* PHP 5.2.8
* CakePHP 2.1+
* wkhtmltopdf (optional) See: http://code.google.com/p/wkhtmltopdf/
* pdftk (optional) See: http://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/


## Installation

_[Manual]_

1. Download this: http://github.com/friendsofcake/CakePdf/zipball/master
2. Unzip that download.
3. Copy the resulting folder to app/Plugin
4. Rename the folder you just copied to CakePdf

_[GIT Submodule]_

In your app directory type:
```bash
git submodule add git://github.com/friendsofcake/CakePdf.git Plugin/CakePdf
git submodule init
git submodule update
```

_[GIT Clone]_

In your plugin directory type
```bash
git clone git://github.com/friendsofcake/CakePdf.git CakePdf
```

_[Composer]_

In your app directory type
```bash
composer require friendsofcake/cake-pdf
```

## Setup

In `app/Config/bootstrap.php` add:
```php
CakePlugin::load('CakePdf', array('bootstrap' => true, 'routes' => true));
```


## Configuration

Use `Configure::write('CakePdf', $config);` or set Controller property `$pdfConfig` (only when used with PdfView)
You need to define at least `$config['engine']`. When using CakePdf directly you can also pass the config array to constructor.
The value for engine should have the `Plugin.ClassName` format without the Engine suffix

Configuration options:
* engine: Engine to be used (required)
* options: Engine options, this varies between Engines
* pageSize: Change the default size, defaults to A4
* orientation: Change the default orientation, defaults to potrait
* margin: Array or margins with the keys: bottom, left, right, top and their values
* title: Title of the document
* encoding: Change the encoding, defaults to UTF-8
* binary: Path to binary (WkHtmlToPdfEngine only), defaults to /usr/bin/wkhtmltopdf
* download: Set to true to force a download, only when using PdfView
* filename: Filename for the document when using forced download

Example:
```php
<?php
    // app/Config/core.php (or anywhere you want to configure)
    Configure::write('CakePdf', array(
        'engine' => 'CakePdf.WkHtmlToPdf',
        'options' => array(
            'print-media-type' => false,
            'outline' => true,
            'dpi' => 96,
            'cache-dir' => TMP,
            'viewport-size' => '1024x768',
        ),
        'margin' => array(
            'bottom' => 15,
            'left' => 50,
            'right' => 30,
            'top' => 45
        ),
        'orientation' => 'landscape',
        'download' => true,

        // WkHtmlToPdfEngine extra config
        'webroot-temp-folder' => 'cache',  // inside WWW_ROOT, webroot writeable

    ));
?>

<?php
    // app/Controller/InvoicesController.php
    class InvoicesController extends AppController {
        //in your Invoices controller you could set additional configs, or override the global ones:
        public function view($id = null) {
            $this->Invoice->id = $id;
            if (!$this->Invoice->exists()) {
                throw new NotFoundException(__('Invalid invoice'));
            }
            $this->pdfConfig = array(
                'orientation' => 'portrait',
                'filename' => 'Invoice_' . $id
            );
            $this->set('invoice', $this->Invoice->read(null, $id));
        }
    }
?>

<?php
    // app/View/Layouts/pdf/default.php
    $this->pdfOptions([

        // header (only supported in WkHtmlToPdf)
        'header-spacing' => 8,
        'header-html' => sprintf('
          <table style="width: 100%%; border: 0; vertical-align: middle; font-size: 13px; color: #666666; margin: 0; padding: 0;" cellpadding="0" cellspacing="0">
          <tr>
            <td style="text-align: left;">
              <p style="vertical-align: middle; font-size: 13px; color: #666666; margin: 2px 0 10px 0; padding: 0;">
                <a href="%s" target="_blank"><img src="%s" border="0" height="20" width="20" style="float: left; vertical-align: middle;" align="middle"></a>

                &nbsp; &nbsp;
                <strong>%s</strong>
              </p>
            </td>
            <td style="text-align: right;">
              <p style="vertical-align: middle; font-size: 13px; color: #666666; margin: 2px 0 10px 0; padding: 0;">
                Page <span class="page"></span> of <span class="topage"></span>
              </p>
            </td>
          </tr>
          </table>'.
          '<hr style="display: block; height: 1px; border: 0; border-top: 1px solid #ccc; margin: 1px 0 15px 0; padding: 0;">',
          'https://example.com/',
          $this->Html->url('/favicon.png'),
          $title_for_layout
        ),
        // or if you don't want custom HTML....
        //'header-left' => $title_for_layout,
        //'header-right' => 'Page [page] of [toPage]',
        //'header-line' => true,
        //   see: http://wkhtmltopdf.org/usage/wkhtmltopdf.txt

        // footer (only supported in WkHtmlToPdf)
        'footer-html' => sprintf(
          '<hr style="display: block; height: 1px; border: 0; border-top: 1px solid #ccc; margin: 1em 0 10px 0; padding: 0;">'.
          '<div style="text-align: center; font-size: 11px; color: #666666;">
            %s
            &nbsp; &nbsp;
            %s voice &middot; %s fax
            &nbsp; &nbsp;
            &copy; %s %s
          </div>',
          Configure::read('site-address'),
          Configure::read('site-phone'),
          Configure::read('site-fax'),
          date('Y'),
          Configure::read('site-domain')
        ),

    ]);


    // ---------------------------------------
    // Gather HTML for rendering
    // ---------------------------------------
    $html = '<!DOCTYPE html><html lang="en" class="no-js" xmlns="//www.w3.org/1999/xhtml" xmlns:og="http://ogp.me/ns#">';
    $html .= '<head><!-- whatever you might want in here --></head>';
    $html .= '<body class="pdf printable">';
    $html .= '<link href="/css/my-normal-full.css" type="text/css" rel="stylesheet">';
    $html .= '<link href="/css/my-pdf-overrides.css" type="text/css" rel="stylesheet">';

    // ---------------------------------------
    // PDF Content (from view)
    // ---------------------------------------
    $html .= $content_for_layout;

    // ---------------------------------------
    // PDF End of Page
    // ---------------------------------------
    $html .= '</body></html>';

    // ---------------------------------------
    // PDF content cleanup
    // ---------------------------------------
    $html = preg_replace('#(src|href)="\/#', '$1="'.$domain.'/', $html);

    echo $html;
?>

<?php
    // app/View/Invoices/pdf/view.php
    echo $this->Html->tag('strong', '#' . $invoice['Invoice']['id']);
    // .. rest of your view here ..
?>

```


## Usage

You can use CakePdf in 2 ways, read carefully which one you actually need.
Many people mix both ways and dont get the expected results.


### 1: Render as PDF (including forced download) in the browser with PdfView

You can create PDF view and layout files for your controller actions and have them automatically rendered.
Place the view templates in a 'pdf' subdir, for instance `app/View/Invoices/pdf/view.ctp`
Layouts will be in `app/View/Layouts/pdf/default.ctp`

Make sure your InvoicesController has RequestHandler Component in the `$components` array.
Browse to http://localhost/invoices/view/1.pdf

Additionally you can map resources by adding `Router::mapResources(array('Invoices'));` to your routes
file and you can access the same document at http://localhost/invoices/1.pdf


### 2: Create PDF for email attachment, file storage etc.

You can use CakePdf lib to create raw PDF data with a view template.
The view file path would look like `app/View/Pdf/newsletter.ctp`.
Layout file path would be like `app/View/Layouts/pdf/default.ctp`
Note that layouts for both usage types are within same directory, but the view templates use different file paths
Optionally you can also write the raw data to file.

Example:
```php
<?php
    $CakePdf = new CakePdf();
    $CakePdf->template('newsletter', 'default');
    //get the pdf string returned
    $pdf = $CakePdf->output();
    //or write it to file directly
    $pdf = $CakePdf->write(APP . 'files' . DS . 'newsletter.pdf');
```


## Encryption

You can optionally encrypt the PDF with permissions

To use encryption you first need to select a crypto engine. Currently we support the following crypto engines:
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


## Note about static assets

Use absolute URLs for static assets in your view templates for PDFs.
If you use `HtmlHelper::image()`, `HtmlHelper::script()` or `HtmlHelper::css()` make sure you have `$options['fullBase'] = true`

Another solution would be to create a `AppHelper` of which it would force `$options['fullBase'] = true` for PDF requests. e.g:

```php
class AppHelper extends Helper {
    public function assetUrl($path, $options = array()) {
        if (!empty($this->request->params['ext']) && $this->request->params['ext'] === 'pdf') {
            $options['fullBase'] = true;
        }
        return parent::assetUrl($path, $options);
    }
}
```

## Thanks

Many thanks to Kim Biesbjerg and Jelle Henkens for their contributions.
Want your name here as well? Create a pull request for improvements/other PDF engines.
