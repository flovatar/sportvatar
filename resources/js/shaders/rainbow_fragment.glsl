uniform float time;

varying vec2 vUv;
uniform vec2 resolution;

void main( void ) {

    vec2 uv = vUv.xy;
//    float widthHeightRatio = resolution.x/resolution.y;
    //uv.y/=widthHeightRatio;


    //Wave/////////////////////
    float slowing = 3.0;
    float frequency = .1;
    float amplitudeLim = 2.;

    //Colors///
    float RGBTime = 2.;
    float colorWidth = 6.;

    float curvature = -pow(uv.x-0.5,2.)/0.5+0.5;
    float wave = sin((uv.x+time/slowing)*frequency)/amplitudeLim;
    float line = wave * curvature + 0.25;
    // line = curvature;
    vec2 centre = vec2(uv.x,line);
    vec2 pos = centre - uv;
    // Equation 1/x gives a hyperbola which is a nice shape to use for drawing glow as
    // it is intense near 0 followed by a rapid fall off and an eventual slow fade
    float dist = 1./length(pos);
    // Dampen the glow to control the radius
    dist *= 0.2;

    // Raising the result to a power allows us to change the glow fade behaviour
    // See https://www.desmos.com/calculator/eecd6kmwy9 for an illustration
    // (Move the slider of m to see different fade rates)
    dist = pow(dist, 0.8);
    RGBTime *= time / 10.;
    float tmp = -RGBTime + uv.x * colorWidth;
    vec3 col = dist * vec3(sin(tmp)/2.+0.5, -sin(tmp)/2.+0.5, cos(tmp)/2.+0.5);
    col = 1.0 - exp( -col );
//    col = vec3(1., .0, 1.);

    vec2 r = -1.0 + 2.0*(uv);
    float vb = max(abs(r.x), abs(r.y));
    float acol = (0.25 + 0.85*(1.0-exp(-(1.0-vb)*30.0)));

    gl_FragColor = vec4(col, acol);
}
