import Alpine from "alpinejs";

Alpine.data('getStartedData', () => ({
    curTab: 'builder',
    hasCrypto: null,
    cryptoWallet: null,
    init() {
        this.loadHashParams();
    },
    getActiveClass(item){
        return (item == this.curTab) ? 'active' : '';
    },
    getTabClass(item){
        return (item == this.curTab) ? 'active' : 'hidden';
    },
    loadHashParams(){
        if(window.location.hash && window.location.hash != '#') {
            let hash = window.location.hash;
            hash = hash.replace('#', '');
            if(hash == 'dapper' || hash=='builder'){
                window.scrollTo({top: 0, behavior: 'smooth'});
                this.curTab = hash;
                this.hasCrypto = null;
                this.cryptoWallet = null;
            }
        }
    }
}));


window.initGetStarted = function(dispatch){
    window.dispatch = dispatch;
}
