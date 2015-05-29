<?php

class CustomerController extends BaseController
{

    /*
     * @func jsonCheckClient
     * @parm post string keyword
     * @cache enabled
     * @return json
     */

    public function jsonCheckClient()
    {
        # Request
        // $time_start = microtime(true);
        $keyword = Input::has('client_keyword') && Input::get('client_keyword') != '' ? Input::get('client_keyword') : 'na';


        # Process
        if ($keyword != 'na') {


            if (!isset($keyword['keyword']))
                $keyword['keyword'] = '';
            if (!isset($keyword['id']))
                $keyword['id'] = '';
            if (!isset($keyword['zone']['zoneId']))
                $keyword['zone']['zoneId'] = '';

            // $keyword = str_replace(array('?', '*'), '%', $keyword);
            $clientArray = Customer::select('deliveryZone', 'phone_1', 'customerName_chi', 'customerId', 'address_chi', 'routePlanningPriority', 'remark', 'paymentTermId', 'shift', 'discount')
                ->where('status', '1')
                ->where(function ($query) use ($keyword) {
                    if ($keyword['keyword'] != '' && $keyword['id'] != '') {
                        $query->where('customerName_chi', 'LIKE', '%' . $keyword['keyword'] . '%')
                            ->orwhere('phone_1', 'LIKE', '%' . $keyword['keyword'] . '%')
                            ->where('customerId', 'LIKE', '%' . $keyword['id'] . '%');
                    }
                    if ($keyword['keyword'] != '') {
                        $query->where('customerName_chi', 'LIKE', '%' . $keyword['keyword'] . '%')
                            ->orwhere('phone_1', 'LIKE', '%' . $keyword['keyword'] . '%');
                    }

                    if ($keyword['id'] != '') {
                        $query->where('customerId', 'LIKE', '%' . $keyword['id'] . '%');
                    }

                });

            if ($keyword['zone']['zoneId'] != '') {
                $clientArray->where('deliveryZone', $keyword['zone']['zoneId']);
            } else {
                $clientArray->wherein('deliveryZone', explode(',', Auth::user()->temp_zone));
            }

            $clientArray = $clientArray->limit(20)
                ->get();


        } else {
            $clientArray = Customer::select('deliveryZone', 'phone_1', 'customerName_chi', 'customerId', 'address_chi', 'routePlanningPriority', 'remark', 'paymentTermId', 'shift', 'discount')
                ->where('deliveryZone', Session::get('zone'))
                ->where('status', '1')
                //->with('Zone')
                ->limit(15)
                ->get();

        }
        // $time_end = microtime(true);
        // $time = $time_end - $time_start;
        // syslog(LOG_INFO, "Search $keyword in $time seconds");
        return Response::json($clientArray);

    }

    public function jsonFindClientById()
    {
        if (!Input::has('customerId')) {

        }

        $id = Input::get('customerId');

        $clientArray = Customer::select('customerId', 'customerName_chi', 'address_chi', 'remark', 'deliveryZone', 'phone_1', 'routePlanningPriority', 'paymentTermId', 'discount')
            ->where('customerId', $id)->with('Zone')
            ->first();

        return Response::json($clientArray);
    }

    public function jsonManiulateCustomer()
    {

        if (Input::get('mode') == 'del') {
            // pd(Input::get('customer_id'));
            $query = Customer::where('customerId', Input::get('customer_id'))->first();
            $query->delete();
            // p(Input::get('customer_id'));
            return [];
        }

        $i = Input::get('customerInfo');
        $cm = new CustomerManipulation($i['customerId'], (isset($i['productnewId']) ? $i['productnewId'] : false));
        $id = $cm->save($i);

        return Response::json(['mode' => ($i['customerId'] == $id ? 'update' : 'create'), 'id' => $id]);
    }

    public function jsonQueryCustomer()
    {


        $mode = Input::get('mode');

        if ($mode == 'collection') {
            $filter = Input::get('filterData');


            if (!isset($filter['zone']['zoneId']))
                $filter['zone']['zoneId'] = '';

            Paginator::setCurrentPage((Input::get('start') + 10) / Input::get('length'));
            $customer = Customer::leftJoin('customer_groups', function($join) {
                $join->on('customer_groups.id', '=','Customer.customer_group_id');
            });


            // $customer->where('customerId', $filter['clientId']);

            if ($filter['status'] == 99) {
                $customer->onlyTrashed();
            } else if ($filter['status'] != 100) {
                $customer->where('status', $filter['status']);
            }

            // zone
            $permittedZone = explode(',', Auth::user()->temp_zone);

            if ($filter['zone']['zoneId'] != '') {
                // check if zone is within permission
                if (!in_array($filter['zone']['zoneId'], $permittedZone)) {
                    // *** status code to be updated
                    App::abort(404);
                } else {
                    $customer->where('deliveryZone', $filter['zone']['zoneId']);
                }
            } else {
                $customer->wherein('deliveryZone', $permittedZone);
            }


            $customer->where(function ($query) use ($filter) {
                $query
                    ->where('customerName_chi', 'LIKE', '%' . $filter['name'] . '%')
                    ->where('Customer.phone_1', 'LIKE', '%' . $filter['phone'] . '%')
                    ->where('Customer.customerId', 'LIKE', '%' . $filter['id'] . '%');
            });

            // query
           $customer->with('group');
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;

            if($filter['groupname']!='')
                $customer->where('customer_groups.name','LIKE','%'.$filter['groupname'].'%');
            $customer = $customer->paginate($page_length);


            foreach ($customer as $c) {
                if ($c->paymentTermId == '1') {
                    $c->paymentTerms = 'Cash';
                } elseif ($c->paymentTermId == '2') {
                    $c->paymentTerms = 'Credit';
                } else {
                    $c->paymentTerms = 'UNKNOWN';
                }

                if ($c->status == '1')
                    $c->status = '正常';
                else
                    $c->status = '暫停';

                if ($c->deleted_at != '') {
                    $c->delete = '';
                    $c->link = '';
                } else {
                    $c->delete = '<span onclick="delCustomer(\'' . $c->customerId . '\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';
                    $c->link = '<span onclick="editCustomer(\'' . $c->customerId . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                }

            }
        } elseif ($mode == 'single') {
            $customer = Customer::where('customerId', Input::get('customerId'))->with('group')->first();
          } elseif ($mode == 'checkId') {
            $customer = Customer::select('customerId')->where('customerId', Input::get('customerId'))->first();
            $customer = count($customer);
        }

        return Response::json($customer);
    }


}