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
import { OrbitControls } from './OrbitControls.js';
import { getRandomColor, shuffleArray } from "./DiscoHelpers";

const DEGS_90 = Math.PI / 2;

const getRandomInt = (min, max) =>{
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

class FlovatestScene {
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
        this.onResize();
    }

    initObjects() {
        let sportvatars = window.sportvatars;

        this.avatars = sportvatars.map((item, idx) => {
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
        this.characters = [];
        let x = 0;
        let y = 0;

        for(let i = 0; i < this.avatars.length; i++) {
            const character = [];

            character.avatarMaterial = new MeshBasicMaterial({
                side: DoubleSide,
                map: this.avatars[i].texture.map,
                transparent: true,
            });
            character.avatarMaterial.map.minFilter = LinearFilter;
            character.avatarGeometry = new PlaneGeometry(5, 5, 1, 1);
            character.avatarMesh = new Mesh(character.avatarGeometry, character.avatarMaterial);
            character.character = new Group();
            character.character.position.set((x-5) * 5, y * 5 - 10, 0);
            character.character.add(character.avatarMesh);

            this.scene.add(character.character);
            this.characters.push(character);
            x += 1;
            if(x > 24) {
                x = 0;
                y +=1;
            }
        }

        this.render();
    }

    initCanvas() {
        this.winsize = { width: window.innerWidth, height: window.innerHeight };
        this.wrapper = {};
        this.wrapper.el = document.getElementById('flovatest-wrapper');
        this.wrapper.width = this.wrapper.el.offsetWidth;
        this.wrapper.height = this.wrapper.el.offsetHeight;
        this.wrapper.widthHalf = this.wrapper.width / 2;
        this.wrapper.heightHalf = this.wrapper.height / 2;
        this.canvas = {};
        this.canvas.el = document.getElementById('flovatest-scene');
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

        this.scene.background = new Color( 0xffffff );


        this.camera.aspect = this.wrapper.width / this.wrapper.height;
        this.camera.position.set(0, 0, 13);

        this.renderer = new WebGLRenderer({
            alpha: false,
            antialias: true,
            canvas: this.canvas.el
        });

        this.controls = new OrbitControls( this.camera, this.renderer.domElement );
        this.controls.update();
        this.renderer.setPixelRatio(1.5)
        this.renderer.setSize(this.wrapper.width, this.wrapper.height);
        this.renderer.setClearColor(0xffffff, 0);
    }

    render() {
        this.renderer.render(this.scene, this.camera);
        this.controls.update();
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

    bindMethods() {
        ['onResize', 'render']
            .forEach((fn) => this[fn] = this[fn].bind(this))
    }

}

export default FlovatestScene
