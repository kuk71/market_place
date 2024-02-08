const App = {
    data() {
        return {
            topic: "Ручное связывание ",
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
            window.location.href = 'manual-ms?linkType=' + this.linkType + '&mpId=' + this.productLink['mp_id']
        },

        changeShowAll() {
            this.showAllButton = "Искать только в не связанных товарах"

            if (this.showAll) {
                this.showAllButton = "Искать среди всех товаров"
            }

            this.showAll = !this.showAll
        },

        async pairLink(key, productId) {
            const response = await fetch('/mp_link/link-products-ms', {
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
            const response = await fetch('/mp_link/get-manual-binding-ms', {
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

                this.topic = this.topic + this.productLink.mp_name + " / " + this.productsForLink[0]['mp_name']
            }


            console.log(this.productLink.mp_name)
            console.log(this.productsForLink[0])
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