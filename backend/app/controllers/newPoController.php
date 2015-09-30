<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class newPoController extends BaseController {

    public $newPoCode = "";
    public $message = "";
    public function jsonNewPo() {
        $itemIds = [];
        $order = Input :: get('order');
        $product = Input :: get('product');
        $poCode = $order['poCode'];
        $this->po = new PurchaseorderManipulation($poCode);
        $this->po->setInvoice($order);
     //   pd($order);
          $have_item=false;    //fOR UPDATE
          foreach ($product as $p) {
          if ($p['dbid'] != '' && $p['deleted'] == 0 && $p['qty']>0) 
               $itemIds[] = $p['dbid'];
          if ($p['dbid'] == '' && $p['code'] != '') 
          $have_item = true;
          } 

          
        //Below should be uncomment when the update function is ready
          if ($order['poCode'] != '') {  //update
          if (count($itemIds) == 0 && !$have_item)
          return [
          'result' => false,
          'status' => 0,
          'message' => '未有下單貨品',
          ];
          else if(count($itemIds) == 0) // If all the items are deleted
              Poitem::where('poCode', $order['poCode'])->delete();
          else
              Poitem::whereNotIn('id', $itemIds)->where('poCode', $order['poCode'])->delete();

          } 
          
        
  
       
            foreach ($product as $p) {
            $this->po->setItem($p['dbid'],$p['code'], $p['unitprice'], $p['unit'], $p['qty'], $p['discount_1'], $p['discount_2'], $p['discount_3'], $p['allowance_1'], $p['allowance_2'], $p['allowance_3'], $p['deleted'], $p['currencyId'], $p['remark']);

            }
        $message = $this->doValidation($order); 
        if($message == "")
        {
            $this->newPoCode = $this->po->save();
            return $this->newPoCode;
        }else{
            return [
	        'result' => false,
	        'poCode' => 0,
                'message' => $message,
	    ];
        }
        
        // $result = $ci->save();
        

       
        //return Response::json($result);
        //  $pom = new PoitemManipulation($poCode);
        //  $pom->getnewPoItem($poCode);
        //$pom->save($product);
        
    }

    public function jsonQueryPo() {
        $itemIds = array('桶', '排', '扎', '箱');

        $ids = "'" . implode("','", $itemIds) . "'";
        $mode = Input::get('mode');
        
        if ($mode == 'collection') {
            $filter = Input::get('filterData');
             $sorting = "poCode";
             $current_sorting = $filter['current_sorting'];
            if ($filter['sorting'] != "") {
                $sorting = $filter['sorting'];
             }


            $purchaseOrder = Purchaseorder::select(['poCode', 'poDate', 'etaDate', 'actualDate', 'poStatus', 'suppliers.supplierName', 'purchaseorders.updated_at', 'users.username','poAmount','purchaseorders.location'])
                    ->leftJoin('suppliers', function($join) {
                        $join->on('suppliers.supplierCode', '=', 'purchaseorders.supplierCode');
                    })
                    ->leftJoin('users', function($join) {
                        $join->on('users.id', '=', 'purchaseorders.updated_by');
                    })
                    ->orderby($sorting, $current_sorting);
               
                 /*     if ($filter['poStatus'] == 99) {
                $purchaseOrder->onlyTrashed();
            } else if ($filter['poStatus'] != 100) {
               $purchaseOrder->where('poStatus', $filter['poStatus']);
            }*/
           //$dDateBegin = );
          //  $dDateEnd = strtotime($filter['endPodate']);
            //dd($dDateBegin, $dDateEnd, date("Y-m-d H:i:s", $dDateBegin), date("Y-m-d H:i:s", $dDateEnd));

          /*  if(isset($filter['deliverydate1']))
                $invoice = Invoice::select('*');
            else*/
           $purchaseOrder
                   ->where('purchaseorders.supplierCode', 'LIKE', '%' . $filter['supplier'] . '%')
                   ->where('poCode', 'LIKE', '%' . $filter['poCode'] . '%')
                   ->where('poStatus', 'LIKE', '%' . $filter['poStatus'] . '%')
                  // ->where('purchaseorders.poDate', '>=', $filter['startPodate'])->where('purchaseorders.poDate', '<=', $filter['endPodate']);
                   ->whereBetween('purchaseorders.poDate', array($filter['startPodate'],$filter['endPodate']));
        
           

            return Datatables::of($purchaseOrder)
                            ->addColumn('link', function ($purchaseOrde) {
                                return '<span onclick="editPo(\'' . $purchaseOrde->poCode . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
                            })
                            ->editColumn('poStatus', function($purchaseOrde) {
                                $statusValue = "";
                                if ($purchaseOrde->poStatus == 1) {
                                    $statusValue = "正常";
                                } else if ($purchaseOrde->poStatus == 20) {
                                    $statusValue = "已收貨";
                                } else if ($purchaseOrde->poStatus == 30) {
                                    $statusValue = "已付款";
                                } else if ($purchaseOrde->poStatus == 99) {
                                    $statusValue = "暫停";
                                }
                                return $statusValue;
                            })
                           
                            ->make(true);
        } else if ($mode == 'single') {
            $poCode = Input::get('poCode');
             $purchaseOrder = Purchaseorder :: select('poCode','poDate','etaDate','actualDate','poStatus','suppliers.supplierName','suppliers.countryId','discount_1','discount_2','allowance_1','allowance_2','purchaseorders.supplierCode','suppliers.contactPerson_1','currencies.currencyName','poAmount','poReference','poRemark','receiveDate','purchaseorders.location')
                     ->where('poCode',$poCode)
                      ->leftJoin('suppliers', function($join) {
                        $join->on('suppliers.supplierCode', '=', 'purchaseorders.supplierCode');
                      })
                       ->leftJoin('currencies', function($join) {
                        $join->on('currencies.currencyId', '=', 'purchaseorders.currencyId');
                      })
                    ->get()->toArray();
             $items = Poitem :: select('productName_chi','poCode','product.productId','productQty','productQtyUnit','discount_1','discount_2','discount_3','allowance_1','allowance_2','allowance_3','unitprice','remark','productUnitName')
                     ->where('poCode',$poCode)
                     ->leftJoin('product', function($join) {
                        $join->on('product.productId', '=', 'poitems.productId');
                      })
                     ->get()->toArray();
             $purchaseOrder['po'] = $purchaseOrder;
             $purchaseOrder['items'] = $items;
        }
        return Response::json($purchaseOrder);
    }
    
      public function doValidation($e)
    {

         $rules = [
	            'supplierCode' => 'required',
	            'poDate' => 'required',
                    'etaDate' => 'required',
                    'poStatus' => 'required',
	        ];
         
      
         $validator = Validator::make($e, $rules);
	 if ($validator->fails())
	  {
	       $this->message = $validator->messages()->all();
	           // return Redirect::action('UserController@authenticationProcess')->with('flash_error', 'Invalid Credential. Please try again');
	  }
       
          return $this->message;
          
    }
    
    public function getSinglePo()
    {
         $poCode = Input :: get('poCode');
       
         $po = Purchaseorder::where('poCode',$poCode);

         $poRecord = Purchaseorder :: getFullPo($po);
         
         return Response::json($poRecord);
         
    }
    
  
    
     public function jsonGetSingleInvoice()
    {
        $invoiceId = Input::get('invoiceId');

        $base = Invoice::where('invoiceId', $invoiceId);

        $invoice = Invoice::categorizePendingInvoice(Invoice::getFullInvoice($base));

        $returnInformation = [
            'invoice' => array_values($invoice['categorized'])[0]['invoices'][0],
            'entrieinfo' => array_values($invoice['categorized'])[0]['zoneName'],
        ];
        return Response::json($returnInformation);
    }
    
    public function voidPo()
    {
        $poCode = Input :: get('poCode');
        $order = Input ::get('updateStatus');
        if($order == 'delete')
        {
             $purOrder = new PurchaseorderManipulation($poCode);
             $purOrder->deleteSave();
        }       
    }
    
    public function printPo()
    {
        $poCode = Input :: get('poCode'); // get po code
        $lang = Input ::get('lang');
        
        $poAndItems = Purchaseorder::where('poCode',$poCode)->with(['Poitem'=>function($query){
            $query->with('productDetail');
        }])->with('Supplier','Currency')->first();
        
        //$good = array_chuck($poAndItems['Poitem'],1,true);
        
        if(isset($poAndItems)){
        
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        
        $this->generateHeader($pdf,$lang);
        
        $this->subHeader($poAndItems,$pdf,$lang);

         $pdf->Output('','I');
        }
    }
    
    public function generateHeader($pdf,$lang)
    {
        
        $pdf->SetFont('chi','',12);
        $pdf->setXY(10, 40);
        $pdf->Cell(0, 0,"炳 記 行 貿 易 有 限 公 司",0,1,"L");


        $pdf->SetFont('chi','',9);
        $pdf->setXY(10, 45);
        $pdf->Cell(0, 0,"Flat B, 9/F., Wang Cheung Industrial Building, ",0,1,"L");
        
        $pdf->SetFont('chi','',9);
        $pdf->setXY(10, 50);
        $pdf->Cell(0, 0,"6 Tsing Yeung St., Tuen Mun, N.T. Hong Kong., ",0,1,"L");
        
        $image = public_path('logo.jpg');
        $pdf->Cell( 40, 40, $pdf->Image($image, 10, 7, 25,28), 0, 0, 'L', false );
        
        if($lang == 'eng')
        {
            $pdf->SetFont('chi','',9);
            $pdf->setXY(10, 55);
            $pdf->Cell(0, 0,"TEL:24552266    FAX:24552449",0,1,"L");
            
            $pdf->SetFont('chi','',20);
           $pdf->setXY(150, 20);
           $pdf->Cell(0, 0,"Purchase order",0,1,"L");
        }
        if($lang == 'chi')
        {
            $pdf->SetFont('chi','',9);
            $pdf->setXY(10, 55);
            $pdf->Cell(0, 0,"電話:24552266    傳真:24552449",0,1,"L");
            
           $pdf->SetFont('chi','',20);
           $pdf->setXY(170, 20);
           $pdf->Cell(0, 0,"採購單",0,1,"L");
        }
      
        
        
    }
    
    public function subHeader($poAndItems,$pdf,$lang)
    {
         $translate = array('包'=>'pack','箱'=>'crate','桶'=>'barrel','札'=>'note','盒'=>'box','排'=>'row','隻'=>'mere','大包'=>'BP');
        if($lang == 'chi')
        {
            $pdf->SetFont('chi','',10);
            $pdf->setXY(150, 40);
            $pdf->Cell(0, 0,"採購單日期:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(150, 45);
            $pdf->Cell(0, 0,"採購單編號:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(10, 65);
            $pdf->Cell(0, 0,"供應商名稱:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(10, 70);
            $pdf->Cell(0, 0,"供應商地址:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(100, 65);
            $pdf->Cell(0, 0,"送到:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(120, 65);
            $pdf->Cell(0, 0," 屯門青楊街6號宏昌工業大廈,",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(120, 70);
            $pdf->Cell(0, 0," 9樓,B室",0,1,"L");
        }
        if($lang == 'eng')
        {
            $pdf->SetFont('chi','',10);
            $pdf->setXY(140, 40);
            $pdf->Cell(0, 0,"Date of purchasing:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(140, 45);
            $pdf->Cell(0, 0,"Purchase No.:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(10, 65);
            $pdf->Cell(0, 0,"Supplier name:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(10, 70);
            $pdf->Cell(0, 0,"Supplier address:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(100, 65);
            $pdf->Cell(0, 0,"ship to:",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(120, 65);
            $pdf->Cell(0, 0," Wang Cheung Indl Bldg, 6th, 9th Floor, Room B,",0,1,"L");

            $pdf->SetFont('chi','',10);
            $pdf->setXY(120, 70);
            $pdf->Cell(0, 0," Qing Yang Street, Tuen Mun, New Territories",0,1,"L");
        }
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(175, 40);
        $pdf->Cell(0, 0,$poAndItems['poDate'],0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(175, 45);
        $pdf->Cell(0, 0,$poAndItems['poCode'],0,1,"L");
        

        $pdf->SetFont('chi','',10);
        $pdf->setXY(40, 65);
        $pdf->Cell(0, 0,$poAndItems['supplier']['supplierName']."(".$poAndItems['supplierCode'].")",0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(40, 70);
        $pdf->Cell(0, 0,$poAndItems['supplier']['address'],0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(40, 76);
        $pdf->Cell(0, 0,$poAndItems['supplier']['address1'],0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(40, 80);
        $pdf->Cell(0, 0,$poAndItems['supplier']['address2'],0,1,"L");
        
       
           
    $pdf->SetXY( 4, 90 );

    $this->setTableTitle($pdf);

    if($lang == 'chi')
    {
        $pdf->Cell(60,5,"貨幣",1,0,'C',true);
        $pdf->Cell(70,5,"運輸方法",1,0,'C',true);
        $pdf->Cell(70,5,"預算到貨日期",1,0,'C',true);

        $pdf->Ln();  // line break 

         $pdf->SetX(4);
        $this->setTableBox($pdf);

        $pdf->Cell(60,5,$poAndItems['currency']['currencyName']."(".$poAndItems['currency']['currencyId']."D)",1,0,'C',true);
        $pdf->Cell(70,5,"陸運",1,0,'C',true);
        $pdf->Cell(70,5,$poAndItems['etaDate'],1,0,'C',true);

        $pdf->Ln();

        $pdf->SetXY( 4, 110 );

        $this->setTableTitle($pdf);

        $pdf->Cell(10,5,"編號",1,0,'C',true);
        $pdf->Cell(20,5,"產品編號",1,0,'C',true);
        $pdf->Cell(50,5,"產品名稱",1,0,'C',true);
        $pdf->Cell(12,5,"數量",1,0,'C',true);
        $pdf->Cell(10,5,"單位",1,0,'C',true);
        $pdf->Cell(30,5,"折扣(%)",1,0,'C',true);
        $pdf->Cell(30,5,"現金折扣($)",1,0,'C',true);
        $pdf->Cell(15,5,"單價($)",1,0,'C',true);
        $pdf->Cell(23,5,"總數($)",1,0,'C',true);
    }
    
    if($lang == 'eng')
    {
       $pdf->Cell(60,5,"Currency",1,0,'C',true);
       $pdf->Cell(70,5,"Shipping methods",1,0,'C',true);
       $pdf->Cell(70,5,"ETA date",1,0,'C',true);

       $pdf->Ln();  // line break 

        $pdf->SetX(4);
       $this->setTableBox($pdf);
       if($lang == 'chi')
           $pdf->Cell(60,5,$poAndItems['currency']['currencyName']."(".$poAndItems['currency']['currencyId']."D)",1,0,'C',true);
       else if($lang == 'eng')
           $pdf->Cell(60,5,$poAndItems['currency']['currencyId']."D",1,0,'C',true);
       $pdf->Cell(70,5,"Land transportation",1,0,'C',true);
       $pdf->Cell(70,5,$poAndItems['etaDate'],1,0,'C',true);

       $pdf->Ln();

       $pdf->SetXY( 4, 110 );

       $this->setTableTitle($pdf);

       $pdf->Cell(10,5,"No.",1,0,'C',true);
       $pdf->Cell(20,5,"ProductId",1,0,'C',true);
       $pdf->Cell(50,5,"Product Name",1,0,'C',true);
       $pdf->Cell(12,5,"Num",1,0,'C',true);
       $pdf->Cell(10,5,"Unit",1,0,'C',true);
       $pdf->Cell(30,5,"Discount(%)",1,0,'C',true);
       $pdf->Cell(30,5,"Cash Dis($)",1,0,'C',true);
       $pdf->Cell(15,5,"Price($)",1,0,'C',true);
       $pdf->Cell(23,5,"Total($)",1,0,'C',true);
    }
    $pdf->Ln();  
    $this->setTableBox($pdf);
    
    $total = 0;
    $num = 1;
    
   // $j = 0;
    foreach($poAndItems['poitem'] as $i)
    { 
    // $j+=5;
        $pdf->SetX(4);
            $pdf->Cell(10,5,$num,1,0,'C',true);
            $pdf->Cell(20,5,$i['productId'],1,0,'L',true);
            $pdf->Cell(50,5,$i['product_detail']['productName_chi'],1,0,'L',true);
            $pdf->Cell(12,5,$i['productQty'],1,0,'L',true);
            if($lang == 'chi')
            {
                $pdf->Cell(10,5,$i['productUnitName'],1,0,'L',true);
            }
            else
            {
                if(isset($translate[$i['productUnitName']]))
                {
                    $pdf->Cell(10,5,$translate[$i['productUnitName']],1,0,'L',true);
                }else
                {
                    $pdf->Cell(10,5,$i['productUnitName'],1,0,'L',true);
                }
            }
                
            $pdf->Cell(30,5,round($i['discount_1'], 1).' , '.round($i['discount_2'], 1).' , '.round($i['discount_3'], 1),1,0,'L',true);
            $pdf->Cell(30,5,round($i['allowance_1'], 1).' , '.round($i['allowance_2'], 1).' , '.round($i['allowance_3'], 1),1,0,'L',true);
            
            $pdf->Cell(15,5,$i['unitprice'],1,0,'L',true);
            $pdf->Cell(23,5,$i['unitprice'] * $i['productQty'],1,0,'L',true);
            $total += $i['unitprice'] * $i['productQty'] * (100 - $i['discount_1'])/100 * (100 - $i['discount_2'])/100 * (100 - $i['discount_3'])/100 - $i['allowance_1'] - $i['allowance_2'] - $i['allowance_3'];
            $pdf->Ln();  
            $num++;
     }
     
     if($lang == "chi")
     {
      $pdf->SetDrawColor(255);
      $pdf->Ln(3);  
      $pdf->Cell(149,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(20,5,"原價:",1,0,'L',true);
      $pdf->Cell(25,5,'$ '.round($total, 2, PHP_ROUND_HALF_UP),1,0,'R',true);
      

      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(149,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(20,5,"採購折扣1:",1,0,'L',true);
      $pdf->Cell(25,5,$poAndItems['discount_1'] . '%',1,0,'R',true);
      
      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(149,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(20,5,"採購折扣2:",1,0,'L',true);
      $pdf->Cell(25,5,$poAndItems['discount_2']. '%',1,0,'R',true);
      
      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(149,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(20,5,"現金折扣1:",1,0,'L',true);
      $pdf->Cell(25,5,'$'.$poAndItems['allowance_1'],1,0,'R',true);
      
      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(149,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(20,5,"現金折扣2:",1,0,'L',true);
      $pdf->Cell(25,5,'$'.$poAndItems['allowance_2'],1,0,'R',true);
      
      $countTotal = $total * (100 - $poAndItems['discount_1'])/100 * (100 - $poAndItems['discount_2'])/100 - $poAndItems['allowance_1'] - $poAndItems['allowance_2'];
      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(149,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(20,5,"總額:",1,0,'L',true);
      $pdf->Cell(25,5,'$ '.round($countTotal, 2, PHP_ROUND_HALF_UP),1,0,'R',true);
      
       $pdf->Ln(20);  
       
       $pdf->SetDrawColor(255);
       $pdf->SetFont('chi','',10);  
       $pdf->Cell(10, 5,"1.請寄出您一式兩份的發票",0,1);
      // $pdf->Cell(10, $j -100,"1.Please send two copies of your invoice.:",0,1,"L");
       
   
      // $pdf->SetXY( 10,  $j - 115);
       $pdf->Cell(10, 5,"2.請按照價格及交貨方式輸入資料",0,1);
       
       //$pdf->SetXY( 10,  $j - 110);
       $pdf->Cell(10, 5,"3.如果您無法運送貨物,請立即通知我們",0,1);
       
       //$pdf->SetXY( 10,  $j - 105);
       $pdf->Cell(10, 5,"4.請郵寄到:",0,1);
       
       $pdf->SetX(15);
       $pdf->Cell(15, 5,$poAndItems['supplier']['address'],0,1);
       
       $pdf->SetX(15);
       $pdf->Cell(15, 5,$poAndItems['supplier']['address1'],0,1);
       $pdf->SetX(15);
       $pdf->Cell(15, 5,$poAndItems['supplier']['address2'],0,1);
       
        $pdf->Ln(20);  
  
     //  $pdf->SetXY( 15,  $j - 80);
       $pdf->SetFont('chi','',10);
       $pdf->SetDrawColor(255);
       $pdf->Cell(100,0,"",1,0,'R',true);
       
       $pdf->SetDrawColor(0,80,180);
       $pdf->Cell(90,0,"",1,0,'R',true);
       
       $pdf->Ln(5);  
       
       $pdf->SetDrawColor(255);
       $pdf->Cell(100,0,"",1,0,'R',true);
      // $pdf->Cell(150, 10,"Authorized by",0,1,'R');
       $pdf->Cell(45,0,"簽名:",1,0,'L',true);
       //$pdf->Cell(150, 10,"Date",0,1,'R');
       $pdf->Cell(20,0,"日期:",1,0,'R',true);
     }
     
      if($lang == "eng")
     {
      $pdf->SetDrawColor(255);
      $pdf->Ln(3);  
      $pdf->Cell(139,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(30,5,"Original price:",1,0,'L',true);
      $pdf->Cell(25,5,'$ '.round($total, 2, PHP_ROUND_HALF_UP),1,0,'R',true);
      

      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(139,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(30,5,"Discount1:",1,0,'L',true);
      $pdf->Cell(25,5,$poAndItems['discount_1'] . '%',1,0,'R',true);
      
      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(139,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(30,5,"Discount2:",1,0,'L',true);
      $pdf->Cell(25,5,$poAndItems['discount_2']. '%',1,0,'R',true);
      
      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(139,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(30,5,"Cash Discount1:",1,0,'L',true);
      $pdf->Cell(25,5,'$'.$poAndItems['allowance_1'],1,0,'R',true);
      
      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(139,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(30,5,"Cash Discount2:",1,0,'L',true);
      $pdf->Cell(25,5,'$'.$poAndItems['allowance_2'],1,0,'R',true);
      
      $countTotal = $total * (100 - $poAndItems['discount_1'])/100 * (100 - $poAndItems['discount_2'])/100 - $poAndItems['allowance_1'] - $poAndItems['allowance_2'];
      $pdf->Ln();
      $pdf->SetDrawColor(255);
      $pdf->Cell(139,0,"",1,0,'L',true);
      $pdf->SetDrawColor(0);
      $pdf->Cell(30,5,"Total:",1,0,'L',true);
      $pdf->Cell(25,5,'$ '.round($countTotal, 2, PHP_ROUND_HALF_UP),1,0,'R',true);
      
       $pdf->Ln(20);  
       
       $pdf->SetDrawColor(255);
       $pdf->SetFont('chi','',10);  
       $pdf->Cell(10, 5,"1.Please send your duplicate invoices",0,1);
      // $pdf->Cell(10, $j -100,"1.Please send two copies of your invoice.:",0,1,"L");
       
   
      // $pdf->SetXY( 10,  $j - 115);
       $pdf->Cell(10, 5,"2.Please enter the data according to the price and delivery",0,1);
       
       //$pdf->SetXY( 10,  $j - 110);
       $pdf->Cell(10, 5,"3.If you are unable to ship goods, please notify us immediately",0,1);
       
       //$pdf->SetXY( 10,  $j - 105);
       $pdf->Cell(10, 5,"4.Please mail to:",0,1);
       
       $pdf->SetX(15);
       $pdf->Cell(15, 5,$poAndItems['supplier']['address'],0,1);
       
       $pdf->SetX(15);
       $pdf->Cell(15, 5,$poAndItems['supplier']['address1'],0,1);
       $pdf->SetX(15);
       $pdf->Cell(15, 5,$poAndItems['supplier']['address2'],0,1);
       
        $pdf->Ln(20);  
  
     //  $pdf->SetXY( 15,  $j - 80);
       $pdf->SetFont('chi','',10);
       $pdf->SetDrawColor(255);
       $pdf->Cell(100,0,"",1,0,'R',true);
       
       $pdf->SetDrawColor(0,80,180);
       $pdf->Cell(90,0,"",1,0,'R',true);
       
       $pdf->Ln(5);  
       
       $pdf->SetDrawColor(255);
       $pdf->Cell(100,0,"",1,0,'R',true);
      // $pdf->Cell(150, 10,"Authorized by",0,1,'R');
       $pdf->Cell(45,0,"Signature:",1,0,'L',true);
       //$pdf->Cell(150, 10,"Date",0,1,'R');
       $pdf->Cell(20,0,"Date:",1,0,'R',true);
     }
       

    }
    
    public function setTableTitle($pdf)
    {
        $pdf->SetFillColor(0, 0, 102); //box color 
        $pdf->SetTextColor(255); //Text color
        $pdf->SetDrawColor(92,92,92);
        $pdf->SetLineWidth(.1); //width of line
    }
    
     public function setT($pdf)
    {
        $pdf->SetFillColor(255); //box color 
        $pdf->SetTextColor(0); //Text color

    }
    
    public function setTableBox($pdf)
    {
        $pdf->SetFillColor(255); //box color
        $pdf->SetTextColor(0); 
        $pdf->SetDrawColor(92,92,92);
        $pdf->SetLineWidth(.1);  //set the line width of box
    }
    
    public function outputShipNote()
    {
        
    }
    
    public function genHead($pdf)
    {
        $pdf->SetFont('chi','',12);
        $pdf->setXY(10, 40);
        $pdf->Cell(0, 0,"炳 記 行 貿 易 有 限 公 司",0,1,"L");


        $pdf->SetFont('chi','',9);
        $pdf->setXY(10, 45);
        $pdf->Cell(0, 0,"TEL:24552266    FAX:24552449 ",0,1,"L");
        
        $image = public_path('logo.jpg');
        $pdf->Cell( 40, 40, $pdf->Image($image, 10, 7, 25,28), 0, 0, 'L', false );
        
        $pdf->SetFont('chi','',20);
        $pdf->setXY(170, 20);
        $pdf->Cell(0, 0,"採購單",0,1,"L");
    }
    
    public function purchaseOrderForm()
    {
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        
        $this->genHead($pdf);
             
        $pdf->SetFont('chi','',14);
        $pdf->setXY(10, 60);
        $pdf->Cell(0, 0,"採購單資料:",0,1,"L");     
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 70);
        $pdf->Cell(0, 0,"採購日期:",0,1,"L");      
        
        $pdf->setXY(35, 72);
        $pdf->Cell(60,0,"",1,0,'R',true);
                      
        $pdf->SetFont('chi','',13);
        $pdf->setXY(100, 70);
        $pdf->Cell(0, 0,"預算到貨日期:",0,1,"L");   
        
        $pdf->setXY(131, 72);
        $pdf->Cell(60,0,"",1,0,'R',true);
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 82);
        $pdf->Cell(0, 0,"供應商名稱:",0,1,"L");   
        
        $pdf->setXY(38, 84);
        $pdf->Cell(153,0,"",1,0,'R',true);
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 95);
        $pdf->Cell(0, 0,"採購折扣1:",0,1,"L");   
        
        $pdf->setXY(38, 97);
        $pdf->Cell(60,0,"",1,0,'R',true);
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(90, 95);
        $pdf->Cell(0, 0,"(%)",0,1,"L");   
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(102, 95);
        $pdf->Cell(0, 0,"採購折扣2:",0,1,"L");   
        
        $pdf->setXY(130, 97);
        $pdf->Cell(60,0,"",1,0,'R',true);
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(181, 95);
        $pdf->Cell(0, 0,"(%)",0,1,"L");   
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 105);
        $pdf->Cell(0, 0,"現金折扣1: $",0,1,"L");   
        
        $pdf->setXY(39, 107);
        $pdf->Cell(60,0,"",1,0,'R',true);        
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(102, 105);
        $pdf->Cell(0, 0,"現金折扣2: $",0,1,"L");   
        
        $pdf->setXY(130, 107);
        $pdf->Cell(60,0,"",1,0,'R',true);
        
        $pdf->setXY(4, 117);
        $pdf->Cell(203,0,"",1,0,'R',true);
        
        $pdf->SetFont('chi','',14);
        $pdf->setXY(10, 123);
        $pdf->Cell(0, 0,"採購單貨品:",0,1,"L");   
        
        $this->setT($pdf);
        $pdf->setXY(4,130);
    
    $pdf->Cell(10,10,"編號",1,0,'C',true);
    $pdf->Cell(50,10,"產品名稱",1,0,'C',true);
    $pdf->Cell(15,10,"數量",1,0,'C',true);
    $pdf->Cell(15,10,"單位",1,0,'C',true);
    $pdf->Cell(35,10,"折扣(%)",1,0,'C',true);
    $pdf->Cell(35,10,"現金折扣($)",1,0,'C',true);
    $pdf->Cell(20,10,"單價($)",1,0,'C',true);
    $pdf->Cell(23,10,"總數($)",1,0,'C',true);
    
    $pdf->setXY(10, 140);
    $num = 1;
    for($num = 1;$num<=9;$num++)
    { 
        $pdf->SetX(4);
            $pdf->Cell(10,15,$num,1,0,'C',true);
            $pdf->Cell(50,15,"",1,0,'L',true);
            $pdf->Cell(15,15,"",1,0,'L',true);
            $pdf->Cell(15,15,"",1,0,'L',true);
            
            $pdf->Cell(35,15,"",1,0,'L',true);
            $pdf->Cell(35,15,"",1,0,'L',true);
            
            $pdf->Cell(20,15,"",1,0,'L',true);
            $pdf->Cell(23,15,"",1,0,'L',true);
            $pdf->Ln();  
     }
   
       
        $pdf->Output('','I');
    }
    
    public function newSupplier()
    {
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        
        $pdf->SetFont('chi','',12);
        $pdf->setXY(10, 40);
        $pdf->Cell(0, 0,"炳 記 行 貿 易 有 限 公 司",0,1,"L");


        $pdf->SetFont('chi','',9);
        $pdf->setXY(10, 45);
        $pdf->Cell(0, 0,"TEL:24552266    FAX:24552449 ",0,1,"L");
        
        $image = public_path('logo.jpg');
        $pdf->Cell( 40, 40, $pdf->Image($image, 10, 7, 25,28), 0, 0, 'L', false );
        
        $pdf->SetFont('chi','',14);
        $pdf->setXY(10, 60);
        $pdf->Cell(0, 0,"供應商資料:",0,1,"L");     
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 75);
        $pdf->Cell(0, 0,"供應商名稱:",0,1,"L");     
        
        $pdf->setXY(39, 77);
        $pdf->Cell(60,0,"",1,0,'R',true);     
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 90);
        $pdf->Cell(0, 0,"地址:",0,1,"L");     
        
        $pdf->setXY(39, 92);
        $pdf->Cell(140,0,"",1,0,'R',true);     
        
        $pdf->setXY(39, 104);
        $pdf->Cell(140,0,"",1,0,'R',true);     
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 115);
        $pdf->Cell(0, 0,"電話1:",0,1,"L");     
        
        $pdf->setXY(39, 117);
        $pdf->Cell(60,0,"",1,0,'R',true);    
        
         $pdf->SetFont('chi','',13);
        $pdf->setXY(110, 115);
        $pdf->Cell(0, 0,"電話2:",0,1,"L");     
        
        $pdf->setXY(130, 117);
        $pdf->Cell(60,0,"",1,0,'R',true);    
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 130);
        $pdf->Cell(0, 0,"fax1:",0,1,"L");     
        
        $pdf->setXY(39, 132);
        $pdf->Cell(60,0,"",1,0,'R',true);   
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(110, 130);
        $pdf->Cell(0, 0,"fax2:",0,1,"L");     
        
        $pdf->setXY(130, 132);
        $pdf->Cell(60,0,"",1,0,'R',true);   
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 145);
        $pdf->Cell(0, 0,"電郵:",0,1,"L");     
        
        $pdf->setXY(39, 146);
        $pdf->Cell(100,0,"",1,0,'R',true);    
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 160);
        $pdf->Cell(0, 0,"貨幣:",0,1,"L");     
        
        $pdf->setXY(39, 161);
        $pdf->Cell(60,0,"",1,0,'R',true);    
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 172);
        $pdf->Cell(0, 0,"國家:",0,1,"L");     
        
        $pdf->setXY(39, 173);
        $pdf->Cell(60,0,"",1,0,'R',true);    
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 185);
        $pdf->Cell(0, 0,"聯繫人1:",0,1,"L");     
        
        $pdf->setXY(39, 187);
        $pdf->Cell(60,0,"",1,0,'R',true);  
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(110, 185);
        $pdf->Cell(0, 0,"聯繫人2:",0,1,"L");     
        
        $pdf->setXY(139, 187);
        $pdf->Cell(60,0,"",1,0,'R',true);  
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 198);
        $pdf->Cell(0, 0,"付款方式:",0,1,"L");     
        
       // $pdf->setXY(39, 200);
        //$pdf->Cell(60,0,"",1,0,'R',true);  
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(40, 198);
        $pdf->Cell(0, 0,"現金 / 信貸",0,1,"L");  
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 211);
        $pdf->Cell(0, 0,"數期:",0,1,"L");     
        
        $pdf->setXY(39, 213);
        $pdf->Cell(60,0,"",1,0,'R',true);  
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 225);
        $pdf->Cell(0, 0,"貸款額貨幣:",0,1,"L");     
        
        $pdf->setXY(39, 227);
        $pdf->Cell(60,0,"",1,0,'R',true);  
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 239);
        $pdf->Cell(0, 0,"貸款限額:",0,1,"L");     
        
        $pdf->setXY(39, 241);
        $pdf->Cell(60,0,"",1,0,'R',true);  
        
        $pdf->SetFont('chi','',13);
        $pdf->setXY(10, 255);
        $pdf->Cell(0, 0,"備註:",0,1,"L");     
        
        $pdf->setXY(39, 257);
        $pdf->Cell(60,0,"",1,0,'R',true);  
        
        
        
     
        
      
        
        
        
        $pdf->Output('','I');
    }
    
   


    

 


}
