const App = {
    data() {
        return {
            products: [],

            linkType: null,
            mpId: null,
        }
    },

    methods: {
        pairSearch(productId) {
            console.log(123)
            window.location.href = "manual-binding?id=" + productId + "&linkType=" + this.linkType
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