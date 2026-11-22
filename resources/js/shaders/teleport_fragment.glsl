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
    float noise_desnity = 100.0;
    vec4 color = vec4(0.0, 1.02, 1.2, 1.0);

    float noise = noise(vUv * noise_desnity) * vUv.y;

    float d1 = step(progress, noise);
    float d2 = step(progress - beam_size, noise);

    vec3 beam = vec3(d2 - d1) * color.rgb;

    vec4 tex = texture2D(texture1, vUv);
    tex.rgb += beam;
    tex.a *= d2;

    gl_FragColor = tex;
}
