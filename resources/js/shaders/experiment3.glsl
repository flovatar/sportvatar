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
