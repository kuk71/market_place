const App = {
    data() {
        return {
            mpLinks: [],
            color: ["table-success", "table-info",],
            lastFirstId: 0,
        }
    },
    methods: {
        setColor() {
            let lastFistId = 0;
            let lastColorId = 0;

            for (let i = 0; i < this.mpLinks.length; i++) {
                this.mpLinks[i]['colorId'] = lastColorId

                if (lastFistId != this.mpLinks[i]['firstId']) {

                    lastFistId = this.mpLinks[i]['firstId']

                    if (lastColorId == 0) {
                        this.mpLinks[i]['colorId'] = 1
                    } else {
                        this.mpLinks[i]['colorId'] = 0
                    }

                    lastColorId = this.mpLinks[i]['colorId']
                }
            }
        },

        delLink(id, linkId) {
            this.delLinkRequest(linkId)
            this.mpLinks.splice(id, 1)
            this.setColor()
        },

        getImg(imgList) {
            const imgs = JSON.parse(imgList)

            let imgRender = ""
            for (let i = 0; i < imgs.length; i++) {
                imgRender = imgRender + '<img src="' + imgs[i] + '" style="margin: 0 0 0 10px; height: 100px"> '
            }

            return imgRender;
        },


        async getLink() {
            let mp;
            const response = await fetch('/mp-link/get', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    linkType: 1
                }),
            })

            mp = await response.json()

            this.mpLinks = await mp.data

            await this.setColor()

            console.log(this.mpLinks)
        },

        async getLinkSecond() {
            let mp;
            const response = await fetch('/mp-link/get-second', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    linkType: 1
                }),
            })

            mp = await response.json()
            this.mpLinks = await mp.data
            await this.setColor()
            // await console.log(mp)
        },

        async delLinkRequest(linkId) {
            const response = await fetch('/mp-link/del-link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    linkId: linkId
                }),
            })
        },
    },

    mounted() {
        this.getLink()
    },
}

Vue.createApp(App).mount('#app')