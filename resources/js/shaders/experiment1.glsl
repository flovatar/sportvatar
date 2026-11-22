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
    vec3 cp = vec3(sin(cr), cos(cr),0.);
    vec3 cu = normalize( cross(cw,cp) );
    vec3 cv = normalize( cross(cu,cw) );
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
