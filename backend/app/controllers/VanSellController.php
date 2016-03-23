<?php

class VanSellController extends BaseController
{

    private $_reportId = "";

    private $_reportTitle = "";
    private $_date = "";
    private $_zone = "";
    private $_shift = '';
    private $_version = '';
    private $_invoices = [];
    private $_uniqueid = "";
    private $_data = [];
    private $_output = '';
    private $_pdf = '';
    private $_zonename = '';
    private $kk = '';
    private $audit = '';
    private $shift1 = '';

    public function postVans()
    {

        $productGroup = ['B010', '101', '167', '100', '170', '200', '203', '218', 'O029', 'N002'];

        van::where('deliveryDate', Input::get('deliveryDate'))->where('zoneId', Input::get('zoneId'))->delete();

        foreach ($productGroup as $v) {
            $van_insert = new van();
            $van_insert->deliveryDate = Input::get('deliveryDate');
            $van_insert->zoneId = Input::get('zoneId');
            $van_insert->productId = $v;
            $van_insert->van_qty = Input::get($v);
            $van_insert->productlevel = 'carton';
            $van_insert->pic = Input::get('pic');
            $van_insert->save();
        }

        $vaninvoices = new vanInvoice();
        $vaninvoices->deliveryDate =  Input::get('deliveryDate');
        $vaninvoices->zoneId = Input::get('zoneId');
        $vaninvoices->remark = Input::get('remark');
        $vaninvoices->pic = Input::get('pic');
        $vaninvoices->save();

        echo '提交成功!';

    }

