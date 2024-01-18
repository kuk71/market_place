const App = {
    data() {
        return {
            products: [],
        }
    },

    methods: {
        pairSearch(key, productId) {
            console.log(key)
        },
        async getProduct() {
            let products;
            const response = await fetch('/mp-link/get-not-link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    linkType: 1,
                    mpId: 1,
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

    computed: {},

    watch: {},

    mounted() {
        this.getProduct()
    },

    component: {
        com: "com"
    }
}

Vue.createApp(App).mount('#app')