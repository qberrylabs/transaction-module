<div class="row">
    <div class="col mb-2">
      <form id="filterTransactions" method="post" action="{{ route('transactions.filter') }}">
          @csrf
        <div class="form-row">
          <!-- Date Range
          ========================= -->
          <div class="col-sm-6 col-md-5 form-group">
            <input id="dateRange" name="date" type="text" class="form-control" placeholder="Date Range">
            <span class="icon-inside"><i class="fas fa-calendar-alt"></i></span>
          </div>
          <!-- All Filters Link
          ========================= -->

            <div class="col-auto d-flex align-items-center mr-auto form-group" data-toggle="collapse">
              <button type="submit" class="btn-link" data-toggle="collapse" aria-expanded="false" aria-controls="allFilters">
                  Filter
                </button>
            </div>
          
        </div>
      </form>
    </div>
  </div>
