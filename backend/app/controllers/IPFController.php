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
            $filter = Input::get('filterData');
            Paginator::setCurrentPage((Input::get('start')+10) / Input::get('length'));
            $ipf = InvoicePrintFormat::select('*');
                        
            // query
            
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $ipf = $ipf->orderBy('updated_at','desc')->paginate($page_length);
            
            
            foreach($ipf as $c)
            {
                $c->from = date("Y-m-d", $c->from);
                $c->to = date("Y-m-d", $c->to);
                $c->link = '<span onclick="editIPF(\''.$c->ipfId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
            }
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