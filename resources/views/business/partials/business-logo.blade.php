    <form action="{{route('business_logo')}}" method="POST" id="update_logo"
        enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="">
                        <label class="form-label">Business logo</label>
                        <input type="file" class="form-control" name="logo"
                            id="logo">
                    </div>
                    @error('logo')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-12 mb-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </form>