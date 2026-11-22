import { gsap, Elastic, Power2, Back , Power0} from "gsap/all";
import {
    WebGLRenderer,
    BufferGeometry,
    LinearFilter,
    Texture,
    Scene,
    ShapePath,
    PlaneBufferGeometry,
    SphereGeometry,
    PerspectiveCamera,
    Vector2,
    Vector3,
    Vector4,
    Box3,
    Mesh,
    Matrix4,
    ShaderMaterial,
    LoadingManager,
    TextureLoader,
    PlaneGeometry,
    Group,
    MeshBasicMaterial,
    DoubleSide,
    Color,
    ShapeBufferGeometry,
    LineBasicMaterial,
    EllipseCurve,
    CurvePath,
    Path,
    CircleGeometry
} from 'three';
import SVGLoader from './SVGLoader.js';
import shaderRainbowVertex from "../shaders/rainbow_vertex.glsl"
import shaderRainbowFragment from "../shaders/rainbow_fragment.glsl"
import shaderSkyVertex from "../shaders/sky_vertex.glsl"
import shaderSkyFragment from "../shaders/sky_fragment.glsl"
import {OrbitControls} from "./OrbitControls";

ShapePath.prototype.absarc = function( aX, aY, aRadius, aStartAngle, aEndAngle, aClockwise ) {
    this.currentPath.absarc(aX, aY, aRadius, aStartAngle, aEndAngle, aClockwise);
};
ShapePath.prototype.absellipse = function( aX, aY, xRadius, yRadius, aStartAngle, aEndAngle, aClockwise ) {
    this.currentPath.absellipse( aX, aY, xRadius, yRadius, aStartAngle, aEndAngle, aClockwise );
};
var DEGS_TO_RADS = Math.PI / 180,
    UNIT_SIZE = 100;
var DIGIT_0 = 48,
    DIGIT_9 = 57,
    COMMA = 44,
    SPACE = 32,
    PERIOD = 46,
    MINUS = 45;

