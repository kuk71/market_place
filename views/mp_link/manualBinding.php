<div id="app" v-cloak>

    <h1>Ручное связывание</h1>

    <button class="btn btn-primary"  @click="hrefToManual()">Вернуться к не связанным товарам</button>


    <br><br>

    <div class="sticky-top bg-white m-0 bg-info-subtle" style="border-bottom: 5px solid red">
        <table class="table table-bordered border-dark m-0">
            <thead>
            <tr>
                <th scope="col" style="width: 135px"></th>
                <th scope="col" style="width: 100px">id</th>
                <th scope="col" style="width: 110px">Цвет</th>
                <th scope="col" style="width: 200px">Код</th>
                <th scope="col" style="width: 200px">Название</th>
                <th scope="col">Описание</th>
                <th scope="col" style="width: 150px">Комплект</th>

                <th scope="col" style="width: 85px">Ширина</th>
                <th scope="col" style="width: 85px">Высота</th>
                <th scope="col" style="width: 85px">Длинна</th>
                <th scope="col" style="width: 85px">Вес</th>
            </tr>
            </thead>

            <tbody>
            <tr>
                <td style="width: 135px" rowspan="2">
                    <button class="btn btn-primary" @click="changeShowAll()">{{ showAllButton }}</button>
                </td>

                <td colspan="10" v-html='this.getImg(productLink.img)'></td>

            </tr>

            <tr>
                <td>{{ productLink.id }} <br> {{ productLink.product_mp_id }}</td>
                <td>{{ productLink.color }}</td>
                <td>{{ productLink.vendor_code }}</td>

                <td>
                    <div style="max-height: 100px; overflow: auto">{{ productLink.name }}</div>
                </td>
                <td>
                    <div style="max-height: 100px; overflow: auto">{{ productLink.description }}</div>
                </td>
                <td>
                    <div style="max-height: 100px; overflow: auto">{{ productLink.kit }}</div>
                </td>

                <td>{{ productLink.size_1_mm }}</td>
                <td>{{ productLink.size_2_mm }}</td>
                <td>{{ productLink.size_3_mm }}</td>
                <td>{{ productLink.weight_gr }}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="m-0">
        <table class="table table-bordered border-dark m-0">


            <tbody
                    class="table-group-divider align-top border-dark"
                    v-for="(product, key) in productsForLink"
                    :key="key"
                    v-show="showAll || product.link_candidate == null"
            >

            <tr>
                <td style="width: 135px" rowspan="2">
                    <button class="btn btn-primary" @click="pairLink(key, product.id)">Связать</button>
                </td>

                <td colspan="10" v-html='this.getImg(product.img)'></td>

            </tr>

            <tr>
                <td style="width: 100px">{{ product.id }} <br> {{ product.product_mp_id }}</td>


                <td style="width: 110px">
                    <div style="max-width: 90px; overflow: auto">{{ product.color }}</div>
                </td>


                <td style="width: 200px">
                    <div style="max-width: 180px; overflow: auto">{{ product.vendor_code }}</div>
                </td>

                <td style="width: 200px">
                    <div style="max-height: 100px; overflow: auto">{{ product.name }}</div>
                </td>
                <td>
                    <div style="max-height: 100px; overflow: auto">{{ product.description }}</div>
                </td>
                <td style="width: 150px">
                    <div style="max-height: 100px; overflow: auto">{{ product.kit }}</div>
                </td>

                <td style="width: 85px">{{ product.size_1_mm }}</td>
                <td style="width: 85px">{{ product.size_2_mm }}</td>
                <td style="width: 85px">{{ product.size_3_mm }}</td>
                <td style="width: 85px">{{ product.weight_gr }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="/js/mp_link/manual_binding.js"></script>