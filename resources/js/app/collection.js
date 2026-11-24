import Alpine from "alpinejs";

/*
 * Big wallets (contract/admin accounts with thousands of Sportbits): every grid rendered in full (all tabs are x-show),
 * list functions re-filtered/re-sorted the whole reactive array on every call and template lookups were linear scans in
 * the sort comparator. Now: O(1) template lookups, memoised filter/sort results and pages of PAGE_SIZE cards per grid.
 */
const PAGE_SIZE = 100;
const memo = new Map();
function memoized(name, key, fn){
    const hit = memo.get(name);
    if(hit && hit.key === key){
        return hit.value;
    }
    const value = fn();
    memo.set(name, {key: key, value: value});
    return value;
}
let templateMapSource = null;
let templateMap = new Map();
function templateByFlowId(templates, id){
    const raw = Alpine.raw(templates);
    if(raw !== templateMapSource){
        templateMapSource = raw;
        templateMap = new Map();
        _.each(raw, t => { templateMap.set(parseInt(t.flow_id, 10), t); });
    }
    return templateMap.get(parseInt(id, 10));
}

Alpine.data('collectionData', (address, templates, saleOffers) => ({
    templates: templates,
    address: address,
    sportvatars: [],
    sportvatarIds: [],
    components: [],
    packs: [],
    saleOffers: saleOffers,
    claimables: {},
    componentsTemp: [],
    showPackAnimation: false,
    showAirdropPackAnimation: false,
    showPackOpening: false,
    showAirdropPackOpening: false,
    fadePackOpening: false,
    fadeAirdropPackOpening: false,
    selectedPackType: null,
    groupSportbits: {},
    sportbitsCount: {},
    showPackModal: false,
    fadePackCompleted: false,
    hidePackOpenBtn: false,
    showAirdropPackModal: false,
    fadeAirdropPackCompleted: false,
    hideAirdropPackOpenBtn: false,
    selectedPackToOpen: null,
    componentsPackTemp: [],
    packNewComponents: [],
    collectionSportvatarFailed: false,
    loadingSportvatarFromIdsStarted: false,
    activeTab: 'sportvatar',
    showAirdropModal: false,
    minMint: null,
    maxMint: null,
    sortBy: 'datenew',
    searchRarity: 'all',
    searchCat: 'all',
    searchCat2: 'all',
    searchTemplate: 'all',
    showFilterPanel: false,
    // pagination + text search for the grids (PAGE_SIZE cards per page)
    pages: {sportvatar: 1, flame: 1, sportbit: 1},
    pageSize: PAGE_SIZE,
    searchText: '',
    dataVersion: 0,
    init(){
        let self = this;
        ['searchRarity', 'searchCat', 'searchCat2', 'searchTemplate', 'minMint', 'maxMint', 'sortBy', 'searchText'].forEach(prop => {
            this.$watch(prop, () => { self.resetPages(); });
        });
        this.$watch('showPackModal', () => {
            if(!self.showPackModal){
                self.showPackAnimation = false;
                self.showPackOpening = false;
                self.fadePackOpening = false;
                self.selectedPackType = null;
                self.selectedPackToOpen = null;
                self.componentsPackTemp = [];
                self.packNewComponents = [];
                self.fadePackCompleted = false;
                self.hidePackOpenBtn = false;
            }
        });
        this.$watch('showAirdropModal', () => {
            if(!self.showPackModal){
                self.showPackAnimation = false;
                self.showPackOpening = false;
                self.fadePackOpening = false;
                self.selectedPackType = null;
                self.selectedPackToOpen = null;
                self.componentsPackTemp = [];
                self.packNewComponents = [];
                self.fadePackCompleted = false;
                self.hidePackOpenBtn = false;
            }
        });

        this.$watch('activeTab', () => {
            this.$nextTick(() =>{
                window.history.pushState({}, null, '#tab='+self.activeTab);

                if(window.collectionFrameSlider){
                    window.collectionFrameSlider.reload();
                }
                this.resetFilterParams();
                setTimeout(function(){
                    if(window.collectionFrameSlider){
                        window.collectionFrameSlider.reload();
                    }
                    self.resetFilterParams();
                },500);
            });
        });
        this.$nextTick(() =>{
            if(window.collectionFrameSlider){
                window.collectionFrameSlider.reload();
            }
            this.resetFilterParams();
        });




        if(window.location.hash && window.location.hash != '#') {
            let hash = window.location.hash;
            hash = hash.replace('#', '');
            let params = _.split(hash, '&');
            _.each(params, function(param) {
                let paramArr = _.split(param, '=');
                if (paramArr.length == 2) {
                    if(paramArr[0] == 'tab'){
                        if(paramArr[1] == 'sportvatar' || paramArr[1] == 'flame' || paramArr[1] == 'sportbit' || paramArr[1] == 'pack'){
                            self.activeTab = paramArr[1];
                        }
                    }
                }
            });
        }
    },
    resetFilterParams(){
        this.sortBy = this.activeTab == 'sportbit' ? 'namelow' : 'datenew';
        this.searchRarity = 'all';
        this.searchCat = 'all';
        this.searchCat2 = 'all';
        this.searchTemplate = 'all';
        this.showFilterPanel = false;
        this.minMint = null;
        this.maxMint = null;
        this.searchText = '';
        this.resetPages();
    },
    resetPages(){
        _.each(_.keys(this.pages), key => { if(this.pages[key] != 1){ this.pages[key] = 1; } });
    },
    // cache key of everything the list filters read (reading the props here also registers them as Alpine dependencies)
    filterKey(){
        return [this.dataVersion, this.sportvatars.length, this.components.length, this.searchRarity, this.searchCat, this.searchCat2,
            this.searchTemplate, this.minMint, this.maxMint, this.sortBy, this.searchText, this.activeTab, JSON.stringify(this.groupSportbits)].join('|');
    },
    matchesText(item){
        let q = (this.searchText || '').trim().toLowerCase();
        if(!q){
            return true;
        }
        let meta = (item && item.metadata && item.metadata.layer !== undefined) ? item.metadata : item;
        let tpl = meta && meta.templateId ? this.getTemplateById(meta.templateId) : null;
        let hay = [item && item.name ? item.name : '', tpl ? tpl.name : '', meta && meta.category ? meta.category : '', meta && meta.sport ? meta.sport : '',
            item && item.id != null ? '#'+item.id : '', meta && meta.mint != null ? '#'+meta.mint : ''].join(' ').toLowerCase();
        return hay.indexOf(q) !== -1;
    },
    fullList(tab){
        switch(tab){
            case 'sportvatar': return this.filteredSportvatars();
            case 'flame': return this.componentsFlame();
            case 'sportbit': return this.filteredSportbits();
        }
        return [];
    },
    pageCount(tab){
        return Math.max(1, Math.ceil(this.fullList(tab).length / this.pageSize));
    },
    currentPage(tab){
        return Math.min(Math.max(1, this.pages[tab] || 1), this.pageCount(tab));
    },
    paged(tab){
        let list = this.fullList(tab);
        let page = Math.min(Math.max(1, this.pages[tab] || 1), Math.max(1, Math.ceil(list.length / this.pageSize)));
        return list.slice((page - 1) * this.pageSize, page * this.pageSize);
    },
    setPage(tab, page, el){
        this.pages[tab] = Math.min(Math.max(1, page), this.pageCount(tab));
        if(el && el.closest){
            let grid = el.closest('[data-grid]');
            if(grid && grid.getBoundingClientRect().top < 0){
                grid.scrollIntoView({block: 'start'});
            }
        }
    },
    refreshClaimables(){
        window.getClaimables(this.address);
    },
    totalResults(){
        if(this.activeTab == 'sportvatar'){
            return this.filteredSportvatars().length;
        } else if(this.activeTab == 'sportbit'){
            return this.filteredSportbitsUngrouped().length;
        }
    },
    filteredSportvatars(){
        return memoized('sportvatar', this.filterKey(), () => this.computeFilteredSportvatars());
    },
    computeFilteredSportvatars(){
        if(!this.sportvatars){
            return [];
        }
        let self = this;
        let filtered = _.filter(this.sportvatars, item => {
            let check = self.matchesText(item);
            if(self.searchRarity != 'all' && self.searchRarity != self.getRarityText(item)){
                check = false;
            }
            if(self.searchTemplate != 'all'){
                let templateIds = self.getSportvatarTemplateIds(item);
                if(templateIds[self.searchCat] != self.searchTemplate){
                    check = false;
                }
            }
            if(self.minMint && item.id < self.minMint){
                check = false;
            }
            if(self.maxMint && item.id > self.maxMint){
                check = false;
            }
            return check;
        });

        return filtered.sort(function(l,r){
            return self.sortResultFunction(self, l, r);
        });
    },
    filteredSportbits(){
        return memoized('sportbit', this.filterKey(), () => this.computeFilteredSportbits());
    },
    computeFilteredSportbits(){
        let allSportbits = this.componentsSportbit() ? Alpine.raw(this.componentsSportbit()) : [];
        let self = this;

        allSportbits = allSportbits.concat(this.componentsStackedSportbit());

        let filtered = _.filter(allSportbits, item => {
            let check = self.matchesText(item);
            if(self.searchRarity != 'all' && self.searchRarity != item.rarity){
                check = false;
            }
            if(self.searchCat != 'all' && self.searchCat != item.category){
                check = false;
            }
            if(self.searchTemplate != 'all' && self.searchTemplate != item.templateId){
                check = false;
            }
            if(!item.count){
                if(self.minMint && item.mint < self.minMint){
                    check = false;
                }
                if(self.maxMint && item.mint > self.maxMint){
                    check = false;
                }
                if(self.groupComponent(item)){
                    check = false;
                }
            } else {
                if(!self.groupComponent(item)){
                    check = false;
                }
            }
            if(!(item.id || item.name)){
                check = false;
            }
            return check;
        });

        return filtered.sort(function(l,r){
            return self.sortResultFunction(self, l, r);
        });
    },
    filteredSportbitsUngrouped(){
        return memoized('sportbitUngrouped', this.filterKey(), () => this.computeFilteredSportbitsUngrouped());
    },
    computeFilteredSportbitsUngrouped(){
        let allSportbits = this.componentsSportbit() ? Alpine.raw(this.componentsSportbit()) : [];
        let self = this;

        let filtered = _.filter(allSportbits, item => {
            let check = self.matchesText(item);
            if(self.searchRarity != 'all' && self.searchRarity != item.rarity){
                check = false;
            }
            if(self.searchCat != 'all' && self.searchCat != item.category){
                check = false;
            }
            if(self.searchTemplate != 'all' && self.searchTemplate != item.templateId){
                check = false;
            }
            if(self.minMint && item.mint < self.minMint){
                check = false;
            }
            if(self.maxMint && item.mint > self.maxMint){
                check = false;
            }
            return check;
        });

        return filtered.sort(function(l,r){
            return self.sortResultFunction(self, l, r);
        });
    },
    sortResultFunction(self, l,r){
        let lId = parseInt(l.id, 10);
        let rId = parseInt(r.id, 10);
        if(self.sortBy == 'datenew'){
            return (lId > rId) ? -1 : ((lId < rId) ? 1 : 0);
        } else if(self.sortBy == 'dateold'){
            return (lId < rId) ? -1 : ((lId > rId) ? 1 : 0);
        } else if(self.sortBy == 'namelow' || self.sortBy == 'namehigh'){
            let lName = l.name ? l.name : self.getComponentName(l);
            let rName = r.name ? r.name : self.getComponentName(r);

            if(self.sortBy == 'namelow'){
                return (lName < rName) ? -1 : ((lName > rName) ? 1 : 0);
            } else {
                return (lName > rName) ? -1 : ((lName < rName) ? 1 : 0);
            }
        } else if(self.sortBy == 'raritylow' || self.sortBy == 'rarityhigh'){
            let rarityL = 0;
            let rarityR = 0;
            let rarityArr = {
                'common': 0,
                'rare': 1,
                'epic': 2,
                'legendary': 3
            }
            let rarityTempL = l.rarity;
            let rarityTempR = r.rarity;
            rarityL = rarityArr[rarityTempL];
            rarityR = rarityArr[rarityTempR];

            let returnVal = 0;
            if(self.sortBy == 'raritylow'){
                returnVal = (rarityL < rarityR) ? -1 : ((rarityL > rarityR) ? 1 : 0);
            } else {
                returnVal = (rarityL > rarityR) ? -1 : ((rarityL < rarityR) ? 1 : 0);
            }
            if(returnVal == 0){
                let lName = l.name ? l.name : self.getComponentName(l);
                let rName = r.name ? r.name : self.getComponentName(r);
                returnVal = (lName < rName) ? -1 : ((lName > rName) ? 1 : 0);
            }
            return returnVal;
        }
    },
    getSportvatarTemplateIds(sportvatar){
        return (sportvatar && sportvatar.layers) ? sportvatar.layers : [];
    },
    showGroupedSportbit(templateId){
        let result = _.find(this.filteredSportbits(), item => {
            return item.templateId == templateId;
        });

        return result && this.groupSportbits[templateId] ? true : false;
    },
    getSearchParams(){
        let params = {
            rarity: this.searchRarity,
            sortby: this.sortBy,
            mintmin: this.minMint,
            mintmax: this.maxMint,
            page: this.nextPage,
            category: this.searchCat,
            template: this.searchTemplate
        };
        let hashParams = this.getHashParams(params);

        if(Object.keys(hashParams).length > 0 || window.location.hash){
            window.writeHashParams(hashParams);
        }
        return params;
    },
    getHashParams(params){
        let hashParams = {};
        if(params.rarity != 'all'){
            hashParams.rarity = params.rarity;
        }
        if(params.sortby != 'datenew'){
            hashParams.sortby = params.sortby;
        }
        if(params.mintmin != null){
            hashParams.mintmin = params.mintmin;
        }
        if(params.mintmax != null){
            hashParams.mintmax = params.mintmax;
        }
        if(params.category != 'all' && params.template != 'all'){
            hashParams.category = params.category;
            hashParams.template = params.template;
        }
        return hashParams;
    },
    getFiltersCount() {
        let params = this.getSearchParams();
        params.sortby = 'datenew';
        params.page = 1;
        let hashParams = this.getHashParams(params);
        return Object.keys(hashParams).length;
    },
    getMintRange() {
        let output = '';
        if(this.minMint && this.maxMint){
            output = this.minMint + ' - ' + this.maxMint;
        } else if(this.minMint){
            output = this.minMint + ' - ∞';
        } else if(this.maxMint){
            output = '0 - ' + this.maxMint;
        } else {
            return '';
        }

        return output;
    },
    filteredTemplates(){
        if(this.searchCat != 'all'){
            return _.filter(this.templates, (template) => {
                return template.category == this.searchCat;
            }).sort(function(l,r){
                return (l.name > r.name) ? 1 : ((l.name < r.name) ? -1 : 0);
            });
        }
        return {};
    },
    getTemplateName(templateId){
        if(templateId == 'all' || !templateId){
            return '';
        }
        let template = _.find(this.templates, (template) => {
            return template.flow_id == parseInt(templateId, 10);
        });
        return template ? template.name : '';
    },
    sportvatarCountTotal(){
        return this.sportvatars.length;
    },
    sportbitCountTotal(){
        return this.componentsSportbit().length;
    },
    flameCountTotal(){
        return this.componentsFlame().length;
    },
    packCountTotal(){
        return this.packs.length;
    },
    isFlame(component){
        return component.layer == 0 ; // TODO: add also     && component.name == 'Sport Flame' && component.sport == 'flame';
    },
    componentsFlame(){
        let self = this;
        return memoized('componentsFlame', this.dataVersion + '|' + this.components.length + '|' + this.searchText, () => _.filter(Alpine.raw(this.components), function(component){
            return self.isFlame(component) && self.matchesText(component);
        }));
    },
    groupComponent(item){
        if(!this.isFlame(item)){
            return this.groupSportbits[item.templateId] && this.groupSportbits[item.templateId] === true && this.sportbitsCount[item.templateId] && this.sportbitsCount[item.templateId] > 1;
        }
    },
    showBodyOutline(component){
        return !this.isFlame(component);
    },
    loadClaimables(data){
        this.claimables = data;
    },
    loadCollections(data){
        //console.log(data);
        this.sportvatars = data.sportvatars; this.dataVersion++;
        this.packs = data.packs;
        this.componentsTemp = data.components;
        if(this.address == ''){
            this.address = data.address;
        }
        this.loadComponents();
    },
    airdropSportbits(){
        let totalSportbits = 0;
        if(this.claimables.sportbits){
            totalSportbits += this.claimables.sportbits.length;
        }
        if(this.claimables.walletComponents){
            totalSportbits += this.claimables.walletComponents.length;
        }
        return totalSportbits;
    },
    loadCollectionsPack(data){
        this.packs = data.packs;
        if(this.address == ''){
            this.address = data.address;
        }
    },
    loadCollectionsSportvatar(data){
        this.sportvatars = data.sportvatars; this.dataVersion++;
        if(this.address == ''){
            this.address = data.address;
        }
    },
    loadCollectionsSportbit(data){
        this.componentsTemp = data.components;
        if(this.address == ''){
            this.address = data.address;
        }

        if(this.showPackOpening){

            let self = this;
            this.packNewComponents = _.filter(this.componentsTemp, (item) => {
                let check = _.find(self.componentsPackTemp, (item2) =>{
                    return item2.id == item.id;
                });
                return !check;
            });
            self.fadePackOpening = true;

            setTimeout(function(){
                self.fadePackCompleted = true;
            },2000);
        }

        this.loadComponents();
    },
    loadCollectionsSportvatarIds(data){
        this.sportvatarIds = data.sportvatarIds;
        if(this.address == ''){
            this.address = data.address;
        }
        if(this.collectionSportvatarFailed && !this.loadingSportvatarFromIdsStarted){
            this.loadSportvatarFromIds();
        }
    },
    loadSportvatarFromIds(){
        this.loadingSportvatarFromIdsStarted = true;
        console.log('Load Sportvatars one by one in Collection');
        let self = this;
        let timeout = 0;
        _.each(this.sportvatarIds, (item) => {
            setTimeout(function(){
                window.getSportvatar(self.address, item);
            }, timeout);
            timeout += 150;
        });
    },
    loadSingleSportvatar(data){
        //console.log('single sportvatar loaded');
        //console.log(data);
        this.sportvatars.push(data.sportvatar);
        this.dataVersion++;
    },
    loadComponents(){
        let self = this;
        self.sportbitsCount = {};

        let collection = [];

        _.each(self.componentsTemp, function(item){
            collection.push(item);
            if(!self.isFlame(item)){
                self.sportbitsCount[item.templateId] = self.sportbitsCount[item.templateId] ? self.sportbitsCount[item.templateId] + 1 : 1;
                if(self.sportbitsCount[item.templateId] && self.sportbitsCount[item.templateId] > 1){
                    self.groupSportbits[item.templateId] = true;
                }
            }
        });

        let rawTemplates = Alpine.raw(self.templates);
        this.components = collection.sort(function (l,r){
            let lItem = l.metadata ? l.metadata : l;
            let rItem = r.metadata ? r.metadata : r;

            if(lItem.category == rItem.category){
                let lTemp = templateByFlowId(rawTemplates, lItem.templateId);
                let rTemp = templateByFlowId(rawTemplates, rItem.templateId);

                if(lTemp && rTemp) {
                    if (lTemp.name > rTemp.name) {
                        return 1;
                    } else if (lTemp.name < rTemp.name) {
                        return -1;
                    } else {
                        if(lItem.mint > rItem.mint){
                            return 1
                        } else if(lItem.mint < rItem.mint){
                            return -1
                        } else {
                            return 0;
                        }
                    }
                } else {
                    if(lItem.templateId > rItem.templateId){
                        return 1;
                    } else if(lItem.templateId < rItem.templateId){
                        return -1;
                    } else {
                        return 0;
                    }
                }
            } else {
                if(lItem.layer > rItem.layer){
                    return 1;
                } else {
                    return -1;
                }
            }
        });
        this.dataVersion++;
    },

    componentsSportbit(){
        let self = this;
        return memoized('componentsSportbit', this.dataVersion + '|' + this.components.length, () => _.filter(Alpine.raw(this.components), function(component){
            return !self.isFlame(component);
        }));
    },
    componentsStackedSportbit(){
        let collection = [];
        let self = this;
        _.each(this.groupSportbits, function(val, key){
            let temp = self.getTemplateById(key);
            collection.push({
                templateId: key,
                count: self.sportbitsCount[key],
                category: temp ? temp.category : 'N/A',
                name: temp ? temp.name : 'N/A',
                rarity: temp ? temp.rarity : 'N/A'
            });
        });
        return collection;
    },
    showOpenPack(){
        return this.isCollectionOwner();
    },
    isCollectionOwner(){
        return (this.user && this.address == this.user.addr);
    },
    selectPackToOpen(pack){
        if(this.showOpenPack()){
            this.selectedPackType = 'common';
            this.componentsPackTemp = this.components;
            this.showPackModal = true;
            this.selectedPackToOpen = pack;
        }
    },
    startPackOpening(){
        this.showPackAnimation = true;
    },
    startAirdropPackOpening(){
        this.showPackAnimation = true;
    },
    claimAirdropSportbits(){
        this.componentsPackTemp = this.components;
        window.claimSportbits();
        this.hidePackOpenBtn = true;
    },
    handlePackOpening(){
        this.showPackOpening = true;
    },
    handleInboxClaimed(){
        console.log('inbox claimed successfully!');
        this.showPackOpening = true;
        this.refreshClaimables();
    },
    hidePackOpening(){
        let self = this;
        self.fadePackOpening = true;
        setTimeout(function(){
            self.showPackAnimation = false;
            self.showPackOpening = false;
            self.fadePackOpening = false;
            self.selectedPackType = null;
            self.selectedPackToOpen = null;
        },1000);
    },
    getRarityText(sportvatar){
        return (sportvatar.rarity) ? sportvatar.rarity : '';
    },

    getRarityColor(sportvatar){
        return this.getRarityClassByName(this.getRarityText(sportvatar));
    },
    getRarityColorComponent(component){
        return (component.rarity) ? component.rarity : '';
    },
    getMaxMintable(templateId){
        let template = templateByFlowId(this.templates, templateId);
        return template ? template.max_mintable : '';
    },
    getComponentMint(component){
        let compMint = component.mint;
        let maxMint = this.getMaxMintable(component.templateId);
        return this.isFlame(component) ? '# ' + compMint : '# ' + compMint + ' / '+maxMint;
    },

    isComponentSportbit(component){
        return !this.isFlame(component);
    },
    getComponentLayer(component){
        if(this.isComponentSportbit(component)){
            return 'Category'
        }
        return component.layer;
    },
    getComponentName(component){
        return component.name;
    },
    getComponentOutlineClass(component){
        return (this.isFlame(component) || component.layer == 13) ? '' : 'builder-component-thumb'
    },
    getTemplateById(id){
        return templateByFlowId(this.templates, id);
    },

    getPackRarity(pack){
        let rarity = 'common';
        if(pack > 8620 && pack <= 13620){
            rarity = 'common';
        } else if((pack > 13620 && pack <= 14540) || (pack > 14680 && pack <= 15580) || (pack > 15630 && pack <= 15810)){
            rarity = 'rare';
        } else if((pack > 14540 && pack <= 14680) || (pack > 15580 && pack <= 15630) || (pack > 15810 && pack <= 16620)){
            rarity = 'epic';
        } else if(pack > 16620){
            rarity = 'legendary';
        }
        return rarity;
    },

    getPackImage(pack){
        return '/images/pack-'+this.getPackRarity(pack)+'.svg';
    },
    getPackName(pack) {
        let rarity = this.getPackRarity(pack);
        if (rarity == 'common' && pack < 8620) {
            return 'Genesis Free Pack';
        } else if (rarity == 'common') {
            return 'S1 Standard Pack';
        } else if (rarity == 'rare') {
            return 'S1 Rare Pack';
        } else if (rarity == 'epic') {
            return 'S1 Epic Pack';
        } else if (rarity == 'legendary') {
            return 'S1 Legendary Pack';
        }
    }
}));


window.initCollection = function(dispatch, address){
    window.dispatch = dispatch;
    if(address) {
        //window.getCollections(address);
        window.getCollectionsSportvatarIds(address);
        window.getCollectionsPack(address);
        window.getCollectionsSportvatar(address);
        window.getCollectionsSportbit(address);
    }
}
