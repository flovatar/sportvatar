import { gsap, Elastic, Power2, Back } from "gsap/all";
import { OrbitControls } from './OrbitControls.js';
import * as THREE from 'three';
import { SVGLoader } from './SVGLoader.js';

THREE.ShapePath.prototype.absarc = function( aX, aY, aRadius, aStartAngle, aEndAngle, aClockwise ) {
    this.currentPath.absarc(aX, aY, aRadius, aStartAngle, aEndAngle, aClockwise);
};
THREE.ShapePath.prototype.absellipse = function( aX, aY, xRadius, yRadius, aStartAngle, aEndAngle, aClockwise ) {
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

class HeroScene {

    constructor() {
        this.body = document.body;
        this.latestKnownScrollY = window.pageYOffset || document.documentElement.scrollTop;
        this.isVisible = true;
        this.bindMethods();
        this.initCanvas();
        this.initObjects();
        this.initAsssets();
        this.initWebGL();
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

        this.textures = {
            // avatar1 : { file: 'avatar1.png' },
            // avatar2 : { file: 'avatar2.png' },
        };

        this.avatars = [
            {
                name: 'Blondie',
                url: 'https://sportvatar.com/sportvatars/2/0x239a18ac5e9af236',
                texture: { file: 'avatar1.png' }
            },
            {
                name: 'Brownie',
                url: 'https://sportvatar.com/sportvatars/2/0x239a18ac5e9af236',
                texture: { file: 'avatar2.png' }
            },
            {
                name: 'Rudy',
                url: 'https://sportvatar.com/sportvatars/2/0x239a18ac5e9af236',
                texture: { file: 'sportvatar-9.png' }
            },
            {
                name: 'Clara',
                url: 'https://sportvatar.com/sportvatars/2/0x239a18ac5e9af236',
                texture: { file: 'sportvatar-10.png' }
            }
        ];

        this.svgFiles = {
            // hero1 : { file: 'hero-1.svg' },
            heroGround : { file: 'hero-ground.svg' },
            // hero2 : { file: 'hero-2.svg' },
            // hero3 : { file: 'hero-3.svg' },
            cloud1 : { file: 'cloud1.svg' },
            cloud2 : { file: 'cloud2.svg' },
            cloud3 : { file: 'cloud3.svg' },
            cloud4 : { file: 'cloud4.svg' },
        }

        this.layers = {
            // top_green_star: new Layer(-100, 0, 182, 240, this.textures.green_star, 25),
            // star: new VectorLayer(0, 0, 200, 200, this.svgFiles.star, 5),
            // bolt: new VectorLayer(0, 0, 200, 200, this.svgFiles.bolt, 5),
            heroGround: new VectorLayer('ground', 0, -10, 473, 22, this.svgFiles.heroGround, 10, true),
            // hero2: new VectorLayer(0, 0, 1440, 678, this.svgFiles.hero2, 2),
            // hero3: new VectorLayer(0, 0, 1440, 678, this.svgFiles.hero3, 3),
            // top_green_star_2: new Layer(100, 0, 182, 240, this.textures.green_star_2, 25),
        };
    }

    loadSVG( url , instance) {
        this.loader.load( url, ( data ) => {
            const paths = data.paths;

            const group = new THREE.Group();
            group.position.x = 0;
            group.position.y = 0;
            group.position.z = 0;

            for ( let i = 0; i < paths.length; i ++ ) {

                const path = paths[ i ];

                const fillColor = path.userData.style.fill;
                if ( fillColor !== undefined && fillColor !== 'none' ) {
                    const material = new THREE.MeshBasicMaterial( {
                        color: new THREE.Color().setStyle( fillColor ),
                        opacity: path.userData.style.fillOpacity,
                        transparent: path.userData.style.fillOpacity < 1,
                        side: THREE.DoubleSide,
                        // depthWrite: false,
                        wireframe: false
                    } );

                    const shapes = path.toShapes( true );

                    for ( let j = 0; j < shapes.length; j ++ ) {

                        const shape = shapes[ j ];
                        const geometry = new THREE.ShapeBufferGeometry( shape );
                        geometry.applyMatrix4(new THREE.Matrix4().makeScale ( 1, -1, 1 ))
                        const mesh = new THREE.Mesh( geometry, material );
                        if(typeof path.userData.node.id !== 'undefined') {
                            mesh.name = path.userData.node.id;
                        }

                        group.add( mesh );

                    }

                }

                const strokeColor = path.userData.style.stroke;

                if ( strokeColor !== undefined && strokeColor !== 'none' ) {

                    const material = new THREE.MeshBasicMaterial( {
                        color: new THREE.Color().setStyle( strokeColor ),
                        opacity: path.userData.style.strokeOpacity,
                        transparent: path.userData.style.strokeOpacity < 1,
                        side: THREE.DoubleSide,
                        depthWrite: false,
                        wireframe: false
                    } );

                    for ( let j = 0, jl = path.subPaths.length; j < jl; j ++ ) {
                        const subPath = path.subPaths[ j ];
                        const geometry = SVGLoader.pointsToStroke( subPath.getPoints(), path.userData.style );
                        if ( geometry ) {
                            geometry.applyMatrix4(new THREE.Matrix4().makeScale ( 1, -1, 1 ))
                            const mesh = new THREE.Mesh( geometry, material );
                            group.add( mesh );
                        }
                    }
                }
            }
            const center = new THREE.Vector3();
            const boundingBox = new THREE.Box3().setFromObject(group);
            const size = boundingBox.getSize(center);
            const deltaX = size.x / 2;
            const deltaY = size.y / 2;
            group.children.forEach((item) => {
                item.geometry.applyMatrix4(new THREE.Matrix4().makeTranslation ( - deltaX, deltaY, 0 ))
            });

            this.svgFiles[instance].object = group;
        } );

    }

    initAsssets() {
        this.assetsManager = new THREE.LoadingManager();
        this.assetsManager.crossOrigin = "";
        this.assetsManager.onLoad = () => {
            this.initScene();
        };
        this.loader = new SVGLoader(this.assetsManager);
        this.textureLoader = new THREE.TextureLoader(this.assetsManager);

        for(let svgFile in this.svgFiles) {
            this.loadSVG(window.assets_url +  this.svgFiles[svgFile].file, svgFile);
        }

        for(let texture in this.textures) {
            this.textures[texture].map = this.textureLoader.load(window.assets_url + this.textures[texture].file)
        }

        for(let avatar_id in this.avatars) {
            this.avatars[avatar_id].texture.map = this.textureLoader.load(window.assets_url + this.avatars[avatar_id].texture.file)
        }

    }

    generateClouds() {
        this.clouds = [
            {
                name: 'cloud_1',
                svg: this.svgFiles.cloud1,
                x: 640,
                y: 200,
                z: 1,
                scale: 0.8
            }, {
                name: 'cloud_2',
                svg: this.svgFiles.cloud2,
                x: 145,
                y: 320,
                z: 1,
                scale: 0.8
            }, {
                name: 'cloud_3',
                svg: this.svgFiles.cloud3,
                x: 550,
                y: 340,
                z: 1,
                scale: 0.85
            }, {
                name: 'cloud_4',
                svg: this.svgFiles.cloud4,
                x: 480,
                y: 380,
                z: 1,
                scale: 0.8
            }
        ];

        this.cloudsGroup = new THREE.Group();
        this.cloudsGroup.name = 'Clouds';

        for (let i = 0; i < this.clouds.length; i++) {
            this.clouds[i].svg.object.position.set(this.clouds[i].x, this.clouds[i].y, this.clouds[i].z);
            this.clouds[i].svg.object.renderOrder = this.clouds[i].z;
            this.clouds[i].svg.object.name = this.clouds[i].name;
            this.clouds[i].svg.object.scale.x = this.clouds[i].scale;
            this.clouds[i].svg.object.scale.y = this.clouds[i].scale;
            // let bb = new Box3().setFromObject(this.layers[item].object.object);
            // let size = bb.getSize(new Vector3());
            // console.log(size)
            // this.layers[item].object.object.visible = false;

            var material = new THREE.ShaderMaterial({
                extensions: {
                    derivatives: "#extension GL_OES_standard_derivatives : enable"
                },
                side: THREE.DoubleSide,
                uniforms: {
                    color1: {
                        type: 'c',
                        value: new THREE.Color(0xffffff)
                    },
                    color2: {
                        type: 'c',
                        value: new THREE.Color(0x111111)
                    }
                },
                vertexShader: `
    varying vec2 vUv;

    void main() {
      vUv = uv;
      gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0);
    }
  `,
                fragmentShader: `
    uniform vec3 color1;
    uniform vec3 color2;

    varying vec2 vUv;

    void main() {

      gl_FragColor = vec4(mix(color1, color2, vUv.y), 1.0);
    }
  `,
            });
            this.clouds[i].svg.object.children[0].material = material;

            this.cloudsGroup.add(this.clouds[i].svg.object);

            /*const svgShapes = this.clouds[i].svgPath.toShapes(true);
            let svgGeometry;
            for ( let j = 0; j < svgShapes.length; j ++ ) {
                const shape = svgShapes[ j ];
                svgGeometry = new ShapeBufferGeometry( shape );
                svgGeometry.applyMatrix4(new Matrix4().makeScale ( 1, -1, 1 ))
            }
            console.log(svgGeometry)
            this.clouds[i].geometry = svgGeometry;

            const cloudMaterial = new MeshBasicMaterial( {
                color: new Color().setStyle( 0xff00ff ),
                opacity: 1,
                transparent: true,
                side: THREE.DoubleSide,
                depthWrite: false,
                wireframe: false
            } );

            const cloudMesh = new Mesh( svgGeometry, cloudMaterial );

            cloudMesh.position.x = this.clouds[i].x;
            cloudMesh.position.y = this.clouds[i].y;
            cloudMesh.position.z = 0;
            cloudMesh.name = `Cloud${i}`;

            this.cloudsGroup.add( cloudMesh );
            this.clouds[i].mesh = cloudMesh;*/
        }
        this.scene.add(this.cloudsGroup);
    }

    generateStars() {
        const starShapePath = transformSVGPath('M21.59,17.33C11.83,17.33,11.64,0,11.64,0,11.45,16.82,0,18.83,0,18.83c12,1,12,15.82,12,15.82s.14-16.07,9.22-17.2');
        const starShape = starShapePath.toShapes(true);

        let starGeometry;
        for ( let j = 0; j < starShape.length; j ++ ) {
            const shape = starShape[ j ];
            starGeometry = new THREE.ShapeBufferGeometry( shape );
            starGeometry.applyMatrix4(new THREE.Matrix4().makeScale ( 1, -1, 1 ))
        }

        this.stars = [];
        this.starsGroup = new Group();
        this.starsGroup.name = 'Stars';

        for (let i = -100; i < 100; i ++) {
            const starMaterial = new THREE.MeshBasicMaterial( {
                color: new THREE.Color().setStyle( 0xffffff ),
                opacity: Math.random(),
                transparent: true,
                side: THREE.DoubleSide,
                depthWrite: false,
                wireframe: false
            } );

            const starSphere = new THREE.Mesh( starGeometry, starMaterial );

            starSphere.position.x = Math.random() * 1600 - 800;
            starSphere.position.y = Math.random() * 1600 - 800;
            starSphere.position.z = 0;
            starSphere.name = 'Star';
            starSphere.isAnimated = false;


            starSphere.scale.x = starSphere.scale.y = Math.random() * 0.5 + 0.5;
            this.starsGroup.add( starSphere );
            this.stars.push( starSphere );
        }
        this.scene.add(this.starsGroup);

        this.starsIndexes = [];
    }

    animateStars () {
        if (typeof this.stars !== 'undefined') {
            for (let i = 0; i < 3; i++) {
                const starIndex = Math.floor(Math.random() * this.stars.length);
                if(this.starsIndexes.indexOf(starIndex) === -1) {
                    this.starsIndexes.push(starIndex);
                    if(!this.stars[starIndex].isAnimated) {
                        this.stars[starIndex].isAnimated = true;
                        gsap.to(this.stars[starIndex].material, {
                            duration: Math.random() * 2 + 3,
                            opacity: Math.random(),
                            onComplete: () => {
                                this.stars[starIndex].isAnimated = false;
                            }
                        })
                    }
                }
            }
            if(this.starsIndexes.length > 30) {
                this.starsIndexes = this.starsIndexes.slice(3);
            }
            // console.log(this.starsIndexes);
        }
    }

    initScene() {
        this.generateStars();
        this.generateClouds();

        this.vertex = `varying vec2 vUv;void main() {vUv = uv;gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );}`;
        this.fragment = `
		uniform float progress;
		uniform sampler2D texture1;

		varying vec2 vUv;

        vec2 random(vec2 uv){
            uv = vec2( dot(uv, vec2(127.1,311.7) ),
                       dot(uv, vec2(269.5,183.3) ) );
            return -1.0 + 2.0 * fract(sin(uv) * 43758.5453123);
        }

        float noise(vec2 uv) {
            vec2 uv_index = floor(uv);
            vec2 uv_fract = fract(uv);

            vec2 blur = smoothstep(0.0, 1.0, uv_fract);

            return mix( mix( dot( random(uv_index + vec2(0.0,0.0) ), uv_fract - vec2(0.0,0.0) ),
                             dot( random(uv_index + vec2(1.0,0.0) ), uv_fract - vec2(1.0,0.0) ), blur.x),
                        mix( dot( random(uv_index + vec2(0.0,1.0) ), uv_fract - vec2(0.0,1.0) ),
                             dot( random(uv_index + vec2(1.0,1.0) ), uv_fract - vec2(1.0,1.0) ), blur.x), blur.y) * 0.5 + 0.5;
        }

		void main()	{
		    float beam_size = 0.08;
		    float noise_desnity = 60.0;
    		vec4 color = vec4(0.0, 1.02, 1.2, 1.0);

            float noise = noise(vUv * noise_desnity) * vUv.y;

            float d1 = step(progress, noise);
            float d2 = step(progress - beam_size, noise);

            vec3 beam = vec3(d2 - d1) * color.rgb;

            vec4 tex = texture2D(texture1, vUv);
            tex.rgb += beam;
            tex.a *= d2;

			gl_FragColor = tex;
			if ( gl_FragColor.a < 0.5 ) discard;
		}
	`;
        this.vertexTeleport = `varying vec2 vUv;

\t\t\tvoid main()
\t\t\t{
\t\t\t\tvUv = uv;
\t\t\t\tvec4 mvPosition = modelViewMatrix * vec4( position, 1.0 );
\t\t\t\tgl_Position = projectionMatrix * mvPosition;
\t\t\t}`;

        this.fragmentTeleport1 = `
		uniform float time;

\t\t\tvarying vec2 vUv;

\t\t\tvoid main( void ) {

\t\t\t\tvec2 position = vUv;

\t\t\t\tfloat color = 0.0;
\t\t\t\tcolor += sin( position.x * cos( time / 15.0 ) * 80.0 ) + cos( position.y * cos( time / 15.0 ) * 10.0 );
\t\t\t\tcolor += sin( position.y * sin( time / 10.0 ) * 40.0 ) + cos( position.x * sin( time / 25.0 ) * 40.0 );
\t\t\t\tcolor += sin( position.x * sin( time / 5.0 ) * 10.0 ) + sin( position.y * sin( time / 35.0 ) * 80.0 );
\t\t\t\tcolor *= sin( time / 10.0 ) * 0.5;

\t\t\t\tgl_FragColor = vec4( vec3( color, color * 0.5, sin( color + time / 3.0 ) * 0.75 ), 1.0 );

\t\t\t}

	`;

        this.fragmentTeleport = `
		#ifdef GL_ES
precision mediump float;
#endif

uniform float time;
uniform vec2 resolution;
varying vec2 vUv;

float rayStrength(vec2 raySource, vec2 rayRefDirection, vec2 coord, float seedA, float seedB, float speed)
{
vec2 sourceToCoord = coord - raySource;
\tfloat cosAngle = dot(normalize(sourceToCoord), rayRefDirection);
\t
\treturn clamp(
\t\t(0.45 + 0.20 * sin(cosAngle * seedA + time * speed)) +
\t\t(0.2 + 0.2 * cos(-cosAngle * seedB + time * speed)),
\t\t0.0, 1.0) *
\t\tclamp((resolution.x - length(sourceToCoord)) / resolution.x, 0.4, 0.01);
}
float sdCircle( in vec2 p, in float r ) {
    return length(p)-r;
}
void main( void ) {
// vec2 p = vec2(vUv.x - vUv.x * 0.5, vUv.y - vUv.y * 0.5);
//vec2 p = vec2((vUv.x - .5)*3., (vUv.y - .5)*3.);
vec2 uv = vUv.xy / resolution.xy;
\tuv.y = 1.0 - uv.y;
\tvec2 coord = vec2(vUv.x, vUv.y);
\t
\t
vec2 rayPos1 = vec2((vUv.x + 0.5 )* 0.5, vUv.y * -0.01);
\tvec2 rayRefDir1 = normalize(vec2(1.0, -0.116));
\tfloat raySeedA1 = 36.2214;
\tfloat raySeedB1 = 21.11349;
\tfloat raySpeed1 = 1.9;
\t
\tvec2 rayPos2 = vec2((vUv.x + 0.5) * 0.5, vUv.y * -0.02);
\tvec2 rayRefDir2 = normalize(vec2(1.0, 0.241));
\tconst float raySeedA2 = 22.39910;
\tconst float raySeedB2 = 18.0234;
\tconst float raySpeed2 = 1.6;

vec4 rays1 =
\t\tvec4(1.0, 1.0, 1.0, 1.0) *
\t\trayStrength(rayPos1, rayRefDir1, coord, raySeedA1, raySeedB1, raySpeed1);
\t
\tvec4 rays2 =
\t\tvec4(1.0, 1.0, 1.0, 1.0) *
\t\trayStrength(rayPos2, rayRefDir2, coord, raySeedA2, raySeedB2, raySpeed2);
\t
\tvec4 color = rays1 * 0.5 + rays2 * 0.4;

vec2 p = vec2((vUv.x - .5)*3., (vUv.y - .5)*3.);

float d = sdCircle(p, 0.5);

\t// Attenuate brightness towards the bottom, simulating light-loss due to depth.
\t// Give the whole thing a blue-green tinge as well.
\tfloat brightness = 1.0 - (coord.y);
\tcolor.r *= 0.6 + (brightness * 0.8);
\tcolor.g *= 0.8 + (brightness * 0.9);
\tcolor.b *= 0.9 + (brightness * 0.9);
color.a = color.a * smoothstep(0., 1., 1. - d);
    gl_FragColor = color;
}

	`;
        this.fragmentTeleport4 = `
		#ifdef GL_ES
precision mediump float;
#endif

uniform float time;
uniform vec2 resolution;
varying vec2 vUv;

float sdCircle( in vec2 p, in float r ) {
    return length(p)-r;
}

float light(vec2 pos)
{
pos.y -= 0.5;
float mask = 1. - (pos.x * pos.x * 20. - pos.y + 0.6);
float brightness = clamp(pow(0.1/(pos.y+.5),2.),0.,1.);
return mask*brightness*18.;
}


void main( void ) {
// vec2 p = vec2(vUv.x - vUv.x * 0.5, vUv.y - vUv.y * 0.5);
vec2 p = vec2((vUv.x - .5)*3., (vUv.y - .5)*3.);

float d = sdCircle(p, 0.5);

 vec3 col = vec3(1.0) - smoothstep(0., 1., d)*vec3(1.,1.,1.);


    gl_FragColor = vec4(col, smoothstep(0., 1., 1. - d));
}

	`;
        this.fragmentTeleport3 = `
		#ifdef GL_ES
precision mediump float;
#endif

uniform float time;
uniform vec2 resolution;
varying vec2 vUv;

float light(vec2 pos)
{
pos.y -= 0.5;
float mask = 1. - (pos.x * pos.x * 20. - pos.y + 0.6);
float brightness = clamp(pow(0.1/(pos.y+.5),2.),0.,1.);
return mask*brightness*18.;
}


void main( void )
{
vec2 p = (vUv.xy/resolution);
vec3 color = vec3(0.0,0.0,0.0);
float lightness = light(vec2(p.x,p.y));
color += vec3 (1., 0., 0. ) * clamp(lightness,0.,1.);

    gl_FragColor = vec4(color, 1.);
    // gl_FragColor = vec4(color, lightness);
}

	`;

        this.fragmentTeleport2 = `
		#ifdef GL_ES
precision mediump float;
#endif
precision highp float;

uniform float time;
uniform vec2 mouse;
uniform vec2 resolution;

vec3 getColor(vec3 p)
{
    return max(vec3(0.2, 0.5, 0.8) * 7.0 * pow(p.y, 4.0) * smoothstep(0.0, -1.0, length(p.xz) - 5.0), vec3(0.0));
}

mat3 camera(vec3 ro, vec3 ta, float cr )
{
  vec3 cw = normalize(ta - ro);
\tvec3 cp = vec3(sin(cr), cos(cr),0.);
\tvec3 cu = normalize( cross(cw,cp) );
\tvec3 cv = normalize( cross(cu,cw) );
    return mat3( cu, cv, cw );
}


vec3 acesFilm(const vec3 x) {
    const float a = 2.51;
    const float b = 0.03;
    const float c = 2.43;
    const float d = 0.59;
    const float e = 0.14;
    return clamp((x * (a * x + b)) / (x * (c * x + d ) + e), 0.0, 1.0);
}

void main( void ) {

vec2 p = (gl_FragCoord.xy * 2.0 - resolution.xy) / min(resolution.x, resolution.y);

    float t = 1.;
    vec3 ro = vec3(0.5, 5.5, 10.0);
    vec3 ta = vec3(-2.0, 1.0, 0.0);
    mat3 c = camera(ro, ta, 0.0);
    vec3 ray = c * normalize(vec3(p, 2.5));
    vec3 col = getColor(ro);

    vec3 lp = vec3(0.0, 6.0, 0.0);
    float tt = 6.;
    float s = 7.5, vol = 0.0;
    for(int i = 0; i < 50; i++) {
        vec3 pos = ro + ray*s;

        vec3 v = normalize(lp - pos);

        vec3 ppos = lp + v * tt;
    vol += 0.05 * smoothstep(0.0, 1.5, pos.y) * smoothstep(-1.0, -4.0, length(ppos.xz) - 5.0);

        s += 0.1;
    }

    col += 1.6*vec3(0.3*vol, 0.5*vol, vol);

    col = acesFilm(col * (cos(time/2.0) * 0.5));
    col = pow(col, vec3(1.0/2.2));

    gl_FragColor = vec4(col,.50);

}

	`;

        for(let item in this.layers) {
            if(!this.layers[item].exclude) {
                if(this.layers[item].type === 'raster') {
                    this.layers[item].posterGeom = new THREE.PlaneGeometry(this.layers[item].width, this.layers[item].height, 1, 1);
                    this.layers[item].material = new THREE.MeshBasicMaterial({
                        map: this.layers[item].sprite.map,
                        side: THREE.DoubleSide,
                        alphaTest: 0.5,
                    });
                    this.layers[item].mesh = new Mesh(this.layers[item].posterGeom, this.layers[item].material);
                    this.layers[item].mesh.renderOrder = this.layers[item].z;
                    const group = new Group();
                    group.add(this.layers[item].mesh);
                    // group.visible = false;
                    group.position.set(this.layers[item].x, this.layers[item].y, this.layers[item].z);
                    group.renderOrder = this.layers[item].z;
                    this.layers[item].object = {
                        object: group
                    };
                    this.scene.add(this.layers[item].object.object)
                }

                if(this.layers[item].type === 'vector') {
                    this.layers[item].object.object.position.set(this.layers[item].x, this.layers[item].y, this.layers[item].z);
                    this.layers[item].object.object.renderOrder = this.layers[item].z;
                    this.layers[item].object.object.name = this.layers[item].name;
                    // let bb = new Box3().setFromObject(this.layers[item].object.object);
                    // let size = bb.getSize(new Vector3());
                    // console.log(size)
                    // this.layers[item].object.object.visible = false;
                    this.scene.add(this.layers[item].object.object);
                }
            }
        }

        // ground
        if(typeof this.layers.heroGround !== 'undefined') {
            this.layers.heroGround.object.object.position.set(this.layers.heroGround.x, this.layers.heroGround.y, this.layers.heroGround.z);
            this.layers.heroGround.object.object.renderOrder = this.layers.heroGround.z;
            this.layers.heroGround.object.object.name = this.layers.heroGround.name;

            // let bb = new Box3().setFromObject(this.layers.heroGround.object.object);
            // let size = bb.getSize(new Vector3());

            this.layers.heroGround.object.object.scale.x = 0.85;
            this.layers.heroGround.object.object.scale.y = 0.85;

            this.scene.add(this.layers.heroGround.object.object);
        }

        this.avatarAspectRatio = 1200 / 890;

        this.characters = [
            {
                size: 360,
                name: 'character_1',
                position: new THREE.Vector3(-520, 30, 30),
                renderOrder: 20,
                currentAvatar: Math.floor(Math.random() * 4)
            },
            {
                size: 160,
                name: 'character_2',
                position: new THREE.Vector3(390, -195, 30),
                renderOrder: 20,
                currentAvatar: Math.floor(Math.random() * 4)
            }
        ];

        this.timelineTeleport = gsap.timeline({repeat: -1});
        this.timelineTeleportStand = gsap.timeline({repeat: -1, yoyo: true});

        for(let i = 0; i < this.characters.length; i++) {
            const vertex = `varying vec2 vUv;void main() {vUv = uv;gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );}`;
            const fragment = `
		uniform float progress;
		uniform sampler2D texture1;

		varying vec2 vUv;

        vec2 random(vec2 uv){
            uv = vec2( dot(uv, vec2(127.1,311.7) ),
                       dot(uv, vec2(269.5,183.3) ) );
            return -1.0 + 2.0 * fract(sin(uv) * 43758.5453123);
        }

        float noise(vec2 uv) {
            vec2 uv_index = floor(uv);
            vec2 uv_fract = fract(uv);

            vec2 blur = smoothstep(0.0, 1.0, uv_fract);

            return mix( mix( dot( random(uv_index + vec2(0.0,0.0) ), uv_fract - vec2(0.0,0.0) ),
                             dot( random(uv_index + vec2(1.0,0.0) ), uv_fract - vec2(1.0,0.0) ), blur.x),
                        mix( dot( random(uv_index + vec2(0.0,1.0) ), uv_fract - vec2(0.0,1.0) ),
                             dot( random(uv_index + vec2(1.0,1.0) ), uv_fract - vec2(1.0,1.0) ), blur.x), blur.y) * 0.5 + 0.5;
        }

		void main()	{
		    float beam_size = 0.08;
		    float noise_desnity = 160.0;
    		vec4 color = vec4(0.0, 1.02, 1.2, 1.0);

            float noise = noise(vUv * noise_desnity) * vUv.y;

            float d1 = step(progress, noise);
            float d2 = step(progress - beam_size, noise);

            vec3 beam = vec3(d2 - d1) * color.rgb;

            vec4 tex = texture2D(texture1, vUv);
            tex.rgb += beam;
            tex.a *= d2;

			gl_FragColor = tex;
			if ( gl_FragColor.a < 0.5 ) discard;
		}
	`;

            this.characters[i].avatarMaterial = new THREE.ShaderMaterial({
                extensions: {
                    derivatives: "#extension GL_OES_standard_derivatives : enable"
                },
                side: THREE.DoubleSide,
                uniforms: {
                    progress: { type: "f", value: 1 },
                    texture1: { type: "f", value: this.avatars[this.characters[i].currentAvatar].texture.map },
                    resolution: { type: "v4", value: new THREE.Vector4() },
                },
                alphaTest: 0.5,
                transparent: true,
                vertexShader: vertex,
                fragmentShader: fragment
            });

            this.characters[i].avatarGeometry = new THREE.PlaneGeometry(this.characters[i].size, this.characters[i].size * this.avatarAspectRatio, 1, 1);
            this.characters[i].avatarMesh = new THREE.Mesh(this.characters[i].avatarGeometry, this.characters[i].avatarMaterial);
            this.characters[i].character = new THREE.Group();
            this.characters[i].character.name = `character_${i}`;
            this.characters[i].character.position.set(this.characters[i].position.x, this.characters[i].position.y, this.characters[i].position.z);
            this.characters[i].character.renderOrder = this.characters[i].renderOrder;
            this.characters[i].character.add(this.characters[i].avatarMesh);
            this.scene.add(this.characters[i].character);

            this.timelineTeleport
                .to(this.characters[i].avatarMaterial.uniforms.progress, {
                    value: 0,
                    duration: 3,
                    ease: Power2[Power2.easeOut]
                }, i * 2)
                .to(this.characters[i].avatarMaterial.uniforms.progress, {
                    value: 1,
                    duration: 3,
                    ease: Power2[Power2.easeOut],
                    onComplete:()=>{
                        let nextAvatar = Math.floor(Math.random() * 4);
                        while (nextAvatar === this.characters[i].currentAvatar) {
                            nextAvatar = Math.floor(Math.random() * 4)
                        }
                        this.characters[i].currentAvatar = nextAvatar;

                        this.characters[i].avatarMaterial.uniforms.texture1.value = this.avatars[nextAvatar].texture.map;
                    }
                }, 6 + i * 2)
        }

        this.teleportMaterial = new ShaderMaterial({
            extensions: {
                derivatives: "#extension GL_OES_standard_derivatives : enable"
            },
            side: THREE.DoubleSide,
            uniforms: {
                time: { type: "f", value: 0 },
                resolution: { type: "v4", value: new Vector4(600.0, 600.0) },
            },
            alphaTest: 0.5,
            transparent: true,
            vertexShader: this.vertexTeleport,
            fragmentShader: this.fragmentTeleport
        });

        this.telportGeometry = new THREE.PlaneBufferGeometry(600, 600, 1, 1);

        this.telportMesh = new THREE.Mesh(this.telportGeometry, this.teleportMaterial);
        this.telport = new THREE.Group();
        this.telport.name = 'TeleportStand1';
        this.telport.position.set(-525, 0, 30);
        this.telport.renderOrder = 30;
        this.telport.add(this.telportMesh);
        this.scene.add(this.telport);

        this.teleportMaterial2 = new THREE.ShaderMaterial({
            extensions: {
                derivatives: "#extension GL_OES_standard_derivatives : enable"
            },
            side: THREE.DoubleSide,
            uniforms: {
                time: { type: "f", value: 0 },
                resolution: { type: "v4", value: new THREE.Vector4(200.0, 200.0) },
            },
            alphaTest: 0.5,
            transparent: true,
            vertexShader: this.vertexTeleport,
            fragmentShader: this.fragmentTeleport
        });

        this.telportGeometry2 = new THREE.PlaneBufferGeometry(200, 200, 1, 1);

        this.telportMesh2 = new THREE.Mesh(this.telportGeometry2, this.teleportMaterial2);
        this.telport2 = new THREE.Group();
        this.telport2.name = 'TeleportStand2';
        this.telport2.position.set(395, -220, 30);
        this.telport2.renderOrder = 30;
        this.telport2.add(this.telportMesh2);
        this.scene.add(this.telport2);

        gsap.ticker.add(this.tick);

        const durationTitle1 = 0.5;
        const durationTitle2 = 2;
        const easeElastic = Elastic.easeOut.config(1.1, 0.4);

        // this.timelineTeleport2 = gsap.timeline({repeat:-1});

        const stand_circle_1 = this.layers.heroGround.object.object.getObjectByName('stand_1');
        const stand_circle_2 = this.layers.heroGround.object.object.getObjectByName('stand_2');
        const stand_circle_color_initial = new THREE.Color(stand_circle_1.material.color.getHex());
        const stand_circle_color_final = new THREE.Color('#ff25a6');
        // console.log(stand_circle_2);
        this.timelineTeleportStand
            .to(stand_circle_color_initial, {
                duration: 1,
                r: stand_circle_color_final.r,
                g: stand_circle_color_final.g,
                b: stand_circle_color_final.b,
                onUpdate: () => {
                    stand_circle_1.material.color = stand_circle_color_initial;
                    stand_circle_2.material.color = stand_circle_color_initial;
                }
            });

    }

    initCanvas() {
        this.winsize = { width: window.innerWidth, height: window.innerHeight };
        this.wrapper = {};
        this.wrapper.el = document.getElementById('hero-wrapper');
        this.wrapper.width = this.wrapper.el.offsetWidth;
        this.wrapper.height = this.wrapper.el.offsetHeight;
        this.wrapper.widthHalf = this.wrapper.width / 2;
        this.wrapper.heightHalf = this.wrapper.height / 2;
        this.canvas = {};
        this.canvas.el = document.getElementById('hero-scene-home');
        this.canvas.width = this.canvas.el.offsetWidth;
        this.canvas.height = this.canvas.el.offsetHeight;

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => this.isVisible = entry.intersectionRatio > 0);
        });
        this.observer.observe(this.wrapper.el);
    }

    initWebGL() {
        this.scene = new THREE.Scene();

        const nearDist = 1;
        const farDist = 1400;
        this.camera = new THREE.PerspectiveCamera(
            45,
            this.canvas.width / this.canvas.height,
            nearDist,
            farDist
        );

        this.camera.aspect = this.wrapper.width / this.wrapper.height;
        this.camera.position.set(0, 0, 1100);

        this.renderer = new THREE.WebGLRenderer({
            alpha: true,
            antialias: true,
            canvas: this.canvas.el
        });

        this.controls = new OrbitControls( this.camera, this.renderer.domElement );
        this.controls.update();
        this.renderer.setPixelRatio(1.5)
        this.renderer.setSize(this.wrapper.width, this.wrapper.height);
        // this.renderer.setClearColor(0x000000, 0);
    }

    tick() {
        this.render();
    }

    render() {
        /*if(typeof this.parallaxElems !== 'undefined') {
            for (const item of this.parallaxElems) {
                if ( item.isVisible ) {
                    item.render();
                }
            }
        }*/
        if(this.isVisible) {
            if(this.teleportMaterial) {
                this.teleportMaterial.uniforms.time.value += 0.05;
            }

            if(this.teleportMaterial2) {
                this.teleportMaterial2.uniforms.time.value += 0.05;
            }

            this.animateStars();
            this.controls.update();
            this.renderer.render(this.scene, this.camera)
        }
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


        /*const redStarFactor = 1 + deltaPercent * 0.002;
        const redStarFactor2 = 1 - deltaPercent * 0.002;
        gsap.to(this.layers.right_red_star.mesh.scale, {x: redStarFactor, y: redStarFactor, z: redStarFactor});
        gsap.to(this.layers.left_red_star.mesh.scale, {x: redStarFactor2, y: redStarFactor2, z: redStarFactor2});*/

        const greenStarFactor = 1; //1 + deltaPercentY * 0.002;
        const greenStarFactor2 = 1 - deltaPercentY * 0.002;
        // gsap.to(this.layers.top_green_star.mesh.scale, {x: greenStarFactor, y: greenStarFactor, z: greenStarFactor});
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
    }

    bindMethods() {
        ['onResize', 'tick', 'onMouseMove', 'onScroll']
            .forEach((fn) => this[fn] = this[fn].bind(this))
    }
}

export default HeroScene
