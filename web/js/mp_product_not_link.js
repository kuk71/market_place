const App = {
    data() {
        return {
            products: [],

            linkType: null,
            mpId: null,
        }
    },

    methods: {
        linkSearch(productId) {
            window.location.href = "manual-binding?id=" + productId + "&linkType=" + this.linkType
        },

        async noLink(key) {
            this.products[key]['noLink'] = !this.products[key]['noLink']

            const response = await fetch('/mp-link/no-link', {
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
            const response = await fetch('/mp-link/get-not-link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    linkType: this.linkType,
                    mpId: this.mpId,
                }),
            })

            products = await response.json()

            this.products = await products.data

            await console.log(this.products)
        },

        getImg(imgList) {
            const imgs = JSON.parse(imgList)

            let imgRender = ""
            for (let i = 0; i < imgs.length; i++) {
                imgRender = imgRender + '<img src="' + imgs[i] + '" style="margin: 0 0 0 10px; height: 100px"> '
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