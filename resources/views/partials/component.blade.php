<template x-if="!groupComponent(component)">
    <div class="col">
        <a x-bind:href="'/components/'+component.id+'/'+address" class="card" x-bind:class="getRarityColorComponent(component)">
            <div class="img-hold component" x-bind:class="(component.layer == 13 || getComponentName(component) == 'Flame') ? '' : 'builder-component-thumb'">
                <!-- img x-bind:src="component.metadata ? '/api/image/template/'+component.metadata.templateId : '/api/image/template/'+component.templateId" / -->
                <template x-if="component.layer != 13">
                    <img x-bind:src="getTemplateUrl(component.templateId)" alt="">
                </template>
                <template x-if="component.layer == 13">
                    <span x-text="parseNumberSportbit(component.name)" className="number-thumb white"></span>
                </template>
            </div>
            <div class="wrap">
                <dl>
                    <dt x-text="getComponentLayer(component)"></dt>
                    <dd x-text="getComponentName(component)"></dd>
                    <dt>Rarity</dt>
                    <dd x-text="component.metadata ? component.metadata.rarity : component.rarity"></dd>
                    <dt>Mint</dt>
                    <dd x-text="getComponentMint(component)"></dd>
                    <dt>Sale Price</dt>
                    <dd x-html="component.price ? formatPrice(component.price)+' <span>Flow</span>' : 'Not for sale'" x-bind:class="component.price ? '' : 'text-red'"></dd>
                </dl>
            </div>
            <span x-show="component.price" class="icon-cart"></span>
        </a>
    </div>
</template>
