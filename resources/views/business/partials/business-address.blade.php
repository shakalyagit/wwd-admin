<form action="{{route('business_address')}}" method="POST" id="update_address"
    enctype="multipart/form-data">
    @csrf
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="form-group">
                    <label class="form-label">Address Line 1</label>
                    <input type="text" class="form-control" name="street_line_1"
                        id="street_line_1">
                </div>
            </div>
            <div class="col-md-12 mb-3">
                <div class="form-group">
                    <label class="form-label">Address Line 2</label>
                    <input type="text" class="form-control" name="street_line_2" id="street_line_2">
                </div>
            </div>
            <div class="col-md-6 md-3">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" name="city"
                        id="city">
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Province/State/Territory</label>
                    <input type="text" class="form-control" name="province_state_territory"
                        id="province_state_territory">
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Postal code</label>
                    <input type="text" class="form-control" name="postal_code"
                        id="postal_code">
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Country</label>
                    <select name="country_id" id="ref_country_id" class="form-select">
                        <option value="">Select Country</option>
                        @foreach($ref_countries as $country)
                        <option value="{{ $country->ref_countries_id }}">
                            {{ $country->printable_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12 mb-3">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</form>