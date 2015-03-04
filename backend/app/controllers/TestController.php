<?php
/*
require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
use google\appengine\api\cloud_storage\CloudStorageTools;*/
use Toddish\Verify\Models\Role;
class TestController extends BaseController {

    public function testMethod()
    {/*
        function get_numerics ($str) {
            preg_match_all('/\d+/', $str, $matches);
            return @$matches[0][0];
        }
        
        $file = file_get_contents('./product-ext.csv');
        $file = explode("\n", $file);
        foreach($file as $rowO)
        {
            $row = explode(',', $rowO);
            $product = new Product();
            
            $x = explode('*', $row[3]);
            
            
            if(count($x) > 3)
            {
                $product->productId = $row[1];
                $product->productName_chi = trim($row[2]);
                $product->productName_eng = trim($row[2]);
                
                $product->productPacking_carton = (get_numerics($x[0]) ? get_numerics($x[0]) : 1);
                $product->productPacking_inner = (get_numerics($x[1]) ? get_numerics($x[1]) : 1);
                $product->productPacking_unit = (get_numerics($x[2]) ? get_numerics($x[2]) : 1);
                $product->productPacking_size = str_replace(' ', '', $x[3]);
                
                $product->productPackingName_carton = str_replace('/', '', $row[7]);
                $product->productPackingName_inner = str_replace(get_numerics($x[1]), '', $x[1]);
                if($product->productPackingName_inner == '箱' OR !$product->productPackingName_inner OR $product->productPackingName_inner == "")
                {
                    $product->productPackingName_inner = '內箱';
                }
                $product->productPackingName_unit = str_replace(get_numerics($x[2]), '', $x[2]);
                
                $product->productStdPrice_carton = str_replace(array('$', ' '), '', $row[6]);
                $product->productStdPrice_inner = str_replace(array('$', ' '), '', $row[5]);
                $product->productStdPrice_unit = str_replace(array('$', ' '), '', $row[4]);
                
                $product->productCost_unit = $row[8];
                $product->productStatus = 'open';
            }
            else
            {
                $product->productId = $row[1];
                $product->productName_chi = trim($row[2]);
                $product->productName_eng = trim($row[2]);
                
                $product->productStatus = 'suspended';
            }
            
            //$product->save();
        }*/
        $role = Role::find('2');
        $role->permissions()->sync(array(1, 5, 6, 10, 4, 12, 8));
        /*
        function microtime_float()
        {
            list($usec, $sec) = explode(" ", microtime());
            return ((float)$usec + (float)$sec);
        }
        # Request
       $timer['request'] = microtime_float();
       $keyword = Input::has('client_keyword') && Input::get('client_keyword') != '' ? Input::get('client_keyword') : 'na';
       # Process
       if($keyword != 'na')
       {           
           $clientArray = Customer::select('customerId', 'customerName_chi', 'address_chi', 'deliveryZone', 'phone_1', 'routePlanningPriority', 'paymentTermId', 'discount')
                                   ->wherein('deliveryZone', explode(',', Auth::user()->temp_zone))
                                   ->where(function($query) use($keyword)
                                   {
                                       $query->where('customerName_chi', 'LIKE', '%'.$keyword.'%')
                                       ->orwhere('phone_1', 'LIKE', '%'.$keyword.'%')
                                       ->orwhere('customerId', 'LIKE', '%' . $keyword . '%');
                                   })
                                   ->with('Zone')
                                   ->limit(50)
                                   ->get();
          $timer['query'] = microtime_float();
           
       }
       else
       {
           $clientArray = Customer::where('deliveryZone', Session::get('zone'))
                                  ->with('Zone')
                                  ->limit(15)
                                  ->get(); 
           $timer['query'] = microtime_float();
     
       }
       dd($timer, DB::getQueryLog());
       return Response::json($clientArray);
       */
       /*
        $image = new InvoiceImage();
        $files = $image->generate('I1501-000005')->saveAll();
        foreach($files as $f)
        {
            $object_image_url[] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
        }
        print_r($object_image_url);
        exit('ok');
        */
/*
        $e = Invoice::where('invoiceId', 'I1501-000001')->first();
        $image = new InvoiceImage();
        $files = $image->generate('I1501-000001')->show('0');
        
        exit;
        $task = new PushTask('/queue/generate-invoice-image.queue', ['invoiceId' => 'I1501-000001']);
        $task_name = $task->add();
        */
        /*
        var_dump(debug_backtrace());
        exit;
        $pl = new PickingList(1422115200, '9');
        $pl->generatePDF()->show();
        
        exit;*/
        /*
        $image = new InvoiceImage();
        $files = $image->generate('I1501-000091')->show('0');
        
        /*
        $e = Invoice::where('invoiceId', 'I1501-000091')->first();
        
        
        // generate preview version
        $preview = new InvoiceImage();
        $files = $preview->generate('I1501-000091')->saveAll();
        $j = 0;
        foreach($files as $f)
        {
            //$files[$j]['url'] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
            $file['preview_url'][$j] = $files[$j]['url'] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
            $file['preview_storage'][$j] = $f['fullpath'];
            $j++;
        }
        
        // generate print version
        $print = new InvoiceImage();
        $print = $print->generate('I1501-000091', true)->saveAll();
        $j = 0;
        foreach($files as $f)
        {
            //$files[$j]['url'] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
            $file['preview_url'][$j] = $files[$j]['url'] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
            $file['preview_storage'][$j] = $f['fullpath'];
            $j++;
        }
        
        
        $e->invoiceImage = serialize($files);
        $e->save();
        
        exit('completed');
        */
        /*
        $reportId = 'pickinglist';
        
        $factory = new ReportFactory($reportId);
        $factory->run();
        exit;
        
        $pl = new CashReceiptSummary(1424016000, '1');
        $pl->generatePDF()->show();
        
        $ftp_user_name = 'yatfai';
        $ftp_user_pass = 'Brehep3f';
        $ftp_server = '128.199.192.177';
        $conn_id = ftp_connect($ftp_server);
        
        // login with username and password
        $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        
        // check connection
        if ((!$conn_id) || (!$login_result)) {
            echo "FTP connection has failed!";
            echo "Attempted to connect to $ftp_server for user $ftp_user_name";
            exit;
        } else {
            echo "Connected to $ftp_server, for user $ftp_user_name";
        }
        exit;
        
        $ftp_user_name = 'yatfai';
        $ftp_user_pass = 'Brehep3f';
        $ftp_server = '128.199.192.177';
        $conn_id = ftp_connect($ftp_server);
        ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        echo ftp_pwd($conn_id);
        
        ftp_chdir($conn_id, '/home/yatfai/invoices/');
        echo ftp_pwd($conn_id);
        exit;
        
        $pdf = new InvoicePdf();
        $pdf->generate('I1501-000003')->save();
        */
        /*
        $image = new InvoiceImage();
        $files = $image->generate('I1502-000032')->show(1);
        
        $pdf = new Fpdf();
        $pagesize = "A5";
        $image = unserialize('a:2:{s:9:"print_url";a:1:{i:0;s:118:"http://lh5.ggpht.com/V6IBW_3Nf6aSF8KnL5ZFHtQM5Zw7NTOzdKaEBybsJd9lYqGHSZmHW20H2f0TqT-Xbj5yKFgbU9LcdN03q0lyqXiK_2lGWw=s0";}s:13:"print_storage";a:1:{i:0;s:83:"gs://lpk-general-bucket/storage/invoices_images/1502/print/print_I1502-000071-1.png";}}');
        $section = 0;
        try
        {
            for($i = 1; $i <= 2; $i++)
            {
                foreach($image['print_url'] as $index => $url)
                {
            
                    if($section == 0 || $section  % 2 == 0)
                    {
                        $pdf->AddPage();
                        $y = 0;
                    }
            
                    
                
                    $pdf->Image($url, 3, $y -2, 207, 0, 'PNG');
            
                    if($pagesize == "A5")
                    {
                        $y += 148;
                    }
                    else
                    {
                        $y = 0;
                    }
            
                $section++;
                }
            }
        }
        catch(Exception $e)
        {
            exit('gg');
        }
        
        $pdf->Output();
        */
    
    }

} 