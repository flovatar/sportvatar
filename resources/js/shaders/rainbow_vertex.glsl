varying vec2 vUv;
uniform float time;

float map (float x, float a, float b, float c, float d) {
    float res = (x - a) * (d - c) / (b - a) + c;
    return res;
}

void main()
{
    vec3 newposition = position;
    float PI = 3.1415925;
    vUv = uv;

//    newposition.z += 10. * sin(( newposition.x * 100.)*2.*PI);
//    newposition.z -= .8 * sin(( newposition.x/3. + 2.5)*.6*PI);
//    newposition.y += sin(uv.x+2.) * 1.;
//    newposition.y *= 2. + 1. * sin(( abs(uv.x) + .5)*1.*PI);
//    newposition.y -= sin(uv.x * PI);
//    newposition.y -= position.y / 2.;
    float a = -10.9;
    float b = 0.0;
    float c = 0.0;
    float map_x = map(position.x, -6., 5.5, 0.1, 1.);
//    float x = smoothstep(-1.0, .5, uv.x);
    float x = uv.x - .5;
//    newposition.y += a * pow(x, 2.) + b * x + c;
    newposition.y *= (position.x + 5.8) / 3.7 + pow(map_x, 2.) * 1.;
    newposition.y += a * pow(x, 2.) + b * x + c;
//    newposition.y += 1. + sin((uv.x * 30. + time) / 8.) ;

    gl_Position = projectionMatrix * modelViewMatrix * vec4( newposition, 1.0 );
}
