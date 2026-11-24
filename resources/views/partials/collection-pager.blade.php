{{-- Pages of collectionData.pageSize cards for big wallets (thousands of Sportbits); hidden when everything fits on one page --}}
<div class="collection-pager" x-show="pageCount('{{ $tab }}') > 1" x-cloak style="display:flex;align-items:center;justify-content:center;gap:16px;margin:24px 0;">
    <button type="button" class="btn-outline" @click.prevent="setPage('{{ $tab }}', currentPage('{{ $tab }}') - 1, $el)" x-bind:disabled="currentPage('{{ $tab }}') <= 1">&lsaquo; {{ __('Prev') }}</button>
    <span x-text="currentPage('{{ $tab }}') + ' / ' + pageCount('{{ $tab }}') + ' (' + fullList('{{ $tab }}').length + ')'"></span>
    <button type="button" class="btn-outline" @click.prevent="setPage('{{ $tab }}', currentPage('{{ $tab }}') + 1, $el)" x-bind:disabled="currentPage('{{ $tab }}') >= pageCount('{{ $tab }}')">{{ __('Next') }} &rsaquo;</button>
</div>
