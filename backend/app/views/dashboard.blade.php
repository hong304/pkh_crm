<?php
$today              = strtotime("00:00:00");
$yesterday          = strtotime("-1 day", $today);
$tomorrow = strtotime("+1 day",$today);
?>
<style>
table {
font-size:20px;
}
div.centre
{
    width: 200px;
    display: block;
    margin-left: auto;
    margin-right: auto;
    text-align: left;
    line-height:2;
}
.pre{
    font-size: 15px;
    color:black;
    margin-left:0px;
}

</style>
<meta http-equiv="refresh" content="30">
<link href="http://frontend.pingkeehong.com/assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>


<div class="row" style="margin: 10px">
<div class="col-md-9">
<table class="table table-bordered" style="border: 1px solid black !important;">
    <tbody>

    <?php foreach($nr as $row):?>
    <tr>
        <?php foreach($row as $k => $v):?>

                      <td style="background-color:<?php echo ($k%2==1)?'#FAFAFA':'';?>"><div><span style="font-weight:bold"><?php printf('%s (%s)',$k,$v['name'])."<br/>"; ?></span></div>
<div style="border-top: 1px solid #ddd;" class="centre">
                      <?php
                              foreach($v['date'] as $k1 => $v1){
                                  if($k1 == $yesterday){
                                      echo '昨天:<span style="color: #ff0000;margin-left:10px;">'.$v1['volume'].'<span class="pre">('.$v1['percentage'].')</span></span><br/>';
                                  }
                                  if($k1 == $today){
                                      if($v['compare']==2)
                                          $arrow = '<span class="glyphicon glyphicon-arrow-up" style="margin-left:8px;"></span>';
                                      else if ($v['compare'] == 0){
                                          $arrow = '<span class="glyphicon glyphicon-arrow-down" style="margin-left:8px;"></span>';
                                      }else
                                          $arrow = '';


                                      echo '今天:<span style="color: blue;margin-left:10px;">'.$v1['volume'].'<span class="pre">('.$v1['percentage'].')</span></span>'.$arrow.'<br/>';
                                  }
                                  if($k1 == $tomorrow){
                                      echo '明天:<span style="color:#610655;margin-left:10px;">'.$v1['volume'].'<span class="pre">('.$v1['percentage'].')</span></span><br/>';
                                  }
                              }
                              ?>
</div>
                      </td>

        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="col-md-3">
    <table class="table table-bordered" style="font-size:20px;">

        <thead>
        <th class="heading">Total Volume</th>
        </thead>
        <tbody>


            <?php
            $i = 0;
            foreach($total as $k => $v){
            $i++;?>
            <tr>
             <td >
                    <?php

                        if($k == $yesterday){
                            echo '昨天:<span style="color: #ff0000;margin-left:10px;">'.$v['volume'].'</span><br/>';
                        }
                        if($k == $today){
                            echo '今天:<span style="color: blue;margin-left:10px;">'.$v['volume'].'</span><br/>';
                        }
                        if($k == $tomorrow){
                            echo '明天:<span style="color:#610655;margin-left:10px;">' .$v['volume'].'</span><br/>';
                        }
             ?>
                </td>
            </tr>
            <?php } ?>


        </tbody>
    </table>
</div>
</div>