<select name="business_for" class="select2 change-select" data-token="{{ csrf_token() }}"
    data-url="{{ route('backend.business.update_select', ['id' => $data->id, 'action_type' => 'update-business-for']) }}"
    style="width: 100%; position: relative !important;">
    @foreach ($business_for_list as $key => $value)
        <option value="{{ $key }}" {{ $data->business_for == $key ? 'selected' : '' }}>{{ $value }}
        </option>
    @endforeach
</select>
