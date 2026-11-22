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
