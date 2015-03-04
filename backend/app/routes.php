<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

# Global Pattern
Route::pattern('id', '[0-9]+');
Route::pattern('sid', '[a-zA-Z0-9]+');

# Require Authentication
Route::group(array('before' => 'auth'), function()
{  
    // Switching Zone
    Route::post('/selectZone', 'UserController@selectZone');
    
    Route::get('/logout', 'UserController@logoutProcess');
    
    Route::any('/changePassword', 'UserController@changePassword');
    
    
    // General System Information
    Route::get('/system.json', 'SystemController@jsonSystem');
    
    // Page Level Information
    Route::get('/dashboard.json', 'HomeController@jsonDashboard'); 
   
    
    // Client Information
    Route::any('/checkClient.json', 'CustomerController@jsonCheckClient');
    Route::post('/findClientById.json', 'CustomerController@jsonFindClientById');
    
    // Product Information
    Route::any('/getAllProduct.json', 'ProductController@jsonGetAllProduct');
    Route::get('/getProductGroups.json', 'ProductController@jsonGetProductGroups');
    Route::post('/getProductsFromGroup.json', 'ProductController@jsonGetProductsfromGroup');
    Route::post('/findRecentProductsByCustomerId.json', 'ProductController@jsonFindRecentProductsByCustomerId');
    Route::any('/searchProductOrHotProduct.json', 'ProductController@jsonSearchProductOrHotItem');
    
    // Invoice Information        
    Route::post('/placeOrder.json', 'OrderController@jsonNewOrder');
    Route::any('/getNotification.json', 'OrderController@jsonGetNotification');
    Route::any('/manipulateInvoiceStatus.json', 'OrderController@jsonUpdateInvoiceStatus');    
    Route::post('/getSingleInvoice.json', 'OrderController@jsonGetSingleInvoice');
    Route::get('/previewInvoice.image','OrderController@previewInvoice');
    Route::post('/getClientLastInvoice.json','OrderController@jsonGetClientLastInvoice');
    Route::post('/voidInvoice.json', 'OrderController@jsonVoidInvoice');
    
    //Route::any('/getInvoices.json', 'OrderController@jsonListOrders');
    Route::any('/queryInvoice.json', 'OrderController@jsonQueryFactory');
    Route::any('/unloadInvoice.json', 'OrderController@jsonUnloadInvoice');
    
    // Report Factory
    Route::any('/getAvailableReportsType.json', 'ReportController@loadAvailableReports');
    Route::any('/getReport.json', 'ReportController@loadReport');
    
    Route::any('/viewArchivedReport', 'ReportController@viewArchivedReport');
    
    
    // Picking List
    Route::any('/generatePickingList.json', 'DeliveryController@jsonGeneratePickingList');
    
    // Customer Maintenance
    Route::post('/queryCustomer.json', 'CustomerController@jsonQueryCustomer');
    Route::post('/manipulateCustomer.json', 'CustomerController@jsonManiulateCustomer');
    
    // Product Maintenance
    Route::post('/queryProduct.json', 'ProductController@jsonQueryProduct');
    Route::post('/manipulateProduct.json', 'ProductController@jsonManiulateProduct');
    
    Route::post('/queryProductDepartment.json', 'ProductController@jsonQueryProductDepartment');
    
    // Invoice Printing Maintenance
    Route::post('/queryIPF.json', 'IPFController@jsonQueryIPF');
    Route::post('/manipulateIPF.json', 'IPFController@jsonManiulateIPF');
    
    // Staff Maintenance
    Route::post('/queryStaff.json', 'UserController@jsonQueryStaff');
    Route::post('/manipulateStaff.json', 'UserController@jsonManiulateStaff');
    
    // Printer
    Route::any('/instantPrint.json', 'PrintQueueController@instantPrint');
    Route::any('/rePrint.json', 'PrintQueueController@rePrint');
    
    // Invoice Status Manager
    Route::post('/retrieveInvoiceAssociation.json', 'InvoiceStatusController@jsonRetrieveAssociation');
    Route::post('/updateStatus.json', 'InvoiceStatusController@updateStatus');
    
});

Route::get('/', function(){
   return Redirect::action('UserController@authenticationProcess'); 
});


Route::get('/system.json', 'SystemController@jsonSystem');
Route::get('/ping.json', function(){
   return 'Pong'; 
});

Route::get('/setZone', function(){
    $zoneid = Input::get('id');
    Session::put('zone', $zoneid);
    return Redirect::to('//portal.pingkee.hk');
});

Route::any('/credential/auth', 'UserController@authenticationProcess');

Route::any('/test.env', 'TestController@testMethod');

// Batch Working
Route::get('/batch/pscmc-0000-daily.bat', 'BatchController@productSearchandCustomerMapClearance');
Route::get('/batch/bistp-5min.bat', 'BatchController@batchSendInvoiceToPrinter');

// Queue
Route::any('/queue/generate-preview-invoice-image.queue', 'QueueController@generatePreviewInvoiceImage');
Route::any('/queue/generate-print-invoice-image.queue', 'QueueController@generatePrintInvoiceImage');
Route::any('/queue/send-print-job-to-printer.queue', 'QueueController@sendPrintJobToPrinter');
Route::any('/queue/generate-invoice-pdf.queue', 'QueueController@generateInvoicePDF');

// Printer
Route::get('/getUnprintJobs.json', 'PrintQueueController@jsonGetUnprintJobs');