<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="20%">貨品</th>
            <th width="50%">名稱</th>
            <th width="15%">累計</th>
            <th width="15%">單位</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row):?> 
            <tr>
                <td><?php echo $row['productId']; ?></td>
                <td><?php echo $row['productName_chi']; ?></td>
                <td><?php echo $row['productQtys']; ?></td>
                  <td><?php echo $row['productUnitName']; ?></td>

            </tr>  
         <?php endforeach; ?>.

    </tbody>
</table>

