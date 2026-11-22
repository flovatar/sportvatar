 <div>
    @if ($paginator->hasPages())

         <ul class="flex justify-center items-center text-md font-staatliches leading-none text-white text-opacity-50 text-center pt-10">

            @if ($paginator->onFirstPage())
                 <li><span class="transition-colors block w-38 text-white text-20 icon-chevron-left"></span></li>
            @else
                 <li><a href="{{ $paginator->previousPageUrl() }}" class="transition-colors block w-38 text-white text-20 icon-chevron-left  hover:text-yellow-100"></a></li>
            @endif

                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li><span class="transition-colors block w-38">...</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li wire:key="paginator-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span class="block w-38 text-yellow-100">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="transition-colors block w-38 hover:text-yellow-100">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

            @if ($paginator->hasMorePages())
                    <li><a href="{{ $paginator->nextPageUrl() }}" class="transition-colors block w-38 text-white text-20 icon-chevron-right  hover:text-yellow-100"></a></li>
            @else
                    <li><span class="transition-colors block w-38 text-white text-20 icon-chevron-right"></span></li>
            @endif
        </ul>

    @endif
</div>
