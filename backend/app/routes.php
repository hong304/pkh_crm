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
   
    //Commission
    Route::any('/queryCommission.json', 'CommissionController@queryCommission');
  //  Route::get('/getExcel', 'CommissionController@queryCommission');


    // Client Information
    Route::any('/checkClient.json', 'CustomerController@jsonCheckClient');
    Route::post('/findClientById.json', 'CustomerController@jsonFindClientById');

    //Group Information
    Route::any('/checkGroup.json', 'GroupController@checkGroup');

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
    Route::post('/ getClientSameDayOrder.json','OrderController@jsonGetSameDayOrder');

    Route::post('/voidInvoice.json', 'OrderController@jsonVoidInvoice');
    Route::post('/getLastItem.json', 'OrderController@jsonGetLastItem');

    
    //Route::any('/getInvoices.json', 'OrderController@jsonListOrders');
    Route::any('/queryInvoice.json', 'OrderController@jsonQueryFactory');
    Route::any('/unloadInvoice.json', 'OrderController@jsonUnloadInvoice');
    
    // Report Factory
    Route::any('/getAvailableReportsType.json', 'ReportController@loadAvailableReports');
    Route::any('/getReport.json', 'ReportController@loadReport');
    Route::any('/getPrintLog.json', 'ReportController@getPrintLog');

    Route::any('/viewArchivedReport', 'ReportController@viewArchivedReport');
    
    // Van sell
    Route::any('/getVansellreport.json', 'VanSellController@loadvanSellReport');

    // Picking List
    Route::any('/generatePickingList.json', 'DeliveryController@jsonGeneratePickingList');
    
    // Customer Maintenance
    Route::post('/queryCustomer.json', 'CustomerController@jsonQueryCustomer');
    Route::post('/manipulateCustomer.json', 'CustomerController@jsonManiulateCustomer');

	 //Supplier Maintenance
    Route::post('/querySupplier.json', 'SupplierController@jsonQuerySupplier');
    Route::post('/checkSupplier.json', 'SupplierController@jsonCheckSupplier');
    Route::post('/maniSupplier.json', 'SupplierController@jsonUpdate');
    //Route::post('/generateSupplier.json', 'SupplierController@generateId');
    
    //Po Maintenance
    Route::post('/queryPo.json', 'PoController@jsonQueryPo');
	
    // Group Maintenance
    Route::post('/queryGroup.json', 'GroupController@jsonQueryGroup');
    Route::post('/manipulateGroup.json', 'GroupController@jsonManiulateGroup');

    // Product Maintenance
    Route::post('/queryProduct.json', 'ProductController@jsonQueryProduct');
    Route::post('/manipulateProduct.json', 'ProductController@jsonManiulateProduct');
    Route::post('/queryProductwithItem.json', 'ProductController@queryProduct');


    
    Route::post('/queryProductDepartment.json', 'ProductController@jsonQueryProductDepartment');
    
    // Invoice Printing Maintenance
    Route::post('/queryIPF.json', 'IPFController@jsonQueryIPF');
    Route::post('/manipulateIPF.json', 'IPFController@jsonManiulateIPF');
	
	  // Country Maintenance
    Route::post('/queryCountry.json', 'countryController@jsonQueryCountry');
    Route::post('/manipulateCountry.json', 'countryController@jsonManiulateCountry');
    
     // Currency Maintenance
    Route::post('/queryCurrency.json', 'currencyController@jsonQueryCurrency');
    Route::post('/manipulateCurrency.json', 'currencyController@jsonManiulateCurrency');
    
    // Staff Maintenance
    Route::post('/queryStaff.json', 'UserController@jsonQueryStaff');
    Route::post('/manipulateStaff.json', 'UserController@jsonManiulateStaff');
    Route::post('/UserManipulation.json','UserController@addStaff');
    
    // Printer
    Route::any('/instantPrint.json', 'PrintQueueController@instantPrint');
    Route::any('/rePrint.json', 'PrintQueueController@rePrint');
    Route::any('/genA4Invoice.json', 'ReportController@genA4Invoice');
    Route::any('/getAllPrintJobsWithinMyZone.json', 'PrintQueueController@getAllPrintJobsWithinMyZone');
    Route::any('/printSelectedJobsWithinMyZone.json', 'PrintQueueController@printSelectedJobsWithinMyZone');
    Route::any('/getInvoiceStatusMatchPrint.json', 'PrintQueueController@getInvoiceStatusMatchPrint');


    
    // Invoice Status Manager
    Route::post('/retrieveInvoiceAssociation.json', 'InvoiceStatusController@jsonRetrieveAssociation');
    Route::post('/updateStatus.json', 'InvoiceStatusController@updateStatus');

    // Payment
    Route::post('/addCheque.json','PaymentController@addCheque');
    Route::any('querryClientClearance.json','PaymentController@getClientClearance');
    Route::any('querryCashCustomer.json','PaymentController@querryCashCustomer');

    //Data analysis
    Route::any('/searchProductDataProduct.json', 'DataWarehouseController@jsonSearchDataProduct');

    //change invoice status to picking
    Route::post('/generalPickingStatus.json','HomeController@generalPickingStatus');

    //Data warehouse
    Route::get('invoice','DataWarehouseController@getInvoice');

    Route::get('/getHoliday.json','OrderController@jsonHoliday');
	
	  //Supplier
    Route::get('/getChoice.json','SupplierController@jsonChoice');
    Route::get('/getCurrency.json','SupplierController@jsonCurrency');


});

