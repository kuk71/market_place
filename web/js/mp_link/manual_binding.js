const App = {
    data() {
        return {
            productsForLink: [],
            productLink: [],
            linkType: null,
            productId: null,
            showAll: true,
            showAllButton: "Искать только в не связанных товарах"
        }
    },

    methods: {
        hrefToManual() {
            window.location.href = 'manual?linkType=' + this.linkType + '&mpId=' + this.productLink['mp_id']
        },

        changeShowAll() {
            this.showAllButton = "Искать только в не связанных товарах"

            if (this.showAll) {
                this.showAllButton = "Искать среди всех товаров"
            }

            this.showAll = !this.showAll
        },

        async pairLink(key, productId) {
            const response = await fetch('/mp_link/link-products', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    firstProductId: this.productLink['id'],
                    secondProductId: productId,
                }),
            })

            this.productsForLink.splice(key, 1)
        },

        async getData() {
            let res;
            const response = await fetch('/mp_link/get-manual-binding', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    linkType: this.linkType,
                    productId: this.productId,
                }),
            })

            res = await response.json()

            if (res.success) {
                this.productLink = await res.data['productLink']
                this.productsForLink = await res.data['productsForLink']['data']
            }

            console.log(this.productsForLink)
        },

        getImg(imgList) {
            if (!imgList) {
                return "";
            }

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
        this.productId = urlParams.get('id');
        this.linkType = urlParams.get('linkType');

        this.getData();
    },
}

Vue.createApp(App).mount('#app')