    public function loadvanSellReport()
    {
        $indata = Input::all();
        $indata['reportId'] = 'vanselllist';

        $this->_reportTitle = '預載單';


        $permittedZone = explode(',', Auth::user()->temp_zone);


        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->deliveryDate = $indata['filterData']['deliveryDate'];
        $this->_zone = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['value'] : $permittedZone[0]);
        $this->_zonename = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['label'] : $permittedZone[0]);
        $this->_shift = (isset($indata['filterData']['shift']) ? $indata['filterData']['shift'] : '1');

        /* $lastid = pickingListVersionControl::where('zone', $this->_zone)->where('date', date("Y-m-d", $this->_date))->where('shift', $this->_shift)->first();

         $this->_version = isset($lastid->f1_version) ? $lastid->f1_version : '';

         if ($this->_version)
             $this->_reportTitle = sprintf("%s - v%s", $this->_reportTitle, $this->_version);
         else*/
        $this->_reportTitle = sprintf("%s", $this->_reportTitle);

        // check if user has clearance to view this zone
        if (!in_array($this->_zone, $permittedZone)) {
            App::abort(401, "Unauthorized Zone");
        }
        $this->_uniqueid = microtime(true);

        $this->_output = Input::get('output');

        if ($this->_output == 'setting') {
            $returnInfo = [
                'title' => $this->_reportTitle,
                'filterOptions' => $this->registerFilter(),
                'downloadOptions' => $this->registerDownload(),
                'shift' => $this->Zoneshift()
            ];

            echo json_encode($returnInfo);
            exit;
        }

        if($this->_output =='unlock'){
            $v = vanHeader::where('zoneId', $this->_zone)->where('deliveryDate', $this->deliveryDate)->where('shift', $this->_shift)->first();
            $v->status = '1';
            $v->save();

            $van_exist = vanHeader::where('zoneId', $this->_zone)->where('deliveryDate', $this->deliveryDate)->where('shift', $this->_shift)->lists('status');
            $this->_data['preload_check'] = $van_exist[0];
            return Response::json($this->_data);
        }

        if ($this->_output == 'vanPost') {
            $filterData = Input::get('filterData');
            $selfdefine = Input::get('selfdefine');
            $input = Input::get('data');

            $this->updateSelfDefine();
            $this->updateVanQty();

            van::where('deliveryDate', $filterData['next_working_day'])->where('zoneId', $this->_zone)->delete();

            foreach ($input as $v) {
                if($v['preload'] > 0){
                    $van_insert = new van();
                    $van_insert->deliveryDate = $filterData['next_working_day'];
                    $van_insert->zoneId = $this->_zone;
                    $van_insert->productId = $v['productId'];
                    $van_insert->van_qty = $v['preload'];
                    $van_insert->productlevel = $v['productlevel'];
                    $van_insert->unit = $v['unit'];
                    //$van_insert->pic = Input::get('pic');
                    $van_insert->save();
                }
            }

            foreach ($selfdefine as $v) {
                if($v['deleted'] == '0' and isset($v['preload'])){
                    if($v['preload']>0){
                        $van_insert = new van();
                        $van_insert->deliveryDate = $filterData['next_working_day'];
                        $van_insert->zoneId = $this->_zone;
                        $van_insert->productId = strtoupper($v['productId']);
                        $van_insert->van_qty = $v['preload'];
                        $van_insert->productlevel = $v['unit']['value'];
                        $van_insert->unit = $v['unit']['label'];
                        $van_insert->save();
                    }
                }
            }
            vanHeader::where('zoneId', $this->_zone)->where('deliveryDate', $this->deliveryDate)->where('shift', $this->_shift)->update(['status'=>'30']);
            //$this->compileResults();
            $van_exist = vanHeader::where('zoneId', $this->_zone)->where('deliveryDate', $this->deliveryDate)->where('shift', $this->_shift)->lists('status');
            $this->compileResults();
            $this->_data['preload_check'] = $van_exist[0];
            return Response::json($this->_data);
            //return Response::json($this->_data);
        }

        if ($this->_output == 'preview') {


            if(Input::get('mode')==0){ //preview

                if(vanHeader::where('zoneId', $this->_zone)->where('deliveryDate', $this->deliveryDate)->where('shift', $this->_shift)->count()==0){
                    $vanheader = new vanHeader();
                    $vanheader->zoneId = $this->_zone;
                    $vanheader->deliveryDate = $this->deliveryDate;
                    $vanheader->status = '1';
                    $vanheader->shift = $this->_shift;
                    $vanheader->updated_by = Auth::user()->id;
                    $vanheader->save();
                }


                if($this->_shift == '-1'){

                    if($this->_zone != '11'){
                        $vansales = vansell::where('zoneId',$this->_zone)->where('date',$this->_date)->where('shift','!=','-1')->get();

                        foreach ($vansales as $v){
                            $merge[$v->productId][$v->productlevel] = [
                                'productId' => $v->productId,
                                'productlevel' => $v->productlevel,
                                'unit'=>$v->unit,
                                'name'=>$v->name,
                                'qty' => (isset($merge[$v->productId][$v->productlevel]) ? $merge[$v->productId][$v->productlevel]['qty'] : 0) + $v->qty,
                            ];
                        }

                        $this->compileResults();

                       // pd(vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->get());

                        foreach ($merge as $v) {
                            foreach($v as $v1){
                                $savevansell = vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->where('productId',$v1['productId'])->first();
                                if(count($savevansell)==0){
                                    $savevansell = new vansell();
                                    $savevansell->zoneId=$this->_zone;
                                    $savevansell->date=$this->_date;
                                    $savevansell->shift=$this->_shift;
                                    $savevansell->productId=$v1['productId'];
                                    $savevansell->productlevel=$v1['productlevel'];
                                    $savevansell->unit=$v1['unit'];
                                    $savevansell->name=$v1['name'];
                                }
                                $savevansell->qty = $v1['qty'];
                                $savevansell->save();
                            }
                        }
                    }
                }

               // pd($merge);

            }else if(Input::get('mode')=='1'){ //double confirm
                $v = vanHeader::where('zoneId', $this->_zone)->where('deliveryDate', $this->deliveryDate)->where('shift', $this->_shift)->first();
                $v->updated_by = Auth::user()->id;
                $v->status = '11';
                $v->save();

                $this->updateSelfDefine();

                $this->updateVanQty();
            }

            $van_exist = vanHeader::where('zoneId', $this->_zone)->where('deliveryDate', $this->deliveryDate)->where('shift', $this->_shift)->lists('status');



            $this->compileResults();
            $this->_data['preload_check'] = $van_exist[0];
            return Response::json($this->_data);
        }

        if ($this->_output == 'create') {

            $this->updateSelfDefine();


            //  $vansells = vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->orderBy('productId', 'asc')->where('self_define',false)->get()->toArray();
            //  $inv = [];


            // $debug = new debug();
            // $debug->content = 'zoneId:'.$this->_zone."shift:".$this->_shift;
            // $debug->content .= json_encode(Input::get('data'));
            //  $debug->save();

$this->updateVanQty();
            /*   pd($vansells);

               foreach ($vansells as $v) {
                   $store = $v->productId.$v->productlevel;
                   $v->qty = $inv[$store];
                   $v->save();
               }*/

        }

        if ($this->_output == 'pdf') {


            $this->compileResults();

            SystemController::reportRecord($this->outputPDF());


            exit;
        }

        if($this->_output == 'discrepancyPDF' || $this->_output == 'auditPdf') {

            if ($this->_shift == '-1')
                $vansales = vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->with('products')->orderby('productId', 'asc')->get();
            else
                $vansales = vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->whereIn('shift', [1, 2])->with('products')->orderby('productId', 'asc')->get();

            //pd($vansales);


            foreach ($vansales as $v) {
                $this->audit[$v['productId']][$v['productlevel']] = [
                    'productId' => $v->productId,
                    'name' => $v->products->productName_chi,
                    'unit' => $v['productlevel'],
                    'unit_txt' => $v['unit'],
                    'van_qty' => (isset($this->audit[$v['productId']][$v['productlevel']]['van_qty']) ? $this->audit[$v['productId']][$v['productlevel']]['van_qty'] : 0) + $v->van_qty,
                    'qty' => (isset($this->audit[$v['productId']][$v['productlevel']]['qty']) ? $this->audit[$v['productId']][$v['productlevel']]['qty'] : 0) + $v->qty,
                    'org_qty' => (isset($this->audit[$v['productId']][$v['productlevel']]['org_qty']) ? $this->audit[$v['productId']][$v['productlevel']]['org_qty'] : 0) + $v->org_qty,
                    'return_qty' => (isset($this->audit[$v['productId']][$v['productlevel']]['return_qty']) ? $this->audit[$v['productId']][$v['productlevel']]['return_qty'] : 0) + $v->return_qty,
                    'neg_qty' => (isset( $this->audit[$v['productId']][$v['productlevel']]['neg_qty']) ?  $this->audit[$v['productId']][$v['productlevel']]['neg_qty'] : 0) + $v->neg_qty,
                ];
            }
            if($this->_output == 'discrepancyPDF'){
                $this->_reportTitle = '總匯對算表';
                SystemController::reportRecord($this->outputPDFAdiscrepancy());
            }else if ($this->_output == 'auditPdf'){
                $this->_reportTitle = '回貨對算表';
                $this->next_working_day = date('d/m',strtotime(Input::get('filterData.next_working_day')));
                SystemController::reportRecord($this->outputPDFAudit());
            }

        }



    }


    public function compileResults()
    {
        $date = $this->_date;
        $zone = $this->_zone;


        // get invoice from that date and that zone
        $this->goods = ['1F' => [], '9F' => []];
        $invoicesQuery = Invoice::select('invoiceId')->wherein('invoiceStatus', ['2', '1','20','30', '96', '97'])->where('zoneId', $zone)->where('deliveryDate', $date);

        if ($this->_shift != '-1')
            $invoicesQuery->where('shift', $this->_shift);
        $invoicesQuery = $invoicesQuery->with(['invoiceItem' => function ($q) {
            $q->with('productDetail');
        }])->get();


        // first of all process all products
        /*  $productsQuery = array_pluck($invoicesQuery, 'products');

          foreach ($productsQuery as $productQuery) {
              $productQuery = head($productQuery);
              //pd($productQuery);
              foreach ($productQuery as $pQ) {
                  $products[$pQ->productId] = $pQ;
              }
          }*/

        // second process invoices
        foreach ($invoicesQuery as $invoiceQ) {
            $this->_invoices[] = $invoiceQ->invoiceId;

            // first, store all invoices
            $invoiceId = $invoiceQ->invoiceId;
            $invoices[$invoiceId] = $invoiceQ;

            // second, separate 1F goods and 9F goods
            foreach ($invoiceQ['invoiceItem'] as $item) {
                // determin its product location


                if($item->productQty > 0){
                    $pqty = $item->productQty;
                    $nqty = 0;
                }else if($item->productQty < 0){
                    $nqty = $item->productQty*-1;
                    $pqty = 0;
                }
                    $productId = $item->productId;

                    //  $productDetail = $products[$productId];
                    $unit = $item->productQtyUnit;

                    if ($item->productDetail->productLocation == '1') {
                        $this->goods['1F'][$productId][$unit] = [
                            'productId' => $productId,
                            'name' => $item->productDetail->productName_chi,
                            'unit' => $unit,
                            'unit_txt' => $item->productUnitName,
                            'counts' => (isset($this->goods['1F'][$productId][$unit]['counts']) ? $this->goods['1F'][$productId][$unit]['counts'] : 0) + $pqty,
                            'neg_qty' => (isset($this->goods['1F'][$productId][$unit]['neg_qty']) ? $this->goods['1F'][$productId][$unit]['neg_qty'] : 0) + $nqty,
                            'van_qty' => 0,
                        ];
                    }

            }
        }



        if($this->_shift != '2'){ // if is not shift 2 , don't need copy preload qty to vansale list

            $van_query = van::where('zoneId', $this->_zone)->where('deliveryDate', date('Y-m-d', $this->_date))->with('products')->get();

            foreach($van_query as $v){
                $this->goods['1F'][$v['productId']][$v['productlevel']] = [
                    'productId' => $v->productId,
                    'name' => $v->products->productName_chi,
                    'unit' => $v['productlevel'],
                    'unit_txt' => $v['unit'],
                    'van_qty' => (isset($this->goods['1F'][$v['productId']][$v['productlevel']]) ? $this->goods['1F'][$v['productId']][$v['productlevel']]['van_qty'] : 0) + $v->van_qty,
                    'counts' => (isset($this->goods['1F'][$v['productId']][$v['productlevel']]) ? $this->goods['1F'][$v['productId']][$v['productlevel']]['counts'] : 0),
                    'neg_qty' => (isset($this->goods['1F'][$v['productId']][$v['productlevel']]) ? $this->goods['1F'][$v['productId']][$v['productlevel']]['neg_qty'] : 0)
                ];
            }
        }else{
            $vansell_query = vansell::where('date', $this->_date)->where('shift', '1')->where('zoneId', $zone)->where('self_define', false)->with('products')->get();
            foreach($vansell_query as $v){
                $this->shift1[$v['productId']][$v['productlevel']] = [
                    'productId' => $v->productId,
                    'name' => $v->products->productName_chi,
                    'unit' => $v['productlevel'],
                    'unit_txt' => $v['unit'],
                    'shift1' => (isset($this->shift1[$v['productId']][$v['productlevel']]['shift1']) ? $this->shift1[$v['productId']][$v['productlevel']]['shift1'] : 0) + $v->org_qty,
                    'shift1_preload' => (isset($this->shift1[$v['productId']][$v['productlevel']]['shift1_preload']) ? $this->shift1[$v['productId']][$v['productlevel']]['shift1_preload'] : 0) + $v->qty+$v->van_qty,
                ];
            }
        }

       //  pd($this->shift1);
        //  pd(DB::getQueryLog());

        $this->_data = $this->goods['1F'];

        $vansell_query = vansell::select('productId', 'productlevel','org_qty','van_qty','neg_qty')->where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('self_define', false)->get()->toArray();
        //$van_query = van::select('productId', 'productlevel','van_qty')->where('zoneId', $this->_zone)->where('deliveryDate', date('Y-m-d', $this->_date))->get()->toArray();

//pd($this->_data);

        $allIds = [];
        $create = [];
        $index = 0;
        foreach ($this->_data as $g) { // invoice and preload products
            foreach ($g as $k => $v) {

                $skip = false;

               /* $van_qty = 0;
                foreach($van_query as $k2 => $v2){
                    if ($v2['productId'] == $v['productId'] && $v2['productlevel'] == $v['unit']){
                        $van_qty = $v2['van_qty'];
                        break;
                    }
                }*/

                foreach ($vansell_query as $k1 => $v1){ //vansale table data
                    if ($v1['productId'] == $v['productId'] && $v1['productlevel'] == $v['unit']) {
                        if($v1['van_qty'] != $v['van_qty'] || $v1['org_qty']!=$v['counts'] || $v1['neg_qty']!=$v['neg_qty'] || isset($this->shift1[$v['productId']][$v['unit']]['shift1'])){
                            $vansell = vansell::where('productId', $v['productId'])->where('productlevel', $v['unit'])->where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('self_define', false)->first();

                            // if there is not self enter, qty will be updated auto from invoice qty
                           // if ($vansell->qty == $vansell->org_qty && $vansell->self_enter == 0)
                           //    $vansell->qty = $v['counts'];
                            $vansell->org_qty = $v['counts'];
                            $vansell->van_qty = $v['van_qty'];
                            $vansell->neg_qty = $v['neg_qty'];
                            if($this->_shift == '2'){
                                $vansell->shift1_preload = isset($this->shift1[$v['productId']][$v['unit']]['shift1_preload'])?$this->shift1[$v['productId']][$v['unit']]['shift1_preload']:0;
                                $vansell->shift1 = isset($this->shift1[$v['productId']][$v['unit']]['shift1'])?$this->shift1[$v['productId']][$v['unit']]['shift1']:0;
                            }
                            $vansell->save();
                        }
                        $skip = true;
                        break;
                    }
                }

                if (!$skip) {
                    $create[$index]['productId'] = $v['productId'];
                    $create[$index]['name'] = $v['name'];
                    $create[$index]['unit'] = $v['unit_txt'];
                    $create[$index]['org_qty'] = $v['counts'];
                    $create[$index]['neg_qty'] = $v['neg_qty'];
                    $create[$index]['productlevel'] = $v['unit'];
                    $create[$index]['van_qty'] = $v['van_qty'];
                    $create[$index]['date'] = $this->_date;
                    $create[$index]['zoneId'] = $this->_zone;
                    $create[$index]['shift'] = $this->_shift;
                    $create[$index]['shift1'] =  isset($this->shift1[$v['productId']][$v['unit']]['shift1'])?$this->shift1[$v['productId']][$v['unit']]['shift1']:0;
                    $create[$index]['created_at'] = date('Y-m-d H:i:s');
                    $create[$index]['updated_at'] = date('Y-m-d H:i:s');

                }


                //$vansell = vansell::where('productId', $v['productId'])->where('productlevel', $v['unit'])->where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('self_define',false)->first();
                /*   if (count($vansell) == 0) {
                       $create = new vansell();
                       $create->productId = $v['productId'];
                       $create->name = $v['name'];
                       $create->unit = $v['unit_txt'];
                       $create->org_qty = $v['counts'];
                       $create->productlevel = $v['unit'];
                       $create->van_qty = $van_qty;
                       $create->date = $this->_date;
                       $create->zoneId = $this->_zone;
                       $create->shift = $this->_shift;
                       $create->save();
                   } else {
                       if($vansell->qty==$vansell->org_qty && $vansell->self_enter == 0)
                           $vansell->qty = $v['counts'];
                       $vansell->org_qty = $v['counts'];
                       $vansell->van_qty = $van_qty;
                       $vansell->save();
                   }*/

                $allIds[] = $v['productId'];
                $index++;
            }
        }
        if(count($create)>0)
            vansell::insert($create);


        $dbIds = vansell::where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('self_define', false)->lists('productId');

        $result = array_diff($dbIds, $allIds);

        if (count($result) > 0)
            foreach ($result as $vv)
            {
                $del = vansell::where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('productId', $vv)->where('self_define', false)->first();


               // if ($del->self_enter == false)
               //     $del->delete();
               // else
               // {
                    $del->org_qty = 0;
                    $del->save();
                //}

            }

        $vansells = vansell::where('zoneId', $zone)->where('date', $date)->where('shift', $this->_shift)->where('self_define', false)->orderBy('productId', 'asc')->get();
        $this->_data['normal'] = $vansells;


        $vansell_selfdefine = vansell::where('zoneId', $zone)->where('date', $date)->where('shift', $this->_shift)->where('self_define', true)->orderBy('productId', 'asc')->with('products')->get();
        $this->_data['selfdefine'] = $vansell_selfdefine;

        $vansells_pdf = vansell::where('zoneId', $zone)->where('date', $date)->where('shift', $this->_shift)->orderBy('productId', 'asc')->get()->toArray();

        $this->_pdf = $vansells_pdf;


    }

    public function registerFilter()
    {
        /*
         * Type:
         * single-dropdown
         * date-picker
         * date-range
         * single-selection
         * multiple-selection
         * text
         */
        $zones = Zone::wherein('zoneId', explode(',', Auth::user()->temp_zone))->get();
        foreach ($zones as $zone) {
            $availablezone[] = [
                'value' => $zone->zoneId,
                'label' => $zone->zoneName,
            ];
        }
        $filterSetting = [
            [
                'id' => 'zoneId',
                'type' => 'single-dropdown',
                'label' => '車號',
                'model' => 'zone',
                'optionList' => $availablezone,
                'defaultValue' => $this->_zone,
            ],
            [
                'id' => 'deliveryDate',
                'type' => 'date-picker',
                'label' => '送貨日期',
                'model' => 'deliveryDate',
                'defaultValue' => date("Y-m-d", $this->_date),
            ],
            [
                'id' => 'submit',
                'type' => 'submit',
                'label' => '提交',
            ],
        ];

        return $filterSetting;
    }

    public function ZoneShift(){
        return Zone::wherein('zoneId', explode(',', Auth::user()->temp_zone))->lists('batch','zoneId');
    }

    public function registerDownload()
    {
        $downloadSetting = [
            [
                'type' => 'pdf',
                'name' => '列印 PDF 版本',
                'warning' => false
            ],
            [
                'type' => 'audit',
                'name' => '回貨對算表',
                'warning' => false
            ],
            [
                'type' => 'discrepancy',
                'name' => '總匯對算表',
                'warning' => false
            ],
        ];

        return $downloadSetting;
    }


