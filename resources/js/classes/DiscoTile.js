import { gsap, Linear } from "gsap/all";
import { Shape, Mesh, ShaderMaterial, Color, ShapeBufferGeometry } from 'three';

class DiscoTile {

    constructor(x, y, wall, color) {
        this.x = x;
        this.y = y;
        this.wall = wall;
        this.isAnimate = false;
        this.isWaveAnimate = false;
        this.colorWhite = new Color(0xffffff);
        this.color = new Color(color);
        this.colorShine = new Color(color).lerp(this.colorWhite, 0.8);

        this.material = new ShaderMaterial({
            uniforms: {
                color1: { value: this.colorShine},
                color2: { value: this.color},
                ratio: { value: 1.0 }
            },
            vertexShader: `varying vec2 vUv;void main() {vUv = uv;gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );}`,
            fragmentShader: `varying vec2 vUv;
            uniform vec3 color1;
            uniform vec3 color2;
            uniform float ratio;
            void main(){
                vec2 uv = (vUv - 0.7) * vec2(ratio, 1.);
                gl_FragColor = vec4( mix( color1, color2, length(uv) * 1.5), 1. );
            }`
        });

        let width = 0.94; let height = 0.94; let radius = 0.05;

        let shape = new Shape();
        const dx = 0;
        const dy = 0;
        shape.moveTo( dx, dy + radius );
        shape.lineTo( dx, dy + height - radius );
        shape.quadraticCurveTo( dx, dy + height, dx + radius, dy + height );
        shape.lineTo( dx + width - radius, dy + height );
        shape.quadraticCurveTo( dx + width, dy + height, dx + width, dy + height - radius );
        shape.lineTo( dx + width, dy + radius );
        shape.quadraticCurveTo( dx + width, dy, dx + width - radius, dy );
        shape.lineTo( dx + radius, dy );
        shape.quadraticCurveTo( dx, dy, dx, dy + radius );

        this.geometry = new ShapeBufferGeometry( shape );

        this.mesh = new Mesh(this.geometry, this.material);
        this.mesh.position.set(x + 0.02, y + 0.02, 0)
        this.mesh.userData.tile = this;
    }

    updateColorAnimation(color) {
        this.isAnimate = true;
        let color_initial = this.color;
        let color_final = new Color(color);
        this.animation = gsap.to(color_initial, {
            duration: 0.3,
            r: color_final.r,
            g: color_final.g,
            b: color_final.b,
            onUpdate: () => {
                this.material.uniforms.color1.value = new Color(color_initial).lerp(this.colorWhite, 0.8);
                this.material.uniforms.color2.value = color_initial;
            },
            onComplete: () => {
                this.isAnimate = false;
                this.color = color_final;
            }
        });
    }

    updateColor(color) {
        if(!this.isAnimate && !this.isWaveAnimate) {
            this.updateColorAnimation(color)
        }
    }

    forceUpdateColor(color) {
        this.updateColorAnimation(color)
    }

    waveInit() {
        this.isAnimate = true;
        this.originalColor = this.color.getHex();
        this.previousColor = this.color.getHex();
        if(typeof this.animation !== "undefined" && this.animation.isActive()) {
            this.animation.kill();
        }
    }

    waveAnimation(color) {
        if(this.previousColor === color) return;
        this.previousColor = color;
        const clearTile = color === false;
        let color_initial = this.color;
        let color_final = clearTile ? new Color(this.originalColor) : new Color(color);

        gsap.to(color_initial, {
            duration: 0.03,
            r: color_final.r,
            g: color_final.g,
            b: color_final.b,
            ease: Linear.easeNone,
            onUpdate: () => {
                this.material.uniforms.color1.value = new Color(color_initial).lerp(this.colorWhite, 0.8);
                this.material.uniforms.color2.value = color_initial;
            },
            onComplete: () => {
                if(clearTile) {
                    this.isAnimate = false;
                }
            }
        });
    }
}

export default DiscoTile
