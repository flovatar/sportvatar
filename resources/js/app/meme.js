import Alpine from "alpinejs";
import interact from "interactjs";
import html2canvas from "html2canvas";

Alpine.data('memeData', (memeTemplates, memes) => ({
    memeTemplates: memeTemplates,
    memes: memes.data,
    extraImages: [
        'pixel-eyeglasses',
        'arrow-down',
        'arrow-up',
        'cap',
        'comic-bubble',
        'doge-steroids',
        'doge',
        'elon',
        'goldchain',
        'laser-eyes',
        'troll',
        'logo-black',
        'logo-horizontal-black',
        'logo-horizontal-pink',
        'logo-pink',
        'logo-rounded-black',
        'logo-rounded-pink',
        'logo-rounded-white',
        'logo-white',
        'ballerina',
        'brains',
        'flow',
        'dapperlabs-full',
        'dapperlabs-small',
        'Pieland-Doughbots',
        //'Pieland-Expieriment',
        //'Pieland-PieBorg',
        //'Pieland-Pielander',
        //'Pieland-Pierate',
        'Pieland-Piesces',
        //'Pieland-Pieson',
        //'Pieland-Vampier',
        'Pieland-Zompie',
        'piggo-1',
        'piggo-2',
        'piggo-3',
        'fright-club',
        'fright-club-eugenemask',
        'fright-club-rabbyte',
        'flunks-1',
        'flunks-2',
        'flunks-3',
        'ice-playerz-1',
        'ice-playerz-2',
        'ice-playerz-3',
        'someplace-1',
        'someplace-2',
        'someplace-3',
        'goobz-1',
        'goobz-2',
        'goobz-3',
        'superfollower',
        'avatarstar'
    ],
    sportvatars: [],
    sportvatarSales: [],
    memeTexts: [],
    memeSportvatars: [],
    memeImages: [],
    curMeme: null,
    maxTemplates: 20,
    uploadedMemeImage: null,
    uploadedStickerImage: null,
    init() {
        let self = this;
        this.randomizeMemeTemplate();
        this.addText();
        this.addText();
        this.addText();

        this.$watch('maxTemplates', () => {
            if(window.memeFrameSlider) {
                window.memeFrameSlider.reload();
            }
        });

        const reader = new FileReader();
        const fileInput = document.getElementById("memeFileUpload");
        reader.onload = e => {
            self.uploadedMemeImage =  e.target.result;
        };
        fileInput.addEventListener('change', e => {
            const f = e.target.files[0];
            reader.readAsDataURL(f);
        })



        const reader2 = new FileReader();
        const fileInput2 = document.getElementById("memeStickerUpload");
        reader2.onload = e => {
            self.uploadedStickerImage =  e.target.result;
        };
        fileInput2.addEventListener('change', e => {
            const f = e.target.files[0];
            reader2.readAsDataURL(f);
        })
    },
    showMemeC(){
        console.log(this.memeTexts);
        console.log(this.memeImages);
        if(this.memeTexts.length > 0 && this.memeImages.length > 0){
            let tempStr = ''+this.memeTexts[0].text;
            if(tempStr.replace(/in dust we trust/i, '') != tempStr && this.memeImages[0].image == 'elon'){
                return true;
            }
        }
        return false;
    },
    memeC(){
        return this.showMemeC() ? 'Well done! Now scan and search for this ID, and you’ll know what the next clue will be: 19877b8e38d2a02dbc3cb2f2d37c3d30b7beae0ebc182054d8dd825bfffd0abf' : '';
    },
    filteredMemeTemplates(){
        let self = this;

        return _.filter(this.memeTemplates, (item, key) => {
            return key < self.maxTemplates;
        });
    },
    resetMemes(){
        this.memeTexts = [];
        this.memeSportvatars = [];
        this.memeImages = [];
        this.addText();
        this.addText();
        this.addText();
    },
    randomizeMemeTemplate(){
        this.curMeme = this.memeTemplates[_.random(0, this.memeTemplates.length-1)];
    },
    loadSportvatarsAndSales(data){
        this.sportvatars = data.sportvatars;
        this.sportvatarSales = data.sportvatarSales;
    },
    getSportvatarsAndSales(){
        if(this.address()){
            window.getSportvatarsAndSales(this.address());
        }
    },
    addSportvatar(sportvatar, event){
        let self = this;
        axios.get('/api/image/nobg/'+sportvatar.id).then(function (response) {
            let svg = response.data;
            let tempSportvatar = _.clone(sportvatar);
            tempSportvatar.list_id = (self.memeSportvatars.length+1)+'sportvatar'+sportvatar.id;
            tempSportvatar.svg = svg;
            tempSportvatar.width = '100%';
            //tempSportvatar.imageDataUrl = this.getResizedSvg(svg, '100%');
            self.memeSportvatars.push(tempSportvatar);
        });
    },
    getResizedSvg(svg, width){
        let newSvg = svg.replace('width="100%"', 'width="'+width+'"').replace('height="100%"', 'height="'+width+'"');
        const base64 = window.btoa(unescape(encodeURIComponent(newSvg)));
        return "data:image/svg+xml;base64," + base64;
    },
    addExtraImage(image){
        this.memeImages.push({id: (this.memeImages.length+1)+image, image: image});
    },
    addText(){
        this.memeTexts.push({id: this.memeTexts.length+1, text: '', size: 50});
    },
    refreshInteractive(){
        let self = this;
        // target elements with the "draggable" class
        interact('.meme-draggable').draggable(this.draggableOptions).resizable(this.resizableTextOptions);
        interact('.meme-resizable').draggable(this.draggableOptions).resizable(this.resizableOptions);

    },
    gesturableOptions: {
        onmove: function (event) {
            //angle += event.da

            event.target.style.webkitTransform =
                event.target.style.transform =
                    'rotate(' + event.da + 'deg)'

        },
    },
    draggableOptions: {
        // enable inertial throwing
        inertia: true,
        // enable autoScroll
        autoScroll: true,

        listeners: {
            // call this function on every dragmove event
            move: function (event) {
                var target = event.target
                // keep the dragged position in the data-x/data-y attributes
                var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx
                var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy

                // translate the element
                target.style.transform = 'translate(' + x + 'px, ' + y + 'px)'

                // update the posiion attributes
                target.setAttribute('data-x', x)
                target.setAttribute('data-y', y)
            },

            // call this function on every dragend event
            end (event) {
                //console.log(event.pageX - event.x0, event.pageY - event.y0);
            }
        }
    },
    resizableOptions: {
        edges: { top: false, left: false, bottom: true, right: true },
        listeners: {
            move: function (event) {
                let { x, y } = event.target.dataset;

                x = (parseFloat(x) || 0) + event.deltaRect.left;
                y = (parseFloat(y) || 0) + event.deltaRect.top;

                Object.assign(event.target.style, {
                    width: `${event.rect.width}px`,
                    height: `${event.rect.height}px`,
                    transform: `translate(${x}px, ${y}px)`
                });

                Object.assign(event.target.dataset, { x, y });
            }
        },
        modifiers: [
            interact.modifiers.aspectRatio({
                // make sure the delta is equal
                ratio: 'preserve',
                // also restrict the size by nesting another modifier
                modifiers: [
                    interact.modifiers.restrictSize({ max: 'parent' }),
                ],
            })
        ],
    },
    resizableTextOptions: {
        edges: { top: false, left: false, bottom: true, right: true },
        listeners: {
            move: function (event) {
                let { x, y } = event.target.dataset;

                x = (parseFloat(x) || 0) + event.deltaRect.left;
                y = (parseFloat(y) || 0) + event.deltaRect.top;

                Object.assign(event.target.style, {
                    'font-size': `${event.rect.height}px`,
                    transform: `translate(${x}px, ${y}px)`
                });

                Object.assign(event.target.dataset, { x, y });
            }
        },
        modifiers: [
            interact.modifiers.aspectRatio({
                // make sure the delta is equal
                //ratio: 'preserve',
                // also restrict the size by nesting another modifier
                modifiers: [
                    interact.modifiers.restrictSize({ max: 'parent' }),
                ],
            })
        ],
    },
    downloadMeme(){
        let self = this;
        let newArray = [];
        _.each(document.querySelectorAll('.meme-sportvatar-box'), (item, key) => {
            self.memeSportvatars[key].width = item.style.width.replace('px','');
            let tempObj = _.clone(self.memeSportvatars[key]);
            tempObj.width = item.style.width.replace('px','');
            newArray.push(tempObj)
        });
        //self.memeSportvatars = newArray;

        setTimeout(function(){
            html2canvas(document.getElementById('meme-output')).then(function(canvas) {
                //console.log(canvas);
                //console.log('html2canvas');
                document.body.appendChild(canvas);

                let imgURI = canvas
                    .toDataURL("image/png")
                    .replace("image/png", "image/octet-stream");

                const evt = new MouseEvent("click", {
                    view: window,
                    bubbles: false,
                    cancelable: true
                });
                const a = document.createElement("a");
                a.setAttribute("download", `sportvatar-${ (new Date().getTime()).toString(10) }.png`);
                a.setAttribute("href", imgURI);
                a.setAttribute("target", '_blank');
                a.dispatchEvent(evt);
                canvas.remove();
                a.remove();
            });
        },100);
    }
}));


window.initMeme = function(dispatch){
    window.dispatch = dispatch;
}
