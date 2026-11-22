
<div class="col">
    <template x-if="component.count && groupComponent(component)">
        <a href="#" @click.prevent.stop="groupSportbits[component.templateId] = false" class="card stack" x-bind:class="component.rarity">
            <div class="img-hold" x-bind:class="getComponentOutlineClass(component)">
                <template x-if="component.layer != 13">
                    <img x-bind:src="getTemplateUrl(component.templateId)" alt="">
                </template>
                <template x-if="component.layer == 13">
                    <span x-text="parseNumberSportbit(component.name)" class="number-thumb white"></span>
                </template>
            </div>
            <div class="wrap">
                <dl>
                    <dt x-text="component.category"></dt>
                    <dd x-text="component.name"></dd>
                    <dt>Rarity</dt>
                    <dd x-text="component.rarity"></dd>
                    <dt>Count</dt>
                    <dd x-text="component.count"></dd>
                    <dt>On sale</dt>
                    <dd x-text="component.salesCount"></dd>
                </dl>
            </div>
            <span x-show="component.salesCount>0" class="icon-cart"></span>
        </a>
    </template>
    <template x-if="!(component.count && groupComponent(component))">
            <a x-bind:href="'/components/'+component.id+'/'+address" class="card" x-bind:class="getRarityColorComponent(component)">
                <div class="img-hold component" x-bind:class="getComponentOutlineClass(component)">
                    <template x-if="component.layer != 13">
                        <img x-bind:src="getTemplateUrl(component.templateId)" alt="">
                    </template>
                    <template x-if="component.layer == 13">
                        <span x-text="parseNumberSportbit(component.name)" class="number-thumb white"></span>
                    </template>

                    <div class="side-items">
                        <button x-bind:href="'/components/'+component.id+'/'+address" class="item" x-show="component.price"><i class="icon-cart"></i></button>
                    </div>
                </div>
                <div class="wrap">
                    <dl>
                        <dt x-text="getComponentLayer(component)"></dt>
                        <dd x-text="getComponentName(component)"></dd>
                        <dt>Rarity</dt>
                        <dd x-text="component.rarity"></dd>
                        <dt>Mint</dt>
                        <dd x-text="getComponentMint(component)"></dd>
                        <dt>Sale Price</dt>
                        <dd x-html="component.price ? formatPrice(component.price)+' <span>Flow</span>' : 'Not for sale'" x-bind:class="component.price ? '' : 'text-red'"></dd>
                    </dl>
                </div>
            </a>
    </template>
</div>



