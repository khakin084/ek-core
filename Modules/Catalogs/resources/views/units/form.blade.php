@php $edit = isset($unit); @endphp

<form name="item-group-reg-form" action="{{ route('unit-store') }}" method="POST" class="item-group-form" autocomplete="off">
    @csrf

    <div class="modal-header">
        <h5 class="modal-title" id="item_group_form_label">Add Unit</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="modal-body">
        <div class="col-lg-12">
            <div class="card-body card-block">
                <!-- Removed the nested <form> tag – the main form already wraps everything -->
                <div class="row form-group" style="margin-bottom: 0.2rem;">
                    <div class="col col-md-3" style="padding-right: 0">
                        <label for="name" class="form-control-label">Unit Name:</label>
                    </div>
                    <div class="col col-md-9">
                        <input type="text" name="name" placeholder="Enter Unit Name" class="form-control"
                               value="{{ old('name', $edit ? $unit->name : '') }}">
                        <input type="hidden" name="id" value="{{ old('id', $edit ? $unit->id : '') }}">
                    </div>
                </div>

                <div class="row form-group" style="margin-bottom: 0.2rem;">
                    <div class="col col-md-3">
                        <label for="symbol" class="form-control-label">Symbol:</label>
                    </div>
                    <div class="col col-md-4">
                        <input type="text" name="symbol" class="form-control"
                               value="{{ old('symbol', $edit ? $unit->symbol : '') }}">
                    </div>
                </div>

                <div class="row form-group" style="margin-bottom: 0.2rem;">
                    <div class="col col-md-3">
                        <label for="descriptions" class="form-control-label">Descriptions:</label>
                    </div>
                    <div class="col col-md-9">
                        <textarea name="descriptions" rows="2" placeholder="Any descriptions..?" class="form-control">{{ old('descriptions', $edit ? $unit->descriptions : '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-primary btn-sm submit_unit">
            <i class="fa fa-save"></i> Submit
        </button>
    </div>
</form>