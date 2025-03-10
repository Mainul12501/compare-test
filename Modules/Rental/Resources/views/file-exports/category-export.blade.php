
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate('Category_List')}}
    </h1></div>
    <div class="col-lg-12">

    <table>
        <thead>
            <tr>
                <th>{{ translate('Filter_Criteria') }}</th>
                <th></th>
                <th>
                    {{ translate('Search_Bar_Content')  }}: {{ $data['search'] ?? translate('N/A') }}

                </th>
                <th> </th>
                </tr>


        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{ translate('Category_Name') }}</th>
            <th>{{ translate('Category_ID') }}</th>
            <th>{{ translate('Status') }}</th>

        </thead>
        <tbody>
        @foreach($data['data'] as $key => $category)
            <tr>
        <td>{{ $loop->index+1}}</td>
        <td>{{ $category->name }}</td>
        <td>{{ $category->id }}</td>
        <td>{{ $category->status == 1 ? translate('messages.Active') : translate('messages.Inactive')  }}</td>

            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