# PDF Section
    public function generateHeader($pdf)
    {
        if ($this->_shift == 1)
            $shift = '早班';
        elseif ($this->_shift == 2)
            $shift = '晚班';
        else
            $shift = '全部';

        $pdf->SetFont('chi', '', 18);
        $pdf->Cell(0, 10, "炳記行貿易有限公司", 0, 1, "C");
        $pdf->SetFont('chi', 'U', 16);
        $pdf->Cell(0, 10, $this->_reportTitle, 0, 1, "C");
        $pdf->SetFont('chi', 'U', 13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->_zone, 2, '0', STR_PAD_LEFT) . "(" . $this->_zonename . ")", 0, 2, "L");
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->_date) . "(" . $shift . ")", 0, 2, "L");
        $pdf->setXY(0, 0);
        $pdf->SetFont('chi', '', 9);
        $pdf->Code128(10, $pdf->h - 15, $this->_uniqueid, 50, 10);
        $pdf->Cell(0, 10, sprintf("報告編號: %s", $this->_uniqueid), 0, 2, "R");

    }


    public function generateHeaderAudit($pdf)
    {
        $pdf->SetFont('chi', '', 18);
        $pdf->Cell(0, 10, "炳記行貿易有限公司", 0, 1, "C");
        $pdf->SetFont('chi', 'U', 16);
        $pdf->Cell(0, 10, $this->_reportTitle, 0, 1, "C");
        $pdf->SetFont('chi', 'U', 13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->_zone, 2, '0', STR_PAD_LEFT) . "(" . $this->_zonename . ")", 0, 2, "L");
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->_date), 0, 2, "L");
        $pdf->setXY(0, 0);
        $pdf->SetFont('chi', '', 9);
        $pdf->Code128(10, $pdf->h - 15, $this->_uniqueid, 50, 10);
        $pdf->Cell(0, 10, sprintf("報告編號: %s", $this->_uniqueid), 0, 2, "R");

    }



    public function outputPDFAdiscrepancy()
    {

        $pdf = new PDF();

        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        // handle 1F goods

        foreach($this->audit as $k=> &$v){
            foreach($v as $k1=> &$u) {
                if (($u['van_qty']+$u['qty']+$u['neg_qty']) - $u['org_qty'] - $u['return_qty'] == 0) {
                    unset($this->audit[$k][$k1]);
                }
            }
        }

        $this->audit= array_filter($this->audit);


        //pd($this->audit);

        $firstF = array_chunk($this->audit, 20, true);



        foreach ($firstF as $i => $f) {
            // for first Floor
            $pdf->AddPage();


            $this->generateHeaderAudit($pdf);

            $pdf->SetFont('chi', '', 12);


            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "產品編號", 0, 0, "L");

            $pdf->setXY(30, 50);
            $pdf->Cell(0, 0, "產品名稱", 0, 0, "L");

            $pdf->setXY(90, 50);
            $pdf->Cell(0, 0, "上貨總數", 0, 0, "L");

            $pdf->setXY(120, 50);
            $pdf->Cell(0, 0, "訂單總數", 0, 0, "L");

            $pdf->setXY(150, 50);
            $pdf->Cell(0, 0, "還貨總數", 0, 0, "L");

            $pdf->setXY(180, 50);
            $pdf->Cell(0, 0, "差異", 0, 0, "L");

            $pdf->Line(10, 53, 190, 53);

            $y = 60;

            $pdf->setXY(10, $pdf->h - 30);
            $pdf->Cell(0, 0, "經手人", 0, 0, "L");

            $pdf->setXY(60, $pdf->h - 30);
            $pdf->Cell(0, 0, "核數人", 0, 0, "L");

            $pdf->Line(10, $pdf->h - 35, 50, $pdf->h - 35);
            $pdf->Line(60, $pdf->h - 35, 100, $pdf->h - 35);

            $pdf->setXY(500, $pdf->h - 30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i + 1, count($firstF)), 0, 0, "R");



            /* foreach ($f as $ga) {
                 foreach ($ga as $u) {
                     p($u);
                 }
             }*/



            foreach ($f as $ga) {

                foreach ($ga as $v1) {


                    $pdf->setXY(10, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, $v1['productId'], 0, 0, "L");

                    $pdf->setXY(30, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, sprintf('%s',$v1['name']), 0, 0, "L");

                    $pdf->setXY(90, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, sprintf('%s',$v1['qty']+$v1['van_qty']), 0, 0, "L");

                    $pdf->setXY(120, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, sprintf('%s',$v1['org_qty']-$v1['neg_qty']), 0, 0, "L");

                    $pdf->setXY(150, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, sprintf('%s',$v1['return_qty']), 0, 0, "L");

                    $pdf->setXY(180, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, sprintf('%s%s', ($v1['van_qty'] + $v1['qty'] + $v1['neg_qty']) - $v1['org_qty']-$v1['return_qty'],$v1['unit_txt']), 0, 0, "L");



                    $y += 8;


                    /*  $d = substr(current($f)['productId'], 0, 1);
                      $nd = substr(next($f)['productId'], 0, 1);
                      if ($nd != '')
                          if ($nd != $d) {
                              $pdf->Line(10, $y, 190, $y);
                              $y += 7;
                          }*/
                }
            }
        }

        // handle 9F goods


        //end of handel nine floor

        // output


        return [
            'pdf' => $pdf,
            'remark' => sprintf("Vansale discrepancy report DeliveryDate = %s", date("Y-m-d", $this->_date)),
            'zoneId' => $this->_zone,
            'uniqueId' => $this->_uniqueid,
            'shift' => $this->_shift,
            'deliveryDate' => date("Y-m-d", $this->_date),
            'reportId' => 'vansaleDiscrepancy',
        ];
    }


    public function outputPDFAudit()
    {

        $pdf = new PDF();

        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        // handle 1F goods

        foreach($this->audit as $k=> &$v){
            foreach($v as $k1=> &$u) {
                if ($u['van_qty'] + $u['qty'] + $u['neg_qty'] - $u['org_qty'] == 0) {
                    unset($this->audit[$k][$k1]);
                }
            }
        }

        $this->audit= array_filter($this->audit);


        //pd($this->audit);

        $firstF = array_chunk($this->audit, 20, true);



        foreach ($firstF as $i => $f) {
            // for first Floor
            $pdf->AddPage();


            $this->generateHeaderAudit($pdf);

            $pdf->SetFont('chi', '', 12);


            $pdf->setXY(137, 40);
            $pdf->Cell(0, 0, "回車時間:______________", 0, 0, "L");

            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "產品編號", 0, 0, "L");

            $pdf->setXY(30, 50);
            $pdf->Cell(0, 0, "產品名稱", 0, 0, "L");

            $pdf->setXY(95, 50);
            $pdf->Cell(0, 0, "借貨數量", 0, 0, "L");

            $pdf->setXY(137, 50);
            $pdf->Cell(0, 0, "回貨數量", 0, 0, "L");


            $pdf->Line(10, 53, 190, 53);

            $y = 60;

            $pdf->setXY(10, $pdf->h - 25);
            $pdf->Cell(0, 0, "經手人", 0, 0, "L");

            $pdf->setXY(60, $pdf->h - 25);
            $pdf->Cell(0, 0, "核數人", 0, 0, "L");

            $pdf->Line(10, $pdf->h - 30, 50, $pdf->h - 30);
            $pdf->Line(60, $pdf->h - 30, 100, $pdf->h - 30);

            $pdf->setXY(500, $pdf->h - 25);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i + 1, count($firstF)), 0, 0, "R");



           /* foreach ($f as $ga) {
                foreach ($ga as $u) {
                    p($u);
                }
            }*/



            foreach ($f as $ga) {

                foreach ($ga as $v1) {


                        $pdf->setXY(10, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, $v1['productId'], 0, 0, "L");

                        $pdf->setXY(30, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, sprintf('%s',$v1['name']), 0, 0, "L");

                        $pdf->setXY(100, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(10, 0, sprintf('%s%s', ($v1['van_qty'] + $v1['qty'] + $v1['neg_qty']) - $v1['org_qty'],$v1['unit_txt']), 0, 0, "R");

                        $pdf->setXY(130, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, "________________", 0, 0, "L");

                        $y += 8;


                    /*  $d = substr(current($f)['productId'], 0, 1);
                      $nd = substr(next($f)['productId'], 0, 1);
                      if ($nd != '')
                          if ($nd != $d) {
                              $pdf->Line(10, $y, 190, $y);
                              $y += 7;
                          }*/
                 }
            }


            $y += 8;
            // Notes part
            if ($i == 0) {
                for ($note = 0; $note <= 1; $note++) {
                    $pdf->Line(10, $y, 80, $y);
                    $pdf->Line(90, $y, 120, $y);
                    $pdf->Line(130, $y, 170, $y);
                   // $pdf->Line(160, $y, 190, $y);


                    $y += 8;
                }
            }


            $pdf->setXY(10, $y);
            $pdf->SetFont('chi', '', 15);
            $pdf->Cell(0, 0, "預載數量(".$this->next_working_day.")", 0, 0, "L");

            $y+=8;

            $pdf->SetFont('chi', '', 12);

            $pdf->setXY(10, $y);
            $pdf->Cell(0, 0, "產品編號", 0, 0, "L");

            $pdf->setXY(30, $y);
            $pdf->Cell(0, 0, "產品名稱", 0, 0, "L");

            $pdf->setXY(90, $y);
            $pdf->Cell(0, 0, "預載數量(".$this->next_working_day.")", 0, 0, "L");

            $pdf->Line(10, $y+3, 190, $y+3);

            $y +=10;

            $products = product::where('vansale',1)->orderBy('productId')->get();

            foreach($products as $v){
                $pdf->setXY(10, $y);
                $pdf->SetFont('chi', '', 13);
                $pdf->Cell(0, 0, $v['productId'], 0, 0, "L");

                $pdf->setXY(30, $y);
                $pdf->SetFont('chi', '', 13);
                $pdf->Cell(0, 0, sprintf('%s',$v['productName_chi']), 0, 0, "L");


                $pdf->setXY(90, $y);
                $pdf->SetFont('chi', '', 13);
                $pdf->Cell(0, 0, "____________", 0, 0, "L");

                $y += 8;
            }


        }

        // handle 9F goods


        //end of handel nine floor

        // output


        return [
            'pdf' => $pdf,
            'remark' => sprintf("Vansale Audit List DeliveryDate = %s", date("Y-m-d", $this->_date)),
            'zoneId' => $this->_zone,
            'uniqueId' => $this->_uniqueid,
            'shift' => $this->_shift,
            'reportId' => 'vansaleAudit',
            'deliveryDate' => date("Y-m-d", $this->_date),
            //'associates' => json_encode($this->_invoices),
        ];
    }

    public function outputPDF()
    {

        // Update it as generated into picking list

        /*
        if($this->_output != 'van_sell_pdf')
        if(count($this->_invoices) > 0)
        {
            Invoice::wherein('invoiceId', $this->_invoices)->update(['invoiceStatus'=>'4']);
        }*/


        $pdf = new PDF();

        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        // handle 1F goods


        //
        $k = [];
        $new_array = $this->_pdf;
        foreach ($this->_pdf as $i => $f) {
            $d = substr(current($this->_pdf)['productId'], 0, 1);
            $nd = substr(next($this->_pdf)['productId'], 0, 1);
            if ($nd != '')
                if ($nd != $d) {
                    $k[] = $i;
                }
        }
// array_splice($new_array, $i+1, 0, ['qty'=>999] );
        if (count($k) > 0) {
            $q = 1;
            foreach ($k as $z) {
                array_splice($new_array, $z + $q, 0, [['qty' => '-100']]);
                $q++;
            }
        }
        // pd($new_array);

        foreach ($new_array as $k1 => $v1) {
            if (isset($v1['productId']))
                if (strpos($v1['productId'], 'D') !== false) {
                    $this->kk = $k1;
                    break;
                }
        }

        if ($this->kk > 26)
            foreach ($new_array as $k1 => $v1) {
                if (isset($v1['productId']))
                    if (strpos($v1['productId'], 'B') !== false) {
                        $this->kk = $k1;
                        break;
                    }
            }


        //array_splice($new_array, 24, 0, [['qty'=>'-1']] );
        if ($this->kk != '')
            for ($i = $this->kk; $i < 26; $i++) {
                array_splice($new_array, $i, 0, [['qty' => '-1']]);
            }
        //  pd($new_array);

        $firstF = array_chunk($new_array, 26, true);

        $firstI = 0;
        foreach ($firstF as $i => $f) {
            // for first Floor
            $pdf->AddPage();


            $this->generateHeader($pdf);

            $pdf->SetFont('chi', '', 12);
            if ($firstI == 0) {
                // first
                $pdf->setXY(155, 16);
                $pdf->Cell(0, 0, "車牌 : ___________", 0, 0, "L");
                $pdf->setXY(155, 24);
                $pdf->Cell(0, 0, "司機 : ___________", 0, 0, "L");
                $pdf->setXY(155, 32);
                $pdf->Cell(0, 0, "跟車 : ___________", 0, 0, "L");
                $pdf->setXY(147, 40);
                $pdf->Cell(0, 0, "出車時間 : ___________", 0, 0, "L");
            }
            $firstI++;

            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "產品編號", 0, 0, "L");

            $pdf->setXY(30, 50);
            $pdf->Cell(0, 0, "產品名稱", 0, 0, "L");

            $pdf->setXY(100, 50);
            $pdf->Cell(0, 0, "預載數量", 0, 0, "L");

            $pdf->setXY(127, 50);
            $pdf->Cell(0, 0, "上貨數量", 0, 0, "L");

            $pdf->setXY(155, 50);
            $pdf->Cell(0, 0, "添加數量", 0, 0, "L");

            $pdf->setXY(180, 50);
            $pdf->Cell(0, 0, "覆核數量", 0, 0, "L");

            $pdf->Line(10, 53, 200, 53);

            $y = 60;

            $pdf->setXY(10, $pdf->h - 30);
            $pdf->Cell(0, 0, "備貨人", 0, 0, "L");

            $pdf->setXY(60, $pdf->h - 30);
            $pdf->Cell(0, 0, "核數人", 0, 0, "L");

            $pdf->Line(10, $pdf->h - 35, 50, $pdf->h - 35);
            $pdf->Line(60, $pdf->h - 35, 100, $pdf->h - 35);

            $pdf->setXY(500, $pdf->h - 30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i + 1, count($firstF)), 0, 0, "R");


            $first = true;
            // pd($f);
            foreach ($f as $id => $u) {


                if ($first) {
                    // do something
                    $first = false;

                    if ( ($u['qty'] != 0 || $u['van_qty']!=0) && $u['qty'] != -100) {
                        $pdf->setXY(10, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, $u['productId'], 0, 0, "L");

                        $pdf->setXY(30, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, $u['name'], 0, 0, "L");

                        $pdf->setXY(100, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, sprintf('%s',$u['van_qty']), 0, 0, "L");

                        $pdf->setXY(111, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, str_replace(' ', '', $u['unit']), 0, 0, "L");

                        $pdf->setXY(127, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, sprintf('%s',$u['qty']), 0, 0, "L");


                        $pdf->setXY(138, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, str_replace(' ', '', $u['unit']), 0, 0, "L");

                        $pdf->setXY(155, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, "________", 0, 0, "L");

                        $pdf->setXY(180, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, "________", 0, 0, "L");

                        $y += 7;
                    }

                } else {
                    if ( ($u['qty'] != 0 || $u['van_qty']!=0)&& $u['qty'] != -100 && $u['qty'] != -1) {
                        $pdf->setXY(10, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, $u['productId'], 0, 0, "L");

                        $pdf->setXY(30, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, $u['name'], 0, 0, "L");

                        $pdf->setXY(100, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, sprintf('%s',$u['van_qty']), 0, 0, "L");

                        $pdf->setXY(111, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, str_replace(' ', '', $u['unit']), 0, 0, "L");

                        $pdf->setXY(127, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, sprintf('%s',$u['qty']), 0, 0, "L");


                        $pdf->setXY(138, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, str_replace(' ', '', $u['unit']), 0, 0, "L");

                        $pdf->setXY(155, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, "________", 0, 0, "L");

                        $pdf->setXY(180, $y);
                        $pdf->SetFont('chi', '', 13);
                        $pdf->Cell(0, 0, "________", 0, 0, "L");

                        $y += 7;
                    }

                    if ($u['qty'] == '-100') {
                        $pdf->Line(10, $y, 200, $y);
                        $y += 7;
                    }

                    if ($u['qty'] == '-1') {
                        // $pdf->Line(10, $y, 190, $y);
                        $y += 7;
                    }

                }


                /*  $d = substr(current($f)['productId'], 0, 1);
                  $nd = substr(next($f)['productId'], 0, 1);
                  if ($nd != '')
                      if ($nd != $d) {
                          $pdf->Line(10, $y, 190, $y);
                          $y += 7;
                      }*/

            }

            /*   $y += 10;
               // Notes part
               if ($i == 0) {
                   for ($note = 0; $note <= 2; $note++) {
                       $pdf->Line(10, $y, 27, $y);
                       $pdf->Line(40, $y, 100, $y);
                       $pdf->Line(120, $y, 135, $y);
                       $pdf->Line(146, $y, 160, $y);
                       $pdf->Line(171, $y, 185, $y);


                       $y += 8;
                   }
               }*/

        }

        // handle 9F goods


        //end of handel nine floor

        // output


        return [
            'pdf' => $pdf,
            'remark' => sprintf("Van Sell List DeliveryDate = %s", date("Y-m-d", $this->_date)),
            'zoneId' => $this->_zone,
            'uniqueId' => $this->_uniqueid,
            'shift' => $this->_shift,
            'reportId' => 'vanselllist',
            'deliveryDate' => date("Y-m-d", $this->_date),
            'associates' => json_encode($this->_invoices),
        ];
    }

