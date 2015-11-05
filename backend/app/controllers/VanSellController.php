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

        echo '提交成功!';

    }

    public function loadvanSellReport()
    {
        $indata = Input::all();
        $indata['reportId'] = 'vanselllist';

        $this->_reportTitle = '預載單';


        $permittedZone = explode(',', Auth::user()->temp_zone);


        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
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
            ];

            echo json_encode($returnInfo);
            exit;
        }

        if ($this->_output == 'preview') {
            $this->compileResults();
            return Response::json($this->_data);
        }

        if ($this->_output == 'create') {
            $selfdefine = Input::get('selfdefine');

            // $debug = new debug();
            //  $debug->content = 'SelfDefine - zoneId:'.$this->_zone."shift:".$this->_shift;
            //  $debug->content .= json_encode($selfdefine);
            //  $debug->save();

            vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->orderBy('productId', 'asc')->where('self_define', true)->delete();

            foreach ($selfdefine as $d) {
                if (trim($d['productName']) != '' && trim($d['qty']) != '' && trim($d['unit']) != '' && trim($d['productId'])) {
                    $i = new vansell;
                    $i->productId = $d['productId'];
                    $i->name = $d['productName'];
                    $i->unit = $d['unit'];
                    $i->qty = $d['qty'];
                    $i->zoneId = $this->_zone;
                    $i->date = $this->_date;
                    $i->shift = $this->_shift;
                    $i->self_define = 1;
                    $i->save();
                }
            }


            //  $vansells = vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->orderBy('productId', 'asc')->where('self_define',false)->get()->toArray();
            //  $inv = [];


            // $debug = new debug();
            // $debug->content = 'zoneId:'.$this->_zone."shift:".$this->_shift;
            // $debug->content .= json_encode(Input::get('data'));
            //  $debug->save();

            foreach (Input::get('data') as $v) {
                //  $inv[$v['productId'].$v['productlevel']] = $v['value'];
                $savevansell = vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift', $this->_shift)->where('self_define', false)->where('id', $v['id'])->first();

                if ($v['value'] === '' || is_null($v['value'])) {
                    $savevansell->qty = $v['org_qty'];
                    $savevansell->self_enter = 0;
                } else {
                    $savevansell->qty = $v['value'];
                    $savevansell->self_enter = 1;
                }

                $savevansell->save();
            }
            /*   pd($vansells);

               foreach ($vansells as $v) {
                   $store = $v->productId.$v->productlevel;
                   $v->qty = $inv[$store];
                   $v->save();
               }*/

        }

        if ($this->_output == 'pdf') {


            $this->_reportId = 'vanselllist';
            $this->compileResults();
            $reportOutput = $this->outputPDF();

            //$filenameUn = $this->_reportId . '-' . str_random(10) . '-' . date("YmdHis");
            //$filenameUn = microtime(true);
            $filenameUn = $reportOutput['uniqueId'];
            $filename = $filenameUn . ".pdf";

            if (!file_exists(storage_path() . '/report_archive/' . $this->_reportId . '/' . $this->_shift))
                mkdir(storage_path() . '/report_archive/' . $this->_reportId . '/' . $this->_shift, 0777, true);
            $path = storage_path() . '/report_archive/' . $this->_reportId . '/' . $this->_shift . '/' . $filename;

            //   $path = storage_path() . '/report_archive/' . $this->_reportId . '/' . $filename;


            if (ReportArchive::where('id', $filenameUn)->count() == 0) {
                $archive = new ReportArchive();
                $archive->id = $filenameUn;
                $archive->report = 'vanselllist';
                $archive->file = $path;
                $archive->remark = $reportOutput['remark'];
                $archive->created_by = Auth::user()->id;
                $archive->zoneId = $this->_zone;
                $archive->shift = $this->_shift;
                $neworder = json_decode($reportOutput['associates']);
                $archive->associates = isset($reportOutput['associates']) ? json_encode(json_encode($neworder)) : false;
                $archive->save();
            }

            $pdf = $reportOutput['pdf'];
            $pdf->Output($path, "IF");
            //$pdf->Code128(10,3,$filenameUn,150,5);

            exit;
        }


    }


    public function compileResults()
    {
        $date = $this->_date;
        $zone = $this->_zone;


        // get invoice from that date and that zone
        $this->goods = ['1F' => [], '9F' => []];
        $invoicesQuery = Invoice::select('invoiceId')->wherein('invoiceStatus', ['2', '1', '96', '97'])->where('zoneId', $zone)->where('deliveryDate', $date);

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


                $productId = $item->productId;

                //  $productDetail = $products[$productId];
                $unit = $item->productQtyUnit;

                if ($item->productDetail->productLocation == '1') {
                    $this->goods['1F'][$productId][$unit] = [
                        'productId' => $productId,
                        'name' => $item->productDetail->productName_chi,
                        'unit' => $unit,
                        'unit_txt' => $item->productUnitName,
                        'counts' => (isset($this->goods['1F'][$productId][$unit]) ? $this->goods['1F'][$productId][$unit]['counts'] : 0) + $item->productQty,
                    ];
                }

            }
        }


        // pd($this->goods['1F']);
        //  pd(DB::getQueryLog());

        $this->_data = $this->goods['1F'];

        $vansell_query = vansell::select('productId', 'productlevel')->where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('self_define', false)->get()->toArray();
        $van_query = van::select('productId', 'productlevel','van_qty')->where('zoneId', $this->_zone)->where('deliveryDate', date('Y-m-d', $this->_date))->get()->toArray();

        // pd($van_query);
        //  pd($this->_data);

        $allIds = [];
        $create = [];
        $index = 0;
        foreach ($this->_data as $g) {
            foreach ($g as $k => $v) {

                $skip = false;

                $van_qty = 0;
                foreach($van_query as $k2 => $v2){
                    if ($v2['productId'] == $v['productId'] && $v2['productlevel'] == $v['unit']){
                        $van_qty = $v2['van_qty'];
                        break;
                    }
                }

                foreach ($vansell_query as $k1 => $v1){
                    if ($v1['productId'] == $v['productId'] && $v1['productlevel'] == $v['unit']) {
                        $vansell = vansell::where('productId', $v['productId'])->where('productlevel', $v['unit'])->where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('self_define', false)->first();
                        if ($vansell->qty == $vansell->org_qty && $vansell->self_enter == 0)
                            $vansell->qty = $v['counts'];
                        $vansell->org_qty = $v['counts'];
                        $vansell->van_qty = $van_qty;
                        $vansell->save();
                        $skip = true;
                        break;
                    }
                }

                if (!$skip) {
                    $create[$index]['productId'] = $v['productId'];
                    $create[$index]['name'] = $v['name'];
                    $create[$index]['unit'] = $v['unit_txt'];
                    $create[$index]['org_qty'] = $v['counts'];
                    $create[$index]['productlevel'] = $v['unit'];
                    $create[$index]['van_qty'] = $van_qty;
                    $create[$index]['date'] = $this->_date;
                    $create[$index]['zoneId'] = $this->_zone;
                    $create[$index]['shift'] = $this->_shift;
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

        vansell::insert($create);


        $dbIds = vansell::where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('self_define', false)->lists('productId');

        $result = array_diff($dbIds, $allIds);

        if (count($result) > 0)
            foreach ($result as $vv)
            {
                $del = vansell::where('date', $this->_date)->where('shift', $this->_shift)->where('zoneId', $zone)->where('productId', $vv)->where('self_define', false)->first();
                if ($del->self_enter == false)
                    $del->delete();
                else
                {
                    $del->org_qty = 0;
                    $del->save();
                }

            }

        $vansells = vansell::where('zoneId', $zone)->where('date', $date)->where('shift', $this->_shift)->where('self_define', false)->orderBy('productId', 'asc')->get();
        $this->_data['normal'] = $vansells;


        $vansell_selfdefine = vansell::where('zoneId', $zone)->where('date', $date)->where('shift', $this->_shift)->where('self_define', true)->orderBy('productId', 'asc')->get();
        $this->_data['selfdefine'] = $vansell_selfdefine;

        $vansells_pdf = vansell::where('zoneId', $zone)->where('date', $date)->where('shift', $this->_shift)->orderBy('productId', 'asc')->get()->toArray();

        $this->_pdf = $vansells_pdf;


    }

public
function registerFilter()
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
    ];

    return $filterSetting;
}

