import Alpine from "alpinejs";
import party from "party-js";

Alpine.data('packData', () => ({
    packData: null,
    packId: null,
    packCode: null,
    packAvailable: true,
    // free packs waiting for the connected wallet (window.checkPendingPacks -> 'free-packs-pending')
    fpCount: (window.pendingFreePacks && window.pendingFreePacks.count) || 0,
    // claim offered to this wallet (claims open + wallet type allowed, or admin preview) / "claims open soon" (e.g. Dapper)
    fpCanClaim: !!(window.pendingFreePacks && window.pendingFreePacks.canClaim),
    fpSoon: !!(window.pendingFreePacks && window.pendingFreePacks.claimsSoon),
    init(){
        let self = this;
        //window.preReservePack('terrakit');

        if(window.packClaimCode){
            this.packRedeemCode = window.packClaimCode;
        }

        this.$watch('packPurchased', () => {
            if(self.packPurchased === true){
                self.showConfetti();
            }
        });

        // v2 voucher codes do not contain a pack id: availability is checked when the code is redeemed

    },
    shotConfetti(){
        party.confetti(document.getElementById('pack-img-hero'), {
            count: party.variation.range(70, 100),
            size: party.variation.range(0.6, 1.4),
            spread: party.variation.range(10, 60),
        });
    },
    showConfetti(){
        let self = this;
        self.shotConfetti();
        setTimeout(function(){ self.shotConfetti(); }, 500);
        setTimeout(function(){ self.shotConfetti(); }, 1000);
        setTimeout(function(){ self.shotConfetti(); }, 2000);
        setTimeout(function(){ self.shotConfetti(); }, 3000);
    },
    purchasePack(){
        if(this.packData){
            window.dispatch('loading-started');
            window.buyPackV2(this.packData.id, this.packData.price)
        } else {
            reservePack('terrakit');
        }
    }
}));

window.initSales = function(dispatch){
    window.dispatch = dispatch;
}
