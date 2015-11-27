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
    Route::get('/getOweInvoices.json', 'HomeController@getOweInvoices');
    Route::post('/updateBroadcast.json', 'HomeController@updateBroadcast');


   
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
    Route::post('/getClientSameDayOrder.json','OrderController@jsonGetSameDayOrder');
    Route::post('/getAllLastItemPrice.json','OrderController@jsonGetLastItem');
    Route::post('/getNoOfOweInvoices.json','OrderController@getNoOfOweInvoices');
    Route::post('/checkInvoiceIdExist.json','OrderController@checkInvoiceIdExist');




    Route::post('/voidInvoice.json', 'OrderController@jsonVoidInvoice');
    Route::post('/getLastItem.json', 'OrderController@jsonGetLastItem');
	
	    //Purchase order Information
    Route::post('/newPoOrder.json', 'newPoController@jsonNewPo');
    Route::post('/getSinglePo.json', 'newPoController@getSinglePo');

    
    //Route::any('/getInvoices.json', 'OrderController@jsonListOrders');
    Route::any('/queryInvoice.json', 'OrderController@jsonQueryFactory');
    Route::any('/unloadInvoice.json', 'OrderController@jsonUnloadInvoice');
    
    // Report Factory
    Route::any('/getAvailableReportsType.json', 'ReportController@loadAvailableReports');
    Route::any('/getReport.json', 'ReportController@loadReport');
    Route::any('/getPrintLog.json', 'ReportController@getPrintLog');

    Route::any('/viewArchivedReport', 'ReportController@viewArchivedReport');


    //financial report
    Route::any('/agingByZoneCash.json','financialReportController@getAgingByZoneCash');
    Route::any('/dailySalesSummary.json','financialReportController@getDailySalesSummary');
    Route::any('/yearEndReport.json','financialReportController@getYearEndReport');
    Route::any('/outputCredit.json','financialReportController@outputCashAndCredit');
    Route::any('/outputCash.json','financialReportController@outputCashAndCredit');

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
    Route::post('/queryPo.json', 'newPoController@jsonQueryPo');
    Route::post('/voidPo.json', 'newPoController@voidPo');
    Route::post('/newPoAdult.json', 'newPoController@newPoAdult');
    Route::post('/queryPoUpdate.json', 'newPoController@queryPoUpdate');	
    Route::get('/overseaPoGetISnvoice.json', 'newPoController@overseaPoGetISnvoice');	
    
    
    // Group Maintenance
    Route::post('/queryGroup.json', 'GroupController@jsonQueryGroup');
    Route::post('/manipulateGroup.json', 'GroupController@jsonManiulateGroup');

    // Product Maintenance
    Route::post('/queryProduct.json', 'ProductController@jsonQueryProduct');
    Route::post('/manipulateProduct.json', 'ProductController@jsonManiulateProduct');
    Route::post('/queryProductwithItem.json', 'ProductController@queryProduct');

    //Inventory Management
    Route::post('/queryInventory.json', 'inventoryController@queryInventory');
    Route::post('/manipulateInventory.json', 'inventoryController@manipulateInventory');
    Route::any('/queryInventoryHistory.json', 'InventoryController@queryInventoryHistory');
    
    Route::post('/queryProductDepartment.json', 'ProductController@jsonQueryProductDepartment');
    Route::post('/manipulateProductDepartment.json', 'ProductController@jsonManProductDepartment');
    
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
    Route::any('getClearance.json','PaymentController@getClearance');
    Route::any('querryCashCustomer.json','PaymentController@querryCashCustomer');
    Route::any('generalFinanceReport.json','PaymentController@outputPdf');



    //Payment Cash Sales
    Route::any('getPaymentDetails.json','financeCashController@getPaymentDetails');
    Route::post('delPayment.json','financeCashController@delPayment');
    Route::any('getCashClearance.json','financeCashController@getClearance');
    Route::post('/addCashCheque.json','financeCashController@addCheque');

    Route::post('addExpenses.json','expensesController@addExpenses');
    Route::post('queryExpenses.json','expensesController@queryExpenses');

    Route::post('addIncome.json','incomeController@addIncome');
    Route::post('queryIncome.json','incomeController@queryIncome');

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
    
        //Shipping
     Route::post('/jsonSelectPo.json','shippingController@jsonSelectPo');
     Route::post('/jsonNewShip.json','shippingController@newShipment');
     Route::post('/jsonQueryShip.json','shippingController@jsonQueryShip');
     Route::post('/jsonGetSingleShip.json','shippingController@jsonGetSingleShip');
     Route::post('/deleteShip.json','shippingController@deleteShip');
      Route::post('/loadShip.json','shippingController@loadShip');     
     Route::any('/outputPreview.json','shippingController@outputPreview');  
     Route::any('/outputPo.json','shippingController@outputPo');  
     Route::post('/loadPo.json','shippingController@loadPo');   
     Route::any('/printPo.json','newPoController@printPo');
     Route::any('/outputShipNote.json','shippingController@outputShipNote');
     Route::any('/outputShipContainer.json','shippingController@outputShipContainer');
     
     //Receiving
     Route::post('/searchSupplier.json','receiveController@searchSupplier');
     Route::post('/searchPoBySupplier.json','receiveController@searchPo');
     Route::post('/searchShipping.json','receiveController@searchShipping');
     Route::post('/newReceive.json','receiveController@newReceive');
     Route::post('/getPurchaseAll.json','receiveController@getPurchaseAll');
     Route::get('/purchaseOrderForm.json','newPoController@purchaseOrderForm');
     Route::post('/jsonSearchSupplier.json','shippingController@jsonSearchSupplier');
     Route::post('/jsonSearchPo.json','shippingController@jsonSearchPo');

    //rePackController
    Route::post('/getAllProducts.json','rePackController@getAllProducts');  //get all items from product table
    Route::post('/queryReceiving.json','rePackController@queryReceiving');  //get all items from product table
    Route::post('/repack.json','rePackController@repack');
    Route::post('/addAjust.json','rePackController@addAjust');
    Route::post('/preRepackProduct.json','rePackController@preRepackProduct');
    Route::post('/outRepackProduct.json','rePackController@outRepackProduct');
    
    
 
    //Permission Control
    Route::post('/getPermissionLists.json','permissionController@getPermissionList');
    Route::get('/getUserGroup.json','permissionController@getUserGroup');
    
    Route::post('/jqueryGetArrived.json','arrivedContainerController@jqueryGetArrived');
    
    //Add products into container
    Route::post('/getProductCost.json','receiveController@getProductCost');
    Route::post('/addProductContainer.json','receiveController@addProductContainer');

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

