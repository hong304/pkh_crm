<?php

class ReportController extends BaseController {
    
    
    
    public function loadAvailableReports()
    {
        /*
        2 = SA
        3 = Manager
        4 = Sales
        5 = Supervisor
        */
        $filter = ['pickinglist','pickinglist9f','printlog','archivedreport','dailylist','vanselllist'];

        if(Auth::user()->can('view_customerbreakdown'))
            array_push($filter,'customerbreakdown');

        if(Auth::user()->can('view_creditsummary'))
           array_push($filter,'creditsummary');

        if(Auth::user()->can('view_commission'))
            array_push($filter,'commission');

        if(Auth::user()->can('view_auditReport'))
           array_push($filter,'auditReport');

        if(Auth::user()->can('view_cashreceiptsummary'))
           array_push($filter,'cashreceiptsummary');

        if(Auth::user()->can('view_costprice'))
           array_push($filter,'costprice');

        if(Auth::user()->can('view_customerReport'))
        array_push($filter,'customerReport');

        if(Auth::user()->can('view_itemssummary'))
        array_push($filter,'itemssummary');

        if(Auth::user()->can('view_productReport'))
        array_push($filter,'productReport');

        if(Auth::user()->id=='46' || Auth::user()->id=='23')
        array_push($filter,'reportstat');

      //  if(Auth::user()->can('view_vanselllist'))
       //     array_push($filter,'vanselllist');





        $reports = Report::select('*')->orderBy('id', 'asc')->whereIn('id',$filter)->get();

      /*  if (Auth::user()->role[0]->id == 4){
            $filter = ['productReport','customerReport','commission','creditsummary','itemssummary','reportstat'];
            $reports = Report::select('*')->orderBy('id', 'asc')->whereNotIn('id',$filter)->get();
        }else{
            $reports = Report::select('*')->orderBy('id', 'asc')->get();
        }*/



        foreach($reports as $report)
        {
            $reportCustom[$report->group]['reports'][] = $report;
            $reportCustom[$report->group]['groupName'] = $report->group;
        }

      asort($reportCustom);

        return Response::json($reportCustom);
    }

