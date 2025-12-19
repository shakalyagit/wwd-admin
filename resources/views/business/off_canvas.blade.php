<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Filter risk register</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="risk_register_filter">
            <div class="mb-3 apply-button">
                <div class="text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Division</label>
                    <select name="division_id" id="division_id" class="form-select">
                        <option value="">Select division</option>
                        @if(count($divisions)>0)
                        @foreach($divisions as $division)
                        <option value="{{$division->division_id}}" {{ $division->division_id == $default_selected_division ? 'selected' : '' }}>{{$division->division_name}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Entity</label>
                    <select name="entity" id="entity" class="form-select">
                        <option value="">Select entity</option>
                        @if(count($entities)>0)
                        @foreach($entities as $entity)
                        <option value="{{$entity->entity_id}}">{{$entity->entity_name}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Sub entity</label>
                    <select name="sub_entity" id="sub_entity" class="form-select">
                        <option value="">Select sub entity</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Process</label>
                    <select name="process" id="process" class="form-select">
                        <option value="">Select process</option>
                        @if(count($processes)>0)
                        @foreach($processes as $process)
                        <option value="{{$process->process_id}}">{{$process->process_name}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Sub process</label>
                    <select name="sub_process" id="sub_process" class="form-select">
                        <option value="">Select sub process</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Risk type</label>
                    <select name="risk_type" id="risk_type" class="form-select">
                        <option value="">Select risk type</option>
                        @if(count($risk_types)>0)
                        @foreach($risk_types as $risk_type)
                        <option value="{{$risk_type->risk_type_id}}">{{$risk_type->risk_type_name}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Risk sub type</label>
                    <select name="risk_sub_type" id="risk_sub_type" class="form-select">
                        <option value="">Select risk sub type</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Risk level</label>
                    <select name="risk_level_id" id="risk_level_id" class="form-select">
                        <option value="">Select risk level</option>
                        @if(count($risk_levels)>0)
                        @foreach($risk_levels as $risk_level)
                        <option value="{{$risk_level->risk_level_id}}">{{$risk_level->level_name}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Financial year</label>
                <select name="financial_year" id="financial_year" class="form-select">
                    <option value="">Select</option>
                    @foreach($financial_years as $financial_year)
                    <option value="{{ $financial_year->financial_year_id }}" {{ $financial_year->is_default == 1 ? 'selected' : '' }}>
                        {{ $financial_year->financial_year }}
                    </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>