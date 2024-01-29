const App = {
    data() {
        return {
            mpFirstId: null,
            mpSecondId: null,
            mpFirstName: null,
            mpSecondName: null,
            linkType: null,
            mpLinks: [],
            color: ["table-success", "table-info",],
            lastFirstId: 0,
            topic: "",
        }
    },
    methods: {
        hrefToManual(num) {
            window.location.href = '/mp_link/manual?linkType=' + this.linkType + '&mpId=' + this.mpLinks[0][num + 'MpId']
        },

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

            console.log(this.mpFirstName, this.mpSecondName)
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


        async getLink(linkNum = 1, delLink = false) {
            let mp;

            this.mpLinks = []

            this.topic = "Первый уровень соединения"

            const response = await fetch('/mp_link/get-link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    linkType: this.linkType,
                    linkNum: linkNum,
                    delLink: delLink
                }),
            })

            mp = await response.json()

            this.mpLinks = await mp.data

            await this.setColor()

            this.mpFirstName = this.mpLinks[0]['firstMpName']
            this.mpSecondName = this.mpLinks[0]['secondMpName']
            this.mpFirstId = this.mpLinks[0]['firstMpId']
            this.mpSecondId = this.mpLinks[0]['secondMpId']

            console.log(this.mpLinks)
        },

        async delLinkRequest(linkId) {
            const response = await fetch('/mp_link/del-link', {
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
        const urlParams = new URLSearchParams(window.location.search);
        this.linkType = urlParams.get('linkType');

        this.getLink()
    },
}

Vue.createApp(App).mount('#app')