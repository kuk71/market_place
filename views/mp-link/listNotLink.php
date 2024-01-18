<div id="app" v-cloak>
    <table class="table table-bordered border-dark">
        <thead>
        <tr>
            <th scope="col"></th>
            <th scope="col">id</th>
            <th scope="col">Цвет</th>
            <th scope="col">Код продовца</th>
            <th scope="col">Название</th>
            <th scope="col">Описание</th>
            <th scope="col">Комплект</th>

            <th scope="col">Ширина</th>
            <th scope="col">Высота</th>
            <th scope="col">Длинна</th>
            <th scope="col">Вес</th>
        </tr>
        </thead>

        <tbody
                class="table-group-divider align-top border-dark"
                v-for="(product, key) in products"
                :key="key"
        >

        <tr>
            <td rowspan="2">
                <button class="btn btn-primary" @click="pairSearch(product.id)">Искать пару</button>
            </td>

            <td colspan="10" v-html='this.getImg(product.img)'></td>

        </tr>

        <tr>
            <td>{{ product.product_mp_id }}</td>
            <td>{{ product.color }}</td>
            <td>{{ product.vendor_code }}</td>

            <td>
                <div style="max-height: 100px; overflow: auto">{{ product.name }}</div>
            </td>
            <td>
                <div style="max-height: 100px; overflow: auto">{{ product.description }}</div>
            </td>
            <td>
                <div style="max-height: 100px; overflow: auto">{{ product.kit }}</div>
            </td>

            <td>{{ product.size_1_mm }}</td>
            <td>{{ product.size_2_mm }}</td>
            <td>{{ product.size_3_mm }}</td>
            <td>{{ product.weight_gr }}</td>
        </tr>
        </tbody>
    </table>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script type="module" src="/js/mp_product_not_link.js"></script>