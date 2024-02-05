<section class="page_section">
    <div class="row">
        <div class="col-xl-1"></div>
        <div class="col-12 col-xl-10">
            <h1 class="pagetitle">
                {{ __('home.dashboard_complete_order') }}
            </h1>
            <div>
                <div>
                    <button id="ve_freelancer_red_table_reload_button" style="display: none"></button>
                    <div class="responsive-table">
                        <table id="ve_freelancer_red_table" class="table table-striped">
                            <thead>
                                <tr>
                                    <th style="text-align:center;">
                                        {{ __('home.order_type') }}</th>
                                    <th>{{ __('home.delivery_time') }}</th>
                                    <th>{{ __('home.order') }}</th>
                                    <th>{{ __('home.date') }}</th>
                                    <th>{{ __('home.project') }}</th>
                                    <th>{{ __('home.status') }}</th>
                                    <th style="text-align:center !important;">
                                        {{ __('home.detail') }}</th>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-1"></div>
    </div>
</section>