const transformSVGPath = (pathStr) => {
    var path = new ShapePath();

    var idx = 1,
        len = pathStr.length,
        activeCmd,
        x = 0,
        y = 0,
        nx = 0,
        ny = 0,
        firstX = null,
        firstY = null,
        x1 = 0,
        x2 = 0,
        y1 = 0,
        y2 = 0,
        rx = 0,
        ry = 0,
        xar = 0,
        laf = 0,
        sf = 0,
        cx,
        cy;

    function eatNum() {
        var sidx,
            c,
            isFloat = false,
            s;

        // eat delims

        while (idx < len) {
            c = pathStr.charCodeAt(idx);

            if (c !== COMMA && c !== SPACE) break;

            idx++;
        }

        if (c === MINUS) {
            sidx = idx++;
        } else {
            sidx = idx;
        }

        // eat number

        while (idx < len) {
            c = pathStr.charCodeAt(idx);

            if (DIGIT_0 <= c && c <= DIGIT_9) {
                idx++;
                continue;
            } else if (c === PERIOD) {
                if (!isFloat) {
                    idx++;
                    isFloat = true;
                    continue;
                }
            }

            s = pathStr.substring(sidx, idx);
            return isFloat ? parseFloat(s) : parseInt(s);
        }

        s = pathStr.substring(sidx);
        return isFloat ? parseFloat(s) : parseInt(s);
    }

    function nextIsNum() {
        var c;

        // do permanently eat any delims...

        while (idx < len) {
            c = pathStr.charCodeAt(idx);

            if (c !== COMMA && c !== SPACE) break;

            idx++;
        }

        c = pathStr.charCodeAt(idx);
        return c === MINUS || c === PERIOD || (DIGIT_0 <= c && c <= DIGIT_9);
    }

    var canRepeat;
    activeCmd = pathStr[0];

    while (idx <= len) {
        canRepeat = true;

        switch (activeCmd) {
            // moveto commands, become lineto's if repeated
            case "M":
                x = eatNum();
                y = eatNum();
                path.moveTo(x, y);
                activeCmd = "L";
                firstX = x;
                firstY = y;
                break;

            case "m":
                x += eatNum();
                y += eatNum();
                path.moveTo(x, y);
                activeCmd = "l";
                firstX = x;
                firstY = y;
                break;

            case "Z":
            case "z":
                canRepeat = false;
                if (x !== firstX || y !== firstY) path.lineTo(firstX, firstY);
                break;

            // - lines!
            case "L":
            case "H":
            case "V":
                nx = activeCmd === "V" ? x : eatNum();
                ny = activeCmd === "H" ? y : eatNum();
                path.lineTo(nx, ny);
                x = nx;
                y = ny;
                break;

            case "l":
            case "h":
            case "v":
                nx = activeCmd === "v" ? x : x + eatNum();
                ny = activeCmd === "h" ? y : y + eatNum();
                path.lineTo(nx, ny);
                x = nx;
                y = ny;
                break;

            // - cubic bezier
            case "C":
                x1 = eatNum();
                y1 = eatNum();

            case "S":
                if (activeCmd === "S") {
                    x1 = 2 * x - x2;
                    y1 = 2 * y - y2;
                }

                x2 = eatNum();
                y2 = eatNum();
                nx = eatNum();
                ny = eatNum();
                path.bezierCurveTo(x1, y1, x2, y2, nx, ny);
                x = nx;
                y = ny;
                break;

            case "c":
                x1 = x + eatNum();
                y1 = y + eatNum();

            case "s":
                if (activeCmd === "s") {
                    x1 = 2 * x - x2;
                    y1 = 2 * y - y2;
                }

                x2 = x + eatNum();
                y2 = y + eatNum();
                nx = x + eatNum();
                ny = y + eatNum();
                path.bezierCurveTo(x1, y1, x2, y2, nx, ny);
                x = nx;
                y = ny;
                break;

            // - quadratic bezier
            case "Q":
                x1 = eatNum();
                y1 = eatNum();

            case "T":
                if (activeCmd === "T") {
                    x1 = 2 * x - x1;
                    y1 = 2 * y - y1;
                }
                nx = eatNum();
                ny = eatNum();
                path.quadraticCurveTo(x1, y1, nx, ny);
                x = nx;
                y = ny;
                break;

            case "q":
                x1 = x + eatNum();
                y1 = y + eatNum();

            case "t":
                if (activeCmd === "t") {
                    x1 = 2 * x - x1;
                    y1 = 2 * y - y1;
                }

                nx = x + eatNum();
                ny = y + eatNum();
                path.quadraticCurveTo(x1, y1, nx, ny);
                x = nx;
                y = ny;
                break;

            // - elliptical arc
            case "A":
            case "a":
                rx = eatNum();
                ry = eatNum();
                xar = eatNum() * DEGS_TO_RADS;
                laf = eatNum(); //large arc flag
                sf = eatNum(); //sweep flag
                nx = eatNum();
                ny = eatNum();

                if (activeCmd == "a") {
                    // relative
                    nx += x;
                    ny += y;
                }

                if(rx != 0 && ry != 0) {

                    /*console.debug(
                        "Read arc params: rx=" + rx + ", ry=" + ry + ", xar=" + xar + ", laf=" + laf + ", sf=" + sf + ", nx=" + nx + ", ny=" + ny
                    );*/

                    //might need to bring this back if absellipse doesn't work
                    //if (rx !== ry)
                    //    console.warn("Forcing elliptical arc to be a circular one :(",  rx, ry);

                    // SVG implementation notes does all the math for us! woo!
                    // http://www.w3.org/TR/SVG/implnote.html#ArcImplementationNotes
                    // step1, using x1 as x1'
                    x1 = Math.cos(xar) * (x - nx) / 2 + Math.sin(xar) * (y - ny) / 2;
                    y1 = -Math.sin(xar) * (x - nx) / 2 + Math.cos(xar) * (y - ny) / 2;
                    // step 2, using x2 as cx'
                    // console.debug( "TMP x1=" + x1 + ", y1=" + y1 + ", (rx*rx * y1*y1 + ry*ry * x1*x1)=" + (rx * rx * y1 * y1 + ry * ry * x1 * x1) + ", (rx*rx * ry*ry - rx*rx * y1*y1 - ry*ry * x1*x1)=" + (rx * rx * ry * ry - rx * rx * y1 * y1 - ry * ry * x1 * x1));
                    var norm = Math.sqrt(
                        Math.abs(
                            (rx * rx * ry * ry - rx * rx * y1 * y1 - ry * ry * x1 * x1) /
                            (rx * rx * y1 * y1 + ry * ry * x1 * x1)
                        )
                    );
                    if (laf === sf) norm = -norm;
                    x2 = norm * rx * y1 / ry;
                    y2 = norm * -ry * x1 / rx;
                    // console.debug("TMP norm=" + norm + ", x2=" + x2 + ", y2=" + y2);
                    // step 3
                    cx = Math.cos(xar) * x2 - Math.sin(xar) * y2 + (x + nx) / 2;
                    cy = Math.sin(xar) * x2 + Math.cos(xar) * y2 + (y + ny) / 2;
                    // console.debug("TMP cx=" + cx + ", cy=" + cy);

                    var u = new Vector2(1, 0),
                        v = new Vector2((x1 - x2) / rx, (y1 - y2) / ry);
                    var startAng = Math.acos(u.dot(v) / u.length() / v.length());
                    if (u.x * v.y - u.y * v.x < 0) startAng = -startAng;

                    // we can reuse 'v' from start angle as our 'u' for delta angle
                    u.x = (-x1 - x2) / rx;
                    u.y = (-y1 - y2) / ry;

                    var deltaAng = Math.acos(v.dot(u) / v.length() / u.length());
                    // This normalization ends up making our curves fail to triangulate...
                    if (u.x * v.y - u.y * v.x < 0) deltaAng = -deltaAng;
                    if (!sf && deltaAng > 0) deltaAng -= Math.PI * 2;
                    if (sf && deltaAng < 0) deltaAng += Math.PI * 2;

                    /*console.debug(
                        "Building arc from values: cx=" + cx + ", cy=" + cy + ", startAng=" + startAng + ", deltaAng=" + deltaAng + ", endAng=" + (startAng + deltaAng) + ", sweepFlag=" + sf );*/
                    // path.absarc(cx, cy, rx, startAng, startAng + deltaAng, sf);
                    path.absellipse(cx, cy, rx, ry, startAng, startAng + deltaAng, sf);
                } else {
                    path.lineTo(nx, ny);
                }
                x = nx;
                y = ny;
                break;

            default:
                throw new Error("Wrong path command: " + activeCmd);
        }

        // just reissue the command

        if (canRepeat && nextIsNum()) continue;

        activeCmd = pathStr[idx++];
    }

    return path;
}

