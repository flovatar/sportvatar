<x-guest-layout>

    <style type="text/css">
        .mosaic-container {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
        }
        .mosaic-container .img-wrap{
            width: 5%;
            margin: 0 -2.5% -1.5% 0;
        }
    </style>
    <div class="mosaic-container">

            <?php for($i=1; $i <= 500; $i++) { ?>
                <div class="img-wrap no-decor">
                    <img src="https://images.sportvatar.com/sportvatar/svg/{{ $i }}.svg" />
                </div>
            <?php } ?>
    </div>

    @include('partials/powered-by')
</x-guest-layout>
