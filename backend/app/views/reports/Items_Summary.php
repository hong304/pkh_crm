<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="15%">貨品</th>
            <th width="40%">名稱</th>
            <th width="15%">累計</th>
            <th width="10%">單位</th>
            <th width="15%">總額</th>
            <th width="20%">平均</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row){
            if ($row['productQtys']>0){?>
            <tr>
                <td><?php echo $row['productId']; ?></td>
                <td><?php echo $row['productName_chi']; ?></td>
                <td><?php echo $row['productQtys']; ?></td>
                  <td><?php echo $row['productUnitName']; ?></td>
                <td><?php echo '$'. number_format($row['productAmount']); ?></td>
                <td><?php echo '$'. number_format($row['productAmount']/$row['productQtys']); ?></td>
            </tr>
         <?php }
        }?>

    </tbody>
</table>

