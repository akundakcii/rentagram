@extends("backend.shared.backend_theme")
@section("title","Ürünler Modülü")
@section("subtitle","Fotoğraflar")
@section("btn_url",url("/cars/$car->car_id/images/create"))
@section("btn_label","Yeni Ekle")
@section("btn_icon","plus")
@section("content")
    <table class="table table-striped table-sm">
        <thead>
        <tr>
            <th scope="col">Sıra No</th>
            <th scope="col">Fotoğraf</th>
            <th scope="col">Açıklama</th>
            <th scope="col">Durum</th>
            <th scope="col">İşlemler</th>
        </tr>
        </thead>
        <tbody>
        @if(count($car->images) > 0)
            @foreach($car->images as $image)
                <tr id="{{$image->image_id}}">
                    <td>{{$image->seq}}</td>
                    <td>
                        @if(Str::of($image->image_url)->isNotEmpty())
                            <img src="{{asset("/storage/cars/$image->image_url")}}"
                                 alt="{{$image->alt}}"
                                 class="img-thumbnail"
                                 width="80">
                        @endif
                    </td>
                    <td>{{$image->alt}}</td>
                    <td>
                        @if($image->is_active == 1)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Pasif</span>
                        @endif
                    </td>
                    <td>
                        <ul class="nav float-start">
                            <li class="nav-item">
                                <a class="nav-link text-black"
                                   href="{{url("/cars/$car->car_id/images/$image->image_id/edit")}}">
                                    <span data-feather="edit"></span>
                                    Güncelle
                                </a>
                            </li>
                            <li class="nav-item">
                                <form onsubmit="return confirm('Bu kaydı silmek istediğinize emin misiniz?')" action="{{url("/cars/$car->car_id/images/$image->image_id")}}" method="POST">
                                    @method("DELETE")
                                    @csrf
                                    <button class="btn btn-danger" type="submit">
                                        <span data-feather="trash-2"></span>
                                        Sil
                                    </button>

                                </form>
                            </li>

                        </ul>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="5">
                    <p class="text-center">Herhangi bir kayıt bulunamadı.</p>
                </td>
            </tr>
        @endif
        </tbody>
    </table>
@endsection