/*Route::get('/insertDate', function(){
    ini_set('max_execution_time', 600);
    for($num = 5;$num<=1939;$num++)
    {
        DB::table('receivings')->where('id',$num)->update(['receivingId'=>'R00'.$num]);
    }
   
});*/

Route::get('/test', function(){

$c = new SystemController();

    pd($c->normalizedUnit('A001','5','carton'));

    die();

    $first_date = shipping::orderBy('etaDate')->first();

    $s = shipping::where('actualDate','!=','')->get();
    $eta = shipping::whereNull('actualDate')->get();
   // pd($s->toArray());
//pd($eta->toArray());

    $date1 =strtotime($first_date->etaDate);
    $sDate = $date1+24*60*60*6;
    while ($date1 <= $sDate) {
        $date[] = date('Y-m-d',$date1);
        foreach($s as $v){
            if($v->actualDate == date('Y-m-d',$date1)){
                $sarr[$v->shippingId][date('Y-m-d',$date1)]['no'] = $v->container_numbers;
                $sarr[$v->shippingId][date('Y-m-d',$date1)]['mode'] = 'actual';
            }
        }

        foreach($eta as $v){
                if ($v->etaDate == date('Y-m-d',$date1)){
                    $sarr[$v->shippingId][date('Y-m-d',$date1)]['no'] = $v->container_numbers;
                    $sarr[$v->shippingId][date('Y-m-d',$date1)]['mode'] = 'eta';
                }
        }
        $date1 = $date1+24*60*60;
    }

    pd($sarr);
   ?>

    <table><tr><td></td>
            <?php foreach($date as $v){
        echo '<td>'.$v.'</td>';
    }
      ?>  </tr>
    <?php foreach($sarr as $kk => $vv){
        echo '<tr>';
        echo '<td>'.$kk.'</td>';
        for($i=0;$i<7;$i++){
            if(isset($vv[$date[$i]])) {
                if ($vv[$date[$i]]['mode'] == 'actual')
                    echo '<td>' . $vv[$date[$i]]['no'] . '*</td>';
                else
                    echo '<td>' . $vv[$date[$i]]['no'] . '</td>';
            }else
                echo '<td></td>';
        }
        echo '</tr>';
    }?>


    </table>


<?php

});

