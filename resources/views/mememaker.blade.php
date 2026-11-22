<x-guest-layout>
    <script>
        @if(isset($memes))
            window.memes = @json($memes);
        @endif
            @if(isset($memeTemplates))
            window.memeTemplates = @json($memeTemplates);
        @endif
    </script>
    <div class="meme-wraper"
         x-data="memeData(window.memeTemplates, window.memes)"
         x-init="initMeme($dispatch); $nextTick(() => { refreshInteractive() })"
         @sportvatars-and-sales-loaded.window="loadSportvatarsAndSales($event.detail.collections)"
         @user-loaded.window="getSportvatarsAndSales()"
    >
        <div class="container">
            <div class="meme-templates">
                <div class="title-row">
                    <h2>Meme Templates</h2>
                </div>
                <div class="meme-slider-wrap">
                    <div class="btn-prev"><i class="icon-chevron-left"></i></div>
                    <div class="meme-slider">
                        <ul class="list">
                            <li>
                                <label for="memeFileUpload" class="upload-btn">Import</label>
                                <input type="file" id="memeFileUpload" name="memeFileUpload" accept="image/*" />
                            </li>
                            <template x-for="memeTemplate in filteredMemeTemplates" :key="memeTemplate.name">
                                <li @click="curMeme = memeTemplate" class="meme-template-item">
                                    <img x-bind:src="'/images/mememaker/'+memeTemplate.image" alt="">
                                </li>
                            </template>
                            <li x-show="maxTemplates < memeTemplates.length">
                                <button class="upload-btn" @click.prevent.stop="maxTemplates += 50">See More</button>
                            </li>
                        </ul>
                    </div>
                    <div class="btn-next"><i class="icon-chevron-right"></i></div>
                </div>
            </div>

            <div x-show="showMemeC()">
                <p x-text="memeC()" class="meme-p"></p>
            </div>

            <div class="meme-maker">
                <div class="col-l">
                    <div id="meme-output" class="meme-output">
                        <template x-if="curMeme">
                            <img class="meme-bg-image" x-bind:src="uploadedMemeImage ? uploadedMemeImage : '/images/mememaker/'+curMeme.image" alt="">
                        </template>

                        <template x-for="memeText in memeTexts" :key="memeText.id">
                            <div class="box meme-draggable" x-show="memeText.text != ''">
                                <span class="meme-text-overlay" x-text="memeText.text"></span>
                            </div>
                        </template>
                        <template x-for="memeSportvatar in memeSportvatars" :key="memeSportvatar.list_id">
                            <div class="box meme-resizable meme-sportvatar-box">
                                <img x-bind:src="getResizedSvg(memeSportvatar.svg, memeSportvatar.width)" class="sportvatar-meme">
                            </div>
                        </template>
                        <template x-for="memeImage in memeImages" :key="memeImage.id">
                            <div class="box meme-resizable">
                                <img x-bind:src="'/images/mememaker-images/'+memeImage.image+'.png'">
                            </div>
                        </template>

                        <template x-if="uploadedStickerImage">
                            <div class="box meme-resizable">
                                <img x-bind:src="uploadedStickerImage" alt="">
                            </div>
                        </template>

                        <img class="meme-watermark-image" src="/images/logo-horizontal-pink-medium.png" alt="">
                    </div>
                </div>
                <div class="col-r">
                    <div class="meme-editor">
                        <div class="tabwrap">
                            <ul class="tabset">
                                <li><a href="#" class="active" data-tab="add-text">Add Text</a></li>
                                <li><a href="#" data-tab="add-sportvatar">Add Sportvatar</a></li>
                                <li><a href="#" data-tab="add-image">Add Image</a></li>
                            </ul>
                            <div id="add-text" class="content-box">
                                <template x-for="memeText in memeTexts" :key="memeText.id">
                                    <div class="row">
                                        <strong class="title" x-text="'Text #'+memeText.id"></strong>
                                        <div class="panel">
                                            <div class="input-wrap">
                                                <input x-model="memeText.text" class="input-text" type="text">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div class="more-option-row">
                                    <a href="#" class="link" @click.prevent.stop="addText()">+ Add Text</a>
                                </div>
                            </div>
                            <div id="add-sportvatar" class="content-box hidden">
                                <div class="custom-scrollbar-wrap">
                                    <div class="custom-scrollbar">
                                        <ul class="sportvatar-list">
                                            <template x-for="sportvatar in sportvatars" :key="sportvatar.id">
                                                <li @click.prevent.stop="addSportvatar(sportvatar, $event)">
                                                    <img x-bind:src="'/api/image/nobg/'+sportvatar.id">
                                                </li>
                                            </template>
                                            <template x-for="sportvatarSale in sportvatarSales" :key="sportvatarSale.id">
                                                <li @click.prevent.stop="addSportvatar(sportvatar, $event)">
                                                    <img x-bind:src="'/api/image/nobg/'+sportvatarSale.id">
                                                </li>
                                            </template>
                                        </ul>
                                        <div x-show="sportvatars.length == 0 && sportvatarSales.length == 0" class="meme-connect-wallet">
                                            <button @click.prevent.stop="fcl.signUp()" class="btn"><span class="inner">Connect Wallet</span></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="add-image" class="content-box hidden">
                                <div class="custom-scrollbar-wrap">
                                    <div class="custom-scrollbar">
                                        <ul class="sportvatar-list">
                                            <li>
                                                <label for="memeStickerUpload" class="upload-btn">Import</label>
                                                <input type="file" id="memeStickerUpload" name="memeStickerUpload" accept="image/*" />
                                            </li>
                                            <template x-for="extraImage in extraImages" :key="extraImage">
                                                <li @click.prevent.stop="addExtraImage(extraImage)">
                                                    <img x-bind:src="'/images/mememaker-images/'+extraImage+'.png'">
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="foot-block">
                            <span class="tip" x-show="false"><b>Pro-tip:</b> Mauris cursus mattis molestie a <a href="#">iaculis</a> at erat. Morbi blandit <a href="#">cursus</a> risus at ultrices.</span>
                            <div class="foot-btns">
                                <a href="#" @click.prevent.stop="downloadMeme()" class="btn btn-sm btn-green"><span class="inner">Download Meme</span></a>
                                <a href="#" @click.prevent.stop="randomizeMemeTemplate()" class="link link-green">Randomize</a>
                                <a href="#" @click.prevent.stop="resetMemes()" class="link link-reset">Reset</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="meme-templates" x-show="memes.length > 0">
                <div class="title-row">
                    <h2>Latest Memes</h2>
                </div>
                <div class="meme-slider-wrap slider-lg">
                    <div class="btn-prev"><i class="icon-chevron-left"></i></div>
                    <div class="meme-slider">
                        <ul class="list">
                            <template x-for="meme in memes" :key="meme.name">
                                <li>
                                    <img x-bind:src="'/storage/meme/'+meme.image" alt="">
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div class="btn-next"><i class="icon-chevron-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    @include('partials/powered-by')

</x-guest-layout>
