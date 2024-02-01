<?php
/** @var $catalog - массив элементов каталога; получен из контроллера */
?>

<table class="table table-bordered border-dark">
    <thead>
    <tr>
        <th scope="col"></th>
        <th scope="col">МП</th>
        <th scope="col">Код МП</th>
        <th scope="col">Код продовца</th>
        <th scope="col">Название</th>
        <th scope="col">Описание</th>
        <th scope="col">Цвет</th>
    </tr>
    </thead>

    <?PHP

    foreach($catalog AS $products) {
        echo "<tbody class=\"table-group-divider align-top border-dark border-3\">";

        foreach ($products AS $product) {
            $img = json_decode($product['img']);

            if(isset($img[0])) {
                $img = "<img style='height: 100px' src='$img[0]'>";
            }

            echo "<tr class=\"border-1\">";
            echo "  <td>{$img}</td>";
            echo "  <td>{$product['mp_name']}</td>";
            echo "  <td>{$product['product_mp_id']}</td>";
            echo "  <td><div style=\"max-height: 100px; overflow: auto\">{$product['vendor_code']}</div></td>";
            echo "  <td><div style=\"max-height: 100px; overflow: auto\">{$product['name']}</div></td>";
            echo "  <td><div style=\"max-height: 100px; overflow: auto\">{$product['description']}</div></td>";
            echo "  <td>{$product['color']}</td>";

            echo "</tr>";
        }

        echo "<tbody>";
    }

    ?>

</table>