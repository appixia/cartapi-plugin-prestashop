> The most recent version of this documentation is available through Appixia Dashboard or Appixia Knowledgebase (http://kb.appixia.com)

Appixia Cart API Plugin for Prestashop
======================================

### Introduction

Appixia apps communicate with the Prestashop cart using a custom web service (XML over HTTP). This cart plugin for Prestashop implements the web service from the cart side and should be installed as a Prestashop module on the merchant's store. Read more about cart integration in [Appixia Knowkedgebase](http://kb.appixia.com/cart).

The plugin is developed by Appixia Prestashop community and used as-is. The plugin is not designed to be changed during your integration process, although you have the full plugin source-code (PHP). If you wish to change the core plugin, join the Appixia open source development community in [Github](https://github.com/appixia?tab=repositories).

### Merchant Plugin Overrides

Many merchants require several customizations to the core Appixia Cart API plugin. These customizations have a special folder named `/overrides` inside the plugin. Integrators should place **all customizations** in this folder. The overrides mechanism is very similar to the Prestashop overrides mechanism. You can extend the core plugin classes and override functions that you wish to change. This development is done in PHP, since the Prestashop plugin is written in PHP.

For more information about plugin overrides, including code examples for common overrides and other tutorials, please visit [Appixia Knowledgebase](http://kb.appixia.com/cart:plugin-overrides).

### Supported Prestashop Versions

Prestashop 1.5.x - Tested on 1.5.0.17 through 1.5.3.1  
Prestashop 1.4.x - Tested on 1.4.0.1 through 1.4.9.0  
Prestashop 1.3.x - Tested on 1.3.0.1 through 1.3.7.0


Installation
------------

### Automatic Installation for Prestashop (all versions)

1. Download the the module package and save it on your computer (zip / tar)
2. Log into the Prestashop admin panel
3. Go to the Modules tab
4. Click on "Add new module"
   Under "Module file" choose either the zip file or tar file you've saved earlier
   Click on the "Upload this module" button.
5. The module is uploaded automatically by Prestashop
6. Enable the module through the Prestashop admin panel by selecting it in the module list and pressing "Install"

### Manual Installation for Prestashop (all versions)

1. Download the the module package and save it on your computer
2. Unzip the package to a temporary directory on your computer
3. Copy the `/appixiacartapi` directory to the `/modules` directory of your Prestashop server, usually using FTP
4. Enable the module through the Prestashop admin panel by selecting it in the module list and pressing "Install"

### Your Connection Details

Connection details for Appixia Dashboard. If your Prestashop server homepage is on http://yourstore.com

Store Home Url: http://yourstore.com  
Your API Endpoint Url: http://yourstore.com/modules/appixiacartapi/api.php


Debugging Cart API Responses
---------------------------

The main purpose of this plugin is to respond to Cart API requests (made by a mobile app). When things don't work as expected, you may want to debug the plugin and see the responses for yourself. In addition, when developing overrides, debugging is essential as part of the development process. 

You can perform the requests to the Cart API web service yourself (without the mobile app). This lets you see in your desktop browser the XML / JSON response. This is also very useful for debugging PHP errors and notices.

A convenient tool is bundled with the appixiacartapi plugin which helps debug Cart API web requests using your desktop web browser. It is found in `appixiacartapi/debug/debugger.php`. The tool shows a list of convenient API requests (request templates) and allows changing the URL parameters before sending the command to an iframe on the lower half of the page. You can add your own templates in `appixiacartapi/overrides/debug/templates.php`. We recommend using the **Firefox** web browser with this tool (since it provides native XML parsing of the response inside an iframe). It is recommended to delete or restrict access to the debug directory once your site goes live.

Here is an example URL to access the debugger:  
http://yourstore.com/modules/appixiacartapi/debug/debugger.php

Here is an example of a request that the debugger generates (to the plugin):  
http://yourstore.com/modules/appixiacartapi/api.php?X-OPERATION=GetSingleItem&Id=124

If you want to see PHP notices and warnings, take a look in `Helpers.php` and edit the function `CartAPI_Handlers_Helpers::setServerNotices()`.

**It is recommended to clear cookies using the debugger's "Clear Cookies" button when you finish your debugging session. If you don't, the debugger cookie might prevent you from seeing your regular store in the browser that accessed the debugger. This issue can only happen in browsers that access the debugger, so don't worry, this can't happen to your users.**

**It is recommended to delete or restrict access to the debug directory once your site goes live.**


Plugin Core Classes For Override
--------------------------------

The core plugin classes which you can override are found in `/modules/appixiacartapi`. You can go over their source code to get a sense of what overrides can be done and where. The main responsibility of these classes is to handle various Appixia Cart API requests. The mobile app communicates with the plugin using a custom web service (XML over HTTP). This web service protocol includes several requests, or "operations", like `GetSingleItem`, which returns an XML description of a single product.

#### Items.php
In charge of everything related to products and product lists.  
Handles the following Cart API operations:
* `GetSingleItem` - returns detailed XML info about a single product using its id.
* `GetItemList` - returns a paged list of products according to various filters.

#### Categories.php
In charge of everything related to categories and category lists.  
Handles the following Cart API operations:
* `GetCategoryList` - returns a list of categories according to various filters.

#### Login.php
In charge of login and register of new customers.  
Handles the following Cart API operations:
* `BuyerLogin` - logins a customer (either with username+password, or Facebook).
* `BuyerRegister` - registers a new customer.

#### Order.php
In charge of the entire checkout process (from adding an item to the cart, to address and payment and creating an order on Prestashop itself). Please note that the term "order" under Appixia is closer to the "cart" term in Prestashop. An "order" under Appixia exists from the moment the customer added a product to the cart.  
Handles the following Cart API operations:
* `GetOrderUpdate` - takes a customer cart of items in some stage of the checkout process (an order), updates the internal Prestashop structures accordingly and replies with changes which need to be made to this "order" by the mobile app.
* `GetShippingMethods` - takes a customer order and returns the possible shipping methods that they need to choose from.
* `GetPaymentMethods` - takes a customer order and returns the possible payment methods that they need to choose from. 


Plugin Folder Structure
-----------------------

Normally, the integrator would not make any modifications to the core Prestashop plugin (found in `/modules/appixiacartapi`), except of course for adding overrides under `/modules/appixiacartapi/overrides`. Nevertheless, you have the full core plugin source code in order to see which functions you can override. If you want to override something which is not available as a convenient function, please contact Appixia to have this functionality in the core plugin refactored.

### Core Plugin Folder Structure

`/modules/appixiacartapi/`  
Root folder, includes implementations for all the core plugin classes (which you can override with your own implementation in the overrides folder).

`/modules/appixiacartapi/overrides/`  
The most important folder for the integrator. This folder will hold all the changes you make to the Appixia Cart API plugin. All customizations for your specific store should be here.

`/modules/appixiacartapi/modules`  
Mobile specific modifications for Prestashop modules are found in this folder. The folder is structured like the Prestashop `/modules` folder. Every Prestashop module which includes an Appixia specific modification will have the modification in here. Most of the modules you can expect to find here are payment modules (like paypal). You should not modify the code in this folder since this is core code.

`/modules/appixiacartapi/engine`  
Core library code used mostly for encoding and decoding the Appixia Cart API web service protocol (XML/JSON over HTTP). This code is not specific to Prestashop and found in Appixia plugins for other carts as well.

### Plugin Overrides Folder Structure

`/modules/appixiacartapi/overrides/`  
The root of the overrides. All the core classes which you wish to override will be placed here. The class file names are identical to the plugin core class file names.

`/modules/appixiacartapi/overrides/cms/`  
Several places in the mobile app display regular HTML content from the Prestashop website. For example, inside the product details additional sections (like product stylist or product look) are plain HTML. The code that renders these HTML files is found in this folder.

`/modules/appixiacartapi/overrides/cms/assets/`  
Static files required by the templates in the `/cms` folder. Mostly mobile-specific versions of JS and CSS files.

### Other Important Core Plugin Files

Most of the interesting plugin core files are found in /modules/appixiacartapi. As stated before, you should not make modifications to these core files. All of your changes should be placed in the overrides folder.

`api.php`  
The main entry point for a Cart API call. The response is usually XML or JSON. The mobile app makes all of its API requests to this entry point. In order to debug, you can also make manual calls to this entry point using a regular desktop browser and see the responses (XML).

`Helpers.php` (class `CartAPI_Handlers_Helpers`)  
A helper class which includes static helper functions related to Prestashop (like getting the Prestashop store base URL). This class is not intended to be overridden.

`engine/Helpers.php` (class `CartAPI_Helpers`)  
A helper class which includes static helper functions related to the Cart API protocol (encoding and decoding XML protocol messages). This class is not intended to be overridden.

`engine/Mediums/Encoder.php` (class `CartAPI_Mediums_Encoder`)  
The abstract base class used for encoding Cart API protocol messages. There are several implementations of different mediums (like XML or JSON). An encoder instance is passed to all Cart API handlers (usually named $encoder). This class is not intended to be overridden.

`appixiacartapi.php`  
The module implementation for Prestashop. Mostly includes hooks the plugin makes on Prestashop. The main hook is a hook on regular rendered pages when accessing from the mobile app. If by some strange reason the mobile app is redirected to a page that starts rendering the regular desktop website, the plugin catches this and redirects.

`pagehook.php`  
The place where the mobile app is redirected when the hook catches a regular desktop web page render.

`debug/debugger.php`  
A helper tool for debugging Cart API requests using your desktop browser. This tool should only be used for development. The request templates for this tool are found in templates.php (in the same directory). You can create your own templates by editing `/overrides/debug/templates.php`.  
**It is recommended to delete or restrict access to the debug directory once your site goes live.**
