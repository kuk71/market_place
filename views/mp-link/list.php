<div id="app" v-cloak>
    <button class="btn" @click="console.log(mpLinks)">Показать</button>

    <button class="btn btn-primary" @click="getLinkSecond()">Загрузить второй уровень соединения</button>

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
                v-for="(mpLink, key) in mpLinks"
                :key="key"
                :class="color[mpLink.colorId]"
        >

        <tr>
            <td rowspan="4">
                <button class="btn btn-primary" @click="delLink(key, mpLink.linkId)">Удалить</button>
            </td>

            <td colspan="10" v-html='this.getImg(mpLink.firstImg)'></td>

        </tr>

        <tr>
            <td colspan="11" v-html='this.getImg(mpLink.secondImg)'></td>
        </tr>

        <tr>
            <td>{{ mpLink.firstId }}</td>
            <td>{{ mpLink.firstColor }}</td>
            <td>{{ mpLink.firstVendorCode }}</td>

            <td>
                <div style="max-height: 100px; overflow: auto">{{ mpLink.firstName }}</div>
            </td>
            <td>
                <div style="max-height: 100px; overflow: auto">{{ mpLink.firstDescription }}</div>
            </td>
            <td><div style="max-height: 100px; overflow: auto">{{ mpLink.firstSet }}</div></td>

            <td>{{ mpLink.firstSize1mm }}</td>
            <td>{{ mpLink.firstSize2mm }}</td>
            <td>{{ mpLink.firstSize3mm }}</td>
            <td>{{ mpLink.firstWeightGr }}</td>
        </tr>

        <tr>
            <td>{{ mpLink.secondId }}</td>
            <td>{{ mpLink.secondColor }}</td>
            <td>{{ mpLink.secondVendorCode }}</td>
            <td>
                <div style="max-height: 100px; overflow: auto">{{ mpLink.secondName }}</div>
            </td>
            <td>
                <div style="max-height: 100px; overflow: auto">{{ mpLink.secondDescription }}</div>
            </td>
            <td><div style="max-height: 100px; overflow: auto">{{ mpLink.firstSet }}</div></td>

            <td>{{ mpLink.secondSize1mm }}</td>
            <td>{{ mpLink.secondSize2mm }}</td>
            <td>{{ mpLink.secondSize3mm }}</td>
            <td>{{ mpLink.secondWeightGr }}</td>
        </tr>

        </tbody>
    </table>

</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="/js/mp_link.js"></script>