precision highp float;

uniform float time;
uniform vec2 resolution;
varying vec2 vUv;

float rayStrength(vec2 raySource, vec2 rayRefDirection, vec2 coord, float seedA, float seedB, float speed)
{
    vec2 sourceToCoord = coord - raySource;
    float cosAngle = dot(normalize(sourceToCoord), rayRefDirection);

    return clamp(
    (0.45 + 0.20 * sin(cosAngle * seedA + time * speed)) +
    (0.2 + 0.2 * cos(-cosAngle * seedB + time * speed)),
    0.0, 1.0) *
    clamp((600. - length(sourceToCoord)) / 600., 0.5, 0.05);
}
float sdCircle( in vec2 p, in float r ) {
    return length(p)-r;
}
void main( void ) {
    // vec2 p = vec2(vUv.x - vUv.x * 0.5, vUv.y - vUv.y * 0.5);
    //vec2 p = vec2((vUv.x - .5)*3., (vUv.y - .5)*3.);
    vec2 uv = vUv.xy / resolution.xy;
    vec2 coord = vec2(vUv.x, vUv.y);


    vec2 rayPos1 = vec2((vUv.x + 0.5 )* 0.5, vUv.y * -0.01);
    vec2 rayRefDir1 = normalize(vec2(1.0, -0.116));
    float raySeedA1 = 36.2214;
    float raySeedB1 = 21.11349;
    float raySpeed1 = 1.9;

    vec2 rayPos2 = vec2((vUv.x + 0.5) * 0.5, vUv.y * -0.02);
    vec2 rayRefDir2 = normalize(vec2(1.0, 0.241));
    const float raySeedA2 = 22.39910;
    const float raySeedB2 = 18.0234;
    const float raySpeed2 = 1.6;

    vec4 rays1 =
    vec4(1.0, 1.0, 1.0, 1.0) *
    rayStrength(rayPos1, rayRefDir1, coord, raySeedA1, raySeedB1, raySpeed1);

    vec4 rays2 =
    vec4(1.0, 1.0, 1.0, 1.0) *
    rayStrength(rayPos2, rayRefDir2, coord, raySeedA2, raySeedB2, raySpeed2);

    vec4 color = rays1 * 0.5 + rays2 * 0.4;

    vec2 p = vec2((vUv.x - .5)*3., (vUv.y - .5)*3.);

    float d = sdCircle(p, 0.5);

    float brightness = 1.0 - (coord.y);
    color.r *= 0.6 + (brightness * 0.8);
    color.g *= 0.8 + (brightness * 0.9);
    color.b *= 0.9 + (brightness * 0.9);
    color.a = color.a * smoothstep(0., 1., 1. - d);
    gl_FragColor = color;
}
