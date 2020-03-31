@extends('layouts.app')

@section('title', '收货地址列表')

@section('content')
  <div class="row">
    <div class="col-md-10 offset-md-1">
      <div class="card panel-default">

        <div class="card-header">
          收货地址列表
          <a href="{{ route('user_addresses.create') }}" class="float-right">新增收货地址</a>
        </div>
        <div class="card-body">
          <table class="table table-borderdb table-striped">
            <thead>
              <tr>
                <th>收货人</th>
                <th>地址</th>
                <th>邮编</th>
                <th>电话</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($addresses as $address)
              <tr>
                <td>{{ $address->contact_name }}</td>
                <td>{{ $address->full_address }}</td>
                <td>{{ $address->zip }}</td>
                <td>{{ $address->contact_phone }}</td>
                <td>
                  <a href="{{ route('user_addresses.edit', ['user_address' => $address->id]) }}" class="btn btn-primary">修改</a>
                  <!-- <form action="{{ route('user_addresses.destroy', ['user_address' => $address->id]) }}" method="POST" style="display: inline-block">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <button class="btn btn-danger" type="submit">删除</button>
                  </form> -->

                  <button class="btn btn-danger btn-del-address" type="button" data-id={{ $address->id }}>删除</button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
@stop

@section('scriptsAfterJs')
<script>
  $(document).ready(function () {
    $('.btn-del-address').click(function () {
      var id = $(this).data('id');
      swal({
        'title': '确定要删除🐴',
        'icon': 'warning',
        'buttons': ['取消', '确定'],
        dangerMode: true,
      })
      .then(function (willDelete) {
        if (!willDelete) {
          return;
        }
        axios.delete('/user_addresses/' + id).then(function () {
          location.reload();
        })
      });
    });
  });
</script>
@stop