Route::get('/', function(){
   return Redirect::action('UserController@authenticationProcess'); 
});


Route::get('/system.json', 'SystemController@jsonSystem');
Route::get('/info', function(){
    p($_SERVER);
});

Route::get('/json_decode', function(){
    pd( unserialize('a:2:{s:12:"deliveryDate";a:1:{i:0;i:1437408000;}s:13:"print_storage";a:1:{i:0;s:24:"print_I1507-026645-1.png";}}'));
});


Route::get('/test', function(){


 /* $result = Invoice::select(DB::RAW('count(*)'),'deliveryDate','zoneId')->where('invoiceStatus','!=','99')->groupBy('zoneId','deliveryDate')->orderBy('deliveryDate','asc')->get()->toArray();
    foreach ($result as $v) {
        echo $v['deliveryDate_date'].":".$v['zoneText'].":".$v['count(*)']."<br>";
   }*/

});


Route::get('/setZone', function(){
    $zoneid = Input::get('id');
    Session::put('zone', $zoneid);
    return Redirect::to($_SERVER['frontend']);
});

Route::any('/credential/auth', 'UserController@authenticationProcess');

Route::get('/cron/resetOrderTrace', function(){
    DB::table('Customer')->update(['today'=>'','tomorrow'=>'']);

    $invoices = Invoice::where('deliveryDate',strtotime('00:00:00'))->lists('customerId');
    if(count($invoices)>0)
    DB::table('Customer')->wherein('customerId',$invoices)->update(['today'=>1]);

    $invoices_tomorrow = Invoice::where('deliveryDate',strtotime('tomorrow'))->lists('customerId');
    if(count($invoices_tomorrow)>0)
    DB::table('Customer')->wherein('customerId',$invoices_tomorrow)->update(['tomorrow'=>1]);

});

Route::get('cron/completeOrder',function(){

    $holidays =  holiday::where('year',date("Y"))->first();

    $h_array = explode(",", $holidays->date);
    foreach($h_array as &$v){
        $md = explode("-",$v);
        $m = str_pad($md[0], 2, '0', STR_PAD_LEFT);
        $d = str_pad($md[1], 2, '0', STR_PAD_LEFT);
        $v = date("Y"). '-'. $m.'-'.$d;
    }
    if(date('w', strtotime(date('Y-m-d'))) == 1 ||date('w', strtotime(date('Y-m-d'))) == 2 ||date('w', strtotime(date('Y-m-d'))) == 3)
        $days_ago = date('Y-m-d', strtotime('-4 days', strtotime(date('Y-m-d'))));
    else
        $days_ago = date('Y-m-d', strtotime('-3 days', strtotime(date('Y-m-d'))));

    function check_in_range($start_date, $end_date, $date_from_user)
    {
        // Convert to timestamp
        $start_ts = strtotime($start_date);
        $end_ts = strtotime($end_date);
        $user_ts = strtotime($date_from_user);

        // Check that user date is between start & end
        return (($user_ts >= $start_ts) && ($user_ts <= $end_ts));
    }

    $count = 0;
    foreach($h_array as $g){
        if(check_in_range($days_ago, date('Y-m-d'), $g))
            $count++;
    }

    $accurage_date = date('Y-m-d', strtotime('-'.$count.' days', strtotime($days_ago)));

    Invoice::where('deliveryDate','<=',strtotime($accurage_date))->where('paymentTerms',1)->where('invoiceStatus',2)->update(['invoiceStatus'=>'30']);
    Invoice::where('deliveryDate','<=',strtotime($accurage_date))->where('paymentTerms',2)->where('invoiceStatus',2)->update(['invoiceStatus'=>'20']);

});


// Batch Working
//Route::get('/batch/pscmc-0000-daily.bat', 'BatchController@productSearchandCustomerMapClearance');
//Route::get('/batch/bistp-5min.bat', 'BatchController@batchSendInvoiceToPrinter');

// Queue
//Route::any('/queue/generate-preview-invoice-image.queue', 'QueueController@generatePreviewInvoiceImage');
//Route::any('/queue/generate-print-invoice-image.queue', 'QueueController@generatePrintInvoiceImage');
//Route::any('/print', 'QueueController@sendPrintJobToPrinter');
//Route::any('/queue/generate-invoice-pdf.queue', 'QueueController@generateInvoicePDF');

// Printer
Route::get('/getUnprintJobs.json', 'PrintQueueController@jsonGetUnprintJobs');
Route::any('/printAllPrintJobsWithinMyZone.json', 'PrintQueueController@printAllPrintJobsWithinMyZone');


