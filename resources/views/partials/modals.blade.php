


<div id="transaction-error-modal" x-show="showTransactionError" class="modal-wrapper jshidden">
    <div class="modal-content">
        <div class="modal-inner">
            <div class="msg-box error">
                <h2>Transaction Error</h2>
                <p>
                    Unfortunately your transaction could not be sealed and has been cancelled.<br>
                    No money or NFT has been transferred to anyone.
                </p>
                <span class="note"><button @click.prevent.stop="showTransactionErrorMessage = true" x-show="!showTransactionErrorMessage" class="underline">Click here to show the full message</button></span>

                <p x-show="showTransactionErrorMessage" x-text="transactionErrorMessage" class="error-details"></p>
                <p x-show="showTransactionErrorMessage && false" class="error-details">
                    <a x-bind:href="(txId) ? 'https://flowscan.org/transaction/'+txId : '#'" target="_blank">See transaction on Flowscan</a>
                </p>
                <div class="btn-hold">
                    <button @click.prevent.stop="showTransactionError = false" class="btn-o btn-message-cta"><span class="inner">Close</span></button>
                </div>
            </div>
        </div>
    </div>
</div>



<div id="transaction-error-modal" x-show="showMaintenance" class="modal-wrapper jshidden">
    <div class="modal-content">
        <div class="modal-inner">
            <div class="msg-box error">
                <h2>Flow Blockchain maintenance in progress</h2>
                <p>
                    Right now the Flow Blockchain is undergoing some maintenance.<br>
                    Please wait a few minuter to be able to use the Sportvatar website.<br>
                    No transaction or script can be executed at the moment since all the access nodes to the blockchain are being upgraded.<br>
                    <br>
                    Thanks for you patience!
                </p>

                <div class="btn-hold">
                    <button @click.prevent.stop="showMaintenance = false" class="btn-o btn-message-cta"><span class="inner">Close</span></button>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="account-init-modal" x-show="showInitPopup" class="modal-wrapper jshidden">
    <div class="modal-content">
        <div class="modal-inner">
            <div class="msg-box">
                <h2>{{ __('Set up your wallet for Sportvatar') }}</h2>
                <p>
                    {{ __('Your Flow Wallet does not have the Sportvatar collections yet.') }}<br>
                    {{ __('Sign one transaction to add the Sportvatar, Sportbit and Pack collections and a marketplace storefront, so that you can receive gifts and trade in FLOW.') }}
                </p>
                <div class="btn-hold">
                    <button @click.prevent.stop="startAccountInit()" class="btn btn-message-cta"><span class="inner">{{ __('Set up') }}</span></button>
                    <button @click.prevent.stop="cancelAccountInit()" class="btn-o btn-message-cta"><span class="inner">{{ __('Later') }}</span></button>
                </div>
            </div>
        </div>
    </div>
</div>