const MathUtils = {
    // map number x from range [a, b] to [c, d]
    map: (x, a, b, c, d) => (x - a) * (d - c) / (b - a) + c,
    // linear interpolation
    lerp: (a, b, n) => (1 - n) * a + n * b
};

class Layer {
    constructor(x, y, w, h, sprite, z) {
        this.type = 'raster';
        this.x = x;
        this.y = y;
        this.width = w;
        this.height = h;
        this.sprite = sprite;
        this.z = z;
    }
}

class VectorLayer {
    constructor(name, x, y, w, h, object, z, exclude) {
        this.type = 'vector';
        this.x = x;
        this.y = y;
        this.width = w;
        this.height = h;
        this.object = object;
        this.z = z;
        this.name = name;
        this.exclude = exclude || false;
    }
}

class HeroSceneShowroom {

    constructor() {
        this.body = document.body;
        this.latestKnownScrollY = window.pageYOffset || document.documentElement.scrollTop;
        this.isVisible = true;
        this.scrollUpdate = false;
        this.bindMethods();
        this.initObjects();
        this.initCanvas();
        this.initWebGL();
        this.initAsssets();
        this.initParallaxScene();
        this.initEvents();
        this.onScroll();
        this.onResize();
    }

    initParallaxScene() {
        this.parallaxOverflow = 50;
        this.parallaxElems = [];
    }

