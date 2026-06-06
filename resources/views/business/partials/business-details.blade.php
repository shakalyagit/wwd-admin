<form action="{{route('business_store')}}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Business Website</label>
                    <input type="text"
                        class="form-control"
                        name="business_website" value="{{old('business_website')}}">
                    @error('business_website')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Business name</label>
                    <input type="text" class="form-control" value="{{old('business_name')}}" name="business_name">
                    @error('business_name')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Business description</label>
                    <textarea class="form-control" name="business_description">{{old('business_description')}}</textarea>
                    @error('business_description')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Business email</label>
                    <input type="email" class="form-control" value="{{old('business_email')}}"  name="business_email">
                    @error('business_email')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Country code</label>
                    <select name="country_code" class="form-select">
                        <option value="">Select Country Code</option>
                        @if(is_array($countries))
                        @foreach($countries as $country)
                        @if(is_array($country) && !empty($country['calling_code']))
                        <option value="+{{ $country['calling_code'] }}">
                            {{ $country['name'] }} (+{{ $country['calling_code'] }})
                        </option>
                        @endif
                        @endforeach
                        @endif
                    </select>
                </div>
                @error('country_code')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Business phone</label>
                    <input type="text" value="{{old('business_phone')}}" class="form-control" name="business_phone">
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Select category</option>
                        @foreach($parents as $parent)
                        <option value="{{ $parent->category_id }}" {{ old('category_id') == $parent->category_id ? 'selected' : '' }}>{{ $parent->cat_name }}</option>
                        @foreach($children->where('parent_cat_id', $parent->category_id) as $child)
                        <option value="{{ $child->category_id }}" {{ old('category_id') == $child->category_id ? 'selected' : '' }}> {{ $parent->cat_name }} » {{ $child->cat_name }}</option>
                        @endforeach
                        @endforeach
                    </select>
                </div>
                @error('category_id')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Facebook URL</label>
                    <input type="text" value="{{old('facebook_url')}}" class="form-control" name="facebook_url">
                </div>
                @error('facebook_url')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Instagram URL</label>
                    <input type="text" value="{{old('instragram_url')}}" class="form-control" name="instragram_url">
                </div>
                @error('instragram_url')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Youtube URL</label>
                    <input type="text" value="{{old('youtube_url')}}" class="form-control" name="youtube_url">
                </div>
                @error('youtube_url')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Twitter URL</label>
                    <input type="text" value="{{old('twitter_url')}}" class="form-control" name="twitter_url">
                </div>
                @error('twitter_url')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Linkedin URL</label>
                    <input type="text" value="{{old('linkedin_url')}}" class="form-control" name="linkedin_url">
                </div>
                @error('linkedin_url')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">First name</label>
                    <input type="text" value="{{old('first_name')}}" class="form-control" name="first_name"
                        id="first_name">
                </div>
                @error('first_name')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Last name</label>
                    <input type="text" value="{{old('last_name')}}" class="form-control" name="last_name" id="last_name">
                </div>
                @error('last_name')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-12 mt-3">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</form>