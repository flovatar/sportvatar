{{-- Download buttons for an item: best available render (browser > server > legacy CDN). GIF/MP4 only when they exist.
     Params: $dlType (render type), $dlId (flow id), $dlName (file name stem), $dlClass (button classes). Item-level data only (cache-safe). --}}
@foreach(\App\Support\ItemImages::downloads($dlType, $dlId) as $dlExt => $dlHref)
<a href="{{ $dlHref }}" download="{{ $dlName }}.{{ $dlExt }}" target="_blank" rel="noopener" class="{{ $dlClass ?? 'btn btn-sm btn-lilac' }}"><span class="inner">{{ strtoupper($dlExt) }}</span></a>
@endforeach
