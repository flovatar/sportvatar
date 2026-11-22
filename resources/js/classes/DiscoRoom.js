import {
    WebGLRenderer,
    Raycaster,
    Scene,
    PlaneBufferGeometry,
    PerspectiveCamera,
    Vector2,
    Vector3,
    Mesh,
    PlaneGeometry,
    Group,
    MeshBasicMaterial,
    DoubleSide,
    Color,
    Texture, LinearFilter
} from 'three';
import { DiscoEffectWave2 } from "./DiscoEffects";
import { getRandomColor, shuffleArray } from "./DiscoHelpers";
import DiscoTile from "./DiscoTile";
import DiscoWave from "./DiscoWave";

const DEGS_90 = Math.PI / 2;

const getRandomInt = (min, max) =>{
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

class DiscoRoom {
    constructor() {
        this.body = document.body;
        this.isVisible = false;
        this.selectedObject = false;
        this.idleTimer = 0;
        this.idleTimerEnd = getRandomInt(40, 80);
        this.waves = [];
        this.arrayWallTiles = [];
        this.arrayWallFront = [];
        this.arrayWallTop = [];
        this.arrayWallBottom = [];
        this.arrayWallLeft = [];
        this.arrayWallRight = [];
        this.bindMethods();
        this.initObjects();
        this.initCanvas();
        this.initWebGL();
        this.initAsssets();
        this.initEvents();
        this.onResize();
    }

    initObjects() {
        let sportvatars = window.sportvatars;
        shuffleArray(sportvatars);
        const sliceSportvatars = (sportvatars.length > 5) ? 5 : sportvatars.length;
        sportvatars = sportvatars.slice(0, sliceSportvatars);

        this.characterTemplates = [
            {
                size: 3.5,
                position: new Vector3(-4, -1.8, 7),
                reverse: false
            },
            {
                size: 3.4,
                position: new Vector3(-1.5, -1.8, 7.5),
                reverse: true
            },
            {
                size: 4,
                position: new Vector3(4, -1.8, 3),
                reverse: true
            },
            {
                size: 3.8,
                position: new Vector3(1, -1.8, 3.5),
                reverse: false
            },
            {
                size: 3.4,
                position: new Vector3(4.5, -1.8, 7.5),
                reverse: true
            },
        ];
        this.characters = [];

        this.avatars = sportvatars.map((item, idx) => {
            const character = this.characterTemplates[idx];
            character.currentAvatar = idx;
            this.characters.push(character);

            item.texture = { map: false }
            return item
        });
    }

    initAsssets() {
        const sportvatarPromises = [];

        for (let i = 0; i < this.avatars.length; i++) {
            sportvatarPromises.push(
                new Promise( (resolve, reject) => {
                    const img = new Image(3000, 3000);
                    const canvas = document.createElement("canvas");
                    canvas.width = 500;
                    canvas.height = 500;
                    const ctx = canvas.getContext("2d");
                    let svg = this.avatars[i].full_svg_no_bg;
                    svg = svg.replace('width="100%"', 'width="3000"').replace('height="100%"', 'height="3000"');
                    const base64 = window.btoa(unescape(encodeURIComponent(svg)));
                    const src = "data:image/svg+xml;base64," + base64;
                    img.src = src;

                    img.onload = () => {
                        ctx.drawImage(img, 0, 0, 500, 500);

                        const texture = new Texture(canvas);
                        texture.needsUpdate = true;

                        this.avatars[i].texture = {
                            map: texture,
                        };
                        resolve(true);
                        canvas.remove();
                        img.remove();
                    }
                })
            )
        }

        Promise.all(sportvatarPromises).then( result => {
            this.initScene();
        });
    }

    initScene() {
        this.groupWallFront = new Group();
        this.groupWallFront.name = "Wall Front";
        this.groupWallFront.position.set(-8, -4, 0);

        for(let j = 0; j < 8; j++) {
            for(let i = 0; i < 16; i++) {
                const tile = new DiscoTile(i, j, this.arrayWallFront, getRandomColor());
                this.arrayWallFront.push(tile);
                this.arrayWallTiles.push(tile);
                this.groupWallFront.add(tile.mesh);
            }
        }

        this.groupWallTop = new Group();
        this.groupWallTop.name = "Wall Top";
        this.groupWallTop.position.set(-8, 4.0, 0);
        this.groupWallTop.rotation.set(DEGS_90, 0, 0)

        for(let j = 0; j < 8; j++) {
            for(let i = 0; i < 16; i++) {
                const tile = new DiscoTile(i, j, this.arrayWallTop, getRandomColor());
                this.arrayWallTop.push(tile);
                this.arrayWallTiles.push(tile);
                this.groupWallTop.add(tile.mesh);
            }
        }

        this.groupWallBottom = new Group();
        this.groupWallBottom.name = "Wall Bottom";
        this.groupWallBottom.position.set(-8, -4.0, 8);
        this.groupWallBottom.rotation.set(-DEGS_90, 0, 0)

        for(let j = 0; j < 8; j++) {
            for(let i = 0; i < 16; i++) {
                const tile = new DiscoTile(i, j, this.arrayWallBottom, getRandomColor());
                this.arrayWallBottom.push(tile);
                this.arrayWallTiles.push(tile);
                this.groupWallBottom.add(tile.mesh);
            }
        }

        this.groupWallLeft = new Group();
        this.groupWallLeft.name = "Wall Left";
        this.groupWallLeft.position.set(-8, -4, 8);
        this.groupWallLeft.rotation.set(0, DEGS_90, 0)

        for(let j = 0; j < 8; j++) {
            for(let i = 0; i < 8; i++) {
                const tile = new DiscoTile(i, j, this.arrayWallLeft, getRandomColor());
                this.arrayWallLeft.push(tile);
                this.arrayWallTiles.push(tile);
                this.groupWallLeft.add(tile.mesh);
            }
        }

        this.groupWallRight = new Group();
        this.groupWallRight.name = "Wall Right";
        this.groupWallRight.position.set(8, -4, 0);
        this.groupWallRight.rotation.set(0, -DEGS_90, 0)

        for(let j = 0; j < 8; j++) {
            for(let i = 0; i < 8; i++) {
                const tile = new DiscoTile(i, j, this.arrayWallRight, getRandomColor());
                this.arrayWallRight.push(tile);
                this.arrayWallTiles.push(tile);
                this.groupWallRight.add(tile.mesh);
            }
        }

        this.materialBlack = new MeshBasicMaterial({
            side: DoubleSide,
            alphaTest: 0.5,
            color: new Color(0x111111)
        });

        this.wallFrontGeometry = new PlaneBufferGeometry(16, 8);
        this.wallFront = new Mesh(this.wallFrontGeometry, this.materialBlack);
        this.wallFront.position.set(0, 0, -0.002)

        this.wallBottomGeometry = new PlaneBufferGeometry(16, 8);
        this.wallBottom = new Mesh(this.wallBottomGeometry, this.materialBlack);
        this.wallBottom.position.set(0, -4.002, 4)
        this.wallBottom.rotation.set(DEGS_90, 0, 0)

        this.wallTopGeometry = new PlaneBufferGeometry(16, 8);
        this.wallTop = new Mesh(this.wallTopGeometry, this.materialBlack);
        this.wallTop.position.set(0, 4.002, 4)
        this.wallTop.rotation.set(DEGS_90, 0, 0)

        this.wallLeftGeometry = new PlaneBufferGeometry(8, 8);
        this.wallLeft = new Mesh(this.wallLeftGeometry, this.materialBlack);
        this.wallLeft.position.set(-8.002, 0, 4)
        this.wallLeft.rotation.set(0, DEGS_90, 0)

        this.wallRightGeometry = new PlaneBufferGeometry(8, 8);
        this.wallRight = new Mesh(this.wallRightGeometry, this.materialBlack);
        this.wallRight.position.set(8.002, 0, 4)
        this.wallRight.rotation.set(0, DEGS_90, 0)

        this.scene.add(this.wallFront);
        this.scene.add(this.wallBottom);
        this.scene.add(this.wallTop);
        this.scene.add(this.wallLeft);
        this.scene.add(this.wallRight);

        this.scene.add(this.groupWallFront);
        this.scene.add(this.groupWallTop);
        this.scene.add(this.groupWallBottom);
        this.scene.add(this.groupWallLeft);
        this.scene.add(this.groupWallRight);

        for(let i = 0; i < this.characters.length; i++) {
            this.characters[i].avatarMaterial = new MeshBasicMaterial({
                side: DoubleSide,
                map: this.avatars[this.characters[i].currentAvatar].texture.map,
                transparent: true,
            });
            this.characters[i].avatarMaterial.map.minFilter = LinearFilter;
            this.characters[i].avatarGeometry = new PlaneGeometry(this.characters[i].size, this.characters[i].size, 1, 1);
            this.characters[i].avatarMesh = new Mesh(this.characters[i].avatarGeometry, this.characters[i].avatarMaterial);
            this.characters[i].character = new Group();
            this.characters[i].character.position.set(this.characters[i].position.x, this.characters[i].position.y, this.characters[i].position.z);
            this.characters[i].character.renderOrder = this.characters[i].renderOrder;
            this.characters[i].character.add(this.characters[i].avatarMesh);
            if(this.characters[i].reverse) {
                this.characters[i].character.scale.x = -1;
            }
            this.scene.add(this.characters[i].character);
        }

        this.render();
    }

    initCanvas() {
        this.winsize = { width: window.innerWidth, height: window.innerHeight };
        this.wrapper = {};
        this.wrapper.el = document.getElementById('scene-wrapper--disco');
        this.wrapper.width = this.wrapper.el.offsetWidth;
        this.wrapper.height = this.wrapper.el.offsetHeight;
        this.wrapper.widthHalf = this.wrapper.width / 2;
        this.wrapper.heightHalf = this.wrapper.height / 2;
        this.canvas = {};
        this.canvas.el = document.getElementById('scene-disco-room');
        this.canvas.width = this.canvas.el.offsetWidth;
        this.canvas.height = this.canvas.el.offsetHeight;

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => this.isVisible = entry.intersectionRatio > 0);
        });
        this.observer.observe(this.wrapper.el);
    }

    initWebGL() {
        this.scene = new Scene();

        const nearDist = 1;
        const farDist = 200.00;
        this.camera = new PerspectiveCamera(
            70,
            this.canvas.width / this.canvas.height,
            nearDist,
            farDist
        );


        this.camera.aspect = this.wrapper.width / this.wrapper.height;
        this.camera.position.set(0, 0, 13);

        this.renderer = new WebGLRenderer({
            alpha: false,
            antialias: true,
            canvas: this.canvas.el
        });

        this.raycaster = new Raycaster();

        this.renderer.setPixelRatio(1.5)
        this.renderer.setSize(this.wrapper.width, this.wrapper.height);
        this.renderer.setClearColor(0x000000, 0);
    }

    onClick(event) {
        const mouse = new Vector2();
        mouse.x = ( event.clientX / window.innerWidth ) * 2 - 1;
        mouse.y = - ( event.clientY / window.innerHeight ) * 2 + 1;
        this.raycaster.setFromCamera(mouse, this.camera);
        const intersects = this.raycaster.intersectObjects(this.arrayWallTiles.map(tile => { return tile.mesh }), true); //array
        if (intersects.length > 0) {
            const selectedObject = intersects[0];
            if(typeof selectedObject.object !== 'undefined'
                && typeof selectedObject.object.userData !== 'undefined'
                && typeof selectedObject.object.userData.tile !== 'undefined' ) {
                const wave = new DiscoWave(selectedObject.object.userData.tile.x,
                    selectedObject.object.userData.tile.y,
                    selectedObject.object.userData.tile.wall,
                    DiscoEffectWave2);
                this.waves.push(wave);
            }
        }
    }

    render() {
        if(this.isVisible) {
            if(typeof this.arrayWallFront !== 'undefined' && this.arrayWallFront.length) {
                const tileIdx = Math.floor(Math.random() * this.arrayWallFront.length);
                this.arrayWallFront[tileIdx].updateColor(getRandomColor());
            }
            if(typeof this.arrayWallLeft !== 'undefined' && this.arrayWallLeft.length) {
                const tileIdx = Math.floor(Math.random() * this.arrayWallLeft.length);
                this.arrayWallLeft[tileIdx].updateColor(getRandomColor());
            }
            if(typeof this.arrayWallRight !== 'undefined' && this.arrayWallRight.length) {
                const tileIdx = Math.floor(Math.random() * this.arrayWallRight.length);
                this.arrayWallRight[tileIdx].updateColor(getRandomColor());
            }
            if(typeof this.arrayWallTop !== 'undefined' && this.arrayWallTop.length) {
                const tileIdx = Math.floor(Math.random() * this.arrayWallTop.length);
                this.arrayWallTop[tileIdx].updateColor(getRandomColor());
            }
            if(typeof this.arrayWallBottom !== 'undefined' && this.arrayWallBottom.length) {
                const tileIdx = Math.floor(Math.random() * this.arrayWallBottom.length);
                this.arrayWallBottom[tileIdx].updateColor(getRandomColor());
            }

            if(this.waves.length) {
                this.waves.forEach((wave, index) => {
                    if(! wave.action()) {
                        this.waves.splice(index, 1);
                    }
                });
                this.idleTimer = 0;
            } else {
                this.idleTimer += 1;
            }

            if(typeof this.arrayWallTiles !== 'undefined' && this.arrayWallTiles.length) {
                if(this.idleTimer === this.idleTimerEnd) {
                    this.idleTimerEnd = getRandomInt(30, 50);
                    const randomTile = this.arrayWallTiles[getRandomInt(0, this.arrayWallTiles.length - 1)];

                    const wave = new DiscoWave(randomTile.x,
                        randomTile.y,
                        randomTile.wall,
                        DiscoEffectWave2);
                    this.waves.push(wave);
                }
            }

        }
        this.renderer.render(this.scene, this.camera);
        window.requestAnimationFrame(this.render);
    }

    onResize () {
        this.wrapper.width = this.wrapper.el.offsetWidth;
        this.wrapper.height = this.wrapper.el.offsetHeight;
        this.wrapper.widthHalf = this.wrapper.width / 2;
        this.wrapper.heightHalf = this.wrapper.height / 2;
        this.wrapper.rect = this.wrapper.el.getBoundingClientRect();
        this.canvas.rect = this.canvas.el.getBoundingClientRect();
        this.canvas.widthHalf = this.canvas.width / 2;
        this.canvas.heightHalf = this.canvas.height / 2;
        this.winsize = {width: window.innerWidth, height: window.innerHeight};

        this.camera.aspect = this.wrapper.width / this.wrapper.height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize( this.wrapper.width, this.wrapper.height );
        this.render();
    }

    onMouseMove (e) {
        if (!this.enableUI) return false;

        let clientX = e.clientX,
            clientY = e.clientY;

        let delta = clientX - this.canvas.rect.left - this.canvas.widthHalf;
        let deltaY = clientY - this.canvas.rect.top - this.canvas.heightHalf;
        let deltaPercent = delta / this.canvas.widthHalf * 100;
        let deltaPercentY = deltaY / this.canvas.heightHalf * 100;
    }

    initEvents() {
        window.addEventListener('resize', this.onResize, {passive: true});
        this.canvas.el.addEventListener('mousemove', e => {
            this.onMouseMove(e)
        } );

        this.renderer.domElement.addEventListener("click", this.onClick, true);
    }

    bindMethods() {
        ['onResize', 'render', 'onMouseMove', 'onClick']
            .forEach((fn) => this[fn] = this[fn].bind(this))
    }

}

export default DiscoRoom