# PDF Section


    public
    function loadAvailableReports()
    {


        if (Auth::user()->role[0]->id == 4) {
            $filter = ['productReport', 'customerReport'];
            $reports = Report::select('*')->orderBy('id', 'asc')->whereNotIn('id', $filter)->get();
        } else {
            $reports = Report::select('*')->orderBy('id', 'asc')->get();
        }


        foreach ($reports as $report) {
            $reportCustom[$report->group]['reports'][] = $report;
            $reportCustom[$report->group]['groupName'] = $report->group;
        }
        return Response::json($reportCustom);
    }

    public function viewArchivedReport()
    {
        $rid = Input::get('rid');


        $report = ReportArchive::where('id', $rid)->first();

        $content = file_get_contents($report->file);

        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($content));
        header('Content-disposition: inline; filename="' . $report->file . '"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

        echo $content;

    }

    public function updateSelfDefine(){
        $selfdefine = Input::get('selfdefine');

        vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->orderBy('productId', 'asc')->where('self_define', true)->delete();

        foreach ($selfdefine as $d) {
            if ($d['deleted'] == 0 and isset($d['success']) and strlen($d['productId'])>2) {
                $i = new vansell;
                $i->productId = strtoupper($d['productId']);
                $i->name = $d['productName'];
                $i->unit = $d['unit']['label'];
                $i->productlevel = $d['unit']['value'];
                $i->qty = $d['qty'];
                $i->zoneId = $this->_zone;
                $i->date = $this->_date;
                $i->shift = $this->_shift;
                $i->return_qty = isset($d['return_qty'])?$d['return_qty']:0;
                $i->preload = isset($d['preload'])?$d['preload']:0;
                $i->self_define = 1;
                $i->save();
            }
        }

    }

    public function updateVanQty(){
        foreach (Input::get('data') as $v) {
            //  $inv[$v['productId'].$v['productlevel']] = $v['value'];
            $savevansell = vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->where('self_define', false)->where('id', $v['id'])->first();

            //if user don't enter any qty , qty will be equal to invoice qty, otherwrise will be updated from user define
            if ($v['qty'] === '' || is_null($v['qty'])) {
                $savevansell->qty = $v['org_qty'];

                // $savevansell->self_enter = 0;
            } else {
                $savevansell->qty = $v['qty'];
                // $savevansell->self_enter = 1;
            }
            $savevansell->return_qty = isset($v['return_qty'])?$v['return_qty']:0;
            $savevansell->preload = isset($v['preload'])?$v['preload']:0;
            $savevansell->save();
        }
    }


}