const App = {
    data() {
        return {
            products: [],
            topic: "Ручное связывание ",

            linkType: null,
            mpId: null,
        }
    },

    methods: {
        hrefToAuto() {
            window.location.href = 'auto-ms?linkType=' + this.linkType
        },

        linkSearch(productId) {
            window.location.href = "manual-binding-ms?id=" + productId + "&linkType=" + this.linkType
        },

        async noLink(key) {
            this.products[key]['noLink'] = !this.products[key]['noLink']

            const response = await fetch('no-link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    linkType: this.linkType,
                    productId: this.products[key]['id'],
                    noLink: this.products[key]['noLink'],
                }),
            })

            const res = await response.json()

            await console.log(res)
        },

        async getProduct() {
            let products;
            const response = await fetch('/mp_link/get-not-link-ms', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    mpId: this.mpId,
                    linkType: this.linkType,
                }),
            })

            products = await response.json()

            this.products = await products.data
            this.topic = this.topic + this.products[0].mp_name

            await console.log(this.products)
        },

        getImg(imgList) {
            const imgs = JSON.parse(imgList)

            let imgRender = ""
            for (let i = 0; i < imgs.length; i++) {
                imgRender = imgRender + '<a target="_blank" href="' + imgs[i] + '"><img src="' + imgs[i] + '" style="margin: 0 0 0 10px; height: 100px"></a> '
            }

            return imgRender;
        },
    },

    mounted() {
        const urlParams = new URLSearchParams(window.location.search);
        this.mpId = urlParams.get('mpId');
        this.linkType = urlParams.get('linkType');

        this.getProduct()
    },

    component: {
        com: "com"
    }
}

Vue.createApp(App).mount('#app')