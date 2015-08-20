<?php

class IPFController extends BaseController {


    
    public function jsonManiulateIPF()
    {
        $i = Input::get('info'); 
        $cm = new IPFManipulation($i['ipfId']);
        $cm->save($i);
    }
    
    public function jsonQueryIPF()
    {
        
         
        $mode = Input::get('mode');
        
        if($mode == 'collection')
        {

            $ipf = InvoicePrintFormat:: select(['ipfId','from', 'to', 'size']);
            return Datatables::of($ipf)
                ->addColumn('link', function ($ip) {
                    if(Auth::user()->can('edit_adv'))
                        return '<span onclick="editIPF(\''.$ip->ipfId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                    else
                        return '';
                })
                ->editColumn('from', function ($ip) {
                    return  date("Y-m-d", $ip->from);
                })
                ->editColumn('to', function ($ip) {
                    return  date("Y-m-d", $ip->to);
                })
                ->make(true);

        }
        elseif($mode == 'single')
        {
            $ipf = InvoicePrintFormat::where('ipfId', Input::get('ipfId'))->first();
            $ipf->from = date("Y-m-d", $ipf->from);
            $ipf->to = date("Y-m-d", $ipf->to);
        }
        
        return Response::json($ipf);
    }
 

}