    initObjects() {

        this.enableUI = false;
        this.avatars = window.sportvatars.data.map((item) => {
            item.texture = { map: false }
            return item
        });

        this.textures = {
            // avatar1 : { file: 'avatar1.png' },
            // avatar2 : { file: 'avatar2.png' },
        };

        this.SVGTextures = {
            sun: { scale: 1, x: 580, y: 380, z: 7, width: 128, height: 128, txt_width: 128, txt_height: 128, file : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128.12 128.12"><defs><style>.a{fill:#5f3faf;stroke-width:2px;}.a,.c{stroke:#292438;stroke-miterlimit:10;}.b{fill:#d4caff;opacity:0.35;isolation:isolate;}.c{fill:none;stroke-width:4px;}</style></defs><circle class="a" cx="64.06" cy="64.06" r="62.06"/><path class="b" d="M64.06,126.12a61.9,61.9,0,0,0,9.81-.78,62.06,62.06,0,0,1,0-122.56,62.06,62.06,0,1,0-9.81,123.34Z"/><circle class="c" cx="64.06" cy="64.06" r="62.06"/></svg>', texture: {map: false}},
        };

        for(let i in this.SVGTextures) {
            const img = document.createElement("img");
            img.setAttribute("width", this.SVGTextures[i].txt_width);
            img.setAttribute("height", this.SVGTextures[i].txt_height);
            img.setAttribute("src", "data:image/svg+xml;utf8," + encodeURIComponent(this.SVGTextures[i].file) );
            const texture = new Texture(img);
            texture.needsUpdate = true;

            this.SVGTextures[i].texture = {
                map: texture
            }

            /*const canvas = document.createElement("canvas");
            canvas.width = this.SVGTextures[i].txt_width;
            canvas.height = this.SVGTextures[i].txt_height;
            const ctx = canvas.getContext("2d");

            img.onload = () => {
                ctx.drawImage(img, 0, 0);

                var texture = new Texture(canvas);
                texture.needsUpdate = true;
                this.SVGTextures[i].texture = {
                    map: texture
                }
            };*/
        }

        this.layers = {
            // top_green_star: new Layer(-100, 0, 182, 240, this.textures.green_star, 25),
            // star: new VectorLayer(0, 0, 200, 200, this.svgFiles.star, 5),
            // bolt: new VectorLayer(0, 0, 200, 200, this.svgFiles.bolt, 5),
            // heroGround: new VectorLayer('ground', 0, 79, 473, 22, this.svgFiles.heroGround, 10, true),
            // hero2: new VectorLayer(0, 0, 1440, 678, this.svgFiles.hero2, 2),
            // hero3: new VectorLayer(0, 0, 1440, 678, this.svgFiles.hero3, 3),
            // top_green_star_2: new Layer(100, 0, 182, 240, this.textures.green_star_2, 25),
        };
    }

    loadSVG( url , instance) {
        this.loader.load( url, ( data ) => {
            const paths = data.paths;

            const group = new Group();
            group.position.x = 0;
            group.position.y = 0;
            group.position.z = 0;

            for ( let i = 0; i < paths.length; i ++ ) {

                const path = paths[ i ];

                const fillColor = path.userData.style.fill;
                if ( fillColor !== undefined && fillColor !== 'none' ) {
                    const material = new MeshBasicMaterial( {
                        color: new Color().setStyle( fillColor ),
                        opacity: path.userData.style.fillOpacity,
                        transparent: path.userData.style.fillOpacity < 1,
                        side: DoubleSide,
                        // depthWrite: false,
                        wireframe: false
                    } );

                    const shapes = path.toShapes( true );

                    for ( let j = 0; j < shapes.length; j ++ ) {

                        const shape = shapes[ j ];
                        const geometry = new ShapeBufferGeometry( shape );
                        geometry.applyMatrix4(new Matrix4().makeScale ( 1, -1, 1 ))
                        const mesh = new Mesh( geometry, material );
                        if(typeof path.userData.node.id !== 'undefined') {
                            mesh.name = path.userData.node.id;
                        }

                        group.add( mesh );

                    }

                }

                const strokeColor = path.userData.style.stroke;

                if ( strokeColor !== undefined && strokeColor !== 'none' ) {

                    const material = new MeshBasicMaterial( {
                        color: new Color().setStyle( strokeColor ),
                        opacity: path.userData.style.strokeOpacity,
                        transparent: path.userData.style.strokeOpacity < 1,
                        side: DoubleSide,
                        depthWrite: false,
                        wireframe: false
                    } );

                    for ( let j = 0, jl = path.subPaths.length; j < jl; j ++ ) {
                        const subPath = path.subPaths[ j ];
                        const geometry = SVGLoader.pointsToStroke( subPath.getPoints(), path.userData.style );
                        if ( geometry ) {
                            geometry.applyMatrix4(new Matrix4().makeScale ( 1, -1, 1 ))
                            const mesh = new Mesh( geometry, material );
                            group.add( mesh );
                        }
                    }
                }
            }
            const center = new Vector3();
            const boundingBox = new Box3().setFromObject(group);
            const size = boundingBox.getSize(center);
            const deltaX = size.x / 2;
            const deltaY = size.y / 2;
            group.children.forEach((item) => {
                item.geometry.applyMatrix4(new Matrix4().makeTranslation ( - deltaX, deltaY, 0 ))
            });

            this.svgFiles[instance].object = group;
        } );

    }

    initAsssets() {
        /*this.assetsManager = new LoadingManager();
        this.assetsManager.crossOrigin = "";
        this.assetsManager.onLoad = () => {
            this.initScene();
        };*/

        this.initScene();
        //this.loader = new SVGLoader(this.assetsManager);
        // this.textureLoader = new TextureLoader(this.assetsManager);

        /*for(let svgFile in this.svgFiles) {
            this.loadSVG(window.assets_url +  this.svgFiles[svgFile].file, svgFile);
        }*/

        /*for(let texture in this.textures) {
            this.textures[texture].map = this.textureLoader.load(window.assets_url + this.textures[texture].file)
        }*/

        /*for(let avatar_id in this.avatars) {
            this.avatars[avatar_id].texture.map = this.textureLoader.load(window.assets_url + this.avatars[avatar_id].texture.file)
        }*/

    }

    initScene() {

        this.skyGeometry = new PlaneBufferGeometry(14.0, 3.6, 1, 1);
        this.skyMaterial = new ShaderMaterial({
            uniforms: {
                time: { type: "f", value: 0 },
                resolution: { type: "v2", value: new Vector2(140.0, 50.0) },
            },
            alphaTest: 0.5,
            transparent: true,
            vertexShader: shaderSkyVertex,
            fragmentShader: shaderSkyFragment,
            // wireframe: true
        });

        this.skyMesh = new Mesh(this.skyGeometry, this.skyMaterial);
        this.sky = new Group();
        this.sky.name = 'Sky';
        this.sky.position.set(0, 1.7, 0);
        this.sky.rotation.set(0, 0, 0);
        this.sky.renderOrder = 0;
        this.sky.add(this.skyMesh);
        this.scene.add(this.sky);

        this.rainbowGeometry = new PlaneBufferGeometry(11.0, 1.1, 50, 50);
        this.rainbowMaterial = new ShaderMaterial({
            side: DoubleSide,
            uniforms: {
                time: { type: "f", value: 0 },
                resolution: { type: "v2", value: new Vector2(1.0, 1.0) },
            },
            alphaTest: 0.5,
            transparent: true,
            vertexShader: shaderRainbowVertex,
            fragmentShader: shaderRainbowFragment,
            // wireframe: true
        });

        this.rainbowMesh = new Mesh(this.rainbowGeometry, this.rainbowMaterial);
        this.rainbow = new Group();
        this.rainbow.name = 'Rainbow';
        this.rainbow.position.set(1.04, .11, 0.1);
        this.rainbow.rotation.set(0, 0, -12.9);
        this.rainbow.scale.set(1.120, 0.94, 1);
        this.rainbow.renderOrder = 1;
        this.rainbow.add(this.rainbowMesh);
        this.scene.add(this.rainbow);

        this.render();
    }

    initCanvas() {
        this.winsize = { width: window.innerWidth, height: window.innerHeight };
        this.wrapper = {};
        this.wrapper.el = document.getElementById('hero-wrapper-showroom');
        this.wrapper.width = this.wrapper.el.offsetWidth;
        this.wrapper.height = this.wrapper.el.offsetHeight;
        this.wrapper.widthHalf = this.wrapper.width / 2;
        this.wrapper.heightHalf = this.wrapper.height / 2;
        this.canvas = {};
        this.canvas.el = document.getElementById('hero-scene-showroom');
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

        //this.camera.fov = 2*Math.atan( (this.canvas.height/2)/600 )* (180/Math.PI);

        this.camera.aspect = this.wrapper.width / this.wrapper.height;
        this.camera.position.set(0, 0, 5);

        this.renderer = new WebGLRenderer({
            alpha: true,
            antialias: true,
            canvas: this.canvas.el
        });


        this.renderer.setPixelRatio(1.5)
        this.renderer.setSize(this.wrapper.width, this.wrapper.height);
        // this.renderer.setClearColor(0x000000, 0);
    }

    render() {
        /*if(typeof this.parallaxElems !== 'undefined') {
            for (const item of this.parallaxElems) {
                if ( item.isVisible ) {
                    item.render();
                }
            }
        }*/

        if(this.scrollUpdate) {
            this.scrollUpdate = false;
            this.posToScroll = Math.min(this.wrapper.heightHalf, this.latestKnownScrollY);
        }

        if(this.isVisible) {
            if(this.rainbowMaterial) {
                this.rainbowMaterial.uniforms.time.value += 0.05;
            }

            if(this.rainbowMaterial) {
                this.skyMaterial.uniforms.time.value += 0.05;
            }

            this.renderer.render(this.scene, this.camera)
        }
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
        } )
        window.addEventListener('scroll', this.onScroll)
    }

    onScroll() {
        this.latestKnownScrollY = window.pageYOffset || document.documentElement.scrollTop;
        this.scrollUpdate = true;
    }

    bindMethods() {
        ['onResize', 'render', 'onMouseMove', 'onScroll']
            .forEach((fn) => this[fn] = this[fn].bind(this))
    }
}

export default HeroSceneShowroom
