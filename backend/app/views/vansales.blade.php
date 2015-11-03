<link href="http://frontend.pingkeehong.com/assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>
    $(function() {

        var today = new Date();
        var plus = today.getDay() == 6 ? 2 : 1;
        var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
        var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * 1);

        var ymonth = start_date.getMonth() + 1;
        var yyear = start_date.getFullYear();
        var yday = start_date.getDate();

        var day = currentDate.getDate();
        var month = currentDate.getMonth() + 1;
        var year = currentDate.getFullYear();

        $( "#datepicker" ).datepicker({
            dateFormat: "yy-mm-dd"
        });
        $( "#datepicker" ).datepicker("setDate" , year + '-' + month + '-' + day);

    });
</script>
<script>


    $("#datepicker").datepicker();
</script>

<div class="col-md-12">
    <span style="font-size:25px;">炳記行貿易有限公司</span>

    <div class="portlet light">


        <div class="form-body form-horizontal">

            {{ Form::open(array('url' => '/vans')) }}
            <div class="form-group">
                <label class="col-md-1 col-xs-2 control-label" style="text-align: left">區域:</label>
                <div class="col-md-3">
                    <select class="form-control ng-valid ng-dirty" name="zoneId">
                        <option value="0" selected="selected" label="NA">NA</option><option value="1" label="屯門">屯門</option><option value="2" label="天水圍">天水圍</option><option value="3" label="元朗">元朗</option><option value="4" label="大埔">大埔</option><option value="5" label="荃灣">荃灣</option><option value="6" label="葵涌">葵涌</option><option value="7" label="深水埗">深水埗</option><option value="8" label="旺角">旺角</option><option value="9" label="尖沙咀">尖沙咀</option><option value="10" label="土瓜灣">土瓜灣</option><option value="11" label="將軍澳">將軍澳</option><option value="12" label="灣仔">灣仔</option><option value="13" label="上環">上環</option><option value="14" label="柴灣">柴灣</option><option value="15" label="觀塘">觀塘</option><option value="16" label="沙田">沙田</option><option value="17" label="上水">上水</option><option value="18" label="太子">太子</option><option value="19" label="青衣">青衣</option><option value="20" label="新蒲崗">新蒲崗</option><option value="21" label="油塘">油塘</option><option value="22" label="馬鞍山">馬鞍山</option>
                    </select>
                </div>
            </div>


            <div class="form-group">
                <label class="col-md-1 col-xs-2 control-label" style="text-align: left">送貨日期:</label>
                <div class="col-md-3">
                    <p><input type="text" id="datepicker" name="deliveryDate"></p>
                </div>
            </div>


        </div>

        <div class="portlet-body">
            <table class="table table-bordered table-hover">
                <thead>
                <tr role="row" class="heading">
                    <th width="20%">
                        貨品編號
                    </th>
                    <th width="60%">
                        貨品名稱
                    </th>
                    <th width="10%">
                        數量
                    </th>
                    <th width="10%">
                        單位
                    </th>
                </tr>
                </thead>
                <tbody>

                <tr>
                    <td>B010</td>
                    <td>丁麵</td>
                    <td><input type="text" class="form-control" name="B010"></td>
                    <td>扎</td>
                </tr>

                <tr>
                    <td>101</td>
                    <td>#70西檸</td>
                    <td><input type="text" class="form-control" name="101"></td>
                    <td>箱</td>
                </tr>

                <tr>
                    <td>167</td>
                    <td>#80西檸</td>
                    <td><input type="text" class="form-control" name="167"></td>
                    <td>箱</td>
                </tr>

                <tr>
                    <td>100</td>
                    <td>#90西檸</td>
                    <td><input type="text" class="form-control" name="100"></td>
                    <td>箱</td>
                </tr>

                <tr>
                    <td>170</td>
                    <td>#138西檸</td>
                    <td><input type="text" class="form-control" name="170"></td>
                    <td>箱</td>
                </tr>

                <tr>
                    <td>200</td>
                    <td>美國小白蛋</td>
                    <td><input type="text" class="form-control" name="200"></td>
                    <td>箱</td>
                </tr>

                <tr>
                    <td>203</td>
                    <td>3A黃蛋</td>
                    <td><input type="text" class="form-control" name="203"></td>
                    <td>箱</td>
                </tr>
                <tr>
                    <td>218</td>
                    <td>4A黃蛋</td>
                    <td><input type="text" class="form-control" name="218"></td>
                    <td>箱</td>
                </tr>

                <tr>
                    <td>O029</td>
                    <td>龍之寶</td>
                    <td><input type="text" class="form-control" name="O029"></td>
                    <td>桶</td>
                </tr>

                <tr>
                    <td>N002</td>
                    <td>幼砂糖</td>
                    <td><input type="text" class="form-control" name="N002"></td>
                    <td>包</td>
                </tr>
                </tbody>
            </table>



        </div>

        <div class="form-group">
            <label class="col-md-2 col-xs-2 control-label" style="text-align: left">負責人:</label>
            <div class="col-md-3">
                <p><input type="text" id="controller" name="pic"></p>
            </div>
        </div>

        <div class="form-group">
            {{Form::submit('提交')}}
        </div>

        {{ Form::close() }}

    </div>
    <!-- END EXAMPLE TABLE PORTLET-->
</div>