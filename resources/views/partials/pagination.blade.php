<div class="pagination-row">
  <div class="col hidden">
    <select class="select-outline">
      <option value="">50</option>
      <option value="">60</option>
      <option value="">70</option>
      <option value="">80</option>
    </select>
    <span>per page</span>
  </div>
  <div class="btn-col">
    <a @click.prevent.stop="loadNextPage" href="#" class="btn-outline" x-show="nextPage">Load more</a>
  </div>
  <div class="col hidden">
    <ul class="pagination">
      <li><a href="#" class="btn-outline"><i class="icon-chevron-left"></i></a></li>
      <li><input type="text" value="2"></li>
      <li><a href="#" class="btn-outline"><i class="icon-chevron-right"></i></a></li>
      <li> of </li>
      <li> 13 </li>
    </ul>
  </div>
</div>
