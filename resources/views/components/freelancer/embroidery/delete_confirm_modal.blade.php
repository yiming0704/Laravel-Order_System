<div class="modal fade" id="delete_em_request_confirm_popup" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div>
                        {{ __('home.free_delete_file_confirm') }}
                    </div>
                </div>
                <div class="row" style="text-align:center;">
                    <div>
                        <button type="button" class="modal_close_btn"
                            id="delete_em_request_confirm">{{ __('home.confirm') }}</button>
                        <button type="button" class="modal_close_btn"
                            onclick="hideAlertModal()">{{ __('home.cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