    public function getPrintLog(){


            $mode = Input::get('mode');


        if($mode == 'reprint'){
            $update = Printlog::where('job_id',Input::get('filterData'))->first()->toArray();
            unset($update['job_id']);
            unset($update['created_at']);
            unset($update['updated_at']);
            unset($update['complete_time']);

            $update['status'] = 'ready_for_ftp';
            $update['created_at'] = new \DateTime;
            $update['updated_at'] = new \DateTime;

            DB::table('Printlogs')->insert(
                $update
            );

            $job_id = DB::getPdo()->lastInsertId();

            $class = new PrintQueueController();
            return $class->sendJobViaFTP($job_id);
        }
            if($mode == 'collection')
            {
               // Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);

                $filter = Input::get('filterData');


                $Printlogs = Printlog::select('*')->where('updated_at','LIKE',$filter['onedate'].'%');

                // zone
                $permittedZone = explode(',', Auth::user()->temp_zone);

                if($filter['zone'] != '')
                {
                    // check if zone is within permission
                    if(!in_array($filter['zone']['zoneId'], $permittedZone))
                    {
                        // *** status code to be updated
                        App::abort(404);
                    }
                    else
                    {
                        $Printlogs->where('target_path', $filter['zone']['zoneId']);
                    }
                }
                else
                {
                    $Printlogs->wherein('target_path', $permittedZone);
                }

                // created by
                //   $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
                $Printlogs = $Printlogs->with('zone')->orderBy('updated_at','desc');

                return Datatables::of($Printlogs)
                    ->addColumn('view', function ($v) {
                       // return '<a href="'.$_SERVER['backend'].'/'.$v->file_path.'" target="_blank">View</a>';
                        if($_SERVER['env'] == 'uat')
                            $url = 'http://backend.pingkeehong.com';
                        else
                            $url = $_SERVER['backend'];

                        return '<a href="'.$url.'/'.$v->file_path.'" target="_blank">View</a>';
                    })
                    ->addColumn('link', function ($v) {
                        return '<span onclick="reprint(\''.$v->job_id.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 重印</span>';
                    })
                    ->make(true);


            }




        }


    
    public function loadReport()
    {

        $reportId = Input::get('reportId');
        $data = Input::all();



        $factory = new ReportFactory($reportId,$data);
        return $factory->run();
        
        
    }


public function loadvanSellReport(){
    $data = Input::all();
    $data['reportId'] = 'vanselllist';
    $factory = new ReportFactory('vanselllist',$data);
    $factory->run();
}

    
    public function viewArchivedReport()
    {
        $rid = Input::get('rid');
        $shift = Input::get('shift');



        $report = ReportArchive::where('id', $rid);
        if($shift!='')
            $report->where('shift',$shift);

        $report= $report->first();

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

    public function genA4Invoice (){


        $invoiceDetails = Invoice::where('invoiceId',Input::get('invoiceId'))->with(['invoiceItem'=>function($query){
            $query->with('productDetail')->orderby('productId','ASC');
        }])->with('client')->first()->toArray();

    //    pd($invoiceDetails);

            $pdf = new PDF();

            $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
            // handle 1F goods
            $good = array_chunk($invoiceDetails['invoice_item'], 30, true);

            $numItems = count($good);
            $i = 0;

            foreach ($good as $ij => $f) {
                $i++;
                $pdf->AddPage();



                $pdf->SetFont('chi','',18);
                $pdf->setXY(45, 10);
                $pdf->Cell(0, 0,"炳 記 行 貿 易 有 限 公 司",0,1,"L");

                $pdf->SetFont('chi','',18);
                $pdf->setXY(45, 18);
                $pdf->Cell(0, 0,"PING KEE HONG TRADING COMPANY LTD.",0,1,"L");

                $pdf->SetFont('chi','',9);
                $pdf->setXY(45, 25);
                $pdf->Cell(0, 0,"Flat B, 9/F., Wang Cheung Industrial Building, 6 Tsing Yeung St., Tuen Mun, N.T. Hong Kong.",0,1,"L");

                $pdf->SetFont('chi','',9);
                $pdf->setXY(45, 30);
                $pdf->Cell(0, 0,"TEL:24552266    FAX:24552449",0,1,"L");

                $pdf->SetFont('chi','U',16);
                $pdf->setXY(0, 40);
                $pdf->Cell(0, 0,'發票',0,0,"C");

                $image = public_path('logo.jpg');
                $pdf->Cell( 40, 40, $pdf->Image($image, 15, 5, 28), 0, 0, 'L', false );


                $pdf->SetFont('chi', 'U', 11);
                $pdf->setXY(10, 50);
                $pdf->Cell(0, 0, "單據編號: " . $invoiceDetails['invoiceId'], 0, 2, "L");
                $pdf->setXY(10, 58);
                $pdf->Cell(0, 0, "送貨日期: " . $invoiceDetails['deliveryDate_date'], 0, 2, "L");
                $pdf->setXY(10, 66);
                $pdf->Cell(0, 0, "參考編號: " . $invoiceDetails['customerRef'], 0, 2, "L");

                $pdf->SetFont('chi', '', 11);
                $pdf->setXY(100, 50);
                $pdf->Cell(0, 0, "客戶名稱: " . $invoiceDetails['client']['customerName_chi'] .'('.$invoiceDetails['client']['customerId'].')', 0, 2, "L");
                $pdf->setXY(100, 58);
                $pdf->Cell(0, 0, "地址: " . $invoiceDetails['client']['address_chi'], 0, 2, "L");


                $remark = explode("\n", $invoiceDetails['invoiceRemark']);

                $ij = 66;
                foreach ($remark as $k => $v){
                         $pdf->setXY(100, $ij);
                    if($k>0){
                        $pdf->Cell(0, 0, "        " . $remark[$k], 0, 2, "L");
                    }else
                         $pdf->Cell(0, 0, "備註: " . $remark[$k], 0, 2, "L");
                        $ij+=5;
                }

$y = 80;

                $pdf->SetFont('chi', '', 10);

                $pdf->setXY(10, $y);
                $pdf->Cell(0, 0, "編號", 0, 0, "L");

                $pdf->setXY(50, $y);
                $pdf->Cell(0, 0, "貨品說明", 0, 0, "L");

                $pdf->setXY(120, $y);
                $pdf->Cell(0, 0, "數量", 0, 0, "L");

                $pdf->setXY(145, $y);
                $pdf->Cell(0, 0, "單價", 0, 0, "L");

                $pdf->setXY(170, $y);
                $pdf->Cell(0, 0, "金額", 0, 0, "L");

                $pdf->Line(10, $y+3, 190, $y+3);

                $y += 10;

                foreach ($f as $u) {

                    $pdf->setXY(10, $y);
                    $pdf->Cell(0, 0, $u['productId'], 0, 0, "L");

                    if($u['productRemark'] != ''){
                        $hyphen = ' - ';
                    }else
                        $hyphen = '';

                    $pdf->setXY(50, $y);
                    $pdf->Cell(0, 0, sprintf("%s%s%s",$u['product_detail']['productName_chi'],$hyphen,$u['productRemark']), 0, 0, "L");

                    $pdf->setXY(120, $y);
                    $pdf->Cell(10, 0, number_format($u['productQty'], 1, '.', ',').$u['productUnitName'], 0, 0, "R");

                    $pdf->setXY(145, $y);
                    $pdf->Cell(10, 0, sprintf("$%s", number_format($u['productPrice'], 1, '.', ',')), 0, 0, "R");

                    $pdf->setXY(170, $y);
                    $pdf->Cell(10, 0,  sprintf("$%s",number_format($u['productPrice']*$u['productQty'], 1, '.', ',')), 0, 0, "R");

                    $y += 6;
                }

                $pdf->setXY(500, 276);
                $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i, $numItems), 0, 0, "R");

            }



            if ($i === $numItems) {

                $pdf->Line(10, $y, 190, $y);

                $pdf->setXY(170, $y + 5);
                $pdf->Cell(10, 0, "$" . number_format($invoiceDetails['amount'], 2, '.', ','), 0, 0, "R");


        }

        $pdf->Output('','I');
    }
}