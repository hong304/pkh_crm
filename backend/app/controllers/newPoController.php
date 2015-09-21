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
             $purchaseOrder = Purchaseorder :: select('poCode','poDate','etaDate','actualDate','poStatus','suppliers.supplierName','suppliers.countryId','discount_1','discount_2','allowance_1','allowance_2','purchaseorders.supplierCode','suppliers.contactPerson_1','currencies.currencyName','poAmount','poReference','poRemark','receiveDate')
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
        
        $poAndItems = Purchaseorder::where('poCode',$poCode)->with(['Poitem'=>function($query){
            $query->with('productDetail');
        }])->with('Supplier','Currency')->first()->toArray();
        
        //$good = array_chuck($poAndItems['Poitem'],1,true);
        
        
        
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        
        $this->generateHeader($pdf);
        
        $this->subHeader($poAndItems,$pdf);
        
        
     //   $numItems = count($good);
        
       /* $i = 0;
        
        foreach($good as $k => $v)
        {
            $i++;
            $pdf->AddPage();

            $pdf->SetFont('chi', 'U', 11);
            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "Purchase Order No.:" . $poAndItems['poCode'], 0, 2, "L");
            $pdf->setXY(10, 58);
            $pdf->Cell(0, 0, "Purchase order date: " . $poAndItems['poDate'], 0, 2, "L");
            $pdf->setXY(10, 66);
            $pdf->Cell(0, 0, "Supplier Confirmation reference No: " . $poAndItems['poReference'], 0, 2, "L");
    
        }*/
        //return Response::json();
         $pdf->Output('','I');
    }
    
    public function generateHeader($pdf)
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
        
        $pdf->SetFont('chi','',9);
        $pdf->setXY(10, 55);
        $pdf->Cell(0, 0,"TEL:24552266    FAX:24552449",0,1,"L");

        $image = public_path('logo.jpg');
        $pdf->Cell( 40, 40, $pdf->Image($image, 10, 7, 25,28), 0, 0, 'L', false );
        
        $pdf->SetFont('chi','',20);
        $pdf->setXY(170, 20);
        $pdf->Cell(0, 0,"採購單",0,1,"L");
        
        
    }
    
    public function subHeader($poAndItems,$pdf)
    {
        $pdf->SetFont('chi','',10);
        $pdf->setXY(150, 40);
        $pdf->Cell(0, 0,"採購單日期:",0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(175, 40);
        $pdf->Cell(0, 0,$poAndItems['poDate'],0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(150, 45);
        $pdf->Cell(0, 0,"採購單編號:",0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(175, 45);
        $pdf->Cell(0, 0,$poAndItems['poCode'],0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(10, 65);
        $pdf->Cell(0, 0,"供應商名稱:",0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(40, 65);
        $pdf->Cell(0, 0,$poAndItems['supplier']['supplierName']."(".$poAndItems['supplierCode'].")",0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(10, 70);
        $pdf->Cell(0, 0,"供應商地址:",0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(40, 70);
        $pdf->Cell(0, 0,$poAndItems['supplier']['address'],0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(40, 76);
        $pdf->Cell(0, 0,$poAndItems['supplier']['address1'],0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(40, 80);
        $pdf->Cell(0, 0,$poAndItems['supplier']['address2'],0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(100, 65);
        $pdf->Cell(0, 0,"送到:",0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(120, 65);
        $pdf->Cell(0, 0," 屯門青楊街6號宏昌工業大廈,",0,1,"L");
        
        $pdf->SetFont('chi','',10);
        $pdf->setXY(120, 70);
        $pdf->Cell(0, 0," 9樓,B室",0,1,"L");
           
    $pdf->SetXY( 4, 90 );

    $this->setTableTitle($pdf);

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
            $pdf->Cell(10,5,$i['productUnitName'],1,0,'L',true);
            
            $pdf->Cell(30,5,round($i['discount_1'], 1).' , '.round($i['discount_2'], 1).' , '.round($i['discount_3'], 1),1,0,'L',true);
            $pdf->Cell(30,5,round($i['allowance_1'], 1).' , '.round($i['allowance_2'], 1).' , '.round($i['allowance_3'], 1),1,0,'L',true);
            
            $pdf->Cell(15,5,$i['unitprice'],1,0,'L',true);
            $pdf->Cell(23,5,$i['unitprice'] * $i['productQty'],1,0,'L',true);
            $total += $i['unitprice'] * $i['productQty'] * (100 - $i['discount_1'])/100 * (100 - $i['discount_2'])/100 * (100 - $i['discount_3'])/100 - $i['allowance_1'] - $i['allowance_2'] - $i['allowance_3'];
            $pdf->Ln();  
            $num++;
     }
     
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
    
    public function setTableTitle($pdf)
    {
        $pdf->SetFillColor(0, 0, 102); //box color 
        $pdf->SetTextColor(255); //Text color
        $pdf->SetDrawColor(92,92,92);
        $pdf->SetLineWidth(.1); //width of line
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
    
   


    

 


}
