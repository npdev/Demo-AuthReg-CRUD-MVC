<table class="table table-bordered table-condensed">
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Price</th>
    </tr>
    <?php
    if (!empty($data)) {
        foreach ($data as $key => $row) {
            echo '<tr>';
            foreach ($row as $field => $value) {
                echo "<td>$value</td>";
            }
            echo '</tr>';
        }
    }
    ?>
</table>
