<?php
/**
 * Created by PhpStorm.
 * User: PKH
 * Date: 18/8/2015
 * Time: 14:42
 */

class permissionController extends Controller {

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */

    public function getPermissionList(){
       // $lists = permission::with('role')->get()->toArray();
        $lists['customer']['groupName']='客戶管理';
        $lists['customer']['view_customer']=1;
        $lists['customer']['edit_customer']=1;
        $lists['customer']['create_customer']=1;
        $lists['customer']['delete_customer']=0;

        return Response::json($lists);
    }

}

//Testing