Route::get('/updateProduct',function(){
    $file = fopen('C:\Users\Administrator\Desktop\Productsss.csv', 'r');
while (($line = fgetcsv($file)) !== FALSE) {
  //$line is an array of the csv elements
  print_r($line);
}
fclose($file);
});



Route::get('/aging',function(){

        $time_interval = [['0', '0'], ['1', '1'], ['2', '2'], ['5', '3'], ['11', '6'], ['120', '12']];


        $first = true;

        foreach ($time_interval as $v) {
            if ($first) {
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime("-" . $v[0] . " month"));
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][1] = date("Y-m-d", strtotime("-" . $v[1] . " month"));
                $first = false;
            } else {
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime("-" . $v[0] . " month"));
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][1] = date("Y-m-t", strtotime("-" . $v[1] . " month"));
            }

        }

        //  pd($time);
        $month[0] = key(array_slice($time, -6, 1, true));
        $month[1] = key(array_slice($time, -5, 1, true));
        $month[2] = key(array_slice($time, -4, 1, true));
        $month[3] = key(array_slice($time, -3, 1, true));
        $month[4] = key(array_slice($time, -2, 1, true));
        $month[5] = key(array_slice($time, -1, 1, true));


        $this->_reportMonth = date("n", $this->_date2);


        $pdf = new PDF();
        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);




