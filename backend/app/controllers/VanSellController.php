<?php

class VanSellController extends BaseController {

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
        foreach($zones as $zone)
        {
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

    public function registerDownload()
    {
        $downloadSetting = [
            [
                'type' => 'pdf',
                'name' => '列印 PDF 版本',
                'warning'   =>  false
            ],
        ];

        return $downloadSetting;
    }


    public function compileResults()
    {
        $date = $this->_date;
        $zone = $this->_zone;
        $regen = false;

        $vansells = vansell::where('zoneId', $zone)->where('date', $date)->where('shift',$this->_shift)->where('version',$this->_version)->orderBy('productId','asc')->get();

        if(count($vansells)==0){
         vansell::where('zoneId', $zone)->where('date', $date)->where('shift',$this->_shift)->orderBy('productId','asc')->delete();

        // get invoice from that date and that zone
        $this->goods = ['1F'=>[], '9F'=>[]];
        Invoice::select('*')->where('invoiceStatus', '2')->where('version',true)->where('zoneId', $zone)->where('deliveryDate', $date)->where('shift',$this->_shift)->with('invoiceItem', 'products', 'client')
            ->chunk(50, function($invoicesQuery){


                // first of all process all products
                $productsQuery = array_pluck($invoicesQuery, 'products');
//pd($productsQuery);
                foreach($productsQuery as $productQuery)
                {
                    $productQuery = head($productQuery);
                    //pd($productQuery);
                    foreach($productQuery as $pQ)
                    {
                        $products[$pQ->productId] = $pQ;
                    }
                }

                // second process invoices
                foreach($invoicesQuery as $invoiceQ)
                {
                    $this->_invoices[] = $invoiceQ->invoiceId;

                    // first, store all invoices
                    $invoiceId = $invoiceQ->invoiceId;
                    $invoices[$invoiceId] = $invoiceQ;
                    $client = $invoiceQ['client'];

                    // second, separate 1F goods and 9F goods
                    foreach($invoiceQ['invoiceItem'] as $item)
                    {
                        // determin its product location
                        $productId = $item->productId;

                        $productDetail = $products[$productId];
                        $unit = $item->productQtyUnit;

                        if($productDetail->productLocation == '1')
                        {
                            $this->goods['1F'][$productId][$unit] = [
                                'productId' => $productId,
                                'name' => $productDetail->productName_chi,
                                'unit' => $unit,
                                'unit_txt' => $item->productUnitName,
                                'counts' => (isset($this->goods['1F'][$productId][$unit]) ? $this->goods['1F'][$productId][$unit]['counts'] : 0) + $item->productQty,
                            ];
                        }

                    }
                }

            });
        $this->_data = $this->goods['1F'];


            foreach($this->_data as $v){
                foreach ($v as $k => $v){
                    $create = new vansell();
                    $create->productId = $v['productId'];
                    $create->name = $v['name'];
                    $create->unit = $v['unit_txt'];
                    $create->org_qty = $v['counts'];
                    $create->date = $this->_date;
                    $create->zoneId= $this->_zone;
                    $create->shift = $this->_shift;
                    $create->version = $this->_version;
                    $create->save();
                }
            }
            $regen = true;
        }

        if($regen){
            $vansells = vansell::where('zoneId', $zone)->where('date', $date)->where('shift',$this->_shift)->where('version',$this->_version)->orderBy('productId','asc')->get();
        }

        $this->_data = $vansells;

    }

# PDF Section
    public function generateHeader($pdf)
    {
        $shift = ($this->_shift== 1)?'早班':'晚班';
        $pdf->SetFont('chi','',18);
        $pdf->Cell(0, 10,"炳記行貿易有限公司",0,1,"C");
        $pdf->SetFont('chi','U',16);
        $pdf->Cell(0, 10,$this->_reportTitle,0,1,"C");
        $pdf->SetFont('chi','U',13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->_zone, 2, '0', STR_PAD_LEFT), 0, 2, "L");
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->_date)."(".$shift.")", 0, 2, "L");
        $pdf->setXY(0, 0);
        $pdf->SetFont('chi','', 9);
        $pdf->Code128(10,$pdf->h-15,$this->_uniqueid,50,10);
        $pdf->Cell(0, 10, sprintf("報告編號: %s", $this->_uniqueid), 0, 2, "R");

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
    $i = 0;
    $pdf->AddFont('chi','','LiHeiProPC.ttf',true);
    // handle 1F goods
    $firstF = array_chunk($this->_data->toArray(), 26, true);

    foreach($firstF as $i=>$f)
    {
        // for first Floor
        $pdf->AddPage();


        $this->generateHeader($pdf);

        $pdf->SetFont('chi','',10);

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

        $pdf->setXY(10, $pdf->h-30);
        $pdf->Cell(0, 0, "備貨人", 0, 0, "L");

        $pdf->setXY(60, $pdf->h-30);
        $pdf->Cell(0, 0, "核數人", 0, 0, "L");

        $pdf->Line(10, $pdf->h-35, 50, $pdf->h-35);
        $pdf->Line(60, $pdf->h-35, 100, $pdf->h-35);

        $pdf->setXY(500, $pdf->h-30);
        $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i+1, count($firstF)) , 0, 0, "R");



        foreach($f as $id=>$u)
        {


                $pdf->setXY(10, $y);
                $pdf->Cell(0, 0, $id, 0, 0, "L");

                $pdf->setXY(40, $y);
                $pdf->Cell(0, 0, $u['name'], 0, 0, "L");

                $pdf->setXY(120, $y);
                $pdf->Cell(0, 0, $u['org_qty']+$u['qty'], 0, 0, "L");


                $pdf->setXY(130, $y);
                $pdf->Cell(0, 0, str_replace(' ', '', $u['unit']), 0, 0, "L");

                $pdf->setXY(145, $y);
                $pdf->Cell(0, 0, "________", 0, 0, "L");

                $pdf->setXY(170, $y);
                $pdf->Cell(0, 0, "________", 0, 0, "L");

                $y += 6;

        }

        $y += 10;
        // Notes part
        if($i == 0)
        {
            for($note=0;$note<=2;$note++)
            {
                $pdf->Line(10, $y, 27, $y);
                $pdf->Line(40, $y, 100, $y);
                $pdf->Line(120, $y, 135, $y);
                $pdf->Line(146, $y, 160, $y);
                $pdf->Line(171, $y, 185, $y);


                $y += 8;
            }
        }

    }

    // handle 9F goods


    //end of handel nine floor

    // output


    return [
        'pdf' => $pdf,
        'remark' => sprintf("Van Sell List Archive for Zone %s, DeliveryDate = %s created by %s on %s", $this->_zone, date("Y-m-d", $this->_date), Auth::user()->username, date("r")),
        'uniqueId' => $this->_uniqueid,
        'associates' => json_encode($this->_invoices),
    ];
}
# PDF Section


    public function loadAvailableReports()
    {


        if (Auth::user()->role[0]->id == 4){
            $filter = ['productReport','customerReport'];
            $reports = Report::select('*')->orderBy('id', 'asc')->whereNotIn('id',$filter)->get();
        }else{
            $reports = Report::select('*')->orderBy('id', 'asc')->get();
        }



        foreach($reports as $report)
        {
            $reportCustom[$report->group]['reports'][] = $report;
            $reportCustom[$report->group]['groupName'] = $report->group;
        }
        return Response::json($reportCustom);
    }

    public function loadvanSellReport(){
    $indata = Input::all();
    $indata['reportId'] = 'vanselllist';

    $this->_reportTitle = '預載單';


    $permittedZone = explode(',', Auth::user()->temp_zone);


    $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
    $this->_zone = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['value'] : $permittedZone[0]);
        $this->_shift = $indata['filterData']['shift'];
        $lastid = pickingListVersionControl::where('zone',$this->_zone)->where('date',date("Y-m-d",$this->_date))->where('shift',$this->_shift)->first();

        //  $lastid = @explode('-', $lastid->id);

        $this->_version = isset($lastid->f1_version) ? $lastid->f1_version : '1';
        $this->_reportTitle = sprintf("%s - v%s",  $this->_reportTitle,  $this->_version);

    // check if user has clearance to view this zone
    if(!in_array($this->_zone, $permittedZone))
    {
        App::abort(401, "Unauthorized Zone");
    }
    $this->_uniqueid = microtime(true);

        $this->_output = Input::get('output');

        if($this->_output == 'setting')
        {
            $returnInfo = [
                'title' => $this->registerTitle(),
                'filterOptions' => $this->registerFilter(),
                'downloadOptions' => $this->registerDownload(),
            ];

            echo json_encode($returnInfo);
            exit;
        }

        if($this->_output == 'preview')
        {
            $this->compileResults();
            return Response::json($this->_data);
        }

        if($this->_output == 'create'){
         // pd(Input::all());

            $vansells = vansell::where('zoneId', $this->_zone)->where('date', $this->_date)->where('shift',$this->_shift)->where('version',$this->_version)->orderBy('productId','asc')->get();
            $inv = [];
            foreach(Input::get('data') as $v){
                $inv[$v['productId']] = $v['value'];
            }
            foreach($vansells as $v){
                $v->qty = $inv[$v->productId];
                $v->save();
            }

        }

          if($this->_output == 'pdf'){

              $this->compileResults();
              $function = "outputPDF";
              $reportOutput = $this->outputPDF();

              //$filenameUn = $this->_reportId . '-' . str_random(10) . '-' . date("YmdHis");
              //$filenameUn = microtime(true);
              $filenameUn = $reportOutput['uniqueId'];
              $filename = $filenameUn . ".pdf";

              $path = storage_path() . '/report_archive/'.$this->_reportId.'/' . $filename;

              /*    if(ReportArchive::where('id',$filenameUn)->count() == 0){
                      $archive = new ReportArchive();
                      $archive->id = $filenameUn;
                      $archive->report = $this->_reportId;
                      $archive->file = $path;
                      $archive->remark = $reportOutput['remark'];
                      $archive->created_by = Auth::user()->id;
                      $unid = explode("-",$reportOutput['uniqueId']);

                      if(isset($reportOutput['associates'])){
                          $neworder = json_decode($reportOutput['associates']);

                          if($unid[1]>1){
                              $unid[1] -= 1;
                              $comma_separated = implode("-", $unid);
                              $chre = ReportArchive::where('id',$comma_separated)->first();
                              if(count($chre)>0){
                                  $invoiceIds = json_decode(json_decode($chre->associates, true, true));
                                  $neworder = array_diff($neworder,$invoiceIds);
                              }

                          }
                          $neworder = array_values($neworder);
                      }





                      $archive->associates = isset($reportOutput['associates']) ? json_encode(json_encode($neworder)) : false;
                      $archive->save();
                  }*/

              $pdf = $reportOutput['pdf'];
              $pdf->Output($path, "IF");
              //$pdf->Code128(10,3,$filenameUn,150,5);

              exit;
          }








}

    
    public function viewArchivedReport()
    {
        $rid = Input::get('rid');



        $report = ReportArchive::where('id', $rid)->first();

        $content = file_get_contents($report->file);

        header('Content-Type: application/pdf');
        header('Content-Length: '.strlen( $content ));
        header('Content-disposition: inline; filename="' . $report->file . '"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

        echo $content;
        
    }
}