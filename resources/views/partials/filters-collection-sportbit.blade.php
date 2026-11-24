<!--div class="filter-panel-wrap">
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
                <li x-show="searchCat != 'all'">
                    <div class="filter-tag">
                        <div class="inner-tag">
                            <span>Category</span>
                            <strong x-text="searchCat"></strong>
                            <a href="#" @click.prevent.stop="searchCat = 'all'" class="icon-cross02"></a>
                        </div>
                        <i class="triangle"></i>
                    </div>
                </li>
                <li x-show="searchCat2 != 'all'">
                    <div class="filter-tag">
                        <div class="inner-tag">
                            <span>Flobit Type</span>
                            <strong x-text="searchCat2"></strong>
                            <a href="#" @click.prevent.stop="searchCat2 = 'all'" class="icon-cross02"></a>
                        </div>
                        <i class="triangle"></i>
                    </div>
                </li>
                <li x-show="searchCat2 != 'all' && searchTemplate != 'all'">
                    <div class="filter-tag">
                        <div class="inner-tag">
                            <span>Flobit</span>
                            <strong x-text="getTemplateName(searchTemplate)"></strong>
                            <a href="#" @click.prevent.stop="searchTemplate = 'all'" class="icon-cross02"></a>
                        </div>
                        <i class="triangle"></i>
                    </div>
                </li>
                <li x-show="minPrice || maxPrice">
                    <div class="filter-tag">
                        <div class="inner-tag">
                            <span>Price</span>
                            <strong x-text="getPriceRange()"></strong>
                            <a href="#" @click.prevent.stop="minPrice = null; maxPrice = null;" class="icon-cross02"></a>
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
                <option value="pricelow">Price - <small>Lowest</small></option>
                <option value="pricehigh">Price - <small>Highest</small></option>
            </select>
        </div>
        <div class="select-wrap md collection-search">
            <span class="label">{{ __('Search') }}</span>
            <input type="search" x-model.debounce.300ms="searchText" placeholder="{{ __('Name or #id') }}" autocomplete="off" style="background:transparent;border:0;border-bottom:1px solid currentColor;color:inherit;min-width:9em;">
        </div>
        <span class="item-no"><span x-text="totalResults"></span> <small>items</small></span>
    </div>
    <div class="filter-panel filter-drop" x-show="showFilterPanel">
        <div class="select-wrap md">
            <span class="label">Category</span>
            <select x-model="searchCat">
                <option value="all">Everything</option>
                <option value="sportvatar">Sportvatar</option>
                @foreach(config('sportvatar.marketplace_categories') as $catKey => $catVal)
                    <option value="{{$catKey}}">{{ $catVal }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="searchCat == 'flobits'" class="select-wrap md">
            <span class="label">Flobit</span>
            <select x-model="searchCat2">
                <option value="all">Everything</option>
                <option value="hat">Hat</option>
                <option value="eyeglasses">Eyeglasses</option>
                <option value="accessory">Accessory</option>
                <option value="background">Background</option>
            </select>
        </div>
        <div x-show="searchCat2 != 'all' && searchCat == 'flobits'" class="select-wrap lg">
            <span class="label">Type</span>
            <select x-model="searchTemplate">
                <option value="all">All</option>
                <template x-for="template in filteredTemplates()" x-key="template.flow_id">
                    <option x-bind:value="template.flow_id" x-text="template.name"></option>
                </template>
            </select>
        </div>
        <div x-show="searchCat != 'flame' && searchTemplate == 'all'" class="select-wrap md">
            <span class="label">Rarity</span>
            <select x-model="searchRarity">
                <option value="all">All</option>
                <template x-if="searchCat != 'boost'">
                    <option value="common">Common</option>
                </template>
                <option value="rare">Rare</option>
                <option value="epic">Epic</option>
                <option value="legendary">Legendary</option>
            </select>
        </div>
        <div class="select-wrap sm">
            <span class="label">Min price</span>
            <div class="input-wrap">
                <input type="text" @input.debounce.500ms="searchResults" x-model="minPrice">
            </div>
        </div>
        <div class="select-wrap sm">
            <span class="label">Max price</span>
            <div class="input-wrap">
                <input type="text" @input.debounce.500ms="searchResults" x-model="maxPrice">
            </div>
        </div>
        <a href="" @click.prevent.stop="resetFilters()" class="btn btn-icon btn-gray btn-cross">
            <div class="inner">
                <i class="icon-cross02"></i>
            </div>
        </a>
    </div>
</div -->