public
function registerDownload()
{
    $downloadSetting = [
        [
            'type' => 'pdf',
            'name' => '列印 PDF 版本',
            'warning' => false
        ],
    ];

    return $downloadSetting;
}


# PDF Section
public
function generateHeader($pdf)
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

public
function outputPDF()
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

    // pd($firstF);

    foreach ($firstF as $i => $f) {
        // for first Floor
        $pdf->AddPage();


        $this->generateHeader($pdf);

        $pdf->SetFont('chi', '', 10);

        $pdf->setXY(10, 50);
        $pdf->Cell(0, 0, "編號", 0, 0, "L");

        $pdf->setXY(40, 50);
        $pdf->Cell(0, 0, "貨品說明", 0, 0, "L");

        $pdf->setXY(120, 50);
        $pdf->Cell(0, 0, "訂單貨量", 0, 0, "L");

        $pdf->setXY(145, 50);
        $pdf->Cell(0, 0, "上貨總數貨", 0, 0, "L");

        $pdf->setXY(170, 50);
        $pdf->Cell(0, 0, "核數", 0, 0, "L");

        $pdf->Line(10, 53, 190, 53);

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

                if ($u['qty'] != 0 && $u['qty'] != -100) {
                    $pdf->setXY(10, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, $u['productId'], 0, 0, "L");

                    $pdf->setXY(40, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, $u['name'], 0, 0, "L");

                    $pdf->setXY(120, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, $u['qty'], 0, 0, "L");


                    $pdf->setXY(131, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, str_replace(' ', '', $u['unit']), 0, 0, "L");

                    $pdf->setXY(145, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, "________", 0, 0, "L");

                    $pdf->setXY(170, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, "________", 0, 0, "L");

                    $y += 7;
                }

            } else {
                if ($u['qty'] != 0 && $u['qty'] != -100 && $u['qty'] != -1) {
                    $pdf->setXY(10, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, $u['productId'], 0, 0, "L");

                    $pdf->setXY(40, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, $u['name'], 0, 0, "L");


                    $pdf->setXY(120, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, $u['qty'], 0, 0, "L");


                    $pdf->setXY(131, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, str_replace(' ', '', $u['unit']), 0, 0, "L");

                    $pdf->setXY(145, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, "________", 0, 0, "L");

                    $pdf->setXY(170, $y);
                    $pdf->SetFont('chi', '', 13);
                    $pdf->Cell(0, 0, "________", 0, 0, "L");

                    $y += 7;
                }

                if ($u['qty'] == '-100') {
                    $pdf->Line(10, $y, 190, $y);
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

public
function viewArchivedReport()
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
}