for ($i = 0; $i <22; $i++){
            $data = [];

            foreach ($time as $k => $v) {
                $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('paymentTerms', 1)->whereNotIn('invoiceStatus','30','99','2')->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->where('Invoice.zoneId', $i)->OrderBy('deliveryDate')->get();

                foreach ($data[$k] as $invoice) {
                    $customerId = $invoice->customerId;

                    if (!isset($this->_monthly[$k]['byCustomer'][$customerId]))
                        $this->_monthly[$k]['byCustomer'][$customerId] = 0;
                        $this->_monthly[$k]['byCustomer'][$customerId] += ($invoice->realAmount - ($invoice->paid + $invoice->discount_taken));
                }
            }
}

    pd($this->_monthly);


        foreach ($time as $k => $v) {
            if (!isset($this->_monthly[$k]['total']))
                $this->_monthly[$k]['total'] = 0;
            if (isset($this->_monthly[$k]['byCustomer']))
                foreach ($this->_monthly[$k]['byCustomer'] as $v) {
                    $this->_monthly[$k]['total'] += $v;
                }
        }

        $bd = array_chunk($this->data, 17, true);

        $i = 1;
        $j = 1;
        $own_total = 0;

        foreach ($bd as $k => $g) {


            $pdf->AddPage('L');


            $pdf->AddFont('chi','','LiHeiProPC.ttf',true);
            $pdf->SetFont('chi','',14);
            $pdf->setXY(10, 2);
            $pdf->Cell(0, 10,"炳記行貿易有限公司",0,1,"C");
            $pdf->setXY(10, 10);
            $pdf->SetFont('chi','U',12);
            $pdf->Cell(0, 10,'帳齡分析搞要(應收)',0,1,"C");

            $y = 10;
            $pdf->SetFont('chi','',9);
            $pdf->setXY(10, $y);
            $pdf->Cell(0, 10,'載至日期 : '. date('Y-m-d',$this->_date2),0,1,"L");

            $pdf->setXY(10, $y+6);
            $pdf->Cell(0, 10,'客戶組 : '.$this->_group,0,1,"L");

            $y = 30;

            $pdf->SetFont('chi', '', 8);
            $pdf->setXY(10, $y);
            $pdf->Cell(0, 0, "客户", 0, 0, "L");

            $pdf->setXY(100, $y);
            $pdf->Cell(0, 0, "結餘", 0, 0, "L");

            $pdf->setXY(130, $y);
            $pdf->Cell(0, 0, $month[0], 0, 0, "L");

            $pdf->setXY(160, $y);
            $pdf->Cell(0, 0, $month[1], 0, 0, "L");

            $pdf->setXY(190, $y);
            $pdf->Cell(0, 0, $month[2], 0, 0, "L");

            $pdf->setXY(220, $y);
            $pdf->Cell(0, 0, $month[3], 0, 0, "L");

            $pdf->setXY(250, $y);
            $pdf->Cell(0, 0, $month[4], 0, 0, "L");

            $pdf->Line(10, $y + 2, 285, $y + 2);


            $pdf->setXY(280, 10);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i, count($bd)), 0, 0, "R");

            $i++;


            foreach ($g as $kk => $client) {

                $amount = 0;
                $paid = 0;
                $accu = 0;

                foreach ($client['breakdown'] as $k => $v) {

                    $amount += $v['invoiceAmount'];
                    $paid += $v['paid'];
                    $accu = $v['accumulator'];

                }

                $own_total += $accu;

                $y += 4;

                $pdf->setXY(10, $y);
                $pdf->Cell(0, 0, $client['customer']['customerId'], 0, 0, "L");

                $pdf->setXY(30, $y);
                $pdf->Cell(0, 0, $client['customer']['customerName'], 0, 0, "L");

                $pdf->setXY(100, $y);
                $pdf->Cell(0, 0, '$' . number_format($accu, 2, '.', ','), 0, 0, "L");

                $pdf->setXY(130, $y);

                if (isset($this->_monthly[$month[0]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[0]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';

                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(160, $y);
                if (isset($this->_monthly[$month[1]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[1]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';
                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(190, $y);
                if (isset($this->_monthly[$month[2]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[2]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';
                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(220, $y);
                if (isset($this->_monthly[$month[3]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[3]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';
                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(250, $y);
                if (isset($this->_monthly[$month[4]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[4]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';

                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->Line(10, $y + 7, 285, $y + 7);

                $y += 5;
            }


            if ($j == count($bd)) {

                $y += 5;
                $pdf->setXY(70, $y);
                $pdf->Cell(0, 0, '合共總額:', 0, 0, "L");

                $pdf->setXY(100, $y);
                $pdf->Cell(0, 0, '$' . number_format($own_total, 2, '.', ','), 0, 0, "L");

                $pdf->setXY(130, $y);
                $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[key(array_slice($time, -6, 1, true))]['total']) ? $this->_monthly[key(array_slice($time, -6, 1, true))]['total'] : 0, 1, '.', ','), 0, 0, "L");

                $pdf->setXY(160, $y);
                $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[key(array_slice($time, -5, 1, true))]['total']) ? $this->_monthly[key(array_slice($time, -5, 1, true))]['total'] : 0, 1, '.', ','), 0, 0, "L");

                $pdf->setXY(190, $y);
                $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[key(array_slice($time, -4, 1, true))]['total']) ? $this->_monthly[key(array_slice($time, -4, 1, true))]['total'] : 0, 1, '.', ','), 0, 0, "L");

                $pdf->setXY(220, $y);
                if (isset($this->_monthly[key(array_slice($time, -3, 1, true))]['total']))
                    if ($this->_monthly[key(array_slice($time, -3, 1, true))]['total'] != 0)
                        $numsum = '$' . number_format($this->_monthly[key(array_slice($time, -3, 1, true))]['total'], 1, '.', ',');
                    else
                        $numsum = '';

                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(250, $y);
                if (isset($this->_monthly[key(array_slice($time, -2, 1, true))]['total']))
                    if ($this->_monthly[key(array_slice($time, -2, 1, true))]['total'] != 0)
                        $numsum = '$' . number_format($this->_monthly[key(array_slice($time, -2, 1, true))]['total'], 1, '.', ',');
                    else
                        $numsum = '';

                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

            }

            $j++;

        }

        $pdf->Output('', 'I');
        // pd( $this->_monthly);

    //aging pdf





});

Route::get('/dashboard', 'SystemController@getDashboard');

Route::get('/vansales',function(){
    return View::make('vansales');
});

Route::post('/vans', 'VanSellController@postVans');

Route::get('/setZone', function(){
    $zoneid = Input::get('id');
    Session::put('zone', $zoneid);
    return Redirect::to($_SERVER['frontend']);
});

Route::any('/credential/auth', 'UserController@authenticationProcess');

//12:10 am
Route::get('/cron/resetOrderTrace', function(){

    set_time_limit(0);
    ini_set('memory_limit', '-1');

    DB::table('Customer')->update(['today'=>'','tomorrow'=>'']);

    $invoices = Invoice::where('deliveryDate',strtotime('00:00:00'))->lists('customerId');
    if(count($invoices)>0)
    DB::table('Customer')->wherein('customerId',$invoices)->update(['today'=>1]);

    $invoices_tomorrow = Invoice::where('deliveryDate',strtotime('tomorrow'))->lists('customerId');
    if(count($invoices_tomorrow)>0)
    DB::table('Customer')->wherein('customerId',$invoices_tomorrow)->update(['tomorrow'=>1]);


    // customer and product analysis date update
    $times  = array();
    $current_year = date('Y');
    $current_month = date("n");
    for($month = $current_month; $month <= $current_month; $month++) {
        $first_minute = mktime(0, 0, 0, $month, 1,$current_year);
        $last_minute = mktime(23, 59, 0, $month, date('t', $first_minute),$current_year);
        $times[$month] = array($first_minute, $last_minute);
    }

    // update datawarehouse_custoemr table.
    foreach($times as $k=>$v){

        $info =  DB::select(DB::raw('SELECT COUNT(1) as total, sum(amount) as amount,customerId FROM invoice WHERE invoiceStatus !=99 and invoiceStatus !=98 and invoiceStatus !=97 and invoiceStatus !=96 and deliveryDate BETWEEN '.$v[0].' AND '.$v[1].' GROUP BY customerId'));
        $info_return =  DB::select(DB::raw('SELECT COUNT(1) as total, sum(amount) as amount,customerId FROM invoice WHERE invoiceStatus =98 and deliveryDate BETWEEN '.$v[0].' AND '.$v[1].' GROUP BY customerId'));

        foreach($info_return as $v){
            $arr[$v->customerId]['total'] = $v->total;
            $arr[$v->customerId]['amount'] = $v->amount;
        }
        if(count($info)>0){
            datawarehouse_customer::where('month',$k)->where('year',$current_year)->delete();
            foreach($info as $v1){
                $save = new datawarehouse_customer();
                $save->customer_id = $v1->customerId;

                if(isset($arr[$v1->customerId])){
                    $save->amount = $v1->amount-$arr[$v1->customerId]['amount'];
                    $save->qty = $v1->total-$arr[$v1->customerId]['total'];
                }else{
                    $save->amount = $v1->amount;
                    $save->qty = $v1->total;
                }

                $save->month = $k;
                $save->year = $current_year;
                $save->save();
            }
            echo $k."月<br>";
        }else{
            echo "no data";
        }

    }

//end of update datawarehouse_customer table


// update datawarehouse_product table;
    foreach($times as $k=>$v){
        $invoiceQ = [];
        // $info =  DB::select(DB::raw('SELECT SUM(productQty) as total, sum(productQty*productPrice) as amount,productId FROM invoiceitem WHERE invoiceId IN (SELECT invoiceId FROM invoice WHERE invoiceStatus !=99 and invoiceStatus !=98 and invoiceStatus !=97 and invoiceStatus !=96 and deliveryDate BETWEEN '.$v[0].' AND '.$v[1].') GROUP BY productId'));

        /*  $invoices = Invoice::whereNoIn('invoiceStatus',[98,97,96])->wherebetween('deliveryDate',[$v[0],$v[1]])->lists('invoiceId');
          $info = InvoiceItem::leftJoin('Product', function ($join) {
              $join->on('InvoiceItem.productId', '=', 'Product.productId');
          })->whereIn('invoiceId',$invoices)->get();*/



        // $invoices = Invoice::whereNotIn('invoiceStatus',[97,96])->wherebetween('deliveryDate',[$v[0],$v[1]])->lists('invoiceId');


        $invoiceitems = invoiceitem::select('invoiceitem.productId','invoiceitem.invoiceId','invoiceStatus','productPrice','productQty','productPacking_carton','productPacking_inner','productPacking_unit','productPackingName_unit','productPackingName_carton','productQtyUnit')
            ->leftJoin('Product', function ($join) {
                $join->on('invoiceitem.productId', '=', 'Product.productId');
            })
            ->leftJoin('Invoice', function ($join) {
                $join->on('invoiceitem.invoiceId', '=', 'Invoice.invoiceId');
            })->whereNotIn('invoiceStatus',[97,96,99])->wherebetween('deliveryDate',[$v[0],$v[1]])
            ->orderBy('deliveryDate')
            ->get();




        foreach($invoiceitems as $k2 => $v){
            $invoiceQ[$v->productId]['productId'] = $v->productId;
            $invoiceQ[$v->productId]['amount'] = (isset($invoiceQ[$v->productId]['amount'])?$invoiceQ[$v->productId]['amount']:0) + $v->productPrice* (($v->invoiceStatus==98)?-1:1) * $v->productQty;

            if(!isset($invoiceQ[$v->productId]['normalizedQty'])){
                $invoiceQ[$v->productId]['normalizedQty'] = 0;
            }

            $carton = ($v->productPacking_carton) ? $v->productPacking_carton:1;
            $inner = ($v->productPacking_inner) ? $v->productPacking_inner:1;
            $unit = ($v->productPacking_unit) ? $v->productPacking_unit:1;

            if($v->invoiceStatus == 98){
                if($v->productQtyUnit == 'carton')
                    $real_normalized_unit =  $v->productQty*$inner*$unit*-1;
                else if($v->productQtyUnit == 'inner')
                    $real_normalized_unit =  $v->productQty*$unit*-1;
                else
                    $real_normalized_unit =  $v->productQty * -1;
            }else{
                if($v->productQtyUnit == 'carton')
                    $real_normalized_unit =  $v->productQty*$inner*$unit;
                else if($v->productQtyUnit == 'inner')
                    $real_normalized_unit =  $v->productQty*$unit;
                else
                    $real_normalized_unit =  $v->productQty;
            }

            $invoiceQ[$v->productId]['normalizedQty'] +=  $real_normalized_unit;
            $invoiceQ[$v->productId]['normalizedUnitName'] = $v->productPackingName_unit;
            $invoiceQ[$v->productId]['unitPerCarton'] = $carton*$inner*$unit;
            $invoiceQ[$v->productId]['cartonName'] = $v->productPackingName_carton;
        }



        foreach($invoiceQ as &$vv){
            $vv['cartonQtys'] = number_format($vv['normalizedQty']/$vv['unitPerCarton'],1,'.','');
        }

        if(count($invoiceQ)>0){
            datawarehouse_product::where('month',$k)->where('year',$current_year)->delete();
            foreach($invoiceQ as $k1 => $v1){
                $save = new datawarehouse_product();
                $save->data_product_id = $v1['productId'];
                $save->amount = $v1['amount'];
                $save->qty = $v1['cartonQtys'];
                $save->unitName = $v1['cartonName'];
                $save->month = $k;
                $save->year = $current_year;
                $save->save();
            }
            echo $k."月<br>";
        }

    }
//end of update datawarehouse_customer table




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
        $days_ago = date('Y-m-d', strtotime('-6 days', strtotime(date('Y-m-d'))));
    else
        $days_ago = date('Y-m-d', strtotime('-5 days', strtotime(date('Y-m-d'))));

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

    Invoice::where('deliveryDate','<=',strtotime($accurage_date))->where('paymentTerms',1)->where('invoiceStatus',2)->where('discount',0)->update(['invoiceStatus'=>'30','paid'=>DB::raw('amount')]);
    Invoice::where('deliveryDate','<=',strtotime($accurage_date))->where('paymentTerms',2)->where('invoiceStatus',2)->update(['invoiceStatus'=>'20']);

    ;

    $accurage_date = '\''.$accurage_date.'\'';

       $sql = 'INSERT INTO printqueue_record SELECT * FROM printqueue WHERE created_at <='.$accurage_date;
       $true = DB::statement($sql);
    if($true)
    {
      $sql = 'DELETE FROM printqueue WHERE created_at <='.$accurage_date;
      DB::select(DB::raw($sql));
    }


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


