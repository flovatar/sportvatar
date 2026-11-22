<div class="filter-panel-wrap">
    <div class="filter-panel">
        <div class="filter-btn">
            <a href="#" @click.prevent.stop="showFilterPanel = !showFilterPanel" class="btn btn-sm btn-gray"> <span class="inner"><i class="icon-funel"></i> Filter</span></a>
            <span x-show="getFiltersCount()" class="filter-no" x-text="getFiltersCount()"></span>
        </div>
        <div class="filter-tag-wrap" x-bind:class="!getFiltersCount() ? 'hide-filters' : ''">
            <ul class="tag-list">
                <li x-show="searchRarity != 'all'">
                    <div class="filter-tag" x-bind:class="searchRarity">
                        <div class="inner-tag">
                            <span>Rarity</span>
                            <strong x-text="searchRarity"></strong>
                            <a href="#" @click.prevent.stop="searchRarity = 'all'" class="icon-cross02"></a>
                        </div>
                        <i class="triangle"></i>
                    </div>
                </li>
                <li x-show="false && searchCat != 'all' && searchTemplate != 'all'">
                    <div class="filter-tag">
                        <div class="inner-tag">
                            <span>Category</span>
                            <strong x-text="searchCat"></strong>
                            <a href="#" @click.prevent.stop="searchCat = 'all'; searchTemplate = 'all';" class="icon-cross02"></a>
                        </div>
                        <i class="triangle"></i>
                    </div>
                </li>
                <li x-show="false && searchCat != 'all' && searchTemplate != 'all'">
                    <div class="filter-tag">
                        <div class="inner-tag">
                            <span>Trait</span>
                            <strong x-text="getTemplateName(searchTemplate)"></strong>
                            <a href="#" @click.prevent.stop="searchTemplate = 'all'" class="icon-cross02"></a>
                        </div>
                        <i class="triangle"></i>
                    </div>
                </li>
                <li x-show="minMint || maxMint">
                    <div class="filter-tag">
                        <div class="inner-tag">
                            <span>Mint #</span>
                            <strong x-text="getMintRange()"></strong>
                            <a href="#" @click.prevent.stop="minMint = null; maxMint = null;" class="icon-cross02"></a>
                        </div>
                        <i class="triangle"></i>
                    </div>
                </li>

            </ul>
        </div>
        <span class="divider"></span>
        <div class="select-wrap select-sort">
            <span class="label">Sort by</span>
            <select name="sortby" x-model="sortBy" value="">
                <option value="datenew">Date - <small>Most recent</small></option>
                <option value="dateold">Date - <small>Oldest</small></option>
                <option value="raritylow">Ability - <small>Lowest</small></option>
                <option value="rarityhigh">Ability - <small>Highest</small></option>
            </select>
        </div>
        <span class="item-no"><span x-text="totalResults"></span> <small>items</small></span>
    </div>
    <div class="filter-panel filter-drop" x-show="showFilterPanel">

        <div class="select-wrap md">
            <span class="label">Rarity</span>
            <select x-model="searchRarity">
                <option value="all">All</option>
                <option value="rare">Rare</option>
                <option value="epic">Epic</option>
                <option value="legendary">Legendary</option>
            </select>
        </div>
        <div class="select-wrap md" x-show="false">
            <span class="label">Category</span>
            <select x-model="searchCat">
                <option value="all">Everything</option>
                <option value="body">Body</option>
                <option value="hair">Hair</option>
                <option value="eyes">Eyes</option>
                <option value="nose">Nose</option>
                <option value="mouth">Mouth</option>
                <option value="facialHair">Facial Hair</option>
                <option value="clothing">Clothing</option>
            </select>
        </div>


        <div x-show="false && searchCat != 'all'" class="select-wrap lg">
            <span class="label">Trait</span>
            <select x-model="searchTemplate">
                <option value="all">All</option>
                <template x-for="template in filteredTemplates()" x-key="template.flow_id">
                    <option x-bind:value="template.flow_id" x-text="template.name"></option>
                </template>
            </select>
        </div>
        <div class="select-wrap sm">
            <span class="label">Min Mint</span>
            <div class="input-wrap">
                <input type="text" @input.debounce.500ms="searchResults" x-model="minMint">
            </div>
        </div>
        <div class="select-wrap sm">
            <span class="label">Max Mint</span>
            <div class="input-wrap">
                <input type="text" @input.debounce.500ms="searchResults" x-model="maxMint">
            </div>
        </div>
        <button @click.prevent.stop="showFilterPanel = false" class="btn btn-icon btn-gray btn-cross">
            <div class="inner">
                <i class="icon-cross02"></i>
            </div>
        </button>
    </div>
</div>
