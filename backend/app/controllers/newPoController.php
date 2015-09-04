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

 


}
